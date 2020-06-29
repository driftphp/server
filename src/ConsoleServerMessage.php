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
 * Class ConsoleServerMessage.
 */
final class ConsoleServerMessage implements Printable
{
    private $message;
    private $elapsedTime;
    private $ok;

    /**
     * Message constructor.
     *
     * @param string $message
     * @param string $elapsedTime
     * @param bool   $ok
     */
    public function __construct(
        string $message,
        string $elapsedTime,
        bool $ok
    ) {
        $this->message = $message;
        $this->elapsedTime = $elapsedTime;
        $this->ok = $ok;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $color = 'red';
        if ($this->ok) {
            $color = 'green';
        }

        $outputPrinter->print("<fg=$color;options=bold>SRV</>");
        $outputPrinter->print(' ');
        $outputPrinter->print("(<muted>".$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000))." MB</muted>)");
        $outputPrinter->print(" - <muted>".$this->message."</muted>");
        $outputPrinter->printLine();
    }
}
