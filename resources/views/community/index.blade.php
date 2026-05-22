@extends('layouts.app')

@section('title', __('messages.community'))

@push('styles')
<style>
    main {
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        padding: 0 !important;
        min-height: 0 !important;
    }
</style>
@endpush

@section('header-actions')
    <button onclick="openCompose()" class="btn-write">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        {{ __('messages.new_post') }}
    </button>
@endsection

@section('content')
<style>
*,*::before,*::after{box-sizing:border-box}

/* ── Write button ── */
.btn-write{display:flex;align-items:center;gap:5px;padding:6px 16px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border:none;border-radius:9999px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(124,58,237,.35);transition:opacity .15s}
.btn-write:hover{opacity:.88}

/* ── Split layout ── */
.comm-split{display:flex;flex:1;min-height:0;overflow:hidden;background:#f1f0f7;padding:10px;gap:10px}

/* ── Left panel ── */
.list-col{flex:1;min-width:0;display:flex;flex-direction:column;overflow:hidden;background:#fff;border-radius:14px;border:1px solid rgba(196,181,253,.2);box-shadow:0 1px 6px rgba(99,102,241,.07);transition:flex .25s,width .25s}
.comm-split.has-detail .list-col{flex:none;width:360px;min-width:360px;flex-shrink:0}

/* Sub-banner */
.sub-banner{flex-shrink:0;background:linear-gradient(135deg,#4c1d95,#7c3aed 55%,#6366f1);height:52px;border-radius:13px 13px 0 0}

/* Header */
.list-top{flex-shrink:0;padding:12px 14px 10px;background:#fff;border-bottom:1px solid rgba(196,181,253,.12)}
.sub-identity{display:flex;align-items:center;gap:10px;margin-bottom:6px}
.sub-icon{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#6366f1);border:3px solid #fff;margin-top:-20px;display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 3px 10px rgba(124,58,237,.3)}
.sub-name{font-size:.88rem;font-weight:800;color:#1e1b2e}
.sub-slug{font-size:.7rem;color:#a78bfa;font-weight:600}
.comm-desc{font-size:.73rem;color:#6b7280;margin-bottom:8px}

/* Sort bar - segmented pills */
.sort-bar{display:flex;gap:0;margin-bottom:8px;background:#f5f3ff;border-radius:10px;padding:3px}
.sort-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:.3rem .5rem;border-radius:8px;font-size:.75rem;font-weight:700;border:none;color:#7c3aed;background:transparent;cursor:pointer;text-decoration:none;transition:all .12s}
.sort-btn:hover{background:rgba(124,58,237,.1);color:#4c1d95}
.sort-btn.active{background:#fff;color:#4c1d95;box-shadow:0 1px 4px rgba(99,102,241,.15)}

/* Category chips */
.cat-bar{display:flex;gap:4px;flex-wrap:wrap}
.cat-chip{padding:.22rem .62rem;border-radius:9999px;font-size:.68rem;font-weight:700;border:1px solid rgba(196,181,253,.3);color:#7c3aed;background:#f5f3ff;cursor:pointer;text-decoration:none;transition:all .12s}
.cat-chip:hover{border-color:#7c3aed;color:#4c1d95;background:#ede9fe}
.cat-chip.active{border-color:transparent;color:#fff}

/* Post list scroll */
.list-scroll{flex:1;overflow-y:auto;padding:8px;background:#f9f8ff}
.list-scroll::-webkit-scrollbar{width:4px}
.list-scroll::-webkit-scrollbar-track{background:transparent}
.list-scroll::-webkit-scrollbar-thumb{background:rgba(124,58,237,.2);border-radius:2px}
.list-scroll::-webkit-scrollbar-thumb:hover{background:rgba(124,58,237,.35)}

/* Full-width centering */
.comm-split:not(.has-detail) .list-top{display:flex;flex-direction:column;align-items:center;padding:12px 20px 10px}
.comm-split:not(.has-detail) .sub-identity,
.comm-split:not(.has-detail) .comm-desc,
.comm-split:not(.has-detail) .sort-bar,
.comm-split:not(.has-detail) .cat-bar{width:100%;max-width:700px}
.comm-split:not(.has-detail) .list-scroll{display:flex;flex-direction:column;align-items:center;padding:10px 20px 14px;background:#f9f8ff}
.comm-split:not(.has-detail) .post-card{width:100%;max-width:700px;margin:0 0 8px}
.comm-split:not(.has-detail) .empty-state{max-width:700px;width:100%}

/* Post card */
.post-card{display:flex;background:#fff;border:1px solid rgba(196,181,253,.2);border-radius:12px;margin:0 0 8px;overflow:hidden;cursor:pointer;transition:all .15s;box-shadow:0 1px 3px rgba(99,102,241,.05)}
.post-card:hover{border-color:rgba(124,58,237,.4);box-shadow:0 3px 10px rgba(99,102,241,.1);transform:translateY(-1px)}
.post-card.selected{border-color:#7c3aed;box-shadow:0 3px 10px rgba(124,58,237,.18)}

/* Vote col */
.lv-col{display:flex;flex-direction:column;align-items:center;gap:2px;padding:.6rem .4rem;background:#f5f3ff;border-right:1px solid rgba(196,181,253,.15);min-width:38px;flex-shrink:0}
.lv-btn{width:22px;height:22px;border-radius:6px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#a78bfa;transition:all .1s}
.lv-btn.up:hover,.lv-btn.up.active{color:#7c3aed;background:rgba(124,58,237,.12)}
.lv-btn.down:hover,.lv-btn.down.active{color:#6366f1;background:rgba(99,102,241,.12)}
.lv-score{font-size:.72rem;font-weight:800;color:#4c1d95;min-width:16px;text-align:center;line-height:1}
.lv-score.pos{color:#7c3aed}
.lv-score.neg{color:#6366f1}

/* Card body */
.post-body{flex:1;padding:.6rem .85rem .65rem;min-width:0}
.post-meta{display:flex;align-items:center;gap:.3rem;margin-bottom:.25rem;flex-wrap:wrap}
.p-cat{font-size:.6rem;font-weight:700;padding:.08rem .4rem;border-radius:9999px;color:#fff;flex-shrink:0}
.p-author{font-size:.67rem;color:#6b7280;font-weight:500}
.p-time{font-size:.65rem;color:#9ca3af}
.p-title{font-size:.9rem;font-weight:700;color:#1e1b2e;line-height:1.3;margin-bottom:.18rem;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.post-card.selected .p-title{color:#7c3aed}
.p-excerpt{font-size:.73rem;color:#6b7280;line-height:1.5;margin-bottom:.3rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.p-footer{display:flex;align-items:center;gap:.45rem}
.p-stat{display:inline-flex;align-items:center;gap:.2rem;font-size:.68rem;color:#9ca3af;font-weight:600}
.p-del{font-size:.63rem;color:#9ca3af;background:none;border:none;cursor:pointer;padding:.08rem .28rem;border-radius:6px;transition:all .1s;margin-left:auto}
.p-del:hover{background:#fee2e2;color:#ef4444}

/* ── Right detail panel ── */
.detail-col{flex:none;width:0;min-width:0;overflow:hidden;background:transparent;display:flex;flex-direction:column;transition:flex .25s,width .25s}
.comm-split.has-detail .detail-col{flex:1;width:auto;background:#fff;border-radius:14px;border:1px solid rgba(196,181,253,.2);box-shadow:0 1px 6px rgba(99,102,241,.07)}

/* Sticky top */
.dp-sticky-top{flex-shrink:0;border-bottom:1px solid rgba(196,181,253,.12);background:#fff;border-radius:14px 14px 0 0;overflow:hidden}

/* Comments scroll */
.dp-scroll-area{flex:1;min-height:0;overflow-y:auto;background:#f9f8ff;padding:10px 12px 16px}
.dp-scroll-area::-webkit-scrollbar{width:4px}
.dp-scroll-area::-webkit-scrollbar-track{background:transparent}
.dp-scroll-area::-webkit-scrollbar-thumb{background:rgba(124,58,237,.2);border-radius:2px}
.dp-scroll-area::-webkit-scrollbar-thumb:hover{background:rgba(124,58,237,.35)}

/* Empty / loading */
.detail-empty{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.6rem;padding:2rem;text-align:center}
.detail-empty-icon{font-size:2.5rem;opacity:.45}
.detail-empty-text{font-size:.9rem;font-weight:700;color:#4c1d95}
.detail-empty-sub{font-size:.78rem;color:#9ca3af;line-height:1.6}
.detail-loading{flex:1;display:flex;align-items:center;justify-content:center}
.spinner{width:28px;height:28px;border:3px solid rgba(196,181,253,.25);border-top-color:#7c3aed;border-radius:50%;animation:spin .7s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

/* ══ Detail partial ══ */
.dp-root{display:flex;flex-direction:column;flex:1;min-height:0;overflow:hidden}
.dp-sticky-top .dp-post{margin:0;border-radius:0;border:none}
.dp-sticky-top .dp-reaction-bar{margin:0 14px;padding:.5rem 0 .6rem}

/* Post */
.dp-post{background:#fff;overflow:hidden}
.dp-post-inner{display:flex}
.dp-vote-col{display:flex;flex-direction:column;align-items:center;gap:2px;padding:1rem .5rem;background:#f5f3ff;border-right:1px solid rgba(196,181,253,.15);min-width:46px;flex-shrink:0}
.dp-vbtn{width:28px;height:28px;border-radius:8px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#a78bfa;transition:all .1s}
.dp-vbtn.up:hover,.dp-vbtn.up.active{color:#7c3aed;background:rgba(124,58,237,.12)}
.dp-vbtn.down:hover,.dp-vbtn.down.active{color:#6366f1;background:rgba(99,102,241,.12)}
.dp-vscore{font-size:.82rem;font-weight:800;color:#4c1d95;text-align:center}
.dp-vscore.pos{color:#7c3aed}
.dp-vscore.neg{color:#6366f1}
.dp-post-body{flex:1;padding:1rem 1.2rem .85rem;min-width:0}
.dp-meta{display:flex;align-items:center;gap:.35rem;margin-bottom:.4rem;flex-wrap:wrap}
.dp-pin{font-size:.66rem;color:#f59e0b;font-weight:700}
.dp-cat-badge{font-size:.62rem;font-weight:700;padding:.1rem .45rem;border-radius:9999px;color:#fff}
.dp-author{font-size:.74rem;color:#6b7280;font-weight:500}
.dp-time{font-size:.68rem;color:#9ca3af}
.dp-del-btn{font-size:.68rem;color:#9ca3af;background:none;border:none;cursor:pointer;padding:.08rem .28rem;border-radius:6px;transition:all .1s}
.dp-del-btn:hover{background:#fee2e2;color:#ef4444}
.dp-title{font-size:1.1rem;font-weight:800;color:#1e1b2e;line-height:1.4;margin-bottom:.6rem}
.dp-content{font-size:.875rem;color:#374151;line-height:1.75;white-space:pre-wrap}

/* Post action bar */
.dp-post-actions{display:flex;align-items:center;gap:2px;padding:.45rem .6rem;border-top:1px solid rgba(196,181,253,.1);background:#faf9ff}
.dp-act-btn{display:inline-flex;align-items:center;gap:.25rem;padding:.3rem .7rem;border-radius:8px;border:none;background:transparent;font-size:.72rem;font-weight:700;color:#7c3aed;cursor:pointer;font-family:inherit;transition:background .1s}
.dp-act-btn:hover{background:#f5f3ff;color:#4c1d95}

/* Reaction bar */
.dp-reaction-bar{display:flex;flex-wrap:wrap;gap:.28rem;padding:.5rem 0 .45rem;border-top:1px solid rgba(196,181,253,.1)}
.dp-react-btn{display:inline-flex;align-items:center;gap:.2rem;padding:.22rem .5rem;border-radius:9999px;border:1px solid rgba(196,181,253,.25);background:#f5f3ff;cursor:pointer;font-family:inherit;transition:all .12s;min-width:36px}
.dp-react-btn:hover{background:#ede9fe;border-color:#a78bfa}
.dp-react-btn.active{background:#7c3aed;border-color:#7c3aed}
.dp-react-icon{font-size:1rem;line-height:1}
.dp-react-cnt{font-size:.7rem;font-weight:800;color:#7c3aed;min-width:8px}
.dp-react-cnt.has-count{color:#7c3aed}
.dp-react-btn.active .dp-react-cnt{color:#fff}

/* Comment input */
.dp-comment-box{background:#fff;border:1px solid rgba(196,181,253,.2);border-radius:12px;padding:.8rem;margin-bottom:10px}
.dp-textarea{width:100%;padding:.55rem .7rem;border:1px solid rgba(196,181,253,.25);border-radius:10px;font-size:.84rem;color:#1e1b2e;outline:none;font-family:inherit;resize:vertical;background:#faf9ff;transition:border-color .15s}
.dp-textarea:focus{border-color:#7c3aed;background:#fff}
.dp-comment-btn{margin-top:.4rem;padding:.42rem 1rem;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border:none;border-radius:9999px;font-size:.78rem;font-weight:700;cursor:pointer;transition:opacity .15s;box-shadow:0 2px 6px rgba(124,58,237,.3)}
.dp-comment-btn:hover{opacity:.88}
.dp-cancel-btn{padding:.38rem .75rem;background:transparent;color:#9ca3af;border:1px solid rgba(196,181,253,.3);border-radius:9999px;font-size:.72rem;font-weight:600;cursor:pointer;transition:all .1s;font-family:inherit}
.dp-cancel-btn:hover{background:#f5f3ff}

/* Comments */
.dp-comments-wrap{margin-top:2px}
.dp-comments-hd{font-size:.8rem;font-weight:800;color:#4c1d95;padding:.35rem 0;margin-bottom:.5rem;border-bottom:2px solid rgba(196,181,253,.2)}
.dp-comment-card{background:#fff;border:1px solid rgba(196,181,253,.15);border-radius:12px;padding:.7rem .9rem;margin-bottom:8px;box-shadow:0 1px 3px rgba(99,102,241,.04)}
.dp-c-header{display:flex;align-items:center;gap:.3rem;margin-bottom:.3rem}
.dp-c-avatar{width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#7c3aed,#6366f1);display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:700;color:#fff;flex-shrink:0}
.dp-c-name{font-size:.74rem;font-weight:700;color:#1e1b2e}
.dp-c-time{font-size:.66rem;color:#9ca3af}
.dp-c-content{font-size:.83rem;color:#374151;line-height:1.6;white-space:pre-wrap;margin-bottom:.35rem}
.dp-c-actions{display:flex;align-items:center;gap:.35rem}
.dp-cv-wrap{display:inline-flex;align-items:center;gap:2px;border-radius:6px}
.dp-cvbtn{width:18px;height:18px;border-radius:4px;border:none;background:transparent;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#a78bfa;transition:color .1s}
.dp-cvbtn.up:hover,.dp-cvbtn.up.active{color:#7c3aed}
.dp-cvbtn.down:hover,.dp-cvbtn.down.active{color:#6366f1}
.dp-cvscore{font-size:.67rem;font-weight:800;color:#4c1d95;min-width:12px;text-align:center}
.dp-reply-toggle{font-size:.7rem;color:#7c3aed;background:none;border:none;cursor:pointer;padding:.15rem .4rem;border-radius:6px;transition:all .1s;font-family:inherit;font-weight:700}
.dp-reply-toggle:hover{background:#f5f3ff;color:#4c1d95}
.dp-cdel-btn{font-size:.67rem;color:#9ca3af;background:none;border:none;cursor:pointer;padding:.08rem .25rem;border-radius:4px;transition:all .1s;font-family:inherit}
.dp-cdel-btn:hover{background:#fee2e2;color:#ef4444}
.dp-reply-form-wrap{margin-top:.45rem;padding:.6rem .7rem;background:#f5f3ff;border-radius:10px;border:1px solid rgba(196,181,253,.2)}
.dp-reply-textarea{width:100%;padding:.42rem .6rem;border:1px solid rgba(196,181,253,.25);border-radius:8px;font-size:.79rem;color:#1e1b2e;outline:none;font-family:inherit;resize:vertical;background:#fff;transition:border-color .15s}
.dp-reply-textarea:focus{border-color:#7c3aed}
.dp-replies{margin-top:.5rem;padding-left:.9rem;border-left:2px solid rgba(196,181,253,.25);display:flex;flex-direction:column;gap:6px}
.dp-reply-card{background:#f9f8ff;border-radius:10px;padding:.55rem .75rem;border:1px solid rgba(196,181,253,.1)}
.dp-no-comment{text-align:center;padding:1.5rem;font-size:.83rem;color:#9ca3af}

/* Reaction pills (list card) */
.p-reactions{display:flex;flex-wrap:wrap;gap:.2rem;margin-top:.3rem}
.p-rpill{display:inline-flex;align-items:center;gap:.1rem;font-size:.63rem;color:#7c3aed;background:#f5f3ff;border:1px solid rgba(196,181,253,.25);border-radius:9999px;padding:.08rem .3rem}

/* Pagination */
.list-pagination{flex-shrink:0;padding:8px 12px;border-top:1px solid rgba(196,181,253,.12);background:#fff}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(15,7,40,.6);backdrop-filter:blur(4px);z-index:1000;display:flex;align-items:center;justify-content:center}
.modal-box{background:#fff;border-radius:18px;padding:1.8rem;width:100%;max-width:540px;box-shadow:0 24px 60px rgba(99,102,241,.22)}
.modal-title{font-size:1.05rem;font-weight:800;color:#1e1b2e;margin-bottom:1rem}
.modal-cats{display:flex;gap:.28rem;flex-wrap:wrap;margin-bottom:.75rem}
.modal-cat{padding:.28rem .65rem;border-radius:9999px;font-size:.73rem;font-weight:700;border:1px solid rgba(196,181,253,.3);color:#7c3aed;background:#f5f3ff;cursor:pointer;transition:all .12s}
.modal-cat:hover{border-color:#a78bfa}
.modal-cat.selected{color:#fff;border-color:transparent}
.modal-input{width:100%;padding:.65rem .85rem;border:1px solid rgba(196,181,253,.25);border-radius:10px;font-size:.875rem;color:#1e1b2e;outline:none;font-family:inherit;margin-bottom:.65rem;background:#faf9ff;transition:border-color .15s}
.modal-input:focus{border-color:#7c3aed;background:#fff}
.modal-textarea{width:100%;padding:.65rem .85rem;border:1px solid rgba(196,181,253,.25);border-radius:10px;font-size:.875rem;color:#1e1b2e;outline:none;font-family:inherit;resize:vertical;min-height:110px;margin-bottom:.65rem;background:#faf9ff;transition:border-color .15s}
.modal-textarea:focus{border-color:#7c3aed;background:#fff}
.modal-actions{display:flex;gap:.4rem;justify-content:flex-end}
.btn-cancel{padding:.52rem .95rem;border-radius:9999px;border:1px solid rgba(196,181,253,.3);background:transparent;color:#6b7280;font-size:.83rem;font-weight:700;cursor:pointer;transition:all .12s}
.btn-cancel:hover{background:#f5f3ff}
.btn-submit{padding:.52rem 1.3rem;border-radius:9999px;border:none;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;font-size:.83rem;font-weight:700;cursor:pointer;transition:opacity .15s;box-shadow:0 2px 8px rgba(124,58,237,.3)}
.btn-submit:hover{opacity:.88}

.empty-state{text-align:center;padding:2.5rem 1rem;color:#9ca3af}
</style>

{{-- ════ Split Layout ════ --}}
<div class="comm-split">

    {{-- ──────── 왼쪽: 목록 패널 ──────── --}}
    <div class="list-col">

        {{-- 배너 --}}
        <div class="sub-banner"></div>

        {{-- 헤더 --}}
        <div class="list-top">
            <div class="sub-identity">
                <div class="sub-icon">
                    <svg width="18" height="18" viewBox="0 0 20 20" fill="#fff"><path d="M10 .4C4.698.4.4 4.698.4 10s4.298 9.6 9.6 9.6 9.6-4.298 9.6-9.6S15.302.4 10 .4zm4.756 10.985a.733.733 0 01-.04.087c-.637 1.388-2.354 2.354-4.316 2.354-1.962 0-3.679-.966-4.316-2.354a.746.746 0 01.038-.087.68.68 0 01.913-.287.675.675 0 01.313.374c.406.897 1.697 1.554 3.052 1.554s2.646-.657 3.052-1.554a.675.675 0 01.313-.374.68.68 0 01.913.287zm-.54-2.52a.9.9 0 11-1.8 0 .9.9 0 011.8 0zm-5.832 0a.9.9 0 11-1.8 0 .9.9 0 011.8 0z"/></svg>
                </div>
                <div>
                    <div class="sub-name">{{ $company }}</div>
                    <div class="sub-slug">{{ __('messages.community') }}</div>
                </div>
            </div>
            <p class="comm-desc">{{ __('messages.community_desc') }}</p>

            {{-- 정렬 --}}
            <div class="sort-bar">
                @foreach(['hot' => __('messages.sort_hot'), 'new' => __('messages.sort_new'), 'top' => __('messages.sort_top')] as $key => $label)
                <a href="{{ route('community.index', array_merge(request()->query(), ['sort' => $key])) }}"
                   class="sort-btn {{ $sort === $key ? 'active' : '' }}"
                   style="text-decoration:none;">{{ $label }}</a>
                @endforeach
            </div>

            {{-- 카테고리 --}}
            <div class="cat-bar">
                <a href="{{ route('community.index', array_merge(request()->query(), ['category' => null])) }}"
                   class="cat-chip {{ !$category ? 'active' : '' }}"
                   style="{{ !$category ? 'background:#4c1d95;' : '' }}">{{ __('common.all') }}</a>
                @foreach(['general' => [__('messages.cat_general'),'#6b7280'], 'question' => [__('messages.cat_question'),'#0079D3'], 'idea' => [__('messages.cat_idea'),'#10b981'], 'announcement' => [__('messages.cat_announcement'),'#ef4444'], 'technical' => [__('messages.cat_technical'),'#8b5cf6']] as $key => [$label, $color])
                <a href="{{ route('community.index', array_merge(request()->query(), ['category' => $key])) }}"
                   class="cat-chip {{ $category === $key ? 'active' : '' }}"
                   style="{{ $category === $key ? 'background:'.$color.';' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        {{-- 게시물 목록 --}}
        <div class="list-scroll" id="post-list">
            @forelse($posts as $post)
            @php $uv = $userVotes[$post->id] ?? 0; @endphp
            <div class="post-card" data-post-id="{{ $post->id }}" data-detail-url="{{ route('community.detail', $post) }}">
                {{-- 투표 --}}
                <div class="lv-col" onclick="event.stopPropagation()">
                    <button class="lv-btn up {{ $uv == 1 ? 'active' : '' }}"
                            data-list-vote="{{ $post->id }}" data-val="1"
                            data-url="{{ route('community.vote', $post) }}">
                        <svg width="11" height="11" fill="{{ $uv == 1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <span class="lv-score {{ $uv == 1 ? 'pos' : ($uv == -1 ? 'neg' : '') }}" id="ls-{{ $post->id }}">{{ $post->votes }}</span>
                    <button class="lv-btn down {{ $uv == -1 ? 'active' : '' }}"
                            data-list-vote="{{ $post->id }}" data-val="-1"
                            data-url="{{ route('community.vote', $post) }}">
                        <svg width="11" height="11" fill="{{ $uv == -1 ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                {{-- 본문 --}}
                <div class="post-body">
                    <div class="post-meta">
                        @if($post->pinned)<span style="font-size:.62rem;color:#f59e0b;">📌</span>@endif
                        <span class="p-cat" style="background:{{ $post->category_color }}">{{ $post->category_label }}</span>
                        <span class="p-author">{{ $post->user->name }}</span>
                        <span class="p-time">· {{ $post->created_at->diffForHumans() }}</span>
                    </div>
                    <span class="p-title">{{ $post->title }}</span>
                    <p class="p-excerpt">{{ $post->content }}</p>
                    <div class="p-footer">
                        <span class="p-stat">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            {{ $post->allComments->count() }}
                        </span>
                        @if(auth()->id() === $post->user_id || auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('community.destroy', $post) }}"
                              onclick="event.stopPropagation()"
                              onsubmit="return confirm('{{ __('messages.delete_confirm') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-del">{{ __('common.delete') }}</button>
                        </form>
                        @endif
                    </div>
                    {{-- 리액션 미리보기 --}}
                    @php
                        $emojiMap = ['like'=>'👍','heart'=>'❤️','laugh'=>'😂','wow'=>'😮','sad'=>'😢','fire'=>'🔥'];
                        $postReactions = $reactionCounts[$post->id] ?? collect();
                    @endphp
                    @if($postReactions->count())
                    <div class="p-reactions" onclick="event.stopPropagation()">
                        @foreach($postReactions->sortByDesc('cnt') as $r)
                        <span class="p-rpill">{{ $emojiMap[$r->emoji] ?? '' }} {{ $r->cnt }}</span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @empty
            <div class="empty-state">
                <div style="font-size:2.5rem;margin-bottom:.6rem;opacity:.6">📭</div>
                <div style="font-size:.88rem;font-weight:700;color:#4c1d95;">{{ __('messages.no_posts_yet') }}</div>
                <div style="font-size:.76rem;color:var(--color-text-tertiary);margin-top:.3rem;">{{ __('messages.write_first_post') }}</div>
            </div>
            @endforelse
        </div>

        {{-- 페이지네이션 --}}
        @if($posts->hasPages())
        <div class="list-pagination">{{ $posts->links() }}</div>
        @endif
    </div>

    {{-- ──────── 오른쪽: 상세 패널 ──────── --}}
    <div class="detail-col" id="detail-panel">
        <div class="detail-empty" id="detail-empty">
            <div class="detail-empty-icon">👈</div>
            <div class="detail-empty-text">{{ __('messages.select_post_hint') }}</div>
            <div class="detail-empty-sub">{{ __('messages.select_post_sub') }}<br>{{ __('messages.select_post_sub2') }}</div>
        </div>
    </div>

</div>

{{-- 글쓰기 모달 --}}
<div id="compose-modal" style="display:none;" class="modal-overlay" onclick="if(event.target===this)closeCompose()">
    <div class="modal-box">
        <div class="modal-title">{{ __('messages.new_post_write') }}</div>
        <form method="POST" action="{{ route('community.store') }}">
            @csrf
            <input type="hidden" name="category" id="selected-cat" value="general">
            <div class="modal-cats">
                @foreach(['general' => [__('messages.cat_general'),'#6b7280'], 'question' => [__('messages.cat_question'),'#3b82f6'], 'idea' => [__('messages.cat_idea'),'#10b981'], 'announcement' => [__('messages.cat_announcement'),'#ef4444'], 'technical' => [__('messages.cat_technical'),'#8b5cf6']] as $key => [$label, $color])
                <button type="button" class="modal-cat {{ $key === 'general' ? 'selected' : '' }}"
                        style="{{ $key === 'general' ? 'background:'.$color.';color:#fff;border-color:transparent;' : '' }}"
                        data-key="{{ $key }}" data-color="{{ $color }}"
                        onclick="selectCat(this)">{{ $label }}</button>
                @endforeach
            </div>
            <input type="text" name="title" class="modal-input" placeholder="{{ __('messages.post_title_placeholder') }}" required maxlength="200">
            <textarea name="content" class="modal-textarea" placeholder="{{ __('messages.post_content_placeholder') }}" required maxlength="10000"></textarea>
            <div class="modal-actions">
                <button type="button" class="btn-cancel" onclick="closeCompose()">{{ __('common.cancel') }}</button>
                <button type="submit" class="btn-submit">{{ __('common.register') }}</button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let currentPostId = null;

// ── height 체인 고정 (split layout) ──
document.documentElement.style.height = '100%';
document.body.style.height            = '100%';
document.body.style.overflow          = 'hidden';
const outerWrap = document.body.querySelector('.min-h-screen');
if (outerWrap) {
    outerWrap.style.height    = '100%';
    outerWrap.style.minHeight = '0';
    outerWrap.style.overflow  = 'hidden';
}
const main = document.querySelector('main');
main.style.display       = 'flex';
main.style.flexDirection = 'column';
main.style.overflow      = 'hidden';
main.style.padding       = '0';
main.style.minHeight     = '0';

// ── 게시물 카드 클릭 → 상세 로드 ──
document.getElementById('post-list').addEventListener('click', async function(e) {
    const card = e.target.closest('.post-card');
    if (!card) return;
    if (e.target.closest('button[type="submit"], form')) return;

    const postId = card.dataset.postId;
    const url    = card.dataset.detailUrl;

    if (currentPostId === postId) return;

    document.querySelectorAll('.post-card').forEach(c => c.classList.remove('selected'));
    card.classList.add('selected');

    loadDetail(postId, url);
});

// ── 상세 패널 로드 ──
async function loadDetail(postId, url) {
    currentPostId = postId;
    const panel = document.getElementById('detail-panel');

    document.querySelector('.comm-split').classList.add('has-detail');

    panel.innerHTML = '<div class="detail-loading"><div class="spinner"></div></div>';

    return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
        .then(r => r.text())
        .then(html => {
            panel.innerHTML = html;
        })
        .catch(() => {
            panel.innerHTML = '<div class="detail-empty"><div class="detail-empty-icon">⚠️</div><div class="detail-empty-text">{{ __("messages.load_failed") }}</div></div>';
        });
}

// ── 상세 패널 이벤트 위임 ──
const detailPanel = document.getElementById('detail-panel');

detailPanel.addEventListener('click', async function(e) {
    const voteBtn = e.target.closest('[data-vote]');
    if (voteBtn) { e.preventDefault(); handleVote(voteBtn); return; }

    const reactBtn = e.target.closest('.dp-react-btn[data-emoji]');
    if (reactBtn) { e.preventDefault(); handleReact(reactBtn); return; }

    const replyToggle = e.target.closest('[data-reply]');
    if (replyToggle) {
        const id = replyToggle.dataset.reply;
        const wrap = document.getElementById('reply-wrap-' + id);
        if (wrap) {
            const visible = wrap.style.display === 'block';
            wrap.style.display = visible ? 'none' : 'block';
            if (!visible) wrap.querySelector('textarea')?.focus();
        }
        return;
    }
    const replyCancel = e.target.closest('[data-reply-cancel]');
    if (replyCancel) {
        const id = replyCancel.dataset.replyCancel;
        const wrap = document.getElementById('reply-wrap-' + id);
        if (wrap) wrap.style.display = 'none';
        return;
    }
});

// 리액션 처리
async function handleReact(btn) {
    const bar   = btn.closest('.dp-reaction-bar');
    const url   = bar.dataset.reactUrl;
    const emoji = btn.dataset.emoji;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ emoji }),
    })
    .then(r => r.json())
    .then(data => {
        bar.querySelectorAll('.dp-react-btn').forEach(b => {
            const key   = b.dataset.emoji;
            const cnt   = data.counts[key] || 0;
            const cntEl = b.querySelector('.dp-react-cnt');

            cntEl.textContent = cnt > 0 ? cnt : '';
            cntEl.classList.toggle('has-count', cnt > 0);
            b.classList.toggle('active', key === emoji && data.reacted);
        });

        updateListReactions(currentPostId, data.counts);
    });
}

const EMOJI_MAP = { like:'👍', heart:'❤️', laugh:'😂', wow:'😮', sad:'😢', fire:'🔥' };
async function updateListReactions(postId, counts) {
    const card = document.querySelector(`.post-card[data-post-id="${postId}"]`);
    if (!card) return;

    let reactionsDiv = card.querySelector('.p-reactions');
    const total = Object.values(counts).reduce((a, b) => a + b, 0);

    if (total === 0) {
        if (reactionsDiv) reactionsDiv.remove();
        return;
    }

    if (!reactionsDiv) {
        reactionsDiv = document.createElement('div');
        reactionsDiv.className = 'p-reactions';
        reactionsDiv.addEventListener('click', e => e.stopPropagation());
        card.querySelector('.post-body').appendChild(reactionsDiv);
    }

    reactionsDiv.innerHTML = Object.entries(counts)
        .filter(([, v]) => v > 0)
        .sort(([, a], [, b]) => b - a)
        .map(([key, cnt]) => `<span class="p-rpill">${EMOJI_MAP[key]} ${cnt}</span>`)
        .join('');
}

// 투표 처리
async function handleVote(btn) {
    const url  = btn.dataset.url;
    const val  = parseInt(btn.dataset.val);
    const type = btn.dataset.vote;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ value: val }),
    })
    .then(r => r.json())
    .then(data => {
        const wrap = btn.closest('.dp-vote-col, .dp-cv-wrap');
        const upBtn   = wrap.querySelector('.up');
        const downBtn = wrap.querySelector('.down');

        if (type === 'post') {
            const scoreEl = detailPanel.querySelector('[data-score-post]');
            if (scoreEl) {
                scoreEl.textContent = data.votes;
                scoreEl.className = 'dp-vscore' + (data.votes > 0 ? ' pos' : data.votes < 0 ? ' neg' : '');
            }
            const listScore = document.getElementById('ls-' + currentPostId);
            if (listScore) {
                listScore.textContent = data.votes;
                listScore.className = 'lv-score' + (data.votes > 0 ? ' pos' : data.votes < 0 ? ' neg' : '');
            }
        } else {
            const scoreEl = btn.closest('.dp-cv-wrap')?.querySelector('.dp-cvscore');
            if (scoreEl) scoreEl.textContent = data.votes;
        }

        upBtn.classList.toggle('active', data.userVote === 1);
        downBtn.classList.toggle('active', data.userVote === -1);
        upBtn.querySelector('svg').setAttribute('fill', data.userVote === 1 ? 'currentColor' : 'none');
        downBtn.querySelector('svg').setAttribute('fill', data.userVote === -1 ? 'currentColor' : 'none');
    });
}

// 댓글 / 답글 폼 제출 (AJAX)
detailPanel.addEventListener('submit', async function(e) {
    const form = e.target;
    if (!form.classList.contains('js-comment-form')) return;
    e.preventDefault();

    const url    = form.dataset.url;
    const postId = form.dataset.post;
    const fd     = new FormData(form);
    const btn    = form.querySelector('[type="submit"]');
    if (btn) btn.disabled = true;

    fetch(url, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': CSRF,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
        body: fd,
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        form.reset();
        const detailUrl = document.querySelector(`.post-card[data-post-id="${postId}"]`)?.dataset.detailUrl;
        if (detailUrl) {
            loadDetail(postId, detailUrl).then(() => {
                const sa = detailPanel.querySelector('.dp-scroll-area');
                if (sa) sa.scrollTop = sa.scrollHeight;
            });
        }
    })
    .catch(() => {})
    .finally(() => { if (btn) btn.disabled = false; });
});

// 목록 투표 버튼
document.getElementById('post-list').addEventListener('click', async function(e) {
    const btn = e.target.closest('[data-list-vote]');
    if (!btn) return;
    e.stopPropagation();

    const postId = btn.dataset.listVote;
    const val    = parseInt(btn.dataset.val);
    const url    = btn.dataset.url;

    fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ value: val }),
    })
    .then(r => r.json())
    .then(data => {
        const col   = btn.closest('.lv-col');
        const upBtn = col.querySelector('.lv-btn.up');
        const dnBtn = col.querySelector('.lv-btn.down');
        const score = document.getElementById('ls-' + postId);

        score.textContent = data.votes;
        score.className = 'lv-score' + (data.votes > 0 ? ' pos' : data.votes < 0 ? ' neg' : '');
        upBtn.classList.toggle('active', data.userVote === 1);
        dnBtn.classList.toggle('active', data.userVote === -1);
        upBtn.querySelector('svg').setAttribute('fill', data.userVote === 1 ? 'currentColor' : 'none');
        dnBtn.querySelector('svg').setAttribute('fill', data.userVote === -1 ? 'currentColor' : 'none');

        if (currentPostId === postId) {
            const dpScore = detailPanel.querySelector('[data-score-post]');
            if (dpScore) {
                dpScore.textContent = data.votes;
                dpScore.className = 'dp-vscore' + (data.votes > 0 ? ' pos' : data.votes < 0 ? ' neg' : '');
            }
        }
    });
});

// ── 글쓰기 모달 ──
async function openCompose() {
    document.getElementById('compose-modal').style.display = 'flex';
}
async function closeCompose() {
    document.getElementById('compose-modal').style.display = 'none';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCompose(); });

async function selectCat(btn) {
    document.querySelectorAll('.modal-cat').forEach(b => {
        b.classList.remove('selected');
        b.style.background = b.style.color = b.style.borderColor = '';
    });
    btn.classList.add('selected');
    btn.style.background = btn.dataset.color;
    btn.style.color = '#fff';
    btn.style.borderColor = 'transparent';
    document.getElementById('selected-cat').value = btn.dataset.key;
}
</script>
@endsection
