<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\UriIntegrationTest;
use HttpSoft\Message\Uri;
use Psr\Http\Message\UriInterface;

final class UriTest extends UriIntegrationTest
{
    public function createUri($uri): UriInterface
    {
        return new Uri($uri);
    }
}
