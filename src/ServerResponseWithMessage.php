<?php

/*
 * This file is part of the React Symfony Server package.
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

use React\Http\Response;

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
     * @var Printable
     *
     * Message
     */
    private $message;

    /**
     * ServerResponseWithMessage constructor.
     *
     * @param Response  $serverResponse
     * @param Printable $message
     */
    public function __construct(
        Response $serverResponse,
        Printable $message
    ) {
        $this->serverResponse = $serverResponse;
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
            ->print();
    }
}
