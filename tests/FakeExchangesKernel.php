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

use Drift\EventBus\EventBusBundle;
use Drift\EventBus\Subscriber\EventBusSubscriber;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Class FakeExchangesKernel.
 */
class FakeExchangesKernel extends FakeKernel
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
            new EventBusBundle(),
        ];
    }

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        parent::process($container);

        $definition = new Definition(FakeEventBusSubscriber::class);
        $definition->setPublic(true);
        $container->setDefinition(EventBusSubscriber::class, $definition);
    }
}
