<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\ResponseIntegrationTest;
use HttpSoft\Message\Response;
use Psr\Http\Message\ResponseInterface;

final class ResponseTest extends ResponseIntegrationTest
{
    public function createSubject(): ResponseInterface
    {
        return new Response();
    }
}
