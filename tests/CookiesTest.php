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
 * Class CookiesTest.
 */
class CookiesTest extends BaseTest
{
    /**
     * Test cookies are passed.
     */
    public function testCookies()
    {
        list($process, $port) = $this->buildServer();
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/cookies", [], [], 'cookie1=val1');
        $content = json_decode($content, true);
        $this->assertEquals('val1', $content['cookies']['cookie1']);
    }

    /**
     * Test cookies are disabled.
     */
    public function testCookiesDisabled()
    {
        list($process, $port) = $this->buildServer(['--no-cookies']);
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/cookies", [], [], 'cookie1=val1');
        $content = json_decode($content, true);
        $this->assertEmpty($content['cookies']);
    }
}
