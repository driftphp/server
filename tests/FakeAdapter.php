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

use Drift\Server\Adapter\DriftKernel\DriftKernelAdapter;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Class FakeAdapter.
 */
class FakeAdapter extends DriftKernelAdapter
{
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
        return new FakeKernel($environment, $debug);
    }

    /**
     * Get static folder by kernel.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string
    {
        return '/tests/public';
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['tests/sandbox', 'tests/sandbox2', 'tests/non-existing'];
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return ['file1'];
    }

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return ['ignore', 'ignore2'];
    }
}
