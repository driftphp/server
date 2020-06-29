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

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Class CustomStyle
 */
abstract class CustomStyle extends OutputFormatterStyle
{
    public function __construct()
    {
        // intentionally with no parameters!
        parent::__construct(null, null, []);
    }
}
