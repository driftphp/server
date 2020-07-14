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
            ->addOption('no-header', null, InputOption::VALUE_NONE, 'Disable the header')
            ->addOption('no-cookies', null, InputOption::VALUE_NONE, 'Disable cookies')
            ->addOption('no-file-uploads', null, InputOption::VALUE_NONE, 'Disable file uploads')
            ->addOption('concurrent-requests', null, InputOption::VALUE_OPTIONAL, 'Limit of concurrent requests', 100)
            ->addOption('request-body-buffer', null, InputOption::VALUE_OPTIONAL, 'Limit of the buffer used for the Request body. In KiB.', 1024)
            ->addOption('adapter', null, InputOption::VALUE_OPTIONAL, 'Server Adapter', 'drift');

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
        $outputPrinter = $this->createOutputPrinter($output);
        $loop = EventLoopFactory::create();
        if ($serverContext->printHeader()) {
            ServerHeaderPrinter::print(
                $serverContext,
                $outputPrinter,
                $this->bootstrapPath
            );
        }

        $this->executeServerCommand(
            $loop,
            $serverContext,
            $outputPrinter
        );

        (new ConsoleServerMessage('EventLoop is running.', '~', true))->print($outputPrinter);
        EventLoopUtils::runLoop($loop, 2, function (int $timesMissing) use ($outputPrinter) {
            (new ConsoleServerMessage(
                sprintf('Rerunning EventLoop. %d times missing', $timesMissing), '~', false)
            )->print($outputPrinter);
        });
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
     */
    abstract protected function executeServerCommand(
        LoopInterface $loop,
        ServerContext $serverContext,
        OutputPrinter $outputPrinter
    );

    /**
     * Create OutputPrinter and add some custom OutputFormatterStyles to the
     * OutputInterface instance.
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return \Drift\Console\OutputPrinter
     */
    protected function createOutputPrinter(OutputInterface $output): OutputPrinter
    {
        $outputFormatter = $output->getFormatter();
        $outputFormatter->setStyle('muted', new Muted());
        $outputFormatter->setStyle('purple', new Purple());

        return new OutputPrinter($output);
    }
}
