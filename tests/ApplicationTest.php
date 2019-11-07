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
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            '0.0.0.0:9999',
            '--adapter='.FakeAdapter::class,
            '--dev',
        ]);

        $process->start();
        usleep(300000);
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
            'run',
            '0.0.0.0:9999',
            '--adapter='.FakeAdapter::class,
            '--quiet',
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
