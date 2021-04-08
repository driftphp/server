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
 * Class ConsoleRequestMessage.
 */
final class ConsoleRequestMessage implements Printable
{
    private string $url;
    private string $method;
    private int $code;
    private string $message;
    private string $elapsedTime;

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
        $hasError = \intval($this->code) >= 400;
        if (
            !$outputPrinter->shouldPrintRegularOutput() &&
            (!$outputPrinter->shouldPrintImportantOutput() || !$hasError)
        ) {
            return;
        }

        $method = str_pad($this->method, 6, ' ');
        $color = 'green';
        if ($this->code >= 300 && $this->code < 400) {
            $color = 'yellow';
        } elseif ($hasError) {
            $color = 'red';
        }

        $forkNumber = isset($GLOBALS['number_of_process'])
            ? "<fg=white>[{$GLOBALS['number_of_process']}] </>"
            : '';
        $outputPrinter->print("$forkNumber<fg=$color;options=bold>{$this->code}</>");
        $outputPrinter->print(" $method $this->url ");
        $outputPrinter->print('(<muted>'.$this->elapsedTime.' | '.((int) (memory_get_usage() / 1000000)).' MB</muted>)');
        if ($this->code >= 400) {
            $outputPrinter->print(' - <muted>'.$this->messageInMessage($this->message).'</muted>');
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
