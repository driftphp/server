<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server;

use Drift\Console\Output\OutputPrinter;
use Drift\Server\Context\ServerContext;

/**
 * Class ServerPrinter.
 */
class ServerPrinter
{
    /**
     * Print header.
     *
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     * @param string        $bootstrapPath
     */
    public static function printServerHeader(
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        string $bootstrapPath
    ) {
        if ($serverContext->isSilent()) {
            return;
        }

        $outputPrinter->printLine();
        $outputPrinter->printHeaderLine();
        $outputPrinter->printHeaderLine('ReactPHP Client for DriftPHP');
        $outputPrinter->printHeaderLine('  by Marc Morera (@mmoreram)');
        $outputPrinter->printHeaderLine();
        $outputPrinter->printHeaderLine("Host: {$serverContext->getHost()}");
        $outputPrinter->printHeaderLine("Port: {$serverContext->getPort()}");
        $outputPrinter->printHeaderLine("Environment: {$serverContext->getEnvironment()}");
        $outputPrinter->printHeaderLine('Debug: '.($serverContext->isDebug() ? 'enabled' : 'disabled'));
        $outputPrinter->printHeaderLine('Static Folder: '.($serverContext->getStaticFolder() ?: 'disabled'));
        $outputPrinter->printHeaderLine("Adapter: {$serverContext->getAdapter()}");
        $outputPrinter->printHeaderLine('Loaded bootstrap file: '.realpath($bootstrapPath));
        $outputPrinter->printHeaderLine();
        $outputPrinter->printLine();
    }
}
