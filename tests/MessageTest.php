<?php

declare(strict_types=1);

namespace HttpSoft\Tests\Message;

use HttpSoft\Tests\Message\TestAsset\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;

use function array_merge;

final class MessageTest extends TestCase
{
    /**
     * @var Message
     */
    private Message $message;

    public function setUp(): void
    {
        $this->message = new Message();
    }

    public function testGetDefault(): void
    {
        $this->assertInstanceOf(StreamInterface::class, $this->message->getBody());
        $this->assertSame([], $this->message->getHeaders());
        $this->assertSame(Message::DEFAULT_PROTOCOL_VERSION, $this->message->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $message = $this->message->withProtocolVersion('2');
        $this->assertNotSame($this->message, $message);
        $this->assertSame('2', $message->getProtocolVersion());
    }

    public function testWithProtocolVersionHasNotBeenChangedNotClone(): void
    {
        $message = $this->message->withProtocolVersion(Message::DEFAULT_PROTOCOL_VERSION);
        $this->assertSame($this->message, $message);
        $this->assertSame(Message::DEFAULT_PROTOCOL_VERSION, $message->getProtocolVersion());
    }

    /**
     * @return array
     */
    public function unsupportedProtocolVersionProvider(): array
    {
        return $this->getInvalidValues(['int' => [1], 'float' => [1.1], 'unsupported' => ['1'], 'null' => [null]]);
    }

    /**
     * @dataProvider unsupportedProtocolVersionProvider
     * @param mixed $version
     */
    public function testWithProtocolVersionThrowExceptionForUnsupportedProtocolVersion($version): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withProtocolVersion($version);
    }

    public function testBodyPassingInConstructorStreamInterface(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $this->assertSame($stream, (new Message($stream))->getBody());
    }

    /**
     * @return array
     */
    public function invalidBodyTypeProvider(): array
    {
        return $this->getInvalidValues(['int' => [1]]);
    }

    /**
     * @dataProvider invalidBodyTypeProvider
     * @param mixed $body
     */
    public function testBodyPassingInConstructorThrowExceptionForInvalidBodyType($body): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Message($body);
    }

    /**
     * @return array
     */
    public function invalidBodyStringTypeProvider(): array
    {
        return ['file-not-exist' => ['/file/not/exist'], 'fail-wrapper' => ['php://fail']];
    }

    /**
     * @dataProvider invalidBodyStringTypeProvider
     * @param mixed $body
     */
    public function testBodyPassingInConstructorThrowExceptionForFileCannotBeOpened($body): void
    {
        $this->expectException(RuntimeException::class);
        new Message($body);
    }

    public function testWithBody(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    public function testWithAndGetHeaders(): void
    {
        $message = $this->message->withHeader('Name', 'Value');
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Name' => ['Value']], $message->getHeaders());
    }

    public function testWithAddedAndGetHeaders(): void
    {
        $firstMessage = $this->message->withAddedHeader('Name', 'FirstValue');
        $this->assertNotSame($this->message, $firstMessage);
        $this->assertSame(['Name' => ['FirstValue']], $firstMessage->getHeaders());
        $secondMessage = $firstMessage->withAddedHeader('Name', 'SecondValue');
        $this->assertNotSame($firstMessage, $secondMessage);
        $this->assertSame(['Name' => ['FirstValue', 'SecondValue']], $secondMessage->getHeaders());
    }

    public function testWithoutAndGetHeaders(): void
    {
        $firstMessage = $this->message->withHeader('Name', 'Value');
        $this->assertNotSame($this->message, $firstMessage);
        $this->assertSame(['Name' => ['Value']], $firstMessage->getHeaders());
        $secondMessage = $firstMessage->withoutHeader('Name');
        $this->assertNotSame($firstMessage, $secondMessage);
        $this->assertSame([], $secondMessage->getHeaders());
    }

    public function testHasHeaderFail(): void
    {
        $this->assertFalse($this->message->hasHeader('Name'));
    }

    public function testHasHeaderSuccess(): void
    {
        $message = $this->message->withHeader('Name', 'Value');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('Name'));
    }

    public function testGetHeader(): void
    {
        $message = $this->message->withHeader('Name', ['FirstValue', 'SecondValue']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['FirstValue', 'SecondValue'], $message->getHeader('Name'));
    }

    public function testGetHeaderNotFound(): void
    {
        $this->assertSame([], $this->message->getHeader('Name'));
    }

    public function testGetHeaderLine(): void
    {
        $message = $this->message->withHeader('Name', ['FirstValue', 'SecondValue']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame('FirstValue,SecondValue', $message->getHeaderLine('Name'));
    }

    public function testGetHeaderLineNotFound(): void
    {
        $this->assertSame('', $this->message->getHeaderLine('Name'));
    }

    /**
     * @return array
     */
    public function invalidHeaderNameProvider(): array
    {
        return $this->getInvalidValues([[null], [1], ['Na\me'], ['Na/me'], ['Na<me'], ['Na>me']]);
    }

    /**
     * @dataProvider invalidHeaderNameProvider
     * @param mixed $name
     */
    public function testWithHeaderThrowExceptionForInvalidHeader($name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withHeader($name, 'Value');
    }

    /**
     * @return array
     */
    public function invalidHeaderValueProvider(): array
    {
        return $this->getInvalidValues([[null], ["Va\nlue"], [["Va\r\nlue"]], ["Va\r)\nlue"]]);
    }

    /**
     * @dataProvider invalidHeaderValueProvider
     * @param mixed $value
     */
    public function testWithHeaderThrowExceptionForInvalidValue($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withHeader('Name', $value);
    }

    /**
     * @param array $values
     * @return array
     */
    private function getInvalidValues(array $values = []): array
    {
        $common = [
            'true' => [true],
            'false' => [false],
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
