<?php

namespace App\Services\WorksBuilder\Ai\Security;

use App\Models\AiSetting;

/**
 * 명세 v11 §4.1 — ApiKeyResolver.
 *
 * AiSetting (암호화 저장) → env() 폴백 순서로 키를 조회.
 */
class ApiKeyResolver
{
    public function claude(): ?string
    {
        $setting = AiSetting::first();
        return $setting?->anthropicKey();
    }

    public function openai(): ?string
    {
        $setting = AiSetting::first();
        return $setting?->openaiKey();
    }
}
