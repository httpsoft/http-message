<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Factory;

use HttpSoft\Message\Factory\RequestFactory;
use HttpSoft\Message\Request;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

class RequestFactoryTest extends TestCase
{
    /**
     * @var RequestFactory
     */
    private RequestFactory $factory;

    public function setUp(): void
    {
        $this->factory = new RequestFactory();
    }

    public function testCreateRequest(): void
    {
        $request = $this->factory->createRequest($method = 'POST', $uri = 'http://example.com');
        $this->assertSame($method, $request->getMethod());
        $this->assertSame($uri, (string) $request->getUri());
        $this->assertSame(['Host' => ['example.com']], $request->getHeaders());
        $this->assertSame('1.1', $request->getProtocolVersion());
        $this->assertInstanceOf(StreamInterface::class, $request->getBody());
        $this->assertInstanceOf(RequestInterface::class, $request);
        $this->assertInstanceOf(Request::class, $request);
    }
}
