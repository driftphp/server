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

namespace Apisearch\SymfonyReactServer;

/**
 * Class ConsoleStaticMessage.
 */
class ConsoleStaticMessage implements Printable
{
    protected $url;
    protected $elapsedTime;

    /**
     * Message constructor.
     *
     * @param string $url
     * @param int    $elapsedTime
     */
    public function __construct(
        string $url,
        int $elapsedTime
    ) {
        $this->url = $url;
        $this->elapsedTime = $elapsedTime;
    }

    /**
     * Print.
     */
    public function print()
    {
        $method = str_pad('GET', 6, ' ');
        $color = '95';

        echo "\033[01;{$color}m200\033[0m";
        echo " $method $this->url ";
        echo "(\e[00;37m".$this->elapsedTime.' ms | '.((int) (memory_get_usage() / 1000000))." MB\e[0m)";
        echo PHP_EOL;
    }
}
