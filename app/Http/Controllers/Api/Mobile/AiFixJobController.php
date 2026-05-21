<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AiFixJob;
use App\Services\AiFix\AiFixOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 관리자(role=admin) 권한 웹 사용자가 모바일 앱에서 호출하는 AI Fix Job API.
 *
 * Base URL: /api/mobile/ai-fix-jobs
 */
class AiFixJobController extends Controller
{
    public function __construct(
        private readonly AiFixOrchestrator $orchestrator,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $status = $request->query('status');                  // 단일 또는 'awaiting,blocked' 같은 콤마 구분
        $query  = AiFixJob::query()->with('systemErrorLog')->latest();

        if ($status === 'awaiting_approval' || $status === null) {
            $query->where('status', AiFixJob::STATUS_AWAITING_APPROVAL);
        } elseif ($status === 'active') {
            $query->active();
        } elseif ($status === 'all') {
            // no filter
        } else {
            $query->where('status', $status);
        }

        $paginator = $query->paginate(20);

        return response()->json([
            'data' => collect($paginator->items())->map(fn($j) => $this->resource($j)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, AiFixJob $aiFixJob): JsonResponse
    {
        $this->authorizeAdmin($request);
        return response()->json($this->resource($aiFixJob->load('systemErrorLog'), full: true));
    }

    public function approve(Request $request, AiFixJob $aiFixJob): JsonResponse
    {
        $this->authorizeAdmin($request);
        try {
            $this->orchestrator->approve($aiFixJob, $request->user());
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        return response()->json($this->resource($aiFixJob->fresh()->load('systemErrorLog'), full: true));
    }

    public function reject(Request $request, AiFixJob $aiFixJob): JsonResponse
    {
        $this->authorizeAdmin($request);
        $reason = (string) $request->input('reason', '');
        try {
            $this->orchestrator->reject($aiFixJob, $request->user(), $reason ?: null);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
        return response()->json($this->resource($aiFixJob->fresh()->load('systemErrorLog'), full: true));
    }

    // ── 헬퍼 ────────────────────────────────────────────────────────────────

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403, 'Admin only');
    }

    private function resource(AiFixJob $j, bool $full = false): array
    {
        $err = $j->systemErrorLog;
        $base = [
            'id'                   => $j->id,
            'status'               => $j->status,
            'decision'             => $j->decision,
            'red_signals'          => $j->red_signals,
            'yellow_signals'       => $j->yellow_signals,
            'decision_reason'      => $j->decision_reason,
            'blocked_path'         => $j->blocked_path,
            'branch_name'          => $j->branch_name,
            'proposed_fix_summary' => $j->proposed_fix_summary,
            'changed_files'        => $j->changed_files,
            'system_error_log_id'  => $j->system_error_log_id,
            'created_at'           => optional($j->created_at)->toIso8601String(),
            'escalated_at'         => optional($j->escalated_at)->toIso8601String(),
            'approved_at'          => optional($j->approved_at)->toIso8601String(),
            'finished_at'          => optional($j->finished_at)->toIso8601String(),
            'is_terminal'          => $j->isTerminal(),
        ];

        if ($full) {
            $base['test_result']     = $j->test_result;
            $base['deployed_commit'] = $j->deployed_commit;
            $base['deployed_at']     = optional($j->deployed_at)->toIso8601String();
            $base['error_message']   = $j->error_message;
            $base['error_log']       = $err ? [
                'id'        => $err->id,
                'level'     => $err->level,
                'exception' => $err->exception,
                'message'   => $err->message,
                'file'      => $err->file,
                'line'      => $err->line,
                'created_at'=> optional($err->created_at)->toIso8601String(),
            ] : null;
        }

        return $base;
    }
}