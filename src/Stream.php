<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\StreamInterface;

final class Stream implements StreamInterface
{
    use StreamTrait;

    /**
     * @param string|resource $stream The stream to use. Must be one of the following:
     *  - a string stream identifier (e.g., 'php://temp') or a file path;
     *  - a valid stream resource.
     * @param string $mode The mode in which to open the stream.
     */
    public function __construct($stream = 'php://temp', string $mode = 'wb+')
    {
        $this->init($stream, $mode);
    }
}
