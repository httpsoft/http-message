<?php

declare(strict_types=1);

namespace HttpSoft\Message;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

use function gettype;
use function get_class;
use function is_int;
use function is_float;
use function is_numeric;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Trait implementing the methods defined in `Psr\Http\Message\ResponseInterface`.
 *
 * @see https://github.com/php-fig/http-message/tree/master/src/ResponseInterface.php
 */
trait ResponseTrait
{
    use MessageTrait;

    /**
     * Map of standard HTTP status code and reason phrases.
     *
     * @link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @var array<int, string>
     */
    private static array $phrases = [
        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        // Successful 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // Client Errors 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        // Server Errors 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var int
     */
    private int $statusCode;

    /**
     * @var string
     */
    private string $reasonPhrase;

    /**
     * Gets the response status code.
     *
     * The status code is a 3-digit integer result code of the server's attempt
     * to understand and satisfy the request.
     *
     * @return int Status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     * @return static
     * @throws InvalidArgumentException for invalid status code arguments.
     * @psalm-suppress DocblockTypeContradiction
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress RedundantCondition
     * @psalm-suppress NoValue
     */
    public function withStatus($code, $reasonPhrase = ''): ResponseInterface
    {
        if (!is_int($code)) {
            if (!is_numeric($code) || is_float($code)) {
                throw new InvalidArgumentException(sprintf(
                    'Response status code is not valid. It must be an integer, %s received.',
                    (is_object($code) ? get_class($code) : gettype($code))
                ));
            }
            $code = (int) $code;
        }

        if (!is_string($reasonPhrase)) {
            throw new InvalidArgumentException(sprintf(
                'Response reason phrase is not valid. It must be a string, %s received.',
                (is_object($reasonPhrase) ? get_class($reasonPhrase) : gettype($reasonPhrase))
            ));
        }

        $new = clone $this;
        $new->setStatus($code, $reasonPhrase);
        return $new;
    }

    /**
     * Gets the response reason phrase associated with the status code.
     *
     * Because a reason phrase is not a required element in a response
     * status line, the reason phrase value MAY be null. Implementations MAY
     * choose to return the default RFC 7231 recommended reason phrase (or those
     * listed in the IANA HTTP Status Code Registry) for the response's
     * status code.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     * @return string Reason phrase; must return an empty string if none present.
     */
    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @param StreamInterface|string|resource|null $body
     * @param array $headers
     * @param string $protocol
     */
    private function init(
        int $statusCode = 200,
        string $reasonPhrase = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ): void {
        $this->setStatus($statusCode, $reasonPhrase);
        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);
    }

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @throws InvalidArgumentException for invalid status code arguments.
     * @link https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     */
    private function setStatus(int $statusCode, string $reasonPhrase = ''): void
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(
                'Response status code "%d" is not valid. It must be in 100..599 range.',
                $statusCode
            ));
        }

        $this->statusCode = $statusCode;
        $this->reasonPhrase = $reasonPhrase ?: (self::$phrases[$statusCode] ?? '');
    }
}
