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
use Exception;
use React\EventLoop\Factory as EventLoopFactory;
use React\EventLoop\LoopInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerCommand.
 */
abstract class ServerCommand extends Command
{
    /**
     * @var string
     */
    protected $bootstrapPath;

    /**
     * Construct.
     *
     * @param string|null $name
     * @param string      $bootstrapPath
     */
    public function __construct(
        string $bootstrapPath,
        string $name
    ) {
        parent::__construct($name);
        $this->bootstrapPath = $bootstrapPath;
    }

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
            ->addOption('no-header', null, InputOption::VALUE_NONE, 'Disabled the header')
            ->addOption('adapter', null, InputOption::VALUE_OPTIONAL, 'Server Adapter', 'drift');
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
        $serverContext = ServerContext::buildByInput($input);
        $outputPrinter = new OutputPrinter($output);
        $loop = EventLoopFactory::create();
        if ($serverContext->printHeader()) {
            $outputPrinter->printServerHeader(
                $serverContext,
                $this->bootstrapPath
            );
        }

        $this->executeServerCommand(
            $loop,
            $serverContext,
            $outputPrinter
        );

        $loop->run();
    }

    /**
     * Run server.
     *
     * @param LoopInterface $loop
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     */
    abstract protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter
    );
}
