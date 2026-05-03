<?php

namespace App\Http\Controllers;

use App\Enums\Agent\StageStatus;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiAgentApprovalController extends Controller
{
    public function __construct(private readonly ApprovalService $approvalService) {}

    // ─────────────────────────────────────────────────────────────────────
    // 승인 요청 생성
    // ─────────────────────────────────────────────────────────────────────

    public function store(Project $project, Request $request): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'type'      => 'required|in:stage,artifact',
            'target_id' => 'required|integer',
            'comment'   => 'nullable|string|max:1000',
        ]);

        try {
            if ($validated['type'] === 'stage') {
                $stage = AiAgentProjectStage::where('project_id', $project->id)
                    ->findOrFail($validated['target_id']);
                $gate = $this->approvalService->requestStageApproval(
                    $stage,
                    auth()->id(),
                    $validated['comment'] ?? null
                );
            } else {
                $artifact = AiAgentArtifact::where('project_id', $project->id)
                    ->findOrFail($validated['target_id']);
                $gate = $this->approvalService->requestArtifactApproval(
                    $artifact,
                    auth()->id(),
                    $validated['comment'] ?? null
                );
            }

            return response()->json([
                'success' => true,
                'gate'    => $this->gateToArray($gate->fresh(['requestedBy', 'reviewedBy'])),
                'message' => '승인 요청이 완료되었습니다.',
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // 승인
    // ─────────────────────────────────────────────────────────────────────

    public function approve(Project $project, AiAgentApprovalGate $gate, Request $request): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeReview($project);
        abort_if($gate->project_id !== $project->id, 403);

        $validated = $request->validate(['comment' => 'nullable|string|max:1000']);

        try {
            $this->approvalService->approve($gate, auth()->id(), $validated['comment'] ?? null);

            return response()->json([
                'success' => true,
                'gate'    => $this->gateToArray($gate->fresh(['requestedBy', 'reviewedBy'])),
                'message' => '승인 처리가 완료되었습니다.',
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // 반려
    // ─────────────────────────────────────────────────────────────────────

    public function reject(Project $project, AiAgentApprovalGate $gate, Request $request): JsonResponse
    {
        $this->authorizeProject($project);
        $this->authorizeReview($project);
        abort_if($gate->project_id !== $project->id, 403);

        $validated = $request->validate([
            'comment' => 'required|string|min:1|max:1000',
        ]);

        try {
            $this->approvalService->reject($gate, auth()->id(), $validated['comment']);

            return response()->json([
                'success' => true,
                'gate'    => $this->gateToArray($gate->fresh(['requestedBy', 'reviewedBy'])),
                'message' => '반려 처리가 완료되었습니다.',
            ]);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // 승인 요청 취소
    // ─────────────────────────────────────────────────────────────────────

    public function cancel(Project $project, AiAgentApprovalGate $gate): JsonResponse
    {
        $this->authorizeProject($project);
        abort_if($gate->project_id !== $project->id, 403);
        abort_if($gate->requested_by !== auth()->id() && !auth()->user()->isAdmin(), 403, '취소 권한이 없습니다.');

        if (!$gate->isPending()) {
            return response()->json(['success' => false, 'message' => '이미 처리된 승인 요청은 취소할 수 없습니다.'], 422);
        }

        if ($gate->gate_type === 'stage_completion' && $gate->stage) {
            $gate->stage->update(['status' => StageStatus::IN_PROGRESS, 'completed_at' => null]);
        }

        if ($gate->artifact_id && $gate->artifact) {
            $gate->artifact->update(['status' => 'draft']);
        }

        $gate->delete();

        return response()->json([
            'success' => true,
            'gate'    => null,
            'message' => '승인 요청이 취소되었습니다.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // 데모 페이지 (테스트용)
    // ─────────────────────────────────────────────────────────────────────

    public function demo(Project $project): View
    {
        $this->authorizeProject($project);

        $canReview = $this->isReviewer($project);
        $canRequest = true;

        return view('ai-agent.approval-demo', compact('project', 'canReview', 'canRequest'));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        if (auth()->user()->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', auth()->id())->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }

    private function authorizeReview(Project $project): void
    {
        abort_unless($this->isReviewer($project), 403, '승인/반려 권한이 없습니다.');
    }

    private function isReviewer(Project $project): bool
    {
        if (auth()->user()->isAdmin()) return true;
        return ProjectMember::where('project_id', $project->id)
            ->where('user_id', auth()->id())
            ->where('role', 'manager')
            ->exists();
    }

    private function gateToArray(AiAgentApprovalGate $gate): array
    {
        return [
            'id'              => $gate->id,
            'status'          => $gate->status->value,
            'gate_type'       => $gate->gate_type,
            'requested_by_id' => $gate->requested_by,
            'requested_by'    => $gate->requestedBy?->name ?? '알 수 없음',
            'requested_at'    => $gate->requested_at?->format('Y.m.d H:i'),
            'reviewed_by'     => $gate->reviewedBy?->name,
            'reviewed_at'     => $gate->reviewed_at?->format('Y.m.d H:i'),
            'request_comment' => $gate->request_comment,
            'review_comment'  => $gate->review_comment,
        ];
    }
}
