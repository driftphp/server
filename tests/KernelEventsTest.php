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
 * Class KernelEventsTest.
 */
class KernelEventsTest extends TestCase
{
    /**
     * Test cookies are passed.
     */
    public function testPreload()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
        ]);

        $process->start();
        usleep(300000);
        $this->assertContains('Kernel preloaded', $process->getOutput());
        $this->assertContains('[Preloaded]', $process->getOutput());
        $this->assertFalse($process->isTerminated());
    }

    /**
     * Test cookies are passed.
     */
    public function testShutdown()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
        ]);

        $process->start();
        sleep(1);
        $this->assertFalse($process->isTerminated());

        $process->signal(SIGTERM);
        sleep(1);
        $this->assertContains('Loop forced to stop', $process->getOutput());
        $this->assertContains('[Shutdown]', $process->getOutput());
        $this->assertTrue($process->isTerminated());
    }

    /**
     * Test cookies are passed.
     */
    public function testShutdownIsForced()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
            '--allowed-loop-stops=10',
        ]);

        $process->start();
        usleep(300000);
        $this->assertFalse($process->isTerminated());
        $this->assertContains('Allowed number of loop stops: 10', $process->getOutput());

        $process->signal(SIGINT);
        usleep(300000);
        $this->assertContains('Loop forced to stop', $process->getOutput());
        $this->assertContains('[Shutdown]', $process->getOutput());
        $this->assertNotContains('9 retries missing', $process->getOutput());
        $this->assertTrue($process->isTerminated());
    }
}
