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

namespace Drift\Server\Adapter;

use Drift\HttpKernel\AsyncKernel;
use App\Kernel as ApplicationKernel;
use Drift\Server\Watcher\ObservableKernel;

/**
 * Class SymfonyKernelAdapter.
 */
class SymfonyKernelAdapter implements KernelAdapter, ObservableKernel
{
    public static function getKernelClass() : string
    {
        return ApplicationKernel::class;
    }

    /**
     * Build AsyncKernel.
     */
    public static function buildKernel(
        string $environment,
        bool $debug
    ): AsyncKernel {
        return new ApplicationKernel($environment, $debug);
    }

    /**
     * Get static folder by kernel.
     *
     * @return string|null
     */
    public static function getStaticFolder(): ? string
    {
        return '/public';
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array
    {
        return ['src', 'public'];
    }

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array
    {
        return ['php', 'yml', 'yaml', 'xml', 'css', 'js', 'html', 'twig'];
    }

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array
    {
        return [];
    }
}
