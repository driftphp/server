<?php

/*
 * This file is part of the React Symfony Server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\SymfonyReactServer\Tests;

use finfo;
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
    public function testBlockingServer()
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            '0.0.0.0:9999',
            '--bootstrap=autoload',
            '--adapter='.FakeAdapter::class,
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        $this->assertTrue(
            strpos(
                $process->getOutput(),
                'Static Folder: /tests/public'
            ) > 0
        );

        $this->assertFileWasReceived('http://localhost:9999/tests/public/app.js', '// Some app', 'text/plain');
        usleep(100000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;95m200[0m GET'
            )
        );

        $process->stop();
    }

    /**
     * Test disable static folder.
     */
    public function testDisabledStaticFolder()
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            '0.0.0.0:9999',
            '--bootstrap=autoload',
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

        $content = @file_get_contents('http://localhost:9999/tests/public/app.js');
        $this->assertFalse($content);

        $process->stop();
    }

    private function assertFileWasReceived(string $file, string $expectedContent, string $expectedMimeType): void
    {
        $content = file_get_contents($file);
        $this->assertEquals($expectedContent, $content);

        $fileInfo = new Finfo(FILEINFO_MIME_TYPE);
        $this->assertEquals($expectedMimeType, $fileInfo->buffer($content));
    }
}
