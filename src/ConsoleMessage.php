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

namespace Drift\Server;

abstract class ConsoleMessage implements Printable
{
    /**
     * @param string $elapsedType
     *
     * @return string
     */
    protected function styledPerformance(string $elapsedType): string
    {
        $info = [
            $this->toLength($elapsedType, str_contains($elapsedType, 'μ') ? 8 : 7),
            $this->toLength((string) (int) (memory_get_usage() / 1048576), 4),
            $this->toLength((string) (int) (memory_get_usage(true) / 1048576), 4),
        ];

        return '<performance>['.implode('|', $info).']</performance>';
    }

    /**
     * @param string $string
     * @param int    $length
     *
     * @return string
     */
    protected function toLength(string $string, int $length): string
    {
        return str_pad($string, $length, ' ', STR_PAD_LEFT);
    }
}
