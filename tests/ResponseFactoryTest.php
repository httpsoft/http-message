<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Response;
use HttpSoft\Message\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class ResponseFactoryTest extends TestCase
{
    /**
     * @var ResponseFactory
     */
    private ResponseFactory $factory;

    public function setUp(): void
    {
        $this->factory = new ResponseFactory();
    }

    public function testCreateResponse(): void
    {
        $response = $this->factory->createResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame('php://temp', $response->getBody()->getMetadata('uri'));
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('1.1', $response->getProtocolVersion());

        $response = $this->factory->createResponse($code = 404);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame('php://temp', $response->getBody()->getMetadata('uri'));
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('1.1', $response->getProtocolVersion());

        $response = $this->factory->createResponse($code = 404, $customPhrase = 'Custom Phrase');
        $this->assertInstanceOf(Response::class, $response);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame($customPhrase, $response->getReasonPhrase());
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame('php://temp', $response->getBody()->getMetadata('uri'));
        $this->assertSame([], $response->getHeaders());
        $this->assertSame('1.1', $response->getProtocolVersion());
    }
}
