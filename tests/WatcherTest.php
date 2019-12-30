<?php

/*
 * This file is part of the DriftPHP Project
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
 * Class WatcherTest.
 */
class WatcherTest extends TestCase
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'watch',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        sleep(2);
        Utils::curl("http://127.0.0.1:$port?code=200");
        $output = $process->getOutput();
        $this->assertNotFalse(
            strpos(
                $output,
                '[01;32m200[0m GET'
            )
        );
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
        $port = rand(2000, 9999);
        $changesMessage = 'restarting due to changes';
        file_put_contents(__DIR__.'/sandbox/a.file1', '');
        file_put_contents(__DIR__.'/sandbox2/ignore/b.file1', '');
        file_put_contents(__DIR__.'/sandbox/a.file2', '');
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'watch',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        sleep(2);
        $output = $process->getOutput();
        $this->assertFalse(
            strpos(
                $output,
                $changesMessage
            )
        );

        file_put_contents(__DIR__.'/sandbox/a.file2', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertFalse(
            strpos(
                $output,
                $changesMessage
            )
        );

        file_put_contents(__DIR__.'/sandbox2/ignore/b.file1', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertFalse(
            strpos(
                $output,
                $changesMessage
            )
        );

        file_put_contents(__DIR__.'/sandbox/a.file1', 'content');
        sleep(2);
        $output = $process->getOutput();
        $this->assertNotFalse(
            strpos(
                $output,
                $changesMessage
            )
        );
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
