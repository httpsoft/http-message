<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Stream;
use RuntimeException;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function fopen;
use function stream_get_meta_data;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

class StreamTest extends TestCase
{
    /**
     * @var string
     */
    private string $tmpFile;

    /**
     * @var resource
     */
    private $defaultResource;

    /**
     * @var Stream
     */
    private Stream $stream;

    public function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'httpsoft');
        $this->defaultResource = fopen('php://temp', 'r');
        $this->stream = new Stream('php://temp', 'wb+');
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testGetDefault(): void
    {
        $stream = new Stream();
        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->eof());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isWritable());
        $this->assertSame(0, $stream->getSize());
        $this->assertSame('', $stream->getContents());
        $this->assertSame($this->stream->getMetadata(), $stream->getMetadata());
    }

    public function testGetMetadata()
    {
        $this->assertSame('PHP', $this->stream->getMetadata('wrapper_type'));
        $this->assertSame('TEMP', $this->stream->getMetadata('stream_type'));
        $this->assertSame('w+b', $this->stream->getMetadata('mode'));
        $this->assertSame(0, $this->stream->getMetadata('unread_bytes'));
        $this->assertSame(true, $this->stream->getMetadata('seekable'));
        $this->assertSame('php://temp', $this->stream->getMetadata('uri'));
    }

    public function testIsWritableAndWriteAndToString(): void
    {
        $this->assertTrue($this->stream->isWritable());
        $this->stream->write($content = 'content');
        $this->assertSame($content, (string) $this->stream);
    }

    public function testCreateResourceThrowExceptionForStreamCannotBeOpened(): void
    {
        $this->expectException(RuntimeException::class);
        new Stream('php://fail');
    }

    public function testToStringThrowExceptionForIsNotReadable(): void
    {
        $stream = new Stream('php://output', 'w');
        $this->expectException(RuntimeException::class);
        $stream->__toString();
    }

    public function testCloseAndGetSizeIfUnknown(): void
    {
        $this->stream->close();
        $this->assertNull($this->stream->getSize());
    }

    public function testDetach(): void
    {
        $stream = new Stream($this->defaultResource);
        $this->assertSame(stream_get_meta_data($this->defaultResource), stream_get_meta_data($stream->detach()));
        $this->assertNull($stream->getSize());
    }

    public function testIsReadableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertTrue($stream->isReadable());
    }

    public function testIsReadableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $this->assertFalse($stream->isReadable());
    }

    public function testIsWritableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $this->assertTrue($stream->isWritable());
    }

    public function testIsWritableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertFalse($stream->isWritable());
    }

    public function testIsSeekableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $stream->close();
        $this->assertFalse($stream->isSeekable());
    }

    public function testTellThrowExceptionForInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->stream->close();
        $this->stream->tell();
    }

    public function testSeekThrowExceptionForInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->stream->close();
        $this->stream->seek(1);
    }

    public function testWriteThrowExceptionForInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->stream->close();
        $this->stream->write('content');
    }

    public function testReadThrowExceptionForInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->stream->close();
        $this->stream->read(1);
    }

    public function testGetContentThrowExceptionForInvalidResource(): void
    {
        $this->expectException(RuntimeException::class);
        $this->stream->close();
        $this->stream->getContents();
    }
}
