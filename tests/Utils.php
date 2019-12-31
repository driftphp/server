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
     * @return string
     */
    public static function curl(
        string $url,
        array $headers = []
    ): string {
        $curl_handle = curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);

        return $result;
    }
}
