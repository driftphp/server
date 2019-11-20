<?php

/*
 * This file is part of the React Symfony Server package.
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
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
        return (new FulfilledPromise($request))
            ->then(function (Request $request) {
                return $this->handle($request);
            });
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $code = \intval($request->query->get('code'));
        if (400 === $code) {
            throw new \Exception('Bad Request');
        }

        return new JsonResponse([
            'query' => $request->query,
        ], $code);
    }
}
