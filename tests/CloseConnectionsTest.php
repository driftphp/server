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
 * Class CloseConnectionsTest.
 */
class CloseConnectionsTest extends BaseTest
{
    /**
     * @group lol
     */
    public function testKeepAlive()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--debug']);
        list($_, $headers) = Utils::curl("http://127.0.0.1:$port/text");
        $this->waitForChange($process, $initialOutput);
        $this->assertEquals('keep-alive', $headers['Connection']);

        $process->stop();
    }

    /**
     * @group lol
     */
    public function testConnectionCloses()
    {
        list($process, $port, $initialOutput) = $this->buildServer(['--debug', '--close-connections']);
        list($_, $headers) = Utils::curl("http://127.0.0.1:$port/text");
        $this->waitForChange($process, $initialOutput);
        $this->assertEquals('close', $headers['Connection']);

        $process->stop();
    }
}
