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

namespace Drift\Server;

/*
 * This file is part of the {Package name}.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

use Drift\Console\OutputPrinter;
use Drift\Console\TimeFormatter;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Mime\MimeTypeChecker;
use function React\Promise\all;
use function React\Promise\resolve;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface as PsrUploadedFile;
use React\Filesystem\FilesystemInterface;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;

/**
 * Class RequestHandler.
 */
class RequestHandler
{
    /**
     * @var OutputPrinter
     */
    private $outputPrinter;

    /**
     * @var MimeTypeChecker
     */
    private $mimetypeChecker;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * RequestHandler constructor.
     *
     * @param OutputPrinter       $outputPrinter
     * @param MimeTypeChecker     $mimetypeChecker
     * @param FilesystemInterface $filesystem
     */
    public function __construct(
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimetypeChecker,
        FilesystemInterface $filesystem
    ) {
        $this->outputPrinter = $outputPrinter;
        $this->mimetypeChecker = $mimetypeChecker;
        $this->filesystem = $filesystem;
    }

    /**
     * Handle server request and return response.
     *
     * Return an array of an instance of ResponseInterface and an array of
     * Printable instances
     *
     * @param AsyncKernel            $kernel
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface <ServerResponseWithMessage>
     */
    public function handleAsyncServerRequest(
        AsyncKernel $kernel,
        ServerRequestInterface $request
    ): PromiseInterface {
        $from = microtime(true);
        $uriPath = $request->getUri()->getPath();
        $method = $request->getMethod();

        return
            $this->toSymfonyRequest(
                $request,
                $method,
                $uriPath
            )
            ->then(function (Request $symfonyRequest) use ($kernel, $from, $uriPath, $method) {
                return all([
                    resolve($symfonyRequest),
                    $kernel->handleAsync($symfonyRequest),
                ])
                ->then(function (array $parts) use ($kernel) {
                    list($symfonyRequest, $symfonyResponse) = $parts;
                    $kernel->terminate($symfonyRequest, $symfonyResponse);

                    return $parts;
                })
                ->then(function (array $parts) use ($from) {
                    list($symfonyRequest, $symfonyResponse) = $parts;

                    /*
                     * We don't have to wait to this clean
                     */
                    $this->cleanTemporaryUploadedFiles($symfonyRequest);

                    return $this->toServerResponse(
                        $symfonyRequest,
                        $symfonyResponse,
                        $from
                    );
                }, function (\Throwable $exception) use ($from, $method, $uriPath) {
                    return $this->createExceptionServerResponse(
                        $exception,
                        $from,
                        $method,
                        $uriPath
                    );
                });
            });
    }

    /**
     * Handle static resource.
     *
     * @param FilesystemInterface $filesystem
     * @param string              $rootPath
     * @param string              $resourcePath
     *
     * @return PromiseInterface
     */
    public function handleStaticResource(
        FilesystemInterface $filesystem,
        string $rootPath,
        string $resourcePath
    ): PromiseInterface {
        $from = microtime(true);

        return $filesystem
            ->getContents($rootPath.$resourcePath)
            ->then(function ($content) use ($rootPath, $resourcePath, $from) {
                $to = microtime(true);
                $mimeType = $this
                    ->mimetypeChecker
                    ->getMimeType($rootPath.$resourcePath);

                return new ServerResponseWithMessage(
                    new \React\Http\Response(
                        Response::HTTP_OK,
                        ['Content-Type' => $mimeType],
                        $content
                    ),
                    $this->outputPrinter,
                    new ConsoleStaticMessage(
                        $resourcePath,
                        TimeFormatter::formatTime($to - $from)
                    )
                );
            }, function (Throwable $exception) use ($resourcePath, $from) {
                $to = microtime(true);

                return new ServerResponseWithMessage(
                    new \React\Http\Response(
                        Response::HTTP_NOT_FOUND,
                        [],
                        ''
                    ),
                    $this->outputPrinter,
                    new ConsoleRequestMessage(
                        $resourcePath,
                        'GET',
                        404,
                        sprintf('Resource %s not found', $resourcePath),
                        TimeFormatter::formatTime($to - $from)
                    )
                );
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
        $uploadedFiles = array_map(function (PsrUploadedFile $file) {
            return $this->toSymfonyUploadedFile($file);
        }, $request->getUploadedFiles());

        return all($uploadedFiles)
            ->then(function (array $uploadedFiles) use ($request, $method, $uriPath) {
                $body = $request->getBody()->getContents();
                $headers = $request->getHeaders();

                $symfonyRequest = new Request(
                    $request->getQueryParams(),
                    $request->getParsedBody() ?? [],
                    $request->getAttributes(),
                    $request->getCookieParams(),
                    $uploadedFiles,
                    $_SERVER,
                    $body
                );

                $symfonyRequest->setMethod($method);
                $symfonyRequest->headers->replace($headers);
                $symfonyRequest->server->set('REQUEST_URI', $uriPath);

                if (isset($headers['Host'])) {
                    $symfonyRequest->server->set('SERVER_NAME', explode(':', $headers['Host'][0]));
                }

                return $symfonyRequest;
            });
    }

    /**
     * Symfony Response to http response.
     *
     * @param Request  $symfonyRequest
     * @param Response $symfonyResponse
     * @param float    $from
     *
     * @return ServerResponseWithMessage
     */
    private function toServerResponse(
        Request $symfonyRequest,
        Response $symfonyResponse,
        float $from
    ): ServerResponseWithMessage {
        $to = microtime(true);

        $nonEncodedContent = $symfonyResponse->getContent();
        $this->applyResponseEncoding(
            $symfonyRequest,
            $symfonyResponse
        );

        if ($symfonyResponse->getStatusCode() >= 400) {
            $nonEncodedContent = 'Error returned';
            if (404 == $symfonyResponse->getStatusCode()) {
                $nonEncodedContent = 'Route not found';
            }
        }

        $serverResponse =
            new ServerResponseWithMessage(
                new \React\Http\Response(
                    $symfonyResponse->getStatusCode(),
                    $symfonyResponse->headers->all(),
                    $symfonyResponse->getContent()
                ),
                $this->outputPrinter,
                new ConsoleRequestMessage(
                    $symfonyRequest->getPathInfo(),
                    $symfonyRequest->getMethod(),
                    $symfonyResponse->getStatusCode(),
                    $nonEncodedContent,
                    TimeFormatter::formatTime($to - $from)
                )
            );

        $symfonyRequest = null;
        $symfonyResponse = null;

        return $serverResponse;
    }

    /**
     * Create exception Server response.
     *
     * @param Throwable $exception
     * @param float     $from
     * @param string    $method
     * @param string    $uriPath
     *
     * @return ServerResponseWithMessage
     */
    private function createExceptionServerResponse(
        Throwable $exception,
        float $from,
        string $method,
        string $uriPath
    ): ServerResponseWithMessage {
        $to = microtime(true);
        $code = 400;
        $message = $exception->getMessage();

        if ($exception instanceof RouteNotFoundException) {
            $code = 404;
            $message = sprintf('Route %s not found', $uriPath);
        }

        $serverResponse =
            new ServerResponseWithMessage(
                new \React\Http\Response(
                    $code,
                    ['Content-Type' => 'text/plain'],
                    $exception->getMessage()
                ),
                $this->outputPrinter,
                new ConsoleRequestMessage(
                    $uriPath,
                    $method,
                    $code,
                    $message,
                    TimeFormatter::formatTime($to - $from)
                )
            );

        $symfonyRequest = null;
        $symfonyResponse = null;

        return $serverResponse;
    }

    /**
     * Apply response encoding.
     *
     * @param Request  $request
     * @param Response $response
     */
    private function applyResponseEncoding(
        Request $request,
        Response $response
    ) {
        $allowedCompressionAsString = $request
            ->headers
            ->get('Accept-Encoding');

        if (!$allowedCompressionAsString) {
            return;
        }
        $allowedCompression = explode(',', $allowedCompressionAsString);
        $allowedCompression = array_map('trim', $allowedCompression);
        if (in_array('gzip', $allowedCompression)) {
            $response->setContent(gzencode($response->getContent()));
            $response
                ->headers
                ->set('Content-Encoding', 'gzip');

            return;
        }
        if (in_array('deflate', $allowedCompression)) {
            $response->setContent(gzdeflate($response->getContent()));
            $response
                ->headers
                ->set('Content-Encoding', 'deflate');

            return;
        }
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
        $extension = $this->mimetypeChecker->getExtension($filename);
        $tmpFilename = sys_get_temp_dir().'/'.md5(uniqid((string) rand(), true)).'.'.$extension;

        $content = $file
            ->getStream()
            ->getContents();

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
     * Clean tmp files.
     *
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
}
