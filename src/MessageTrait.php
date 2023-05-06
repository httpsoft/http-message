<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function array_merge;
use function array_values;
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
use function trim;

/**
 * Trait implementing the methods defined in `Psr\Http\Message\MessageInterface`.
 *
 * @see https://github.com/php-fig/http-message/tree/master/src/MessageInterface.php
 */
trait MessageTrait
{
    /**
     * Supported HTTP Protocol Versions.
     *
     * @var string[]
     */
    private static array $supportedProtocolVersions = ['1.0', '1.1', '2.0', '2'];

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
     * @var StreamInterface|null
     */
    private ?StreamInterface $stream;

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
        return (is_string($name) && isset($this->headerNames[strtolower($name)]));
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

        return $this->headers[$this->headerNames[strtolower($name)]];
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
        $normalized = $this->normalizeHeaderName($name);
        $value = $this->normalizeHeaderValue($value);
        $new = clone $this;

        if (isset($new->headerNames[$normalized])) {
            unset($new->headers[$new->headerNames[$normalized]]);
        }

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
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $header = $this->headerNames[$this->normalizeHeaderName($name)];
        $value = $this->normalizeHeaderValue($value);

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
        if ($this->stream === null) {
            $this->stream = new Stream();
        }

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
     * @throws InvalidArgumentException if the body is not valid.
     */
    public function withBody(StreamInterface $body): MessageInterface
    {
        if ($this->stream === $body) {
            return $this;
        }

        $new = clone $this;
        $new->stream = $body;
        return $new;
    }

    /**
     * @param mixed $stream
     * @param string $mode
     */
    private function registerStream($stream, string $mode = 'wb+'): void
    {
        if ($stream === null || $stream instanceof StreamInterface) {
            $this->stream = $stream;
            return;
        }

        if (is_string($stream) || is_resource($stream)) {
            $this->stream = new Stream($stream, $mode);
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Stream must be a `Psr\Http\Message\StreamInterface` implementation or null'
            . ' or a string stream resource identifier or an actual stream resource; received `%s`.',
            (is_object($stream) ? get_class($stream) : gettype($stream))
        ));
    }

    /**
     * @param array $originalHeaders
     * @throws InvalidArgumentException if the header name or header value is not valid.
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedPropertyTypeCoercion
     */
    private function registerHeaders(array $originalHeaders = []): void
    {
        $this->headers = [];
        $this->headerNames = [];

        foreach ($originalHeaders as $name => $value) {
            $this->headerNames[$this->normalizeHeaderName($name)] = $name;
            $this->headers[$name] = $this->normalizeHeaderValue($value);
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
     * @see https://tools.ietf.org/html/rfc7230#section-3.2
     *
     * @param mixed $name
     * @return string
     * @throws InvalidArgumentException for invalid header name.
     */
    private function normalizeHeaderName($name): string
    {
        if (!is_string($name) || !preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name)) {
            throw new InvalidArgumentException(sprintf(
                '`%s` is not valid header name.',
                (is_object($name) ? get_class($name) : (is_string($name) ? $name : gettype($name)))
            ));
        }

        return strtolower($name);
    }

    /**
     * @see https://tools.ietf.org/html/rfc7230#section-3.2
     *
     * @param mixed $value
     * @return array
     * @throws InvalidArgumentException for invalid header name.
     */
    private function normalizeHeaderValue($value): array
    {
        $value = is_array($value) ? array_values($value) : [$value];

        if (empty($value)) {
            throw new InvalidArgumentException(
                'Header value must be a string or an array of strings, empty array given.',
            );
        }

        $normalizedValues = [];

        foreach ($value as $v) {
            if ((!is_string($v) && !is_numeric($v)) || !preg_match('/^[ \t\x21-\x7E\x80-\xFF]*$/D', (string) $v)) {
                throw new InvalidArgumentException(sprintf(
                    '"%s" is not valid header value.',
                    (is_object($v) ? get_class($v) : (is_string($v) ? $v : gettype($v)))
                ));
            }

            $normalizedValues[] = trim((string) $v, " \t");
        }

        return $normalizedValues;
    }

    /**
     * @param mixed $protocol
     * @throws InvalidArgumentException for invalid HTTP protocol version.
     */
    private function validateProtocolVersion($protocol): void
    {
        if (!in_array($protocol, self::$supportedProtocolVersions, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP protocol version "%s" provided. The following strings are supported: "%s".',
                is_string($protocol) ? $protocol : gettype($protocol),
                implode('", "', self::$supportedProtocolVersions),
            ));
        }
    }
}
