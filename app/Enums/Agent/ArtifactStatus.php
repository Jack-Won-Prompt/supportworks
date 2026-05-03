<?php

namespace App\Enums\Agent;

enum ArtifactStatus: string
{
    case DRAFT            = 'draft';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED         = 'approved';
    case REJECTED         = 'rejected';

    public function label(): string
    {
        return match($this) {
            self::DRAFT            => '초안',
            self::PENDING_APPROVAL => '승인 대기',
            self::APPROVED         => '승인 완료',
            self::REJECTED         => '반려',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED]);
    }
}
