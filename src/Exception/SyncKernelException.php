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

namespace Drift\Server\Exception;

use Drift\HttpKernel\AsyncKernel;
use Exception;

/**
 * Class SyncKernelException.
 */
final class SyncKernelException extends Exception
{
    /**
     * Build a new instance.
     */
    public static function build(): SyncKernelException
    {
        return new self(sprintf('Your kernel MUST implement %s', AsyncKernel::class));
    }
}
