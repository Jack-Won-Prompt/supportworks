<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\CommunityComment;
use App\Models\CommunityPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $posts = CommunityPost::companyOf($user)
            ->withCount(['allComments', 'votes'])
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $posts->map(fn($p) => $this->postResource($p)),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page'    => $posts->lastPage(),
                'total'        => $posts->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $post = CommunityPost::create([
            'user_id'          => $request->user()->id,
            'company_group_id' => $request->user()->company_group_id,
            'title'            => $request->title,
            'content'          => $request->content,
        ]);

        $post->load('user');

        return response()->json($this->postResource($post), 201);
    }

    public function show(Request $request, CommunityPost $post): JsonResponse
    {
        $post->load(['user', 'comments.user', 'comments.replies.user']);

        return response()->json([
            ...$this->postResource($post),
            'comments' => $post->comments->whereNull('parent_id')->values()->map(fn($c) => [
                'id'      => $c->id,
                'content' => $c->content,
                'user'    => ['id' => $c->user->id, 'name' => $c->user->name],
                'created_at' => $c->created_at,
                'replies' => $c->replies->map(fn($r) => [
                    'id'      => $r->id,
                    'content' => $r->content,
                    'user'    => ['id' => $r->user->id, 'name' => $r->user->name],
                    'created_at' => $r->created_at,
                ]),
            ]),
        ]);
    }

    public function destroy(Request $request, CommunityPost $post): JsonResponse
    {
        abort_if($post->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $post->delete();
        return response()->json(['message' => '게시글이 삭제되었습니다.']);
    }

    public function vote(Request $request, CommunityPost $post): JsonResponse
    {
        $user   = $request->user();
        $exists = $post->votes()->where('user_id', $user->id)->first();

        if ($exists) {
            $exists->delete();
            $voted = false;
        } else {
            $post->votes()->create(['user_id' => $user->id]);
            $voted = true;
        }

        return response()->json([
            'voted'       => $voted,
            'votes_count' => $post->votes()->count(),
        ]);
    }

    public function storeComment(Request $request, CommunityPost $post): JsonResponse
    {
        $request->validate([
            'content'   => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:community_comments,id',
        ]);

        $comment = $post->comments()->create([
            'user_id'   => $request->user()->id,
            'content'   => $request->content,
            'parent_id' => $request->parent_id,
        ]);

        $comment->load('user');

        return response()->json([
            'id'         => $comment->id,
            'content'    => $comment->content,
            'parent_id'  => $comment->parent_id,
            'user'       => ['id' => $comment->user->id, 'name' => $comment->user->name],
            'created_at' => $comment->created_at,
        ], 201);
    }

    public function destroyComment(Request $request, CommunityComment $comment): JsonResponse
    {
        abort_if($comment->user_id !== $request->user()->id && !$request->user()->isAdmin(), 403);
        $comment->delete();
        return response()->json(['message' => '댓글이 삭제되었습니다.']);
    }

    private function postResource(CommunityPost $p): array
    {
        return [
            'id'                 => $p->id,
            'title'              => $p->title,
            'content'            => $p->content,
            'all_comments_count' => $p->all_comments_count ?? 0,
            'votes_count'        => $p->votes_count ?? 0,
            'user'               => $p->user ? ['id' => $p->user->id, 'name' => $p->user->name] : null,
            'created_at'         => $p->created_at,
        ];
    }
}