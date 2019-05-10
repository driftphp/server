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

use Exception;

/**
 * Class Arguments.
 */
class Arguments
{
    /**
     * Make key value on arguments
     *
     * @param array $originalArguments
     *
     * @return array
     *
     * @throws Exception
     */
    public static function build(array $originalArguments)
    {
        $arguments = array_slice($originalArguments, 2);
        $newArguments = [];
        foreach ($arguments as $value) {
            $parts = explode('=', $value, 2);
            $key = $parts[0];
            $value = $parts[1] ?? true;
            $newArguments[$key] = $value;
        }

        $serverArgs = explode(':', $originalArguments[1], 2);
        if (count($serverArgs) !== 2) {
            throw new Exception('You should start the server defining a host and a port as a first argument: php/server 0.0.0.0:8000');
        }

        list($host, $port) = $serverArgs;
        $newArguments['host'] = $host;
        $newArguments['port'] = $port;

        return $newArguments;
    }
}