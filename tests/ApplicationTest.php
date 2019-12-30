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
        ]);

        $process->start();
        usleep(300000);
        Utils::curl("http://127.0.0.1:$port/valid/query?code=200");
        usleep(100000);
        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;32m200[0m GET'
            )
        );

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '/valid/query'
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
        usleep(300000);
        Utils::curl("http://127.0.0.1:$port?code=200");
        usleep(100000);

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
        ]);

        $process->start();
        usleep(300000);
        Utils::curl("http://127.0.0.1:$port/another/route?code=200");
        usleep(300000);

        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                '[01;31m404[0m GET'
            )
        );

        $process->stop();
    }
}
