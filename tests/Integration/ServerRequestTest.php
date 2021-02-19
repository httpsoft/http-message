<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use HttpSoft\Message\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestTest extends ServerRequestIntegrationTest
{
    public function createSubject(): ServerRequestInterface
    {
        return new ServerRequest($_SERVER, [], [], [], null, 'GET', '/');
    }
}
