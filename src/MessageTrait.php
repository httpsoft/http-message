<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function array_merge;
use function gettype;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function is_resource;
use function is_string;
use function preg_match;
use function sprintf;
use function strtolower;

/**
 * Trait implementing the methods defined in `Psr\Http\Message\MessageInterface`.
 *
 * @see https://github.com/php-fig/http-message/tree/master/src/MessageInterface.php
 */
trait MessageTrait
{
    /**
     * Map of all registered original headers, as `original header name` => `array of values`.
     *
     * @var string[][]
     */
    private array $headers = [];

    /**
     * Map of all header names, as `normalized header name` => `original header name` at registration.
     *
     * @var string[]
     */
    private array $headerNames = [];

    /**
     * @var string
     */
    private string $protocol = '1.1';

    /**
     * @var StreamInterface
     */
    private StreamInterface $stream;

    /**
     * Retrieves the HTTP protocol version as a string.
     *
     * The string MUST contain only the HTTP version number (e.g., "1.1", "1.0").
     *
     * @return string HTTP protocol version.
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     *
     * The version string MUST contain only the HTTP version number (e.g.,
     * "1.1", "1.0").
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new protocol version.
     *
     * @param string $version HTTP protocol version
     * @return static
     * @throws InvalidArgumentException for invalid HTTP protocol version.
     */
    public function withProtocolVersion($version): MessageInterface
    {
        if ($version === $this->protocol) {
            return $this;
        }

        $this->validateProtocolVersion($version);
        $new = clone $this;
        $new->protocol = $version;
        return $new;
    }

    /**
     * Retrieves all message header values.
     *
     * The keys represent the header name as it will be sent over the wire, and
     * each value is an array of strings associated with the header.
     *
     *     // Represent the headers as a string
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ": " . implode(", ", $values);
     *     }
     *
     *     // Emit headers iteratively:
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * While header names are not case-sensitive, getHeaders() will preserve the
     * exact case in which headers were originally specified.
     *
     * @return string[][] Returns an associative array of the message's headers. Each
     *     key MUST be a header name, and each value MUST be an array of strings
     *     for that header.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     *
     * @param string $name Case-insensitive header field name.
     * @return bool Returns true if any header names match the given header
     *     name using a case-insensitive string comparison. Returns false if
     *     no matching header name is found in the message.
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public function hasHeader($name): bool
    {
        return (is_string($name) && isset($this->headerNames[$this->normalizeHeaderName($name)]));
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     *
     * This method returns an array of all the header values of the given
     * case-insensitive header name.
     *
     * If the header does not appear in the message, this method MUST return an
     * empty array.
     *
     * @param string $name Case-insensitive header field name.
     * @return string[] An array of string values as provided for the given
     *    header. If the header does not appear in the message, this method MUST
     *    return an empty array.
     */
    public function getHeader($name): array
    {
        if (!$this->hasHeader($name)) {
            return [];
        }

        return $this->headers[$this->headerNames[$this->normalizeHeaderName($name)]];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     *
     * This method returns all of the header values of the given
     * case-insensitive header name as a string concatenated together using
     * a comma.
     *
     * NOTE: Not all header values may be appropriately represented using
     * comma concatenation. For such headers, use getHeader() instead
     * and supply your own delimiter when concatenating.
     *
     * If the header does not appear in the message, this method MUST return
     * an empty string.
     *
     * @param string $name Case-insensitive header field name.
     * @return string A string of values as provided for the given header
     *    concatenated together using a comma. If the header does not appear in
     *    the message, this method MUST return an empty string.
     */
    public function getHeaderLine($name): string
    {
        if (!$value = $this->getHeader($name)) {
            return '';
        }

        return implode(',', $value);
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     *
     * While header names are case-insensitive, the casing of the header will
     * be preserved by this function, and returned from getHeaders().
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new and/or updated header and value.
     *
     * @param string $name Case-insensitive header field name.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function withHeader($name, $value): MessageInterface
    {
        $this->validateHeaderName($name);
        $normalized = $this->normalizeHeaderName($name);
        $new = clone $this;

        if ($new->hasHeader($name)) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

        $value = $this->normalizeHeaderValue($value);
        $this->validateHeaderValue($value);

        $new->headerNames[$normalized] = $name;
        $new->headers[$name] = $value;
        return $new;
    }

    /**
     * Return an instance with the specified header appended with the given value.
     *
     * Existing values for the specified header will be maintained. The new
     * value(s) will be appended to the existing list. If the header did not
     * exist previously, it will be added.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new header and/or value.
     *
     * @param string $name Case-insensitive header field name to add.
     * @param string|string[] $value Header value(s).
     * @return static
     * @throws InvalidArgumentException for invalid header names or values.
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    public function withAddedHeader($name, $value): MessageInterface
    {
        $this->validateHeaderName($name);

        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $header = $this->headerNames[$this->normalizeHeaderName($name)];
        $value = $this->normalizeHeaderValue($value);
        $this->validateHeaderValue($value);

        $new = clone $this;
        $new->headers[$header] = array_merge($this->headers[$header], $value);
        return $new;
    }

    /**
     * Return an instance without the specified header.
     *
     * Header resolution MUST be done without case-sensitivity.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that removes
     * the named header.
     *
     * @param string $name Case-insensitive header field name to remove.
     * @return static
     */
    public function withoutHeader($name): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }

        $normalized = $this->normalizeHeaderName($name);
        $new = clone $this;
        unset($new->headers[$this->headerNames[$normalized]], $new->headerNames[$normalized]);
        return $new;
    }

    /**
     * Gets the body of the message.
     *
     * @return StreamInterface Returns the body as a stream.
     */
    public function getBody(): StreamInterface
    {
        return $this->stream;
    }

    /**
     * Return an instance with the specified message body.
     *
     * The body MUST be a StreamInterface object.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return a new instance that has the
     * new body stream.
     *
     * @param StreamInterface $body Body.
     * @return static
     * @throws InvalidArgumentException When the body is not valid.
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    /**
     * @param StreamInterface|string|resource $stream
     * @param string $mode
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    private function registerStream($stream, string $mode = 'wb+'): void
    {
        if ($stream instanceof StreamInterface) {
            $this->stream = $stream;
            return;
        }

        if (is_string($stream) || is_resource($stream)) {
            $this->stream = new Stream($stream, $mode);
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Stream must be a `Psr\Http\Message\StreamInterface` implementation'
            . ' or a string stream resource identifier or an actual stream resource; received `%s`.',
            (is_object($stream) ? get_class($stream) : gettype($stream))
        ));
    }

    /**
     * @param array<string, string|int|float> $originalHeaders
     * @throws InvalidArgumentException When the header name or header value is not valid.
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    private function registerHeaders(array $originalHeaders = []): void
    {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($originalHeaders as $name => $value) {
            $value = $this->normalizeHeaderValue($value);
            $this->validateHeaderValue($value);
            $this->validateHeaderName($name);
            $this->headers[$name] = $value;
            $this->headerNames[$this->normalizeHeaderName($name)] = $name;
        }
    }

    /**
     * @param string $protocol
     * @throws InvalidArgumentException for invalid HTTP protocol version.
     */
    private function registerProtocolVersion(string $protocol): void
    {
        if (!empty($protocol) && $protocol !== $this->protocol) {
            $this->validateProtocolVersion($protocol);
            $this->protocol = $protocol;
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function normalizeHeaderName(string $name): string
    {
        return strtolower($name);
    }

    /**
     * @param mixed $value
     * @return array
     */
    private function normalizeHeaderValue($value): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * @param mixed $name
     * @throws InvalidArgumentException for invalid header name.
     */
    private function validateHeaderName($name): void
    {
        if (!is_string($name) || !preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/', $name)) {
            throw new InvalidArgumentException(sprintf(
                '`%s` is not valid header name',
                (is_object($name) ? get_class($name) : (is_string($name) ? $name : gettype($name)))
            ));
        }
    }

    /**
     * @param mixed $value
     * @throws InvalidArgumentException for invalid header value.
     * @psalm-suppress MixedAssignment
     */
    private function validateHeaderValue($value): void
    {
        if (!is_array($value) || empty($value)) {
            throw new InvalidArgumentException('Invalid header value: must be an array and must not be empty.');
        }

        foreach ($value as $item) {
            if (
                (!is_string($item) && !is_numeric($item))
                || preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', (string) $item)
                || preg_match("/(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))/", (string) $item)
            ) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not valid header value',
                    (is_object($item) ? get_class($item) : (is_string($item) ? $item : gettype($item)))
                ));
            }
        }
    }

    /**
     * @param mixed $protocol
     * @throws InvalidArgumentException for invalid HTTP protocol version.
     */
    private function validateProtocolVersion($protocol): void
    {
        if (!is_string($protocol) || empty($protocol)) {
            throw new InvalidArgumentException('HTTP protocol version must be an string and must not be empty.');
        }

        $supportedProtocolVersions = ['1.0', '1.1', '2.0', '2'];

        if (!in_array($protocol, $supportedProtocolVersions, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version `%s` provided. Supported (%s) in string types.',
                $protocol,
                implode(', ', $supportedProtocolVersions)
            ));
        }
    }
}
