<?php

namespace App\Services\Agent;

use App\Enums\Agent\ApprovalStatus;
use App\Enums\Agent\StageStatus;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    /**
     * 단계 완료 승인 요청 생성.
     * 이미 대기 중인 요청이 있으면 중복 생성하지 않는다.
     */
    public function requestStageApproval(
        AiAgentProjectStage $stage,
        int                 $requestedBy,
        ?string             $comment = null
    ): AiAgentApprovalGate {
        $existing = AiAgentApprovalGate::where('stage_id', $stage->id)
            ->where('gate_type', 'stage_completion')
            ->where('status', ApprovalStatus::PENDING)
            ->first();

        if ($existing) {
            return $existing;
        }

        $gate = AiAgentApprovalGate::create([
            'project_id'      => $stage->project_id,
            'stage_id'        => $stage->id,
            'gate_type'       => 'stage_completion',
            'status'          => ApprovalStatus::PENDING,
            'requested_by'    => $requestedBy,
            'requested_at'    => now(),
            'request_comment' => $comment,
        ]);

        $stage->requestApproval();

        return $gate;
    }

    /**
     * 산출물 개별 승인 요청 생성.
     */
    public function requestArtifactApproval(
        AiAgentArtifact $artifact,
        int             $requestedBy,
        ?string         $comment = null
    ): AiAgentApprovalGate {
        $existing = AiAgentApprovalGate::where('artifact_id', $artifact->id)
            ->where('gate_type', 'artifact')
            ->where('status', ApprovalStatus::PENDING)
            ->first();

        if ($existing) {
            return $existing;
        }

        $artifact->update(['status' => 'pending_approval']);

        return AiAgentApprovalGate::create([
            'project_id'      => $artifact->project_id,
            'stage_id'        => $artifact->stage_id,
            'artifact_id'     => $artifact->id,
            'gate_type'       => 'artifact',
            'status'          => ApprovalStatus::PENDING,
            'requested_by'    => $requestedBy,
            'requested_at'    => now(),
            'request_comment' => $comment,
        ]);
    }

    /**
     * 승인 처리. 리뷰어 권한 확인은 컨트롤러/Policy 레이어에서 수행.
     */
    public function approve(AiAgentApprovalGate $gate, int $reviewerId, ?string $comment = null): void
    {
        if (!$gate->isPending()) {
            throw new \DomainException('이미 처리된 승인 요청입니다.');
        }

        DB::transaction(function () use ($gate, $reviewerId, $comment) {
            $gate->approve($reviewerId, $comment);

            // 단계의 모든 필수 산출물이 승인됐으면 다음 단계 잠금 해제
            if ($gate->gate_type === 'stage_completion' && $gate->stage) {
                $this->unlockNextStage($gate->stage);
            }
        });
    }

    /**
     * 반려 처리.
     */
    public function reject(AiAgentApprovalGate $gate, int $reviewerId, ?string $comment = null): void
    {
        if (!$gate->isPending()) {
            throw new \DomainException('이미 처리된 승인 요청입니다.');
        }

        DB::transaction(function () use ($gate, $reviewerId, $comment) {
            $gate->reject($reviewerId, $comment);

            // 단계 반려 시 해당 Stage를 IN_PROGRESS로 복귀
            if ($gate->gate_type === 'stage_completion' && $gate->stage) {
                $gate->stage->update(['status' => StageStatus::IN_PROGRESS]);
            }
        });
    }

    /**
     * 프로젝트의 대기 중인 승인 게이트 목록.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pendingGates(int $projectId)
    {
        return AiAgentApprovalGate::where('project_id', $projectId)
            ->pending()
            ->with(['stage', 'artifact', 'requestedBy'])
            ->latest('requested_at')
            ->get();
    }

    /**
     * 특정 단계가 진행 가능한 상태인지 검증.
     * 미승인 상태에서 다음 단계 진행 시도 시 예외 발생.
     */
    public function assertStageAccessible(AiAgentProjectStage $stage): void
    {
        if ($stage->status === StageStatus::LOCKED) {
            throw new \DomainException("단계 [{$stage->type->label()}]은(는) 아직 잠금 상태입니다. 이전 단계 승인 후 진행하세요.");
        }

        if ($stage->status === StageStatus::PENDING_APPROVAL) {
            throw new \DomainException("단계 [{$stage->type->label()}]이(가) 승인 대기 중입니다.");
        }
    }

    /**
     * 승인된 단계의 다음 단계를 잠금 해제.
     */
    private function unlockNextStage(AiAgentProjectStage $approvedStage): void
    {
        $nextType = $approvedStage->type->next();

        if ($nextType === null) {
            return;
        }

        $nextStage = AiAgentProjectStage::where('project_id', $approvedStage->project_id)
            ->where('type', $nextType)
            ->first();

        $nextStage?->unlock();
    }
}
