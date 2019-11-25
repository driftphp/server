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

use Drift\Server\Output\OutputPrinter;
use Throwable;

/**
 * Class ConsoleException.
 */
final class ConsoleException implements Printable
{
    private $url;
    private $method;
    private $exception;
    private $elapsedTime;

    /**
     * ConsoleException constructor.
     *
     * @param Throwable $exception
     * @param string    $url
     * @param string    $method
     * @param string    $elapsedTime
     */
    public function __construct(
        Throwable $exception,
        string $url,
        string $method,
        string $elapsedTime
    ) {
        $this->exception = $exception;
        $this->url = $url;
        $this->method = $method;
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $exception = $this->exception;
        $color = '31';
        $method = str_pad($this->method, 6, ' ');
        echo "\033[01;{$color}m400\033[0m";
        echo " $method $this->url ";
        echo "(\e[00;37m".$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000))." MB\e[0m)";
        echo " - \e[00;37m".$exception->getMessage()."\e[0m";
        echo PHP_EOL;
    }
}
