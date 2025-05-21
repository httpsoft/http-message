<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class Response implements ResponseInterface
{
    use ResponseTrait;

    /**
     * @param int $statusCode The HTTP status code.
     * @param array $headers The headers to send with the response.
     * @param StreamInterface|string|resource|null $body The body of the response. Must be one of the following:
     *  - an instance of `StreamInterface`;
     *  - a string stream identifier (e.g., 'php://temp') or a file path;
     *  - a valid stream resource;
     *  - `null`.
     * @param string $protocol The HTTP protocol version.
     * @param string $reasonPhrase The reason phrase associated with the status code.
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
