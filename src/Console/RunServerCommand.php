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

namespace Apisearch\SymfonyReactServer\Console;

use Apisearch\SymfonyReactServer\Adapter\KernelAdapter;
use Apisearch\SymfonyReactServer\Adapter\Symfony4KernelAdapter;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RunServerCommand.
 */
class RunServerCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Run the server')
            ->addArgument('path', InputArgument::REQUIRED, 'The server will start listening to this address')
            ->addOption('env', null, InputOption::VALUE_OPTIONAL, 'Environment', 'prod')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Dev environment')
            ->addOption('static-folder', null, InputOption::VALUE_OPTIONAL, 'Static folder path', '')
            ->addOption('no-static-folder', null, InputOption::VALUE_NONE, 'Disable static folder')
            ->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug')
            ->addOption('adapter', null, InputOption::VALUE_OPTIONAL, 'Server Adapter', 'symfony4');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootPath = getcwd();
        $environment = $input->getOption('dev')
            ? 'dev'
            : $input->getOption('env');
        $silent = $input->getOption('quiet');
        $staticFolder = $input->getOption('static-folder');
        $staticFolder = $input->getOption('no-static-folder') ? null : $staticFolder;
        $debug = $input->getOption('debug');

        $adapter = $input->getOption('adapter');
        $adapter = [
                'symfony4' => Symfony4KernelAdapter::class,
            ][$adapter] ?? $adapter;

        if (!is_a($adapter, KernelAdapter::class, true)) {
            die('You must define an existing kernel adapter, or by an alias or my a namespace. This class MUST implement KernelAdapter'.PHP_EOL);
        }

        $path = $input->getArgument('path');
        $serverArgs = explode(':', $path, 2);
        if (2 !== count($serverArgs)) {
            throw new Exception('The path should have a host:port format - 0.0.0.0:80');
        }

        list($host, $port) = $serverArgs;

        $application = new \Apisearch\SymfonyReactServer\Application(
            $rootPath,
            $host,
            \intval($port),
            $environment,
            $debug,
            $silent,
            $adapter,
            $staticFolder
        );

        $application->run();
    }
}
