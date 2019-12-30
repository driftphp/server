<?php

/*
 * This file is part of the DriftPHP Project
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Drift\Server;

use Exception;

/**
 * Class ErrorHandler.
 */
class ErrorHandler
{
    /**
     * Handle error to exception.
     */
    public static function handle()
    {
        set_error_handler([ErrorHandler::class, 'errorToException'], E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED);
    }

    /**
     * Errors to Exceptions.
     *
     * @throws Exception
     */
    public static function errorToException($code, $message, $file, $line, $context)
    {
        throw new Exception($message, $code);
    }
}
