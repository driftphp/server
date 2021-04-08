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
use Psr\Http\Message\ResponseInterface;

/**
 * Class ServerResponseWithMessage.
 */
class ServerResponseWithMessage
{
    private ResponseInterface $serverResponse;
    private OutputPrinter $outputPrinter;
    private Printable $message;

    /**
     * ServerResponseWithMessage constructor.
     *
     * @param ResponseInterface $serverResponse
     * @param OutputPrinter     $outputPrinter
     * @param Printable         $message
     */
    public function __construct(
        ResponseInterface $serverResponse,
        OutputPrinter $outputPrinter,
        Printable $message
    ) {
        $this->serverResponse = $serverResponse;
        $this->outputPrinter = $outputPrinter;
        $this->message = $message;
    }

    /**
     * Get ServerResponse.
     *
     * @return ResponseInterface
     */
    public function getServerResponse(): ResponseInterface
    {
        return $this->serverResponse;
    }

    /**
     * Print message.
     */
    public function printMessage()
    {
        $this
            ->message
            ->print($this->outputPrinter);
    }
}
