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

/**
 * Class FakeExchangesAdapter.
 */
class FakeExchangesAdapter extends FakeAdapter
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
        return new FakeExchangesKernel($environment, $debug);
    }
}
