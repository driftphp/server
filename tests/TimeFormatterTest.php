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

namespace Drift\Server\Tests;

use Drift\Server\TimeFormatter;
use PHPUnit\Framework\TestCase;

/**
 * Class TimeFormatterTest.
 */
class TimeFormatterTest extends TestCase
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
