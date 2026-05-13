<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use App\Models\ItemChangeHistory;
use App\Models\Project;
use App\Models\Question;
use App\Models\Requirement;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IssueController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $query = $project->issues()->with(['assignee', 'reporter', 'linkedRequirement'])->latest();

        if ($v = $request->get('status'))   $query->where('status', $v);
        if ($v = $request->get('priority')) $query->where('priority', $v);
        if ($v = $request->get('category')) $query->where('category', $v);
        if ($v = $request->get('severity')) $query->where('severity', $v);
        if ($v = $request->get('assignee')) $query->where('assignee_id', $v);
        if ($v = $request->get('search'))   $query->where('title', 'like', "%{$v}%");

        $issues  = $query->paginate(25)->withQueryString();
        $members = $project->members()->get();

        // 칸반용 상태별 그룹
        $kanbanGroups = [];
        if ($request->get('view') === 'kanban') {
            $allIssues = $project->issues()->with(['assignee'])->get();
            foreach (array_keys(Issue::STATUS_LABELS) as $status) {
                $kanbanGroups[$status] = $allIssues->where('status', $status)->values();
            }
        }

        $view = $request->get('view', 'table');

        return view('issues.index', compact('project', 'issues', 'members', 'kanbanGroups', 'view'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|in:버그,장애,문의,개선요청,기타',
            'priority'    => 'required|in:critical,high,medium,low',
            'severity'    => 'nullable|in:Critical,Major,Minor,Trivial',
            'environment' => 'nullable|in:운영,스테이징,개발',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'tags'        => 'nullable|string',
            'sla_due'     => 'nullable|date',
            'linked_requirement_id' => 'nullable|integer|exists:requirements,id',
        ]);

        $tags = isset($validated['tags']) && $validated['tags']
            ? array_values(array_filter(array_map('trim', explode(',', $validated['tags']))))
            : null;

        $issue = $project->issues()->create([
            'title'                => $validated['title'],
            'description'          => $validated['description'] ?? null,
            'category'             => $validated['category'],
            'priority'             => $validated['priority'],
            'severity'             => $validated['severity'] ?? null,
            'environment'          => $validated['environment'] ?? null,
            'assignee_id'          => $validated['assignee_id'] ?? null,
            'tags'                 => $tags,
            'sla_due'              => $validated['sla_due'] ?? null,
            'linked_requirement_id'=> $validated['linked_requirement_id'] ?? null,
            'reporter_id'          => auth()->id(),
            'status'               => '신규',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $issue->id]);
        }

        return redirect()->route('projects.issues.show', [$project, $issue])
            ->with('success', '이슈가 등록되었습니다.');
    }

    public function show(Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $issue->load([
            'reporter', 'assignee', 'resolvedBy',
            'linkedRequirement',
            'convertedFromQuestion',
            'comments.author',
            'attachments.uploader',
            'watchers.user',
            'changeHistories.changedBy',
        ]);

        $members       = $project->members()->get();
        $isWatching    = $issue->isWatchedBy(auth()->id());
        $isManager     = auth()->user()->isAdmin()
                      || $project->getMemberRole(auth()->user()) === 'manager';
        $requirements  = $project->requirements()->where('status', '!=', 'cancelled')->get(['id', 'title']);

        return view('issues.show', compact(
            'project', 'issue', 'members', 'isWatching', 'isManager', 'requirements'
        ));
    }

    public function update(Request $request, Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $validated = $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'sometimes|in:버그,장애,문의,개선요청,기타',
            'status'      => 'sometimes|in:신규,처리중,해결,검증중,종결,보류,반려',
            'priority'    => 'sometimes|in:critical,high,medium,low',
            'severity'    => 'nullable|in:Critical,Major,Minor,Trivial',
            'environment' => 'nullable|in:운영,스테이징,개발',
            'assignee_id' => 'nullable|integer|exists:users,id',
            'tags'        => 'nullable|string',
            'sla_due'     => 'nullable|date',
        ]);

        if (array_key_exists('tags', $validated)) {
            $tagInput = $validated['tags'] ?? '';
            $validated['tags'] = $tagInput
                ? array_values(array_filter(array_map('trim', explode(',', $tagInput))))
                : null;
        }

        $trackable = ['title', 'status', 'priority', 'category', 'assignee_id', 'severity'];
        $histories = [];
        foreach ($trackable as $field) {
            if (!array_key_exists($field, $validated)) continue;
            $old = (string)($issue->$field ?? '');
            $new = (string)($validated[$field] ?? '');
            if ($old !== $new) {
                $histories[] = [
                    'item_type'     => Issue::class,
                    'item_id'       => $issue->id,
                    'changed_by_id' => auth()->id(),
                    'changed_at'    => now(),
                    'field_name'    => $field,
                    'old_value'     => $old,
                    'new_value'     => $new,
                ];
            }
        }

        $issue->update($validated);
        if (!empty($histories)) ItemChangeHistory::insert($histories);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '수정되었습니다.');
    }

    public function destroy(Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $user = auth()->user();
        if ($issue->reporter_id !== $user->id && !$user->isAdmin()) {
            abort(403);
        }

        $issue->delete();

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('projects.issues.index', $project)
            ->with('success', '이슈가 삭제되었습니다.');
    }

    public function resolve(Request $request, Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate([
            'resolution' => 'required|string',
            'status'     => 'required|in:해결,종결',
        ]);

        $old = $issue->status;
        $issue->update([
            'resolution'     => $request->resolution,
            'status'         => $request->status,
            'resolved_at'    => now(),
            'resolved_by_id' => auth()->id(),
        ]);

        ItemChangeHistory::create([
            'item_type'     => Issue::class,
            'item_id'       => $issue->id,
            'changed_by_id' => auth()->id(),
            'changed_at'    => now(),
            'field_name'    => 'status',
            'old_value'     => $old,
            'new_value'     => $request->status,
        ]);

        return response()->json(['ok' => true, 'status' => $issue->status]);
    }

    public function linkRequirement(Request $request, Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate(['requirement_id' => 'required|integer|exists:requirements,id']);

        $req = Requirement::findOrFail($request->requirement_id);
        abort_if($req->project_id !== $project->id, 403);

        $issue->update(['linked_requirement_id' => $req->id]);

        return response()->json([
            'ok' => true,
            'requirement' => ['id' => $req->id, 'title' => $req->title],
        ]);
    }

    public function unlinkRequirement(Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $issue->update(['linked_requirement_id' => null]);

        return response()->json(['ok' => true]);
    }

    public function storeComment(Request $request, Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate(['content' => 'required|string|max:5000']);

        $comment = $issue->comments()->create([
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

    public function toggleWatcher(Project $project, Issue $issue)
    {
        $this->authorizeProject($project);
        abort_if($issue->project_id !== $project->id, 404);

        $userId  = auth()->id();
        $watcher = $issue->watchers()->where('user_id', $userId)->first();

        if ($watcher) {
            $watcher->delete();
            $watching = false;
        } else {
            $issue->watchers()->create([
                'user_id'       => $userId,
                'subscribed_at' => now(),
            ]);
            $watching = true;
        }

        return response()->json(['ok' => true, 'watching' => $watching]);
    }

    public function convertFromQuestion(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $request->validate([
            'question_id' => 'required|integer|exists:questions,id',
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'required|in:버그,장애,문의,개선요청,기타',
            'priority'    => 'required|in:critical,high,medium,low',
        ]);

        $question = Question::findOrFail($request->question_id);
        abort_if($question->project_id !== $project->id, 403);
        abort_if($question->converted_to_issue_id !== null, 422, '이미 이슈로 전환된 Q&A입니다.');

        $issue = $project->issues()->create([
            'title'                       => $request->title,
            'description'                 => $request->description ?? $question->content,
            'category'                    => $request->category,
            'priority'                    => $request->priority,
            'reporter_id'                 => auth()->id(),
            'status'                      => '신규',
            'converted_from_question_id'  => $question->id,
        ]);

        $question->update(['converted_to_issue_id' => $issue->id]);

        return response()->json([
            'ok'       => true,
            'issue_id' => $issue->id,
            'url'      => route('projects.issues.show', [$project, $issue]),
        ]);
    }

    public function stats(Project $project)
    {
        $this->authorizeProject($project);

        $issues = $project->issues();

        $byStatus   = (clone $issues)->selectRaw('status, count(*) as cnt')->groupBy('status')->pluck('cnt', 'status');
        $byPriority = (clone $issues)->selectRaw('priority, count(*) as cnt')->groupBy('priority')->pluck('cnt', 'priority');
        $byCategory = (clone $issues)->selectRaw('category, count(*) as cnt')->groupBy('category')->pluck('cnt', 'category');
        $total      = (clone $issues)->count();
        $resolved   = (clone $issues)->whereIn('status', ['해결', '종결'])->count();
        $slaBreached= (clone $issues)->where('sla_breached', true)->count();

        return response()->json(compact('byStatus', 'byPriority', 'byCategory', 'total', 'resolved', 'slaBreached'));
    }

    public function export(Project $project): StreamedResponse
    {
        $this->authorizeProject($project);

        $issues = $project->issues()->with(['assignee', 'reporter', 'linkedRequirement'])->get();

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="issues_' . $project->id . '.csv"',
        ];

        return response()->stream(function () use ($issues) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, ['ID', '제목', '카테고리', '상태', '우선순위', '심각도', '환경', '담당자', '등록자', '등록일']);
            foreach ($issues as $issue) {
                fputcsv($out, [
                    $issue->id,
                    $issue->title,
                    $issue->category,
                    $issue->status,
                    Issue::PRIORITY_LABELS[$issue->priority] ?? $issue->priority,
                    $issue->severity ?? '-',
                    $issue->environment ?? '-',
                    $issue->assignee?->name ?? '-',
                    $issue->reporter?->name ?? '-',
                    $issue->created_at->format('Y-m-d'),
                ]);
            }
            fclose($out);
        }, 200, $headers);
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403, '접근 권한이 없습니다.');
    }
}
