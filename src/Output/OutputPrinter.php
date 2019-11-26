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

namespace Drift\Server\Output;

use Drift\Server\Context\ServerContext;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ServerHeaderPrinter.
 */
class OutputPrinter
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * ServerHeaderPrinter constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Print header.
     *
     * @param ServerContext $serverContext
     * @param string        $bootstrapPath
     */
    public function printServerHeader(
        ServerContext $serverContext,
        string $bootstrapPath
    ) {
        if ($serverContext->isSilent()) {
            return;
        }

        $this->printLine();
        $this->printHeaderLine();
        $this->printHeaderLine('ReactPHP Client for DriftPHP');
        $this->printHeaderLine('  by Marc Morera (@mmoreram)');
        $this->printHeaderLine();
        $this->printHeaderLine("Host: {$serverContext->getHost()}");
        $this->printHeaderLine("Port: {$serverContext->getPort()}");
        $this->printHeaderLine("Environment: {$serverContext->getEnvironment()}");
        $this->printHeaderLine('Debug: '.($serverContext->isDebug() ? 'enabled' : 'disabled'));
        $this->printHeaderLine('Static Folder: '.($serverContext->getStaticFolder() ?: 'disabled'));
        $this->printHeaderLine("Adapter: {$serverContext->getAdapter()}");
        $this->printHeaderLine('Loaded bootstrap file: '.realpath($bootstrapPath));
        $this->printHeaderLine();
        $this->printLine();
    }

    /**
     * Print header line.
     *
     * @param string $line
     */
    private function printHeaderLine(string $line = '')
    {
        $this->printLine(">  $line");
    }

    /**
     * Print line.
     *
     * @param string $line
     */
    public function printLine(string $line = '')
    {
        $this
            ->output
            ->writeln($line);
    }

    /**
     * Print line.
     *
     * @param string $data
     */
    public function print(string $data)
    {
        $this
            ->output
            ->write($data);
    }
}
