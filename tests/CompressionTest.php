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
 * Class CompressionTest.
 */
class CompressionTest extends TestCase
{
    /**
     * Test default adapter static folder.
     */
    public function testRegular()
    {
        $this->assertEncodingType('gzip', function (string $text) {
            return gzdecode($text);
        });
        $this->assertEncodingType('deflate', function (string $text) {
            return gzinflate($text);
        });
    }

    /**
     * Assert encoding.
     *
     * @param string   $encodingType
     * @param callable $decompressCallback
     */
    private function assertEncodingType(string $encodingType, callable $decompressCallback)
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
        $response = Utils::curl("http://127.0.0.1:$port/text", [
            "Accept-Encoding: $encodingType",
        ]);
        usleep(500000);
        $text = $decompressCallback($response[0]);

        $this->assertEquals(
            'This is one text for testing',
            $text
        );

        $process->stop();
    }

    /**
     * Test default adapter static folder.
     */
    public function testEncodedPSR7()
    {
        $this->assertEncodedPSR7('gzip', function (string $text) {
            return gzdecode($text);
        });
        $this->assertEncodedPSR7('deflate', function (string $text) {
            return gzinflate($text);
        });
    }

    /**
     * Test encoded PSR7 Response.
     *
     * @param string   $encodingType
     * @param callable $decompressCallback
     */
    public function assertEncodedPSR7(string $encodingType, callable $decompressCallback)
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
        $response = Utils::curl("http://127.0.0.1:$port/psr", [
            "Accept-Encoding: $encodingType",
        ]);
        $this->assertEquals('ReactPHP Response', $decompressCallback($response[0]));
        $process->stop();
    }

    /**
     * Test default adapter static folder.
     */
    public function testStream()
    {
        $this->assertStream('gzip', function (string $text) {
            return gzdecode($text);
        });
        $this->assertStream('deflate', function (string $text) {
            return gzinflate($text);
        });
    }

    /**
     * Test basic PSR Stream.
     *
     * @param string   $encodingType
     * @param callable $decompressCallback
     */
    public function assertStream(string $encodingType, callable $decompressCallback)
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
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Accept-encoding: $encodingType\r\n",
            ],
        ];
        $context = stream_context_create($opts);
        $stream = fopen("http://127.0.0.1:$port/psr-stream", 'r', false, $context);
        usleep(100000);
        $content = stream_get_contents($stream, 100, 0);
        $this->assertEquals('PHP stream...', $decompressCallback($content));
        $process->stop();
    }

    /**
     * Test static file.
     */
    public function testStaticFile()
    {
        $this->assertStaticFile('gzip', function (string $text) {
            return gzdecode($text);
        });
    }

    /**
     * Test basic PSR Stream.
     *
     * @param string   $encodingType
     * @param callable $decompressCallback
     */
    public function assertStaticFile(string $encodingType, callable $decompressCallback)
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
        $response = Utils::curl("http://127.0.0.1:$port/tests/public/app.js", [
            "Accept-Encoding: $encodingType",
        ]);
        usleep(100000);
        $fileContent = $decompressCallback($response[0]);
        $this->assertEquals('$(\'lol\');', $fileContent);
        $process->stop();
    }
}
