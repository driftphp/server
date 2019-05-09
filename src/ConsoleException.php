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

use Throwable;

/**
 * Class ConsoleException.
 */
class ConsoleException implements Printable
{
    /**
     * @var Throwable
     *
     * Exception
     */
    private $exception;

    /**
     * ConsoleException constructor.
     *
     * @param Throwable $exception
     */
    public function __construct(Throwable $exception)
    {
        $this->exception = $exception;
    }

    /**
     * Print.
     */
    public function print()
    {
        $e = $this->exception;

        echo "[{$e->getFile()}] [{$e->getCode()}] ::: [{$e->getMessage()}]".PHP_EOL;
    }
}