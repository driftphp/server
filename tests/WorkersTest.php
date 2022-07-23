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
 * Class workersTest.
 */
class WorkersTest extends BaseTest
{
    public function testOneWorker()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi', '--workers=1']);
        Utils::curl("http://127.0.0.1:$port/text");
        $output = $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString('Workers: 1', $output);
        $this->assertStringContainsString('200', $output);
        $this->assertStringContainsString(' GET ', $output);
        $this->assertStringNotContainsString('[00] ', $output);
        $this->assertStringNotContainsString('[01] ', $output);
        $this->assertStringNotContainsString('[02] ', $output);

        $process->stop();
    }

    public function testNoWorkerDefault()
    {
        list($process, $port, $initialOutput) = $this->buildServer();
        Utils::curl("http://127.0.0.1:$port/text");
        $output = $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString('Workers: 1', $output);
        $this->assertStringContainsString('200', $output);
        $this->assertStringContainsString(' GET ', $output);
        $this->assertStringNotContainsString('[00] ', $output);
        $this->assertStringNotContainsString('[01] ', $output);
        $this->assertStringNotContainsString('[02] ', $output);

        $process->stop();
    }

    public function testSeveralWorkers()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi', '--workers=2']);
        Utils::curl("http://127.0.0.1:$port/text");
        $this->waitForChange($process, $initialOutput);
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");
        Utils::curl("http://127.0.0.1:$port/text");

        $output = $process->getOutput();
        $this->assertStringContainsString('Workers: 2', $output);
        $this->assertStringContainsString('[00] ', $output);
        $this->assertStringContainsString('[01] ', $output);
        $this->assertStringNotContainsString('[02] ', $output);

        $process->stop();
    }

    public function testWorkersOptimized()
    {
        $numberOfProcs = \intval(shell_exec('nproc'));
        $numberOfRequests = $numberOfProcs * 10;
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi', '--workers=-1']);
        Utils::curl("http://127.0.0.1:$port/text");
        $this->waitForChange($process, $initialOutput);

        for ($i = 0; $i < $numberOfRequests; ++$i) {
            Utils::curl("http://127.0.0.1:$port/text");
        }

        $output = $process->getOutput();
        sleep(2);
        $this->assertStringContainsString("Workers: $numberOfProcs", $output);
        for ($i = 0; $i < $numberOfProcs; ++$i) {
            $iWithTrailingZeros = str_pad(\strval($i), 2, '0', STR_PAD_LEFT);
            $this->assertStringContainsString("[$iWithTrailingZeros] ", $output);
        }
    }
}
