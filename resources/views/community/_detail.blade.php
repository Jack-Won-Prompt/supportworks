{{-- community/_detail.blade.php — AJAX partial, no layout --}}
<div class="dp-root" data-post-id="{{ $post->id }}">

    {{-- ══ 상단 고정: 원글 + 리액션 ══ --}}
    <div class="dp-sticky-top">
    {{-- ── 게시물 본문 ── --}}
    <div class="dp-post">
        <div class="dp-post-inner">
            {{-- 투표 컬럼 --}}
            <div class="dp-vote-col">
                <button class="dp-vbtn up {{ $userPostVote == 1 ? 'active' : '' }}"
                        data-vote="post"
                        data-url="{{ route('community.vote', $post) }}"
                        data-val="1" title="{{ __('messages.recommend') }}">
                    <svg width="14" height="14" fill="{{ $userPostVote == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                </button>
                <span class="dp-vscore {{ $userPostVote == 1 ? 'pos' : ($userPostVote == -1 ? 'neg' : '') }}"
                      data-score-post="{{ $post->id }}">{{ $post->votes }}</span>
                <button class="dp-vbtn down {{ $userPostVote == -1 ? 'active' : '' }}"
                        data-vote="post"
                        data-url="{{ route('community.vote', $post) }}"
                        data-val="-1" title="{{ __('messages.disrecommend') }}">
                    <svg width="14" height="14" fill="{{ $userPostVote == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>
            </div>

            {{-- 본문 --}}
            <div class="dp-post-body">
                <div class="dp-meta">
                    @if($post->pinned)<span class="dp-pin" title="{{ __('messages.pinned') }}">📌</span>@endif
                    <span class="dp-cat-badge" style="background:{{ $post->category_color }}">{{ $post->category_label }}</span>
                    <span class="dp-author">{{ $post->user->name }}</span>
                    <span class="dp-time">· {{ $post->created_at->diffForHumans() }}</span>
                    @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
                    <form method="POST" action="{{ route('community.destroy', $post) }}"
                          style="margin-left:auto;"
                          data-ajax-delete="post"
                          onsubmit="return confirm('{{ __('messages.delete_post_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="dp-del-btn">{{ __('common.delete') }}</button>
                    </form>
                    @endif
                </div>
                <h2 class="dp-title">{{ $post->title }}</h2>
                <div class="dp-content">{{ $post->content }}</div>

                {{-- ── 감정 표현 리액션 바 ── --}}
                <div class="dp-reaction-bar"
                     data-react-url="{{ route('community.react', $post) }}">
                    @php
                        $emojiMap = ['like'=>'👍','heart'=>'❤️','laugh'=>'😂','wow'=>'😮','sad'=>'😢','fire'=>'🔥'];
                        $emojiLabel = ['like'=>__('messages.emoji_like'),'heart'=>__('messages.emoji_heart'),'laugh'=>__('messages.emoji_laugh'),'wow'=>__('messages.emoji_wow'),'sad'=>__('messages.emoji_sad'),'fire'=>__('messages.emoji_fire')];
                    @endphp
                    @foreach($emojiMap as $key => $icon)
                    @php $cnt = $reactionCounts[$key] ?? 0; $mine = $userReactions->contains($key); @endphp
                    <button class="dp-react-btn {{ $mine ? 'active' : '' }}"
                            data-emoji="{{ $key }}"
                            title="{{ $emojiLabel[$key] }}">
                        <span class="dp-react-icon">{{ $icon }}</span>
                        <span class="dp-react-cnt {{ $cnt > 0 ? 'has-count' : '' }}"
                              data-cnt="{{ $key }}">{{ $cnt > 0 ? $cnt : '' }}</span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    </div>{{-- /dp-sticky-top --}}

    {{-- ══ 하단 스크롤: 댓글 입력 + 목록 ══ --}}
    <div class="dp-scroll-area">
    {{-- ── 댓글 입력 ── --}}
    <div class="dp-comment-box">
        <form class="js-comment-form"
              data-url="{{ route('community.comments.store', $post) }}"
              data-post="{{ $post->id }}">
            @csrf
            <textarea name="content" class="dp-textarea" placeholder="{{ __('messages.comment_placeholder') }}" required maxlength="5000" rows="3"></textarea>
            <button type="submit" class="dp-comment-btn">{{ __('messages.comment_submit') }}</button>
        </form>
    </div>

    {{-- ── 댓글 목록 ── --}}
    <div class="dp-comments-wrap">
        <div class="dp-comments-hd">{{ __('messages.comment_count', ['count' => $post->comments->count()]) }}</div>

        @forelse($post->comments as $comment)
        @php $cv = $userCommentVotes[$comment->id] ?? 0; @endphp
        <div class="dp-comment-card" id="comment-{{ $comment->id }}">
            <div class="dp-c-header">
                <div class="dp-c-avatar">{{ mb_substr($comment->user->name, 0, 1) }}</div>
                <span class="dp-c-name">{{ $comment->user->name }}</span>
                <span class="dp-c-time">· {{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <div class="dp-c-content">{{ $comment->content }}</div>
            <div class="dp-c-actions">
                {{-- 댓글 투표 --}}
                <span class="dp-cv-wrap">
                    <button class="dp-cvbtn up {{ $cv == 1 ? 'active' : '' }}"
                            data-vote="comment"
                            data-url="{{ route('community.comments.vote', $comment) }}"
                            data-val="1">
                        <svg width="10" height="10" fill="{{ $cv == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <span class="dp-cvscore" data-score-comment="{{ $comment->id }}">{{ $comment->votes }}</span>
                    <button class="dp-cvbtn down {{ $cv == -1 ? 'active' : '' }}"
                            data-vote="comment"
                            data-url="{{ route('community.comments.vote', $comment) }}"
                            data-val="-1">
                        <svg width="10" height="10" fill="{{ $cv == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </span>
                <button class="dp-reply-toggle" data-reply="{{ $comment->id }}">{{ __('messages.reply') }}</button>
                @if(auth()->id() === $comment->user_id || auth()->user()->isAdmin())
                <form method="POST" action="{{ route('community.comments.destroy', $comment) }}"
                      data-ajax-delete="comment"
                      style="margin-left:auto;"
                      onsubmit="return confirm('{{ __('messages.delete_comment_confirm') }}')">
                    @csrf @method('DELETE')
                    <button type="submit" class="dp-cdel-btn">{{ __('common.delete') }}</button>
                </form>
                @endif
            </div>

            {{-- 답글 입력 --}}
            <div class="dp-reply-form-wrap" id="reply-wrap-{{ $comment->id }}" style="display:none;">
                <form class="js-comment-form"
                      data-url="{{ route('community.comments.store', $post) }}"
                      data-post="{{ $post->id }}">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                    <textarea name="content" class="dp-reply-textarea" placeholder="{{ __('messages.reply_to_placeholder', ['name' => $comment->user->name]) }}" required maxlength="5000" rows="2"></textarea>
                    <div style="display:flex;gap:.4rem;margin-top:.4rem;">
                        <button type="submit" class="dp-comment-btn" style="padding:.45rem .9rem;font-size:.78rem;">{{ __('messages.reply_register') }}</button>
                        <button type="button" class="dp-cancel-btn" data-reply-cancel="{{ $comment->id }}">{{ __('common.cancel') }}</button>
                    </div>
                </form>
            </div>

            {{-- 답글 목록 --}}
            @if($comment->replies->count() > 0)
            <div class="dp-replies">
                @foreach($comment->replies as $reply)
                @php $rv = $userCommentVotes[$reply->id] ?? 0; @endphp
                <div class="dp-reply-card">
                    <div class="dp-c-header">
                        <div class="dp-c-avatar" style="width:20px;height:20px;font-size:.62rem;">{{ mb_substr($reply->user->name, 0, 1) }}</div>
                        <span class="dp-c-name">{{ $reply->user->name }}</span>
                        <span class="dp-c-time">· {{ $reply->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="dp-c-content" style="font-size:.81rem;">{{ $reply->content }}</div>
                    <div class="dp-c-actions">
                        <span class="dp-cv-wrap">
                            <button class="dp-cvbtn up {{ $rv == 1 ? 'active' : '' }}"
                                    data-vote="comment"
                                    data-url="{{ route('community.comments.vote', $reply) }}"
                                    data-val="1">
                                <svg width="9" height="9" fill="{{ $rv == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <span class="dp-cvscore" data-score-comment="{{ $reply->id }}">{{ $reply->votes }}</span>
                            <button class="dp-cvbtn down {{ $rv == -1 ? 'active' : '' }}"
                                    data-vote="comment"
                                    data-url="{{ route('community.comments.vote', $reply) }}"
                                    data-val="-1">
                                <svg width="9" height="9" fill="{{ $rv == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </span>
                        @if(auth()->id() === $reply->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('community.comments.destroy', $reply) }}"
                              data-ajax-delete="comment"
                              style="margin-left:auto;"
                              onsubmit="return confirm('{{ __('messages.delete_comment_confirm') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="dp-cdel-btn">{{ __('common.delete') }}</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="dp-no-comment">{{ __('messages.no_comments_yet') }}</div>
        @endforelse
    </div>
    </div>{{-- /dp-scroll-area --}}
</div>
