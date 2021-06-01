<?php

/*
 * This file is part of the Drift Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server\Tests;

use Drift\HttpKernel\AsyncKernel;
use React\Http\Message\Response;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function React\Promise\resolve;
use React\Stream\ThroughStream;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Class FakeKernel.
 */
class FakeKernel extends AsyncKernel
{
    /**
     * Returns an array of bundles to register.
     *
     * @return iterable|BundleInterface[]
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
        ];
    }

    /**
     * Loads the container configuration.
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
    }

    /**
     * Preload kernel.
     */
    public function preload(): PromiseInterface
    {
        echo '[Preloaded]';

        return parent::preload();
    }

    /**
     * Shutdown kernel.
     *
     * @return PromiseInterface
     */
    public function shutdown(): PromiseInterface
    {
        echo '[Shutdown]';

        return parent::shutdown();
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        $container->setParameter('kernel.secret', 'engonga');
    }

    /**
     * Handles a Request to convert it to a Response.
     *
     * When $catch is true, the implementation must catch all exceptions
     * and do its best to convert them to a Response instance.
     *
     * @param Request $request A Request instance
     *
     * @return PromiseInterface
     */
    public function handleAsync(Request $request): PromiseInterface
    {
        return (resolve($request))
            ->then(function (Request $request) {
                return $this->handle($request);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $loop = $this->getContainer()->get('reactphp.event_loop');
        $code = \intval($request->query->get('code', '200'));
        $pathInfo = $request->getPathInfo();

        if (in_array($code, [400, 403])) {
            throw new \Exception('Bad Request');
        }

        if ('/file' === $pathInfo) {
            $files = $request->files->all();

            return new JsonResponse([
                'files' => array_map(function (UploadedFile $file) {
                    return [
                        $file->getPath().'/'.$file->getFilename(),
                        file_get_contents($file->getPath().'/'.$file->getFilename()),
                        $file->isValid(),
                    ];
                }, $files),
            ], $code);
        }

        if ('/psr' === $pathInfo) {
            return new Response(200, [
                'Content-Type' => 'plain/text',
            ], 'ReactPHP Response');
        }

        if (
            '/psr-stream' === $pathInfo
        ) {
            $streamResponse = new ThroughStream();
            $streamResponse->write('React');
            $loop->futureTick(function () use ($streamResponse, $loop) {
                $streamResponse->write('PHP ');
                $loop->futureTick(function () use ($streamResponse) {
                    $streamResponse->write('stream');
                    $streamResponse->end('...');
                });
            });

            return new Response(200, [
                'Content-Type' => 'plain/text',
            ], $streamResponse);
        }

        if ('/text' === $pathInfo) {
            return new Response(200, [
                'Content-Type' => 'plain/text',
                'Connection' => 'keep-alive',
            ], 'This is one text for testing');
        }

        if ('/query' === $pathInfo) {
            return new JsonResponse([
                'query' => $request->query,
            ], $code);
        }

        if ('/cookies' === $pathInfo) {
            return new JsonResponse([
                'cookies' => $request->cookies->all(),
            ], $code);
        }

        if ('/body' === $pathInfo) {
            return new JsonResponse([
                'body' => $request->getContent(),
            ], $code);
        }

        if ('/gzip' === $pathInfo) {
            return new Response($code, [
                'Content-Encoding' => 'gzip'
            ], gzencode('ReactPHP Response'));
        }

        if ('/auth' === $pathInfo) {
            return new JsonResponse([
                'user' => $request->headers->get('PHP_AUTH_USER'),
                'password' => $request->headers->get('PHP_AUTH_PW'),
            ], $code);
        }

        if ('/streamed-body' === $pathInfo) {
            $stream = $request->attributes->get('body');

            $deferred = new Deferred();
            $data = '';
            $stream->on('data', function (string $chunk) use (&$data) {
                $data .= $chunk;
            });

            $stream->on('close', function () use ($deferred, &$data) {
                $deferred->resolve($data);
            });

            return $deferred
                ->promise()
                ->then(function (string $data) use ($loop) {
                    return new Response(200, [], $data);
                });
        }

        if ('/check-srv-vars' === $pathInfo) {
            $server = $request->server;

            $code = (
                '/check-srv-vars' === $server->get('REQUEST_URI') &&
                '127.0.0.1' === $server->get('REMOTE_ADDR') &&
                \intval($server->get('SERVER_PORT')) === \intval($request->query->get('port'))
            ) ? 200 : 500;

            return new JsonResponse([], $code);
        }

        throw new RouteNotFoundException();
    }
}
