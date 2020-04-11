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

namespace Drift\Server\Watcher;

/**
 * Interface ObservableKernel.
 */
interface ObservableKernel
{
    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableFolders(): array;

    /**
     * Get watcher folders.
     *
     * @return string[]
     */
    public static function getObservableExtensions(): array;

    /**
     * Get watcher ignoring folders.
     *
     * @return string[]
     */
    public static function getIgnorableFolders(): array;
}
