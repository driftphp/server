# Symfony ReactPHP Server

[![CircleCI](https://circleci.com/gh/apisearch-io/symfony-react-server.svg?style=svg)](https://circleci.com/gh/apisearch-io/symfony-react-server)
[![Join the Slack](https://img.shields.io/badge/join%20us-on%20slack-blue.svg)](https://apisearch.slack.com)

This package provides an async server for Symfony kernel based on ReactPHP
packages and Promise implementation. The server is distributed with all the
Symfony kernel adapters, and can be easily extended for new Kernel
modifications.

You can take a look at the 
[Symfony + ReactPHP Series](https://medium.com/@apisearch/symfony-and-reactphp-series-82082167f6fb)
in order to understand a little bit better the rationale behind using this
server and Promises in your domain.

> This server is mainly designed to work as a non-blocking symfony server. Used
> as a regular one should be part of an architecture, containing, at least, a
> balancer and several instanced in several ports. Otherwise you may have 
> performance issues.

## Installation

In order to use this server, you only have to add the requirement in composer.
Once updated your dependencies, you will find a brand new server bin inside the
`vendor/bin` folder.

```yml
{
  "require": {
    "apisearch-io/symfony-react-server": "dev-master"
  }
}
```

## Usage

This is a PHP file. This means that the way of starting this server is by, just,
executing it.

```console
php vendor/bin/server 0.0.0.0:8100
```

You will find that the server starts with a default configuration. You can
configure how the server starts and what adapters use.

- Adapter: The kernel adapter for the server. This server needs a Kernel
  instance in order to start serving Requests. By default, `symfony4`. Can be
  overridden with option `--adapter` and the value must be a valid class
  namespace of an instance of `KernelAdapter`

```console
php vendor/bin/server 0.0.0.0:8100 --adapter=symfony4
php vendor/bin/server 0.0.0.0:8100 --adapter=My\Own\Adapter
```

- Bootstrap: How the application is bootstrapped. This would be usually a simple
  autoload require, but sometimes, like in symfony, can be some extra actions
  before the Kernel is instanced. By default, `symfony4`. Available options are
  `Symfony4` and `autoload`. Can be overridden with the option `--bootstrap` and
  the value must be a valid path of a file, starting from the project root.

```console
php vendor/bin/server 0.0.0.0:8100 --bootstrap=symfony4
php vendor/bin/server 0.0.0.0:8100 --bootstrap=autoload
php vendor/bin/server 0.0.0.0:8100 --bootstrap=config/myfile.php
```

- Environment: Kernel environment. By default `prod`, but turns `dev` if the
  option `--dev` is found.

```console
php vendor/bin/server 0.0.0.0:8100 --dev
```

- Debug: Kernel will start with this option is enabled. By default false,
  enabled if the option `--debug` is found. Makes sense on development
  environment, but is not exclusive.

```console
php vendor/bin/server 0.0.0.0:8100 --dev --debug
```

- Silent: No information nor any kind of report will be printed in the standard
  output. By default disabled, but can be enabled with `--silent`.

```console
php vendor/bin/server 0.0.0.0:8100 --silent
```


## Turning the server non-blocking

By default, the server will work as a blocking server with the Symfony HTTP 
Kernel. The server will use the method `handle` to properly serve requests. If
your application starts working with the asynchronous kernel, you must ensure
your kernel uses the AsyncKernel implementation.

> Make sure you have the dependency installed in your composer.json file. You
> must include the line `apisearch-io/symfony-async-http-kernel` under require
> section. Then, `composer update`.

To turn on the asynchronous feature, just add this flag


```console
php vendor/bin/server 0.0.0.0:8100 --non-blocking
```

## Serving static files

Kernel Adapters have already defined the static folder related to the kernel.
For example, Symfony4 adapter will provide static files from folder `/public`.

You can override the static folder with the command option `--static-folder`.
All files inside this defined folder will be served statically in a non-blocking
way

```console
php vendor/bin/server 0.0.0.0:8100 --static-folder=public
```

You can disable static folder with the option `--no-static-folder`. This can be
useful when working with the adapter value and want to disable the default
value, for example, for an API.


```console
php vendor/bin/server 0.0.0.0:8100 --no-static-folder
```
