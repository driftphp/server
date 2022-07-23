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
 * Class ApplicationStaticFolderTest.
 */
class ApplicationStaticFolderTest extends BaseTest
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        list($process, $port) = $this->buildServer(['--ansi']);

        $this->assertStringContainsString(
            'Static Folder: /tests/public/',
            $process->getOutput()
        );

        usleep(250000);
        $initialOutput = $process->getOutput();
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.js", '$(\'lol\');', 'application/javascript');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.css", '.lol {}', 'text/css');
        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/public/app.txt", 'LOL', 'text/plain');
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString(
            '[01;95m200[0m',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            ' GET ',
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
        list($process, $port, $initialOutput) = $this->buildServer(['--no-static-folder']);

        $this->assertStringContainsString(
            'Static Folder: disabled',
            $process->getOutput()
        );

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
        list($process, $port) = $this->buildServer(['--static-folder=/tests/anotherpublic/']);

        $this->assertStringContainsString(
            'Static Folder: /tests/anotherpublic/',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            'Static Folder: /tests/public/',
            $process->getOutput()
        );

        $this->assertFileWasReceived("http://127.0.0.1:$port/tests/anotherpublic/app.txt", 'LOL ALT', 'text/plain');
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/app.txt");
        $this->assertEquals(404, $statusCode);

        $process->stop();
    }

    /**
     * Test custom static folder.
     */
    public function testStaticFolderAlias()
    {
        list($process, $port) = $this->buildServer(['--static-folder=/rewritten-public/:/tests/public/']);

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

        $this->assertFileWasReceived("http://127.0.0.1:$port/rewritten-public/app.txt", 'LOL', 'text/plain');
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/anotherpublic/app.txt");
        $this->assertEquals(404, $statusCode);
        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/app.txt");
        $this->assertEquals(404, $statusCode);

        $process->stop();
    }

    /**
     * Test files not found.
     */
    public function testFilesNotFound()
    {
        list($process, $port) = $this->buildServer(['--ansi']);

        list($_, $_, $statusCode) = Utils::curl("http://127.0.0.1:$port/tests/public/non-existing-app.txt");
        $this->assertEquals(404, $statusCode);

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
