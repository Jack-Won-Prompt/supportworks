<?php

namespace App\Services\Agent\Figma\Exceptions;

class FigmaTokenNotConfiguredException extends FigmaApiException
{
    public function __construct()
    {
        parent::__construct('Figma Personal Access Token이 설정되지 않았습니다. 웍스 Agent 설정에서 토큰을 등록해 주세요.', 0);
    }
}
