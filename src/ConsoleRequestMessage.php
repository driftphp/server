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

use Drift\Server\Output\OutputPrinter;

/**
 * Class ConsoleRequestMessage.
 */
final class ConsoleRequestMessage implements Printable
{
    private $url;
    private $method;
    private $code;
    private $message;
    private $elapsedTime;

    /**
     * Message constructor.
     *
     * @param string $url
     * @param string $method
     * @param int    $code
     * @param string $message
     * @param string $elapsedTime
     */
    public function __construct(
        string $url,
        string $method,
        int $code,
        string $message,
        string $elapsedTime
    ) {
        $this->url = $url;
        $this->method = $method;
        $this->code = $code;
        $this->message = $message;
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * Print.
     *
     * @param OutputPrinter $outputPrinter
     */
    public function print(OutputPrinter $outputPrinter)
    {
        $method = str_pad($this->method, 6, ' ');
        $color = '32';
        if ($this->code >= 300 && $this->code < 400) {
            $color = '33';
        } elseif ($this->code >= 400) {
            $color = '31';
        }

        $outputPrinter->print("\033[01;{$color}m".$this->code."\033[0m");
        $outputPrinter->print(" $method $this->url ");
        $outputPrinter->print("(\e[00;37m".$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000))." MB\e[0m)");
        if ($this->code >= 300) {
            $outputPrinter->print(" - \e[00;37m".$this->messageInMessage($this->message)."\e[0m");
        }
        $outputPrinter->printLine();
    }

    /**
     * Find message.
     *
     * @param string $message
     *
     * @return string
     */
    private function messageInMessage(string $message): string
    {
        $decodedMessage = json_decode($message, true);
        if (
            is_array($decodedMessage) &&
            isset($decodedMessage['message']) &&
            is_string($decodedMessage['message'])
        ) {
            return $decodedMessage['message'];
        }

        return $message;
    }
}
