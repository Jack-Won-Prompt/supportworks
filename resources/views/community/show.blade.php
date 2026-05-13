@extends('layouts.app')

@section('title', Str::limit($post->title, 40))

@section('header-breadcrumb')
    <span style="color:var(--t300);font-size:13px;">›</span>
    <a href="{{ route('community.index') }}" style="font-size:13px;color:#a1a1aa;text-decoration:none;" onmouseover="this.style.color='var(--tText)'" onmouseout="this.style.color='#a1a1aa'">{{ __('messages.community') }}</a>
@endsection

@section('content')
<style>
    .show-wrap { max-width: 780px; margin: 0 auto; }

    /* post card */
    .main-card { background:#fff;border:1px solid var(--t100);border-radius:14px;overflow:hidden;margin-bottom:1.25rem; }
    .main-card-inner { display:flex;gap:0; }
    .main-vote-col { display:flex;flex-direction:column;align-items:center;gap:.2rem;padding:1.25rem .75rem;background:var(--tBg);border-right:1px solid var(--t50);min-width:56px; }
    .vote-btn { width:30px;height:30px;border-radius:8px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s;color:var(--t300); }
    .vote-btn:hover { background:var(--t100);color:var(--tText); }
    .vote-btn.up.active { color:#f97316;background:#fff7ed; }
    .vote-btn.down.active { color:#3b82f6;background:#eff6ff; }
    .vote-score { font-size:.9rem;font-weight:800;color:#6b7280;min-width:24px;text-align:center; }
    .vote-score.positive { color:#f97316; }
    .vote-score.negative { color:#3b82f6; }

    .main-body { flex:1;padding:1.25rem 1.4rem;min-width:0; }
    .main-meta { display:flex;align-items:center;gap:.5rem;margin-bottom:.65rem;flex-wrap:wrap; }
    .cat-badge { font-size:.68rem;font-weight:700;padding:.18rem .55rem;border-radius:6px;color:#fff; }
    .main-author { font-size:.75rem;color:#9e97c0; }
    .main-time { font-size:.72rem;color:#c4b0d0; }
    .main-title { font-size:1.2rem;font-weight:800;color:#1e1b2e;line-height:1.4;margin-bottom:.9rem; }
    .main-content { font-size:.875rem;color:#3f3c5a;line-height:1.75;white-space:pre-wrap; }
    .main-footer { display:flex;align-items:center;gap:.75rem;margin-top:1rem;padding-top:.85rem;border-top:1px solid var(--tBg); }
    .main-stat { display:inline-flex;align-items:center;gap:.3rem;font-size:.73rem;color:#a1a1aa; }
    .del-link { font-size:.73rem;color:#e9b8b8;background:none;border:none;cursor:pointer;transition:color .13s; }
    .del-link:hover { color:#ef4444; }

    /* comment input */
    .comment-input-box { background:#fff;border:1px solid var(--t100);border-radius:14px;padding:1.1rem 1.25rem;margin-bottom:1.25rem; }
    .comment-input-title { font-size:.8rem;font-weight:700;color:var(--tText);margin-bottom:.65rem; }
    .comment-textarea { width:100%;padding:.75rem .9rem;border:1.5px solid var(--t100);border-radius:10px;font-size:.86rem;color:#1e1b2e;outline:none;font-family:inherit;resize:vertical;min-height:80px;transition:border-color .15s; }
    .comment-textarea:focus { border-color:var(--t400); }
    .comment-submit { margin-top:.65rem;padding:.6rem 1.2rem;background:linear-gradient(135deg,var(--t500),var(--t700));color:#fff;border:none;border-radius:9px;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s;box-shadow:0 2px 8px rgba(0,0,0,.1); }
    .comment-submit:hover { transform:translateY(-1px);box-shadow:0 4px 12px rgba(0,0,0,.15); }

    /* comments */
    .comments-section { }
    .comments-count { font-size:.82rem;font-weight:700;color:#52525b;margin-bottom:.9rem; }
    .comment-card { background:#fff;border:1px solid var(--t100);border-radius:12px;padding:1rem 1.15rem;margin-bottom:.65rem; }
    .comment-header { display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem; }
    .comment-avatar { width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--t200),var(--t300));display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--t700);flex-shrink:0; }
    .comment-name { font-size:.78rem;font-weight:700;color:#1e1b2e; }
    .comment-time { font-size:.7rem;color:#a1a1aa; }
    .comment-content { font-size:.84rem;color:#3f3c5a;line-height:1.65;white-space:pre-wrap;margin-bottom:.65rem; }
    .comment-actions { display:flex;align-items:center;gap:.75rem; }
    .comment-vote-wrap { display:inline-flex;align-items:center;gap:.2rem;background:var(--tBg);border:1px solid var(--t50);border-radius:8px;padding:.15rem .3rem; }
    .c-vote-btn { width:22px;height:22px;border-radius:5px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .13s;color:var(--t300); }
    .c-vote-btn:hover { background:var(--t100);color:var(--tText); }
    .c-vote-btn.up.active { color:#f97316; }
    .c-vote-btn.down.active { color:#3b82f6; }
    .c-vote-score { font-size:.72rem;font-weight:800;color:#a1a1aa;min-width:14px;text-align:center; }
    .reply-btn { font-size:.72rem;color:#a1a1aa;background:none;border:none;cursor:pointer;padding:.2rem .4rem;border-radius:5px;transition:all .13s;font-family:inherit; }
    .reply-btn:hover { background:var(--t50);color:var(--tText); }
    .c-del-btn { font-size:.7rem;color:#e9b8b8;background:none;border:none;cursor:pointer;margin-left:auto;transition:color .13s;font-family:inherit; }
    .c-del-btn:hover { color:#ef4444; }

    /* replies */
    .replies-wrap { margin-top:.75rem;padding-left:1.25rem;border-left:2px solid var(--t50);display:flex;flex-direction:column;gap:.6rem; }
    .reply-card { background:var(--tBg);border-radius:10px;padding:.75rem .9rem; }
    .reply-input-wrap { margin-top:.65rem;display:none; }
    .reply-textarea { width:100%;padding:.6rem .8rem;border:1.5px solid var(--t100);border-radius:9px;font-size:.82rem;color:#1e1b2e;outline:none;font-family:inherit;resize:vertical;min-height:60px;transition:border-color .15s; }
    .reply-textarea:focus { border-color:var(--t400); }
    .reply-submit-btn { margin-top:.45rem;padding:.5rem 1rem;background:linear-gradient(135deg,var(--t500),var(--t700));color:#fff;border:none;border-radius:8px;font-size:.78rem;font-weight:700;cursor:pointer;transition:all .2s; }
    .reply-cancel-btn { margin-top:.45rem;margin-left:.4rem;padding:.5rem .8rem;background:var(--tBg);color:#a1a1aa;border:1px solid var(--t100);border-radius:8px;font-size:.78rem;font-weight:600;cursor:pointer;transition:all .13s; }
</style>

<div class="show-wrap">

    {{-- 메인 포스트 --}}
    <div class="main-card">
        <div class="main-card-inner">
            {{-- 투표 --}}
            <div class="main-vote-col">
                <button class="vote-btn up {{ $userPostVote == 1 ? 'active' : '' }}"
                        onclick="votePost({{ $post->id }}, 1, this)" title="{{ __('messages.recommend') }}">
                    <svg width="15" height="15" fill="{{ $userPostVote == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                </button>
                <span class="vote-score {{ $userPostVote == 1 ? 'positive' : ($userPostVote == -1 ? 'negative' : '') }}" id="post-score-{{ $post->id }}">{{ $post->votes }}</span>
                <button class="vote-btn down {{ $userPostVote == -1 ? 'active' : '' }}"
                        onclick="votePost({{ $post->id }}, -1, this)" title="{{ __('messages.disrecommend') }}">
                    <svg width="15" height="15" fill="{{ $userPostVote == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            {{-- 본문 --}}
            <div class="main-body">
                <div class="main-meta">
                    @if($post->pinned)<span style="font-size:.7rem;color:#f59e0b;font-weight:700;">{{ __('messages.pinned') }}</span>@endif
                    <span class="cat-badge" style="background:{{ $post->category_color }}">{{ $post->category_label }}</span>
                    <span class="main-author">{{ $post->user->name }}</span>
                    <span class="main-time">· {{ $post->created_at->diffForHumans() }}</span>
                </div>
                <h1 class="main-title">{{ $post->title }}</h1>
                <div class="main-content">{{ $post->content }}</div>
                <div class="main-footer">
                    <span class="main-stat">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        {{ __('messages.comments_count_label', ['count' => $post->allComments->count()]) }}
                    </span>
                    @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('community.destroy', $post) }}" onsubmit="return confirm('{{ __('messages.delete_post_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="del-link">{{ __('common.delete') }}</button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 댓글 입력 --}}
    <div class="comment-input-box">
        <div class="comment-input-title">{{ __('messages.comment_input_title') }}</div>
        <form method="POST" action="{{ route('community.comments.store', $post) }}">
            @csrf
            <textarea name="content" class="comment-textarea" placeholder="{{ __('messages.comment_placeholder') }}" required maxlength="5000"></textarea>
            <button type="submit" class="comment-submit">{{ __('messages.comment_submit') }}</button>
        </form>
    </div>

    {{-- 댓글 목록 --}}
    <div class="comments-section">
        <div class="comments-count">{{ __('messages.comment_count', ['count' => $post->comments->count()]) }}</div>

        @forelse($post->comments as $comment)
        @php $cv = $userCommentVotes[$comment->id] ?? 0; @endphp
        <div class="comment-card">
            <div class="comment-header">
                <div class="comment-avatar">{{ mb_substr($comment->user->name, 0, 1) }}</div>
                <span class="comment-name">{{ $comment->user->name }}</span>
                <span class="comment-time">· {{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <div class="comment-content">{{ $comment->content }}</div>
            <div class="comment-actions">
                {{-- 투표 --}}
                <div class="comment-vote-wrap">
                    <button class="c-vote-btn up {{ $cv == 1 ? 'active' : '' }}"
                            onclick="voteComment({{ $comment->id }}, 1, this)" title="{{ __('messages.recommend') }}">
                        <svg width="11" height="11" fill="{{ $cv == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <span class="c-vote-score" id="comment-score-{{ $comment->id }}">{{ $comment->votes }}</span>
                    <button class="c-vote-btn down {{ $cv == -1 ? 'active' : '' }}"
                            onclick="voteComment({{ $comment->id }}, -1, this)" title="{{ __('messages.disrecommend') }}">
                        <svg width="11" height="11" fill="{{ $cv == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>
                {{-- 답글 --}}
                <button class="reply-btn" onclick="toggleReply('reply-form-{{ $comment->id }}')">{{ __('messages.reply') }}</button>
                {{-- 삭제 --}}
                @if(auth()->id() === $comment->user_id || auth()->user()->isAdmin())
                <form method="POST" action="{{ route('community.comments.destroy', $comment) }}" onsubmit="return confirm('{{ __('messages.delete_comment_confirm') }}')" style="margin-left:auto;">
                    @csrf @method('DELETE')
                    <button type="submit" class="c-del-btn">{{ __('common.delete') }}</button>
                </form>
                @endif
            </div>

            {{-- 답글 입력폼 --}}
            <div id="reply-form-{{ $comment->id }}" class="reply-input-wrap">
                <form method="POST" action="{{ route('community.comments.store', $post) }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="content" class="reply-textarea" placeholder="{{ __('messages.reply_placeholder', ['name' => $comment->user->name]) }}" required maxlength="5000"></textarea>
                    <button type="submit" class="reply-submit-btn">{{ __('messages.reply_register') }}</button>
                    <button type="button" class="reply-cancel-btn" onclick="toggleReply('reply-form-{{ $comment->id }}')">{{ __('common.cancel') }}</button>
                </form>
            </div>

            {{-- 답글 목록 --}}
            @if($comment->replies->count() > 0)
            <div class="replies-wrap">
                @foreach($comment->replies as $reply)
                @php $rv = $userCommentVotes[$reply->id] ?? 0; @endphp
                <div class="reply-card">
                    <div class="comment-header">
                        <div class="comment-avatar" style="width:22px;height:22px;font-size:.65rem;">{{ mb_substr($reply->user->name, 0, 1) }}</div>
                        <span class="comment-name">{{ $reply->user->name }}</span>
                        <span class="comment-time">· {{ $reply->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="comment-content" style="font-size:.82rem;">{{ $reply->content }}</div>
                    <div class="comment-actions">
                        <div class="comment-vote-wrap">
                            <button class="c-vote-btn up {{ $rv == 1 ? 'active' : '' }}"
                                    onclick="voteComment({{ $reply->id }}, 1, this)">
                                <svg width="10" height="10" fill="{{ $rv == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <span class="c-vote-score" id="comment-score-{{ $reply->id }}">{{ $reply->votes }}</span>
                            <button class="c-vote-btn down {{ $rv == -1 ? 'active' : '' }}"
                                    onclick="voteComment({{ $reply->id }}, -1, this)">
                                <svg width="10" height="10" fill="{{ $rv == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        @if(auth()->id() === $reply->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('community.comments.destroy', $reply) }}" onsubmit="return confirm('{{ __('messages.delete_comment_confirm') }}')" style="margin-left:auto;">
                            @csrf @method('DELETE')
                            <button type="submit" class="c-del-btn">{{ __('common.delete') }}</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div style="text-align:center;padding:2.5rem;color:#c4b5d8;font-size:.85rem;">
            {{ __('messages.no_comments_yet') }}
        </div>
        @endforelse
    </div>

</div>
@endsection

@section('scripts')
<script>
const POST_VOTE_URL    = @json(route('community.vote', $post));
const CSRF             = document.querySelector('meta[name="csrf-token"]').content;

const COMMENT_VOTE_URLS = @json($post->allComments->pluck('id')->mapWithKeys(fn($id) => [$id => route('community.comments.vote', $id)]));

async function votePost(postId, value, btn) {
    fetch(POST_VOTE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ value }),
    })
    .then(r => r.json())
    .then(data => {
        const col     = btn.closest('.main-vote-col');
        const upBtn   = col.querySelector('.vote-btn.up');
        const downBtn = col.querySelector('.vote-btn.down');
        const score   = document.getElementById('post-score-' + postId);

        score.textContent = data.votes;
        score.className = 'vote-score' + (data.votes > 0 ? ' positive' : data.votes < 0 ? ' negative' : '');
        upBtn.classList.toggle('active', data.userVote === 1);
        downBtn.classList.toggle('active', data.userVote === -1);
        upBtn.querySelector('svg').setAttribute('fill', data.userVote === 1 ? 'currentColor' : 'none');
        downBtn.querySelector('svg').setAttribute('fill', data.userVote === -1 ? 'currentColor' : 'none');
    });
}

async function voteComment(commentId, value, btn) {
    const url = COMMENT_VOTE_URLS[commentId];
    if (!url) return;
    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ value }),
    })
    .then(r => r.json())
    .then(data => {
        const wrap    = btn.closest('.comment-vote-wrap');
        const upBtn   = wrap.querySelector('.c-vote-btn.up');
        const downBtn = wrap.querySelector('.c-vote-btn.down');
        const score   = document.getElementById('comment-score-' + commentId);

        score.textContent = data.votes;
        upBtn.classList.toggle('active', data.userVote === 1);
        downBtn.classList.toggle('active', data.userVote === -1);
        upBtn.querySelector('svg').setAttribute('fill', data.userVote === 1 ? 'currentColor' : 'none');
        downBtn.querySelector('svg').setAttribute('fill', data.userVote === -1 ? 'currentColor' : 'none');
    });
}

async function toggleReply(id) {
    const el = document.getElementById(id);
    const visible = el.style.display === 'block';
    el.style.display = visible ? 'none' : 'block';
    if (!visible) el.querySelector('textarea').focus();
}
</script>
@endsection
