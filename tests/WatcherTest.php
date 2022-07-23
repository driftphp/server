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

use Symfony\Component\Process\Process;

/**
 * Class WatcherTest.
 */
class WatcherTest extends BaseTest
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        list($process, $port) = $this->buildWatcher(['--no-ansi']);
        sleep(2);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(300000);
        $output = $process->getOutput();
        $this->assertStringContainsString('Workers: 1', $output);
        $this->assertStringContainsString('200', $output);
        $this->assertStringContainsString('GET', $output);
        $process->stop();

        $processKill = new Process(['pkill', '-f', "0.0.0.0:$port", '-c']);
        $processKill->start();
        sleep(2);
        $processKill->stop();
    }

    /**
     * Test default adapter static folder.
     */
    public function testFileWatching()
    {
        $changesMessage = 'restarting due to changes';
        file_put_contents(__DIR__.'/sandbox/a.file1', '');
        file_put_contents(__DIR__.'/sandbox2/ignore/b.file1', '');
        file_put_contents(__DIR__.'/sandbox/a.file2', '');

        list($process, $port, $output) = $this->buildWatcher(['--no-ansi', '--workers=8']);

        $this->assertStringContainsString('Workers: 1', $output);
        $this->assertStringNotContainsString($changesMessage, $output);

        file_put_contents(__DIR__.'/sandbox/a.file2', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertStringNotContainsString($changesMessage, $output);

        file_put_contents(__DIR__.'/sandbox2/ignore/b.file1', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertStringNotContainsString($changesMessage, $output);

        file_put_contents(__DIR__.'/sandbox/a.file1', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertStringContainsString($changesMessage, $output);
        file_put_contents(__DIR__.'/sandbox/a.file1', '');
        file_put_contents(__DIR__.'/sandbox2/ignore/b.file1', '');
        file_put_contents(__DIR__.'/sandbox/a.file2', '');
        $process->stop();

        $processKill = new Process(['pkill', '-f', "0.0.0.0:$port", '-c']);
        $processKill->start();
        sleep(2);
        $processKill->stop();
    }
}
