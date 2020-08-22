<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    use StreamTrait;

    /**
     * @param string|resource $stream
     * @param string $mode
     */
    public function __construct($stream = 'php://temp', string $mode = 'r')
    {
        $this->init($stream, $mode);
    }
}
