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

use Clue\React\Zlib\Compressor;
use Drift\Console\OutputPrinter;
use Drift\Console\TimeFormatter;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Context\ServerContext;
use Drift\Server\Mime\MimeTypeChecker;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface as PsrUploadedFile;
use React\Filesystem\FilesystemInterface;
use React\Http\Message\Response as ReactResponse;
use function React\Promise\all;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use RingCentral\Psr7\Response as PSRResponse;
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
     * @var ServerContext
     */
    private $serverContext;

    /**
     * @param OutputPrinter       $outputPrinter
     * @param MimeTypeChecker     $mimetypeChecker
     * @param FilesystemInterface $filesystem
     * @param ServerContext       $serverContext
     */
    public function __construct(
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimetypeChecker,
        FilesystemInterface $filesystem,
        ServerContext $serverContext
    ) {
        $this->outputPrinter = $outputPrinter;
        $this->mimetypeChecker = $mimetypeChecker;
        $this->filesystem = $filesystem;
        $this->serverContext = $serverContext;
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
     * @param ServerRequestInterface $request
     * @param FilesystemInterface    $filesystem
     * @param string                 $rootPath
     * @param string                 $resourcePath
     *
     * @return PromiseInterface
     */
    public function handleStaticResource(
        ServerRequestInterface $request,
        FilesystemInterface $filesystem,
        string $rootPath,
        string $resourcePath
    ): PromiseInterface {
        $from = microtime(true);

        return $filesystem
            ->file($rootPath.$resourcePath)
            ->open('r')
            ->then(function (ReadableStreamInterface $stream) use ($rootPath, $resourcePath, $from, $request) {
                $mimeType = $this
                    ->mimetypeChecker
                    ->getMimeType($resourcePath);

                $response = new ReactResponse(
                    Response::HTTP_OK,
                    ['Content-Type' => $mimeType],
                    $stream
                );

                return $this
                    ->applyResponseEncoding($response, $request->getHeaderLine('Accept-Encoding'))
                    ->then(function (PSRResponse $response) use ($resourcePath, $from) {
                        $to = microtime(true);

                        return new ServerResponseWithMessage(
                            $response,
                            $this->outputPrinter,
                            new ConsoleStaticMessage(
                                $resourcePath,
                                TimeFormatter::formatTime($to - $from)
                            )
                        );
                    });
            })
            ->otherwise(function (Throwable $exception) use ($resourcePath, $from) {
                $to = microtime(true);

                return new ServerResponseWithMessage(
                    new ReactResponse(
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

                $server = ['REQUEST_URI', $uriPath] + $request->getServerParams() + $_SERVER;
                if (isset($headers['Host'])) {
                    $server['SERVER_NAME'] = explode(':', $headers['Host'][0]);
                }

                $symfonyRequest = new Request(
                    $request->getQueryParams(),
                    $bodyParsed,
                    $request->getAttributes(),
                    $this->serverContext->areCookiesDisabled()
                        ? []
                        : $request->getCookieParams(),
                    $uploadedFiles,
                    $server,
                    $bodyContent
                );

                $symfonyRequest->setMethod($method);
                $symfonyRequest->headers->replace($headers);
                $symfonyRequest->attributes->set('body', $request->getBody());

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
     * @return PromiseInterface<ServerResponseWithMessage>
     */
    private function toServerResponse(
        Request $symfonyRequest,
        $response,
        float $from
    ): PromiseInterface {
        if ($response instanceof Response) {
            $response = new PSRResponse(
                $response->getStatusCode(),
                $response->headers->all(),
                $response->getContent()
            );
        }

        return $this
            ->applyResponseEncoding($response, $symfonyRequest->headers->get('Accept-Encoding'))
            ->then(function (PSRResponse $response) use ($symfonyRequest, $from) {
                $to = microtime(true);
                $serverResponse =
                    new ServerResponseWithMessage(
                        $response,
                        $this->outputPrinter,
                        new ConsoleRequestMessage(
                            $symfonyRequest->getPathInfo(),
                            $symfonyRequest->getMethod(),
                            $response->getStatusCode(),
                            '',
                            TimeFormatter::formatTime($to - $from)
                        )
                    );

                $symfonyRequest = null;
                $symfonyResponse = null;

                return $serverResponse;
            });
    }

    /**
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
                new ReactResponse(
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
     * @param PSRResponse $response
     * @param string|null $acceptEncodingHeader
     *
     * @return PromiseInterface
     */
    private function applyResponseEncoding(
        PSRResponse $response,
        ?string $acceptEncodingHeader
    ): PromiseInterface {
        if (!$acceptEncodingHeader) {
            return resolve($response);
        }

        $allowedCompression = explode(',', $acceptEncodingHeader);
        $allowedCompression = array_map('trim', $allowedCompression);
        if (in_array('gzip', $allowedCompression)) {
            return $this->compressResponse($response, 'gzip');
        }

        if (in_array('deflate', $allowedCompression)) {
            return $this->compressResponse($response, 'deflate');
        }

        return resolve($response);
    }

    /**
     * @param PSRResponse $response
     * @param string      $compression
     *
     * @return PromiseInterface
     */
    private function compressResponse(
        PSRResponse $response,
        string $compression
    ): PromiseInterface {
        $body = $response->getBody();
        $response = $response->withHeader('Content-Encoding', $compression);

        if ($body instanceof ReadableStreamInterface) {
            $compressedStream = new ThroughStream();
            $compressionStrategy = 'gzip' === $compression
                ? ZLIB_ENCODING_GZIP
                : ZLIB_ENCODING_RAW;
            $compressor = new Compressor($compressionStrategy);
            $body->pipe($compressor)->pipe($compressedStream);
            $compressedStream->on('close', function () use ($body, $compressor) {
                $compressor->close();
                $body->close();
            });

            return resolve(new ReactResponse(
                $response->getStatusCode(),
                $response->getHeaders(),
                $compressedStream
            ));
        }

        if ($body instanceof StreamInterface) {
            $compressionMethod = 'gzip' === $compression
                ? 'gzencode'
                : 'gzdeflate';
            $content = $body->getContents();

            return resolve(new ReactResponse(
                $response->getStatusCode(),
                $response->getHeaders(),
                $compressionMethod($content)
            ));
        }

        return resolve($response);
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
}
