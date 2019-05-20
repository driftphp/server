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

namespace Apisearch\SymfonyReactServer\Tests;

use Apisearch\SymfonyReactServer\Adapter\KernelAdapter;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class FakeAdapter.
 */
class FakeAdapter implements KernelAdapter
{
    /**
     * Build kernel.
     *
     * @param string $environment
     * @param bool   $debug
     *
     * @return Kernel
     */
    public static function buildKernel(
        string $environment,
        bool $debug
    ): Kernel {
        return new FakeKernel($environment, $debug);
    }

    /**
     * Get static folder by kernel.
     *
     * @param Kernel $kernel
     *
     * @return string|null
     */
    public static function getStaticFolder(Kernel $kernel): ? string
    {
        return '/tests/public';
    }
}
