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
use React\Http\Response;
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
        $code = \intval($request->query->get('code', 200));
        $pathInfo = $request->getPathInfo();
        if (400 === $code) {
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
            '/psr-stream' === $pathInfo ||
            '/psr-stream-gzipped' === $pathInfo
        ) {
            if ('/psr-stream-gzipped' === $request->getPathInfo()) {
                $request->headers->set('Accept-Encoding', $request->query->get('type'));
            }

            $streamResponse = new ThroughStream();
            $streamResponse->write('React');
            $loop = $this->getContainer()->get('reactphp.event_loop');
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

        throw new RouteNotFoundException();
    }
}
