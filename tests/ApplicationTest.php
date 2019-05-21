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

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

/**
 * Class ApplicationTest.
 */
class ApplicationTest extends TestCase
{
    /**
     * Test blocking server.
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
                'Non Blocking: disabled'
            ) > 0
        );
        file_get_contents('http://localhost:9999?code=200');
        usleep(100000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;32m200[0m GET'
            )
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     */
    public function testNonBlockingServer()
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            '0.0.0.0:9999',
            '--bootstrap=autoload',
            '--adapter='.FakeAdapter::class,
            '--non-blocking',
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        $this->assertTrue(
            strpos(
                $process->getOutput(),
                'Non Blocking: enabled'
            ) > 0
        );

        file_get_contents('http://localhost:9999?code=200');
        usleep(100000);
        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;32m200[0m GET'
            )
        );

        $process->stop();
    }

    /**
     * Test silent.
     */
    public function testSilentServer()
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            '0.0.0.0:9999',
            '--bootstrap=autoload',
            '--adapter='.FakeAdapter::class,
            '--silent',
            '--dev',
        ]);

        $process->start();
        usleep(300000);
        file_get_contents('http://localhost:9999?code=200');
        usleep(100000);

        $this->assertEquals(
            '',
            $process->getOutput()
        );
    }
}
