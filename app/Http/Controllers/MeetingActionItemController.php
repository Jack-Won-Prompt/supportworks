<?php

namespace App\Http\Controllers;

use App\Models\MeetingActionItem;
use App\Models\MeetingMinute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingActionItemController extends Controller
{
    public function store(Request $request, MeetingMinute $meetingMinute): RedirectResponse
    {
        $request->validate([
            'title'       => 'required|string|max:200',
            'description' => 'nullable|string',
            'owner_id'    => 'nullable|exists:users,id',
            'owner_name'  => 'nullable|string|max:100',
            'due_date'    => 'nullable|date',
            'priority'    => 'nullable|in:high,medium,low',
            'memo_id'     => 'nullable|exists:meeting_memos,id',
        ]);

        MeetingActionItem::create([
            ...$request->only('title', 'description', 'owner_id', 'owner_name', 'due_date', 'priority', 'memo_id'),
            'minute_id' => $meetingMinute->id,
            'priority'  => $request->input('priority', 'medium'),
            'status'    => 'pending',
        ]);

        return back()->with('success', 'Action Item이 추가되었습니다.');
    }

    public function updateStatus(Request $request, MeetingActionItem $meetingActionItem): RedirectResponse
    {
        $request->validate(['status' => 'required|in:pending,in_progress,completed']);

        $user = auth()->user();
        abort_if(
            $meetingActionItem->owner_id !== $user->id
            && $meetingActionItem->minute->author_id !== $user->id
            && !$user->isAdmin(),
            403
        );

        $meetingActionItem->update(['status' => $request->status]);

        return back();
    }

    public function destroy(MeetingActionItem $meetingActionItem): RedirectResponse
    {
        $user = auth()->user();
        abort_if(
            $meetingActionItem->minute->author_id !== $user->id && !$user->isAdmin(),
            403
        );

        $meetingActionItem->delete();

        return back()->with('success', 'Action Item이 삭제되었습니다.');
    }
}
