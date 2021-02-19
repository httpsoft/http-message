<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\RequestIntegrationTest;
use HttpSoft\Message\Request;
use Psr\Http\Message\RequestInterface;

final class RequestTest extends RequestIntegrationTest
{
    public function createSubject(): RequestInterface
    {
        return new Request('GET', '/');
    }
}
