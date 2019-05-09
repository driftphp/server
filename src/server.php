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

$environment = in_array('--dev', $argv) ? 'dev' : 'prod';
$appPath = __DIR__.'/..';
$silent = in_array('--silent', $argv);
$debug = in_array('--debug', $argv);

require dirname(__FILE__).'/../config/bootstrap.php';

use App\Kernel;
use Symfony\Component\Debug\Debug;

\Apisearch\ReactSymfonyServer\ErrorHandler::handle();
if ($debug) {
    umask(0000);
    Debug::enable();
}

$kernel = new Kernel($environment, $debug);

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