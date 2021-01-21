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

namespace Drift\Server\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;

/**
 * Class StreamedBodyCheckerMiddleware.
 */
class StreamedBodyCheckerMiddleware
{
    private int $maxBufferSize;

    /**
     * @param int $maxBufferSize
     */
    public function __construct(int $maxBufferSize)
    {
        $this->maxBufferSize = $maxBufferSize;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable               $next
     */
    public function __invoke(ServerRequestInterface $request, $next)
    {
        if ($request->hasHeader('Transfer-Encoding')) {
            return $next($request);
        }

        return (new RequestBodyBufferMiddleware($this->maxBufferSize))($request,
            function (ServerRequestInterface $request) use ($next) {
                return (new RequestBodyParserMiddleware())($request, $next);
            }
        );
    }
}
