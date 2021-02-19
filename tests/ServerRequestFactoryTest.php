<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestFactoryTest extends TestCase
{
    /**
     * @var ServerRequestFactory
     */
    private ServerRequestFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ServerRequestFactory();
    }

    public function testCreateServerRequest(): void
    {
        $serverRequest = $this->factory->createServerRequest('GET', 'https://example.com');
        $this->assertInstanceOf(ServerRequest::class, $serverRequest);
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertSame([], $serverRequest->getServerParams());
        $this->assertSame([], $serverRequest->getUploadedFiles());
        $this->assertSame([], $serverRequest->getCookieParams());
        $this->assertSame([], $serverRequest->getQueryParams());
        $this->assertNull($serverRequest->getParsedBody());
        $this->assertSame([], $serverRequest->getAttributes());
        $this->assertSame('php://temp', $serverRequest->getBody()->getMetadata('uri'));

        $serverRequest = $this->factory->createServerRequest('GET', 'https://example.com', $server = [
            'HTTP_HOST' => 'example.com',
            'CONTENT_TYPE' => 'text/html; charset=UTF-8',
        ]);
        $this->assertInstanceOf(ServerRequest::class, $serverRequest);
        $this->assertInstanceOf(ServerRequestInterface::class, $serverRequest);
        $this->assertSame($server, $serverRequest->getServerParams());
        $this->assertSame([], $serverRequest->getUploadedFiles());
        $this->assertSame([], $serverRequest->getCookieParams());
        $this->assertSame([], $serverRequest->getQueryParams());
        $this->assertNull($serverRequest->getParsedBody());
        $this->assertSame([], $serverRequest->getAttributes());
        $this->assertSame('php://temp', $serverRequest->getBody()->getMetadata('uri'));
    }
}
