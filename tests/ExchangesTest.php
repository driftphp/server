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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Class ExchangesTest.
 */
class ExchangesTest extends TestCase
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeExchangesAdapter::class,
            '--exchange=exchange1',
            '--exchange=exchange2:queue2',
        ]);

        $process->start();
        sleep(1);
        $output = $process->getOutput();
        $process->stop();
        $this->assertStringContainsString(
            'Exchanges subscribed: exchange1, exchange2:queue2',
            $output
        );
        $this->assertStringContainsString(
            'Subscribed to exchange exchange1 and temporary queue',
            $output
        );
        $this->assertStringContainsString(
            'Subscribed to exchange exchange2 and queue queue2',
            $output
        );
    }
}
