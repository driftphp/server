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
 * Class PSR7ResponsesTest.
 */
class PSR7ResponsesTest extends TestCase
{
    /**
     * Test basic PSR response.
     */
    public function testBasicPSR7()
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
        usleep(500000);
        $response = Utils::curl("http://127.0.0.1:$port/psr");
        $this->assertEquals('ReactPHP Response', $response[0]);
        $this->assertEquals('17', $response[1]['Content-Length']);
        $process->stop();
    }

    /**
     * Test basic PSR Stream.
     */
    public function testStream()
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
        usleep(500000);
        $stream = fopen("http://127.0.0.1:$port/psr-stream", 'r');
        usleep(100000);
        $content = stream_get_contents($stream, 30, 0);
        $this->assertEquals('PHP stream...', $content);
        $process->stop();
    }
}
