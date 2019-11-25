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
        $this->assertEncodingType('gzip');
        $this->assertEncodingType('deflate');
    }

    /**
     * Assert encoding.
     *
     * @param string $encodingType
     */
    private function assertEncodingType(string $encodingType)
    {
        $process = new Process([
            'php',
            dirname(__FILE__).'/../bin/server',
            'run',
            '0.0.0.0:9999',
            '--adapter='.FakeAdapter::class,
        ]);

        $process->start();
        usleep(300000);

        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => "Accept-Encoding: $encodingType\r\n",
            ],
        ];

        $context = stream_context_create($opts);
        @file_get_contents('http://localhost:9999?code=400', false, $context);
        usleep(300000);
        $this->assertNotFalse(
            strpos(
                $process->getOutput(),
                'Bad Request'
            )
        );

        $process->stop();
    }
}
