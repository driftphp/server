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

namespace Drift\Server;

/**
 * Class TimeFormatter.
 */
class TimeFormatter
{
    /**
     * Format time in milliseconds.
     *
     * @param float $timeInSeconds
     *
     * @return string
     */
    public static function formatTime(float $timeInSeconds): string
    {
        $timeInMicroseconds = \intval($timeInSeconds * 1000000);
        
        if ($timeInMicroseconds >= 1000) {
            return \intval($timeInMicroseconds / 1000) . " ms";
        }

        return "$timeInMicroseconds μs";
    }
}
