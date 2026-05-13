<?php

namespace App\Enums\Agent;

enum AgentOutputStatus: string
{
    case PENDING     = 'pending';
    case GENERATING  = 'generating';
    case GENERATED   = 'generated';
    case REVIEWING   = 'reviewing';
    case REVISED     = 'revised';
    case CONFIRMED   = 'confirmed';
    case FAILED      = 'failed';
    case SUPERSEDED  = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::PENDING    => '생성 대기',
            self::GENERATING => '생성 중',
            self::GENERATED  => '생성 완료',
            self::REVIEWING  => '검수 중',
            self::REVISED    => '수정됨',
            self::CONFIRMED  => '확정',
            self::FAILED     => '실패',
            self::SUPERSEDED => '후속 버전으로 대체됨',
        };
    }

    public function isFinal(): bool
    {
        return $this === self::CONFIRMED;
    }
}
