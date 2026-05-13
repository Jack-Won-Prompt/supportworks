<?php

namespace App\Http\Controllers;

use App\Models\CommunityComment;
use App\Models\CommunityPost;
use App\Models\CommunityReaction;
use App\Models\CommunityVote;
use Illuminate\Http\Request;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $user     = auth()->user();
        $sort     = $request->get('sort', 'new');
        $category = $request->get('category');

        // ── CompanyScope 가 자동으로 회사 범위 필터를 적용함 ──────────────
        $query = CommunityPost::with(['user', 'allComments']);

        if ($category) {
            $query->where('category', $category);
        }

        match ($sort) {
            'hot'  => $query->orderBy('votes', 'desc')->orderBy('created_at', 'desc'),
            'top'  => $query->orderByRaw('(votes * 2) - DATEDIFF(NOW(), created_at) DESC'),
            default => $query->orderBy('pinned', 'desc')->orderBy('created_at', 'desc'),
        };

        $posts     = $query->paginate(20)->withQueryString();
        $postIds   = $posts->pluck('id');

        $userVotes = CommunityVote::where('user_id', $user->id)
            ->where('votable_type', 'post')
            ->whereIn('votable_id', $postIds)
            ->pluck('value', 'votable_id');

        $reactionCounts = CommunityReaction::whereIn('post_id', $postIds)
            ->selectRaw('post_id, emoji, COUNT(*) as cnt')
            ->groupBy('post_id', 'emoji')
            ->get()
            ->groupBy('post_id');

        $company = $user->companyGroup?->name ?? $user->company;

        return view('community.index', compact('posts', 'sort', 'category', 'company', 'userVotes', 'reactionCounts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'    => 'required|string|max:200',
            'content'  => 'required|string|max:10000',
            'category' => 'required|in:general,question,idea,announcement,technical',
        ]);

        // ── BelongsToCompany trait 이 company_group_id 를 자동 설정 ──────
        $post = CommunityPost::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('community.show', $post)
            ->with('success', '게시물이 등록됐습니다.');
    }

    public function quickView(CommunityPost $post)
    {
        // route model binding 이 CompanyScope 를 통해 자동으로 회사 격리
        // → 다른 회사 게시글은 findOrFail 에서 404 반환

        $post->load(['user', 'comments.user', 'comments.replies.user']);

        $user             = auth()->user();
        $commentIds       = $post->allComments->pluck('id');
        $userCommentVotes = CommunityVote::where('user_id', $user->id)
            ->where('votable_type', 'comment')
            ->whereIn('votable_id', $commentIds)
            ->pluck('value', 'votable_id');

        $userPostVote = CommunityVote::where('user_id', $user->id)
            ->where('votable_type', 'post')
            ->where('votable_id', $post->id)
            ->value('value');

        $reactionCounts = CommunityReaction::where('post_id', $post->id)
            ->selectRaw('emoji, COUNT(*) as cnt')
            ->groupBy('emoji')
            ->pluck('cnt', 'emoji');

        $userReactions = CommunityReaction::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->pluck('emoji');

        return view('community._detail', compact('post', 'userCommentVotes', 'userPostVote', 'reactionCounts', 'userReactions'));
    }

    public function show(CommunityPost $post)
    {
        $post->load(['user', 'comments.user', 'comments.replies.user']);

        $user             = auth()->user();
        $commentIds       = $post->allComments->pluck('id');
        $userCommentVotes = CommunityVote::where('user_id', $user->id)
            ->where('votable_type', 'comment')
            ->whereIn('votable_id', $commentIds)
            ->pluck('value', 'votable_id');

        $userPostVote = CommunityVote::where('user_id', $user->id)
            ->where('votable_type', 'post')
            ->where('votable_id', $post->id)
            ->value('value');

        return view('community.show', compact('post', 'userCommentVotes', 'userPostVote'));
    }

    public function destroy(CommunityPost $post)
    {
        abort_if($post->user_id !== auth()->id() && !auth()->user()->isAdmin(), 403);
        $post->delete();
        return redirect()->route('community.index')->with('success', '게시물이 삭제됐습니다.');
    }

    public function vote(Request $request, CommunityPost $post)
    {
        $value    = (int) $request->validate(['value' => 'required|in:-1,1'])['value'];
        $userId   = auth()->id();
        $existing = CommunityVote::where('user_id', $userId)
            ->where('votable_type', 'post')
            ->where('votable_id', $post->id)
            ->first();

        if ($existing) {
            if ($existing->value === $value) {
                $post->decrement('votes', $value);
                $existing->delete();
                $userVote = 0;
            } else {
                $post->increment('votes', $value - $existing->value);
                $existing->update(['value' => $value]);
                $userVote = $value;
            }
        } else {
            CommunityVote::create(['user_id' => $userId, 'votable_type' => 'post', 'votable_id' => $post->id, 'value' => $value]);
            $post->increment('votes', $value);
            $userVote = $value;
        }

        return response()->json(['votes' => $post->fresh()->votes, 'userVote' => $userVote]);
    }

    public function storeComment(Request $request, CommunityPost $post)
    {
        $validated = $request->validate([
            'content'   => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:community_comments,id',
        ]);

        CommunityComment::create([...$validated, 'post_id' => $post->id, 'user_id' => auth()->id()]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', '댓글이 등록됐습니다.');
    }

    public function destroyComment(CommunityComment $comment)
    {
        abort_if($comment->user_id !== auth()->id() && !auth()->user()->isAdmin(), 403);
        $comment->delete();
        return back()->with('success', '댓글이 삭제됐습니다.');
    }

    public function voteComment(Request $request, CommunityComment $comment)
    {
        $value    = (int) $request->validate(['value' => 'required|in:-1,1'])['value'];
        $userId   = auth()->id();
        $existing = CommunityVote::where('user_id', $userId)
            ->where('votable_type', 'comment')
            ->where('votable_id', $comment->id)
            ->first();

        if ($existing) {
            if ($existing->value === $value) {
                $comment->decrement('votes', $value);
                $existing->delete();
                $userVote = 0;
            } else {
                $comment->increment('votes', $value - $existing->value);
                $existing->update(['value' => $value]);
                $userVote = $value;
            }
        } else {
            CommunityVote::create(['user_id' => $userId, 'votable_type' => 'comment', 'votable_id' => $comment->id, 'value' => $value]);
            $comment->increment('votes', $value);
            $userVote = $value;
        }

        return response()->json(['votes' => $comment->fresh()->votes, 'userVote' => $userVote]);
    }

    public function react(Request $request, CommunityPost $post)
    {
        $emoji    = $request->validate(['emoji' => 'required|in:like,heart,laugh,wow,sad,fire'])['emoji'];
        $userId   = auth()->id();
        $existing = CommunityReaction::where('post_id', $post->id)->where('user_id', $userId)->first();

        if ($existing) {
            if ($existing->emoji === $emoji) {
                $existing->delete();
                $reacted = false;
            } else {
                $existing->update(['emoji' => $emoji]);
                $reacted = true;
            }
        } else {
            CommunityReaction::create(['post_id' => $post->id, 'user_id' => $userId, 'emoji' => $emoji]);
            $reacted = true;
        }

        $counts = CommunityReaction::where('post_id', $post->id)
            ->selectRaw('emoji, COUNT(*) as cnt')
            ->groupBy('emoji')
            ->pluck('cnt', 'emoji');

        return response()->json(['reacted' => $reacted, 'emoji' => $emoji, 'counts' => $counts]);
    }
}
