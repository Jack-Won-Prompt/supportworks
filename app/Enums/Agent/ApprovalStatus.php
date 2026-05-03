<?php

namespace App\Enums\Agent;

enum ApprovalStatus: string
{
    case PENDING  = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::PENDING  => '대기 중',
            self::APPROVED => '승인',
            self::REJECTED => '반려',
        };
    }

    public function isTerminal(): bool
    {
        return $this !== self::PENDING;
    }
}
