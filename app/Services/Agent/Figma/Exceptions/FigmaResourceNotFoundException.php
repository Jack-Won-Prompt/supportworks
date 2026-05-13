<?php

namespace App\Services\Agent\Figma\Exceptions;

class FigmaResourceNotFoundException extends FigmaApiException
{
    public function __construct(string $endpoint = '')
    {
        parent::__construct(
            'Figma 리소스를 찾을 수 없습니다.' . ($endpoint ? " ({$endpoint})" : ''),
            404
        );
    }
}
