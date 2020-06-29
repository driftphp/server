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

namespace Drift\Server\Console\Style;

/**
 * Class Purple
 */
final class Purple extends CustomStyle
{
    public function apply(string $text)
    {
        return sprintf("\033[01;95m%s\e[0m", $text);
    }
}
