<?php

namespace App\Services\Agent;

use App\Enums\Agent\ApprovalStatus;
use App\Enums\Agent\StageStatus;
use App\Models\Agent\AiAgentApprovalGate;

/**
 * Single source of truth for the spec ↔ DB approval status mapping.
 *
 * Spec logical statuses:
 *   NOT_REQUESTED  — no gate record exists (gate === null)
 *   IN_REVIEW      — DB status is PENDING (awaiting reviewer decision)
 *   APPROVED       — DB status is APPROVED
 *   REJECTED       — DB status is REJECTED
 */
final class ApprovalGateHelper
{
    public static function getLogicalStatus(?AiAgentApprovalGate $gate): string
    {
        if ($gate === null) {
            return 'NOT_REQUESTED';
        }

        return match($gate->status) {
            ApprovalStatus::PENDING  => 'IN_REVIEW',
            ApprovalStatus::APPROVED => 'APPROVED',
            ApprovalStatus::REJECTED => 'REJECTED',
        };
    }

    public static function getUiLabel(?AiAgentApprovalGate $gate): string
    {
        return match(self::getLogicalStatus($gate)) {
            'NOT_REQUESTED' => '요청 전',
            'IN_REVIEW'     => '승인 대기',
            'APPROVED'      => '승인됨',
            'REJECTED'      => '반려됨',
        };
    }

    /** Returns the CSS modifier class for .apg-badge.{class}. */
    public static function getUiBadgeClass(?AiAgentApprovalGate $gate): string
    {
        return match(self::getLogicalStatus($gate)) {
            'NOT_REQUESTED' => 'none',
            'IN_REVIEW'     => 'pending',
            'APPROVED'      => 'approved',
            'REJECTED'      => 'rejected',
        };
    }

    /** Returns an inline SVG string — render with {!! !!} in Blade. */
    public static function getUiIcon(?AiAgentApprovalGate $gate): string
    {
        return match(self::getLogicalStatus($gate)) {
            'NOT_REQUESTED' => '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>',
            'IN_REVIEW'     => '<svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            'APPROVED'      => '<svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>',
            'REJECTED'      => '<svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>',
        };
    }

    /** True only when the gate has been approved — safe to proceed to the next stage. */
    public static function isPassable(?AiAgentApprovalGate $gate): bool
    {
        return $gate?->status === ApprovalStatus::APPROVED;
    }

    /** Returns a 14×14 SVG icon for the stage-sidebar status column. */
    public static function getStageStatusIcon(?StageStatus $status): string
    {
        return match($status) {
            StageStatus::APPROVED         => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            StageStatus::PENDING_APPROVAL => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
            StageStatus::IN_PROGRESS      => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 010 1.972l-11.54 6.347a1.125 1.125 0 01-1.667-.986V5.653z"/></svg>',
            default                       => '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>',
        };
    }
}
