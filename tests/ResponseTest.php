<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Message\Response;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use stdClass;

use function array_merge;

final class ResponseTest extends TestCase
{
    private const DEFAULT_STATUS_CODE = 200;
    private const DEFAULT_REASON_PHRASE = 'OK';

    /**
     * @var Response
     */
    private Response $response;

    public function setUp(): void
    {
        $this->response = new Response();
    }

    public function testGettersDefault(): void
    {
        $this->assertSame(self::DEFAULT_STATUS_CODE, $this->response->getStatusCode());
        $this->assertSame(self::DEFAULT_REASON_PHRASE, $this->response->getReasonPhrase());
        $this->assertInstanceOf(StreamInterface::class, $this->response->getBody());
        $this->assertSame('php://temp', $this->response->getBody()->getMetadata('uri'));
        $this->assertSame('', $this->response->getBody()->getContents());
        $this->assertSame([], $this->response->getHeaders());
        $this->assertSame('1.1', $this->response->getProtocolVersion());
    }

    public function testGettersSpecifiedArguments(): void
    {
        $response = new Response(
            $statusCode = 404,
            ['Content-Language' => 'en'],
            $stream = 'php://memory',
            $protocol = '2',
            $reasonPhrase = 'Not Found'
        );
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($reasonPhrase, $response->getReasonPhrase());
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
        $this->assertSame($stream, $response->getBody()->getMetadata('uri'));
        $this->assertSame('', $response->getBody()->getContents());
        $this->assertSame(['Content-Language' => ['en']], $response->getHeaders());
        $this->assertSame($protocol, $response->getProtocolVersion());
    }

    public function testWithStatus(): void
    {
        $response = $this->response->withStatus($code = 404);
        $this->assertNotSame($this->response, $response);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testWithStatusWithStringNumericCode(): void
    {
        $response = $this->response->withStatus($code = '404');
        $this->assertNotSame($this->response, $response);
        $this->assertSame((int) $code, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testWithStatusAndCustomReasonPhrase(): void
    {
        $response = $this->response->withStatus($code = 404, $customPhrase = 'Custom Phrase');
        $this->assertNotSame($this->response, $response);
        $this->assertSame($code, $response->getStatusCode());
        $this->assertSame($customPhrase, $response->getReasonPhrase());
    }

    public function testWithStatusHasNotBeenChangedCodeAndHasBeenChangedReasonPhrase(): void
    {
        $response = $this->response->withStatus(self::DEFAULT_STATUS_CODE, $customPhrase = 'Custom Phrase');
        $this->assertNotSame($this->response, $response);
        $this->assertSame(self::DEFAULT_STATUS_CODE, $response->getStatusCode());
        $this->assertSame($customPhrase, $response->getReasonPhrase());
    }

    /**
     * @return array
     */
    public function invalidStatusCodeProvider(): array
    {
        return $this->getInvalidValues(['status-code-less' => [99], 'status-code-more' => [600]]);
    }

    /**
     * @dataProvider invalidStatusCodeProvider
     * @param mixed $code
     */
    public function testWithStatusThrowExceptionForInvalidCode($code): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->response->withStatus($code);
    }

    /**
     * @return array
     */
    public function invalidStatusReasonPhraseProvider(): array
    {
        return $this->getInvalidValues(['status-code' => [self::DEFAULT_STATUS_CODE]]);
    }

    /**
     * @dataProvider invalidStatusReasonPhraseProvider
     * @param mixed $reasonPhrase
     */
    public function testWithStatusThrowExceptionForInvalidReasonPhrase($reasonPhrase): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->response->withStatus(self::DEFAULT_STATUS_CODE, $reasonPhrase);
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
            'array' => [[1, 1.1]],
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
