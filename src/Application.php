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

namespace Apisearch\SymfonyReactServer;

use Apisearch\SymfonyReactServer\Adapter\KernelAdapter;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Factory as EventLoopFactory;
use React\Filesystem\Filesystem;
use React\Http\Server as HttpServer;
use React\Promise\Promise;
use React\Socket\Server as SocketServer;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpKernel\AsyncKernel;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class Application
 */
class Application
{
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
     * @var bool
     */
    private $nonBlocking;

    /**
     * @var string
     */
    private $adapter;

    /**
     * @var string
     */
    private $bootstrapFile;

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
     * Application constructor.
     *
     * @param string $rootPath
     * @param string $host
     * @param int    $port
     * @param string $environment
     * @param bool   $debug
     * @param bool   $silent
     * @param bool   $nonBlocking
     * @param string $adapter
     * @param string $bootstrapFile
     * @param string|null $staticFolder
     *
     * @throws Exception
     */
    public function __construct(
        string $rootPath,
        string $host,
        int $port,
        string $environment,
        bool $debug,
        bool $silent,
        bool $nonBlocking,
        string $adapter,
        string $bootstrapFile,
        ?string $staticFolder
    )
    {
        $this->rootPath = $rootPath;
        $this->host = $host;
        $this->port = $port;
        $this->environment = $environment;
        $this->debug = $debug;
        $this->silent = $silent;
        $this->nonBlocking = $nonBlocking;
        $this->adapter = $adapter;
        $this->bootstrapFile = $bootstrapFile;

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

        if ($this->nonBlocking && !$this->kernel instanceof AsyncKernel) {
            throw new Exception(
                'You have configured the server to work as a non-blocking application, but you\'re using a synchronous Kernel'
            );
        }

        if (!is_null($staticFolder)) {
            $this->staticFolder = empty($staticFolder)
                ? $adapter::getStaticFolder($this->kernel)
                : $staticFolder;
            $this->staticFolder = '/' . trim($this->staticFolder, '/') . '/';
        }
    }

    /**
     * Run
     */
    public function run()
    {
        /**
         * REACT SERVER.
         */
        $loop = EventLoopFactory::create();
        $socket = new SocketServer($this->host . ':' . $this->port, $loop);
        $filesystem = Filesystem::create($loop);
        $requestHandler = new RequestHandler();
        $this->kernel->boot();

        if ($this->nonBlocking) {
            $this
                ->kernel
                ->getContainer()
                ->set('reactphp.event_loop', $loop);
        }

        if (!$this->silent) {
            $this->print();
        }

        $http = new HttpServer(
            function (ServerRequestInterface $request) use ($requestHandler, $filesystem) {
                return new Promise(function (Callable $resolve) use ($request, $requestHandler, $filesystem) {

                    $resolveResponseCallback = function(ServerResponseWithMessage $serverResponseWithMessage) use ($resolve) {
                        if (!$this->silent) {
                            $serverResponseWithMessage->printMessage();
                        }

                        return $resolve($serverResponseWithMessage->getServerResponse());
                    };

                    $uriPath = $request->getUri()->getPath();
                    $uriPath = '/' . ltrim($uriPath, '/');

                    if (strpos(
                        $uriPath,
                        $this->staticFolder
                    ) === 0) {
                        $requestHandler->handleStaticResource(
                            $filesystem,
                            $this->rootPath,
                            $uriPath
                        )
                        ->then(function(ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback){
                            $resolveResponseCallback($serverResponseWithMessage);
                        });

                        return;
                    }

                    $this->nonBlocking
                        ? $requestHandler
                        ->handleAsyncServerRequest($this->kernel, $request)
                        ->then(function (ServerResponseWithMessage $serverResponseWithMessage) use ($resolveResponseCallback) {
                            $resolveResponseCallback($serverResponseWithMessage);
                        })
                        : $resolveResponseCallback($requestHandler
                        ->handleServerRequest($this->kernel, $request)
                    );
                });
            }
        );

        $http->on('error', function (\Throwable $e) {
            (new ConsoleException($e, '/', 'EXC', 0))->print();
        });

        $http->listen($socket);
        $loop->run();
    }

    /**
     * Print
     */
    private function print()
    {
        if (!$this->silent) {
            echo PHP_EOL;
            echo '>' . PHP_EOL;
            echo '>  ReactPHP Client for Symfony Kernel' . PHP_EOL;
            echo '>    by Apisearch' . PHP_EOL;
            echo '>' . PHP_EOL;
            echo ">  Host: $this->host" . PHP_EOL;
            echo ">  Port: $this->port" . PHP_EOL;
            echo ">  Environment: $this->environment" . PHP_EOL;
            echo ">  Debug: " . ($this->debug ? 'enabled' : 'disabled') . PHP_EOL;
            echo ">  Silent: disabled" . PHP_EOL;
            echo ">  Non Blocking: " . ($this->nonBlocking ? 'enabled' : 'disabled') . PHP_EOL;
            echo ">  Static Folder: " . (empty($this->staticFolder) ? 'disabled' : $this->staticFolder) . PHP_EOL;
            echo ">  Adapter: $this->adapter" . PHP_EOL;
            echo ">  Bootstrap: $this->bootstrapFile" . PHP_EOL;
            echo '>' . PHP_EOL . PHP_EOL;
        }
    }
}
