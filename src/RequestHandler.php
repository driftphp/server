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

namespace Apisearch\ReactSymfonyServer;

/*
 * This file is part of the {Package name}.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

use Psr\Http\Message\ServerRequestInterface;
use React\Promise;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\AsyncKernel;

/**
 * Class RequestHandler
 */
class RequestHandler
{
    /**
     * @var AsyncKernel
     *
     * Kernel
     */
    private $kernel;
    /**
     * RequestHandler constructor.
     *
     * @param AsyncKernel $kernel
     */
    public function __construct(AsyncKernel $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Handle server request and return response.
     *
     * Return an array of an instance of ResponseInterface and an array of
     * Printable instances
     *
     * @param ServerRequestInterface $request
     *
     * @return PromiseInterface
     */
    public function handleAsyncServerRequest(ServerRequestInterface $request): PromiseInterface
    {
        $from = microtime(true);

        return (new FulfilledPromise($from))
            ->then(function() use ($request) {
                $body = $request->getBody()->getContents();
                $uriPath = $request->getUri()->getPath();
                $method = $request->getMethod();
                $headers = $request->getHeaders();

                $symfonyRequest = new Request(
                    $request->getQueryParams(),
                    $request->getParsedBody() ?? [],
                    $request->getAttributes(),
                    $request->getCookieParams(),
                    $request->getUploadedFiles(),
                    [], // Server is partially filled a few lines below
                    $body
                );

                $symfonyRequest->setMethod($method);
                $symfonyRequest->headers->replace($headers);
                $symfonyRequest->server->set('REQUEST_URI', $uriPath);

                if (isset($headers['Host'])) {
                    $symfonyRequest->server->set('SERVER_NAME', explode(':', $headers['Host'][0]));
                }

                return $symfonyRequest;
            })
            ->then(function(Request $symfonyRequest) {

                return Promise\all(
                    [
                        new FulfilledPromise($symfonyRequest),
                        $this
                            ->kernel
                            ->handleAsync($symfonyRequest)
                    ]
                );
            })
            ->then(function(array $parts) {

                list($symfonyRequest, $symfonyResponse) = $parts;
                $this
                    ->kernel
                    ->terminate($symfonyRequest, $symfonyResponse);

                return $parts;
            })
            ->then(function(array $parts) use ($request, $from) {

                list($symfonyRequest, $symfonyResponse) = $parts;
                $to = microtime(true);
                $messages[] = new ConsoleMessage(
                    $request->getUri()->getPath(),
                    $request->getMethod(),
                    $symfonyResponse->getStatusCode(),
                    $symfonyResponse->getContent(),
                    \intval(($to - $from) * 1000)
                );

                $this->applyResponseEncoding(
                    $symfonyRequest,
                    $symfonyResponse
                );

                $httpResponse = new \React\Http\Response(
                    $symfonyResponse->getStatusCode(),
                    $symfonyResponse->headers->all(),
                    $symfonyResponse->getContent()
                );

                $symfonyRequest = null;
                $symfonyResponse = null;

                return [$httpResponse, $messages];
            }, function(\Throwable $exception) {
                $messages[] = new ConsoleException($exception);
                $httpResponse = new \React\Http\Response(
                    400,
                    ['Content-Type' => 'text/plain'],
                    $exception->getMessage()
                );

                return [$httpResponse, $messages];
            });
    }


    /**
     * Apply response encoding
     *
     * @param Request $request
     * @param Response $response
     */
    private function applyResponseEncoding(
        Request $request,
        Response $response
    )
    {
        $allowedCompressionAsString = $request
            ->headers
            ->get('Accept-Encoding');
        if (!$allowedCompressionAsString) {
            return;
        }
        $allowedCompression = explode(',', $allowedCompressionAsString);
        $allowedCompression = array_map('trim', $allowedCompression);
        if (in_array('gzip', $allowedCompression)) {
            $response->setContent(gzencode($response->getContent()));
            $response
                ->headers
                ->set('Content-Encoding', 'gzip');
            return;
        }
        if (in_array('deflate', $allowedCompression)) {
            $response->setContent(gzdeflate($response->getContent()));
            $response
                ->headers
                ->set('Content-Encoding', 'deflate');
            return;
        }
    }
}