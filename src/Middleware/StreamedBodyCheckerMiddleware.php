<?php


namespace Drift\Server\Middleware;


use Psr\Http\Message\ServerRequestInterface;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;

/**
 * Class StreamedBodyCheckerMiddleware
 */
class StreamedBodyCheckerMiddleware
{
    /**
     * @var int
     */
    private $maxBufferSize;

    /**
     * @param int $maxBufferSize
     */
    public function __construct(int $maxBufferSize)
    {
        $this->maxBufferSize = $maxBufferSize;
    }

    /**
     * @param ServerRequestInterface $request
     * @param Callable $next
     */
    public function __invoke(ServerRequestInterface $request, $next)
    {
        if ($request->hasHeader('Transfer-Encoding')) {
            return $next($request);
        }

        return (new RequestBodyBufferMiddleware($this->maxBufferSize))($request,
            function(ServerRequestInterface $request) use ($next) {
                return (new RequestBodyParserMiddleware())($request, $next);
            }
        );
    }
}