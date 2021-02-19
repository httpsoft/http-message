<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message\Integration;

use Http\Psr7Test\StreamIntegrationTest;
use HttpSoft\Message\Stream;
use Psr\Http\Message\StreamInterface;

final class StreamTest extends StreamIntegrationTest
{
    public function createStream($data): StreamInterface
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }

        return new Stream($data);
    }
}
