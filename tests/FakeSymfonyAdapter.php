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
use Drift\Server\Adapter\SymfonyKernel\SymfonyKernelAdapter;

/**
 * Class FakeSymfonyAdapter.
 */
class FakeSymfonyAdapter extends SymfonyKernelAdapter
{
    /**
     * @param string $environment
     * @param bool   $debug
     *
     * @return AsyncKernel
     */
    protected static function createKernelByEnvironmentAndDebug(
        string $environment,
        bool $debug
    ): AsyncKernel {
        return new FakeKernel($environment, $debug);
    }
}
