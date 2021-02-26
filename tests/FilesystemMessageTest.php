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
 * Class FilesystemMessageTest.
 */
class FilesystemMessageTest extends BaseTest
{
    /**
     * Test non blocking server.
     *
     * @group with-filesystem-message
     */
    public function testAttentionFilesystemMessageAppears()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
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
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
        $this->assertStringNotContainsString(
            'react/filesystem',
            $process->getOutput()
        );

        $process->stop();
    }
}
