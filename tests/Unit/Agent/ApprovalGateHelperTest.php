<?php

namespace Tests\Unit\Agent;

use App\Enums\Agent\ApprovalStatus;
use App\Enums\Agent\StageStatus;
use App\Models\Agent\AiAgentApprovalGate;
use App\Services\Agent\ApprovalGateHelper;
use PHPUnit\Framework\TestCase;

class ApprovalGateHelperTest extends TestCase
{
    // ── Helpers ───────────────────────────────────────────────────────────

    private function gate(ApprovalStatus $status): AiAgentApprovalGate
    {
        $gate = new AiAgentApprovalGate();
        $gate->status = $status;
        return $gate;
    }

    // ── getLogicalStatus ──────────────────────────────────────────────────

    public function test_null_gate_is_not_requested(): void
    {
        $this->assertSame('NOT_REQUESTED', ApprovalGateHelper::getLogicalStatus(null));
    }

    public function test_pending_gate_is_in_review(): void
    {
        $this->assertSame('IN_REVIEW', ApprovalGateHelper::getLogicalStatus($this->gate(ApprovalStatus::PENDING)));
    }

    public function test_approved_gate_is_approved(): void
    {
        $this->assertSame('APPROVED', ApprovalGateHelper::getLogicalStatus($this->gate(ApprovalStatus::APPROVED)));
    }

    public function test_rejected_gate_is_rejected(): void
    {
        $this->assertSame('REJECTED', ApprovalGateHelper::getLogicalStatus($this->gate(ApprovalStatus::REJECTED)));
    }

    // ── getUiLabel ────────────────────────────────────────────────────────

    public function test_ui_labels_match_all_statuses(): void
    {
        $this->assertSame('요청 전',   ApprovalGateHelper::getUiLabel(null));
        $this->assertSame('승인 대기', ApprovalGateHelper::getUiLabel($this->gate(ApprovalStatus::PENDING)));
        $this->assertSame('승인됨',    ApprovalGateHelper::getUiLabel($this->gate(ApprovalStatus::APPROVED)));
        $this->assertSame('반려됨',    ApprovalGateHelper::getUiLabel($this->gate(ApprovalStatus::REJECTED)));
    }

    // ── getUiBadgeClass ───────────────────────────────────────────────────

    public function test_badge_classes_match_all_statuses(): void
    {
        $this->assertSame('none',     ApprovalGateHelper::getUiBadgeClass(null));
        $this->assertSame('pending',  ApprovalGateHelper::getUiBadgeClass($this->gate(ApprovalStatus::PENDING)));
        $this->assertSame('approved', ApprovalGateHelper::getUiBadgeClass($this->gate(ApprovalStatus::APPROVED)));
        $this->assertSame('rejected', ApprovalGateHelper::getUiBadgeClass($this->gate(ApprovalStatus::REJECTED)));
    }

    // ── getUiIcon ─────────────────────────────────────────────────────────

    public function test_ui_icons_return_non_empty_svg_strings(): void
    {
        foreach ([null, ApprovalStatus::PENDING, ApprovalStatus::APPROVED, ApprovalStatus::REJECTED] as $status) {
            $gate = $status ? $this->gate($status) : null;
            $icon = ApprovalGateHelper::getUiIcon($gate);
            $this->assertNotEmpty($icon, "Icon empty for status: " . ($status?->value ?? 'null'));
            $this->assertStringContainsString('<svg', $icon);
        }
    }

    // ── isPassable ────────────────────────────────────────────────────────

    public function test_only_approved_gate_is_passable(): void
    {
        $this->assertFalse(ApprovalGateHelper::isPassable(null));
        $this->assertFalse(ApprovalGateHelper::isPassable($this->gate(ApprovalStatus::PENDING)));
        $this->assertTrue(ApprovalGateHelper::isPassable($this->gate(ApprovalStatus::APPROVED)));
        $this->assertFalse(ApprovalGateHelper::isPassable($this->gate(ApprovalStatus::REJECTED)));
    }

    // ── getStageStatusIcon ────────────────────────────────────────────────

    public function test_stage_status_icons_return_svg_strings(): void
    {
        foreach ([null, StageStatus::LOCKED, StageStatus::IN_PROGRESS, StageStatus::PENDING_APPROVAL, StageStatus::APPROVED] as $status) {
            $icon = ApprovalGateHelper::getStageStatusIcon($status);
            $this->assertStringContainsString('<svg', $icon, "No SVG for StageStatus: " . ($status?->value ?? 'null'));
        }
    }
}
