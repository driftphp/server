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
use Drift\EventBus\Bus\EventBus;
use Drift\EventLoop\EventLoopUtils;
use Drift\Server\Console\Style\Muted;
use Drift\Server\Console\Style\Purple;
use Drift\Server\ConsoleServerMessage;
use Drift\Server\Context\ServerContext;
use Drift\Server\ServerHeaderPrinter;
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
    protected string $bootstrapPath;

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
            ->addOption('no-header', null, InputOption::VALUE_NONE, 'Disable the header')
            ->addOption('no-cookies', null, InputOption::VALUE_NONE, 'Disable cookies')
            ->addOption('no-file-uploads', null, InputOption::VALUE_NONE, 'Disable file uploads')
            ->addOption('concurrent-requests', null, InputOption::VALUE_OPTIONAL, 'Limit of concurrent requests', 100)
            ->addOption('request-body-buffer', null, InputOption::VALUE_OPTIONAL, 'Limit of the buffer used for the Request body. In KiB.', 1024)
            ->addOption('adapter', null, InputOption::VALUE_OPTIONAL, 'Server Adapter. Can be a namespace or a shortcut. Available shortcuts [drift, symfony]', 'drift')
            ->addOption('allowed-loop-stops', null, InputOption::VALUE_OPTIONAL, 'Number of allowed loop stops', 0)
            ->addOption('workers', null, InputOption::VALUE_OPTIONAL,
                'Number of workers. Use -1 to get as many workers as physical thread available for your system. Maximum of 128 workers. Option disabled for watch command.', 1
            );

        /*
         * If we have the EventBus loaded, we can add listeners as well
         */
        if (class_exists(EventBus::class)) {
            $this->addOption(
                'exchange',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Exchanges to listen'
            );
        }
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

        if ($serverContext->isDebug()) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
        } else {
            ini_set('display_errors', '0');
        }

        $this->configureServerContext($serverContext);
        $outputPrinter = $this->createOutputPrinter($output, $serverContext->isQuiet());

        $loop = EventLoopFactory::create();
        if ($serverContext->printHeader()) {
            ServerHeaderPrinter::print(
                $serverContext,
                $outputPrinter,
                $this->bootstrapPath
            );
        }

        $forceShutdownReference = false;
        $this->executeServerCommand(
            $loop,
            $serverContext,
            $outputPrinter,
            $forceShutdownReference
        );

        (new ConsoleServerMessage('EventLoop is running.', '~', true))->print($outputPrinter);
        EventLoopUtils::runLoop($loop, (\intval($input->getOption('allowed-loop-stops')) + 1), function (int $timesMissing) use ($outputPrinter, &$forceShutdownReference) {
            if ($forceShutdownReference) {
                (new ConsoleServerMessage(
                    sprintf('Loop forced to stop.'), '~', false)
                )->print($outputPrinter);
            } else {
                (new ConsoleServerMessage(
                    sprintf('Rerunning EventLoop. %d retries missing', $timesMissing), '~', false)
                )->print($outputPrinter);
            }
        }, $forceShutdownReference);
        (new ConsoleServerMessage('EventLoop stopped.', '~', false))->print($outputPrinter);
        (new ConsoleServerMessage('Closing the server.', '~', false))->print($outputPrinter);
        (new ConsoleServerMessage('Bye bye!.', '~', false))->print($outputPrinter);

        return 0;
    }

    /**
     * Run server.
     *
     * @param LoopInterface $loop
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     * @param bool          $forceShutdownReference
     */
    abstract protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        bool &$forceShutdownReference
    );

    /**
     * Create OutputPrinter and add some custom OutputFormatterStyles to the
     * OutputInterface instance.
     *
     * @param OutputInterface $output
     * @param bool            $isQuiet
     *
     * @return OutputPrinter
     */
    protected function createOutputPrinter(
        OutputInterface $output,
        bool $isQuiet
    ): OutputPrinter {
        $outputFormatter = $output->getFormatter();
        $outputFormatter->setStyle('muted', new Muted());
        $outputFormatter->setStyle('purple', new Purple());

        return new OutputPrinter($output, $isQuiet);
    }

    /**
     * @param ServerContext $context
     */
    protected function configureServerContext(ServerContext $context)
    {
        // Do nothing
    }
}
