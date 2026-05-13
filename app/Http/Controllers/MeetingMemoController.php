<?php

namespace App\Http\Controllers;

use App\Models\MeetingMemo;
use App\Models\MeetingMinute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MeetingMemoController extends Controller
{
    public function store(Request $request, MeetingMinute $meetingMinute): RedirectResponse
    {
        $request->validate(['content' => 'required|string|max:3000']);

        MeetingMemo::create([
            'minute_id' => $meetingMinute->id,
            'user_id'   => auth()->id(),
            'content'   => $request->content,
        ]);

        return back()->with('success', '메모가 추가되었습니다.');
    }

    public function destroy(MeetingMemo $meetingMemo): RedirectResponse
    {
        abort_if(
            $meetingMemo->user_id !== auth()->id() && !auth()->user()->isAdmin(),
            403
        );

        $meetingMemo->delete();

        return back()->with('success', '메모가 삭제되었습니다.');
    }
}
