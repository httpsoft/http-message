<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\UploadedFile;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use StdClass;

use const UPLOAD_ERR_OK;

final class ServerRequestTest extends TestCase
{
    private const DEFAULT_METHOD = 'GET';

    /**
     * @var ServerRequest
     */
    private ServerRequest $request;

    public function setUp(): void
    {
        $this->request = new ServerRequest();
    }

    public function testGetDefault(): void
    {
        $this->assertSame('/', $this->request->getRequestTarget());
        $this->assertSame(self::DEFAULT_METHOD, $this->request->getMethod());
        $this->assertInstanceOf(UriInterface::class, $this->request->getUri());
        $this->assertSame([], $this->request->getAttributes());
        $this->assertSame([], $this->request->getServerParams());
        $this->assertSame([], $this->request->getCookieParams());
        $this->assertSame([], $this->request->getQueryParams());
        $this->assertSame([], $this->request->getUploadedFiles());
        $this->assertNull($this->request->getParsedBody());
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
        $uri = $this->createMock(UriInterface::class);
        $request = $this->request->withUri($uri);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($uri, $request->getUri());
    }

    public function testWithUriUpdateHostHeaderFromUri(): void
    {
        $request = new ServerRequest([], [], [], [], [], 'GET', 'http://example.com/path/to/action');
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
        new ServerRequest([], [], [], [], [], self::DEFAULT_METHOD, $uri);
    }

    public function testWithAttributeAndGetAttributes(): void
    {
        $firstRequest = $this->request->withAttribute('name', 'value');
        $this->assertNotSame($this->request, $firstRequest);
        $this->assertSame('value', $firstRequest->getAttribute('name'));

        $secondRequest = $firstRequest->withAttribute('name', 'value');
        $this->assertSame($firstRequest, $secondRequest);
        $this->assertSame('value', $secondRequest->getAttribute('name'));
    }

    public function testWithoutAttributeAndGetAttributes(): void
    {
        $firstRequest = $this->request->withAttribute('name', 'value');
        $this->assertNotSame($this->request, $firstRequest);
        $this->assertSame('value', $firstRequest->getAttribute('name'));

        $secondRequest = $firstRequest->withoutAttribute('name');
        $this->assertNotSame($firstRequest, $secondRequest);
        $this->assertNull($secondRequest->getAttribute('name'));
        $this->assertSame([], $secondRequest->getAttributes());

        $thirdRequest = $secondRequest->withoutAttribute('name');
        $this->assertSame($secondRequest, $thirdRequest);
        $this->assertNull($thirdRequest->getAttribute('name'));
        $this->assertSame([], $thirdRequest->getAttributes());
    }

    public function testGetAttributePassedDefaultValue(): void
    {
        $this->assertNull($this->request->getAttribute('name'));
        $this->assertSame([], $this->request->getAttribute('name', []));
        $this->assertSame(123, $this->request->getAttribute('name', 123));
    }

    public function testWithCookieParams(): void
    {
        $cookieParams = [
            'cookie_name' => 'adf8ck8eb43218g8fa5f8259b6425371',
        ];
        $request = $this->request->withCookieParams($cookieParams);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($cookieParams, $request->getCookieParams());
    }

    public function testWithQueryParams(): void
    {
        $queryParams = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $request = $this->request->withQueryParams($queryParams);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($queryParams, $request->getQueryParams());
    }

    /**
     * @return array
     */
    public function validParsedBodyProvider(): array
    {
        return [
            'object' => [new StdClass()],
            'array' => [['key' => 'value']],
            'empty-array' => [[]],
        ];
    }

    /**
     * @dataProvider validParsedBodyProvider
     * @param mixed $parsedBody
     */
    public function testWithParsedBodyPassedValidParsedBody($parsedBody): void
    {
        $request = $this->request->withParsedBody($parsedBody);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($parsedBody, $request->getParsedBody());
    }

    /**
     * @return array
     */
    public function invalidParsedBodyProvider(): array
    {
        return [
            'true' => [true],
            'false' => [false],
            'int' => [1],
            'float' => [1.1],
            'string' => ['string'],
        ];
    }

    /**
     * @dataProvider invalidParsedBodyProvider
     * @param mixed $parsedBody
     */
    public function testWithParsedBodyThrowExceptionForInvalidParsedBody($parsedBody): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withParsedBody($parsedBody);
    }

    public function testWithUploadedFiles(): void
    {
        $uploadedFiles = [
            new UploadedFile('file.txt', 1024, UPLOAD_ERR_OK),
            new UploadedFile('image.png', 67890, UPLOAD_ERR_OK),
            [
                new UploadedFile('file.md', 1024, UPLOAD_ERR_OK),
                new UploadedFile('image.jpg', 67890, UPLOAD_ERR_OK),
            ],
        ];

        $request = $this->request->withUploadedFiles($uploadedFiles);
        $this->assertNotSame($this->request, $request);
        $this->assertSame($uploadedFiles, $request->getUploadedFiles());
    }

    /**
     * @return array
     */
    public function invalidUploadedFilesProvider(): array
    {
        return [
            'array-null' => [[null]],
            'array-true' => [[true]],
            'array-false' => [[false]],
            'array-int' => [[1]],
            'array-float' => [[1.1]],
            'array-string' => [['string']],
            'array-object' => [[new StdClass()]],
            'array-callable' => [[fn() => null]],
        ];
    }

    /**
     * @dataProvider invalidUploadedFilesProvider
     * @param mixed $uploadedFiles
     */
    public function testWithUploadedFilesThrowExceptionForInvalidUploadedFiles($uploadedFiles): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request->withUploadedFiles($uploadedFiles);
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
}
