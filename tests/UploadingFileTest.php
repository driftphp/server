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
 * Class UploadingFileTest.
 */
class UploadingFileTest extends BaseTest
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        list($process, $port, $initialOutput) = $this->buildServer();

        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt']);
        $initialOutput = $this->waitForChange($process, $initialOutput);
        $content = json_decode($content, true);
        list($content2, $headers2) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt', 'anotherfile.txt']);
        $content2 = json_decode($content2, true);
        $this->waitForChange($process, $initialOutput);

        $this->assertEquals(file_get_contents(__DIR__.'/somefile.txt'), $content['files']['somefile'][1]);
        $this->assertEquals(file_get_contents(__DIR__.'/somefile.txt'), $content2['files']['somefile'][1]);
        $this->assertEquals(file_get_contents(__DIR__.'/anotherfile.txt'), $content2['files']['anotherfile'][1]);
        $this->assertFalse(file_exists($content['files']['somefile'][0]));
        $this->assertFalse(file_exists($content2['files']['somefile'][0]));
        $this->assertFalse(file_exists($content2['files']['anotherfile'][0]));
        $this->assertTrue($content['files']['somefile'][2]);
        $this->assertTrue($content2['files']['somefile'][2]);
        $this->assertTrue($content2['files']['anotherfile'][2]);

        $content3 = exec('curl -s -F "file1=lolazo;filename=lolazo.txt" http://127.0.0.1:'.$port.'/file');
        $content3 = json_decode($content3, true);
        $this->assertEquals('lolazo', $content3['files']['file1'][1]);
        $this->assertTrue($content3['files']['file1'][2]);

        $process->stop();
    }

    /**
     * Test upload empty file.
     */
    public function testEmptyFile()
    {
        list($process, $port, $initialOutput) = $this->buildServer();

        $content = exec('curl -s -F "file1=@/dev/null;filename=" http://127.0.0.1:'.$port.'/file');
        $content = json_decode($content, true);
        $this->assertEmpty($content['files']);
        $process->stop();
    }

    /**
     * Test disable file uploads.
     */
    public function testDisableFileUploads()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-file-uploads']);
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/file", [], ['somefile.txt']);
        $content = json_decode($content, true);
        $this->assertEmpty($content['files']);
    }
}
