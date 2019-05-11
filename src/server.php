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

/**
 * Make key value on arguments
 *
 * @param array $originalArguments
 *
 * @return array
 *
 * @throws Exception
 */
function buildServerArguments(array $originalArguments)
{
    $arguments = array_slice($originalArguments, 2);
    $newArguments = [];
    foreach ($arguments as $value) {
        $parts = explode('=', $value, 2);
        $key = $parts[0];
        $value = $parts[1] ?? true;
        $newArguments[$key] = $value;
    }

    $serverArgs = explode(':', $originalArguments[1], 2);
    if (count($serverArgs) !== 2) {
        throw new Exception('You should start the server defining a host and a port as a first argument: php/server 0.0.0.0:8000');
    }

    list($host, $port) = $serverArgs;
    $newArguments['host'] = $host;
    $newArguments['port'] = $port;

    return $newArguments;
}

use Apisearch\ReactSymfonyServer\Adapter\KernelAdapter;
use Apisearch\ReactSymfonyServer\Adapter\Symfony4KernelAdapter;
use Symfony\Component\Debug\Debug;

/**
 * Server
 */
$arguments = buildServerArguments($argv);
$environment = array_key_exists('--dev', $arguments) ? 'dev' : 'prod';
$appPath = dirname(__FILE__).'/..';
$silent = $arguments['--silent'] ?? false;
$debug = $arguments['--debug'] ?? false;
$adapter = $arguments['--adapter'] ?? 'symfony4';
$bootstrap = $arguments['--bootstrap'] ?? 'symfony4';
var_dump($bootstrap);
$host = $arguments['host'];
$port = $arguments['port'];

$bootstrapFile = [
    'autoload' => $appPath . '/vendor/autoload.php',
    'symfony4' => $appPath . '/config/bootstrap.php',
][$bootstrap] ?? $bootstrap;

$bootstrapFile = realpath($bootstrapFile);
if (!is_file($bootstrapFile)) {
    throw new \Exception('You must define an existing kernel bootstrap file, or by an alias or my a file path');
}

require realpath($bootstrapFile);

$adapter = [
    'symfony4' => Symfony4KernelAdapter::class
][$adapter] ?? $adapter;

if (!is_a($adapter, KernelAdapter::class, true)) {
    throw new \Exception('You must define an existing kernel adapter, or by an alias or my a namespace. This class MUST implement KernelAdapter');
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
    echo ">  Bootstrap: $bootstrapFile" . PHP_EOL;
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