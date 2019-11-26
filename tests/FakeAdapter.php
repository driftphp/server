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
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Watcher\ObservableKernel;

/**
 * Class FakeAdapter.
 */
class FakeAdapter implements KernelAdapter, ObservableKernel
{
    /**
     * Build kernel.
     *
     * @param string $environment
     * @param bool   $debug
     *
     * @return AsyncKernel
     */
    public static function buildKernel(
        string $environment,
        bool $debug
    ): AsyncKernel {
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
     * Get watcher folders
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['tests/sandbox', 'tests/sandbox2', 'tests/non-existing'];
    }

    /**
     * Get watcher folders
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return ['file1'];
    }

    /**
     * Get watcher ignoring folders
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return ['ignore', 'ignore2'];
    }
}
