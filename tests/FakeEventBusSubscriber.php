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

use Drift\Server\OutputPrinter;

/**
 * Class FakeEventBusSubscriber.
 */
class FakeEventBusSubscriber
{
    /**
     * Subscribe to exchanges.
     *
     * @param array         $exchanges
     * @param OutputPrinter $outputPrinter
     */
    public function subscribeToExchanges(
        array $exchanges,
        OutputPrinter $outputPrinter
    ) {
        foreach ($exchanges as $exchange => $queue) {
            empty($queue)
                ? $outputPrinter->printLine("Subscribed to exchange $exchange and temporary queue")
                : $outputPrinter->printLine("Subscribed to exchange $exchange and queue $queue");
        }
    }

    /**
     * Get async adapter name.
     *
     * @return string
     */
    public function getAsyncAdapterName(): string
    {
        return 'fake';
    }
}
