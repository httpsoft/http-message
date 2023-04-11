<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Request;
use HttpSoft\Message\Uri;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use stdClass;

use function array_merge;
use function chr;

final class RequestTest extends TestCase
{
    private const DEFAULT_METHOD = 'GET';

    /**
     * @var Request
     */
    private Request $request;

    public function setUp(): void
    {
        $this->request = new Request();
    }

    public function testGetDefault(): void
    {
        $this->assertSame('/', $this->request->getRequestTarget());
        $this->assertSame(self::DEFAULT_METHOD, $this->request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());
    }

    public function testConstructorAndGet(): void
    {
        $request = new Request($method = 'POST', $uri = new Uri('/example.com/?query=string'));
        $this->assertSame('/example.com/?query=string', $request->getRequestTarget());
        $this->assertSame($method, $request->getMethod());
        $this->assertSame($uri, $request->getUri());
    }

    public function testWithRequestTarget(): void
    {
        $request = $this->request->withRequestTarget('*');
        $this->assertNotSame($this->request, $request);
        $this->assertSame('*', $request->getRequestTarget());
    }

    public function testWithRequestTargetHasNotBeenChangedNotClone(): void
    {
        $request = $this->request->withRequestTarget(null);
        $this->assertSame($this->request, $request);
        $this->assertSame('/', $request->getRequestTarget());

        $request = new Request('GET', '/example.com/?query=string');
        $newRequest = $request->withRequestTarget(null);
        $this->assertSame($request, $newRequest);
        $this->assertSame('/example.com/?query=string', $request->getRequestTarget());
    }

    /**
     * @return array
     */
    public function invalidRequestTargetProvider(): array
    {
        return [['/ *'], ['Request Target'], ["Request\nTarget"], ["Request\tTarget"], ["Request\rTarget"]];
    }

    /**
     * @dataProvider invalidRequestTargetProvider
     * @param mixed $requestTarget
     */
    public function testWithRequestTargetThrowExceptionInvalidRequestTarget($requestTarget): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withRequestTarget($requestTarget);
    }

    public function testWithMethod(): void
    {
        $request = $this->request->withMethod($method = 'POST');
        $this->assertNotSame($this->request, $request);
        $this->assertSame($method, $request->getMethod());

        $request = $this->request->withMethod($method = 'PoSt');
        $this->assertNotSame($this->request, $request);
        $this->assertSame($method, $request->getMethod());
    }

    public function testWithMethodHasNotBeenChangedNotClone(): void
    {
        $request = $this->request->withMethod(self::DEFAULT_METHOD);
        $this->assertSame($this->request, $request);
        $this->assertSame(self::DEFAULT_METHOD, $request->getMethod());
    }

    public function testWithUri(): void
    {
        $uri = new Uri('/');
        $request = $this->request->withUri($uri);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUriHasNotBeenChangedNotClone(): void
    {
        $uri = $this->request->getUri();
        $request = $this->request->withUri($uri);
        $this->assertSame($this->request, $request);
        $this->assertSame($uri, $this->request->getUri());
    }

    public function testWithUriUpdateHostHeaderFromUri(): void
    {
        $request = new Request('GET', 'http://example.com/path/to/action');
        $this->assertSame(['Host' => ['example.com']], $request->getHeaders());
        $this->assertSame(['example.com'], $request->getHeader('host'));

        $newUri = $request->getUri()->withHost('example.org');

        $newRequest = $request->withUri($newUri);
        $this->assertSame(['Host' => ['example.org']], $newRequest->getHeaders());
        $this->assertSame(['example.org'], $newRequest->getHeader('host'));

        $newRequestWithUriPort = $request->withUri($newUri->withPort(8080));
        $this->assertSame(['Host' => ['example.org:8080']], $newRequestWithUriPort->getHeaders());
        $this->assertSame(['example.org:8080'], $newRequestWithUriPort->getHeader('host'));

        $newRequestWithUriStandardPort = $request->withUri($newUri->withPort(80));
        $this->assertSame(['Host' => ['example.org']], $newRequestWithUriStandardPort->getHeaders());
        $this->assertSame(['example.org'], $newRequestWithUriStandardPort->getHeader('host'));
    }

    /**
     * @return array
     */
    public function invalidUriProvider(): array
    {
        return $this->getInvalidValues();
    }

    /**
     * @dataProvider invalidUriProvider
     * @param mixed $uri
     */
    public function testUriPassingInConstructorThrowExceptionInvalidUri($uri): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Request(self::DEFAULT_METHOD, $uri);
    }

    /**
     * @param array $values
     * @return array
     */
    private function getInvalidValues(array $values = []): array
    {
        $common = [
            'null' => [null],
            'true' => [true],
            'false' => [false],
            'int' => [1],
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

    /**
     * @dataProvider provideHeaderValuesContainingNotAllowedChars
     */
    public function testCannotHaveHeaderWithInvalidValue(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $request = new Request('GET', 'https://example.com/');
        $request->withHeader($name, 'Bar');
    }

    public static function provideHeaderValuesContainingNotAllowedChars(): array
    {
        // Explicit tests for newlines as the most common exploit vector.
        $tests = [
            ["new\nline"],
            ["new\r\nline"],
            ["new\rline"],
            ["new\r\n line"],
            ["newline\n"],
            ["\nnewline"],
            ["newline\r\n"],
            ["\n\rnewline"],
        ];

        for ($i = 0; $i <= 0xff; $i++) {
            if (chr($i) === "\t") {
                continue;
            }
            if (chr($i) === " ") {
                continue;
            }
            if ($i >= 0x21 && $i <= 0x7e) {
                continue;
            }
            if ($i >= 0x80) {
                continue;
            }

            $tests[] = ["foo" . chr($i) . "bar"];
            $tests[] = ["foo" . chr($i)];
        }

        return $tests;
    }
}
