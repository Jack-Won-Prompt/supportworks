<?php

namespace App\Http\Controllers;

use App\Models\Memo;
use App\Models\MemoShare;
use App\Models\User;
use Illuminate\Http\Request;

class MemoController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $myMemos = Memo::where('user_id', $userId)
            ->with(['shares.sharedToUser'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        // 다른 사람이 나에게 공유한 메모
        $sharedWithMe = MemoShare::where('shared_to', $userId)
            ->with(['memo.user', 'sharedByUser'])
            ->latest()
            ->get()
            ->map(fn($s) => $this->sharedMemoData($s));

        if (request()->wantsJson()) {
            return response()->json([
                'mine'   => $myMemos->map(fn($m) => $this->memoData($m)),
                'shared' => $sharedWithMe,
            ]);
        }

        $memos = $myMemos;
        return view('memos.index', compact('memos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
            'title'   => 'nullable|string|max:200',
            'color'   => 'nullable|in:yellow,green,blue,pink,purple',
        ]);

        $memo = Memo::create([
            'user_id' => auth()->id(),
            'title'   => $request->title,
            'content' => $request->content,
            'color'   => $request->color ?? 'yellow',
        ]);

        if ($request->wantsJson()) {
            return response()->json($this->memoData($memo->load('shares')), 201);
        }

        return back()->with('success', '메모가 저장되었습니다.');
    }

    public function update(Request $request, Memo $memo)
    {
        abort_if($memo->user_id !== auth()->id(), 403);

        $request->validate([
            'content' => 'required|string|max:2000',
            'title'   => 'nullable|string|max:200',
            'color'   => 'nullable|in:yellow,green,blue,pink,purple',
        ]);

        $memo->update([
            'title'   => $request->has('title') ? $request->title : $memo->title,
            'content' => $request->content,
            'color'   => $request->color ?? $memo->color,
        ]);

        if ($request->wantsJson()) {
            return response()->json($this->memoData($memo->load('shares')));
        }

        return back()->with('success', '메모가 수정되었습니다.');
    }

    public function togglePin(Memo $memo)
    {
        abort_if($memo->user_id !== auth()->id(), 403);
        $memo->update(['is_pinned' => !$memo->is_pinned]);

        if (request()->wantsJson()) {
            return response()->json($this->memoData($memo->load('shares')));
        }

        return back();
    }

    public function destroy(Memo $memo)
    {
        abort_if($memo->user_id !== auth()->id(), 403);
        $memo->delete();

        if (request()->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '메모가 삭제되었습니다.');
    }

    public function share(Request $request, Memo $memo)
    {
        abort_if($memo->user_id !== auth()->id(), 403);

        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $userId = auth()->id();
        $now    = now();

        foreach ($request->user_ids as $toId) {
            if ($toId == $userId) continue;

            MemoShare::updateOrCreate(
                ['memo_id' => $memo->id, 'shared_to' => $toId],
                ['shared_by' => $userId, 'updated_at' => $now]
            );
        }

        return response()->json([
            'ok'   => true,
            'memo' => $this->memoData($memo->load('shares.sharedToUser')),
        ]);
    }

    public function unshare(Request $request, Memo $memo)
    {
        abort_if($memo->user_id !== auth()->id(), 403);

        $request->validate(['user_id' => 'required|integer|exists:users,id']);

        MemoShare::where('memo_id', $memo->id)
                 ->where('shared_to', $request->user_id)
                 ->delete();

        return response()->json([
            'ok'   => true,
            'memo' => $this->memoData($memo->load('shares.sharedToUser')),
        ]);
    }

    public function members()
    {
        $users = User::where('id', '!=', auth()->id())
            ->whereIn('role', ['admin', 'member'])
            ->select('id', 'name', 'email', 'avatar')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    private function memoData(Memo $memo): array
    {
        $sharedWith = $memo->relationLoaded('shares')
            ? $memo->shares->map(fn($s) => [
                'user_id'  => $s->shared_to,
                'name'     => optional($s->sharedToUser)->name,
                'avatar'   => optional($s->sharedToUser)->avatar,
              ])->values()->all()
            : [];

        return [
            'id'          => $memo->id,
            'title'       => $memo->title,
            'content'     => $memo->content,
            'color'       => $memo->color,
            'is_pinned'   => $memo->is_pinned,
            'updated_at'  => $memo->updated_at->diffForHumans(),
            'shared_with' => $sharedWith,
        ];
    }

    public function toggleSharedPin(MemoShare $share)
    {
        abort_if($share->shared_to !== auth()->id(), 403);

        $share->update(['is_pinned' => !$share->is_pinned]);

        return response()->json(
            $this->sharedMemoData($share->load(['memo.user', 'sharedByUser']))
        );
    }

    private function sharedMemoData(MemoShare $share): array
    {
        $memo = $share->memo;
        return [
            'share_id'      => $share->id,
            'id'            => $memo->id,
            'title'         => $memo->title,
            'content'       => $memo->content,
            'color'         => $memo->color,
            'is_pinned'     => $share->is_pinned,
            'updated_at'    => $memo->updated_at->diffForHumans(),
            'shared_with'   => [],
            'is_received'   => true,
            'shared_by_id'  => $share->shared_by,
            'shared_by_name'=> optional($share->sharedByUser)->name,
            'shared_at'     => $share->created_at->diffForHumans(),
        ];
    }
}
