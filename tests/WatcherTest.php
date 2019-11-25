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
 * Class WatcherTest.
 */
class WatcherTest extends TestCase
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'watch',
            '0.0.0.0:9999',
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        usleep(300000);
        file_get_contents('http://localhost:9999?code=200');
        usleep(100000);
        $output = $process->getOutput();
        $this->assertNotFalse(
            strpos(
                $output,
                '[01;32m200[0m GET'
            )
        );
        $process->stop();

        $processKill = new Process(['pkill', '-f', '0.0.0.0:9999', '-c']);
        $processKill->start();
        usleep(300000);
        $processKill->stop();
    }
}
