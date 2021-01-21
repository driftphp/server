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
 * Class workersTest.
 */
class WorkersTest extends TestCase
{
    public function testOneWorker()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--workers=1',
        ]);

        $process->start();
        sleep(2);
        Utils::curl("http://127.0.0.1:$port/text");
        sleep(1);

        $output = $process->getOutput();
        $this->assertContains('Workers: 1', $output);
        $this->assertContains('200 GET', $output);
        $this->assertNotContains('[00] ', $output);
        $this->assertNotContains('[01] ', $output);
        $this->assertNotContains('[02] ', $output);

        $process->stop();
    }

    public function testNoWorkerDefault()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        sleep(2);
        Utils::curl("http://127.0.0.1:$port/text");
        sleep(1);

        $output = $process->getOutput();
        $this->assertContains('Workers: 1', $output);
        $this->assertContains('200 GET', $output);
        $this->assertNotContains('[00] ', $output);
        $this->assertNotContains('[01] ', $output);
        $this->assertNotContains('[02] ', $output);

        $process->stop();
    }

    public function testSeveralWorkers()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--workers=2',
        ]);

        $process->start();
        sleep(2);
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
        $this->assertContains('Workers: 2', $output);
        $this->assertContains('[00] ', $output);
        $this->assertContains('[01] ', $output);
        $this->assertNotContains('[02] ', $output);

        $process->stop();
    }

    public function testWorkersOptimized()
    {
        $numberOfProcs = \intval(shell_exec('nproc'));
        $numberOfRequests = $numberOfProcs * 10;
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--workers=-1',
        ]);

        $process->start();
        sleep(2);

        for ($i = 0; $i < $numberOfRequests; ++$i) {
            Utils::curl("http://127.0.0.1:$port/text");
        }

        $output = $process->getOutput();
        sleep(2);
        $this->assertContains("Workers: $numberOfProcs", $output);
        for ($i = 0; $i < $numberOfProcs; ++$i) {
            $iWithTrailingZeros = str_pad(\strval($i), 2, '0', STR_PAD_LEFT);
            $this->assertContains("[$iWithTrailingZeros] ", $output);
        }
    }
}
