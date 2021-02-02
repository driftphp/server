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
            '--ansi',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringContainsString(
            '[32;1m200[39;22m GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     */
    public function testDefaultHost()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
            '--ansi',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringContainsString(
            '[32;1m200[39;22m GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     */
    public function testEmptyHost()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            ":$port",
            '--adapter='.FakeAdapter::class,
            '--dev',
            '--ansi',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringContainsString(
            '[32;1m200[39;22m GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
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
            '[Preloaded]',
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
            '--ansi',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/another/route?code=200");
        usleep(500000);

        $this->assertStringContainsString(
            '[31;1m404[39;22m GET',
            $process->getOutput()
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
            '--no-ansi',
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

    /**
     * Test another psr7 implementation.
     */
    public function testAnotherPSR7Implementation()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeLaminasKernel::class,
        ]);

        $process->start();
        usleep(500000);
        $response = Utils::curl("http://127.0.0.1:$port/");
        usleep(500000);

        $this->assertStringContainsString(
            '200 GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            'Laminas Response',
            $response[0]
        );

        $process->stop();
    }

    /**
     * Test server values.
     */
    public function testServerValues()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        usleep(500000);
        list($_, $_, $code) = Utils::curl("http://127.0.0.1:$port/check-srv-vars?port=$port");
        $this->assertEquals(200, $code);
        usleep(500000);

        $process->stop();
    }

    /**
     * Test server values.
     */
    public function testBasicAuthHeaders()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        usleep(500000);
        list($content) = Utils::curl("http://127.0.0.1:$port/auth", [
            'authorization: basic '.base64_encode('my_key:my_value'),
        ]);

        $headers = json_decode($content, true);
        $this->assertEquals('my_key', $headers['user']);
        $this->assertEquals('my_value', $headers['password']);
        usleep(500000);

        $process->stop();
    }

    /**
     * Test Symfony adapter.
     */
    public function testSymfonyAdapter()
    {
        $port = rand(2000, 9999);
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            "0.0.0.0:$port",
            '--adapter='.FakeSymfonyAdapter::class,
            '--dev',
            '--ansi',
        ]);

        $process->start();
        usleep(500000);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        usleep(500000);
        $this->assertStringContainsString(
            '[32;1m200[39;22m GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
        );

        $process->stop();
    }
}
