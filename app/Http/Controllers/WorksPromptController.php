<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorksPromptRequest;
use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\PromptHistory;
use App\Services\WorksPrompt\WorksPromptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorksPromptController extends Controller
{
    public function __construct(
        private WorksPromptService $service,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $projects = Project::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('projectMembers', fn($m) => $m->where('user_id', $user->id));
        })->orderBy('name')->get(['id', 'name', 'status']);

        return view('works-prompt.index', compact('projects'));
    }

    public function refine(WorksPromptRequest $request): JsonResponse
    {
        $result = $this->service->refine($request->user(), $request->validated());
        return response()->json($result);
    }

    /**
     * 선택된 프로젝트의 최신 PlanningDoc 메타정보(존재 여부·제목·상태)만 반환.
     * 본문은 LLM 호출에만 사용하고 프론트엔드에는 노출하지 않는다.
     */
    public function projectPlan(Request $request, int $projectId): JsonResponse
    {
        $user    = $request->user();
        $project = Project::find($projectId);

        if (!$project) {
            return response()->json(['has_plan' => false], 404);
        }

        $hasAccess = $user->isAdmin()
            || $project->created_by === $user->id
            || $project->projectMembers()->where('user_id', $user->id)->exists();

        if (!$hasAccess) {
            return response()->json(['has_plan' => false], 403);
        }

        $plan = $project->planningDocs()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->first();

        if (!$plan instanceof PlanningDoc) {
            return response()->json([
                'has_plan' => false,
                'message'  => '이 프로젝트에 기획서가 없습니다. 일반 질문은 그대로 답변됩니다.',
            ]);
        }

        return response()->json([
            'has_plan'      => true,
            'title'         => $plan->title,
            'version'       => (int) $plan->version,
            'status'        => $plan->status,
            'status_label'  => $plan->status_label,
            'updated_at'    => $plan->updated_at?->toIso8601String(),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = PromptHistory::where('user_id', $user->id)->orderByDesc('created_at');

        // 기존 mode 값('project','general','works')과 호환
        if ($request->filled('mode') && $request->mode !== 'all') {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('task_type')) {
            $query->where('task_type', $request->task_type);
        }

        $limit  = min((int)($request->limit ?? 20), 100);
        $cursor = $request->cursor;

        if ($cursor) {
            $decoded = json_decode(base64_decode($cursor), true);
            if (!empty($decoded['id'])) {
                $query->where('history_id', '<', $decoded['id']);
            }
        }

        $items   = $query->limit($limit + 1)->get();
        $hasMore = $items->count() > $limit;
        if ($hasMore) {
            $items = $items->take($limit);
        }

        $nextCursor = null;
        if ($hasMore && $items->isNotEmpty()) {
            $nextCursor = base64_encode(json_encode(['id' => $items->last()->history_id]));
        }

        return response()->json([
            'items'       => $items->map(fn($h) => [
                'history_id'         => $h->history_id,
                'user_input_preview' => Str::limit($h->original_input, 80),
                'task_type'          => $h->task_type,
                'mode'               => $h->mode,
                'project_id'         => $h->project_id,
                'elapsed_ms'         => $h->elapsed_ms,
                'created_at'         => $h->created_at?->toIso8601String(),
            ]),
            'next_cursor' => $nextCursor,
            'has_more'    => $hasMore,
        ]);
    }

    public function historyShow(Request $request, string $historyId): JsonResponse
    {
        $history = PromptHistory::where('history_id', $historyId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'history_id'      => $history->history_id,
            'mode'            => $history->mode,
            'project_id'      => $history->project_id,
            'task_type'       => $history->task_type,
            'original_input'  => $history->original_input,
            // refined_prompt 컬럼은 신 버전부터 "AI 답변" 저장 용도로 재사용됨 (컬럼명 유지).
            'answer'          => $history->refined_prompt,
            'refined_prompt'  => $history->refined_prompt, // 하위 호환
            'metadata'        => $history->metadata,
            'llm_model'       => $history->llm_model,
            'elapsed_ms'      => $history->elapsed_ms,
            'created_at'      => $history->created_at?->toIso8601String(),
        ]);
    }

    public function historyDestroy(Request $request, string $historyId): JsonResponse
    {
        $history = PromptHistory::where('history_id', $historyId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $history->delete();

        return response()->json(null, 204);
    }
}
