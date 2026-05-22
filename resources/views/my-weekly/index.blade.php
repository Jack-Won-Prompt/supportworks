@extends('layouts.app')

@section('title', __('myweekly.page_title'))

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">{{ __('myweekly.page_title') }}</span>
@endsection

@section('content')
@php
    $totalCount     = $reports->count();
    $submittedCount = $reports->where('status', 'submitted')->count();
    $draftCount     = $reports->where('status', 'draft')->count();
    $projectCount   = $reports->pluck('project_id')->unique()->count();
@endphp

<div class="space-y-5">

{{-- ── 탭 헤더 ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <div style="display:flex;align-items:center;gap:4px;background:var(--color-bg-muted);border-radius:10px;padding:4px;">
        <button id="tab-list-btn" onclick="switchTab('list')"
            style="padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.08);">
            {{ __('myweekly.tab_list') }}
        </button>
        @if($isManager && $managerProjects->isNotEmpty())
        <button id="tab-ai-btn" onclick="switchTab('ai')"
            style="padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:var(--color-text-secondary);">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px;margin-top:-2px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            {{ __('myweekly.tab_summary') }}
        </button>
        @endif
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
        @if($userProjects->isNotEmpty())
        <select id="write-project-sel"
            style="padding:6px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:12.5px;color:#1f2937;background:#fff;outline:none;cursor:pointer;">
            <option value="">{{ __('myweekly.select_project') }}</option>
            @foreach($userProjects as $up)
            <option value="{{ route('projects.weekly-reports.create', $up) }}">{{ $up->name }}</option>
            @endforeach
        </select>
        <button onclick="openWritePopup()"
            style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;background:#4f46e5;color:#fff;border:none;border-radius:8px;font-size:12.5px;font-weight:600;cursor:pointer;transition:opacity .15s;"
            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('myweekly.write_weekly') }}
        </button>
        @endif
        <span style="font-size:12px;color:var(--color-text-tertiary);">
            @if($isManager) {{ __('myweekly.scope_all_members') }} @else {{ __('myweekly.scope_my_weekly') }} @endif
            — {{ __('myweekly.total_prefix') }} <strong style="color:var(--color-text-secondary);">{{ $totalCount }}</strong>{{ __('myweekly.total_suffix') }}
        </span>
    </div>
</div>

{{-- ══════════════════════ 보고서 목록 탭 ══════════════════════ --}}
<div id="tab-list">

@if($isManager)
@php
    $filterProjects = $reports->pluck('project')->filter()->unique('id')->sortBy('name');
    $filterMembers  = $reports->map(fn($r) => [
        'id'   => $r->user_id,
        'name' => $r->author_name ?: ($r->user?->name ?? ''),
        'pid'  => $r->project_id,
    ])->unique('id')->sortBy('name');
    $membersByProject = $filterMembers->groupBy('pid')->map(fn($g) => $g->values())->toArray();
@endphp
{{-- 필터 바 --}}
<div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:12px 18px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:4px;">
    <svg width="14" height="14" fill="none" stroke="#6b7280" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707l-6.414 6.414A1 1 0 0014 13.828V19a1 1 0 01-1.447.894l-4-2A1 1 0 018 17v-3.172a1 1 0 00-.293-.707L1.293 6.707A1 1 0 011 6V4z"/></svg>
    <select id="filter-project" onchange="onFilterProjectChange()"
        style="padding:6px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:12.5px;color:#1f2937;background:#fff;outline:none;cursor:pointer;min-width:160px;">
        <option value="">{{ __('myweekly.filter_all_projects') }}</option>
        @foreach($filterProjects as $fp)
        <option value="{{ $fp->id }}">{{ $fp->name }}</option>
        @endforeach
    </select>
    <select id="filter-member" onchange="applyListFilter()"
        style="padding:6px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:12.5px;color:#1f2937;background:#fff;outline:none;cursor:pointer;min-width:140px;">
        <option value="">{{ __('myweekly.filter_all_members') }}</option>
        @foreach($filterMembers->unique('id') as $fm)
        <option value="{{ $fm['id'] }}" data-pid="{{ $fm['pid'] }}">{{ $fm['name'] }}</option>
        @endforeach
    </select>
    <button onclick="clearListFilter()"
        style="padding:6px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:12px;font-weight:600;color:var(--color-text-secondary);background:#f9fafb;cursor:pointer;transition:all .12s;"
        onmouseover="this.style.borderColor='#7c3aed';this.style.color='#7c3aed'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280'">
        {{ __('common.reset') }}
    </button>
    <span id="filter-count" style="margin-left:auto;font-size:12px;color:var(--color-text-tertiary);"></span>
</div>
@endif

    {{-- 통계 카드 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:8px;">
        <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:16px 18px;">
            <div style="font-size:22px;font-weight:700;color:var(--color-text-primary);">{{ $totalCount }}</div>
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-top:2px;">{{ __('myweekly.stat_total') }}</div>
        </div>
        <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:16px 18px;">
            <div style="font-size:22px;font-weight:700;color:#065f46;">{{ $submittedCount }}</div>
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-top:2px;">{{ __('myweekly.stat_submitted') }}</div>
        </div>
        <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:16px 18px;">
            <div style="font-size:22px;font-weight:700;color:#92400e;">{{ $draftCount }}</div>
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-top:2px;">{{ __('myweekly.stat_draft') }}</div>
        </div>
        <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:16px 18px;">
            <div style="font-size:22px;font-weight:700;color:var(--tText);">{{ $projectCount }}</div>
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-top:2px;">{{ __('myweekly.stat_projects') }}</div>
        </div>
    </div>

    @if($reports->isEmpty())
    <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:60px 24px;text-align:center;">
        <div style="width:52px;height:52px;background:var(--t50);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg width="24" height="24" fill="none" stroke="#a78bfa" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <p style="font-size:14px;font-weight:600;color:var(--color-text-secondary);margin:0 0 6px;">{{ __('myweekly.empty_title') }}</p>
        <p style="font-size:12.5px;color:var(--color-text-tertiary);margin:0;">{{ __('myweekly.empty_hint') }}</p>
    </div>
    @else
    @php $grouped = $reports->groupBy(fn($r) => $r->week_start_date->format('Y-m-d'))->sortKeysDesc(); @endphp
    @foreach($grouped as $weekStart => $weekReports)
    @php
        $first        = $weekReports->first();
        $weekLabel    = $first->week_label;
        $weekStartFmt = \Carbon\Carbon::parse($weekStart)->format('m/d');
        $weekEndFmt   = \Carbon\Carbon::parse($weekStart)->addDays(6)->format('m/d');
    @endphp
    <div class="week-group" data-week="{{ $weekStart }}" style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;overflow:hidden;margin-bottom:12px;">
        <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;background:linear-gradient(135deg,var(--t50),var(--t100));border-bottom:1px solid var(--t200);">
            <span style="background:linear-gradient(135deg,#4f46e5,var(--t600));color:#fff;border-radius:7px;padding:3px 11px;font-size:12.5px;font-weight:700;">{{ $weekLabel }}</span>
            <span style="font-size:12px;color:var(--t600);font-weight:500;">{{ $weekStartFmt }} ~ {{ $weekEndFmt }}</span>
            <span class="week-group-count" style="margin-left:auto;font-size:11.5px;color:var(--color-text-tertiary);">{{ __('myweekly.count_unit', ['count' => $weekReports->count()]) }}</span>
        </div>

        @foreach($weekReports as $report)
        @php
            $isOwn   = $report->user_id === $user->id;
            $proj    = $report->project;
            $curCnt  = $report->current_task_count ?? 0;
            $nxtCnt  = $report->next_task_count    ?? 0;
            $editUrl = route('projects.weekly-reports.edit', [$report->project_id, $report]);
            $initial = mb_substr($report->author_name ?: ($report->user?->name ?? '?'), 0, 1);
        @endphp
        <div class="report-row" data-project-id="{{ $report->project_id }}" data-user-id="{{ $report->user_id }}"
            style="display:flex;align-items:center;gap:12px;padding:14px 20px;border-bottom:1px solid var(--color-bg-muted);transition:background .12s;cursor:pointer;"
            onclick="openWeeklyPopup('{{ $editUrl }}')"
            onmouseover="this.style.background='#fafaff'" onmouseout="this.style.background=''">

            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--t700),#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:#fff;font-size:14px;font-weight:700;">{{ $initial }}</span>
            </div>

            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                    @if($isManager)
                    <span style="font-size:13.5px;font-weight:700;color:#1f2937;">{{ $report->author_name }}</span>
                    @if($isOwn)<span style="background:var(--t100);color:var(--t600);border-radius:4px;font-size:10px;font-weight:700;padding:1px 6px;">{{ __('myweekly.badge_me') }}</span>@endif
                    @endif
                    @if($proj)
                    <span style="display:inline-flex;align-items:center;gap:4px;background:var(--t100);color:var(--t700);border-radius:5px;padding:2px 8px;font-size:11.5px;font-weight:700;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                        {{ $proj->name }}
                    </span>
                    @endif
                    @if($report->team_name)
                    <span style="background:var(--color-bg-muted);color:var(--color-text-secondary);border-radius:4px;font-size:11px;padding:2px 7px;">{{ $report->team_name }}</span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:11.5px;color:var(--color-text-tertiary);"><span style="color:var(--color-text-secondary);font-weight:500;">{{ __('myweekly.report_date') }}</span> {{ $report->report_date->format('Y.m.d') }}</span>
                    @if($curCnt > 0 || $nxtCnt > 0)
                    <span style="font-size:11.5px;color:var(--color-text-tertiary);">{{ __('myweekly.this_week') }} <strong style="color:var(--color-text-secondary);">{{ $curCnt }}</strong>{{ __('myweekly.unit_item') }} &middot; {{ __('myweekly.next_week') }} <strong style="color:var(--color-text-secondary);">{{ $nxtCnt }}</strong>{{ __('myweekly.unit_item') }}</span>
                    @endif
                    <span style="font-size:11.5px;color:var(--t300);">{{ $report->updated_at->diffForHumans() }}</span>
                </div>
            </div>

            <div style="flex-shrink:0;">
                @if($report->status === 'submitted')
                <span style="display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>{{ __('myweekly.stat_submitted') }}
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ __('myweekly.stat_draft') }}
                </span>
                @endif
            </div>

            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;" onclick="event.stopPropagation()">
                <button type="button" onclick="openWeeklyPopup('{{ $editUrl }}')"
                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border:1.5px solid #d1d5db;border-radius:7px;font-size:12px;font-weight:600;color:var(--color-text-secondary);background:#fff;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.borderColor='#7c3aed';this.style.color='#7c3aed';this.style.background='#f5f3ff'"
                    onmouseout="this.style.borderColor='#d1d5db';this.style.color='#374151';this.style.background='#fff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    {{ __('myweekly.view_detail') }}
                </button>
                @if($isOwn || $user->isAdmin())
                <a href="{{ route('projects.weekly-reports.download', [$report->project_id, $report]) }}"
                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border:1.5px solid var(--t300);border-radius:7px;font-size:12px;font-weight:600;color:var(--t700);text-decoration:none;background:#faf5ff;transition:all .12s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Word
                </a>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endforeach
    @endif

</div>{{-- #tab-list --}}

{{-- ══════════════════════ 웍스 서머리 탭 ══════════════════════ --}}
@if($isManager && $managerProjects->isNotEmpty())
<div id="tab-ai" style="display:none;">

    {{-- 컨트롤 패널 --}}
    <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:18px 20px;display:flex;flex-direction:column;gap:12px;">

        {{-- 1행: 프로젝트 선택 + 서머리 타입 --}}
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;flex:1;min-width:200px;">
                <label style="font-size:12.5px;font-weight:600;color:var(--color-text-secondary);white-space:nowrap;">{{ __('myweekly.label_project') }}</label>
                <select id="ai-project-sel" onchange="onProjectChange()"
                    style="flex:1;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:#1f2937;background:#fff;outline:none;cursor:pointer;">
                    <option value="">{{ __('myweekly.placeholder_choose') }}</option>
                    @foreach($managerProjects as $mp)
                    <option value="{{ $mp->id }}" data-name="{{ $mp->name }}"
                        data-ai-url="{{ route('projects.weekly-reports.ai-summary', $mp) }}"
                        data-gen-url="{{ route('projects.weekly-reports.ai-summary.generate', $mp) }}"
                        data-word-url="{{ route('projects.weekly-reports.ai-summary.download', $mp) }}">
                        {{ $mp->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- 서머리 타입 토글 --}}
            <div style="display:flex;background:var(--color-bg-muted);border-radius:8px;padding:3px;gap:4px;">
                <button id="type-full-btn" onclick="setType('full')"
                    style="padding:6px 16px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 2px rgba(0,0,0,.07);">
                    {{ __('myweekly.summary_type_full') }}
                </button>
                <button id="type-weekly-btn" onclick="setType('weekly')"
                    style="padding:6px 16px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:var(--color-text-secondary);">
                    {{ __('myweekly.summary_type_weekly') }}
                </button>
            </div>
        </div>

        {{-- 2행: 주차 선택 (weekly 타입일 때만) --}}
        <div id="week-row" style="display:none;align-items:center;gap:8px;">
            <label style="font-size:12.5px;font-weight:600;color:var(--color-text-secondary);white-space:nowrap;">{{ __('myweekly.label_week') }}</label>
            <select id="ai-week-sel" onchange="loadStoredSummary()"
                style="flex:1;max-width:320px;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:#1f2937;background:#fff;outline:none;cursor:pointer;">
                <option value="">{{ __('myweekly.week_select_project_first') }}</option>
            </select>
        </div>

        {{-- 3행: 생성 버튼 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div id="ai-meta" style="font-size:12px;color:var(--color-text-tertiary);"></div>
            <div style="display:flex;gap:8px;">
                <button id="ai-word-btn" onclick="downloadAiWord()" style="display:none;align-items:center;gap:4px;padding:8px 14px;border:1.5px solid var(--t300);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--t700);background:#faf5ff;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('myweekly.word_download') }}
                </button>
                <button id="ai-gen-btn" onclick="generateSummary()"
                    style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:#4f46e5;border:none;border-radius:8px;font-size:13px;font-weight:600;color:#fff;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <span id="ai-gen-label">{{ __('myweekly.summary_generate') }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- 서머리 결과 영역 --}}
    <div id="ai-result-area" style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;min-height:200px;overflow:hidden;">

        {{-- 초기 상태 --}}
        <div id="ai-empty" style="padding:60px 24px;text-align:center;">
            <div style="width:52px;height:52px;background:var(--color-bg-success-subtle);border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <svg width="24" height="24" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <p style="font-size:14px;font-weight:600;color:var(--color-text-secondary);margin:0 0 5px;">{{ __('myweekly.summary_empty_title') }}</p>
            <p style="font-size:12.5px;color:var(--color-text-tertiary);margin:0;">{{ __('myweekly.summary_empty_hint') }}</p>
        </div>

        {{-- 로딩 --}}
        <div id="ai-loading" style="display:none;padding:60px 24px;text-align:center;">
            <div style="width:36px;height:36px;border:3px solid #d1fae5;border-top-color:#059669;border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 14px;"></div>
            <p style="font-size:13.5px;color:var(--color-text-secondary);">{{ __('myweekly.summary_loading') }}</p>
        </div>

        {{-- 에러 --}}
        <div id="ai-error" style="display:none;padding:32px 24px;text-align:center;">
            <p id="ai-error-msg" style="font-size:13.5px;color:var(--color-alert-warning-500);"></p>
        </div>

        {{-- 서머리 본문 --}}
        <div id="ai-content" style="display:none;padding:28px 32px;font-size:13.5px;line-height:1.8;color:var(--color-text-secondary);"></div>

    </div>

</div>{{-- #tab-ai --}}
@endif

</div>{{-- .space-y-5 --}}

{{-- ── 보고서 팝업 오버레이 ── --}}
<style>
@keyframes spin { to { transform:rotate(360deg); } }
#mw-popup-backdrop {
    display:none;position:fixed;inset:0;z-index:1200;
    background:rgba(15,23,42,.52);align-items:center;justify-content:center;padding:20px;
}
#mw-popup-backdrop.open { display:flex; }
#mw-popup-panel {
    background:#fff;border-radius:14px;width:min(860px,100%);height:min(90vh,920px);
    display:flex;flex-direction:column;overflow:hidden;
    box-shadow:0 24px 60px rgba(0,0,0,.22);transition:opacity .2s,transform .2s;
}
#ai-content h1,#ai-content h2,#ai-content h3{font-weight:700;color:#1f2937;margin:18px 0 6px;}
#ai-content h1{font-size:16px;}#ai-content h2{font-size:14.5px;}#ai-content h3{font-size:13.5px;}
#ai-content ul,#ai-content ol{padding-left:22px;margin:6px 0;}
#ai-content li{margin:3px 0;}
#ai-content strong{font-weight:700;color:#1f2937;}
#ai-content hr{border:none;border-top:1px solid #e5e7eb;margin:16px 0;}
#ai-content p{margin:6px 0;}
</style>

<div id="mw-popup-backdrop" onclick="if(event.target===this) closeWeeklyPopup()">
    <div id="mw-popup-panel">
        <iframe id="mw-popup-iframe" src="" style="flex:1;border:none;width:100%;border-radius:14px;"></iframe>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const PROJECT_WEEKS = @json($projectWeeksMap ?? []);

// 번역 문자열
const MW_I18N = {
    count_unit:        @json(__('myweekly.count_unit', ['count' => ''])),
    count_filtered:    @json(__('myweekly.count_filtered', ['count' => ''])),
    week_select:       @json(__('myweekly.week_select')),
    summary_regenerate:@json(__('myweekly.summary_regenerate')),
    summary_generate:  @json(__('myweekly.summary_generate')),
    alert_select_project: @json(__('myweekly.alert_select_project')),
    alert_select_week:    @json(__('myweekly.alert_select_week')),
    summary_gen_error:    @json(__('myweekly.summary_gen_error')),
    generated_by_suffix:  @json(__('myweekly.generated_by_suffix')),
};

// ── 탭 전환 ─────────────────────────────────────────────────────────
function switchTab(tab) {
    const listEl = document.getElementById('tab-list');
    const aiEl   = document.getElementById('tab-ai');
    const listBtn= document.getElementById('tab-list-btn');
    const aiBtn  = document.getElementById('tab-ai-btn');

    if (tab === 'list') {
        listEl && (listEl.style.display = 'block');
        aiEl   && (aiEl.style.display   = 'none');
        listBtn.style.cssText = 'padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.08);';
        if (aiBtn) aiBtn.style.cssText = 'padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6b7280;';
    } else {
        listEl && (listEl.style.display = 'none');
        aiEl   && (aiEl.style.display   = 'block');
        if (aiBtn) aiBtn.style.cssText = 'padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 3px rgba(0,0,0,.08);';
        listBtn.style.cssText = 'padding:7px 18px;border-radius:7px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6b7280;';
    }
}

// ── 보고서 목록 필터 ─────────────────────────────────────────────────
const MEMBERS_BY_PROJECT = @json($membersByProject ?? []);

function onFilterProjectChange() {
    const pid      = document.getElementById('filter-project').value;
    const memberSel= document.getElementById('filter-member');
    const opts     = memberSel.querySelectorAll('option');

    opts.forEach(o => {
        if (!o.value) return;
        o.style.display = (!pid || o.dataset.pid === pid) ? '' : 'none';
    });

    if (memberSel.value && memberSel.options[memberSel.selectedIndex].style.display === 'none') {
        memberSel.value = '';
    }
    applyListFilter();
}

function applyListFilter() {
    const pid = document.getElementById('filter-project')?.value ?? '';
    const uid = document.getElementById('filter-member')?.value  ?? '';

    const rows   = document.querySelectorAll('.report-row');
    let visible  = 0;

    rows.forEach(row => {
        const match = (!pid || row.dataset.projectId === pid)
                   && (!uid || row.dataset.userId    === uid);
        row.style.display = match ? 'flex' : 'none';
        if (match) visible++;
    });

    document.querySelectorAll('.week-group').forEach(group => {
        const anyVisible = [...group.querySelectorAll('.report-row')].some(r => r.style.display !== 'none');
        group.style.display = anyVisible ? '' : 'none';
        const cnt = group.querySelector('.week-group-count');
        if (cnt) {
            const n = [...group.querySelectorAll('.report-row')].filter(r => r.style.display !== 'none').length;
            cnt.textContent = MW_I18N.count_unit.replace(':count', n);
        }
    });

    const countEl = document.getElementById('filter-count');
    if (countEl) countEl.textContent = (pid || uid) ? MW_I18N.count_filtered.replace(':count', visible) : '';
}

function clearListFilter() {
    const p = document.getElementById('filter-project');
    const m = document.getElementById('filter-member');
    if (p) p.value = '';
    if (m) { m.value = ''; m.querySelectorAll('option').forEach(o => o.style.display = ''); }
    applyListFilter();
}

// ── 웍스 탭 프로젝트 변경 ──────────────────────────────────────────────
function onProjectChange() {
    const sel   = document.getElementById('ai-project-sel');
    const opt   = sel.options[sel.selectedIndex];
    const pid   = sel.value;
    const wordBtn = document.getElementById('ai-word-btn');
    if (wordBtn) wordBtn.style.display = 'none';

    // 주차 목록 업데이트
    const weekSel = document.getElementById('ai-week-sel');
    if (weekSel) {
        weekSel.innerHTML = '<option value=""></option>';
        weekSel.options[0].textContent = MW_I18N.week_select;
        const weeks = PROJECT_WEEKS[pid] ?? [];
        weeks.forEach(w => {
            const o = document.createElement('option');
            o.value = w.date;
            o.textContent = w.label;
            weekSel.appendChild(o);
        });
    }

    resetResult();
    if (pid) loadStoredSummary();
}

// ── 타입 전환 (전체/주차) ───────────────────────────────────────────
let currentType = 'full';
function setType(type) {
    currentType = type;
    const fullBtn   = document.getElementById('type-full-btn');
    const weeklyBtn = document.getElementById('type-weekly-btn');
    const weekRow   = document.getElementById('week-row');

    const activeStyle  = 'padding:6px 16px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 2px rgba(0,0,0,.07);';
    const passiveStyle = 'padding:6px 16px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6b7280;';

    if (type === 'full') {
        fullBtn.style.cssText   = activeStyle;
        weeklyBtn.style.cssText = passiveStyle;
        weekRow.style.display   = 'none';
    } else {
        weeklyBtn.style.cssText = activeStyle;
        fullBtn.style.cssText   = passiveStyle;
        weekRow.style.display   = 'flex';
    }

    resetResult();
    const pid = document.getElementById('ai-project-sel').value;
    if (pid) loadStoredSummary();
}

// ── 저장된 서머리 불러오기 ─────────────────────────────────────────
function loadStoredSummary() {
    const pid  = document.getElementById('ai-project-sel').value;
    if (!pid) return;

    const opt     = document.getElementById('ai-project-sel').options[document.getElementById('ai-project-sel').selectedIndex];
    const apiUrl  = opt?.dataset?.aiUrl;
    if (!apiUrl) return;

    const weekDate = currentType === 'weekly' ? (document.getElementById('ai-week-sel')?.value ?? '') : '';
    if (currentType === 'weekly' && !weekDate) { resetResult(); return; }

    const params = new URLSearchParams({ type: currentType, week: weekDate });
    fetch(`${apiUrl}?${params}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json())
        .then(data => {
            if (data.summary) {
                renderContent(data.summary.content, data.summary.generated_at, data.summary.generated_by);
                document.getElementById('ai-gen-label').textContent = MW_I18N.summary_regenerate;
            } else {
                resetResult();
            }
        })
        .catch(() => resetResult());
}

// ── 웍스 서머리 생성 ──────────────────────────────────────────────────
function generateSummary() {
    const pid = document.getElementById('ai-project-sel').value;
    if (!pid) { alert(MW_I18N.alert_select_project); return; }

    const opt    = document.getElementById('ai-project-sel').options[document.getElementById('ai-project-sel').selectedIndex];
    const genUrl = opt?.dataset?.genUrl;
    if (!genUrl) return;

    const weekDate = currentType === 'weekly' ? (document.getElementById('ai-week-sel')?.value ?? '') : '';
    if (currentType === 'weekly' && !weekDate) { alert(MW_I18N.alert_select_week); return; }

    showLoading();
    const btn = document.getElementById('ai-gen-btn');
    btn.disabled = true;

    fetch(genUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ type: currentType, week: weekDate }),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.error) {
            showError(data.error);
        } else {
            renderContent(data.content, data.generated_at, data.generated_by);
            document.getElementById('ai-gen-label').textContent = MW_I18N.summary_regenerate;
        }
    })
    .catch(() => {
        btn.disabled = false;
        showError(MW_I18N.summary_gen_error);
    });
}

// ── UI 상태 헬퍼 ────────────────────────────────────────────────────
function resetResult() {
    document.getElementById('ai-empty').style.display   = 'block';
    document.getElementById('ai-loading').style.display = 'none';
    document.getElementById('ai-error').style.display   = 'none';
    document.getElementById('ai-content').style.display = 'none';
    document.getElementById('ai-meta').textContent      = '';
    document.getElementById('ai-gen-label').textContent = MW_I18N.summary_generate;

    const wordBtn = document.getElementById('ai-word-btn');
    if (wordBtn) wordBtn.style.display = 'none';
}
function showLoading() {
    document.getElementById('ai-empty').style.display   = 'none';
    document.getElementById('ai-loading').style.display = 'block';
    document.getElementById('ai-error').style.display   = 'none';
    document.getElementById('ai-content').style.display = 'none';
}
function showError(msg) {
    document.getElementById('ai-empty').style.display      = 'none';
    document.getElementById('ai-loading').style.display    = 'none';
    document.getElementById('ai-error-msg').textContent    = msg;
    document.getElementById('ai-error').style.display      = 'block';
    document.getElementById('ai-content').style.display    = 'none';
}
function renderContent(content, generatedAt, generatedBy) {
    document.getElementById('ai-empty').style.display   = 'none';
    document.getElementById('ai-loading').style.display = 'none';
    document.getElementById('ai-error').style.display   = 'none';
    document.getElementById('ai-content').style.display = 'block';
    document.getElementById('ai-content').innerHTML     = mdToHtml(content);
    document.getElementById('ai-meta').textContent      = generatedAt + '  ' + generatedBy + MW_I18N.generated_by_suffix;

    const wordBtn = document.getElementById('ai-word-btn');
    if (wordBtn) wordBtn.style.display = 'inline-flex';
}

function downloadAiWord() {
    const sel     = document.getElementById('ai-project-sel');
    const opt     = sel.options[sel.selectedIndex];
    const baseUrl = opt?.dataset?.wordUrl;
    if (!baseUrl) return;

    const weekDate = currentType === 'weekly' ? (document.getElementById('ai-week-sel')?.value ?? '') : '';
    const params   = new URLSearchParams({ type: currentType });
    if (weekDate) params.append('week', weekDate);

    window.location.href = baseUrl + '?' + params.toString();
}

// ── 마크다운 → HTML 변환 ────────────────────────────────────────────
function mdToHtml(text) {
    return text
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/^#### (.+)$/gm,'<h3>$1</h3>')
        .replace(/^### (.+)$/gm,'<h3>$1</h3>')
        .replace(/^## (.+)$/gm,'<h2>$1</h2>')
        .replace(/^# (.+)$/gm,'<h1>$1</h1>')
        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
        .replace(/^\s*[-*•]\s+(.+)$/gm,'<li>$1</li>')
        .replace(/(<li>[\s\S]+?<\/li>)/g,'<ul>$1</ul>')
        .replace(/^---$/gm,'<hr>')
        .replace(/\n\n/g,'<p></p>')
        .replace(/\n/g,'<br>');
}

// ── 보고서 작성 팝업 ─────────────────────────────────────────────────
function openWritePopup() {
    const sel = document.getElementById('write-project-sel');
    if (!sel || !sel.value) { alert(MW_I18N.alert_select_project); return; }
    openWeeklyPopup(sel.value);
}

// ── 팝업 ────────────────────────────────────────────────────────────
function openWeeklyPopup(url) {
    const iframe   = document.getElementById('mw-popup-iframe');
    const backdrop = document.getElementById('mw-popup-backdrop');
    iframe.src = url + (url.includes('?') ? '&' : '?') + 'popup=1';
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
}
window.closeWeeklyReportPopup = function(refresh) { closeWeeklyPopup(refresh); };
function closeWeeklyPopup(refresh) {
    const backdrop = document.getElementById('mw-popup-backdrop');
    const panel    = document.getElementById('mw-popup-panel');
    const iframe   = document.getElementById('mw-popup-iframe');
    panel.style.opacity   = '0';
    panel.style.transform = 'scale(.96) translateY(10px)';
    setTimeout(function() {
        backdrop.classList.remove('open');
        panel.style.opacity   = '';
        panel.style.transform = '';
        iframe.src = '';
        document.body.style.overflow = '';
        if (refresh) location.reload();
    }, 200);
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('mw-popup-backdrop').classList.contains('open')) {
        closeWeeklyPopup(false);
    }
});
</script>
@endpush
