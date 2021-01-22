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
use Drift\Server\Adapter\KernelAdapter;
use Drift\Server\Application;
use Drift\Server\ConsoleServerMessage;
use Drift\Server\Context\ServerContext;
use Drift\Server\Mime\MimeTypeChecker;
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
     * @param bool          $forceShutdownReference
     */
    protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        bool &$forceShutdownReference
    ) {
        $rootPath = getcwd();
        $filesystem = Filesystem::create($loop);
        $mimeTypeChecker = new MimeTypeChecker();
        $application = new Application(
            $loop,
            $serverContext,
            $outputPrinter,
            $mimeTypeChecker,
            $rootPath,
            $this->bootstrapPath
        );

        $kernelAdapterNamespace = $serverContext->getAdapter();
        $kernelAdapterNamespace::create(
            $loop,
            $rootPath,
            $serverContext,
            $filesystem,
            $outputPrinter,
            $mimeTypeChecker
        )
            ->then(function (KernelAdapter $kernelAdapter) use ($application, $outputPrinter, $serverContext, $filesystem, &$forceShutdownReference) {
                (new ConsoleServerMessage('Kernel built.', '~', true))->print($outputPrinter);
                (new ConsoleServerMessage('Kernel preloaded.', '~', true))->print($outputPrinter);
                (new ConsoleServerMessage('Kernel ready to accept requests.', '~', true))->print($outputPrinter);

                if ($serverContext->hasExchanges()) {
                    (new ConsoleServerMessage('Kernel connected to exchanges.', '~', true))->print($outputPrinter);
                }

                $applicationCallback = function () use ($application, $kernelAdapter, $filesystem, &$forceShutdownReference) {
                    $application->runServer(
                        $kernelAdapter,
                        $filesystem,
                        $forceShutdownReference
                    );
                };

                if (1 === $serverContext->getWorkers()) {
                    $applicationCallback();
                } else {
                    $this->forkApplication($serverContext->getWorkers(), $applicationCallback, $outputPrinter);
                }
            }, function (Throwable $e) use ($outputPrinter) {
                (new ConsoleServerMessage($e->getMessage(), '~', false))->print($outputPrinter);
                (new ConsoleServerMessage('The server will shut down.', '~', false))->print($outputPrinter);
                (new ConsoleServerMessage('Bye!', '~', false))->print($outputPrinter);
            });
    }

    /**
     * @param int           $timesMissing
     * @param callable      $callable
     * @param OutputPrinter $outputPrinter
     * @param int           $numberOfFork
     */
    private function forkApplication(
        int $timesMissing,
        callable $callable,
        OutputPrinter $outputPrinter,
        int $numberOfFork = 0
    ) {
        $pid = pcntl_fork();
        switch ($pid) {
            case -1:
                // @fail
                die('Fork failed');
                break;

            case 0:
                $GLOBALS['number_of_process'] = str_pad(\strval($numberOfFork), 2, '0', STR_PAD_LEFT);
                (new ConsoleServerMessage("Worker #$numberOfFork starting", '~', true))->print($outputPrinter);
                $callable();
                break;

            default:
                // @parent
                $timesMissing--;
                ++$numberOfFork;
                if ($timesMissing > 0) {
                    $this->forkApplication(
                        $timesMissing,
                        $callable,
                        $outputPrinter,
                        $numberOfFork
                    );
                }

                pcntl_waitpid($pid, $status);
                break;
        }
    }
}
