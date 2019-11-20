<?php

/*
 * This file is part of the React Symfony Server package.
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
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
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
     * @var string
     */
    private $rootPath;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $environment;

    /**
     * @var bool
     */
    private $debug;

    /**
     * @var bool
     */
    private $silent;

    /**
     * @var string
     */
    private $adapter;

    /**
     * @var string
     */
    private $bootstrapPath;

    /**
     * @var string
     *
     * Static folder
     */
    private $staticFolder;

    /**
     * @var Kernel
     *
     * Kernel
     */
    private $kernel;

    /**
     * @var HttpServer
     */
    private $http;

    /**
     * @var SocketServer
     */
    private $socket;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Application constructor.
     *
     * @param LoopInterface $loop
     * @param RequestHandler $requestHandler
     * @param string      $rootPath
     * @param string      $host
     * @param int         $port
     * @param string      $environment
     * @param bool        $debug
     * @param bool        $silent
     * @param string      $adapter
     * @param string      $bootstrapPath
     * @param string|null $staticFolder
     *
     * @throws Exception
     */
    public function __construct(
        LoopInterface $loop,
        RequestHandler $requestHandler,
        Filesystem $filesystem,
        string $rootPath,
        string $host,
        int $port,
        string $environment,
        bool $debug,
        bool $silent,
        string $adapter,
        string $bootstrapPath,
        ?string $staticFolder
    ) {
        $this->loop = $loop;
        $this->requestHandler = $requestHandler;
        $this->filesystem = $filesystem;
        $this->rootPath = $rootPath;
        $this->host = $host;
        $this->port = $port;
        $this->environment = $environment;
        $this->debug = $debug;
        $this->silent = $silent;
        $this->adapter = $adapter;
        $this->bootstrapPath = $bootstrapPath;

        ErrorHandler::handle();
        if ($this->debug) {
            umask(0000);
            Debug::enable();
        }

        /**
         * @var KernelAdapter $adapter
         */
        $this->kernel = $adapter::buildKernel(
            $this->environment,
            $this->debug
        );

        if (!$this->kernel instanceof AsyncKernel) {
            throw new Exception(sprintf('Your kernel MUST implement %s', AsyncKernel::class));
        }

        if (!is_null($staticFolder)) {
            $this->staticFolder = empty($staticFolder)
                ? $adapter::getStaticFolder($this->kernel)
                : $staticFolder;

            if (is_string($this->staticFolder)) {
                $this->staticFolder = '/'.trim($this->staticFolder, '/').'/';
            }
        }
    }

    /**
     * Connect socket
     */
    public function connectSocket()
    {
        if (!$this->socket instanceof SocketServer) {
            $this->socket = new SocketServer($this->host.':'.$this->port, $this->loop);
        }
    }

    /**
     * Run.
     */
    public function run()
    {
        /**
         * REACT SERVER.
         */
        $this->kernel->boot();
        $this
            ->kernel
            ->getContainer()
            ->set('reactphp.event_loop', $this->loop);

        $this->http = new HttpServer(
            function (ServerRequestInterface $request) {
                return new Promise(function (callable $resolve) use ($request) {
                    $resolveResponseCallback = function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolve) {
                        if (!$this->silent) {
                            $serverResponseWithMessage->printMessage();
                        }

                        return $resolve($serverResponseWithMessage->getServerResponse());
                    };

                    $uriPath = $request->getUri()->getPath();
                    $uriPath = '/'.ltrim($uriPath, '/');

                    return (0 === strpos(
                        $uriPath,
                        $this->staticFolder
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

        $this->http->on('error', function (\Throwable $e) {
            (new ConsoleException($e, '/', 'EXC', '0'))->print();
        });

        $this->http->listen($this->socket);
    }

    /**
     * Stop.
     *
     * Shutdowns the kernel and removes all event listeners.
     */
    public function stop()
    {
        $this->kernel->shutdown();
        $this->http->removeAllListeners();
        $this->socket->removeAllListeners();
        //$this->socket->close();
    }

    /**
     * Print header
     */
    public function printHeader()
    {
        if (!$this->silent) {
            echo PHP_EOL;
            echo '>'.PHP_EOL;
            echo '>  ReactPHP Client for DriftPHP'.PHP_EOL;
            echo '>    by Marc Morera (@mmoreram)'.PHP_EOL;
            echo '>'.PHP_EOL;
            echo ">  Host: $this->host".PHP_EOL;
            echo ">  Port: $this->port".PHP_EOL;
            echo ">  Environment: $this->environment".PHP_EOL;
            echo '>  Debug: '.($this->debug ? 'enabled' : 'disabled').PHP_EOL;
            echo '>  Static Folder: '.(empty($this->staticFolder) ? 'disabled' : $this->staticFolder).PHP_EOL;
            echo ">  Adapter: $this->adapter".PHP_EOL;
            echo '>  Loaded bootstrap file: '.realpath($this->bootstrapPath).PHP_EOL;
            echo '>'.PHP_EOL.PHP_EOL;
        }
    }
}
