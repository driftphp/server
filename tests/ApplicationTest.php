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
 * Class ApplicationTest.
 */
class ApplicationTest extends BaseTest
{
    /**
     * Test non blocking server.
     */
    public function testRegular()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
        $this->assertStringContainsString(
            '200 GET',
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
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
        $this->assertStringContainsString(
            '200 GET',
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
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
        $this->assertStringContainsString(
            '200 GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test quiet.
     */
    public function testQuietServer()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--quiet']);
        Utils::curl("http://127.0.0.1:$port?code=200");
        Utils::curl("http://127.0.0.1:$port?code=300");
        Utils::curl("http://127.0.0.1:$port?code=400");
        $this->waitForChange($process, $initialOutput);
        $this->assertEquals(
            '[Preloaded]',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test almost quiet.
     */
    public function testAlmostQuietServer()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--almost-quiet']);
        Utils::curl("http://127.0.0.1:$port?code=200");
        Utils::curl("http://127.0.0.1:$port?code=300");
        Utils::curl("http://127.0.0.1:$port?code=400");
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString(
            'EventLoop',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            '200 GET',
            $process->getOutput()
        );

        $this->assertStringNotContainsString(
            '300 GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '400 GET',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test route not found.
     */
    public function testRouteNotFound()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi']);
        Utils::curl("http://127.0.0.1:$port/another/route");
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString(
            '404 GET',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * Test non blocking server.
     */
    public function testAnsi()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--ansi']);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        Utils::curl("http://127.0.0.1:$port/query?code=404");
        $this->waitForChange($process, $initialOutput);

        $this->assertStringContainsString('[32;1m200[39;22m GET', $process->getOutput());
        $this->assertStringContainsString('[31;1m404[39;22m GET', $process->getOutput());

        $process->stop();
    }

    /**
     * Test another psr7 implementation.
     *
     * @group lol
     */
    public function testAnotherPSR7Implementation()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi'], FakeLaminasKernel::class);
        $response = Utils::curl("http://127.0.0.1:$port/");
        $this->waitForChange($process, $initialOutput);

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
        list($process, $port) = $this->buildServer(['--no-ansi']);
        list($_, $_, $code) = Utils::curl("http://127.0.0.1:$port/check-srv-vars?port=$port");
        $this->assertEquals(200, $code);

        $process->stop();
    }

    /**
     * Test server values.
     */
    public function testBasicAuthHeaders()
    {
        list($process, $port) = $this->buildServer(['--no-ansi']);
        list($content) = Utils::curl("http://127.0.0.1:$port/auth", [
            'authorization: basic '.base64_encode('my_key:my_value'),
        ]);

        $headers = json_decode($content, true);
        $this->assertEquals('my_key', $headers['user']);
        $this->assertEquals('my_value', $headers['password']);

        $process->stop();
    }

    /**
     * Test Symfony adapter.
     */
    public function testSymfonyAdapter()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--no-ansi'], FakeSymfonyAdapter::class);
        Utils::curl("http://127.0.0.1:$port/query?code=200");
        $this->waitForChange($process, $initialOutput);
        $this->assertStringContainsString(
            '200 GET',
            $process->getOutput()
        );

        $this->assertStringContainsString(
            '/query',
            $process->getOutput()
        );

        $process->stop();
    }
}
