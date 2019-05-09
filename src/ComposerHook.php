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

/**
 * Class ComposerHook.
 */
class ComposerHook
{
    /**
     * Install react server.
     */
    public static function installReactServer()
    {
        $appPath = __DIR__.'/../../../..';
        self::createFolderIfNotExists("$appPath/bin");
        self::createCopy(
            __DIR__,
            'server.php',
            "$appPath/bin",
            'server'
        );
        chmod("$appPath/bin/server", 0755);
    }

    /**
     * Create folder if not exists.
     *
     * @param string $path
     */
    private static function createFolderIfNotExists(string $path)
    {
        if (false === @mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf("Unable to create the %s directory\n", $path));
        }
    }

    /**
     * Make a copy of a file, from a folder, into a folder.
     *
     * @param string $from
     * @param string $fromFilename
     * @param string $to
     * @param string $toFilename
     */
    private static function createCopy(
        string $from,
        string $fromFilename,
        string $to,
        string $toFilename
    ) {
        if (file_exists("$to/$toFilename")) {
            unlink("$to/$toFilename");
        }

        copy(
            realpath($from)."/$fromFilename",
            realpath($to)."/$toFilename"
        );

        echo '> * Copy origin - '.realpath($from)."/$toFilename".PHP_EOL;
        echo '> * Copy destination - '.realpath($to)."/$toFilename".PHP_EOL;
    }
}