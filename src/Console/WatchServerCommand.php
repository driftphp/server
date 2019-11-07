<?php

/*
 * This file is part of the React Symfony Server package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server\Console;

use Drift\Server\Context\ServerContext;
use Drift\Server\Output\OutputPrinter;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;

/**
 * Class WatchServerCommand.
 */
final class WatchServerCommand extends ServerCommand
{
    /**
     * @var array
     */
    private $argv;

    /**
     * Construct.
     *
     * @param string|null $name
     * @param array       $argv
     * @param string      $bootstrapPath
     */
    public function __construct(
        string $bootstrapPath,
        array $argv,
        string $name
    ) {
        parent::__construct($bootstrapPath, $name);
        $this->argv = $argv;
    }

    /**
     * Run server.
     *
     * @param LoopInterface $loop
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     */
    protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter
    ) {
        $argv = $this->argv;
        $argv[] = '--no-header';
        $path = dirname(__DIR__).'/../vendor/bin/php-watcher';
        $path = realpath($path);
        $script = '"'.addslashes(addslashes(implode(' ', array_values($argv)))).'"';
        $script = str_replace('/server watch ', '/server run ', $script);
        $command = sprintf('%s %s --exec %s %s', PHP_BINARY, $path, PHP_BINARY, $script);

        $process = new Process($command);
        $process->start($loop);
        $process->stdout->on('data', function (string $data) use ($outputPrinter) {
            $outputPrinter->print($data);
        });
    }
}
