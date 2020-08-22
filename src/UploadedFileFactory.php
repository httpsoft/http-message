<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;

use const UPLOAD_ERR_OK;

final class UploadedFileFactory implements UploadedFileFactoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        $size = (int) ($size === null ? $stream->getSize() : $size);
        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }
}
