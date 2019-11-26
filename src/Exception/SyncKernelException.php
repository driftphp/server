<?php


namespace Drift\Server\Exception;

use Drift\HttpKernel\AsyncKernel;
use Exception;

/**
 * Class SyncKernelException
 */
final class SyncKernelException extends Exception
{
    /**
     * Build a new instance
     */
    public static function build() : SyncKernelException
    {
        return new self(sprintf('Your kernel MUST implement %s', AsyncKernel::class));
    }
}