<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\MeetingActionItem;
use App\Models\MeetingMinute;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = MeetingMinute::companyOf($user)->with(['author', 'project', 'actionItems']);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $minutes = $query->latest('meeting_date')->paginate(20);

        return response()->json([
            'data' => $minutes->map(fn($m) => $this->minuteResource($m)),
            'meta' => [
                'current_page' => $minutes->currentPage(),
                'last_page'    => $minutes->lastPage(),
                'total'        => $minutes->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'title'        => 'required|string|max:200',
            'type'         => 'required|in:general,project',
            'project_id'   => 'nullable|exists:projects,id',
            'meeting_date' => 'required|date',
            'location'     => 'nullable|string|max:200',
            'agenda'       => 'nullable|string',
            'discussion'   => 'nullable|string',
            'decisions'    => 'nullable|string',
        ]);

        $minute = MeetingMinute::create([
            ...$validated,
            'author_id'        => $user->id,
            'company_group_id' => $user->company_group_id,
        ]);

        $minute->load(['author', 'project']);

        return response()->json($this->minuteResource($minute), 201);
    }

    public function show(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        abort_unless($meetingMinute->canBeViewedBy($request->user()), 403);

        $meetingMinute->load(['author', 'project', 'attendees.user', 'memos', 'actionItems.assignee']);

        return response()->json([
            ...$this->minuteResource($meetingMinute),
            'attendees'    => $meetingMinute->attendees->map(fn($a) => [
                'id'   => $a->id,
                'name' => $a->name ?? ($a->user ? $a->user->name : ''),
            ]),
            'memos'        => $meetingMinute->memos->map(fn($m) => [
                'id'      => $m->id,
                'content' => $m->content,
                'created_at' => $m->created_at,
            ]),
            'action_items' => $meetingMinute->actionItems->map(fn($a) => [
                'id'          => $a->id,
                'content'     => $a->content,
                'status'      => $a->status,
                'due_date'    => $a->due_date,
                'assignee'    => $a->assignee ? ['id' => $a->assignee->id, 'name' => $a->assignee->name] : null,
            ]),
        ]);
    }

    public function update(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        abort_if($meetingMinute->author_id !== $request->user()->id && !$request->user()->isAdmin(), 403);

        $validated = $request->validate([
            'title'        => 'required|string|max:200',
            'meeting_date' => 'required|date',
            'location'     => 'nullable|string|max:200',
            'agenda'       => 'nullable|string',
            'discussion'   => 'nullable|string',
            'decisions'    => 'nullable|string',
        ]);

        $meetingMinute->update($validated);
        $meetingMinute->load(['author', 'project']);

        return response()->json($this->minuteResource($meetingMinute));
    }

    public function destroy(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        abort_if($meetingMinute->author_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $meetingMinute->delete();
        return response()->json(['message' => '회의록이 삭제되었습니다.']);
    }

    public function storeActionItem(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        abort_unless($meetingMinute->canBeViewedBy($request->user()), 403);

        $request->validate([
            'content'     => 'required|string|max:500',
            'due_date'    => 'nullable|date',
            'assignee_id' => 'nullable|exists:users,id',
        ]);

        $item = $meetingMinute->actionItems()->create([
            'content'     => $request->content,
            'due_date'    => $request->due_date,
            'assignee_id' => $request->assignee_id,
            'status'      => 'pending',
        ]);

        $item->load('assignee');

        return response()->json([
            'id'       => $item->id,
            'content'  => $item->content,
            'status'   => $item->status,
            'due_date' => $item->due_date,
            'assignee' => $item->assignee ? ['id' => $item->assignee->id, 'name' => $item->assignee->name] : null,
        ], 201);
    }

    public function updateActionItemStatus(Request $request, MeetingActionItem $meetingActionItem): JsonResponse
    {
        $meetingActionItem->loadMissing('minute');
        abort_if(!$meetingActionItem->minute || !$meetingActionItem->minute->canBeViewedBy($request->user()), 403);

        $request->validate(['status' => 'required|in:pending,in_progress,done']);
        $meetingActionItem->update(['status' => $request->status]);
        return response()->json(['status' => $meetingActionItem->status]);
    }

    public function destroyActionItem(Request $request, MeetingActionItem $meetingActionItem): JsonResponse
    {
        $meetingActionItem->loadMissing('minute');
        abort_if(!$meetingActionItem->minute || !$meetingActionItem->minute->canBeViewedBy($request->user()), 403);

        $meetingActionItem->delete();
        return response()->json(['message' => '액션 아이템이 삭제되었습니다.']);
    }

    private function minuteResource(MeetingMinute $m): array
    {
        return [
            'id'           => $m->id,
            'title'        => $m->title,
            'type'         => $m->type,
            'meeting_date' => $m->meeting_date,
            'location'     => $m->location,
            'agenda'       => $m->agenda,
            'discussion'   => $m->discussion,
            'decisions'    => $m->decisions,
            'author'       => $m->author ? ['id' => $m->author->id, 'name' => $m->author->name] : null,
            'project'      => $m->project ? ['id' => $m->project->id, 'name' => $m->project->name] : null,
            'action_items_count' => $m->actionItems ? $m->actionItems->count() : 0,
            'created_at'   => $m->created_at,
        ];
    }
}