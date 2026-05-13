<?php

namespace App\Services\Agent\Figma\Exceptions;

class FigmaRateLimitException extends FigmaApiException
{
    public function __construct(public readonly ?string $retryAfter = null)
    {
        parent::__construct(
            'Figma API 요청 한도를 초과했습니다.' . ($retryAfter ? " {$retryAfter}초 후 재시도하세요." : ''),
            429
        );
    }
}
