<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Uri;
use HttpSoft\Message\UriFactory;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;

final class UriFactoryTest extends TestCase
{
    /**
     * @var UriFactory
     */
    private UriFactory $factory;

    public function setUp(): void
    {
        $this->factory = new UriFactory();
    }

    public function testCreateUri(): void
    {
        $uri = $this->factory->createUri($empty = '');
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame($empty, (string) $uri);

        $uri = $this->factory->createUri('http://example.com/path/to/tar<>get');
        $this->assertInstanceOf(Uri::class, $uri);
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertSame('http://example.com/path/to/tar%3C%3Eget', (string) $uri);
    }

    public function testCreateStreamFromFileThrowExceptionForInvalidUri(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createUri('https:///invalid-uri');
    }
}
