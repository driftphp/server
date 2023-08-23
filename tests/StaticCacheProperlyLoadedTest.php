<?php

namespace Drift\Server\Tests;

class StaticCacheProperlyLoadedTest extends BaseTest
{

    /**
     * Test different options.
     */
    public function testFileIsLoadedProperly()
    {
        list($process, $port) = $this->buildServer(['--ansi', '--static-cache=' . __DIR__ .'/public/static.conf.yml']);

        $this->assertStringContainsString(
            'public/static.conf.yml',
            $process->getOutput()
        );

        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/tests/public/app.js");
        $this->assertEquals('63', $headers['cache-control']);
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/tests/public/anotherfile.js");
        $this->assertEquals('value', $headers['myheader']);
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/tests/public/yetanother.js");
        $this->assertEquals(null, $headers['myheader'] ?? null);

        $process->stop();
    }

    public function testFileIsNotFound()
    {
        list($process, $port) = $this->buildServer(['--ansi', '--static-cache=' . __DIR__ .'/public/static.conf.notfound.yml']);

        $this->assertStringContainsString(
            'Static cache file: Attention! File  not found',
            $process->getOutput()
        );

        $process->stop();
    }

    /**
     * This case is only to test that the whole server does not crash with a bad format file
     * @return void
     */
    public function testFileHasBadFormat()
    {
        list($process, $port) = $this->buildServer(['--ansi', '--static-cache=' . __DIR__ .'/public/static.conf.badformat.yml']);
        $this->expectNotToPerformAssertions();
        list($content, $headers) = Utils::curl("http://127.0.0.1:$port/tests/public/app.js");

        $process->stop();
    }
}