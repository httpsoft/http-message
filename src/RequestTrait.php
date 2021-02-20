<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

use function gettype;
use function get_class;
use function is_object;
use function is_string;
use function preg_match;
use function sprintf;

/**
 * Trait implementing the methods defined in `Psr\Http\Message\RequestInterface`.
 *
 * @see https://github.com/php-fig/http-message/tree/master/src/RequestInterface.php
 */
trait RequestTrait
{
    use MessageTrait;

    /**
     * @var string
     */
    private string $method = 'GET';

    /**
     * @var null|string
     */
    private ?string $requestTarget = null;

    /**
     * @var UriInterface
     */
    private UriInterface $uri;

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget !== null) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        $query = $this->uri->getQuery();

        if ($target !== '' && $query !== '') {
            $target .= '?' . $query;
        }

        return $target ?: '/';
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget): RequestInterface
    {
        if ($requestTarget === $this->requestTarget) {
            return $this;
        }

        if (!is_string($requestTarget) || preg_match('/\s/', $requestTarget)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid request target. Request target must be a string and cannot contain whitespace.',
                (is_object($requestTarget) ? get_class($requestTarget) : gettype($requestTarget))
            ));
        }

        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     * @return static
     * @throws InvalidArgumentException for invalid HTTP methods.
     * @psalm-suppress DocblockTypeContradiction
     */
    public function withMethod($method): RequestInterface
    {
        if ($method === $this->method) {
            return $this;
        }

        if (!is_string($method)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid HTTP method. It must be a string, %s received.',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $new = clone $this;
        $new->method = $method;
        return $new;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     * @return static
     * @throws InvalidArgumentException for invalid URI.
     */
    public function withUri(UriInterface $uri, $preserveHost = false): RequestInterface
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $new = clone $this;
        $new->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('host')) {
            $new->updateHostHeaderFromUri();
        }

        return $new;
    }

    /**
     * @param string $method
     * @param UriInterface|string $uri
     * @param array $headers
     * @param StreamInterface|string|resource|null $body
     * @param string $protocol
     */
    private function init(
        string $method = 'GET',
        $uri = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ): void {
        $this->method = $method;
        $this->setUri($uri);

        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);

        if (!$this->hasHeader('host')) {
            $this->updateHostHeaderFromUri();
        }
    }

    /**
     * @param UriInterface|string $uri
     * @throws InvalidArgumentException for invalid URI.
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    private function setUri($uri): void
    {
        if ($uri instanceof UriInterface) {
            $this->uri = $uri;
            return;
        }

        if (is_string($uri)) {
            $this->uri = new Uri($uri);
            return;
        }

        throw new InvalidArgumentException(sprintf(
            '"%s" is not valid URI. It must be a null, a string, or a "\Psr\Http\Message\UriInterface" instance.',
            (is_object($uri) ? get_class($uri) : gettype($uri))
        ));
    }

    /**
     * Updates `Host` header from the current URI and sets the `Host` first in the list of headers.
     *
     * @see https://tools.ietf.org/html/rfc7230#section-5.4
     */
    private function updateHostHeaderFromUri(): void
    {
        $host = $this->uri->getHost();

        if ($host === '') {
            return;
        }

        if ($port = $this->uri->getPort()) {
            $host .= ':' . $port;
        }

        $this->headerNames['host'] ??= 'Host';
        $this->headers = [$this->headerNames['host'] => [$host]] + $this->headers;
    }
}
