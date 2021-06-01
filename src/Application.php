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

use function Clue\React\Block\await;
use Clue\React\Zlib\Compressor;
use Drift\Console\OutputPrinter;
use Drift\Console\TimeFormatter;
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Context\ServerContext;
use Drift\Server\Exception\RouteNotFoundException;
use Drift\Server\Middleware\StreamedBodyCheckerMiddleware;
use Drift\Server\Mime\MimeTypeChecker;
use Exception;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Http\Message\Response as ReactResponse;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Http\Server as HttpServer;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use function React\Promise\reject;
use function React\Promise\resolve;
use React\Socket\Server as SocketServer;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use Throwable;

/**
 * Class Application.
 */
class Application
{
    private LoopInterface $loop;
    private ServerContext $serverContext;
    private string $rootPath;
    private string $bootstrapPath;
    private string $kernelAdapter;
    private OutputPrinter $outputPrinter;
    private MimeTypeChecker $mimeTypeChecker;

    /**
     * @param LoopInterface   $loop
     * @param ServerContext   $serverContext
     * @param OutputPrinter   $outputPrinter
     * @param MimeTypeChecker $mimeTypeChecker
     * @param string          $rootPath
     * @param string          $bootstrapPath
     *
     * @throws Exception
     */
    public function __construct(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker,
        string $rootPath,
        string $bootstrapPath
    ) {
        $this->loop = $loop;
        $this->serverContext = $serverContext;
        $this->outputPrinter = $outputPrinter;
        $this->rootPath = $rootPath;
        $this->bootstrapPath = $bootstrapPath;
        $this->mimeTypeChecker = $mimeTypeChecker;

        ErrorHandler::handle();

        /*
         * @var KernelAdapter
         */
        $this->kernelAdapter = $serverContext->getAdapter();
    }

    /**
     * @return string
     */
    public function getKernelAdapter(): string
    {
        return $this->kernelAdapter;
    }

    /**
     * @param KernelAdapter        $kernelAdapter
     * @param ?FilesystemInterface $filesystem
     * @param bool                 $forceShutdownReference
     */
    public function runServer(
        KernelAdapter $kernelAdapter,
        ?FilesystemInterface $filesystem,
        bool &$forceShutdownReference
    ) {
        $socket = new SocketServer(
            $this->serverContext->getHost().':'.
            $this->serverContext->getPort(),
            $this->loop,
            ['tcp' => ['so_reuseport' => ($this->serverContext->getWorkers() > 1)]]
        );

        $http = new HttpServer(
            $this->loop,
            new StreamingRequestMiddleware(),
            new StreamedBodyCheckerMiddleware($this->serverContext->getRequestBodyBufferInBytes()),
            new LimitConcurrentRequestsMiddleware($this->serverContext->getLimitConcurrentRequests()),
            function (ServerRequestInterface $request) use ($kernelAdapter, $filesystem) {
                return new Promise(function (callable $resolve) use ($request, $kernelAdapter, $filesystem) {
                    $from = microtime(true);
                    $resolveResponseCallback = function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolve) {
                        if (!$this->serverContext->isQuiet()) {
                            $serverResponseWithMessage->printMessage();
                        }

                        return $resolve($serverResponseWithMessage->getServerResponse());
                    };

                    $uriPath = $request->getUri()->getPath();
                    $uriPath = '/'.ltrim($uriPath, '/');
                    $staticFolder = $this->serverContext->getStaticFolder();

                    if ($staticFolder && (0 === strpos($uriPath, $staticFolder[0]))) {
                        if (!$staticFolder[2]) {
                            $uriPath = str_replace($staticFolder[0], $staticFolder[1], $uriPath);
                        }

                        return $this
                            ->handleStaticResource(
                                $request,
                                $filesystem,
                                $this->rootPath,
                                $uriPath
                            )
                            ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                                $resolveResponseCallback($serverResponseWithMessage);
                            });
                    }

                    return $kernelAdapter->handle($request)
                        ->then(function (ResponseInterface $response) use ($from, $request) {
                            return $this->toServerResponse(
                                $request,
                                $response,
                                $from
                            );
                        })
                        ->otherwise(function (Throwable $throwable) use ($from, $request, $uriPath) {
                            return $this->createExceptionServerResponse(
                                $throwable,
                                $from,
                                $request->getMethod(),
                                $uriPath
                            );
                        })
                        ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                            $resolveResponseCallback($serverResponseWithMessage);
                        });
                });
            }
        );

        $http->on('error', function (\Throwable $e) {
            (new ConsoleRequestMessage('/', 'EXC', 500, $e->getMessage(), ''))->print($this->outputPrinter);
        });

        $http->listen($socket);

        $signalHandler = function () use (&$signalHandler, $socket, $kernelAdapter, &$forceShutdownReference) {
            $loop = $this->loop;
            $loop->removeSignal(SIGTERM, $signalHandler);
            $loop->removeSignal(SIGINT, $signalHandler);
            $socket->close();
            $forceShutdownReference = true;
            await($kernelAdapter->shutdown(), $loop);
        };

        $this->loop->addSignal(SIGTERM, $signalHandler);
        $this->loop->addSignal(SIGINT, $signalHandler);
    }

    /**
     * Response to http response.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param float             $from
     *
     * @return PromiseInterface<ServerResponseWithMessage>
     */
    private function toServerResponse(
        RequestInterface $request,
        ResponseInterface $response,
        float $from
    ): PromiseInterface {
        return $this
            ->applyResponseEncoding($response, $request->getHeaderLine('Accept-encoding'))
            ->then(function (ResponseInterface $response) use ($request, $from) {
                $to = microtime(true);
                $responseMessage = '';
                if ($response->hasHeader('x-server-message')) {
                    $responseMessage = $response->getHeader('x-server-message');
                    $responseMessage = ($responseMessage[0] ?? '') ? \strval($responseMessage[0]) : '';
                    $response = $response->withoutHeader('x-server-message');
                }

                $serverResponse =
                    new ServerResponseWithMessage(
                        $response,
                        $this->outputPrinter,
                        new ConsoleRequestMessage(
                            $request->getUri()->getPath(),
                            $request->getMethod(),
                            $response->getStatusCode(),
                            $responseMessage,
                            TimeFormatter::formatTime($to - $from)
                        )
                    );

                $request = null;
                $response = null;

                return $serverResponse;
            });
    }

    /**
     * @param ResponseInterface $response
     * @param string|null       $acceptEncodingHeader
     *
     * @return PromiseInterface
     */
    private function applyResponseEncoding(
        ResponseInterface $response,
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
     * @param ResponseInterface $response
     * @param string            $compression
     *
     * @return PromiseInterface
     */
    private function compressResponse(
        ResponseInterface $response,
        string $compression
    ): PromiseInterface {
        $body = $response->getBody();
        if ($response->hasHeader('Content-Encoding')) {
            return resolve($response);
        }

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
     * @param ServerRequestInterface   $request
     * @param FilesystemInterface|null $filesystem
     * @param string                   $rootPath
     * @param string                   $resourcePath
     *
     * @return PromiseInterface
     */
    public function handleStaticResource(
        ServerRequestInterface $request,
        ?FilesystemInterface $filesystem,
        string $rootPath,
        string $resourcePath
    ): PromiseInterface {
        $from = microtime(true);
        $fileFullPath = $rootPath.$resourcePath;

        if (is_null($filesystem)) {
            try {
                $content = file_get_contents($fileFullPath);
                $content = false === $content
                    ? reject(new Exception('File not found'))
                    : resolve(file_get_contents($fileFullPath));
            } catch (Exception $exception) {
                $content = reject($exception);
            }
        } else {
            $file = $filesystem->file($rootPath.$resourcePath);
            $content = $file
                ->exists()
                ->then(function () use ($file) {
                    return $file->getContents();
                });
        }

        return $content
            ->then(function (string $content) use ($rootPath, $resourcePath, $from, $request) {
                $mimeType = $this
                    ->mimeTypeChecker
                    ->getMimeType($resourcePath);

                $response = new ReactResponse(
                    200,
                    ['Content-Type' => $mimeType],
                    $content
                );

                return $this
                    ->applyResponseEncoding($response, $request->getHeaderLine('Accept-Encoding'))
                    ->then(function (ResponseInterface $response) use ($resourcePath, $from) {
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
                        404,
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

        return new ServerResponseWithMessage(
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
    }
}
