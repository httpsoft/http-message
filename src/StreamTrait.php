<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function get_resource_type;
use function is_resource;
use function is_string;
use function restore_error_handler;
use function set_error_handler;
use function stream_get_contents;
use function stream_get_meta_data;
use function strpos;

use const SEEK_SET;

/**
 * Trait implementing the methods defined in `Psr\Http\Message\StreamInterface`.
 *
 * @see https://github.com/php-fig/http-message/tree/master/src/StreamInterface.php
 */
trait StreamTrait
{
    /**
     * @var resource|null
     */
    private $resource;

    /**
     * @var int|null
     */
    private ?int $size = null;

    /**
     * @var bool|null
     */
    private ?bool $seekable = null;

    /**
     * @var bool|null
     */
    private ?bool $writable = null;

    /**
     * @var bool|null
     */
    private ?bool $readable = null;

    /**
     * Closes the stream and any underlying resources when the instance is destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.

     * @return string
     * @throws RuntimeException
     */
    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->rewind();
        }

        return $this->getContents();
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->resource) {
            $resource = $this->detach();

            if (is_resource($resource)) {
                fclose($resource);
            }
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = $this->size = null;
        $this->seekable = $this->writable = $this->readable = false;
        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     * @psalm-suppress RedundantCast
     */
    public function getSize(): ?int
    {
        if ($this->resource === null) {
            return null;
        }

        if ($this->size !== null) {
            return $this->size;
        }

        $stats = fstat($this->resource);
        return $this->size = isset($stats['size']) ? (int) $stats['size'] : null;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws RuntimeException on error.
     */
    public function tell(): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot tell position');
        }

        if (($result = ftell($this->resource)) === false) {
            throw new RuntimeException('Error occurred during tell operation');
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof(): bool
    {
        return (!$this->resource || feof($this->resource));
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }

        return $this->seekable = ($this->resource && $this->getMetadata('seekable'));
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot seek position.');
        }

        if (!$this->isSeekable()) {
            throw new RuntimeException('Stream is not seekable.');
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            throw new RuntimeException('Error seeking within stream.');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @throws RuntimeException on failure.
     * @link http://www.php.net/manual/en/function.fseek.php
     * @see seek()
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     * @psalm-suppress MixedAssignment
     */
    public function isWritable(): bool
    {
        if ($this->writable !== null) {
            return $this->writable;
        }

        if (!is_string($mode = $this->getMetadata('mode'))) {
            return $this->writable = false;
        }

        return $this->writable = (
            strpos($mode, 'w') !== false
            || strpos($mode, '+') !== false
            || strpos($mode, 'x') !== false
            || strpos($mode, 'c') !== false
            || strpos($mode, 'a') !== false
        );
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws RuntimeException on failure.
     */
    public function write($string): int
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot write.');
        }

        if (!$this->isWritable()) {
            throw new RuntimeException('Stream is not writable.');
        }

        $this->size = null;

        if (($result = fwrite($this->resource, $string)) === false) {
            throw new RuntimeException('Error writing to stream.');
        }

        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     * @psalm-suppress MixedAssignment
     */
    public function isReadable(): bool
    {
        if ($this->readable !== null) {
            return $this->readable;
        }

        if (!is_string($mode = $this->getMetadata('mode'))) {
            return $this->readable = false;
        }

        return $this->readable = (strpos($mode, 'r') !== false || strpos($mode, '+') !== false);
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws RuntimeException if an error occurs.
     */
    public function read($length): string
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot read.');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        if (($result = fread($this->resource, $length)) === false) {
            throw new RuntimeException('Error reading stream.');
        }

        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws RuntimeException if unable to read or an error occurs while reading.
     */
    public function getContents(): string
    {
        if (!$this->resource) {
            throw new RuntimeException('No resource available. Cannot read.');
        }

        if (!$this->isReadable()) {
            throw new RuntimeException('Stream is not readable.');
        }

        $exception = null;
        $message = 'Unable to read stream contents';

        set_error_handler(static function (int $errno, string $errstr) use (&$exception, $message) {
            throw $exception = new RuntimeException("$message: $errstr");
        });

        try {
            return stream_get_contents($this->resource);
        } catch (Throwable $e) {
            throw $e === $exception ? $e : new RuntimeException("$message: {$e->getMessage()}", 0, $e);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string|null $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->resource)) {
            return $key ? null : [];
        }

        $metadata = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }

    /**
     * Initialization the stream resource.
     *
     * Called when creating `Psr\Http\Message\StreamInterface` instance.
     *
     * @param mixed $stream String stream target or stream resource.
     * @param string $mode Resource mode for stream target.
     * @throws RuntimeException if the stream or file cannot be opened.
     * @throws InvalidArgumentException if the stream or resource is invalid.
     */
    private function init($stream, string $mode): void
    {
        if (is_string($stream)) {
            $stream = $stream === '' ? false : @fopen($stream, $mode);

            if ($stream === false) {
                throw new RuntimeException('The stream or file cannot be opened.');
            }
        }

        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException(
                'Invalid stream provided. It must be a string stream identifier or stream resource.',
            );
        }

        $this->resource = $stream;
    }
}
