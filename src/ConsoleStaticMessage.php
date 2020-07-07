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
final class ConsoleStaticMessage implements Printable
{
    private $url;
    private $elapsedTime;

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
        $method = str_pad('GET', 6, ' ');

        $outputPrinter->print('<purple>200</purple>');
        $outputPrinter->print(" $method $this->url ");
        $outputPrinter->print('(<muted>'.$this->elapsedTime.' |  '.((int) (memory_get_usage() / 1000000)).' MB</muted>');
        $outputPrinter->printLine();
    }
}
