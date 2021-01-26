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
            '--ansi',
        ]);

        $process->start();
        usleep(300000);

        $this->assertStringContainsString(
            'Static Folder: /tests/public/',
            $process->getOutput()
        );

        usleep(250000);
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.js", '$(\'lol\');', 'application/javascript');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.css", '.lol {}', 'text/css');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.txt", 'LOL', 'text/plain');
        usleep(250000);

        $this->assertStringContainsString(
            '[01;95m200[0m GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            'tests/public/app.js',
            $process->getOutput()
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

        $this->assertStringContainsString(
            'Static Folder: disabled',
            $process->getOutput()
        );

        usleep(500000);
        list($content, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/app.js");
        $this->assertEmpty($content);
        $this->assertEquals(404, $statusCode);

        $process->stop();
    }

    /**
     * Test custom static folder.
     */
    public function testCustomStaticFolder()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--static-folder=/tests/anotherpublic/',
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        $this->assertStringContainsString(
            'Static Folder: /tests/anotherpublic/',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            'Static Folder: /tests/public/',
            $process->getOutput()
        );

        usleep(250000);
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/anotherpublic/app.txt", 'LOL ALT', 'text/plain');
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/app.txt");
        $this->assertEquals(404, $statusCode);
        usleep(250000);

        $process->stop();
    }

    /**
     * Test custom static folder.
     */
    public function testStaticFolderAlias()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--static-folder=/rewritten-public/:/tests/public/',
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        $this->assertStringNotContainsString(
            'Static Folder: /tests/anotherpublic/',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            'Static Folder: /tests/public/',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            'Static Folder: /rewritten-public/ resolves to /tests/anotherpublic/',
            $process->getOutput()
        );

        usleep(250000);
        $this->assertFileWasReceived("http://127.0.0.1:$port/rewritten-public/app.txt", 'LOL', 'text/plain');
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/anotherpublic/app.txt");
        $this->assertEquals(404, $statusCode);
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/app.txt");
        $this->assertEquals(404, $statusCode);
        usleep(250000);

        $process->stop();
    }

    /**
     * Test files not found.
     */
    public function testFilesNotFound()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
            '--ansi',
        ]);

        $process->start();
        usleep(300000);

        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/non-existing-app.txt");
        $this->assertEquals(404, $statusCode);
        usleep(250000);

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
