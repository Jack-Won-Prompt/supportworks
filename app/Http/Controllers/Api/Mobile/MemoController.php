<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Memo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $memos = Memo::where('user_id', $request->user()->id)
            ->orderByDesc('is_pinned')
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($memos->map(fn($m) => $this->memoResource($m)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'   => 'nullable|string|max:200',
            'content' => 'required|string|max:2000',
            'color'   => 'nullable|in:yellow,green,blue,pink,purple',
        ]);

        $memo = Memo::create([
            'user_id' => $request->user()->id,
            'title'   => $request->title,
            'content' => $request->content,
            'color'   => $request->color ?? 'yellow',
        ]);

        return response()->json($this->memoResource($memo), 201);
    }

    public function update(Request $request, Memo $memo): JsonResponse
    {
        abort_if($memo->user_id !== $request->user()->id, 403);

        $request->validate([
            'title'   => 'nullable|string|max:200',
            'content' => 'required|string|max:2000',
            'color'   => 'nullable|in:yellow,green,blue,pink,purple',
        ]);

        $memo->update([
            'title'   => $request->title,
            'content' => $request->content,
            'color'   => $request->color ?? $memo->color,
        ]);

        return response()->json($this->memoResource($memo));
    }

    public function togglePin(Request $request, Memo $memo): JsonResponse
    {
        abort_if($memo->user_id !== $request->user()->id, 403);
        $memo->update(['is_pinned' => !$memo->is_pinned]);
        return response()->json($this->memoResource($memo));
    }

    public function destroy(Request $request, Memo $memo): JsonResponse
    {
        abort_if($memo->user_id !== $request->user()->id, 403);
        $memo->delete();
        return response()->json(['message' => '메모가 삭제되었습니다.']);
    }

    private function memoResource(Memo $memo): array
    {
        return [
            'id'         => $memo->id,
            'title'      => $memo->title,
            'content'    => $memo->content,
            'color'      => $memo->color,
            'is_pinned'  => $memo->is_pinned,
            'created_at' => $memo->created_at,
            'updated_at' => $memo->updated_at,
        ];
    }
}