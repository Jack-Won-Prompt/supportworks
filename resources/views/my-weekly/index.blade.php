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
        @if(!empty($canViewAiSummary) && $aiProjects->isNotEmpty())
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
@if(!empty($canViewAiSummary) && $aiProjects->isNotEmpty())
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
                    @foreach($aiProjects as $mp)
                    <option value="{{ $mp->id }}" data-name="{{ $mp->name }}"
                        data-ai-url="{{ route('projects.weekly-reports.ai-summary', $mp) }}"
                        data-gen-url="{{ route('projects.weekly-reports.ai-summary.generate', $mp) }}"
                        data-word-url="{{ route('projects.weekly-reports.ai-summary.download', $mp) }}"
                        data-ww-linked="{{ $mp->withworks_linked ? '1' : '0' }}">
                        {{ $mp->name }}{{ $mp->withworks_linked ? ' 🔗' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- 서머리 타입 토글 — 지난 달 / 이번 달 / 주차 --}}
            <div style="display:flex;background:var(--color-bg-muted);border-radius:8px;padding:3px;gap:4px;">
                <button id="type-full-btn" onclick="setType('full')"
                    style="padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 2px rgba(0,0,0,.07);">
                    전체 서머리 (지난 달)
                </button>
                <button id="type-this-month-btn" onclick="setType('this_month')"
                    style="padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:var(--color-text-secondary);">
                    전체 서머리 (이번 달)
                </button>
                <button id="type-weekly-btn" onclick="setType('weekly')"
                    style="padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:var(--color-text-secondary);">
                    {{ __('myweekly.summary_type_weekly') }}
                </button>
            </div>

            {{-- WITHWORKS Git 동기화 버튼 — 관리자/매니저만 노출 + 연결된 프로젝트 선택 시에만 표시 --}}
            @if(!empty($canSyncGit))
            <button type="button" onclick="syncWithWorksGit()" id="ww-sync-btn" title="WITHWORKS 의 최근 30일 커밋을 가져옵니다"
                style="display:none;align-items:center;gap:5px;padding:7px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:12px;font-weight:600;color:#374151;background:#fff;cursor:pointer;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5 9a9 9 0 0114-3l1 1M19 15a9 9 0 01-14 3l-1-1"/></svg>
                <span id="ww-sync-label">Git 동기화</span>
            </button>
            @endif
        </div>

        {{-- 2행: 주차 선택 (weekly 타입일 때만) — From(월) ~ To(금, 자동) --}}
        <div id="week-row" style="display:none;align-items:center;gap:10px;flex-wrap:wrap;">
            <label style="font-size:12.5px;font-weight:600;color:var(--color-text-secondary);white-space:nowrap;">{{ __('myweekly.label_week') }}</label>
            <div style="display:flex;align-items:center;gap:6px;">
                <input type="date" id="ai-week-from"
                       onchange="onWeekFromChange()" title="월요일만 선택 가능"
                       style="padding:7px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:#1f2937;background:#fff;outline:none;cursor:pointer;">
                <span style="font-size:12.5px;color:var(--color-text-tertiary);">~</span>
                <input type="date" id="ai-week-to" readonly tabindex="-1"
                       style="padding:7px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:#475569;background:#f8fafc;outline:none;cursor:not-allowed;">
                <span id="ai-week-hint" style="font-size:11.5px;color:#94a3b8;margin-left:4px;">월요일 선택 시 금요일까지 자동</span>
            </div>
        </div>

        {{-- SR 회사 멀티선택 (체크박스 그룹) — 선택된 회사 SR 만 서머리에 포함 --}}
        @if(!empty($srCompaniesForFilter) && $srCompaniesForFilter->count() > 0)
        <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap;">
            <label style="font-size:12.5px;font-weight:600;color:var(--color-text-secondary);white-space:nowrap;padding-top:6px;">SR 회사
                <span style="font-size:11px;color:var(--color-text-tertiary);font-weight:500;">(다중 선택)</span>
            </label>
            <div id="ai-sr-companies" style="display:flex;flex-wrap:wrap;gap:6px;flex:1;">
                @foreach($srCompaniesForFilter as $sc)
                <label style="display:inline-flex;align-items:center;gap:5px;padding:5px 10px;background:#fff;border:1.5px solid var(--color-border-default);border-radius:7px;font-size:12px;color:#334155;cursor:pointer;user-select:none;">
                    <input type="checkbox" class="ai-sr-company" value="{{ $sc->id }}" onchange="loadStoredSummary()" style="width:13px;height:13px;accent-color:#7c3aed;">
                    <span>{{ $sc->name }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endif


        {{-- 3행: 생성 버튼 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <div id="ai-meta" style="display:none;font-size:12px;color:var(--color-text-tertiary);"></div>
            <div style="display:flex;gap:8px;">
                <button id="ai-word-btn" onclick="downloadAiWord()" style="display:none;align-items:center;gap:4px;padding:8px 14px;border:1.5px solid var(--t300);border-radius:8px;font-size:12.5px;font-weight:600;color:var(--t700);background:#faf5ff;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    {{ __('myweekly.word_download') }}
                </button>
                <button id="ai-view-btn" onclick="viewStoredSummary()" title="저장된 서머리가 있으면 다시 불러옵니다"
                    style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;background:#fff;border:1.5px solid #cbd5e1;border-radius:8px;font-size:12.5px;font-weight:600;color:#475569;cursor:pointer;transition:all .15s;"
                    onmouseover="this.style.background='#f8fafc';this.style.borderColor='#94a3b8';" onmouseout="this.style.background='#fff';this.style.borderColor='#cbd5e1';">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    서머리 조회
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

    {{-- ── Git 커밋 내역 (접힌 영역) — 관리자/매니저만 ── --}}
    @if(!empty($canSyncGit))
    <details id="ai-commits-wrap" style="display:none;margin-top:12px;background:#fff;border:1px solid #e9e7fb;border-radius:12px;overflow:hidden;">
        <summary style="cursor:pointer;padding:12px 18px;font-size:13px;font-weight:600;color:#334155;background:#f8fafc;border-bottom:1px solid #e9e7fb;display:flex;align-items:center;gap:8px;list-style:none;user-select:none;">
            <svg id="ai-commits-chevron" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transition:transform .2s;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
            <span>Git 커밋 내역</span>
            <span id="ai-commits-count" style="font-size:11px;color:#94a3b8;font-weight:500;"></span>
            <span style="flex:1;"></span>
            <span style="font-size:11px;color:#94a3b8;font-weight:500;">관리자·매니저 전용</span>
        </summary>
        <div style="padding:14px 18px;display:flex;flex-direction:column;gap:18px;">
            <div id="ai-commits-project">
                <h4 style="font-size:12.5px;font-weight:700;color:#1e293b;margin:0 0 8px;">📂 프로젝트 영역</h4>
                <div id="ai-commits-project-body" style="font-size:12px;color:#475569;"></div>
            </div>
            <div id="ai-commits-common" style="display:none;">
                <h4 style="font-size:12.5px;font-weight:700;color:#1e293b;margin:0 0 8px;">📁 공통 영역 <span style="font-size:11px;color:#94a3b8;font-weight:500;">(어느 프로젝트 키워드와도 매칭되지 않음)</span></h4>
                <div id="ai-commits-common-body" style="font-size:12px;color:#475569;"></div>
            </div>
        </div>
    </details>
    @endif

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
/* ─── 웍스 서머리 본문 — 카드형 세련된 레이아웃 ─── */
#ai-content{font-size:13.5px;line-height:1.75;color:#334155;}
#ai-content > h1:first-child,#ai-content > h2:first-child{margin-top:0;}

/* 섹션 헤딩 (## 헤딩이 섹션 카드의 헤더) */
#ai-content h2{
    font-size:15px;font-weight:700;color:#0f172a;
    margin:24px 0 12px;padding:10px 14px;
    background:linear-gradient(90deg,#eef2ff 0%,#faf5ff 100%);
    border-left:4px solid #7c3aed;border-radius:7px;
    display:flex;align-items:center;gap:8px;letter-spacing:-.01em;
}
#ai-content h2:first-child{margin-top:4px;}
#ai-content h1{font-size:16.5px;font-weight:800;color:#1e293b;margin:20px 0 10px;}
#ai-content h3{font-size:13.5px;font-weight:700;color:#475569;margin:14px 0 6px;}

/* 본문 */
#ai-content p{margin:8px 0;}
#ai-content strong{font-weight:700;color:#1e293b;}
#ai-content hr{border:none;border-top:1px dashed #e2e8f0;margin:20px 0;}

/* 리스트 — 컬러 마커 */
#ai-content ul,#ai-content ol{padding-left:6px;margin:8px 0;list-style:none;}
#ai-content li{margin:6px 0;padding-left:20px;position:relative;}
#ai-content li::before{
    content:'';position:absolute;left:6px;top:9px;
    width:6px;height:6px;border-radius:50%;background:#a78bfa;
}
#ai-content ul ul li::before{background:#cbd5e1;}

/* 마크다운 테이블 → 데이터 표 */
#ai-content table{
    width:100%;border-collapse:separate;border-spacing:0;
    margin:10px 0 14px;font-size:12.5px;
    border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;
}
#ai-content thead th{
    background:#f8fafc;color:#475569;font-weight:700;text-align:left;
    padding:9px 12px;border-bottom:1px solid #e2e8f0;font-size:11.5px;
    text-transform:uppercase;letter-spacing:.03em;
}
#ai-content tbody td{padding:9px 12px;border-bottom:1px solid #f1f5f9;color:#334155;}
#ai-content tbody tr:last-child td{border-bottom:none;}
#ai-content tbody tr:hover td{background:#fafbfc;}
#ai-content tbody td:first-child{font-weight:600;color:#0f172a;}

/* 평가 라벨 / 난이도 라벨 인라인 배지 */
#ai-content .ai-badge{
    display:inline-flex;align-items:center;padding:1px 8px;border-radius:11px;
    font-size:11px;font-weight:700;margin:0 2px;letter-spacing:-.01em;
}
#ai-content .ai-badge-good{background:#dcfce7;color:#15803d;}
#ai-content .ai-badge-ok{background:#dbeafe;color:#1e40af;}
#ai-content .ai-badge-warn{background:#fef3c7;color:#a16207;}
#ai-content .ai-badge-bad{background:#fee2e2;color:#b91c1c;}
#ai-content .ai-badge-easy{background:#ecfeff;color:#0e7490;}
#ai-content .ai-badge-medium{background:#eef2ff;color:#4338ca;}
#ai-content .ai-badge-hard{background:#fef3c7;color:#a16207;}
#ai-content .ai-badge-critical{background:#fee2e2;color:#b91c1c;}

/* 코드 인라인 */
#ai-content code{
    background:#f1f5f9;padding:1px 6px;border-radius:4px;
    font-family:ui-monospace,SFMono-Regular,Consolas,monospace;
    font-size:12px;color:#7c3aed;
}

/* 최상단 담당자 SR·Git 막대 차트 */
#ai-content .top-chart{
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    padding:12px 14px;margin:0 0 14px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);
}
#ai-content .top-chart-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;flex-wrap:wrap;gap:8px;}
#ai-content .top-chart-head h3{margin:0;font-size:13.5px;font-weight:700;color:#0f172a;}
#ai-content .top-chart-legend{display:flex;gap:12px;font-size:11.5px;color:#64748b;}
#ai-content .top-chart-legend i{display:inline-block;width:9px;height:9px;border-radius:2px;margin-right:4px;vertical-align:middle;}
#ai-content .top-chart-rows{display:flex;flex-direction:column;gap:6px;}
#ai-content .top-chart-row{display:grid;grid-template-columns:90px 1fr;gap:10px;align-items:center;}
#ai-content .top-chart-name{font-size:12.5px;font-weight:600;color:#0f172a;}
#ai-content .top-chart-bars{display:flex;flex-direction:column;gap:3px;}
#ai-content .top-chart-bar-row{display:flex;align-items:center;gap:6px;}
#ai-content .top-chart-bar{height:13px;border-radius:3px;min-width:2px;transition:width .4s ease;}
#ai-content .top-chart-bar-row > span{font-size:11px;color:#475569;font-weight:600;min-width:24px;font-variant-numeric:tabular-nums;}

/* 주요 이슈 / 해결 방안 — 항목별 가로 카드 그리드 */
#ai-content .issue-grid{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:10px;
    margin:8px 0 18px;align-items:start;
}
#ai-content .issue-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:9px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);padding:10px 14px;
    font-size:12.5px;line-height:1.65;color:#334155;min-width:0;
    border-left:3px solid #c4b5fd;
}
#ai-content .issue-card:hover{border-left-color:#7c3aed;}
#ai-content .issue-card strong{display:inline-block;color:#0f172a;font-weight:700;}
#ai-content .issue-card ul{margin:4px 0;padding-left:2px;}
#ai-content .issue-card li{margin:3px 0;padding-left:14px;font-size:12px;}
#ai-content .issue-card li::before{top:6px;left:2px;width:4px;height:4px;background:#94a3b8;}
/* 카드 내부 작은 그리드(eval/assignee)는 그리드 자식이 single 컬럼이 되도록 */
#ai-content .section-card .eval-grid,
#ai-content .section-card .assignee-grid{
    grid-template-columns:1fr;
}

/* 담당자 카드 그리드 (정량 지표 표 → 카드) — 컴팩트 */
#ai-content .assignee-grid{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;
    margin:8px 0 14px;
}
#ai-content .assignee-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden;
}
#ai-content .assignee-card-head{
    display:flex;align-items:center;gap:8px;padding:10px 12px;
    background:linear-gradient(135deg,#eef2ff 0%,#faf5ff 100%);
    border-bottom:1px solid #e9e7fb;
}
#ai-content .assignee-avatar{
    width:28px;height:28px;border-radius:50%;
    background:#7c3aed;color:#fff;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:700;flex-shrink:0;
}
#ai-content .assignee-name{font-size:13.5px;font-weight:700;color:#0f172a;}
#ai-content .assignee-card-body{padding:8px 12px 10px;}
#ai-content .assignee-metric{
    display:flex;align-items:center;justify-content:space-between;
    padding:4px 0;border-bottom:1px dashed #f1f5f9;font-size:12px;
}
#ai-content .assignee-metric:last-child{border-bottom:none;}
#ai-content .assignee-metric-key{color:#64748b;font-weight:500;}
#ai-content .assignee-metric-val{color:#0f172a;font-weight:600;font-variant-numeric:tabular-nums;}

/* 담당자별 업무 평가 카드 그리드 (### 이름 → 카드) — 컴팩트 */
#ai-content .eval-grid{
    display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px;
    margin:6px 0 14px;
}
#ai-content .eval-card{
    background:#fff;border:1px solid #e2e8f0;border-radius:10px;
    box-shadow:0 1px 2px rgba(15,23,42,.04);overflow:hidden;
    display:flex;flex-direction:column;
}
#ai-content .eval-card-head{
    display:flex;align-items:center;gap:8px;padding:10px 13px;
    background:linear-gradient(135deg,#fafbff 0%,#fff 100%);
    border-bottom:1px solid #f1f5f9;
}
#ai-content .eval-avatar{
    width:30px;height:30px;border-radius:50%;
    background:linear-gradient(135deg,#a78bfa,#7c3aed);color:#fff;
    display:inline-flex;align-items:center;justify-content:center;
    font-size:13.5px;font-weight:700;flex-shrink:0;
}
#ai-content .eval-name{font-size:14px;font-weight:700;color:#0f172a;}
#ai-content .eval-card-body{padding:8px 13px 12px;font-size:12.5px;line-height:1.7;}
#ai-content .eval-card-body ul{padding-left:2px;margin:4px 0;}
#ai-content .eval-card-body li{margin:4px 0;padding-left:18px;}
#ai-content .eval-card-body li::before{top:8px;left:4px;}
#ai-content .eval-card-body p{margin:4px 0;}
#ai-content .eval-card-body strong{color:#475569;font-size:11.5px;text-transform:uppercase;letter-spacing:.04em;}
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
const SR_GEN_URL  = @json(route('weekly-reports.sr-summary.generate'));
const SR_SHOW_URL = @json(route('weekly-reports.sr-summary.show'));
const CAN_SEE_EVAL_BADGE = @json((bool) ($canSyncGit ?? false));  // 평가 라벨 배지 노출 권한 (관리자/매니저)
const IS_ADMIN_USER      = @json((bool) (auth()->user()?->isAdmin() ?? false));  // 관리자 전용 콘텐츠 노출
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

    // Git 동기화 버튼: 프로젝트가 withworks 와 연결되어 있을 때만 표시 (권한 게이팅은 서버에서)
    const syncBtn = document.getElementById('ww-sync-btn');
    if (syncBtn) {
        const linked = opt?.dataset?.wwLinked === '1';
        syncBtn.style.display = (pid && linked) ? 'inline-flex' : 'none';
    }

    // 주차 목록 업데이트 — 최근 12주 자동 생성 (보고서 유무 무관)
    populateWeekOptions();

    resetResult();
    if (pid) loadStoredSummary();
}

// ── 타입 전환 (전체 지난 달 / 주차) ──────────────────────────────────
let currentType = 'full';
function setType(type) {
    currentType = type;
    const fullBtn      = document.getElementById('type-full-btn');
    const thisMonthBtn = document.getElementById('type-this-month-btn');
    const weeklyBtn    = document.getElementById('type-weekly-btn');
    const weekRow      = document.getElementById('week-row');

    const activeStyle  = 'padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:#fff;color:#1f2937;box-shadow:0 1px 2px rgba(0,0,0,.07);';
    const passiveStyle = 'padding:6px 14px;border-radius:6px;font-size:12.5px;font-weight:600;border:none;cursor:pointer;transition:all .15s;background:transparent;color:#6b7280;';

    fullBtn.style.cssText      = type === 'full'       ? activeStyle : passiveStyle;
    thisMonthBtn.style.cssText = type === 'this_month' ? activeStyle : passiveStyle;
    weeklyBtn.style.cssText    = type === 'weekly'     ? activeStyle : passiveStyle;
    weekRow.style.display      = type === 'weekly'     ? 'flex' : 'none';

    resetResult();
    const pid = document.getElementById('ai-project-sel').value;
    const srAny = document.querySelectorAll('.ai-sr-company:checked').length > 0;
    if (pid || srAny) loadStoredSummary();
}

// ── WITHWORKS Git 동기화 ─────────────────────────────────────────────
async function syncWithWorksGit() {
    const btn   = document.getElementById('ww-sync-btn');
    const label = document.getElementById('ww-sync-label');
    if (!btn || btn.disabled) return;
    btn.disabled = true;
    const orig = label.textContent;
    label.textContent = '동기화 중...';
    try {
        const r = await fetch(@json(route('withworks.sync')), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ days: 30 }),
        });
        const data = await r.json();
        if (data.ok) {
            label.textContent = `완료 +${data.inserted}/스킵 ${data.skipped}`;
            if (window.appToast) window.appToast('success', `WITHWORKS Git 동기화 완료 — ${data.inserted}건 추가, ${data.skipped}건 스킵`, 4000);
        } else {
            label.textContent = '실패';
            if (window.appToast) window.appToast('error', data.error || 'Git 동기화 실패', 4500);
        }
    } catch (e) {
        label.textContent = '실패';
        if (window.appToast) window.appToast('error', 'Git 동기화 요청 실패: ' + e.message, 4500);
    } finally {
        setTimeout(() => { btn.disabled = false; label.textContent = orig; }, 4000);
    }
}

// ── 주차 선택 (From=월요일, To=금요일 자동) ──────────────────────────
function fmtDate(d) {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${dd}`;
}
function thisMonday() {
    const t = new Date();
    const dow = t.getDay();
    const off = dow === 0 ? -6 : 1 - dow;
    return new Date(t.getFullYear(), t.getMonth(), t.getDate() + off);
}

// 페이지 로드 시 From input 초기 설정
document.addEventListener('DOMContentLoaded', () => {
    const fromInput = document.getElementById('ai-week-from');
    if (!fromInput) return;
    const mon = thisMonday();
    fromInput.max = fmtDate(mon);          // 미래 주(이번주 월요일 이후) 선택 불가
    // 기본값은 비워둠 — 사용자가 선택하도록
});

// From 변경 시: 월요일 검증 + 미래 검증 + To 자동 설정 + 캐시 조회
function onWeekFromChange() {
    const fromInput = document.getElementById('ai-week-from');
    const toInput   = document.getElementById('ai-week-to');
    const hint      = document.getElementById('ai-week-hint');
    if (!fromInput.value) {
        toInput.value = '';
        if (hint) hint.textContent = '월요일 선택 시 금요일까지 자동';
        return;
    }
    const d = new Date(fromInput.value + 'T00:00:00');
    const dow = d.getDay(); // 0=일, 1=월
    const today = new Date(); today.setHours(0,0,0,0);

    // 미래 검증
    if (d > today) {
        if (hint) { hint.textContent = '미래 날짜는 선택할 수 없습니다.'; hint.style.color = '#dc2626'; }
        fromInput.value = '';
        toInput.value = '';
        return;
    }
    // 월요일이 아니면 가장 가까운 이전 월요일로 보정
    if (dow !== 1) {
        const off = dow === 0 ? -6 : 1 - dow;
        const mon = new Date(d.getFullYear(), d.getMonth(), d.getDate() + off);
        fromInput.value = fmtDate(mon);
        if (hint) { hint.textContent = `월요일이 아니라서 ${fmtDate(mon)} 로 보정됨`; hint.style.color = '#a16207'; }
    } else {
        if (hint) { hint.textContent = '월~금 5일'; hint.style.color = '#16a34a'; }
    }
    // To = From + 4일 (금요일)
    const start = new Date(fromInput.value + 'T00:00:00');
    const end = new Date(start);
    end.setDate(start.getDate() + 4);
    toInput.value = fmtDate(end);

    loadStoredSummary();
}

// 기존 ai-week-sel 참조 호환 — From input 값을 폼 제출용으로 노출
function getCurrentWeekDate() {
    const f = document.getElementById('ai-week-from');
    return f && f.value ? f.value : '';
}

// ── 저장된 서머리 불러오기 ─────────────────────────────────────────
function loadStoredSummary() {
    const pid  = document.getElementById('ai-project-sel').value;
    const srCompanyIds = Array.from(document.querySelectorAll('.ai-sr-company:checked'))
        .map(cb => parseInt(cb.value, 10)).filter(Number.isFinite);
    if (!pid && srCompanyIds.length === 0) { resetResult(); return; }

    const weekDate = currentType === 'weekly' ? getCurrentWeekDate() : '';
    if (currentType === 'weekly' && !weekDate) { resetResult(); return; }

    // 프로젝트 있으면 프로젝트 라우트, 없으면 SR 전용 라우트
    let apiUrl;
    const params = new URLSearchParams({ type: currentType });
    if (weekDate) params.set('week', weekDate);
    if (pid) {
        const opt = document.getElementById('ai-project-sel').options[document.getElementById('ai-project-sel').selectedIndex];
        apiUrl = opt?.dataset?.aiUrl;
    } else {
        apiUrl = SR_SHOW_URL;
        srCompanyIds.forEach(id => params.append('sr_company_ids[]', id));
    }
    if (!apiUrl) return;

    fetch(`${apiUrl}?${params}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json())
        .then(data => {
            if (data.summary) {
                renderContent(data.summary.content, data.summary.generated_at, data.summary.generated_by, data.summary.metrics);
                renderCommitDetails(data.summary.commit_details, data.summary.common_commit_details);
                document.getElementById('ai-gen-label').textContent = MW_I18N.summary_regenerate;
                // 캐시가 7일 이상 낡으면 자동 재생성 (Git 최신화 + AI 재분석)
                if (data.summary.is_stale) {
                    if (window.appToast) window.appToast('info', `이전 서머리(${data.summary.stale_days}일 전) — 자동 갱신 중...`, 3500);
                    setTimeout(() => generateSummary(), 200);
                }
            } else {
                resetResult();
            }
        })
        .catch(() => resetResult());
}

// ── 저장된 서머리 명시적 조회 (버튼 클릭) ─────────────────────────────
function viewStoredSummary() {
    const pid = document.getElementById('ai-project-sel').value;
    const srCompanyIds = Array.from(document.querySelectorAll('.ai-sr-company:checked'))
        .map(cb => parseInt(cb.value, 10)).filter(Number.isFinite);
    if (!pid && srCompanyIds.length === 0) {
        alert('프로젝트 또는 SR 회사를 먼저 선택하세요.');
        return;
    }
    const weekDate = currentType === 'weekly' ? getCurrentWeekDate() : '';
    if (currentType === 'weekly' && !weekDate) {
        alert(MW_I18N.alert_select_week);
        return;
    }

    // 명시적 조회 모드 — 캐시 없으면 안내. is_stale 이어도 자동 재생성 안 함 (조회만).
    let apiUrl;
    const params = new URLSearchParams({ type: currentType });
    if (weekDate) params.set('week', weekDate);
    if (pid) {
        const opt = document.getElementById('ai-project-sel').options[document.getElementById('ai-project-sel').selectedIndex];
        apiUrl = opt?.dataset?.aiUrl;
    } else {
        apiUrl = SR_SHOW_URL;
        srCompanyIds.forEach(id => params.append('sr_company_ids[]', id));
    }
    if (!apiUrl) return;

    showLoading();
    fetch(`${apiUrl}?${params}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } })
        .then(r => r.json())
        .then(data => {
            if (data.summary) {
                renderContent(data.summary.content, data.summary.generated_at, data.summary.generated_by, data.summary.metrics);
                renderCommitDetails(data.summary.commit_details, data.summary.common_commit_details);
                document.getElementById('ai-gen-label').textContent = MW_I18N.summary_regenerate;
                if (data.summary.is_stale && window.appToast) {
                    window.appToast('info', `저장된 서머리(${data.summary.stale_days}일 전). 갱신은 [생성] 버튼.`, 4000);
                }
            } else {
                resetResult();
                if (window.appToast) window.appToast('warn', '저장된 서머리가 없습니다. [생성] 버튼으로 만드세요.', 3500);
                else alert('저장된 서머리가 없습니다.');
            }
        })
        .catch(() => {
            resetResult();
            alert('조회 실패');
        });
}

// ── 웍스 서머리 생성 ──────────────────────────────────────────────────
function generateSummary() {
    const pid = document.getElementById('ai-project-sel').value;
    const srCompanyIds = Array.from(document.querySelectorAll('.ai-sr-company:checked'))
        .map(cb => parseInt(cb.value, 10)).filter(Number.isFinite);

    // 프로젝트 또는 SR 회사 둘 중 하나는 있어야 함
    if (!pid && srCompanyIds.length === 0) { alert(MW_I18N.alert_select_project); return; }

    const weekDate = currentType === 'weekly' ? getCurrentWeekDate() : '';
    if (currentType === 'weekly' && !weekDate) { alert(MW_I18N.alert_select_week); return; }

    showLoading();
    const btn = document.getElementById('ai-gen-btn');
    btn.disabled = true;

    // 프로젝트가 있으면 프로젝트 라우트, 없으면 SR 전용 라우트
    let genUrl, body;
    if (pid) {
        const opt = document.getElementById('ai-project-sel').options[document.getElementById('ai-project-sel').selectedIndex];
        genUrl = opt?.dataset?.genUrl;
        body = { type: currentType, week: weekDate, sr_company_ids: srCompanyIds };
    } else {
        genUrl = SR_GEN_URL;
        body = { type: currentType, week: weekDate, sr_company_ids: srCompanyIds };
    }
    if (!genUrl) { btn.disabled = false; showError('생성 URL 을 찾을 수 없습니다.'); return; }

    fetch(genUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        if (data.error) {
            showError(data.error);
        } else {
            renderContent(data.content, data.generated_at, data.generated_by, data.metrics);
            renderCommitDetails(data.commit_details, data.common_commit_details);
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

    const wrap = document.getElementById('ai-commits-wrap');
    if (wrap) wrap.style.display = 'none';
}

// ── Git 커밋 내역 (관리자/매니저 only — wrap 자체가 페이지에 없으면 skip) ──
function renderCommitDetails(projectList, commonList) {
    const wrap = document.getElementById('ai-commits-wrap');
    if (!wrap) return;

    const total = (projectList?.length || 0) + (commonList?.length || 0);
    if (total === 0) { wrap.style.display = 'none'; return; }

    wrap.style.display = 'block';
    document.getElementById('ai-commits-count').textContent =
        `(프로젝트 ${projectList?.length || 0}건 / 공통 ${commonList?.length || 0}건)`;

    document.getElementById('ai-commits-project-body').innerHTML =
        renderCommitGroup(projectList, '프로젝트 영역');
    const commonBox = document.getElementById('ai-commits-common');
    if ((commonList?.length || 0) > 0) {
        commonBox.style.display = 'block';
        document.getElementById('ai-commits-common-body').innerHTML =
            renderCommitGroup(commonList, '공통 영역');
    } else {
        commonBox.style.display = 'none';
    }
}

function renderCommitGroup(commits, label) {
    if (!commits || commits.length === 0) return `<div style="color:#94a3b8;font-style:italic;">(${label} 커밋 없음)</div>`;
    return commits.map(c => {
        const diff = c.difficulty != null ? `<span style="font-size:11px;color:#7c3aed;font-weight:600;">난이도 ${Number(c.difficulty).toFixed(1)}</span>` : '';
        const files = (c.files || []).map(f =>
            `<div style="display:flex;gap:6px;padding:2px 0;font-family:ui-monospace,monospace;font-size:11.5px;">
                <span style="color:#64748b;width:60px;flex-shrink:0;">${escapeHtml(f.status || '')}</span>
                <span style="flex:1;word-break:break-all;color:#334155;">${escapeHtml(f.path || '')}</span>
                <span style="color:#16a34a;flex-shrink:0;">+${f.additions}</span>
                <span style="color:#dc2626;flex-shrink:0;">-${f.deletions}</span>
            </div>`
        ).join('');
        const moreNote = c.files_count > (c.files?.length || 0)
            ? `<div style="color:#94a3b8;font-size:11px;padding:2px 0;">… 외 ${c.files_count - c.files.length}건</div>`
            : '';
        const branchInfo = (c.first_branch || c.last_branch)
            ? `<div style="padding:4px 12px;border-top:1px solid #f1f5f9;font-size:11px;color:#64748b;display:flex;gap:10px;flex-wrap:wrap;">
                ${c.first_branch ? `<span><strong style="color:#0f172a;">최초:</strong> <code style="background:#eef2ff;padding:1px 5px;border-radius:3px;font-size:10.5px;color:#4338ca;">${escapeHtml(c.first_branch)}</code></span>` : ''}
                ${c.last_branch && c.last_branch !== c.first_branch ? `<span><strong style="color:#0f172a;">최후:</strong> <code style="background:#fef3c7;padding:1px 5px;border-radius:3px;font-size:10.5px;color:#a16207;">${escapeHtml(c.last_branch)}</code></span>` : ''}
                ${(c.branches && c.branches.length > 1) ? `<span style="color:#94a3b8;">(${c.branches.length}개 브랜치)</span>` : ''}
            </div>`
            : '';
        return `<details style="border:1px solid #e2e8f0;border-radius:6px;margin-bottom:6px;background:#fff;">
            <summary style="cursor:pointer;padding:8px 10px;font-size:12px;display:flex;gap:8px;align-items:center;list-style:none;">
                <code style="background:#f1f5f9;padding:1px 5px;border-radius:3px;font-size:10.5px;color:#475569;">${escapeHtml(c.sha)}</code>
                <span style="color:#64748b;font-size:11px;">${escapeHtml(c.date || '')}</span>
                <span style="color:#0f172a;font-weight:600;">${escapeHtml(c.author || '')}</span>
                <span style="flex:1;color:#334155;font-weight:500;">${escapeHtml(c.subject || '')}</span>
                <span style="color:#16a34a;font-size:11px;">+${c.add}</span>
                <span style="color:#dc2626;font-size:11px;">-${c.del}</span>
                ${diff}
                <span style="color:#94a3b8;font-size:11px;">${c.files_count}파일</span>
            </summary>
            ${branchInfo}
            <div style="padding:6px 12px 10px;border-top:1px solid #f1f5f9;">${files}${moreNote}</div>
        </details>`;
    }).join('');
}

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
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
function renderContent(content, generatedAt, generatedBy, metrics) {
    document.getElementById('ai-empty').style.display   = 'none';
    document.getElementById('ai-loading').style.display = 'none';
    document.getElementById('ai-error').style.display   = 'none';
    const box = document.getElementById('ai-content');
    box.style.display = 'block';
    box.innerHTML = mdToHtml(content);
    document.getElementById('ai-meta').textContent = generatedAt + '  ' + generatedBy + MW_I18N.generated_by_suffix;

    // 0) 최상단 — 담당자별 SR/Git 차트 (metrics 배열 또는 {project, common} 받음)
    renderTopChart(box, metrics);
    // 1) 담당자 정량 지표 표 → 가로 카드 그리드
    transformAssigneeTables(box);
    // 2) 담당자별 업무 평가 (### 이름) → 가로 카드 그리드
    transformAssigneeEvalSection(box);
    // 3) 난이도 분석 섹션 숨김
    hideSectionByTitle(box, '난이도 분석');
    // 4) 주요 이슈 / 해결 방안 — 항목별 가로 카드
    transformListSectionToCards(box, '주요 이슈');
    transformListSectionToCards(box, '해결 방안');
    // 5) 민감 키워드 필터
    applySensitiveKeywordFilters(box);

    const wordBtn = document.getElementById('ai-word-btn');
    if (wordBtn) wordBtn.style.display = 'inline-flex';
}

// ── 최상단 담당자별 SR/Git 막대 차트 ─────────────────────────────────
function renderTopChart(box, metrics) {
    // metrics 가 {project:[...], common:[...]} 이면 project 사용. 배열이면 그대로.
    let rows = [];
    if (Array.isArray(metrics)) rows = metrics;
    else if (metrics && Array.isArray(metrics.project)) rows = metrics.project;
    if (!rows.length) return;

    const data = rows
        .map(m => ({
            name: m.name || '—',
            sr:  Number(m.sr_completed || 0),
            git: Number(m.commits || 0),
        }))
        .filter(d => d.sr + d.git > 0);
    if (!data.length) return;

    const maxSr  = Math.max(1, ...data.map(d => d.sr));
    const maxGit = Math.max(1, ...data.map(d => d.git));

    const chart = document.createElement('div');
    chart.className = 'top-chart';
    chart.innerHTML =
        `<div class="top-chart-head">` +
            `<h3>📊 담당자별 SR·Git 활동</h3>` +
            `<div class="top-chart-legend">` +
                `<span><i style="background:#3b82f6"></i>SR 처리</span>` +
                `<span><i style="background:#7c3aed"></i>Git 반영</span>` +
            `</div>` +
        `</div>` +
        `<div class="top-chart-rows">` +
            data.map(d =>
                `<div class="top-chart-row">` +
                    `<div class="top-chart-name">${escapeHtml(d.name)}</div>` +
                    `<div class="top-chart-bars">` +
                        `<div class="top-chart-bar-row">` +
                            `<div class="top-chart-bar" style="width:${(d.sr/maxSr)*100}%;background:#3b82f6"></div>` +
                            `<span>${d.sr}</span>` +
                        `</div>` +
                        `<div class="top-chart-bar-row">` +
                            `<div class="top-chart-bar" style="width:${(d.git/maxGit)*100}%;background:#7c3aed"></div>` +
                            `<span>${d.git}</span>` +
                        `</div>` +
                    `</div>` +
                `</div>`
            ).join('') +
        `</div>`;

    box.insertBefore(chart, box.firstChild);
}

// 민감 키워드가 포함된 요소(li/p/카드) 권한별 필터링
function applySensitiveKeywordFilters(root) {
    const hideForAll       = ['증빙', '부재', '실패'];        // 모두 숨김
    const hideForNonAdmin  = ['불일치'];                      // 관리자 외 숨김
    const hideForNonManager = ['기한 재설정'];                // 매니저(관리자 포함) 외 숨김

    const candidates = root.querySelectorAll('li, p, .issue-card, .eval-card, .eval-card-body > *');
    candidates.forEach(el => {
        const txt = el.textContent || '';
        if (hideForAll.some(kw => txt.includes(kw))) {
            el.style.display = 'none';
            return;
        }
        if (!IS_ADMIN_USER && hideForNonAdmin.some(kw => txt.includes(kw))) {
            el.style.display = 'none';
            return;
        }
        if (!CAN_SEE_EVAL_BADGE && hideForNonManager.some(kw => txt.includes(kw))) {
            el.style.display = 'none';
        }
    });
}

// 특정 ## 섹션(헤딩 텍스트로 매칭)을 헤딩 + 다음 h2 전 sibling 들과 함께 숨김
function hideSectionByTitle(root, title) {
    const h2 = Array.from(root.querySelectorAll('h2')).find(h => h.textContent.trim().includes(title));
    if (!h2) return;
    h2.style.display = 'none';
    let n = h2.nextSibling;
    while (n) {
        const next = n.nextSibling;
        if (n.nodeType === 1) {
            if (n.tagName === 'H2') break;
            n.style.display = 'none';
        }
        n = next;
    }
}

// 특정 ## 섹션 안의 첫 <ul> 항목들을 가로 카드 그리드로 변환
function transformListSectionToCards(root, title) {
    const h2 = Array.from(root.querySelectorAll('h2')).find(h => h.textContent.trim().includes(title));
    if (!h2) return;
    // 다음 ul 찾기 (다음 h2 전에)
    let n = h2.nextElementSibling, ul = null;
    while (n && n.tagName !== 'H2') {
        if (n.tagName === 'UL') { ul = n; break; }
        n = n.nextElementSibling;
    }
    if (!ul) return;

    const items = Array.from(ul.children).filter(c => c.tagName === 'LI');
    if (items.length === 0) return;

    const grid = document.createElement('div');
    grid.className = 'issue-grid';
    items.forEach(li => {
        const card = document.createElement('div');
        card.className = 'issue-card';
        card.innerHTML = li.innerHTML;
        grid.appendChild(card);
    });
    ul.replaceWith(grid);
}

// 모든 ## H2 섹션을 카드로 wrap 한 뒤 가로 그리드로 배치
function transformSectionsToGrid(root) {
    const headings = Array.from(root.querySelectorAll('h2'));
    if (headings.length === 0) return;

    const grid = document.createElement('div');
    grid.className = 'section-grid';
    headings[0].parentNode.insertBefore(grid, headings[0]);

    headings.forEach(h => {
        // ⚠ h2 를 카드로 옮기기 전에 sibling 들을 먼저 수집 (옮긴 뒤엔 참조 불가)
        const siblings = [];
        let n = h.nextSibling;
        while (n) {
            if (n.nodeType === 1 && n.tagName === 'H2') break;
            siblings.push(n);
            n = n.nextSibling;
        }

        const card = document.createElement('section');
        card.className = 'section-card';
        card.appendChild(h);
        siblings.forEach(s => card.appendChild(s));
        grid.appendChild(card);
    });

    grid.querySelectorAll('p').forEach(p => {
        if (!p.textContent.trim() && p.children.length === 0) p.remove();
    });
}

// '## 담당자별 업무 평가' 다음의 '### 이름' 헤딩 단위를 가로 카드로 묶음
function transformAssigneeEvalSection(root) {
    const headings = Array.from(root.querySelectorAll('h2'));
    const target = headings.find(h => h.textContent.trim().includes('담당자별 업무 평가'));
    if (!target) return;

    // 다음 h2 를 만날 때까지의 요소들을 모음
    const collected = [];
    let n = target.nextElementSibling;
    while (n && n.tagName !== 'H2') {
        collected.push(n);
        n = n.nextElementSibling;
    }
    // h3 단위로 그룹화
    const groups = [];
    let cur = null;
    collected.forEach(el => {
        if (el.tagName === 'H3') {
            cur = { head: el, body: [] };
            groups.push(cur);
        } else if (cur) {
            cur.body.push(el);
        }
    });
    if (groups.length === 0) return;

    // 그리드 컨테이너 생성
    const grid = document.createElement('div');
    grid.className = 'eval-grid';

    groups.forEach(g => {
        const card = document.createElement('div');
        card.className = 'eval-card';

        // 라벨 자동 추출 (첫 li 안의 우수/양호/개선 필요/위험)
        const text = g.body.map(b => b.outerHTML).join('');
        let badgeClass = '';
        let labelText = '';
        const m = text.match(/<span class="ai-badge ai-badge-(good|ok|warn|bad)">([^<]+)<\/span>/);
        if (m) { badgeClass = m[1]; labelText = m[2]; }

        const name = g.head.textContent.trim();
        const head = document.createElement('div');
        head.className = 'eval-card-head';
        head.innerHTML =
            `<span class="eval-avatar">${escapeHtml(name).slice(0,1)}</span>` +
            `<span class="eval-name">${escapeHtml(name)}</span>` +
            (labelText && CAN_SEE_EVAL_BADGE ? `<span class="ai-badge ai-badge-${badgeClass}" style="margin-left:auto;">${escapeHtml(labelText)}</span>` : '');
        card.appendChild(head);

        const body = document.createElement('div');
        body.className = 'eval-card-body';
        g.body.forEach(el => body.appendChild(el.cloneNode(true)));
        card.appendChild(body);

        grid.appendChild(card);
    });

    // 원본 헤딩(들)·내용 제거 후 그리드로 교체
    target.insertAdjacentElement('afterend', grid);
    [...collected, ...groups.map(g => g.head)].forEach(el => el.remove());
}

// 첫 컬럼이 '담당자' 인 표를 가로 카드 그리드로 변환
function transformAssigneeTables(root) {
    const tables = root.querySelectorAll('table');
    tables.forEach(tbl => {
        const headers = Array.from(tbl.querySelectorAll('thead th')).map(th => th.textContent.trim());
        if (headers.length === 0 || headers[0] !== '담당자') return;

        const rows = Array.from(tbl.querySelectorAll('tbody tr'));
        if (rows.length === 0) return;

        const grid = document.createElement('div');
        grid.className = 'assignee-grid';

        rows.forEach(tr => {
            const cells = Array.from(tr.querySelectorAll('td')).map(td => td.innerHTML.trim());
            const name = cells[0] || '—';
            const card = document.createElement('div');
            card.className = 'assignee-card';

            // 헤더 (담당자명)
            const head = document.createElement('div');
            head.className = 'assignee-card-head';
            head.innerHTML = `<span class="assignee-avatar">${escapeHtml(name).slice(0,1)}</span><span class="assignee-name">${escapeHtml(name)}</span>`;
            card.appendChild(head);

            // 메트릭 행들 (1~N 컬럼)
            const body = document.createElement('div');
            body.className = 'assignee-card-body';
            for (let i = 1; i < headers.length; i++) {
                const row = document.createElement('div');
                row.className = 'assignee-metric';
                row.innerHTML = `<span class="assignee-metric-key">${escapeHtml(headers[i])}</span><span class="assignee-metric-val">${cells[i] ?? '—'}</span>`;
                body.appendChild(row);
            }
            card.appendChild(body);
            grid.appendChild(card);
        });

        tbl.replaceWith(grid);
    });
}

function downloadAiWord() {
    const sel     = document.getElementById('ai-project-sel');
    const opt     = sel.options[sel.selectedIndex];
    const baseUrl = opt?.dataset?.wordUrl;
    if (!baseUrl) return;

    const weekDate = currentType === 'weekly' ? getCurrentWeekDate() : '';
    const params   = new URLSearchParams({ type: currentType });
    if (weekDate) params.append('week', weekDate);

    window.location.href = baseUrl + '?' + params.toString();
}

// ── 마크다운 → HTML 변환 (테이블·코드·라벨 배지 포함) ──────────────────
function mdToHtml(text) {
    // 1) HTML 이스케이프 먼저
    let s = text.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // 2) 마크다운 테이블 파싱 — | col | col | 형태 연속 라인
    s = s.replace(/((?:^\|.+\|\n?)+)/gm, (block) => {
        const lines = block.trim().split('\n').filter(l => l.startsWith('|'));
        if (lines.length < 2) return block;
        // 두번째 줄이 separator (--- 형태) 인지
        const isSep = /^\|[\s:|-]+\|$/.test(lines[1].trim());
        if (!isSep) return block;
        const head = lines[0].split('|').slice(1, -1).map(c => c.trim());
        const body = lines.slice(2).map(r => r.split('|').slice(1, -1).map(c => c.trim()));
        const thead = '<thead><tr>' + head.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
        const tbody = '<tbody>' + body.map(r => '<tr>' + r.map(c => `<td>${c}</td>`).join('') + '</tr>').join('') + '</tbody>';
        return `<table>${thead}${tbody}</table>`;
    });

    // 3) 헤딩
    s = s.replace(/^#### (.+)$/gm,'<h3>$1</h3>')
         .replace(/^### (.+)$/gm,'<h3>$1</h3>')
         .replace(/^## (.+)$/gm,'<h2>$1</h2>')
         .replace(/^# (.+)$/gm,'<h1>$1</h1>');

    // 4) 강조 / 코드 / hr
    s = s.replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
         .replace(/`([^`]+)`/g,'<code>$1</code>')
         .replace(/^---$/gm,'<hr>');

    // 5) 불릿 리스트 (연속된 -/* 라인을 하나의 <ul> 로)
    s = s.replace(/(?:^[ \t]*[-*•] .+(?:\n|$))+/gm, (block) => {
        const items = block.split('\n')
            .filter(l => /^[ \t]*[-*•] /.test(l))
            .map(l => l.replace(/^[ \t]*[-*•] /, ''))
            .map(l => `<li>${l}</li>`)
            .join('');
        return `<ul>${items}</ul>`;
    });

    // 6) 단락 / 줄바꿈 — 테이블 내부는 손대지 않게 보호
    const protect = [];
    s = s.replace(/<(table|ul|h[1-3]|hr|pre)[\s\S]*?<\/\1>|<hr>/g, (m) => {
        protect.push(m); return `${protect.length - 1}`;
    });
    s = s.split(/\n{2,}/).map(p => p.trim() ? `<p>${p.replace(/\n/g,'<br>')}</p>` : '').join('');
    s = s.replace(/(\d+)/g, (_, i) => protect[Number(i)]);

    // 7) 평가 라벨 — 관리자/매니저만 컬러 배지. 그 외 사용자는 일반 텍스트로 노출.
    if (CAN_SEE_EVAL_BADGE) {
        s = s.replace(/(?:^|\s|>|\()(우수|양호|개선 ?필요|위험)(?=\s|<|\)|,|\.|$)/g, (m, w) => {
            const cls = w === '우수' ? 'good' : w === '양호' ? 'ok' : (w.replace(' ','') === '개선필요' ? 'warn' : 'bad');
            return m.replace(w, `<span class="ai-badge ai-badge-${cls}">${w}</span>`);
        });
    }
    s = s.replace(/(?:^|\s|>|\()(쉬움|보통-쉬움|보통|어려움|매우 ?어려움)(?=\s|<|\)|,|\.|$)/g, (m, w) => {
        const k = w.replace(' ','');
        const cls = k === '쉬움' ? 'easy' : (k === '보통-쉬움' || k === '보통') ? 'medium' : (k === '어려움' ? 'hard' : 'critical');
        return m.replace(w, `<span class="ai-badge ai-badge-${cls}">${w}</span>`);
    });

    return s;
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
