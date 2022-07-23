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
 * Class ConsoleMasterServerMessage.
 */
final class ConsoleMasterServerMessage implements Printable
{
    private string $message;
    private bool $ok;

    /**
     * @param string $message
     * @param bool   $ok
     */
    public function __construct(
        string $message,
        bool $ok
    ) {
        $this->message = $message;
        $this->ok = $ok;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        if (!$outputPrinter->shouldPrintImportantOutput()) {
            return;
        }

        $color = 'red';
        if ($this->ok) {
            $color = 'green';
        }

        $forkNumber = isset($GLOBALS['number_of_process'])
            ? "<fg=white>[{$GLOBALS['number_of_process']}] </>"
            : '';
        $outputPrinter->print("$forkNumber<fg=$color;options=bold>SRV</>");
        $outputPrinter->print(' ');
        $outputPrinter->print('<muted>'.$this->message.'</muted>');
        $outputPrinter->printLine();
    }
}
