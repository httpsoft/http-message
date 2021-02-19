<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    use ResponseTrait;

    /**
     * @param int $statusCode
     * @param array $headers
     * @param StreamInterface|string|resource|null $body
     * @param string $protocol
     * @param string $reasonPhrase
     */
    public function __construct(
        int $statusCode = 200,
        array $headers = [],
        $body = null,
        string $protocol = '1.1',
        string $reasonPhrase = ''
    ) {
        $this->init($statusCode, $reasonPhrase, $headers, $body, $protocol);
    }
}
