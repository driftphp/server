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
 * Class ApplicationTest.
 */
class ApplicationTest extends TestCase
{
    /**
     * Test non blocking server.
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
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[32;1m200[39;22m GET'
            )
        );

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '/query'
            )
        );

        $process->stop();
    }

    /**
     * Test silent.
     */
    public function testSilentServer()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--quiet',
            '--dev',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port?code=200");
        usleep(500000);

        $this->assertEquals(
            '',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test route not found.
     */
    public function testRouteNotFound()
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
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/another/route?code=200");
        usleep(500000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[31;1m404[39;22m GET'
            )
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     */
    public function testNonAnsi()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
            '--no-ansi'
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '200 GET'
            )
        );

        $process->stop();
    }
}
