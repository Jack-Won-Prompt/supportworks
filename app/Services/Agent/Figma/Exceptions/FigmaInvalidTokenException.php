<?php

namespace App\Services\Agent\Figma\Exceptions;

class FigmaInvalidTokenException extends FigmaApiException
{
    public function __construct()
    {
        parent::__construct('Figma Personal Access Token이 유효하지 않습니다. 토큰을 확인해 주세요.', 401);
    }
}
