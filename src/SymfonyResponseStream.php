<?php

declare(strict_types=1);

namespace Drift\Server;

use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Filesystem\Stream\ReadableStreamTrait;
use React\Stream\ReadableStreamInterface;
use React\Stream\Util;
use React\Stream\WritableStreamInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SymfonyResponseStream extends EventEmitter implements ReadableStreamInterface
{
    /**
     * @var StreamedResponse
     */
    private $symfonyResponse;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var bool
     */
    private $closed = false;

    public function __construct(StreamedResponse $response, LoopInterface $loop)
    {
        $this->symfonyResponse = $response;
        $this->loop = $loop;
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function resume()
    {
        ob_start(function($data) {
            if (!$this->closed) {
                $this->emit('data', [
                    $data
                ]);
            }
        });
        $this->symfonyResponse->sendContent();
        if (!ob_end_clean()) {
            $this->emit('error', array(new \RuntimeException('Error closing stream')));
        }
        $this->emit('end');
        $this->closed = true;
        $this->emit('close');
    }

    public function pause()
    {
    }

    public function close()
    {
        $this->closed = true;
    }

    public function pipe(WritableStreamInterface $dest, array $options = array())
    {
        if ($this === $dest) {
            throw new \Exception('Can\'t pipe stream into itself!');
        }

        Util::pipe($this, $dest, $options);

        return $dest;
    }
}
