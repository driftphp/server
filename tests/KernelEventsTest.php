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
 * Class KernelEventsTest.
 */
class KernelEventsTest extends BaseTest
{
    /**
     * Test cookies are passed.
     */
    public function testPreload()
    {
        list($process) = $this->buildServer(['--ansi']);
        $this->assertStringContainsString('Kernel preloaded', $process->getOutput());
        $this->assertStringContainsString('[Preloaded]', $process->getOutput());
        $this->assertFalse($process->isTerminated());
    }

    /**
     * Test cookies are passed.
     */
    public function testShutdown()
    {
        list($process) = $this->buildServer(['--ansi']);
        $this->assertFalse($process->isTerminated());

        $process->signal(SIGTERM);
        sleep(1);
        $this->assertStringContainsString('Loop forced to stop', $process->getOutput());
        $this->assertStringContainsString('[Shutdown]', $process->getOutput());
        $this->assertTrue($process->isTerminated());
    }

    /**
     * Test cookies are passed.
     */
    public function testShutdownIsForced()
    {
        list($process) = $this->buildServer(['--ansi', '--allowed-loop-stops=10']);
        $this->assertFalse($process->isTerminated());
        $this->assertStringContainsString('Allowed number of loop stops: 10', $process->getOutput());

        $process->signal(SIGINT);
        usleep(300000);
        $this->assertStringContainsString('Loop forced to stop', $process->getOutput());
        $this->assertStringContainsString('[Shutdown]', $process->getOutput());
        $this->assertStringNotContainsString('9 retries missing', $process->getOutput());
        $this->assertTrue($process->isTerminated());
    }
}
