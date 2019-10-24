# DriftPHP Server

[![CircleCI](https://circleci.com/gh/driftphp/server.svg?style=svg)](https://circleci.com/gh/driftphp/server)

This package provides an async server for DriftPHP framework based on ReactPHP
packages and Promise implementation. The server is distributed with all the
Symfony based kernel adapters, and can be easily extended for new Kernel
modifications.

You can take a look at the 
[Symfony + ReactPHP Series](https://medium.com/@apisearch/symfony-and-reactphp-series-82082167f6fb)
in order to understand a little bit better the rationale behind using this
server and Promises in your domain.

## Installation

In order to use this server, you only have to add the requirement in composer.
Once updated your dependencies, you will find a brand new server bin inside the
`vendor/bin` folder.

```yml
{
  "require": {
    "driftphp/server": "dev-master"
  }
}
```

## Usage

This is a PHP file. This means that the way of starting this server is by, just,
executing it.

```console
vendor/bin/server run 0.0.0.0:8100
```

You will find that the server starts with a default configuration. You can
configure how the server starts and what adapters use.

- Adapter: The kernel adapter for the server. This server needs a Kernel
  instance in order to start serving Requests. By default, `symfony4`. Can be
  overridden with option `--adapter` and the value must be a valid class
  namespace of an instance of `KernelAdapter`

```console
php vendor/bin/server run 0.0.0.0:8100 --adapter=symfony4
php vendor/bin/server run 0.0.0.0:8100 --adapter=My\Own\Adapter
```

- Environment: Kernel environment. By default `prod`, but turns `dev` if the
  option `--dev` is found, or the defined one if you define it with `--env`
  option.

```console
php vendor/bin/server run 0.0.0.0:8100 --dev
php vendor/bin/server run 0.0.0.0:8100 --env=test
```

- Debug: Kernel will start with this option is enabled. By default false,
  enabled if the option `--debug` is found. Makes sense on development
  environment, but is not exclusive.

```console
php vendor/bin/server run 0.0.0.0:8100 --dev --debug
```

## Serving static files

Kernel Adapters have already defined the static folder related to the kernel.
For example, Symfony4 adapter will provide static files from folder `/public`.

You can override the static folder with the command option `--static-folder`.
All files inside this defined folder will be served statically in a non-blocking
way

```console
php vendor/bin/server run 0.0.0.0:8100 --static-folder=public
```

You can disable static folder with the option `--no-static-folder`. This can be
useful when working with the adapter value and want to disable the default
value, for example, for an API.


```console
php vendor/bin/server run 0.0.0.0:8100 --no-static-folder
```
