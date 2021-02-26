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
 * Class PSR7ResponsesTest.
 */
class PSR7ResponsesTest extends BaseTest
{
    /**
     * Test basic PSR response.
     */
    public function testBasicPSR7()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--ansi']);
        $response = Utils::curl("http://127.0.0.1:$port/psr");
        $this->waitForChange($process, $initialOutput);
        $this->assertEquals('ReactPHP Response', $response[0]);
        $this->assertEquals('17', $response[1]['Content-Length']);
        $process->stop();
    }

    /**
     * Test basic PSR Stream.
     */
    public function testStream()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--ansi']);
        $stream = fopen("http://127.0.0.1:$port/psr-stream", 'r');
        $this->waitForChange($process, $initialOutput);
        $content = stream_get_contents($stream, 30, 0);
        $this->assertEquals('PHP stream...', $content);
        $process->stop();
    }
}
