#!/usr/bin/env php
<?php

/*
 * This file is part of the React Symfony Server package.
 *
 * Copyright (c) >=2019 Marc Morera
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

use Apisearch\ReactSymfonyServer\Adapter\KernelAdapter;
use Apisearch\ReactSymfonyServer\Arguments;
use Symfony\Component\Debug\Debug;

require dirname(__FILE__).'/../config/bootstrap.php';

$arguments = Arguments::build($argv);
$environment = array_key_exists('--dev', $arguments) ? 'dev' : 'prod';
$appPath = __DIR__.'/..';
$silent = $arguments['--silent'] ?? false;
$debug = $arguments['--debug'] ?? false;
$adapter = $arguments['--adapter'] ?? 'symfony4';
$host = $arguments['host'];
$port = $arguments['port'];

$adapter = [
    'symfony4' => \Apisearch\ReactSymfonyServer\Adapter\Symfony4KernelAdapter::class
][$adapter] ?? null;

if (null === $adapter) {
    throw new \Exception('You must define an existing kernel adapter, or by an alias (symfony4) or my a namespace');
}

if (!$silent) {
    echo PHP_EOL;
    echo '>' . PHP_EOL;
    echo '>  ReactPHP Client for Symfony Async Kernel' . PHP_EOL;
    echo '>    by Apisearch' . PHP_EOL;
    echo '>' . PHP_EOL;
    echo ">  Host: $host" . PHP_EOL;
    echo ">  Port: $port" . PHP_EOL;
    echo ">  Environment: $environment" . PHP_EOL;
    echo ">  Debug: " . ($debug ? 'enabled' : 'disabled') . PHP_EOL;
    echo ">  Silent: " . ($debug ? 'enabled' : 'disabled') . PHP_EOL;
    echo ">  Silent: disabled" . PHP_EOL;
    echo ">  Adapter: $adapter" . PHP_EOL;
    echo '>' . PHP_EOL . PHP_EOL;
}


\Apisearch\ReactSymfonyServer\ErrorHandler::handle();
if ($debug) {
    umask(0000);
    Debug::enable();
}

/**
 * @var KernelAdapter
 */
$kernel = $adapter::buildKernel($environment, $debug);

/**
 * REACT SERVER.
 */
$loop = \React\EventLoop\Factory::create();
$socket = new \React\Socket\Server($argv[1], $loop);
$requestHandler = new \Apisearch\ReactSymfonyServer\RequestHandler($kernel);
$kernel->boot();
$kernel->getContainer()->set('reactphp.event_loop', $loop);

$http = new \React\Http\Server(
    function (\Psr\Http\Message\ServerRequestInterface $request) use ($requestHandler, $silent) {
        return new \React\Promise\Promise(function (Callable $resolve) use ($request, $requestHandler, $silent) {
            $requestHandler
                ->handleAsyncServerRequest($request)
                ->then(function (array $parts) use ($silent, $resolve) {
                    list($httpResponse, $messages) = $parts;
                    if (!$silent) {
                        foreach ($messages as $message) {
                            $message->print();
                        }
                    }
                    gc_collect_cycles();
                    return $resolve($httpResponse);
                });
        });
    }
);

$http->on('error', function (\Throwable $e) {
    (new \Apisearch\ReactSymfonyServer\ConsoleException($e))->print();
});

$http->listen($socket);
$loop->run();