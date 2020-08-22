<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

final class RequestFactory implements RequestFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
