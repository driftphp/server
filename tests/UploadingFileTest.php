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
 * Class UploadingFileTest.
 */
class UploadingFileTest extends TestCase
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
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt']);
        $content = json_decode($content, true);
        list($content2, $headers2) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt', 'anotherfile.txt']);
        $content2 = json_decode($content2, true);
        usleep(300000);

        $this->assertEquals(file_get_contents(__DIR__.'/somefile.txt'), $content['files']['somefile'][1]);
        $this->assertEquals(file_get_contents(__DIR__.'/somefile.txt'), $content2['files']['somefile'][1]);
        $this->assertEquals(file_get_contents(__DIR__.'/anotherfile.txt'), $content2['files']['anotherfile'][1]);
        $this->assertFalse(file_exists($content['files']['somefile'][0]));
        $this->assertFalse(file_exists($content2['files']['somefile'][0]));
        $this->assertFalse(file_exists($content2['files']['anotherfile'][0]));
        $this->assertTrue($content['files']['somefile'][2]);
        $this->assertTrue($content2['files']['somefile'][2]);
        $this->assertTrue($content2['files']['anotherfile'][2]);

        $process->stop();
    }

    /**
     * Test upload empty file.
     */
    public function testEmptyFile()
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
        usleep(300000);

        $content = exec('curl -s -F "file1=@/dev/null;filename=" http://127.0.0.1:'.$port.'/file');
        $content = json_decode($content, true);
        $this->assertFalse($content['files']['file1'][2]);
        $process->stop();
    }

    /**
     * Test disable file uploads.
     */
    public function testDisableFileUploads()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--no-file-uploads',
            '--dev',
        ]);

        $process->start();
        usleep(300000);
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt']);
        $content = json_decode($content, true);
        $this->assertEmpty($content['files']);
    }
}
