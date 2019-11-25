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

namespace Drift\Server\Console;

use Drift\Server\Context\ServerContext;
use Drift\Server\Output\OutputPrinter;
use Drift\Server\RequestHandler;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;

/**
 * Class RunServerCommand.
 */
final class RunServerCommand extends ServerCommand
{
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
        $rootPath = getcwd();
        $requestHandler = new RequestHandler($outputPrinter);
        $filesystem = Filesystem::create($loop);

        $application = new \Drift\Server\Application(
            $loop,
            $requestHandler,
            $filesystem,
            $serverContext,
            $outputPrinter,
            $rootPath,
            $this->bootstrapPath
        );

        $application->run();
    }
}
