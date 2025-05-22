<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

final class Request implements RequestInterface
{
    use RequestTrait;

    /**
     * @param string $method The HTTP method.
     * @param UriInterface|string $uri The URI to request.
     * @param array $headers The headers to send with the request.
     * @param StreamInterface|string|resource|null $body The body of the request. Must be one of the following:
     *  - an instance of `StreamInterface`;
     *  - a string stream identifier (e.g., 'php://temp') or a file path;
     *  - a valid stream resource;
     *  - `null`.
     * @param string $protocol The HTTP protocol version.
     */
    public function __construct(
        string $method = 'GET',
        $uri = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ) {
        $this->init($method, $uri, $headers, $body, $protocol);
    }
}
