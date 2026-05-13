@extends('layouts.app')

@section('title', '#' . $issue->id . ' ' . $issue->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.issues.index', $project) }}" class="hover:text-indigo-500 transition-colors">이슈</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">#{{ $issue->id }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    @if(!$issue->isResolved())
    <button onclick="openResolveModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">해결 처리</button>
    @endif
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'issues'])

@php
    $sc = $issue->status_color;
    $pc = $issue->priority_color;
    $svc = $issue->severity_color;
@endphp

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

{{-- ─── LEFT: 메인 ─────────────────────────────────────── --}}
<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- 헤더 카드 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:24px;">
        <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;">
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap;">
                    <span style="font-size:12px;color:#9ca3af;">#{{ $issue->id }}</span>
                    <span style="padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['text'] }};" id="status-badge">{{ $issue->status }}</span>
                    <span style="padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $pc['bg'] }};color:{{ $pc['text'] }};">{{ $issue->priority_label }}</span>
                    @if($issue->severity)
                    <span style="padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600;background:{{ $svc['bg'] }};color:{{ $svc['text'] }};">{{ $issue->severity }}</span>
                    @endif
                    <span style="font-size:12px;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:4px;">{{ $issue->category }}</span>
                    @if($issue->environment)
                    <span style="font-size:12px;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:4px;">{{ $issue->environment }}</span>
                    @endif
                </div>
                <h1 style="font-size:20px;font-weight:700;color:#111827;margin:0;" id="issue-title-display">{{ $issue->title }}</h1>
            </div>
            @if($isManager || auth()->id() === $issue->reporter_id)
            <button onclick="openEditModal()" style="padding:6px 14px;font-size:12px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;flex-shrink:0;">수정</button>
            @endif
        </div>

        @if($issue->description)
        <div style="background:#f9fafb;border-radius:8px;padding:14px;font-size:13px;color:#374151;white-space:pre-line;line-height:1.7;" id="issue-desc-display">{{ $issue->description }}</div>
        @endif

        @if($issue->tags)
        <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:12px;">
            @foreach($issue->tags as $tag)
            <span style="font-size:11px;color:#6b7280;background:#f3f4f6;padding:2px 8px;border-radius:4px;">{{ $tag }}</span>
            @endforeach
        </div>
        @endif
    </div>

    {{-- 해결 정보 --}}
    @if($issue->isResolved() && $issue->resolution)
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <span style="font-size:14px;font-weight:700;color:#15803d;">✓ 해결 완료</span>
            @if($issue->resolvedBy)
            <span style="font-size:12px;color:#6b7280;">{{ $issue->resolvedBy->name }} · {{ $issue->resolved_at?->format('Y-m-d H:i') }}</span>
            @endif
        </div>
        <p style="font-size:13px;color:#374151;white-space:pre-line;margin:0;">{{ $issue->resolution }}</p>
    </div>
    @endif

    {{-- SM 모드: SLA 정보 --}}
    @if($project->sm_mode_enabled)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">SLA 정보</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <p style="font-size:11px;color:#9ca3af;font-weight:600;margin:0 0 4px;">SLA 마감</p>
                <p style="font-size:13px;color:#111827;margin:0;" id="sla-due-display">
                    {{ $issue->sla_due ? $issue->sla_due->format('Y-m-d H:i') : '-' }}
                </p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;font-weight:600;margin:0 0 4px;">SLA 위반</p>
                <p style="font-size:13px;margin:0;" id="sla-breached-display">
                    @if($issue->sla_breached)
                    <span style="color:#dc2626;font-weight:600;">위반</span>
                    @else
                    <span style="color:#6b7280;">정상</span>
                    @endif
                </p>
            </div>
        </div>
        @if($isManager)
        <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
            <input type="datetime-local" id="sla-due-input" value="{{ $issue->sla_due ? $issue->sla_due->format('Y-m-d\TH:i') : '' }}"
                   style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
            <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#374151;">
                <input type="checkbox" id="sla-breached-check" {{ $issue->sla_breached ? 'checked' : '' }}>
                SLA 위반 표시
            </label>
            <button onclick="saveSla()"
                    style="padding:6px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">저장</button>
        </div>
        @endif
    </div>
    @endif

    {{-- 연결된 요구사항 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0;">연결된 요구사항</h3>
            @if($isManager && !$issue->linkedRequirement)
            <button onclick="document.getElementById('link-req-form').style.display='flex'"
                    style="font-size:11px;color:var(--t600);background:none;border:none;cursor:pointer;font-weight:600;">+ 연결</button>
            @endif
        </div>

        <div id="link-req-form" style="display:none;gap:8px;align-items:center;margin-bottom:12px;">
            <select id="link-req-select"
                    style="flex:1;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                <option value="">요구사항 선택...</option>
                @foreach($requirements as $req)
                <option value="{{ $req->id }}">{{ $req->title }}</option>
                @endforeach
            </select>
            <button onclick="linkReq()"
                    style="padding:7px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">연결</button>
            <button onclick="document.getElementById('link-req-form').style.display='none'"
                    style="padding:7px 10px;font-size:12px;color:#6b7280;background:#f3f4f6;border:none;border-radius:7px;cursor:pointer;">취소</button>
        </div>

        <div id="linked-req-display">
        @if($issue->linkedRequirement)
        <div style="display:flex;align-items:center;gap:8px;padding:10px 12px;background:#f9fafb;border-radius:8px;border:1px solid #e5e7eb;">
            <a href="{{ route('projects.requirements.show', [$project, $issue->linkedRequirement]) }}"
               style="flex:1;font-size:13px;font-weight:500;color:#111827;text-decoration:none;"
               onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#111827'">
                {{ $issue->linkedRequirement->title }}
            </a>
            @if($isManager)
            <button onclick="unlinkReq()"
                    style="font-size:11px;color:#9ca3af;background:none;border:none;cursor:pointer;"
                    onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">연결 해제</button>
            @endif
        </div>
        @else
        <p style="font-size:13px;color:#9ca3af;">연결된 요구사항이 없습니다.</p>
        @endif
        </div>
    </div>

    {{-- Q&A 출처 --}}
    @if($issue->convertedFromQuestion)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 10px;">Q&A에서 전환</h3>
        <a href="{{ route('projects.questions.show', $issue->convertedFromQuestion) }}"
           style="font-size:13px;color:var(--t600);text-decoration:none;"
           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
            {{ $issue->convertedFromQuestion->title }}
        </a>
    </div>
    @endif

    {{-- 댓글 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">댓글 ({{ $issue->comments->count() }})</h3>

        <div id="comments-list" style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
            @foreach($issue->comments as $comment)
            <div style="display:flex;gap:10px;padding:12px;background:#f9fafb;border-radius:8px;">
                <div style="width:30px;height:30px;background:#e0e7ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4f46e5;flex-shrink:0;">
                    {{ mb_substr($comment->author->name, 0, 1) }}
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ $comment->author->name }}</span>
                        <span style="font-size:11px;color:#9ca3af;">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0;white-space:pre-line;">{!! nl2br(e($comment->content)) !!}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div style="display:flex;gap:8px;align-items:flex-start;">
            <textarea id="comment-input" rows="2" placeholder="댓글 입력..."
                      style="flex:1;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:none;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            <button onclick="submitComment()"
                    style="padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">등록</button>
        </div>
    </div>

</div>

{{-- ─── RIGHT: 사이드바 ─────────────────────────────────────── --}}
<div style="display:flex;flex-direction:column;gap:14px;">

    {{-- 상태 변경 --}}
    @if($isManager || auth()->id() === $issue->reporter_id || auth()->id() === $issue->assignee_id)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">상태 변경</p>
        <select id="status-select" onchange="changeStatus(this.value)"
                style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;">
            @foreach(\App\Models\Issue::STATUS_LABELS as $val => $label)
            <option value="{{ $val }}" {{ $issue->status === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- 이슈 정보 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 12px;">이슈 정보</p>
        <div style="display:flex;flex-direction:column;gap:10px;">
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">담당자</p>
                <p style="font-size:13px;color:#374151;margin:0;font-weight:500;">{{ $issue->assignee?->name ?? '미배정' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">등록자</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->reporter?->name ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">등록일</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->created_at->format('Y-m-d H:i') }}</p>
            </div>
            @if($issue->resolved_at)
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">해결일</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->resolved_at->format('Y-m-d H:i') }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- 담당자 변경 --}}
    @if($isManager)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">담당자 변경</p>
        <select id="assignee-select" onchange="changeAssignee(this.value)"
                style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;">
            <option value="">미배정</option>
            @foreach($members as $m)
            <option value="{{ $m->id }}" {{ $issue->assignee_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- 구독 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">알림</p>
        <button id="watch-btn" onclick="toggleWatch()"
                style="width:100%;padding:8px;font-size:13px;font-weight:500;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;
                       {{ $isWatching ? 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;' : 'color:#374151;' }}">
            {{ $isWatching ? '✓ 구독 중' : '구독하기' }}
        </button>
        <p style="font-size:11px;color:#9ca3af;text-align:center;margin:6px 0 0;">{{ $issue->watchers->count() }}명 구독 중</p>
    </div>

    {{-- 변경 이력 --}}
    @if($issue->changeHistories->isNotEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 12px;">변경 이력</p>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($issue->changeHistories->take(10) as $history)
            <div style="font-size:11px;color:#6b7280;display:flex;gap:6px;align-items:flex-start;">
                <span style="flex-shrink:0;color:#9ca3af;">{{ $history->changed_at->format('m-d H:i') }}</span>
                <span>
                    <b>{{ $history->changedBy?->name }}</b>이(가)
                    <b>{{ $history->field_name }}</b>을 변경:
                    {{ $history->old_value ?: '(없음)' }} → {{ $history->new_value ?: '(없음)' }}
                </span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>
</div>

{{-- ─── 해결 처리 모달 ─────────────────────────────────────── --}}
<div id="resolve-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:480px;max-width:95vw;position:relative;">
        <button onclick="closeResolveModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">해결 처리</h3>
        <div style="display:flex;flex-direction:column;gap:14px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">해결 내용 *</label>
                <textarea id="resolution-input" rows="4" placeholder="해결 방법, 조치 내용을 입력하세요..."
                          style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                          onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">최종 상태</label>
                <div style="display:flex;gap:10px;">
                    <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="resolve-status" value="해결" checked> 해결
                    </label>
                    <label style="display:flex;align-items:center;gap:5px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="resolve-status" value="종결"> 종결
                    </label>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
            <button onclick="closeResolveModal()"
                    style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">취소</button>
            <button onclick="submitResolve()" id="resolve-btn"
                    style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;">처리</button>
        </div>
    </div>
</div>

{{-- ─── 이슈 수정 모달 ─────────────────────────────────────── --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="closeEditModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">이슈 수정</h3>
        <form id="edit-form" onsubmit="submitEdit(event)">
            @method('PATCH')
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">제목</label>
                    <input name="title" value="{{ $issue->title }}"
                           style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">설명</label>
                    <textarea name="description" rows="4"
                              style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">{{ $issue->description }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">분류</label>
                        <select name="category" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::CATEGORY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->category === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">우선순위</label>
                        <select name="priority" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::PRIORITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->priority === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">심각도</label>
                        <select name="severity" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::SEVERITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->severity === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">환경</label>
                        <select name="environment" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::ENVIRONMENT_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->environment === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">태그 (쉼표 구분)</label>
                    <input name="tags" value="{{ is_array($issue->tags) ? implode(', ', $issue->tags) : '' }}"
                           style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeEditModal()"
                        style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">취소</button>
                <button type="submit" id="edit-save-btn"
                        style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">저장</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';
const ISSUE_UPDATE_URL = '{{ route('projects.issues.update', [$project, $issue]) }}';
const COMMENT_URL      = '{{ route('projects.issues.comments.store', [$project, $issue]) }}';
const WATCH_URL        = '{{ route('projects.issues.watch', [$project, $issue]) }}';
const RESOLVE_URL      = '{{ route('projects.issues.resolve', [$project, $issue]) }}';
const LINK_REQ_URL     = '{{ route('projects.issues.link-requirement', [$project, $issue]) }}';
const UNLINK_REQ_URL   = '{{ route('projects.issues.unlink-requirement', [$project, $issue]) }}';

async function patch(url, body) {
    const res = await fetch(url, {
        method: 'PATCH',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify(body),
    });
    return res.json();
}

async function changeStatus(val) {
    await patch(ISSUE_UPDATE_URL, {status: val});
    const colors = @json(\App\Models\Issue::STATUS_COLORS);
    const c = colors[val] || {bg:'#f3f4f6', text:'#6b7280'};
    const badge = document.getElementById('status-badge');
    badge.textContent = val;
    badge.style.background = c.bg;
    badge.style.color = c.text;
}

async function changeAssignee(val) {
    await patch(ISSUE_UPDATE_URL, {assignee_id: val || null});
}

async function submitComment() {
    const input = document.getElementById('comment-input');
    const content = input.value.trim();
    if (!content) return;
    const res = await fetch(COMMENT_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({content}),
    });
    const data = await res.json();
    if (data.ok) {
        const c = data.comment;
        const div = document.createElement('div');
        div.style.cssText = 'display:flex;gap:10px;padding:12px;background:#f9fafb;border-radius:8px;';
        div.innerHTML = `<div style="width:30px;height:30px;background:#e0e7ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4f46e5;flex-shrink:0;">나</div>
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    <span style="font-size:12px;font-weight:600;color:#374151;">나</span>
                    <span style="font-size:11px;color:#9ca3af;">${c.created_at}</span>
                </div>
                <p style="font-size:13px;color:#374151;margin:0;white-space:pre-line;">${c.content}</p>
            </div>`;
        document.getElementById('comments-list').appendChild(div);
        input.value = '';
    }
}

async function toggleWatch() {
    const res = await fetch(WATCH_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
    });
    const data = await res.json();
    const btn = document.getElementById('watch-btn');
    if (data.watching) {
        btn.textContent = '✓ 구독 중';
        btn.style.background = '#eef2ff';
        btn.style.color = '#4f46e5';
        btn.style.borderColor = '#c7d2fe';
    } else {
        btn.textContent = '구독하기';
        btn.style.background = '#fff';
        btn.style.color = '#374151';
        btn.style.borderColor = '#e4e4e7';
    }
}

async function openResolveModal() { document.getElementById('resolve-modal').style.display = 'flex'; }
async function closeResolveModal() { document.getElementById('resolve-modal').style.display = 'none'; }

async function submitResolve() {
    const resolution = document.getElementById('resolution-input').value.trim();
    if (!resolution) { alert('해결 내용을 입력해주세요.'); return; }
    const status = document.querySelector('input[name="resolve-status"]:checked').value;
    const btn = document.getElementById('resolve-btn');
    btn.disabled = true; btn.textContent = '처리 중...';
    const res = await fetch(RESOLVE_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({resolution, status}),
    });
    if (res.ok) location.reload();
    else { alert('오류가 발생했습니다.'); btn.disabled = false; btn.textContent = '처리'; }
}

async function openEditModal() { document.getElementById('edit-modal').style.display = 'flex'; }
async function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }

async function submitEdit(e) {
    e.preventDefault();
    const btn = document.getElementById('edit-save-btn');
    btn.disabled = true; btn.textContent = '저장 중...';
    const fd = new FormData(e.target);
    fd.append('_method', 'PATCH');
    const res = await fetch(ISSUE_UPDATE_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: fd,
    });
    if (res.ok) location.reload();
    else { alert('저장 실패'); btn.disabled = false; btn.textContent = '저장'; }
}

async function linkReq() {
    const val = document.getElementById('link-req-select').value;
    if (!val) return;
    const res = await fetch(LINK_REQ_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({requirement_id: val}),
    });
    const data = await res.json();
    if (data.ok) location.reload();
}

async function unlinkReq() {
    if (!await __confirm('연결을 해제할까요?')) return;
    const res = await fetch(UNLINK_REQ_URL, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
    });
    if (res.ok) location.reload();
}

async function saveSla() {
    const sla_due = document.getElementById('sla-due-input').value || null;
    const sla_breached = document.getElementById('sla-breached-check').checked;
    await patch(ISSUE_UPDATE_URL, {sla_due, sla_breached});
    location.reload();
}

[document.getElementById('resolve-modal'), document.getElementById('edit-modal')].forEach(m => {
    if (m) m.addEventListener('click', async function(e) { if (e.target === this) this.style.display = 'none'; });
});
</script>
@endsection
