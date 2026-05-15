<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $issues = Issue::where('project_id', $project->id)
            ->with(['reporter:id,name', 'assignee:id,name'])
            ->withCount('comments')
            ->latest()
            ->get();

        return response()->json($issues->map(fn($i) => $this->issueResource($i)));
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);

        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|in:버그,장애,문의,개선요청,기타',
            'priority'    => 'nullable|in:critical,high,medium,low',
            'severity'    => 'nullable|in:Critical,Major,Minor,Trivial',
            'environment' => 'nullable|in:운영,스테이징,개발',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $issue = Issue::create([
            'project_id'  => $project->id,
            'reporter_id' => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'category'    => $request->category ?? '기타',
            'priority'    => $request->priority ?? 'medium',
            'severity'    => $request->severity,
            'environment' => $request->environment,
            'assignee_id' => $request->assignee_id,
            'status'      => '신규',
        ]);

        $issue->load(['reporter:id,name', 'assignee:id,name']);

        return response()->json($this->issueResource($issue), 201);
    }

    public function show(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($issue->project_id !== $project->id, 404);

        $issue->load([
            'reporter:id,name',
            'assignee:id,name',
            'resolvedBy:id,name',
            'comments.user:id,name',
        ]);

        return response()->json([
            ...$this->issueResource($issue),
            'description'  => $issue->description,
            'resolution'   => $issue->resolution,
            'resolved_at'  => $issue->resolved_at,
            'resolved_by'  => $issue->resolvedBy ? ['id' => $issue->resolvedBy->id, 'name' => $issue->resolvedBy->name] : null,
            'comments'     => $issue->comments->map(fn($c) => [
                'id'         => $c->id,
                'content'    => $c->content,
                'user'       => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name] : null,
                'created_at' => $c->created_at,
            ]),
        ]);
    }

    public function update(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category'    => 'nullable|in:버그,장애,문의,개선요청,기타',
            'status'      => 'nullable|in:신규,처리중,해결,검증중,종결,보류,반려',
            'priority'    => 'nullable|in:critical,high,medium,low',
            'severity'    => 'nullable|in:Critical,Major,Minor,Trivial',
            'environment' => 'nullable|in:운영,스테이징,개발',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $issue->update($request->only([
            'title', 'description', 'category', 'status', 'priority',
            'severity', 'environment', 'assignee_id',
        ]));

        $issue->load(['reporter:id,name', 'assignee:id,name']);

        return response()->json($this->issueResource($issue));
    }

    public function destroy(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($issue->project_id !== $project->id, 404);
        abort_if($issue->reporter_id !== $request->user()->id && !$request->user()->isAdmin(), 403);

        $issue->delete();
        return response()->json(['message' => '이슈가 삭제되었습니다.']);
    }

    public function resolve(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate([
            'resolution' => 'required|string',
        ]);

        $issue->update([
            'status'         => '해결',
            'resolution'     => $request->resolution,
            'resolved_at'    => now(),
            'resolved_by_id' => $request->user()->id,
        ]);

        return response()->json(['message' => '이슈가 해결되었습니다.']);
    }

    public function storeComment(Request $request, Project $project, Issue $issue): JsonResponse
    {
        $this->authorizeProject($request->user(), $project);
        abort_if($issue->project_id !== $project->id, 404);

        $request->validate(['content' => 'required|string']);

        $comment = $issue->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        $comment->load('user:id,name');

        return response()->json([
            'id'         => $comment->id,
            'content'    => $comment->content,
            'user'       => ['id' => $comment->user->id, 'name' => $comment->user->name],
            'created_at' => $comment->created_at,
        ], 201);
    }

    private function issueResource(Issue $i): array
    {
        return [
            'id'             => $i->id,
            'title'          => $i->title,
            'category'       => $i->category,
            'status'         => $i->status,
            'status_label'   => $i->status_label,
            'priority'       => $i->priority,
            'priority_label' => $i->priority_label,
            'severity'       => $i->severity,
            'environment'    => $i->environment,
            'reporter'       => $i->reporter ? ['id' => $i->reporter->id, 'name' => $i->reporter->name] : null,
            'assignee'       => $i->assignee ? ['id' => $i->assignee->id, 'name' => $i->assignee->name] : null,
            'comments_count' => $i->comments_count ?? 0,
            'is_resolved'    => $i->isResolved(),
            'created_at'     => $i->created_at,
        ];
    }

    private function authorizeProject($user, Project $project): void
    {
        if ($user->isAdmin()) return;
        $member = $project->projectMembers()->where('user_id', $user->id)->first();
        if (!$member) abort(403, '접근 권한이 없습니다.');
    }
}