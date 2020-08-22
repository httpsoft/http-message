<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\UploadedFile;
use HttpSoft\Message\UploadedFileFactory;
use HttpSoft\Message\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

use function file_exists;
use function sys_get_temp_dir;
use function tempnam;

use const UPLOAD_ERR_OK;

class UploadedFileFactoryTest extends TestCase
{
    /**
     * @var UploadedFileFactory
     */
    private UploadedFileFactory $factory;

    /**
     * @var string
     */
    private string $tmpFile = '';

    public function setUp(): void
    {
        $this->factory = new UploadedFileFactory();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'httpsoft');
    }

    public function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testCreateUploadedFile(): void
    {
        $uploadedFile = $this->factory->createUploadedFile(
            $stream = new Stream($this->tmpFile),
            $size = 1024,
            UPLOAD_ERR_OK,
            $clientFilename = 'file.txt',
            $clientMediaType = 'text/plain'
        );
        $this->assertInstanceOf(UploadedFile::class, $uploadedFile);
        $this->assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
        $this->assertInstanceOf(StreamInterface::class, $uploadedFile->getStream());
        $this->assertInstanceOf(Stream::class, $uploadedFile->getStream());
        $this->assertSame($stream, $uploadedFile->getStream());
        $this->assertSame($size, $uploadedFile->getSize());
        $this->assertSame(UPLOAD_ERR_OK, $uploadedFile->getError());
        $this->assertSame($clientFilename, $uploadedFile->getClientFilename());
        $this->assertSame($clientMediaType, $uploadedFile->getClientMediaType());
    }
}
