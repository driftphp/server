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

/**
 * Class ExchangesTest.
 */
class ExchangesTest extends BaseTest
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        list($process, $port, $initialOutput) = $this->buildServer([
            '--exchange=exchange1',
            '--exchange=exchange2:queue2',
        ], FakeExchangesAdapter::class);

        $this->assertStringContainsString(
            'Exchanges subscribed: exchange1, exchange2:queue2',
            $initialOutput
        );
        $this->assertStringContainsString(
            'Subscribed to exchange exchange1 and temporary queue',
            $initialOutput
        );
        $this->assertStringContainsString(
            'Subscribed to exchange exchange2 and queue queue2',
            $initialOutput
        );

        $process->stop();
    }
}
