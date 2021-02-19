<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\UploadedFileIntegrationTest;
use HttpSoft\Message\Stream;
use HttpSoft\Message\UploadedFile;
use Psr\Http\Message\UploadedFileInterface;

use const UPLOAD_ERR_OK;

final class UploadedFileTest extends UploadedFileIntegrationTest
{
    public function createSubject(): UploadedFileInterface
    {
        $stream = new Stream('php://memory', 'rw');
        $stream->write('foobar');

        return new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK);
    }
}
