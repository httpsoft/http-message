<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Factory;

use HttpSoft\Message\Factory\StreamFactory;
use HttpSoft\Message\Stream;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

use function file_exists;
use function fopen;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class StreamFactoryTest extends TestCase
{
    /**
     * @var StreamFactory
     */
    private StreamFactory $factory;

    /**
     * @var string
     */
    private $tmpFile;

    /**
     * @var resource
     */
    private $resource;

    public function setUp(): void
    {
        $this->factory = new StreamFactory();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'httpsoft');
        $this->resource = fopen('php://temp', 'r');
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testCreateStream(): void
    {
        $stream = $this->factory->createStream($content = 'content');
        $this->assertSame($content, $stream->getContents());
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testCreateStreamFromFile(): void
    {
        $stream = $this->factory->createStreamFromFile($this->tmpFile);
        $this->assertSame($this->tmpFile, $stream->getMetadata('uri'));
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testCreateStreamFromFileThrowExceptionForInvalidMode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createStreamFromFile($this->tmpFile, 'invalid-mode');
    }

    public function testCreateStreamFromFileThrowExceptionForFileNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createStreamFromFile('/file/not/exist');
    }

    public function testCreateStreamFromResource(): void
    {
        $stream = $this->factory->createStreamFromResource($this->resource);
        $this->assertSame('php://temp', $stream->getMetadata('uri'));
        $this->assertInstanceOf(Stream::class, $stream);
        $this->assertInstanceOf(StreamInterface::class, $stream);
    }

    public function testCreateStreamFromResourceThrowExceptionForNotResourcePassed(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->factory->createStreamFromResource($this->tmpFile);
    }
}
