# Symfony ReactPHP Server

This package provides an async server for Symfony kernel based on ReactPHP
packages and Promise implementation. The server is distributed with all the
Symfony kernel adapters, and can be easily extended for new Kernel
modifications.

> At the moment, this server cannot serve static files. This feature will be
> added soon.

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
    "apisearch-io/symfony-react-server": "dev-master"
  }
}
```

## Usage

This is a PHP file. This means that the way of starting this server is by, just,
executing it.

```php
php vendor/bin/server
```

You will find that the server starts with a default configuration. You can
configure how the server starts and what adapters use.

- Adapter: The kernel adapter for the server. This server needs a Kernel
  instance in order to start serving Requests. By default, `symfony4`. Can be
  overridden with option `--adapter` and the value must be a valid class
  namespace of an instance of `KernelAdapter`
  
- Bootstrap: How the application is bootstrapped. This would be usually a simple
  autoload require, but sometimes, like in symfony, can be some extra actions
  before the Kernel is instanced. By default, `symfony4`. Available options are
  `Symfony4` and `autoload`. Can be overridden with the option `--bootstrap` and
  the value must be a valid path of a file, starting from the project root.
  
- Environment: Kernel environment. By default `prod`, but turns `dev` if the
  option `--dev` is found.
  
- Debug: Kernel will start with this option is enabled. By default false,
  enabled if the option `--debug` is found.
  
- Silent: No information nor any kind of report will be printed in the standard
  output. By default disabled, but can be enabled with `--silent`.
  
- Non Blocking: This option enabled async kernel. If this option is not found,
  the server will use the standard `handle` kernel method. Otherwise, will
  use the `handleAsync` method, working in that case, as a non-blocking server.