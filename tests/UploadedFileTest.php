<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Stream;
use HttpSoft\Message\UploadedFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use StdClass;

use function file_get_contents;
use function file_exists;
use function sys_get_temp_dir;
use function tempnam;

use const UPLOAD_ERR_CANT_WRITE;
use const UPLOAD_ERR_EXTENSION;
use const UPLOAD_ERR_FORM_SIZE;
use const UPLOAD_ERR_INI_SIZE;
use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_NO_TMP_DIR;
use const UPLOAD_ERR_OK;
use const UPLOAD_ERR_PARTIAL;

final class UploadedFileTest extends TestCase
{
    /**
     * @var string
     */
    private string $tmpFile;

    /**
     * @var Stream
     */
    private Stream $stream;

    public function setUp(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'httpsoft');
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
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $this->assertSame($this->stream, $uploadedFile->getStream());
        $this->assertSame($size, $uploadedFile->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $uploadedFile->getError());
        $this->assertNull($uploadedFile->getClientFilename());
        $this->assertNull($uploadedFile->getClientMediaType());
    }

    public function testGetStream()
    {
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $this->assertSame($this->stream, $uploadedFile->getStream());
        $this->stream->write($content = 'Content');
        $this->assertSame($content, (string) $uploadedFile->getStream());
        $this->assertSame((string) $this->stream, (string) $uploadedFile->getStream());

        $uploadedFile = new UploadedFile($this->tmpFile, $size = 1024, UPLOAD_ERR_OK);
        $this->assertInstanceOf(StreamInterface::class, $uploadedFile->getStream());
        $this->assertInstanceOf(Stream::class, $uploadedFile->getStream());
        $stream = $uploadedFile->getStream();
        $stream->write($content = 'Content');
        $this->assertSame($content, (string) $uploadedFile->getStream());
    }

    public function testMoveTo()
    {
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $this->stream->write($content = 'Content');
        $uploadedFile->moveTo($this->tmpFile);
        $this->assertTrue(file_exists($this->tmpFile));
        $this->assertSame($content, file_get_contents($this->tmpFile));
        $this->assertSame((string) $this->stream, file_get_contents($this->tmpFile));
    }

    public function testGetSize(): void
    {
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $this->assertSame($size, $uploadedFile->getSize());
    }

    public function testGetClientFilenameIfPassed(): void
    {
        $uploadedFile = new UploadedFile($this->stream, 1024, UPLOAD_ERR_OK, $clientFilename = 'file.txt');
        $this->assertSame($clientFilename, $uploadedFile->getClientFilename());
    }

    public function testGetClientMediaTypeIfPassed(): void
    {
        $uploadedFile = new UploadedFile(
            $this->stream,
            1024,
            UPLOAD_ERR_OK,
            'file.txt',
            $clientMediaType = 'text/plain'
        );
        $this->assertSame($clientMediaType, $uploadedFile->getClientMediaType());
    }

    public function testGetStreamThrowExceptionForHasBeenMoved(): void
    {
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $uploadedFile->moveTo($this->tmpFile);
        $this->expectException(RuntimeException::class);
        $uploadedFile->getStream();
    }

    public function testMoveToThrowExceptionForHasBeenMoved(): void
    {
        $uploadedFile = new UploadedFile($this->stream, $size = 1024, UPLOAD_ERR_OK);
        $uploadedFile->moveTo($this->tmpFile);
        $this->expectException(RuntimeException::class);
        $uploadedFile->moveTo($this->tmpFile);
    }

    /**
     * @return array
     */
    public function invalidErrorStatusProvider(): array
    {
        return [[-111], [-1], [9], [999]];
    }

    /**
     * @dataProvider invalidErrorStatusProvider
     * @param mixed $errorStatus
     */
    public function testConstructorThrowExceptionForInvalidErrorStatus($errorStatus): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UploadedFile($this->stream, 1024, $errorStatus);
    }

    public function notOkErrorStatusProvider()
    {
        return [
            'UPLOAD_ERR_INI_SIZE'   => [UPLOAD_ERR_INI_SIZE],
            'UPLOAD_ERR_FORM_SIZE'  => [UPLOAD_ERR_FORM_SIZE],
            'UPLOAD_ERR_PARTIAL'    => [UPLOAD_ERR_PARTIAL],
            'UPLOAD_ERR_NO_FILE'    => [UPLOAD_ERR_NO_FILE],
            'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
            'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
            'UPLOAD_ERR_EXTENSION'  => [UPLOAD_ERR_EXTENSION],
        ];
    }

    /**
     * @dataProvider notOkErrorStatusProvider
     * @param mixed $errorStatus
     */
    public function testGetStreamThrowExceptionForErrorStatusNotOk($errorStatus): void
    {
        $uploadedFile = new UploadedFile($this->stream, 1024, $errorStatus);
        $this->expectException(RuntimeException::class);
        $uploadedFile->getStream();
    }

    /**
     * @dataProvider notOkErrorStatusProvider
     * @param mixed $errorStatus
     */
    public function testMoveToThrowExceptionForErrorStatusNotOk($errorStatus): void
    {
        $uploadedFile = new UploadedFile($this->stream, 1024, $errorStatus);
        $this->expectException(RuntimeException::class);
        $uploadedFile->moveTo($this->tmpFile);
    }

    /**
     * @return array
     */
    public function invalidTargetPathProvider(): array
    {
        return [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'array' => [['']],
            'empty-array' => [[]],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
        ];
    }

    /**
     * @dataProvider invalidTargetPathProvider
     * @param mixed $targetPath
     */
    public function testMoveToThrowExceptionForInvalidTargetPath($targetPath): void
    {
        $uploadedFile = new UploadedFile($this->stream, 1024, UPLOAD_ERR_OK);
        $this->expectException(InvalidArgumentException::class);
        $uploadedFile->moveTo($targetPath);
    }
}
