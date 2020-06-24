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
 * Class ApplicationStaticFolderTest.
 */
class ApplicationStaticFolderTest extends TestCase
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
            '--ansi'
        ]);

        $process->start();
        usleep(300000);

        $this->assertTrue(
            strpos(
                $process->getOutput(),
                'Static Folder: /tests/public/'
            ) > 0
        );

        usleep(100000);
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.js", '$(\'lol\');', 'application/javascript');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.css", '.lol {}', 'text/css');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.txt", 'LOL', 'text/plain');
        usleep(100000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;95m200[0m GET'
            )
        );

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                'tests/public/app.js'
            )
        );

        $process->stop();
    }

    /**
     * Test disable static folder.
     */
    public function testDisabledStaticFolder()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--no-static-folder',
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        $this->assertTrue(
            strpos(
                $process->getOutput(),
                'Static Folder: disabled'
            ) > 0
        );
        usleep(500000);
        list($content, $_) = Utils::curl("http://127.0.0.1:$port/tests/public/app.js");
        $this->assertEmpty($content);

        $process->stop();
    }

    /**
     * @param string $file
     * @param string $expectedContent
     * @param string $expectedMimeType
     */
    private function assertFileWasReceived(string $file, string $expectedContent, string $expectedMimeType): void
    {
        list($content, $headers) = Utils::curl($file);
        $this->assertEquals($expectedContent, $content);
        $this->assertEquals($expectedMimeType, $headers['Content-Type']);
    }
}
