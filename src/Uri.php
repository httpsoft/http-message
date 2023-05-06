<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function implode;
use function get_class;
use function gettype;
use function in_array;
use function is_float;
use function is_numeric;
use function is_object;
use function is_string;
use function ltrim;
use function parse_url;
use function preg_replace;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;
use function strtolower;
use function strtr;

final class Uri implements UriInterface
{
    /**
     * Standard ports and supported schemes.
     */
    private const SCHEMES = [80 => 'http', 443 => 'https'];

    /**
     * @var string
     */
    private string $scheme = '';

    /**
     * @var string
     */
    private string $userInfo = '';

    /**
     * @var string
     */
    private string $host = '';

    /**
     * @var int|null
     */
    private ?int $port = null;

    /**
     * @var string
     */
    private string $path = '';

    /**
     * @var string
     */
    private string $query = '';

    /**
     * @var string
     */
    private string $fragment = '';

    /**
     * @var string|null
     */
    private ?string $cache = null;

    /**
     * @param string $uri
     */
    public function __construct(string $uri = '')
    {
        if ($uri === '') {
            return;
        }

        if (($uri = parse_url($uri)) === false) {
            throw new InvalidArgumentException('The source URI string appears to be malformed.');
        }

        $this->scheme = isset($uri['scheme']) ? $this->normalizeScheme($uri['scheme']) : '';
        $this->userInfo = isset($uri['user']) ? $this->normalizeUserInfo($uri['user'], $uri['pass'] ?? null) : '';
        $this->host = isset($uri['host']) ? $this->normalizeHost($uri['host']) : '';
        $this->port = isset($uri['port']) ? $this->normalizePort($uri['port']) : null;
        $this->path = isset($uri['path']) ? $this->normalizePath($uri['path']) : '';
        $this->query = isset($uri['query']) ? $this->normalizeQuery($uri['query']) : '';
        $this->fragment = isset($uri['fragment']) ? $this->normalizeFragment($uri['fragment']) : '';
    }

    /**
     * When cloning resets the URI string representation cache.
     */
    public function __clone()
    {
        $this->cache = null;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        if (is_string($this->cache)) {
            return $this->cache;
        }

        $this->cache = '';

        if ($this->scheme !== '') {
            $this->cache .= $this->scheme . ':';
        }

        if (($authority = $this->getAuthority()) !== '') {
            $this->cache .= '//' . $authority;
        }

        if ($this->path !== '') {
            if ($authority === '') {
                // If the path is starting with more than one "/" and no authority is present,
                // the starting slashes MUST be reduced to one.
                $this->cache .= $this->path[0] === '/' ? '/' . ltrim($this->path, '/') : $this->path;
            } else {
                // If the path is rootless and an authority is present, the path MUST be prefixed by "/".
                $this->cache .= $this->path[0] === '/' ? $this->path : '/' . $this->path;
            }
        }

        if ($this->query !== '') {
            $this->cache .= '?' . $this->query;
        }

        if ($this->fragment !== '') {
            $this->cache .= '#' . $this->fragment;
        }

        return $this->cache;
    }

    /**
     * {@inheritDoc}
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress PossiblyNullOperand
     */
    public function getAuthority(): string
    {
        if (($authority = $this->host) === '') {
            return '';
        }

        if ($this->userInfo !== '') {
            $authority = $this->userInfo . '@' . $authority;
        }

        if ($this->isNotStandardPort()) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * {@inheritDoc}
     */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * {@inheritDoc}
     */
    public function getPort(): ?int
    {
        return $this->isNotStandardPort() ? $this->port : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getPath(): string
    {
        if ($this->path === '' || $this->path === '/') {
            return $this->path;
        }

        if ($this->path[0] !== '/') {
            // If the path is rootless and an authority is present, the path MUST be prefixed by "/".
            return $this->host === '' ? $this->path : '/' . $this->path;
        }

        return '/' . ltrim($this->path, '/');
    }

    /**
     * {@inheritDoc}
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * {@inheritDoc}
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * {@inheritDoc}
     */
    public function withScheme($scheme): UriInterface
    {
        $this->checkStringType($scheme, 'scheme', __METHOD__);
        $schema = $this->normalizeScheme($scheme);

        if ($schema === $this->scheme) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $schema;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withUserInfo($user, $password = null): UriInterface
    {
        $this->checkStringType($user, 'user', __METHOD__);

        if ($password !== null) {
            $this->checkStringType($password, 'or null password', __METHOD__);
        }

        $userInfo = $this->normalizeUserInfo($user, $password);

        if ($userInfo === $this->userInfo) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $userInfo;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withHost($host): UriInterface
    {
        $this->checkStringType($host, 'host', __METHOD__);
        $host = $this->normalizeHost($host);

        if ($host === $this->host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withPort($port): UriInterface
    {
        $port = $this->normalizePort($port);

        if ($port === $this->port) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withPath($path): UriInterface
    {
        $this->checkStringType($path, 'path', __METHOD__);
        $path = $this->normalizePath($path);

        if ($path === $this->path) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withQuery($query): UriInterface
    {
        $this->checkStringType($query, 'query string', __METHOD__);
        $query = $this->normalizeQuery($query);

        if ($query === $this->query) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;
        return $new;
    }

    /**
     * {@inheritDoc}
     */
    public function withFragment($fragment): UriInterface
    {
        $this->checkStringType($fragment, 'URI fragment', __METHOD__);
        $fragment = $this->normalizeFragment($fragment);

        if ($fragment === $this->fragment) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }

    /**
     * Normalize the scheme component of the URI.
     *
     * @param string $scheme
     * @return string
     * @throws InvalidArgumentException for invalid or unsupported schemes.
     */
    private function normalizeScheme(string $scheme): string
    {
        if (!$scheme = preg_replace('#:(//)?$#', '', strtolower($scheme))) {
            return '';
        }

        if (!in_array($scheme, self::SCHEMES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Unsupported scheme "%s". It must be an empty string or any of "%s".',
                $scheme,
                implode('", "', self::SCHEMES)
            ));
        }

        return $scheme;
    }

    /**
     * Normalize the user information component of the URI.
     *
     * @param string $user
     * @param string|null $pass
     * @return string
     */
    private function normalizeUserInfo(string $user, ?string $pass = null): string
    {
        if ($user === '') {
            return '';
        }

        $pattern = '/(?:[^%a-zA-Z0-9_\-\.~\pL!\$&\'\(\)\*\+,;=]+|%(?![A-Fa-f0-9]{2}))/u';
        $userInfo = $this->encode($user, $pattern);

        if ($pass !== null) {
            $userInfo .= ':' . $this->encode($pass, $pattern);
        }

        return $userInfo;
    }

    /**
     * Normalize the host component of the URI.
     *
     * @param string $host
     * @return string
     */
    private function normalizeHost(string $host): string
    {
        return strtr($host, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Normalize the port component of the URI.
     *
     * @param mixed $port
     * @return int|null
     * @throws InvalidArgumentException for invalid ports.
     */
    private function normalizePort($port): ?int
    {
        if ($port === null) {
            return null;
        }

        if (!is_numeric($port) || is_float($port)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%s" specified. It must be an integer, an integer string, or null.',
                (is_object($port) ? get_class($port) : gettype($port))
            ));
        }

        $port = (int) $port;

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException(sprintf(
                'Invalid port "%d" specified. It must be a valid TCP/UDP port in range 1..65535.',
                $port
            ));
        }

        return $port;
    }

    /**
     * Normalize the path component of the URI.
     *
     * @param string $path
     * @return string
     * @throws InvalidArgumentException for invalid paths.
     */
    private function normalizePath(string $path): string
    {
        if ($path === '' || $path === '/') {
            return $path;
        }

        return $this->encode($path, '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/]++|%(?![A-Fa-f0-9]{2}))/');
    }

    /**
     * Normalize the query string of the URI.
     *
     * @param string $query
     * @return string
     * @throws InvalidArgumentException for invalid query strings.
     */
    private function normalizeQuery(string $query): string
    {
        if ($query === '' || $query === '?') {
            return '';
        }

        if ($query[0] === '?') {
            $query = ltrim($query, '?');
        }

        return $this->encode($query, '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/');
    }

    /**
     * Normalize the fragment component of the URI.
     *
     * @param string $fragment
     * @return string
     */
    private function normalizeFragment(string $fragment): string
    {
        if ($fragment === '' || $fragment === '#') {
            return '';
        }

        if ($fragment[0] === '#') {
            $fragment = ltrim($fragment, '#');
        }

        return $this->encode($fragment, '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/');
    }

    /**
     * Percent encodes all reserved characters in the provided string according to the provided pattern.
     * Characters that are already encoded as a percentage will not be re-encoded.
     *
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @param string $string
     * @param string $pattern
     * @return string
     */
    private function encode(string $string, string $pattern): string
    {
        return (string) preg_replace_callback(
            $pattern,
            static fn (array $matches) => rawurlencode($matches[0]),
            $string,
        );
    }

    /**
     * Is this a non-standard port for the scheme.
     *
     * @return bool
     */
    private function isNotStandardPort(): bool
    {
        if ($this->port === null) {
            return false;
        }

        return (!isset(self::SCHEMES[$this->port]) || $this->scheme !== self::SCHEMES[$this->port]);
    }

    /**
     * Checks whether the value being passed is a string.
     *
     * @param mixed $value
     * @param string $phrase
     * @param string $method
     * @throws InvalidArgumentException for not string types.
     */
    private function checkStringType($value, string $phrase, string $method): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException(sprintf(
                '"%s" method expects a string type %s. "%s" received.',
                $method,
                $phrase,
                (is_object($value) ? get_class($value) : gettype($value))
            ));
        }
    }
}
