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

use Drift\Server\Application;
use Drift\Server\Context\ServerContext;
use Drift\Server\Output\OutputPrinter;
use Drift\Server\Watcher\ObservableKernel;
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
     *
     * @throws \Exception Watcher not found
     */
    protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter
    ) {
        $rootPath = getcwd();
        $application = new Application(
            $loop,
            $serverContext,
            $outputPrinter,
            $rootPath,
            $this->bootstrapPath
        );

        $argv = $this->argv;
        $argv[] = '--no-header';

        $dirname = dirname(__DIR__);
        $found = false;
        $paths = [
            '../../../../vendor/bin/php-watcher',
            '../../../../bin/php-watcher',
            '../vendor/bin/php-watcher',
        ];

        $completePath = null;
        foreach ($paths as $path) {
            $completePath = "$dirname/$path";
            if (is_file($completePath)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new \Exception('The executable php-watcher was not found. Check dependencies');
        }

        $kernelAdapter = $application->getKernelAdapter();
        $extra = [];
        if (is_subclass_of($kernelAdapter, ObservableKernel::class)) {
            $folders = $this->formatFolders($kernelAdapter::getObservableFolders(), $rootPath);
            $extensions = $kernelAdapter::getObservableExtensions();
            $ignoredFolders = $this->formatIgnoreFolders($kernelAdapter::getIgnorableFolders());

            $extra[] = sprintf(
                '%s --ext=%s %s',
                implode(' ', $folders),
                implode(',', $extensions),
                implode(' ', $ignoredFolders)
            );
        }

        $completePath = realpath($completePath);
        $script = '"'.addslashes(addslashes(implode(' ', array_values($argv)))).'"';
        $script = str_replace('/server watch ', '/server run ', $script);
        $command = sprintf('%s %s --exec %s %s %s', PHP_BINARY, $completePath, PHP_BINARY, $script, implode(' ', $extra));

        $process = new Process($command);
        $process->start($loop);
        $process->stdout->on('data', function (string $data) use ($outputPrinter) {
            $outputPrinter->print($data);
        });
    }

    /**
     * Format folder array.
     *
     * @param string[] $folders
     * @param string   $rootPath
     */
    private function formatFolders(
        array $folders,
        string $rootPath
    ): array {
        $folders = array_map(function (string $path) use ($rootPath) {
            $path = sprintf('%s/%s/', $rootPath, trim($path, '/'));

            return is_file($path) || is_dir($path)
                ? $path
                : false;
        }, $folders);
        $folders = array_filter($folders);
        $folders = array_map(function (string $folder) {
            return "--watch $folder";
        }, $folders);

        return $folders;
    }

    /**
     * Format ignore array.
     *
     * @param string[] $ignoreFolders
     */
    private function formatIgnoreFolders(array $ignoreFolders): array
    {
        return array_map(function (string $folder) {
            return "--ignore $folder";
        }, $ignoreFolders);
    }
}
