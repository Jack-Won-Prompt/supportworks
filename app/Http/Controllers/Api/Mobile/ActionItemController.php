<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\ActionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActionItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $items = ActionItem::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhere('assigned_to', $user->id);
            })
            ->with(['project', 'assignee'])
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($items->map(fn($a) => $this->actionResource($a)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date'    => 'nullable|date',
            'project_id'  => 'nullable|exists:projects,id',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $item = ActionItem::create([
            'user_id'     => $request->user()->id,
            'title'       => $request->title,
            'description' => $request->description,
            'due_date'    => $request->due_date,
            'project_id'  => $request->project_id,
            'assigned_to' => $request->assigned_to,
            'is_completed' => false,
        ]);

        $item->load(['project', 'assignee']);

        return response()->json($this->actionResource($item), 201);
    }

    public function toggle(Request $request, ActionItem $actionItem): JsonResponse
    {
        abort_if($actionItem->user_id !== $request->user()->id && $actionItem->assigned_to !== $request->user()->id, 403);
        $actionItem->update(['is_completed' => !$actionItem->is_completed]);
        return response()->json($this->actionResource($actionItem));
    }

    public function destroy(Request $request, ActionItem $actionItem): JsonResponse
    {
        abort_if($actionItem->user_id !== $request->user()->id, 403);
        $actionItem->delete();
        return response()->json(['message' => '액션 아이템이 삭제되었습니다.']);
    }

    private function actionResource(ActionItem $a): array
    {
        return [
            'id'           => $a->id,
            'title'        => $a->title,
            'description'  => $a->description,
            'is_completed' => $a->is_completed,
            'due_date'     => $a->due_date,
            'project'      => $a->project ? ['id' => $a->project->id, 'name' => $a->project->name] : null,
            'assignee'     => $a->assignee ? ['id' => $a->assignee->id, 'name' => $a->assignee->name] : null,
            'created_at'   => $a->created_at,
        ];
    }
}