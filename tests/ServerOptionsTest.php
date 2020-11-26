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
 * Class ServerOptionsTest.
 */
class ServerOptionsTest extends TestCase
{
    /**
     * Test different options.
     */
    public function testMultipleOptionsAreWorking()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--concurrent-requests=2',
            '--ansi',
            '--request-body-buffer=64',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);

        $this->assertContains(
            '[32;1m200[39;22m GET',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test 0 concurrency.
     */
    public function test0Concurrency()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--concurrent-requests=0',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);

        $this->assertContains(
            '500 EXC',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test body size limited.
     */
    public function testBodySizeLimited()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--request-body-buffer=1',
        ]);

        $process->start();
        usleep(500000);
        list($body, $headers) = Utils::curl("http://127.0.0.1:$port/body", [], [], '', json_encode(array_fill(0, 1000, 'Holis')));
        usleep(500000);

        $body = json_decode($body, true);
        $this->assertEmpty($body['body']);

        $process->stop();
    }

    /**
     * Test body size OK.
     */
    public function testBodySizeOk()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--request-body-buffer=1000',
        ]);

        $process->start();
        usleep(500000);
        list($body, $headers) = Utils::curl("http://127.0.0.1:$port/body", [], [], '', json_encode(array_fill(0, 1000, 'Holis')));
        usleep(500000);

        $body = json_decode($body, true);
        $body = json_decode($body['body'], true);
        $this->assertCount(1000, $body);

        $process->stop();
    }
}
