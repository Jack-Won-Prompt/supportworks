<?php

namespace App\Services\WorksBuilder\Ai;

/**
 * AI Provider 호출 실패 — 명세 v11 §1.7 분류 코드 포함.
 */
class AiAttemptException extends \RuntimeException
{
    public const STATUS_TIMEOUT        = 'timeout';
    public const STATUS_RATE_LIMIT     = 'rate_limit';
    public const STATUS_CONTENT_FILTER = 'content_filter';
    public const STATUS_HTTP_5XX       = 'http_5xx';
    public const STATUS_HTTP_4XX       = 'http_4xx';
    public const STATUS_PARSE_ERROR    = 'parse_error';
    public const STATUS_OTHER          = 'other';

    public function __construct(
        public readonly string $statusCode,
        string $message,
        public readonly bool $fatal = false,
    ) {
        parent::__construct($message);
    }
}
