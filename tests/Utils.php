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

namespace Drift\Server\Tests;

use CURLFile;
use Psr\Http\Message\ResponseInterface;
use React\EventLoop\LoopInterface;
use React\Http\Browser;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Stream\ReadableStreamInterface;

/**
 * Class Utils.
 */
class Utils
{
    /**
     * Make curl.
     *
     * @param string   $url
     * @param string[] $headers
     * @param string[] $files
     * @param string   $cookie
     * @param string   $json
     *
     * @return [string, string]
     */
    public static function curl(
        string $url,
        array $headers = [],
        array $files = [],
        string $cookie = '',
        string $json = null
    ): array {
        $curlHandle = curl_init();

        if (!empty($json)) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Content-Length: '.strlen($json);
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $json);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'POST');
        }

        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Your application name');
        curl_setopt($curlHandle, CURLOPT_HEADER, 1);
        curl_setopt($curlHandle, CURLOPT_COOKIE, $cookie);
        curl_setopt($curlHandle, CURLOPT_TIMEOUT_MS, 500);

        if (!empty($files)) {
            $postData = [];
            foreach ($files as $file) {
                $fileParts = explode('.', $file, 2);
                $postData[$fileParts[0]] = new CURLFile(realpath(__DIR__.'/'.$file));
            }

            curl_setopt($curlHandle, CURLOPT_POST, 1);
            curl_setopt(
                $curlHandle,
                CURLOPT_POSTFIELDS,
                $postData
            );
        }

        $response = curl_exec($curlHandle);
        if (false === $response) {
            return [false, [], 500];
        }

        $statusCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($curlHandle, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);
        $headersArray = explode("\r\n", $headers);
        $headersClean = [];
        array_shift($headersArray);
        foreach ($headersArray as $headerElement) {
            $parts = explode(':', $headerElement);
            if (2 === count($parts)) {
                $headersClean[trim($parts[0])] = trim($parts[1]);
            }
        }

        $body = substr($response, $headerSize);
        curl_close($curlHandle);

        return [$body, $headersClean, $statusCode];
    }

    /**
     * Make stream call.
     *
     * @param LoopInterface           $loop
     * @param string                  $url
     * @param ReadableStreamInterface $stream
     * @param string[]                $headers
     *
     * @return PromiseInterface<ResponseInterface>
     */
    public static function callWithStreamedBody(
        LoopInterface $loop,
        string $url,
        ReadableStreamInterface $stream,
        array $headers = []
    ): PromiseInterface {
        $deferred = new Deferred();

        $loop
            ->futureTick(function () use ($loop, $url, $stream, $headers, $deferred) {
                $browser = new Browser($loop);
                $browser->put($url, $headers, $stream)
                    ->then(function (ResponseInterface $response) use ($deferred) {
                        $deferred->resolve($response->getBody()->getContents());
                    });
            });

        return $deferred->promise();
    }
}
