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

use function Clue\React\Block\await;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\Factory;
use React\Stream\ThroughStream;
use Symfony\Component\Process\Process;

/**
 * Class StreamedRequestTest.
 */
class StreamedRequestTest extends TestCase
{
    /**
     * Test basic PSR response.
     */
    public function testBasicPSR7()
    {
        $loop = Factory::create();

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

        $stream = new ThroughStream();
        $promise = Utils::callWithStreamedBody($loop, "http://127.0.0.1:$port/streamed-body", $stream)
            ->then(function (string $response) use (&$data) {
                $this->assertEquals('Received stuff !!!', $response);
            });

        $loop->addTimer(0.1, function () use ($stream) {
            $stream->write('Received ');
            $stream->write('stuff !');
        });

        $loop->addTimer(0.2, function () use ($stream) {
            $stream->end('!!');
        });

        /*
         * @var ResponseInterface $response
         */
        await($promise, $loop);

        $process->stop();
    }
}
