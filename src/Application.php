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

use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Context\ServerContext;
use Drift\Server\Output\OutputPrinter;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;
use React\Http\Server as HttpServer;
use React\Promise\Promise;
use React\Socket\Server as SocketServer;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class Application.
 */
class Application
{
    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var RequestHandler
     */
    private $requestHandler;

    /**
     * @var ServerContext
     */
    private $serverContext;

    /**
     * @var string
     */
    private $rootPath;

    /**
     * @var string
     */
    private $bootstrapPath;

    /**
     * @var Kernel
     *
     * Kernel
     */
    private $kernel;

    /**
     * @var SocketServer
     */
    private $socket;

    /**
     * @var FilesystemInterface
     */
    private $filesystem;

    /**
     * @var OutputPrinter
     */
    private $outputPrinter;

    /**
     * Application constructor.
     *
     * @param LoopInterface       $loop
     * @param RequestHandler      $requestHandler
     * @param FilesystemInterface $filesystem
     * @param ServerContext       $serverContext
     * @param string              $rootPath
     * @param string              $bootstrapPath
     *
     * @throws Exception
     */
    public function __construct(
        LoopInterface $loop,
        RequestHandler $requestHandler,
        FilesystemInterface $filesystem,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        string $rootPath,
        string $bootstrapPath
    ) {
        $this->loop = $loop;
        $this->requestHandler = $requestHandler;
        $this->filesystem = $filesystem;
        $this->serverContext = $serverContext;
        $this->outputPrinter = $outputPrinter;
        $this->rootPath = $rootPath;
        $this->bootstrapPath = $bootstrapPath;

        ErrorHandler::handle();
        if ($serverContext->isDebug()) {
            umask(0000);
            Debug::enable();
        }

        /**
         * @var KernelAdapter
         */
        $adapter = $serverContext->getAdapter();
        $this->kernel = $adapter::buildKernel(
            $serverContext->getEnvironment(),
            $serverContext->isDebug()
        );

        if (!$this->kernel instanceof AsyncKernel) {
            throw new Exception(sprintf('Your kernel MUST implement %s', AsyncKernel::class));
        }
    }

    /**
     * Run.
     */
    public function run()
    {
        /*
         * REACT SERVER.
         */
        $this->kernel->boot();
        $this
            ->kernel
            ->getContainer()
            ->set('reactphp.event_loop', $this->loop);

        if (!$this->socket instanceof SocketServer) {
            $this->socket = new SocketServer(
                $this->serverContext->getHost().':'.
                $this->serverContext->getPort(),
                $this->loop
            );
        }

        $http = new HttpServer(
            function (ServerRequestInterface $request) {
                return new Promise(function (callable $resolve) use ($request) {
                    $resolveResponseCallback = function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolve) {
                        if (!$this->serverContext->isSilent()) {
                            $serverResponseWithMessage->printMessage();
                        }

                        return $resolve($serverResponseWithMessage->getServerResponse());
                    };

                    $uriPath = $request->getUri()->getPath();
                    $uriPath = '/'.ltrim($uriPath, '/');

                    return (0 === strpos(
                        $uriPath,
                        $this->serverContext->getStaticFolder()
                    ))
                        ? $this
                            ->requestHandler
                            ->handleStaticResource(
                                $this->loop,
                                $this->filesystem,
                                $this->rootPath,
                                $uriPath
                            )
                            ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                                $resolveResponseCallback($serverResponseWithMessage);
                            })
                        : $this
                            ->requestHandler
                            ->handleAsyncServerRequest($this->kernel, $request)
                            ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                                $resolveResponseCallback($serverResponseWithMessage);
                            });
                });
            }
        );

        $http->on('error', function (\Throwable $e) {
            (new ConsoleException($e, '/', 'EXC', '0'))->print($this->outputPrinter);
        });

        $http->listen($this->socket);
    }
}
