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
 * Make key value on arguments.
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
    if (2 !== count($serverArgs)) {
        throw new Exception('You should start the server defining a host and a port as a first argument: php/server 0.0.0.0:8000');
    }

    list($host, $port) = $serverArgs;
    $newArguments['host'] = $host;
    $newArguments['port'] = $port;

    return $newArguments;
}

use Apisearch\SymfonyReactServer\Adapter\KernelAdapter;
use Apisearch\SymfonyReactServer\Adapter\Symfony4KernelAdapter;

/**
 * Server.
 */
$arguments = buildServerArguments($argv);
$environment = array_key_exists('--dev', $arguments) ? 'dev' : 'prod';
$appPath = dirname(__FILE__).'/..';
$silent = $arguments['--silent'] ?? false;
$debug = $arguments['--debug'] ?? false;
$nonBlocking = $arguments['--non-blocking'] ?? false;
$adapter = $arguments['--adapter'] ?? 'symfony4';
$bootstrap = $arguments['--bootstrap'] ?? 'symfony4';
$host = $arguments['host'];
$port = $arguments['port'];

$bootstrapFile = [
    'autoload' => $appPath.'/vendor/autoload.php',
    'symfony4' => $appPath.'/config/bootstrap.php',
][$bootstrap] ?? $bootstrap;

$bootstrapFile = realpath($bootstrapFile);
if (!is_file($bootstrapFile)) {
    throw new \Exception('You must define an existing kernel bootstrap file, or by an alias or my a file path');
}

require realpath($bootstrapFile);

$adapter = [
    'symfony4' => Symfony4KernelAdapter::class,
][$adapter] ?? $adapter;

if (!is_a($adapter, KernelAdapter::class, true)) {
    throw new \Exception('You must define an existing kernel adapter, or by an alias or my a namespace. This class MUST implement KernelAdapter');
}

$application = new \Apisearch\SymfonyReactServer\Application(
    $host,
    $port,
    $environment,
    $debug,
    $silent,
    $nonBlocking,
    $adapter,
    $bootstrapFile
);

$application->run();
