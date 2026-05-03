<?php

namespace App\Enums\Agent;

enum StageStatus: string
{
    case LOCKED           = 'locked';
    case IN_PROGRESS      = 'in_progress';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED         = 'approved';

    public function label(): string
    {
        return match($this) {
            self::LOCKED           => '잠김',
            self::IN_PROGRESS      => '진행 중',
            self::PENDING_APPROVAL => '승인 대기',
            self::APPROVED         => '승인 완료',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::IN_PROGRESS;
    }
}
