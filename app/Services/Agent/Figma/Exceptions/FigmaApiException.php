<?php

namespace App\Services\Agent\Figma\Exceptions;

use RuntimeException;

class FigmaApiException extends RuntimeException
{
    public function __construct(string $message = 'Figma API 오류가 발생했습니다.', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
