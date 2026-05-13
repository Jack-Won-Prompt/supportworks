<?php

namespace App\Enums\Agent;

enum AgentFeedbackType: string
{
    case OK         = 'ok';
    case ISSUE      = 'issue';
    case TEXT       = 'text';
    case SCREENSHOT = 'screenshot';

    public function label(): string
    {
        return match ($this) {
            self::OK         => '정상',
            self::ISSUE      => '문제 있음',
            self::TEXT       => '텍스트 피드백',
            self::SCREENSHOT => '스크린샷',
        };
    }

    public function requiresRevision(): bool
    {
        return in_array($this, [self::ISSUE, self::TEXT, self::SCREENSHOT], true);
    }
}
