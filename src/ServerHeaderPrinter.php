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

namespace Drift\Server;

use Drift\Console\OutputPrinter;
use Drift\Server\Context\ServerContext;
use React\Filesystem\Filesystem;

/**
 * Class ServerHeaderPrinter.
 */
class ServerHeaderPrinter
{
    /**
     * Print header.
     *
     * @param ServerContext $serverContext
     * @param OutputPrinter $outputPrinter
     * @param string        $bootstrapPath
     */
    public static function print(
        ServerContext $serverContext,
        OutputPrinter $outputPrinter,
        string $bootstrapPath
    ) {
        $outputPrinter->printLine();
        $outputPrinter->printHeaderLine();
        $outputPrinter->printHeaderLine('ReactPHP HTTP Server for DriftPHP');
        $outputPrinter->printHeaderLine('  by Marc Morera (@mmoreram)');
        $outputPrinter->printHeaderLine();
        $outputPrinter->printHeaderLine("Host: {$serverContext->getHost()}");
        $outputPrinter->printHeaderLine("Port: {$serverContext->getPort()}");
        $outputPrinter->printHeaderLine("Environment: {$serverContext->getEnvironment()}");
        $outputPrinter->printHeaderLine('Debug: '.($serverContext->isDebug() ? 'enabled' : 'disabled'));
        $outputPrinter->printHeaderLine('Static folder: '.$serverContext->getStaticFolderAsString());
        if (!class_exists(Filesystem::class)) {
            $outputPrinter->printHeaderLine('<purple>Static folder: Attention! You should install the dependency `react/filesystem` to serve static content in a non-blocking way.</purple>');
            $outputPrinter->printHeaderLine('<purple>Static folder: Serving the content with blocking PHP functions.</purple>');
        }

        if (!is_null($serverContext->getCacheFilePath())) {
            if ($serverContext->cacheFilePathExists()) {
                $outputPrinter->printHeaderLine('Static cache file: '.realpath($serverContext->getCacheFilePath()));
            } else {
                $outputPrinter->printHeaderLine('<purple>Static cache file: Attention! File '.realpath($serverContext->getCacheFilePath()).' not found</purple>');
            }
        } else {
            $outputPrinter->printHeaderLine('Static cache file: disabled');
        }

        $outputPrinter->printHeaderLine("Adapter: {$serverContext->getAdapter()}");
        $outputPrinter->printHeaderLine("Workers: {$serverContext->getWorkers()}");
        $outputPrinter->printHeaderLine('Exchanges subscribed: '.($serverContext->hasExchanges()
            ? implode(', ', $serverContext->getPlainExchanges())
            : 'disabled'
        ));
        $outputPrinter->printHeaderLine('Loaded bootstrap file: '.realpath($bootstrapPath));
        $outputPrinter->printHeaderLine('Allowed number of loop stops: '.$serverContext->getAllowedLoopStops());

        $outputPrinter->printHeaderLine();
        $outputPrinter->printLine();
    }
}
