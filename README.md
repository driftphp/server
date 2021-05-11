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

### Table of content

- [Installation](#installation)
- [Start the server](#start-the-server)
- [Build your adapter](#build-your-adapter)
- [Workers](#workers)
- [Watcher](#watcher)
- [Static server](#static-server)
- [Symfony bridge](#symfony-bridge)
- [DriftPHP resources](#driftphp-resources)

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

You can use as well the short mode, defining only the port and assuming this 
host `0.0.0.0`.

```bash
php vendor/bin/server run 8000
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
     * @param LoopInterface            $loop
     * @param string                   $rootPath
     * @param ServerContext            $serverContext
     * @param OutputPrinter            $outputPrinter
     * @param MimeTypeChecker          $mimeTypeChecker
     * @param FilesystemInterface|null $filesystem
     *
     * @return PromiseInterface<self>
     *
     * @throws KernelException
     */
    public static function create(
        LoopInterface $loop,
        string $rootPath,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        MimeTypeChecker $mimeTypeChecker,
        ?FilesystemInterface $filesystem
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

### Custom response output

You can internally use the `x-server-message` header for custom server messages.
The server will remove this server value before returning the response content.

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

## Static server

This server can serve static files as well located in your project. By default,
an adapter will provide a path where static files should be found (like DriftPHP
statics are located under `public/` folder), but you can overwrite this value,
or even override it.

```bash
php vendor/bin/server watch 0.0.0.0:8000 --static-folder=/my/own/folder/
php vendor/bin/server watch 0.0.0.0:8000 --no-static-folder
```

You can create an alias as well if you need it. That can be useful if you want
to mask the internal path with an external one, only exposing this second one.
Both values must be separated by the symbol `:`, being the first part the alias,
and the second one the internal path.

```bash
php vendor/bin/server watch 0.0.0.0:8000 --static-folder=/public/:/internal/public/path
```

In this example, a file named `app.js` located under `/internal/public/path/` 
folder will be accessible at `http://localhost:8000/public/app.js`. By default,
this feature is disabled.

### Important

By default, this package will not install the `react/filesystem` package. This
means that, if you don't install it by hand in your project, all the disk 
operations will be blocking. These operations done synchronously will be much
faster and efficient, but by using large size files could slow down the entire 
process.

## Symfony bridge

In order to help you from migrating an application from Symfony to DriftPHP, 
assuming that this means that your whole domain should turn on top of Promises, 
including your infrastructure layer, this server is distributed with a small
Symfony adapter. Use it as a tool, and never use it at production (using a
ReactPHP based server in a blocking application is something not recommendable
at all in terms of performance and service availability). That adapter will help
your migrating from one platform to the other, as will allow this server to work
with your Symfony kernel.

```bash
php vendor/bin/server watch 0.0.0.0:8000 --adapter=symfony
```

## DriftPHP resources

Some first steps for you!

- [Go to DOCS](https://driftphp.io/#/?id=the-server)

or

- [Try a demo](https://github.com/driftphp/demo)
- [Install the skeleton](https://github.com/driftphp/skeleton)
