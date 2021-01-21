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

namespace Drift\Server\Mime;

/**
 * Class MimeTypeChecker.
 */
class MimeTypeChecker
{
    /**
     * @var string[]
     */
    private array $types;

    /**
     * MimeTypeChecker constructor.
     */
    public function __construct()
    {
        $this->types = require __DIR__.'/types.php';
    }

    /**
     * Get extension.
     *
     * @param string $filename
     *
     * @return string
     */
    public function getExtension(string $filename): string
    {
        return strtolower(substr($filename, strrpos($filename, '.') + 1));
    }

    /**
     * Get mime type.
     *
     * @param string $fileName
     *
     * @return string
     */
    public function getMimeType(string $filename): string
    {
        $extension = $this->getExtension($filename);
        if (isset($this->types[$extension])) {
            return $this->types[$extension];
        }

        return 'application/octet-stream';
    }
}
