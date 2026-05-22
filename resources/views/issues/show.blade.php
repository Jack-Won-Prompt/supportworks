@extends('layouts.app')

@section('title', '#' . $issue->id . ' ' . $issue->title)

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.issues.index', $project) }}" class="hover:text-indigo-500 transition-colors">{{ __('issues.breadcrumb_issue') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">#{{ $issue->id }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    @if(!$issue->isResolved())
    <button onclick="openResolveModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">{{ __('issues.resolve_action') }}</button>
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
            <button onclick="openEditModal()" style="padding:6px 14px;font-size:12px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;flex-shrink:0;">{{ __('issues.edit_issue_btn') }}</button>
            @endif
        </div>

        @if($issue->description)
        <div style="background:#f9fafb;border-radius:8px;padding:14px;font-size:13px;color:#374151;white-space:pre-line;line-height:1.7;" id="issue-desc-display">{{ $issue->description }}</div>
        @endif

        @if($issue->tags)
        <div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:12px;">
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
            <span style="font-size:14px;font-weight:700;color:#15803d;">{{ __('issues.resolved_done') }}</span>
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
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">{{ __('issues.sla_info') }}</h3>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <p style="font-size:11px;color:#9ca3af;font-weight:600;margin:0 0 4px;">{{ __('issues.sla_due') }}</p>
                <p style="font-size:13px;color:#111827;margin:0;" id="sla-due-display">
                    {{ $issue->sla_due ? $issue->sla_due->format('Y-m-d H:i') : '-' }}
                </p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;font-weight:600;margin:0 0 4px;">{{ __('issues.sla_breach') }}</p>
                <p style="font-size:13px;margin:0;" id="sla-breached-display">
                    @if($issue->sla_breached)
                    <span style="color:#dc2626;font-weight:600;">{{ __('issues.sla_breached') }}</span>
                    @else
                    <span style="color:#6b7280;">{{ __('issues.sla_normal') }}</span>
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
                {{ __('issues.sla_breach_label') }}
            </label>
            <button onclick="saveSla()"
                    style="padding:6px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">{{ __('common.save') }}</button>
        </div>
        @endif
    </div>
    @endif

    {{-- 연결된 요구사항 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0;">{{ __('issues.linked_requirement') }}</h3>
            @if($isManager && !$issue->linkedRequirement)
            <button onclick="document.getElementById('link-req-form').style.display='flex'"
                    style="font-size:11px;color:var(--t600);background:none;border:none;cursor:pointer;font-weight:600;">{{ __('issues.link_add') }}</button>
            @endif
        </div>

        <div id="link-req-form" style="display:none;gap:8px;align-items:center;margin-bottom:12px;">
            <select id="link-req-select"
                    style="flex:1;padding:7px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;background:#fff;">
                <option value="">{{ __('issues.select_requirement') }}</option>
                @foreach($requirements as $req)
                <option value="{{ $req->id }}">{{ $req->title }}</option>
                @endforeach
            </select>
            <button onclick="linkReq()"
                    style="padding:7px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">{{ __('issues.link_btn') }}</button>
            <button onclick="document.getElementById('link-req-form').style.display='none'"
                    style="padding:7px 10px;font-size:12px;color:#6b7280;background:#f3f4f6;border:none;border-radius:7px;cursor:pointer;">{{ __('common.cancel') }}</button>
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
                    onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">{{ __('issues.unlink_btn') }}</button>
            @endif
        </div>
        @else
        <p style="font-size:13px;color:#9ca3af;">{{ __('issues.no_linked_requirement') }}</p>
        @endif
        </div>
    </div>

    {{-- Q&A 출처 --}}
    @if($issue->convertedFromQuestion)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 10px;">{{ __('issues.converted_from_qa') }}</h3>
        <a href="{{ route('projects.questions.show', $issue->convertedFromQuestion) }}"
           style="font-size:13px;color:var(--t600);text-decoration:none;"
           onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
            {{ $issue->convertedFromQuestion->title }}
        </a>
    </div>
    @endif

    {{-- 댓글 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:20px;">
        <h3 style="font-size:13px;font-weight:700;color:#374151;margin:0 0 14px;">{{ __('issues.comments_title', ['count' => $issue->comments->count()]) }}</h3>

        <div id="comments-list" style="display:flex;flex-direction:column;gap:12px;margin-bottom:16px;">
            @foreach($issue->comments as $comment)
            <div style="display:flex;gap:12px;padding:12px;background:#f9fafb;border-radius:8px;">
                <div style="width:30px;height:30px;background:#e0e7ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4f46e5;flex-shrink:0;">
                    {{ mb_substr($comment->author->name, 0, 1) }}
                </div>
                <div style="flex:1;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <span style="font-size:12px;font-weight:600;color:#374151;">{{ $comment->author->name }}</span>
                        <span style="font-size:11px;color:#9ca3af;">{{ $comment->created_at->format('Y-m-d H:i') }}</span>
                    </div>
                    <p style="font-size:13px;color:#374151;margin:0;white-space:pre-line;">{!! nl2br(e($comment->content)) !!}</p>
                </div>
            </div>
            @endforeach
        </div>

        <div style="display:flex;gap:8px;align-items:flex-start;">
            <textarea id="comment-input" rows="2" placeholder="{{ __('issues.comment_placeholder') }}"
                      style="flex:1;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:none;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            <button onclick="submitComment()"
                    style="padding:8px 16px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">{{ __('issues.comment_submit') }}</button>
        </div>
    </div>

</div>

{{-- ─── RIGHT: 사이드바 ─────────────────────────────────────── --}}
<div style="display:flex;flex-direction:column;gap:12px;">

    {{-- 상태 변경 --}}
    @if($isManager || auth()->id() === $issue->reporter_id || auth()->id() === $issue->assignee_id)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">{{ __('issues.status_change') }}</p>
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
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 12px;">{{ __('issues.issue_info') }}</p>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">{{ __('issues.info_assignee') }}</p>
                <p style="font-size:13px;color:#374151;margin:0;font-weight:500;">{{ $issue->assignee?->name ?? __('issues.unassigned') }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">{{ __('issues.info_reporter') }}</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->reporter?->name ?? '-' }}</p>
            </div>
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">{{ __('issues.info_created_at') }}</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->created_at->format('Y-m-d H:i') }}</p>
            </div>
            @if($issue->resolved_at)
            <div>
                <p style="font-size:11px;color:#9ca3af;margin:0 0 2px;">{{ __('issues.info_resolved_at') }}</p>
                <p style="font-size:13px;color:#374151;margin:0;">{{ $issue->resolved_at->format('Y-m-d H:i') }}</p>
            </div>
            @endif
        </div>
    </div>

    {{-- 담당자 변경 --}}
    @if($isManager)
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">{{ __('issues.assignee_change') }}</p>
        <select id="assignee-select" onchange="changeAssignee(this.value)"
                style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;">
            <option value="">{{ __('issues.unassigned') }}</option>
            @foreach($members as $m)
            <option value="{{ $m->id }}" {{ $issue->assignee_id == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    @endif

    {{-- 구독 --}}
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 10px;">{{ __('issues.notification') }}</p>
        <button id="watch-btn" onclick="toggleWatch()"
                style="width:100%;padding:8px;font-size:13px;font-weight:500;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;
                       {{ $isWatching ? 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;' : 'color:#374151;' }}">
            {{ $isWatching ? __('issues.watching') : __('issues.watch') }}
        </button>
        <p style="font-size:11px;color:#9ca3af;text-align:center;margin:6px 0 0;">{{ __('issues.watcher_count', ['count' => $issue->watchers->count()]) }}</p>
    </div>

    {{-- 변경 이력 --}}
    @if($issue->changeHistories->isNotEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:18px;">
        <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;margin:0 0 12px;">{{ __('issues.change_history') }}</p>
        <div style="display:flex;flex-direction:column;gap:8px;">
            @foreach($issue->changeHistories->take(10) as $history)
            <div style="font-size:11px;color:#6b7280;display:flex;gap:8px;align-items:flex-start;">
                <span style="flex-shrink:0;color:#9ca3af;">{{ $history->changed_at->format('m-d H:i') }}</span>
                <span>{!! __('issues.history_changed', [
                    'user'  => '<b>' . e($history->changedBy?->name) . '</b>',
                    'field' => '<b>' . e($history->field_name) . '</b>',
                    'old'   => e($history->old_value ?: __('issues.history_empty_value')),
                    'new'   => e($history->new_value ?: __('issues.history_empty_value')),
                ]) !!}</span>
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
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">{{ __('issues.resolve_modal_title') }}</h3>
        <div style="display:flex;flex-direction:column;gap:12px;">
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.resolution_label') }}</label>
                <textarea id="resolution-input" rows="4" placeholder="{{ __('issues.resolution_placeholder') }}"
                          style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                          onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.final_status') }}</label>
                <div style="display:flex;gap:12px;">
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="resolve-status" value="해결" checked> {{ __('issues.resolve_status_resolved') }}
                    </label>
                    <label style="display:flex;align-items:center;gap:4px;font-size:13px;cursor:pointer;">
                        <input type="radio" name="resolve-status" value="종결"> {{ __('issues.resolve_status_closed') }}
                    </label>
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
            <button onclick="closeResolveModal()"
                    style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">{{ __('common.cancel') }}</button>
            <button onclick="submitResolve()" id="resolve-btn"
                    style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;">{{ __('issues.resolve_submit') }}</button>
        </div>
    </div>
</div>

{{-- ─── 이슈 수정 모달 ─────────────────────────────────────── --}}
<div id="edit-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="closeEditModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">{{ __('issues.edit_modal_title') }}</h3>
        <form id="edit-form" onsubmit="submitEdit(event)">
            @method('PATCH')
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.edit_field_title') }}</label>
                    <input name="title" value="{{ $issue->title }}"
                           style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_description') }}</label>
                    <textarea name="description" rows="4"
                              style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">{{ $issue->description }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.edit_field_category') }}</label>
                        <select name="category" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::CATEGORY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->category === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.edit_field_priority') }}</label>
                        <select name="priority" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::PRIORITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->priority === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_severity') }}</label>
                        <select name="severity" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::SEVERITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->severity === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_environment') }}</label>
                        <select name="environment" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::ENVIRONMENT_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $issue->environment === $v ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_tags') }}</label>
                    <input name="tags" value="{{ is_array($issue->tags) ? implode(', ', $issue->tags) : '' }}"
                           style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeEditModal()"
                        style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit" id="edit-save-btn"
                        style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const CSRF = '{{ csrf_token() }}';
const COMMENT_ME = @json(__('issues.comment_me'));
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
        div.innerHTML = `<div style="width:30px;height:30px;background:#e0e7ff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#4f46e5;flex-shrink:0;">${COMMENT_ME}</div>
            <div style="flex:1;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="font-size:12px;font-weight:600;color:#374151;">${COMMENT_ME}</span>
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
        btn.textContent = @json(__('issues.watching'));
        btn.style.background = '#eef2ff';
        btn.style.color = '#4f46e5';
        btn.style.borderColor = '#c7d2fe';
    } else {
        btn.textContent = @json(__('issues.watch'));
        btn.style.background = '#fff';
        btn.style.color = '#374151';
        btn.style.borderColor = '#e4e4e7';
    }
}

async function openResolveModal() { document.getElementById('resolve-modal').style.display = 'flex'; }
async function closeResolveModal() { document.getElementById('resolve-modal').style.display = 'none'; }

async function submitResolve() {
    const resolution = document.getElementById('resolution-input').value.trim();
    if (!resolution) { alert(@json(__('issues.resolution_required'))); return; }
    const status = document.querySelector('input[name="resolve-status"]:checked').value;
    const btn = document.getElementById('resolve-btn');
    btn.disabled = true; btn.textContent = @json(__('issues.resolving'));
    const res = await fetch(RESOLVE_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json'},
        body: JSON.stringify({resolution, status}),
    });
    if (res.ok) location.reload();
    else { alert(@json(__('issues.error_occurred'))); btn.disabled = false; btn.textContent = @json(__('issues.resolve_submit')); }
}

async function openEditModal() { document.getElementById('edit-modal').style.display = 'flex'; }
async function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }

async function submitEdit(e) {
    e.preventDefault();
    const btn = document.getElementById('edit-save-btn');
    btn.disabled = true; btn.textContent = @json(__('issues.saving'));
    const fd = new FormData(e.target);
    fd.append('_method', 'PATCH');
    const res = await fetch(ISSUE_UPDATE_URL, {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: fd,
    });
    if (res.ok) location.reload();
    else { alert(@json(__('issues.save_failed'))); btn.disabled = false; btn.textContent = @json(__('common.save')); }
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
    if (!await __confirm(@json(__('issues.confirm_unlink')))) return;
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
