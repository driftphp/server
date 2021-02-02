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

namespace Drift\Server\Adapter\DriftKernel;

use Drift\EventBus\Subscriber\EventBusSubscriber;
use Drift\HttpKernel\AsyncKernel;
use Drift\Kernel as ApplicationKernel;
use Drift\Server\Adapter\SymfonyKernelBasedAdapter;
use Exception;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class DriftKernelAdapter.
 */
class DriftKernelAdapter extends SymfonyKernelBasedAdapter
{
    /**
     * @param $kernel
     *
     * @throws Exception
     */
    protected function checkKernel($kernel)
    {
        if (!$kernel instanceof AsyncKernel) {
            throw new SyncKernelException('The kernel should implement AsyncKernel interface, as you are using the DriftPHP kernel adapter');
        }
    }

    /**
     * @param string $environment
     * @param bool   $debug
     *
     * @return Kernel
     */
    protected static function createKernelByEnvironmentAndDebug(
        string $environment,
        bool $debug
    ): Kernel {
        return new ApplicationKernel($environment, $debug);
    }

    /**
     * @return PromiseInterface
     */
    protected function preload(): PromiseInterface
    {
        return $this
            ->kernel
            ->preload()
            ->then(function () {
                $container = $this->kernel->getContainer();
                $serverContext = $this->serverContext;

                if (
                    class_exists(EventBusSubscriber::class) &&
                    $serverContext->hasExchanges() &&
                    $container->has(EventBusSubscriber::class)
                ) {
                    $eventBusSubscriber = $container->get(EventBusSubscriber::class);
                    $eventBusSubscriber->subscribeToExchanges(
                        $serverContext->getExchanges(),
                        $this->outputPrinter
                    );
                }
            });
    }

    /**
     * @param Kernel|AsyncKernel $kernel
     * @param Request            $request
     *
     * @return PromiseInterface
     */
    protected function kernelHandle(
        Kernel $kernel,
        Request $request
    ): PromiseInterface {
        return $kernel->handleAsync($request);
    }

    /**
     * @return PromiseInterface
     */
    public function shutDown(): PromiseInterface
    {
        return $this->kernel->shutdown();
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['Drift', 'src', 'public', 'views'];
    }
}
