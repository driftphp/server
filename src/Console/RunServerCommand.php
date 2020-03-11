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

use Drift\Console\OutputPrinter;
use Drift\HttpKernel\AsyncKernel;
use Drift\Server\Application;
use Drift\Server\ConsoleServerMessage;
use Drift\Server\Context\ServerContext;
use Drift\Server\Mime\MimeTypeChecker;
use Drift\Server\RequestHandler;
use React\EventLoop\LoopInterface;
use React\Filesystem\Filesystem;
use Throwable;

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
        $requestHandler = new RequestHandler($outputPrinter, new MimeTypeChecker());
        $filesystem = Filesystem::create($loop);

        $application = new Application(
            $loop,
            $serverContext,
            $outputPrinter,
            $rootPath,
            $this->bootstrapPath
        );

        $application
            ->buildAKernel()
            ->then(function (AsyncKernel $kernel) use ($application, $requestHandler, $filesystem, $outputPrinter, $serverContext) {
                (new ConsoleServerMessage('Kernel built.', '~', true))->print($outputPrinter);
                (new ConsoleServerMessage('Kernel preloaded.', '~', true))->print($outputPrinter);
                (new ConsoleServerMessage('Kernel ready to accept requests.', '~', true))->print($outputPrinter);

                if ($serverContext->hasExchanges()) {
                    (new ConsoleServerMessage('Kernel connected to exchanges.', '~', true))->print($outputPrinter);
                }

                $application->run(
                    $kernel,
                    $requestHandler,
                    $filesystem
                );
            }, function (Throwable $e) use ($outputPrinter) {
                (new ConsoleServerMessage($e->getMessage(), '~', false))->print($outputPrinter);
                (new ConsoleServerMessage('The server will shut down.', '~', false))->print($outputPrinter);
                (new ConsoleServerMessage('Bye!', '~', false))->print($outputPrinter);
            });
    }
}
