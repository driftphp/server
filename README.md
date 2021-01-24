# DriftPHP Server

[![CircleCI](https://circleci.com/gh/driftphp/server.svg?style=svg)](https://circleci.com/gh/driftphp/server)

This package provides a ReactPHP based async, reactive and non-blocking server
for PHP applications working on top of ReactPHP Promises and PSR standards. The 
server has a small kernel/application abstraction for an optimized integration
for several domain implementations. Here some features.

- Handle request based on Promises
- Serve static content in a non-blocking way
- Compress both Responses and Stream data
- Work with different workers, using multiple PHP threads (and CPUs)
- Visualize the usage and check how fast and light your request handles are
- Use the PHP-Watcher (only available if the PHP-Watcher is included in your 
  composer) to automatically update the server if you change some of your code

By default, the server will use the DriftPHP Kernel adapter, but you can change
the adapter easily when starting the server (Check the [adapters](#build-your-adapter) 
chapter)

## Installation

You can install the server by adding the dependency in your `composer.json`
file

```json
"require": {
    "drift/server": "^0.1"
}
```

## Start the server

This package provides an async server for DriftPHP framework based on ReactPHP
packages and Promise implementation. The server is distributed with all the
Symfony based kernel adapters, and can be easily extended for new Kernel
modifications.

To start the server, just type this line. Your project might have a custom `bin`
folder, so check it.

```bash
php vendor/bin/server run 0.0.0.0:8000
```

And that's it. You will have a fully working server for your application.

## Build your adapter

In order to build your adapter, the only thing you need is to create an 
implementation of the interface `Drift\Server\Adapter\KernelAdapter`. This layer
will allow the server to start your application, handle each request, locate
your static resources and shutdown the application. The `ObservableKernel` will
provide as well some information about where your code is located, specifically
designed for the watcher feature.

```php
/**
 * Class KernelAdapter.
 */
interface KernelAdapter extends ObservableKernel
{
    /**
     * @param LoopInterface       $loop
     * @param string              $rootPath
     * @param ServerContext       $serverContext
     * @param FilesystemInterface $filesystem
     * @param OutputPrinter       $outputPrinter
     * @param MimeTypeChecker     $mimeTypeChecker
     *
     * @return PromiseInterface<self>
     *
     * @throws KernelException
     */
    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        FilesystemInterface $filesystem,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker
    ): PromiseInterface;

    /**
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public function handle(ServerRequestInterface $request): PromiseInterface;

    /**
     * Get static folder.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string;

    /**
     * @return PromiseInterface
     */
    public function shutDown(): PromiseInterface;
}
```

When you have your adapter created, the is as easy is this to start serving from
your application

```bash
php vendor/bin/server run 0.0.0.0:8000 --adapter='My\Namespace\Adapter"
```

## Workers

This server creates a single worker by default. A simple PHP thread that will 
use one single CPUs. Luckily this server provides you a simple way of creating
multiple instances listening the same port, emulating a simple balancer between
N threads.

```bash
php vendor/bin/server run 0.0.0.0:8000 --workers=8
```

You can guess the number of physical threads your host has by using the value 
`-1`. By default, a single worker will be used.

This feature is not designed and intended for production environments. We 
encourage to use a reversed proxy or a small balancer if you need to balance 
between several processes. Furthermore, this feature uses `pcntl_fork`, so as
the documentation explains it is not available for Windows users.

## Watcher

You can use the watcher by installing the `seregazhuk/php-watcher` dependency
in your composer.

```json
"require-dev": {
    "seregazhuk/php-watcher": "*"
}
```

After installing the dependency, you will be able to start your server by 
checking code changes.

```bash
php vendor/bin/server watch 0.0.0.0:8000
```

This feature is for development only.

## DriftPHP resources

Some first steps for you!

- [Go to DOCS](https://driftphp.io/#/?id=the-server)

or

- [Try a demo](https://github.com/driftphp/demo)
- [Install the skeleton](https://github.com/driftphp/skeleton)