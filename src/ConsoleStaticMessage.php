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

/**
 * Class ConsoleStaticMessage.
 */
final class ConsoleStaticMessage extends ConsoleMessage
{
    private string $url;
    private string $elapsedTime;

    /**
     * ConsoleStaticMessage constructor.
     *
     * @param string $url
     * @param string $elapsedTime
     */
    public function __construct(
        string $url,
        string $elapsedTime
    ) {
        $this->url = $url;
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        if (!$outputPrinter->shouldPrintRegularOutput()) {
            return;
        }

        $method = str_pad('GET', 6, ' ');

        $performance = $this->styledPerformance($this->elapsedTime);
        $forkNumber = isset($GLOBALS['number_of_process'])
            ? "<fg=white>[{$GLOBALS['number_of_process']}] </>"
            : '';
        $outputPrinter->print("$forkNumber<purple>200</purple>");
        $outputPrinter->print(" $performance $method $this->url ");
        $outputPrinter->printLine();
    }
}
