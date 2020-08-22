<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

final class UriFactory implements UriFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
