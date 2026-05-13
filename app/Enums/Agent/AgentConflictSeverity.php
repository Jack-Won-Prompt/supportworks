<?php

namespace App\Enums\Agent;

enum AgentConflictSeverity: string
{
    case LOW      = 'low';
    case MEDIUM   = 'medium';
    case HIGH     = 'high';
    case CRITICAL = 'critical';

    public function label(): string
    {
        return match ($this) {
            self::LOW      => '낮음',
            self::MEDIUM   => '중간',
            self::HIGH     => '높음',
            self::CRITICAL => '치명적',
        };
    }

    /** 해당 심각도에서 사용자 명시 결정이 필수인지. */
    public function blocksConfirmation(): bool
    {
        return in_array($this, [self::HIGH, self::CRITICAL], true);
    }
}
