<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use StdClass;

use function array_merge;

final class UriTest extends TestCase
{
    /**
     * @var Uri
     */
    private Uri $uri;

    public function setUp(): void
    {
        $this->uri = new Uri();
    }

    public function testGettersDefault(): void
    {
        $this->assertEmpty($this->uri->__toString());
        $this->assertEmpty($this->uri->getScheme());
        $this->assertEmpty($this->uri->getAuthority());
        $this->assertEmpty($this->uri->getUserInfo());
        $this->assertEmpty($this->uri->getHost());
        $this->assertNull($this->uri->getPort());
        $this->assertEmpty($this->uri->getPath());
        $this->assertEmpty($this->uri->getQuery());
        $this->assertEmpty($this->uri->getFragment());
    }

    public function testConstructorWithUriParameter(): void
    {
        $uri = new Uri('http://us<er:pass>word@Host:8080/pa[th/to/tar]get/?qu^ery=str|ing#frag%ment');

        $this->assertSame('http', $uri->getScheme());
        $this->assertSame('us%3Cer:pass%3Eword@host:8080', $uri->getAuthority());
        $this->assertSame('us%3Cer:pass%3Eword', $uri->getUserInfo());
        $this->assertSame('host', $uri->getHost());
        $this->assertSame(8080, $uri->getPort());
        $this->assertSame('/pa%5Bth/to/tar%5Dget/', $uri->getPath());
        $this->assertSame('qu%5Eery=str%7Cing', $uri->getQuery());
        $this->assertSame('frag%25ment', $uri->getFragment());
        $this->assertSame(
            $uri->__toString(),
            'http://us%3Cer:pass%3Eword@host:8080/pa%5Bth/to/tar%5Dget/?qu%5Eery=str%7Cing#frag%25ment'
        );
    }

    /**
     * @return array
     */
    public function invalidConstructorProvider(): array
    {
        return [
            'more-than-two-slashes-to-http-scheme' => ['http:///host'],
            'more-than-two-slashes-to-https-scheme' => ['https:///host'],
            'slash-in-password' => ['http://user:pass/word@host'],
            'question-mark-in-password' => ['http://user:pass?word@host'],
            'number-sign-in-password' => ['http://user:pass#word@host'],
        ];
    }

    /**
     * @dataProvider invalidConstructorProvider
     * @param mixed $uri
     */
    public function testConstructorThrowExceptionForInvalidSourceUri($uri): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Uri($uri);
    }

    public function testToString(): void
    {
        $uri = new Uri($url = 'https://host/path?query=string#fragment');
        $this->assertSame($url, $uri->__toString());
        $this->assertSame($url, (string) $uri);
    }

    public function testToStringIfEmpty(): void
    {
        $uri = new Uri($url = '');
        $this->assertSame($url, $uri->__toString());
        $this->assertSame($url, (string) $uri);
    }

    public function testClone(): void
    {
        $uri = new Uri('https://host/path?query=string#fragment');
        $this->assertSame('https://host/path?query=string#fragment', $uri->__toString());
        $this->assertSame('https://host/path?query=string#fragment', (string) $uri);
        $this->assertSame('host', $uri->getHost());
        $cloneUri = $uri->withHost('clone-host');
        $this->assertSame('https://clone-host/path?query=string#fragment', $cloneUri->__toString());
        $this->assertSame('https://clone-host/path?query=string#fragment', (string) $cloneUri);
        $this->assertSame('clone-host', $cloneUri->getHost());
    }

    public function testSchemeAndPortIsStandard(): void
    {
        $uri = new Uri('http://example.com:80');
        $this->assertSame('http://example.com', (string) $uri);
        $this->assertSame('http', $uri->getScheme());
        $this->assertNull($uri->getPort());

        $uri = $uri->withScheme('https')->withPort(443);
        $this->assertSame('https://example.com', (string) $uri);
        $this->assertSame('https', $uri->getScheme());
        $this->assertNull($uri->getPort());
    }

    public function testSchemeAndPortIsNotStandard(): void
    {
        $uri = new Uri('http://example.com:8080');
        $this->assertSame('http://example.com:8080', (string) $uri);
        $this->assertSame('http', $uri->getScheme());
        $this->assertSame(8080, $uri->getPort());

        $uri = $uri->withScheme('https')->withPort(80);
        $this->assertSame('https://example.com:80', (string) $uri);
        $this->assertSame('https', $uri->getScheme());
        $this->assertSame(80, $uri->getPort());
    }

    public function testWithScheme(): void
    {
        $uri = $this->uri->withScheme($scheme = 'https');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame($scheme, $uri->getScheme());
    }

    public function testWithSchemeHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withScheme('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getScheme(), $uri->getScheme());
    }

    /**
     * @return array
     */
    public function invalidWithSchemeProvider(): array
    {
        return $this->getInvalidValues([
            'null' => [null],
            'int' => [1],
            'any-string' => ['string'],
            'file' => ['file:///'],
            'ftp' => ['ftp://'],
            'git' => ['git://'],
            'mailto' => ['mailto://'],
            'ssh' => ['ssh://'],
            'telnet' => ['telnet://'],
        ]);
    }

    /**
     * @dataProvider invalidWithSchemeProvider
     * @param mixed $scheme
     */
    public function testWithSchemeThrowExceptionForInvalidScheme($scheme): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withScheme($scheme);
    }

    public function testWithUserInfo(): void
    {
        $uri = $this->uri->withUserInfo('us<>er', $password = 'password');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('us%3C%3Eer:' . $password, $uri->getUserInfo());
    }

    public function testWithUserInfoWithoutPassword(): void
    {
        $uri = $this->uri->withUserInfo('us<>er');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('us%3C%3Eer', $uri->getUserInfo());
    }

    public function testWithUserInfoHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withUserInfo('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getUserInfo(), $uri->getUserInfo());
    }

    /**
     * @return array
     */
    public function invalidWithUserInfoUserProvider(): array
    {
        return $this->getInvalidValues(['null' => [null], 'int' => [1]]);
    }

    /**
     * @dataProvider invalidWithUserInfoUserProvider
     * @param mixed $user
     */
    public function testWithUserInfoThrowExceptionForInvalidUser($user): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withUserInfo($user);
    }

    /**
     * @return array
     */
    public function invalidWithUserInfoPasswordProvider(): array
    {
        return $this->getInvalidValues(['int' => [1]]);
    }

    /**
     * @dataProvider invalidWithUserInfoPasswordProvider
     * @param mixed $password
     */
    public function testWithUserInfoThrowExceptionForInvalidPassword($password): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withUserInfo('user', $password);
    }

    public function testWithHost(): void
    {
        $uri = $this->uri->withHost('Host');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('host', $uri->getHost());
    }

    public function testWithHostHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withHost('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getHost(), $uri->getHost());
    }

    /**
     * @return array
     */
    public function invalidWithHostProvider(): array
    {
        return $this->getInvalidValues(['null' => [null], 'int' => [1]]);
    }

    /**
     * @dataProvider invalidWithHostProvider
     * @param mixed $host
     */
    public function testWithHostThrowExceptionForInvalidHost($host): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withHost($host);
    }

    public function testWithPort(): void
    {
        $uri = $this->uri->withPort($port = 8080);
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame($port, $uri->getPort());
    }

    public function testWithPortHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withPort(null);
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getPort(), $uri->getPort());
    }

    /**
     * @return array
     */
    public function invalidWithPortProvider(): array
    {
        return $this->getInvalidValues([
            'empty-string' => [''],
            'any-string' => ['string'],
            'int-less-than-allowed' => [0],
            'int-more-than-allowed' => [65536],
        ]);
    }

    /**
     * @dataProvider invalidWithPortProvider
     * @param mixed $port
     */
    public function testWithPortThrowExceptionForInvalidPort($port): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withPort($port);
    }

    public function testWithPath(): void
    {
        $uri = $this->uri->withPath($path = 'path/to/tar<>get');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('path/to/tar%3C%3Eget', $uri->getPath());
    }

    public function testWithPathNormalizeWithFirstAndLastSlashes(): void
    {
        $uri = $this->uri->withPath('/path/to/tar<>get/');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('/path/to/tar%3C%3Eget/', $uri->getPath());
    }

    public function testWithPathNormalizeWithManyFirstSlash(): void
    {
        $uri = $this->uri->withPath('///path/to/tar<>get');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('/path/to/tar%3C%3Eget', $uri->getPath());
    }

    public function testWithPathHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withPath('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getPath(), $uri->getPath());
    }

    /**
     * @return array
     */
    public function invalidWithPathProvider(): array
    {
        return $this->getInvalidValues(['null' => [null], 'int' => [1]]);
    }

    /**
     * @dataProvider invalidWithPathProvider
     * @param mixed $path
     */
    public function testWithPathThrowExceptionForInvalidPath($path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withPath($path);
    }

    public function testWithQuery(): void
    {
        $uri = $this->uri->withQuery('key1=val<>ue1&ke<>y2=value2');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('key1=val%3C%3Eue1&ke%3C%3Ey2=value2', $uri->getQuery());
    }

    public function testWithQueryNormalizeLeadingCharacter(): void
    {
        $uri = $this->uri->withQuery('?key1=val<>ue1&ke<>y2=value2');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('key1=val%3C%3Eue1&ke%3C%3Ey2=value2', $uri->getQuery());
    }

    public function testWithQueryHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withQuery('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getQuery(), $uri->getQuery());
    }

    /**
     * @return array
     */
    public function invalidWithQueryProvider(): array
    {
        return $this->getInvalidValues(['null' => [null], 'int' => [1]]);
    }

    /**
     * @dataProvider invalidWithQueryProvider
     * @param mixed $query
     */
    public function testWithQueryThrowExceptionForInvalidQuery($query): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withQuery($query);
    }

    public function testWithFragment(): void
    {
        $uri = $this->uri->withFragment('frag<>ment');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('frag%3C%3Ement', $uri->getFragment());
    }

    public function testWithFragmentNormalizeLeadingCharacter(): void
    {
        $uri = $this->uri->withFragment('#frag<>ment');
        $this->assertNotSame($this->uri, $uri);
        $this->assertSame('frag%3C%3Ement', $uri->getFragment());
    }

    public function testWithFragmentHasNotBeenChangedNotClone(): void
    {
        $uri = $this->uri->withFragment('');
        $this->assertSame($this->uri, $uri);
        $this->assertSame($this->uri->getFragment(), $uri->getFragment());
    }

    /**
     * @return array
     */
    public function invalidWithFragmentProvider(): array
    {
        return $this->getInvalidValues(['null' => [null], 'int' => [1]]);
    }

    /**
     * @dataProvider invalidWithFragmentProvider
     * @param mixed $query
     */
    public function testWithFragmentThrowExceptionForInvalidFragment($query): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->uri->withQuery($query);
    }

    public function testUtf8Host(): void
    {
        $uri = (new Uri())->withHost($host = '例子.例子');
        $this->assertSame($host, $uri->getHost());
        $this->assertSame('//' . $host, (string) $uri);
    }

    public function testPercentageEncodedWillNotBeReEncoded(): void
    {
        $uri = new Uri('https://example.com/pa<th/to/tar>get/?qu^ery=str|ing#frag%ment');
        $this->assertSame('https://example.com/pa%3Cth/to/tar%3Eget/?qu%5Eery=str%7Cing#frag%25ment', (string) $uri);

        $newUri = new Uri((string) $uri);
        $this->assertSame((string) $uri, (string) $newUri);

        $uri = (new Uri($path = '/pa%3C%3Eth'))->withPath($path);
        $this->assertSame($path, $uri->getPath());

        $uri = (new Uri('?' . $query = 'que%3C%3Ery=str%7Cing'))->withQuery($query);
        $this->assertSame($query, $uri->getQuery());

        $uri = (new Uri('#' . $fragment = 'frag%3C%3Ement'))->withFragment($fragment);
        $this->assertSame($fragment, $uri->getFragment());
    }

    /**
     * @param array $values
     * @return array
     */
    private function getInvalidValues(array $values = []): array
    {
        $common = [
            'false' => [false],
            'true' => [true],
            'float' => [1.1],
            'empty-array' => [[]],
            'object' => [new StdClass()],
            'callable' => [fn() => null],
        ];

        if ($values) {
            return array_merge($common, $values);
        }

        return $common;
    }
}
