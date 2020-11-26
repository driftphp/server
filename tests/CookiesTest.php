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
 * Class CookiesTest.
 */
class CookiesTest extends TestCase
{
    /**
     * Test cookies are passed.
     */
    public function testCookies()
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

        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/cookies", [], [], 'cookie1=val1');
        $content = json_decode($content, true);
        $this->assertEquals('val1', $content['cookies']['cookie1']);
    }

    /**
     * Test cookies are disabled.
     */
    public function testCookiesDisabled()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
            '--no-cookies',
            '--dev',
        ]);

        $process->start();
        usleep(300000);

        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/cookies", [], [], 'cookie1=val1');
        $content = json_decode($content, true);
        $this->assertEmpty($content['cookies']);
    }
}
