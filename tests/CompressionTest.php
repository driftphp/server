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
 * Class CompressionTest.
 */
class CompressionTest extends BaseTest
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
        list($process, $port, $initialOutput) = $this->buildServer();
        $response = Utils::curl("http://127.0.0.1:$port/text", [
            "Accept-Encoding: $encodingType",
        ]);
        $this->waitForChange($process, $initialOutput);
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
        list($process, $port) = $this->buildServer();
        $response = Utils::curl("http://127.0.0.1:$port/psr", [
            "Accept-Encoding: $encodingType",
        ]);
        $this->assertEquals('ReactPHP Response', $decompressCallback($response[0]));
        $process->stop();
    }

    /**
     * @group lol
     */
    public function testAlreadyEncodedContent()
    {
        list($process, $port) = $this->buildServer();
        $response = Utils::curl("http://127.0.0.1:$port/gzip", [
            'Accept-Encoding: gzip',
        ]);

        $this->assertEquals('ReactPHP Response', gzdecode($response[0]));
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
        list($process, $port, $initialOutput) = $this->buildServer();
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Accept-encoding: $encodingType\r\n",
            ],
        ];
        $context = stream_context_create($opts);
        $stream = fopen("http://127.0.0.1:$port/psr-stream", 'r', false, $context);
        $this->waitForChange($process, $initialOutput);
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
        list($process, $port, $initialOutput) = $this->buildServer();
        $response = Utils::curl("http://127.0.0.1:$port/tests/public/app.js", [
            "Accept-Encoding: $encodingType",
        ]);
        $this->waitForChange($process, $initialOutput);
        $fileContent = $decompressCallback($response[0]);
        $this->assertEquals('$(\'lol\');', $fileContent);
        $process->stop();
    }
}
