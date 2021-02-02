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
 * Class FilesystemMessageTest.
 */
class FilesystemMessageTest extends TestCase
{
    /**
     * Test non blocking server.
     *
     * @group with-filesystem-message
     */
    public function testAttentionFilesystemMessageAppears()
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
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringContainsString(
            'react/filesystem',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     *
     * @group without-filesystem-message
     */
    public function testAttentionFilesystemMessageNotAppears()
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
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringNotContainsString(
            'react/filesystem',
            $process->getOutput()
        );

        $process->stop();
    }
}
