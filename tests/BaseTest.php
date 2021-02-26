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
 * Class BaseTest.
 */
abstract class BaseTest extends TestCase
{
    /**
     * @param array  $params
     * @param string $adapter
     * @param string $command
     *
     * @return array
     */
    protected function buildServer(
        array $params = [],
        string $adapter = FakeAdapter::class,
        string $command = 'run'
    ): array {
        $port = rand(2000, 9999);
        $process = new Process(array_merge([
            'php',
            dirname(__FILE__).'/../bin/server',
            $command,
            "0.0.0.0:$port",
            '--adapter='.$adapter,
        ], $params));

        $process->start();

        if (in_array('--quiet', $params)) {
            sleep(1);
        } else {
            while (true) {
                if (str_contains($process->getOutput(), 'EventLoop is running')) {
                    break;
                }

                usleep(1000);
            }
        }

        return [$process, $port, $process->getOutput()];
    }

    /**
     * @param array  $params
     * @param string $adapter
     *
     * @return array
     */
    protected function buildWatcher(
        array $params = [],
        string $adapter = FakeAdapter::class
    ): array {
        return $this->buildServer($params, $adapter, 'watch');
    }

    /**
     * @param Process $process
     * @param string  $initialOutput
     *
     * @return string
     */
    protected function waitForChange(
        Process $process,
        string $initialOutput
    ) {
        $now = time();
        while (true) {
            usleep(100000);
            if ($initialOutput !== $process->getOutput()) {
                break;
            }

            if ((time() - $now) > 1) {
                break;
            }
        }

        return $process->getOutput();
    }
}
