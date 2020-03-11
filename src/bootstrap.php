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

function requireIfExists(string &$bootstrapPath, string $file): bool
{
    if (file_exists($file)) {
        $bootstrapPath = $file;
        require $file;

        return true;
    }

    return false;
}

$bootstrapPath = '';
foreach ([
             'Drift/config/bootstrap.php',
             'config/bootstrap.php',
             'vendor/autoload.php',
         ] as $path) {
    requireIfExists($bootstrapPath, getcwd()."/$path") ||
    requireIfExists($bootstrapPath, __DIR__."/../$path") ||
    requireIfExists($bootstrapPath, __DIR__."/../../$path") ||
    requireIfExists($bootstrapPath, __DIR__."/../../../../$path");

    if ($bootstrapPath) {
        break;
    }
}

if (!$bootstrapPath) {
    die('You must define an existing kernel bootstrap file, or by an alias or my a file path'.PHP_EOL);
}

return $bootstrapPath;
