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
use RingCentral\Psr7\Response;

/**
 * Class ServerResponseWithMessage.
 */
class ServerResponseWithMessage
{
    /**
     * @var Response
     *
     * Server response
     */
    private $serverResponse;

    /**
     * @var OutputPrinter
     */
    private $outputPrinter;

    /**
     * @var Printable
     *
     * Message
     */
    private $message;

    /**
     * ServerResponseWithMessage constructor.
     *
     * @param Response      $serverResponse
     * @param OutputPrinter $outputPrinter
     * @param Printable     $message
     */
    public function __construct(
        Response $serverResponse,
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
     * @return Response
     */
    public function getServerResponse(): Response
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
