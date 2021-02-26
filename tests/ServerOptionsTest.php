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
 * Class ServerOptionsTest.
 */
class ServerOptionsTest extends BaseTest
{
    /**
     * Test different options.
     */
    public function testMultipleOptionsAreWorking()
    {
        list($process, $port, $initialOutput) = $this->buildServer([
            '--concurrent-requests=2',
            '--request-body-buffer=64',
        ]);

        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString(
            '200 GET',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test 0 concurrency.
     */
    public function test0Concurrency()
    {
        list($process, $port, $initialOutput) = $this->buildServer([
            '--concurrent-requests=0',
        ]);

        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString(
            '500 EXC',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test body size limited.
     *
     * @group lala
     */
    public function testBodySizeLimited()
    {
        list($process, $port, $initialOutput) = $this->buildServer([
            '--request-body-buffer=1',
        ]);

        list($body, $headers, $statusCode) = Utils::curl("http://127.0.0.1:$port/body", [], [], '', json_encode(array_fill(0, 1000, 'Holis')));
        $this->waitForChange($process, $initialOutput);
        $body = json_decode($body, true);
        $this->assertEmpty($body['body']);

        $process->stop();
    }

    /**
     * Test body size OK.
     */
    public function testBodySizeOk()
    {
        list($process, $port, $initialOutput) = $this->buildServer([
            '--request-body-buffer=1000',
        ]);

        list($body, $headers) = Utils::curl("http://127.0.0.1:$port/body", [], [], '', json_encode(array_fill(0, 1000, 'Holis')));
        $this->waitForChange($process, $initialOutput);

        $body = json_decode($body, true);
        $body = json_decode($body['body'], true);
        $this->assertCount(1000, $body);

        $process->stop();
    }
}
