<?php

/*
 * This file is part of the Drift Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server\Adapter;

use Drift\Console\OutputPrinter;
use Drift\EventBus\Subscriber\EventBusSubscriber;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Context\ServerContext;
use Drift\Server\Exception\KernelException;
use Drift\Server\Exception\RouteNotFoundException;
use Drift\Server\Mime\MimeTypeChecker;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface as PsrUploadedFile;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Http\Message\Response as ReactResponse;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Exception\RouteNotFoundException as SymfonyRouteNotFoundException;
use Throwable;

/**
 * Class SymfonyKernelBasedAdapter.
 */
abstract class SymfonyKernelBasedAdapter implements KernelAdapter
{
    protected Kernel $kernel;
    private FilesystemInterface $filesystem;
    private ServerContext $serverContext;
    private MimeTypeChecker $mimeTypeChecker;
    private string $rootPath;

    /**
     * @param string $environment
     * @param bool   $debug
     *
     * @return AsyncKernel
     */
    abstract protected static function createKernelByEnvironmentAndDebug(
        string $environment,
        bool $debug
    ): AsyncKernel;

    /**
     * @param $kernel
     *
     * @throws Exception
     */
    abstract protected function checkKernel($kernel);

    /**
     * @param Kernel  $kernel
     * @param Request $request
     *
     * @return PromiseInterface
     */
    abstract protected function kernelHandle(
        Kernel $kernel,
        Request $request
    ): PromiseInterface;

    /**
     * @param LoopInterface       $loop
     * @param string              $rootPath
     * @param ServerContext       $serverContext
     * @param FilesystemInterface $filesystem
     * @param OutputPrinter       $outputPrinter
     * @param MimeTypeChecker     $mimeTypeChecker
     *
     * @return PromiseInterface<self>
     *
     * @throws KernelException
     */
    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        FilesystemInterface $filesystem,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker
    ): PromiseInterface {
        $adapter = new static();
        $kernel = static::createKernelByEnvironmentAndDebug($serverContext->getEnvironment(), $serverContext->isDebug());

        $kernel->boot();
        $kernel
            ->getContainer()
            ->set('reactphp.event_loop', $loop);

        $adapter->kernel = $kernel;
        $adapter->serverContext = $serverContext;
        $adapter->filesystem = $filesystem;
        $adapter->mimeTypeChecker = $mimeTypeChecker;
        $adapter->rootPath = $rootPath;

        return $kernel
            ->preload()
            ->then(function () use ($adapter, $outputPrinter) {
                $container = $adapter->kernel->getContainer();
                $serverContext = $adapter->serverContext;

                if (
                    $serverContext->hasExchanges() &&
                    $container->has(EventBusSubscriber::class)
                ) {
                    $eventBusSubscriber = $container->get(EventBusSubscriber::class);
                    $eventBusSubscriber->subscribeToExchanges(
                        $serverContext->getExchanges(),
                        $outputPrinter
                    );
                }
            })
            ->then(function () use ($adapter) {
                return $adapter;
            });
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function handle(ServerRequestInterface $request): PromiseInterface
    {
        $uriPath = $request->getUri()->getPath();
        $method = $request->getMethod();

        return
            $this->toSymfonyRequest(
                $request,
                $method,
                $uriPath
            )
                ->then(function (Request $symfonyRequest) use ($request) {
                    return all([
                        resolve($symfonyRequest),
                        $this->kernelHandle($this->kernel, $symfonyRequest),
                    ])
                        ->otherwise(function (SymfonyRouteNotFoundException $symfonyRouteNotFoundException) {
                            throw new RouteNotFoundException($symfonyRouteNotFoundException->getMessage());
                        })
                        ->then(function (array $parts) use ($request) {
                            list($symfonyRequest, $symfonyResponse) = $parts;

                            /*
                             * We don't have to wait to this clean
                             */
                            $this->cleanTemporaryUploadedFiles($symfonyRequest);
                            $symfonyRequest = null;

                            $response = $symfonyResponse;
                            if ($response instanceof Response) {
                                $response = new ReactResponse(
                                    $response->getStatusCode(),
                                    $response->headers->all(),
                                    $response->getContent()
                                );
                            }

                            return $response;
                        });
                });
    }

    /**
     * Http request to symfony request.
     *
     * @param ServerRequestInterface $request
     * @param string                 $method
     * @param string                 $uriPath
     *
     * @return PromiseInterface<Request>
     */
    private function toSymfonyRequest(
        ServerRequestInterface $request,
        string $method,
        string $uriPath
    ): PromiseInterface {
        $allowFileUploads = !$this
            ->serverContext
            ->areFileUploadsDisabled();

        $uploadedFiles = $allowFileUploads
            ? array_map(function (PsrUploadedFile $file) {
                return $this->toSymfonyUploadedFile($file);
            }, $request->getUploadedFiles())
            : [];

        return all($uploadedFiles)
            ->then(function (array $uploadedFiles) use ($request, $method, $uriPath) {
                $uploadedFiles = array_filter($uploadedFiles);
                $headers = $request->getHeaders();
                $isNotTransferEncoding = !array_key_exists('Transfer-Encoding', $headers);

                $bodyParsed = [];
                $bodyContent = '';
                if ($isNotTransferEncoding) {
                    $bodyParsed = $request->getParsedBody() ?? [];
                    $bodyContent = $request->getBody()->getContents();
                }

                $symfonyRequest = new Request(
                    $request->getQueryParams(),
                    $bodyParsed,
                    $request->getAttributes(),
                    $this->serverContext->areCookiesDisabled()
                        ? []
                        : $request->getCookieParams(),
                    $uploadedFiles,
                    $_SERVER,
                    $bodyContent
                );

                $symfonyRequest->setMethod($method);
                $symfonyRequest->headers->replace($headers);

                $symfonyRequest->server->replace(
                    $request->getServerParams()
                    + ['REQUEST_URI' => $uriPath]
                    + $symfonyRequest->server->all()
                );

                if ($symfonyRequest->headers->has('authorization') &&
                    0 === stripos($symfonyRequest->headers->get('authorization'), 'basic ')) {
                    $exploded = explode(':', base64_decode(substr($symfonyRequest->headers->get('authorization'), 6)), 2);
                    if (2 == \count($exploded)) {
                        list($basicAuthUsername, $basicAuthPassword) = $exploded;
                        $symfonyRequest->headers->set('PHP_AUTH_USER', $basicAuthUsername);
                        $symfonyRequest->headers->set('PHP_AUTH_PW', $basicAuthPassword);
                    }
                }

                $symfonyRequest->attributes->set('body', $request->getBody());

                if (isset($headers['Host'])) {
                    $symfonyRequest->server->set('SERVER_NAME', explode(':', $headers['Host'][0]));
                }

                return $symfonyRequest;
            });
    }

    /**
     * PSR Uploaded file to Symfony file.
     *
     * @param PsrUploadedFile $file
     *
     * @return PromiseInterface<SymfonyUploadedFile>
     */
    private function toSymfonyUploadedFile(PsrUploadedFile $file): PromiseInterface
    {
        if (UPLOAD_ERR_NO_FILE == $file->getError()) {
            return resolve(new SymfonyUploadedFile(
                '',
                $file->getClientFilename(),
                $file->getClientMediaType(),
                $file->getError(),
                true
            ));
        }

        $filename = $file->getClientFilename();
        $extension = $this->mimeTypeChecker->getExtension($filename);
        $tmpFilename = sys_get_temp_dir().'/'.md5(uniqid((string) rand(), true)).'.'.$extension;

        try {
            $content = $file
                ->getStream()
                ->getContents();
        } catch (Throwable $throwable) {
            return resolve(false);
        }

        $promise = (UPLOAD_ERR_OK == $file->getError())
            ? $this
                ->filesystem
                ->file($tmpFilename)
                ->putContents($content)
            : resolve();

        return $promise
            ->then(function () use ($file, $tmpFilename, $filename) {
                return new SymfonyUploadedFile(
                    $tmpFilename,
                    $filename,
                    $file->getClientMediaType(),
                    $file->getError(),
                    true
                );
            });
    }

    /**
     * @param Request $request
     *
     * @return PromiseInterface[]
     */
    private function cleanTemporaryUploadedFiles(Request $request): array
    {
        return array_map(function (SymfonyUploadedFile $file) {
            return $this
                ->filesystem
                ->file($file->getPath().'/'.$file->getFilename())
                ->remove();
        }, $request->files->all());
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return ['php', 'yml', 'yaml', 'xml', 'css', 'js', 'html', 'twig'];
    }

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return [];
    }

    /**
     * Get static folder by kernel.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string
    {
        return '/public';
    }
}
