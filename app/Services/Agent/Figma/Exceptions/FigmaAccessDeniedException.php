<?php

namespace App\Services\Agent\Figma\Exceptions;

class FigmaAccessDeniedException extends FigmaApiException
{
    public function __construct(string $endpoint = '')
    {
        parent::__construct(
            'Figma 리소스에 접근 권한이 없습니다.' . ($endpoint ? " ({$endpoint})" : ''),
            403
        );
    }
}
