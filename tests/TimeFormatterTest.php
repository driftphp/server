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

namespace Drift\Server\Tests;

use Drift\Console\TimeFormatter;

/**
 * Class TimeFormatterTest.
 */
class TimeFormatterTest extends BaseTest
{
    /**
     * Test format time.
     */
    public function testFormatTime()
    {
        $this->assertEquals('10 ms', TimeFormatter::formatTime(0.010));
        $this->assertEquals('300 μs', TimeFormatter::formatTime(0.000300));
    }
}
