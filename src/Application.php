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
use Drift\Console\OutputPrinter;
use Drift\EventBus\Subscriber\EventBusSubscriber;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Context\ServerContext;
use Drift\Server\Exception\SyncKernelException;
use Drift\Server\Middleware\StreamedBodyCheckerMiddleware;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Http\Server as HttpServer;
use React\Promise\Promise;
use React\Promise\PromiseInterface;
use React\Socket\Server as SocketServer;
use Symfony\Component\Debug\Debug;

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

    /**
     * @param LoopInterface $loop
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     * @param string        $rootPath
     * @param string        $bootstrapPath
     *
     * @throws Exception
     */
    public function __construct(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        string $rootPath,
        string $bootstrapPath
    ) {
        $this->loop = $loop;
        $this->serverContext = $serverContext;
        $this->outputPrinter = $outputPrinter;
        $this->rootPath = $rootPath;
        $this->bootstrapPath = $bootstrapPath;

        ErrorHandler::handle();
        if (
            $serverContext->isDebug() &&
            class_exists('Symfony\Component\Debug\Debug')
        ) {
            umask(0000);
            Debug::enable();
        }

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
     * @return PromiseInterface
     *
     * @throws SyncKernelException
     */
    public function buildAKernel(): PromiseInterface
    {
        $kernel = $this->kernelAdapter::buildKernel(
            $this->serverContext->getEnvironment(),
            $this->serverContext->isDebug()
        );

        if (!$kernel instanceof AsyncKernel) {
            throw SyncKernelException::build();
        }

        $kernel->boot();
        $kernel
            ->getContainer()
            ->set('reactphp.event_loop', $this->loop);

        return $kernel
            ->preload()
            ->then(function () use ($kernel) {
                return $kernel;
            });
    }

    /**
     * @param AsyncKernel         $kernel
     * @param RequestHandler      $requestHandler
     * @param FilesystemInterface $filesystem
     * @param bool                $forceShutdownReference
     */
    public function run(
        AsyncKernel $kernel,
        RequestHandler $requestHandler,
        FilesystemInterface $filesystem,
        bool &$forceShutdownReference
    ) {
        $this->runServer(
            $kernel,
            $requestHandler,
            $filesystem,
            $forceShutdownReference
        );

        $container = $kernel->getContainer();
        $serverContext = $this->serverContext;

        if (
            $serverContext->hasExchanges() &&
            $container->has(EventBusSubscriber::class)
        ) {
            $eventBusSubscriber = $container->get(EventBusSubscriber::class);
            $eventBusSubscriber->subscribeToExchanges(
                $serverContext->getExchanges(),
                $this->outputPrinter
            );
        }
    }

    /**
     * @param AsyncKernel         $kernel
     * @param RequestHandler      $requestHandler
     * @param FilesystemInterface $filesystem
     * @param bool                $forceShutdownReference
     */
    public function runServer(
        AsyncKernel $kernel,
        RequestHandler $requestHandler,
        FilesystemInterface $filesystem,
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
            function (ServerRequestInterface $request) use ($kernel, $requestHandler, $filesystem) {
                return new Promise(function (callable $resolve) use ($request, $kernel, $requestHandler, $filesystem) {
                    $resolveResponseCallback = function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolve) {
                        if (!$this->serverContext->isSilent()) {
                            $serverResponseWithMessage->printMessage();
                        }

                        return $resolve($serverResponseWithMessage->getServerResponse());
                    };

                    $uriPath = $request->getUri()->getPath();
                    $uriPath = '/'.ltrim($uriPath, '/');

                    $staticFolder = $this->serverContext->getStaticFolder();

                    return $staticFolder && (0 === strpos($uriPath, $staticFolder))

                        ? $requestHandler
                            ->handleStaticResource(
                                $request,
                                $filesystem,
                                $this->rootPath,
                                $uriPath
                            )
                            ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                                $resolveResponseCallback($serverResponseWithMessage);
                            })
                        : $requestHandler
                            ->handleAsyncServerRequest($kernel, $request)
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

        $signalHandler = function () use (&$signalHandler, $socket, $kernel, &$forceShutdownReference) {
            $loop = $this->loop;
            $loop->removeSignal(SIGTERM, $signalHandler);
            $loop->removeSignal(SIGINT, $signalHandler);
            $socket->close();
            $forceShutdownReference = true;
            await($kernel->shutdown(), $loop);
        };

        $this->loop->addSignal(SIGTERM, $signalHandler);
        $this->loop->addSignal(SIGINT, $signalHandler);
    }
}
