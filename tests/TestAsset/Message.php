<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\TestAsset;

use HttpSoft\Message\MessageTrait;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class Message implements MessageInterface
{
    use MessageTrait;

    /**
     * @const string
     */
    public const DEFAULT_PROTOCOL_VERSION = '1.1';

    /**
     * @param StreamInterface|string|resource $body
     * @param array $headers
     * @param string $protocol
     */
    public function __construct($body = 'php://temp', array $headers = [], string $protocol = '')
    {
        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);
    }
}
