<?php

namespace App\Http\Controllers;

use App\Http\Requests\PromptRefineRequest;
use App\Models\Project;
use App\Models\PromptHistory;
use App\Services\PromptRefiner\ContextLoader;
use App\Services\PromptRefiner\PromptRefinerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PromptRefinerController extends Controller
{
    public function __construct(
        private PromptRefinerService $service,
        private ContextLoader $contextLoader
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();

        $projects = Project::where(function ($q) use ($user) {
            $q->where('created_by', $user->id)
              ->orWhereHas('projectMembers', fn($m) => $m->where('user_id', $user->id));
        })->orderBy('name')->get(['id', 'name', 'status']);

        return view('prompt-refiner.index', compact('projects'));
    }

    public function refine(PromptRefineRequest $request): JsonResponse
    {
        $result = $this->service->refine($request->user(), $request->validated());
        return response()->json($result);
    }

    public function projectTasks(Request $request, int $projectId): JsonResponse
    {
        $tasks = $this->contextLoader->getProjectTasks($projectId, $request->user());
        return response()->json(['items' => $tasks]);
    }

    public function history(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = PromptHistory::where('user_id', $user->id)->orderByDesc('created_at');

        if ($request->filled('mode') && $request->mode !== 'all') {
            $query->where('mode', $request->mode);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('schedule_id')) {
            $query->where('schedule_id', $request->schedule_id);
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
                'schedule_id'            => $h->schedule_id,
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
            'history_id'           => $history->history_id,
            'mode'                 => $history->mode,
            'project_id'           => $history->project_id,
            'schedule_id'              => $history->schedule_id,
            'task_type'            => $history->task_type,
            'original_input'       => $history->original_input,
            'clarification_rounds' => $history->clarification_rounds,
            'refined_prompt'       => $history->refined_prompt,
            'metadata'             => $history->metadata,
            'llm_model'            => $history->llm_model,
            'elapsed_ms'           => $history->elapsed_ms,
            'created_at'           => $history->created_at?->toIso8601String(),
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
