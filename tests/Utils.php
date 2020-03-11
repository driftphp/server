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

/**
 * Class Utils.
 */
class Utils
{
    /**
     * Make curl.
     *
     * @param string $url
     * @param array  $headers
     *
     * @return [string, string]
     */
    public static function curl(
        string $url,
        array $headers = []
    ): array {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curlHandle, CURLOPT_USERAGENT, 'Your application name');
        curl_setopt($curlHandle, CURLOPT_HEADER, 1);
        $response = curl_exec($curlHandle);
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

        return [$body, $headersClean];
    }
}
