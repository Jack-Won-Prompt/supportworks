<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\ItemAttachment;
use App\Models\ItemChangeHistory;
use App\Models\PlanApplication;
use App\Models\Project;
use App\Models\ProjectFeatureSuggestion;
use App\Models\Requirement;
use App\Models\SubTask;
use App\Services\Analysis\RequirementValidationService;
use App\Services\PlanApplication\MarkdownInserter;
use App\Services\PlanApplication\PlanApplicationService;
use App\Services\PlanApplication\Templates\TemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RequirementController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $query = $project->requirements()->with(['assignee', 'reporter', 'duplicateOf'])->withCount('attachments')->latest();

        if ($v = $request->get('status'))           $query->where('status', $v);
        if ($v = $request->get('priority'))         $query->where('priority', $v);
        if ($v = $request->get('category'))         $query->where('category', $v);
        if ($v = $request->get('assignee'))         $query->where('assignee_id', $v);
        if ($v = $request->get('requirement_type')) $query->where('requirement_type', $v);
        if ($v = $request->get('approval_status'))  $query->where('approval_status', $v);
        if ($v = $request->get('search'))           $query->where('title', 'like', "%{$v}%");
        if ($request->boolean('out_of_scope'))      $query->where('out_of_scope', true);
        if ($request->boolean('has_duplicate'))     $query->whereNotNull('duplicate_of_id');

        $requirements = $query->paginate(25)->withQueryString();
        $members      = $project->members()->get();
        $ganttReqIds  = SubTask::where('project_id', $project->id)
                            ->whereNotNull('requirement_id')
                            ->pluck('requirement_id')
                            ->all();
        $ganttBlockedReqIds = SubTask::where('project_id', $project->id)
                            ->whereNotNull('requirement_id')
                            ->whereIn('status', ['in_progress', 'completed', 'blocked'])
                            ->pluck('requirement_id')
                            ->all();

        return view('requirements.index', compact('project', 'requirements', 'members', 'ganttReqIds', 'ganttBlockedReqIds'));
    }

    /**
     * 신규 요구사항 등록 전 AI 범위/중복 검증 (AJAX).
     * 클라이언트는 결과를 사용자에게 보여주고, 사용자가 "그래도 등록" 선택 시
     * 결과 필드(out_of_scope, scope_reason, duplicate_of_id, duplicate_reason)를
     * hidden field로 store에 함께 POST한다.
     */
    public function validateBeforeStore(Request $request, Project $project, RequirementValidationService $service): JsonResponse
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $result = $service->validate($project, $data['title'], (string) ($data['description'] ?? ''));

        return response()->json($result);
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'priority'         => 'required|in:critical,high,medium,low',
            'category'         => 'required|in:functional,non_functional,ui,data,security',
            'assignee_id'      => 'nullable|integer|exists:users,id',
            'tags'             => 'nullable|string',
            'requirement_type' => 'sometimes|in:initial,additional,change',
            'source_type'      => 'nullable|string|max:50',
            'source_ref'       => 'nullable|string|max:255',
            'attachments'      => 'nullable|array',
            'attachments.*'    => 'file|max:10240',
            // AI 검증 결과 (사용자가 "그래도 등록" 시 함께 전달)
            'out_of_scope'     => 'nullable|boolean',
            'scope_reason'     => 'nullable|string|max:800',
            'duplicate_of_id'  => 'nullable|integer',
            'duplicate_reason' => 'nullable|string|max:800',
        ]);

        // 태그: 쉼표 구분 문자열 → 배열
        $tagInput = $validated['tags'] ?? '';
        $tags = $tagInput
            ? array_values(array_filter(array_map('trim', explode(',', $tagInput))))
            : null;

        // 중복 ID가 같은 프로젝트의 유효한 요구사항인지 확인 (위조 방지)
        $duplicateOfId = $validated['duplicate_of_id'] ?? null;
        if ($duplicateOfId) {
            $valid = Requirement::where('id', $duplicateOfId)
                ->where('project_id', $project->id)
                ->exists();
            if (!$valid) $duplicateOfId = null;
        }

        $req = $project->requirements()->create([
            'title'            => $validated['title'],
            'description'      => $validated['description'] ?? null,
            'priority'         => $validated['priority'],
            'category'         => $validated['category'],
            'assignee_id'      => $validated['assignee_id'] ?? null,
            'tags'             => $tags,
            'requirement_type' => $validated['requirement_type'] ?? 'initial',
            'source_type'      => $validated['source_type'] ?? 'manual',
            'source_ref'       => $validated['source_ref'] ?? null,
            'reporter_id'      => auth()->id(),
            'status'           => 'draft',
            'out_of_scope'     => (bool) ($validated['out_of_scope'] ?? false),
            'scope_reason'     => ($validated['out_of_scope'] ?? false) ? ($validated['scope_reason'] ?? null) : null,
            'duplicate_of_id'  => $duplicateOfId,
            'duplicate_reason' => $duplicateOfId ? ($validated['duplicate_reason'] ?? null) : null,
        ]);

        if ($request->hasFile('attachments')) {
            $now = now();
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('requirements/' . $req->id, 'public');
                $req->attachments()->create([
                    'filename'    => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'mime_type'   => $file->getMimeType(),
                    'size'        => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                    'uploaded_at' => $now,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $req->id]);
        }

        return redirect()->route('projects.requirements.index', $project)
            ->with('success', '요구사항이 등록되었습니다.');
    }

    public function show(Request $request, Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $requirement->load([
            'assignee', 'reporter', 'approver',
            'comments.author',
            'attachments.uploader',
            'watchers.user',
            'changeHistories.changedBy',
            'planApplications.plan',
            'planApplications.appliedBy',
        ]);

        $members    = $project->members()->get();
        $isWatching = $requirement->isWatchedBy(auth()->id());
        $isManager  = auth()->user()->isAdmin()
                   || $project->getMemberRole(auth()->user()) === 'manager';
        $canDelete  = $requirement->reporter_id === auth()->id() || auth()->user()->isAdmin();
        $inGantt    = SubTask::where('requirement_id', $requirement->id)->exists();

        if ($request->expectsJson()) {
            return response()->json([
                'requirement' => [
                    'id'                 => $requirement->id,
                    'title'              => $requirement->title,
                    'description'        => $requirement->description ?? '',
                    'status'             => $requirement->status,
                    'status_label'       => $requirement->status_label,
                    'status_color'       => $requirement->status_color,
                    'priority'           => $requirement->priority,
                    'priority_label'     => $requirement->priority_label,
                    'priority_color'     => $requirement->priority_color,
                    'category'           => $requirement->category,
                    'category_label'     => $requirement->category_label,
                    'assignee_id'        => $requirement->assignee_id,
                    'assignee_name'      => $requirement->assignee?->name,
                    'reporter_name'      => $requirement->reporter?->name,
                    'tags'               => $requirement->tags ?? [],
                    'source_type'        => $requirement->source_type,
                    'ai_confidence'      => $requirement->ai_confidence,
                    'source_ref'         => $requirement->source_ref,
                    'source_session_id'  => $requirement->source_session_id,
                    'requirement_type'   => $requirement->requirement_type,
                    'approval_status'    => $requirement->approval_status,
                    'approval_label'     => $requirement->approval_label,
                    'approval_color'     => $requirement->approval_color,
                    'approver_name'      => $requirement->approver?->name,
                    'approved_at'        => $requirement->approved_at?->format('Y-m-d'),
                    'applied_to_plan'    => $requirement->applied_to_plan,
                    'created_at'         => $requirement->created_at->format('Y-m-d H:i'),
                    'updated_at'         => $requirement->updated_at->format('Y-m-d H:i'),
                ],
                'comments' => $requirement->comments->map(fn($c) => [
                    'id'          => $c->id,
                    'author_name' => $c->author?->name,
                    'content'     => $c->content,
                    'created_at'  => $c->created_at->format('Y-m-d H:i'),
                ]),
                'histories' => $requirement->changeHistories->map(fn($h) => [
                    'field_name' => $h->field_name,
                    'old_value'  => $h->old_value,
                    'new_value'  => $h->new_value,
                    'changed_by' => $h->changedBy?->name,
                    'changed_at' => $h->changed_at->format('Y-m-d H:i'),
                ]),
                'plan_applications' => $requirement->planApplications->map(fn($a) => [
                    'id'         => $a->id,
                    'plan_id'    => $a->plan_id,
                    'plan_title' => $a->plan?->title ?? '기획서 #' . $a->plan_id,
                    'plan_url'   => route('projects.planning.show', [$project, $a->plan_id]),
                    'applied_by' => $a->appliedBy?->name,
                    'applied_at' => $a->applied_at?->format('Y-m-d H:i'),
                ]),
                'attachments' => $requirement->attachments->map(fn($att) => [
                    'id'           => $att->id,
                    'filename'     => $att->filename,
                    'mime_type'    => $att->mime_type,
                    'size_human'   => $att->size_human,
                    'uploaded_by'  => $att->uploader?->name,
                    'uploaded_at'  => $att->uploaded_at?->format('Y-m-d H:i'),
                    'download_url' => route('projects.requirements.attachments.download', [$project, $requirement, $att]),
                ]),
                'members'    => $members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]),
                'is_watching'=> $isWatching,
                'is_manager' => $isManager,
                'can_delete' => $canDelete,
                'in_gantt'   => $inGantt,
                'urls' => [
                    'update'      => route('projects.requirements.update',        [$project, $requirement]),
                    'comment'     => route('projects.requirements.comments.store',[$project, $requirement]),
                    'watch'       => route('projects.requirements.watch',         [$project, $requirement]),
                    'approve'     => route('projects.requirements.approve',       [$project, $requirement]),
                    'destroy'     => route('projects.requirements.destroy',       [$project, $requirement]),
                    'show'        => route('projects.requirements.show',          [$project, $requirement]),
                    'revert_base' => url("projects/{$project->id}/plan-applications"),
                ],
            ]);
        }

        return view('requirements.show', compact(
            'project', 'requirement', 'members', 'isWatching', 'isManager', 'inGantt'
        ));
    }

    public function update(Request $request, Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $validated = $request->validate([
            'title'            => 'sometimes|string|max:255',
            'description'      => 'nullable|string',
            'status'           => 'sometimes|in:draft,analyzing,confirmed,changed,deferred,cancelled',
            'priority'         => 'sometimes|in:critical,high,medium,low',
            'category'         => 'sometimes|in:functional,non_functional,ui,data,security',
            'assignee_id'      => 'nullable|integer|exists:users,id',
            'tags'             => 'nullable|string',
            'requirement_type' => 'sometimes|in:initial,additional,change',
            'source_ref'       => 'nullable|string|max:255',
        ]);

        if (array_key_exists('tags', $validated)) {
            $tagInput = $validated['tags'] ?? '';
            $validated['tags'] = $tagInput
                ? array_values(array_filter(array_map('trim', explode(',', $tagInput))))
                : null;
        }

        // 변경 이력 기록
        $trackable = ['title', 'status', 'priority', 'category', 'assignee_id', 'requirement_type'];
        $histories = [];
        foreach ($trackable as $field) {
            if (!array_key_exists($field, $validated)) continue;
            $old = (string)($requirement->$field ?? '');
            $new = (string)($validated[$field] ?? '');
            if ($old !== $new) {
                $histories[] = [
                    'item_type'     => Requirement::class,
                    'item_id'       => $requirement->id,
                    'changed_by_id' => auth()->id(),
                    'changed_at'    => now(),
                    'field_name'    => $field,
                    'old_value'     => $old,
                    'new_value'     => $new,
                ];
            }
        }

        $requirement->update($validated);
        if (!empty($histories)) ItemChangeHistory::insert($histories);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '수정되었습니다.');
    }

    public function destroy(Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $user = auth()->user();
        if ($requirement->reporter_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        // 진행중·완료·블로킹 Task가 연결되어 있으면 삭제 불가
        $blockingTask = SubTask::where('requirement_id', $requirement->id)
            ->whereIn('status', ['in_progress', 'completed', 'blocked'])
            ->first();

        if ($blockingTask) {
            $msg = '진행중·완료·블로킹 상태의 일정 Task가 연결되어 있어 삭제할 수 없습니다.';
            if (request()->expectsJson()) {
                return response()->json(['ok' => false, 'error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $this->cascadeDeleteRequirement($requirement);

        ItemChangeHistory::create([
            'item_type'     => Requirement::class,
            'item_id'       => $requirement->id,
            'changed_by_id' => $user->id,
            'changed_at'    => now(),
            'field_name'    => 'deleted',
            'old_value'     => $requirement->title,
            'new_value'     => null,
        ]);

        $requirement->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.requirements.index', $project)
            ->with('success', '요구사항이 삭제되었습니다.');
    }

    public function bulkDestroy(Request $request, Project $project)
    {
        $this->authorizeProject($project);
        $request->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer']);

        $user = auth()->user();
        $requirements = $project->requirements()->whereIn('id', $request->input('ids'))->get();

        $deleted = [];
        $skipped = [];

        foreach ($requirements as $requirement) {
            if ($requirement->reporter_id !== $user->id && !$user->isAdmin()) {
                $skipped[] = ['id' => $requirement->id, 'reason' => '삭제 권한이 없습니다.'];
                continue;
            }

            $blockingTask = SubTask::where('requirement_id', $requirement->id)
                ->whereIn('status', ['in_progress', 'completed', 'blocked'])
                ->first();

            if ($blockingTask) {
                $skipped[] = ['id' => $requirement->id, 'reason' => '진행중·완료 일정 Task가 연결되어 있습니다.'];
                continue;
            }

            $this->cascadeDeleteRequirement($requirement);

            ItemChangeHistory::create([
                'item_type'     => Requirement::class,
                'item_id'       => $requirement->id,
                'changed_by_id' => $user->id,
                'changed_at'    => now(),
                'field_name'    => 'deleted',
                'old_value'     => $requirement->title,
                'new_value'     => null,
            ]);

            $requirement->delete();
            $deleted[] = $requirement->id;
        }

        return response()->json([
            'ok'      => true,
            'deleted' => count($deleted),
            'skipped' => $skipped,
        ]);
    }

    public function approve(Request $request, Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $user = auth()->user();
        if (!$user->isAdmin() && $project->getMemberRole($user) !== 'manager') {
            abort(403, '승인 권한이 없습니다.');
        }

        $request->validate(['approval_status' => 'required|in:reviewing,approved,rejected,returned']);

        $old = $requirement->approval_status;
        $newStatus = $request->approval_status;

        $requirement->update([
            'approval_status' => $newStatus,
            'approved_by_id'  => $newStatus === 'approved' ? $user->id : $requirement->approved_by_id,
            'approved_at'     => $newStatus === 'approved' ? now() : $requirement->approved_at,
        ]);

        ItemChangeHistory::create([
            'item_type'     => Requirement::class,
            'item_id'       => $requirement->id,
            'changed_by_id' => $user->id,
            'changed_at'    => now(),
            'field_name'    => 'approval_status',
            'old_value'     => $old,
            'new_value'     => $newStatus,
        ]);

        return response()->json(['ok' => true, 'label' => Requirement::APPROVAL_LABELS[$newStatus] ?? $newStatus]);
    }

    public function storeComment(Request $request, Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $request->validate(['content' => 'required|string|max:5000']);

        $comment = $requirement->comments()->create([
            'author_id' => auth()->id(),
            'content'   => $request->content,
        ]);

        $comment->load('author');

        return response()->json([
            'ok'      => true,
            'comment' => [
                'id'          => $comment->id,
                'content'     => nl2br(e($comment->content)),
                'author_name' => $comment->author->name,
                'created_at'  => $comment->created_at->format('Y-m-d H:i'),
                'is_mine'     => true,
            ],
        ]);
    }

    public function toggleWatcher(Project $project, Requirement $requirement)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);

        $userId  = auth()->id();
        $watcher = $requirement->watchers()->where('user_id', $userId)->first();

        if ($watcher) {
            $watcher->delete();
            $watching = false;
        } else {
            $requirement->watchers()->create([
                'user_id'       => $userId,
                'subscribed_at' => now(),
            ]);
            $watching = true;
        }

        return response()->json(['ok' => true, 'watching' => $watching]);
    }

    public function export(Project $project): StreamedResponse
    {
        $this->authorizeProject($project);

        $requirements = $project->requirements()->with(['assignee', 'reporter'])->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="requirements_' . $project->id . '.csv"',
        ];

        return response()->stream(function () use ($requirements) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, ['ID', '제목', '카테고리', '상태', '우선순위', '담당자', '등록자', '등록일']);
            foreach ($requirements as $req) {
                fputcsv($out, [
                    $req->id,
                    $req->title,
                    Requirement::CATEGORY_LABELS[$req->category]  ?? $req->category,
                    Requirement::STATUS_LABELS[$req->status]       ?? $req->status,
                    Requirement::PRIORITY_LABELS[$req->priority]   ?? $req->priority,
                    $req->assignee?->name ?? '-',
                    $req->reporter?->name ?? '-',
                    $req->created_at->format('Y-m-d'),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function cascadeDeleteRequirement(Requirement $requirement): void
    {
        $service = new PlanApplicationService(new MarkdownInserter(), new TemplateRegistry());

        // 1. 기획서 반영(PlanApplication) 취소 — 블로킹 재검사 없이 강제 취소
        PlanApplication::where('requirement_id', $requirement->id)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn($app) => $service->forceRevert($app->id));

        // 2. 웍스 기능 추천 반영 취소 (PlanApplication 없이 직접 삽입된 내용 제거)
        ProjectFeatureSuggestion::where('requirement_id', $requirement->id)
            ->whereNull('deleted_at')
            ->get()
            ->each(fn($suggestion) => $service->revertFeatureSuggestion($suggestion));
    }

    /** GET ai-context: 분석에 필요한 기존 요구사항 + 기획서 목록 반환 */
    public function aiContext(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $requirements = $project->requirements()
            ->select('id', 'title', 'description', 'category', 'priority')
            ->latest()
            ->get()
            ->map(fn($r) => [
                'title'       => $r->title,
                'description' => $r->description ?? '',
                'category'    => $r->category,
                'priority'    => $r->priority,
            ]);

        $plans = $project->planningDocs()
            ->select('id', 'title', 'content')
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id'      => $p->id,
                'title'   => $p->title,
                'content' => mb_substr($p->content ?? '', 0, 4000),
            ]);

        return response()->json([
            'requirements' => $requirements,
            'plans'        => $plans,
        ]);
    }

    public function analyzeAttachment(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);
        $request->validate(['file' => 'required|file|max:20480']);

        $settings  = AiSetting::current();
        $file      = $request->file('file');
        $mime      = $file->getMimeType() ?? 'application/octet-stream';
        $name      = $file->getClientOriginalName();
        $isImage   = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
        $isPdf     = $mime === 'application/pdf';
        $lastError = '웍스 API 키가 설정되지 않았습니다.';

        // 클라이언트가 전달한 컨텍스트 (기존 요구사항 + 기획서)
        $contextJson = $request->input('context', '{}');
        $context     = json_decode($contextJson, true) ?? [];
        $prompt      = $this->requirementExtractionPrompt($context);

        // ── 1차: Anthropic ────────────────────────────────────────────
        $anthropicKey = $settings->anthropicKey();
        if ($anthropicKey) {
            $content = [];
            if ($isImage) {
                $b64       = base64_encode(file_get_contents($file->getRealPath()));
                $content[] = ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $mime, 'data' => $b64]];
                $content[] = ['type' => 'text',  'text'   => "파일명: {$name}"];
            } elseif ($isPdf) {
                $b64       = base64_encode(file_get_contents($file->getRealPath()));
                $content[] = ['type' => 'document', 'source' => ['type' => 'base64', 'media_type' => 'application/pdf', 'data' => $b64]];
                $content[] = ['type' => 'text', 'text' => "파일명: {$name}"];
            } else {
                $raw       = @file_get_contents($file->getRealPath());
                $textBody  = $raw !== false ? mb_convert_encoding(mb_substr($raw, 0, 40000), 'UTF-8', 'auto') : '';
                $content[] = ['type' => 'text', 'text' => "파일명: {$name}\n\n내용:\n{$textBody}"];
            }
            $content[] = ['type' => 'text', 'text' => $prompt];

            $res = Http::withOptions(['verify' => false])
                ->withHeaders(['x-api-key' => $anthropicKey, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json'])
                ->timeout(120)
                ->post('https://api.anthropic.com/v1/messages', [
                    'model'      => 'claude-sonnet-4-6',
                    'max_tokens' => 4000,
                    'messages'   => [['role' => 'user', 'content' => $content]],
                ]);

            if ($res->successful()) {
                return $this->parseRequirementsJson($res->json('content.0.text') ?? '');
            }
            $lastError = $res->json('error.message') ?? $res->body();
        }

        // ── 2차: OpenAI 폴백 ─────────────────────────────────────────
        $openaiKey = $settings->openaiKey();
        if (!$openaiKey) {
            return response()->json(['ok' => false, 'error' => "웍스 분석 오류: {$lastError}"], 422);
        }

        if ($isImage) {
            $b64           = base64_encode(file_get_contents($file->getRealPath()));
            $openaiContent = [
                ['type' => 'text',      'text'      => "파일명: {$name}\n\n{$prompt}"],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$b64}"]],
            ];
        } else {
            $raw           = @file_get_contents($file->getRealPath());
            $textBody      = $raw !== false ? mb_convert_encoding(mb_substr($raw, 0, 40000), 'UTF-8', 'auto') : '';
            $openaiContent = "파일명: {$name}\n\n내용:\n{$textBody}\n\n{$prompt}";
        }

        $res2 = Http::withOptions(['verify' => false])
            ->withToken($openaiKey)
            ->timeout(120)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model'                 => config('services.openai.model', 'gpt-4o'),
                'max_completion_tokens' => 4000,
                'messages'   => [['role' => 'user', 'content' => $openaiContent]],
            ]);

        if (!$res2->successful()) {
            $err = $res2->json('error.message') ?? $res2->body();
            return response()->json(['ok' => false, 'error' => "웍스 분석 오류 (OpenAI): {$err}"], 500);
        }

        return $this->parseRequirementsJson($res2->json('choices.0.message.content') ?? '');
    }

    private function requirementExtractionPrompt(array $context = []): string
    {
        $sections = [];

        // 기획서 섹션 (내용이 있는 경우만)
        $plans = $context['plans'] ?? [];
        if (!empty($plans)) {
            $planText = implode("\n\n", array_map(
                fn($p) => "### {$p['title']}\n" . trim($p['content'] ?? ''),
                $plans
            ));
            $sections[] = "## 기획서 내용\n{$planText}";
        }

        // 기존 요구사항 섹션
        $reqs = $context['requirements'] ?? [];
        if (!empty($reqs)) {
            $lines = array_map(
                fn($r) => "- [{$r['priority']}][{$r['category']}] {$r['title']}"
                        . ($r['description'] ? ": {$r['description']}" : ''),
                $reqs
            );
            $sections[] = "## 기존 요구사항 목록 (" . count($reqs) . "개)\n" . implode("\n", $lines);
        }

        $contextBlock    = !empty($sections) ? implode("\n\n", $sections) . "\n\n---\n\n" : '';
        $duplicationRule = !empty($reqs)
            ? "- **기존 요구사항과 중복되지 않는** 새로운 기능만 추천\n"
            : '';

        return "{$contextBlock}" .
"위 첨부 파일의 내용을 분석하여, 이 프로젝트에 추가로 필요한 요구기능을 추천해주세요.

**반드시 한국어로 응답해주세요.**

응답 규칙:
- title, description 은 반드시 한국어로 작성
{$duplicationRule}" .
"- 기획서·파일·기존 요구사항을 종합 검토하여 실질적으로 필요한 기능 추천
- priority: high=핵심 기능, medium=중요 기능, low=선택 기능
- category: functional=기능, non_functional=비기능, ui=UI/UX, data=데이터, security=보안
- 최소 3개, 최대 15개

다음 JSON 배열 형식으로만 응답 (마크다운 코드블록·설명 텍스트 없이 JSON만):
[{\"title\":\"요구사항 제목\",\"description\":\"상세 설명\",\"priority\":\"high|medium|low\",\"category\":\"functional|non_functional|ui|data|security\"}]";
    }

    private function parseRequirementsJson(string $text): JsonResponse
    {
        preg_match('/\[[\s\S]*\]/u', $text, $m);
        $requirements = isset($m[0]) ? (json_decode($m[0], true) ?? []) : [];
        return response()->json(['ok' => true, 'requirements' => $requirements]);
    }

    public function downloadAttachment(Project $project, Requirement $requirement, ItemAttachment $attachment)
    {
        $this->authorizeProject($project);
        abort_if($requirement->project_id !== $project->id, 404);
        abort_if($attachment->item_type !== Requirement::class || $attachment->item_id !== $requirement->id, 404);

        abort_unless(Storage::disk('public')->exists($attachment->file_path), 404);

        $path = Storage::disk('public')->path($attachment->file_path);
        return response()->file($path, [
            'Content-Type'        => $attachment->mime_type ?? 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . $attachment->filename . '"',
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
