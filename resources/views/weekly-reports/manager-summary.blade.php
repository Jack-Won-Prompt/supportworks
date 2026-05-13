@extends('layouts.app')

@section('title', '위클리 웍스 서머리 - ' . $project->name)

@section('header-actions')@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.weekly-reports.index', $project) }}" class="hover:text-indigo-500 transition-colors">주간 업무 보고</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">웍스 서머리 종합</span>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'weekly-reports'])

@php
    $analyzeUrl  = route('projects.weekly-reports.analyze', $project);
    $downloadUrl = route('projects.weekly-reports.manager-summary.download', $project);
    $totalCount  = $grouped->flatten()->count();
@endphp

<div class="space-y-5">

{{-- ── 필터 + 액션 바 ── --}}
<div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:14px 18px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

    {{-- 주차 탭 --}}
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;flex:1;">
        <a href="{{ route('projects.weekly-reports.manager-summary', ['project'=>$project,'week'=>'all']) }}"
            style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:7px;font-size:12.5px;font-weight:600;text-decoration:none;transition:all .15s;
            {{ $showAll ? 'background:#4f46e5;color:#fff;' : 'border:1.5px solid #d1d5db;color:#6b7280;background:#fff;' }}"
            onmouseover="if(!this.style.background.includes('46e5'))this.style.background='#f5f3ff'"
            onmouseout="if(!this.style.background.includes('46e5'))this.style.background='#fff'">
            전체
        </a>
        @foreach($allWeeks->take(8) as $wk)
        @php
            $wDate = $wk->week_start_date instanceof \Carbon\Carbon
                ? $wk->week_start_date->format('Y-m-d')
                : \Carbon\Carbon::parse($wk->week_start_date)->format('Y-m-d');
            $wLabel = \Carbon\Carbon::parse($wDate)->locale('ko')->isoFormat('YYYY년 M월 W주차');
            // Use the model's week_label if available
            $tempReport = $grouped->get($wDate)?->first();
            if ($tempReport) $wLabel = $tempReport->week_label;
            $isActive = !$showAll && $selectedWeek === $wDate;
        @endphp
        <a href="{{ route('projects.weekly-reports.manager-summary', ['project'=>$project,'week'=>$wDate]) }}"
            style="display:inline-flex;align-items:center;padding:5px 12px;border-radius:7px;font-size:12.5px;font-weight:600;text-decoration:none;white-space:nowrap;transition:all .15s;
            {{ $isActive ? 'background:#4f46e5;color:#fff;' : 'border:1.5px solid #d1d5db;color:#6b7280;background:#fff;' }}"
            onmouseover="if(!this.style.background.includes('46e5'))this.style.background='#f5f3ff'"
            onmouseout="if(!this.style.background.includes('46e5'))this.style.background='#fff'">
            {{ $wLabel }}
        </a>
        @endforeach
        @if($allWeeks->count() > 8)
        <span style="font-size:11.5px;color:#9ca3af;">+{{ $allWeeks->count() - 8 }}개</span>
        @endif
    </div>

    {{-- Word 다운로드 --}}
    <button type="button" onclick="openDownloadModal()"
        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1.5px solid #c4b5fd;border-radius:8px;font-size:12.5px;font-weight:600;color:#6d28d9;background:#faf5ff;cursor:pointer;transition:all .15s;"
        onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Word 다운로드
    </button>

    {{-- 웍스 종합 분석 --}}
    <button type="button" onclick="openAiAnalysis()"
        style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1.5px solid #a7f3d0;border-radius:8px;font-size:12.5px;font-weight:600;color:#065f46;background:#f0fdf4;cursor:pointer;transition:all .15s;"
        onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        웍스 종합 분석
    </button>
</div>

{{-- ── 리포트 카운트 헤더 ── --}}
@if($totalCount > 0)
<div style="font-size:13px;color:#6b7280;padding:0 2px;">
    {{ $showAll ? '전체 주차' : ($grouped->first()?->first()?->week_label ?? '') }}
    — 총 <strong style="color:#4f46e5;">{{ $totalCount }}</strong>건 ·
    <strong style="color:#059669;">{{ $grouped->flatten()->where('status','submitted')->count() }}</strong>건 제출 완료
</div>
@endif

{{-- ── 내용 없을 때 ── --}}
@if($totalCount === 0)
<div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:60px 24px;text-align:center;">
    <div style="width:52px;height:52px;background:#f5f3ff;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
        <svg width="24" height="24" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <p style="font-size:14px;font-weight:600;color:#374151;margin-bottom:6px;">표시할 보고서가 없습니다.</p>
    <p style="font-size:12.5px;color:#9ca3af;">다른 주차를 선택하거나 팀원에게 보고서 작성을 요청하세요.</p>
</div>
@else

{{-- ── 주차별 그룹 ── --}}
@foreach($grouped as $weekDate => $weekReports)
@php
    $firstRpt  = $weekReports->first();
    $weekLabel = $firstRpt->week_label;
    $wStart    = \Carbon\Carbon::parse($weekDate);
    $wEnd      = $wStart->copy()->addDays(6);
    $submitted = $weekReports->where('status','submitted')->count();
@endphp
<div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;overflow:hidden;">

    {{-- 주차 헤더 --}}
    <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-bottom:1px solid #ddd6fe;">
        <span style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:7px;padding:3px 11px;font-size:12.5px;font-weight:700;">{{ $weekLabel }}</span>
        <span style="font-size:12px;color:#7c3aed;font-weight:500;">{{ $wStart->format('m/d') }} ~ {{ $wEnd->format('m/d') }}</span>
        <span style="font-size:11.5px;color:#9ca3af;margin-left:auto;">
            {{ $weekReports->count() }}명 작성  ·  제출 완료 {{ $submitted }}명
        </span>
    </div>

    {{-- 멤버 카드 목록 --}}
    @foreach($weekReports as $report)
    @php
        $hasSummary  = !empty(trim(strip_tags($report->summary ?? '')));
        $currentDone = $report->tasks->where('section','current_week')->where('status','completed')->count();
        $currentProg = $report->tasks->where('section','current_week')->where('status','in_progress')->count();
        $nextCount   = $report->tasks->where('section','next_week')->count();
        $cardId      = 'card-' . $report->id;
    @endphp
    <div style="border-bottom:1px solid #f3f4f6;" id="{{ $cardId }}">

        {{-- 카드 헤더 (클릭 시 확장) --}}
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;cursor:pointer;transition:background .12s;"
            onclick="toggleCard('{{ $cardId }}')"
            onmouseover="this.style.background='#fafaf9'" onmouseout="this.style.background=''">

            {{-- 아바타 --}}
            <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#6d28d9,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:#fff;font-size:14px;font-weight:700;">{{ mb_substr($report->author_name, 0, 1) }}</span>
            </div>

            {{-- 이름 + 팀 + 상태 --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:14px;font-weight:700;color:#1f2937;">{{ $report->author_name }}</span>
                    @if($report->team_name)
                    <span style="background:#f3f4f6;color:#6b7280;border-radius:4px;font-size:11px;padding:1px 7px;">{{ $report->team_name }}</span>
                    @endif
                    @if($report->status === 'submitted')
                    <span style="background:#d1fae5;color:#065f46;border-radius:5px;font-size:11px;font-weight:600;padding:1px 7px;">제출 완료</span>
                    @else
                    <span style="background:#fef3c7;color:#92400e;border-radius:5px;font-size:11px;font-weight:600;padding:1px 7px;">임시 저장</span>
                    @endif
                    @if(!$hasSummary)
                    <span style="background:#fee2e2;color:#991b1b;border-radius:5px;font-size:11px;padding:1px 7px;">서머리 없음</span>
                    @endif
                </div>
                <div style="margin-top:3px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="font-size:11.5px;color:#9ca3af;">{{ $report->report_date->format('Y.m.d') }}</span>
                    <span style="font-size:11.5px;color:#9ca3af;">
                        완료 {{ $currentDone }}  ·  진행 {{ $currentProg }}  ·  차주 {{ $nextCount }}
                    </span>
                </div>
            </div>

            {{-- 액션 + 토글 화살표 --}}
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <a href="{{ route('projects.weekly-reports.download', [$project, $report]) }}"
                    onclick="event.stopPropagation()"
                    style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border:1.5px solid #c4b5fd;border-radius:6px;font-size:11.5px;font-weight:600;color:#6d28d9;text-decoration:none;background:#faf5ff;transition:all .12s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Word
                </a>
                <svg id="arrow-{{ $report->id }}" width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                    style="transition:transform .2s;transform:rotate(0deg);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
        </div>

        {{-- 확장 패널 --}}
        <div id="panel-{{ $report->id }}" style="display:none;border-top:1px solid #f3f4f6;background:#fafaf9;">
            <div style="padding:20px 24px;display:grid;grid-template-columns:1fr 1fr;gap:20px;">

                {{-- 좌: 웍스 서머리 --}}
                <div>
                    <div style="font-size:12px;font-weight:700;color:#4f46e5;margin-bottom:10px;display:flex;align-items:center;gap:5px;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        주요 성과 요약
                    </div>
                    @if($hasSummary)
                    <div style="font-size:12.5px;line-height:1.75;color:#374151;background:#fff;border:1px solid #e9e7fb;border-radius:8px;padding:12px 14px;max-height:220px;overflow-y:auto;">
                        {!! nl2br(e(strip_tags($report->summary))) !!}
                    </div>
                    @else
                    <div style="font-size:12px;color:#9ca3af;background:#f9fafb;border:1px dashed #d1d5db;border-radius:8px;padding:14px;text-align:center;">작성된 서머리가 없습니다.</div>
                    @endif

                    @if($report->special_notes)
                    <div style="margin-top:12px;">
                        <div style="font-size:11.5px;font-weight:600;color:#dc2626;margin-bottom:6px;">특이 사항</div>
                        <div style="font-size:12px;line-height:1.65;color:#374151;background:#fef2f2;border:1px solid #fca5a5;border-radius:7px;padding:10px 12px;">
                            {!! nl2br(e($report->special_notes)) !!}
                        </div>
                    </div>
                    @endif
                </div>

                {{-- 우: 업무 현황 --}}
                <div>
                    @php
                        $completed  = $report->tasks->where('section','current_week')->where('status','completed');
                        $inProgress = $report->tasks->where('section','current_week')->where('status','in_progress');
                        $pending    = $report->tasks->where('section','current_week')->whereIn('status',['pending','planned']);
                        $next       = $report->tasks->where('section','next_week');
                    @endphp

                    {{-- 금주 완료 --}}
                    @if($completed->isNotEmpty())
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11.5px;font-weight:700;color:#059669;margin-bottom:5px;">✔ 완료 ({{ $completed->count() }})</div>
                        @foreach($completed as $t)
                        <div style="font-size:12px;color:#1f2937;padding:3px 0 3px 10px;border-left:3px solid #6ee7b7;">{{ $t->task_name }}</div>
                        @endforeach
                    </div>
                    @endif

                    {{-- 진행 중 --}}
                    @if($inProgress->isNotEmpty())
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11.5px;font-weight:700;color:#d97706;margin-bottom:5px;">▶ 진행 중 ({{ $inProgress->count() }})</div>
                        @foreach($inProgress as $t)
                        <div style="font-size:12px;color:#1f2937;padding:3px 0 3px 10px;border-left:3px solid #fbbf24;">{{ $t->task_name }}</div>
                        @endforeach
                    </div>
                    @endif

                    {{-- 미착수 --}}
                    @if($pending->isNotEmpty())
                    <div style="margin-bottom:12px;">
                        <div style="font-size:11.5px;font-weight:700;color:#6b7280;margin-bottom:5px;">○ 미착수 ({{ $pending->count() }})</div>
                        @foreach($pending as $t)
                        <div style="font-size:12px;color:#6b7280;padding:3px 0 3px 10px;border-left:3px solid #d1d5db;">{{ $t->task_name }}</div>
                        @endforeach
                    </div>
                    @endif

                    {{-- 차주 계획 --}}
                    @if($next->isNotEmpty())
                    <div>
                        <div style="font-size:11.5px;font-weight:700;color:#7c3aed;margin-bottom:5px;">→ 차주 계획 ({{ $next->count() }})</div>
                        @foreach($next as $t)
                        <div style="font-size:12px;color:#1f2937;padding:3px 0 3px 10px;border-left:3px solid #c4b5fd;">
                            {{ $t->task_name }}
                            @if($t->end_date)
                            <span style="color:#9ca3af;font-size:11px;margin-left:5px;">~ {{ $t->end_date->format('m/d') }}</span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif

                    @if($report->tasks->isEmpty())
                    <div style="font-size:12px;color:#9ca3af;text-align:center;padding:20px 0;">등록된 업무가 없습니다.</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach

</div>
@endforeach

@endif

</div>{{-- .space-y-5 --}}

{{-- ══════════════ Word 다운로드 모달 ══════════════ --}}
<div id="dl-modal-backdrop" style="display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,23,42,.5);align-items:center;justify-content:center;padding:20px;">
    <div id="dl-modal-panel" style="background:#fff;border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.22);width:100%;max-width:420px;overflow:hidden;opacity:0;transform:scale(.96) translateY(10px);transition:opacity .2s ease,transform .2s ease;">
        {{-- 헤더 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #f3f4f6;">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg width="18" height="18" fill="none" stroke="#6d28d9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span style="font-size:15px;font-weight:700;color:#1f2937;">Word 다운로드</span>
            </div>
            <button onclick="closeDlModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#9ca3af;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="dl-form" method="POST" action="{{ $downloadUrl }}">
            @csrf
            <div style="padding:20px;">
                <p style="font-size:13px;color:#6b7280;margin-bottom:16px;">다운로드 범위를 선택하세요.</p>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid #e9e7fb;border-radius:9px;cursor:pointer;transition:border-color .15s;"
                        onclick="selectDlRange(this,'current')">
                        <input type="radio" name="week" value="{{ $showAll ? 'all' : ($selectedWeek ?? 'all') }}" id="dl-current" checked style="accent-color:#6d28d9;width:15px;height:15px;">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:#1f2937;">
                                {{ $showAll ? '전체 주차' : ($grouped->first()?->first()?->week_label ?? '현재 필터') }}
                            </div>
                            <div style="font-size:11.5px;color:#9ca3af;margin-top:1px;">현재 보기 기준 ({{ $totalCount }}건)</div>
                        </div>
                    </label>
                    <label style="display:flex;align-items:center;gap:10px;padding:12px 14px;border:1.5px solid #e9e7fb;border-radius:9px;cursor:pointer;transition:border-color .15s;"
                        onclick="selectDlRange(this,'all')">
                        <input type="radio" name="week" value="all" id="dl-all" style="accent-color:#6d28d9;width:15px;height:15px;">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:#1f2937;">전체 주차 종합</div>
                            <div style="font-size:11.5px;color:#9ca3af;margin-top:1px;">모든 주차 포함</div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="padding:0 20px 20px;display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" onclick="closeDlModal()"
                    style="padding:8px 16px;border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;color:#374151;background:#fff;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">
                    취소
                </button>
                <button type="submit"
                    style="padding:8px 18px;background:#4f46e5;border:none;border-radius:8px;font-size:13px;font-weight:600;color:#fff;cursor:pointer;transition:opacity .15s;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Word 다운로드
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ══════════════ 웍스 종합 분석 모달 ══════════════ --}}
<div id="ai-modal-backdrop" style="display:none;position:fixed;inset:0;z-index:1300;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px;">
    <div id="ai-modal-panel" style="background:#fff;border-radius:14px;box-shadow:0 24px 80px rgba(0,0,0,.22);width:100%;max-width:760px;max-height:88vh;display:flex;flex-direction:column;opacity:0;transform:scale(.96) translateY(10px);transition:opacity .2s ease,transform .2s ease;overflow:hidden;">

        {{-- 헤더 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg width="18" height="18" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span style="font-size:15px;font-weight:700;color:#1f2937;">웍스 종합 분석</span>
                <span id="ai-modal-scope" style="font-size:12px;color:#6b7280;"></span>
            </div>
            <button onclick="closeAiModal()" style="background:none;border:none;cursor:pointer;padding:4px;color:#9ca3af;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- 로딩 --}}
        <div id="ai-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:50px 24px;gap:14px;flex:1;">
            <div style="width:40px;height:40px;border:3px solid #d1fae5;border-top-color:#059669;border-radius:50%;animation:spin .7s linear infinite;"></div>
            <p style="font-size:13.5px;color:#6b7280;">웍스가 보고서를 분석하고 있습니다…</p>
        </div>

        {{-- 결과 --}}
        <div id="ai-modal-body" style="display:none;flex:1;overflow-y:auto;padding:24px;font-size:13.5px;line-height:1.75;color:#374151;"></div>

        {{-- 에러 --}}
        <div id="ai-error" style="display:none;padding:24px;text-align:center;">
            <p style="font-size:13.5px;color:#dc2626;" id="ai-error-msg"></p>
        </div>

        {{-- 푸터 --}}
        <div id="ai-footer" style="display:none;padding:12px 20px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;">
            <button onclick="closeAiModal()"
                style="padding:7px 16px;border:1.5px solid #d1d5db;border-radius:8px;font-size:13px;font-weight:600;color:#374151;background:#fff;cursor:pointer;"
                onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">닫기</button>
        </div>
    </div>
</div>

<style>
@keyframes spin { to { transform:rotate(360deg); } }
#ai-modal-body h1,#ai-modal-body h2,#ai-modal-body h3{font-weight:700;color:#1f2937;margin:16px 0 6px;}
#ai-modal-body h1{font-size:16px;}#ai-modal-body h2{font-size:14.5px;}#ai-modal-body h3{font-size:13.5px;}
#ai-modal-body ul,#ai-modal-body ol{padding-left:20px;margin:6px 0;}
#ai-modal-body li{margin:2px 0;}
#ai-modal-body strong{font-weight:700;color:#1f2937;}
#ai-modal-body hr{border:none;border-top:1px solid #e5e7eb;margin:14px 0;}
#ai-modal-body p{margin:6px 0;}
</style>

@push('scripts')
<script>
const ANALYZE_URL = '{{ $analyzeUrl }}';
const CSRF_TOKEN  = '{{ csrf_token() }}';

// ── 카드 토글 ──────────────────────────────────────────────────
function toggleCard(cardId) {
    const reportId = cardId.replace('card-', '');
    const panel  = document.getElementById('panel-' + reportId);
    const arrow  = document.getElementById('arrow-' + reportId);
    const isOpen = panel.style.display !== 'none';
    panel.style.display = isOpen ? 'none' : 'block';
    arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
}

// ── Word 다운로드 모달 ───────────────────────────────────────────
function openDownloadModal() {
    const bd = document.getElementById('dl-modal-backdrop');
    bd.style.display = 'flex';
    requestAnimationFrame(() => {
        document.getElementById('dl-modal-panel').style.opacity = '1';
        document.getElementById('dl-modal-panel').style.transform = 'scale(1) translateY(0)';
    });
}
function closeDlModal() {
    const panel = document.getElementById('dl-modal-panel');
    panel.style.opacity = '0';
    panel.style.transform = 'scale(.96) translateY(10px)';
    setTimeout(() => { document.getElementById('dl-modal-backdrop').style.display = 'none'; }, 200);
}
function selectDlRange(label, type) {
    document.querySelectorAll('#dl-form label').forEach(l => {
        l.style.borderColor = '#e9e7fb';
    });
    label.style.borderColor = '#6d28d9';
    if (type === 'current') document.getElementById('dl-current').checked = true;
    else document.getElementById('dl-all').checked = true;
}
document.addEventListener('DOMContentLoaded', () => {
    const firstLabel = document.querySelector('#dl-form label');
    if (firstLabel) firstLabel.style.borderColor = '#6d28d9';
});

// ── 웍스 종합 분석 ─────────────────────────────────────────────────
function openAiAnalysis() {
    const bd = document.getElementById('ai-modal-backdrop');
    bd.style.display = 'flex';
    requestAnimationFrame(() => {
        document.getElementById('ai-modal-panel').style.opacity = '1';
        document.getElementById('ai-modal-panel').style.transform = 'scale(1) translateY(0)';
    });
    document.getElementById('ai-loading').style.display = 'flex';
    document.getElementById('ai-modal-body').style.display = 'none';
    document.getElementById('ai-error').style.display = 'none';
    document.getElementById('ai-footer').style.display = 'none';
    document.getElementById('ai-modal-scope').textContent = '';

    const totalCount = @json($totalCount);
    @php
        $_aiScope = $showAll
            ? "전체 주차 · {$totalCount}건"
            : (($grouped->first()?->first()?->week_label ?? '') . " · {$totalCount}건");
    @endphp
    const scopeLabel = @json($_aiScope);
    document.getElementById('ai-modal-scope').textContent = '— ' + scopeLabel;

    const weekParam = @json($showAll ? 'all' : ($selectedWeek ?? 'all'));

    fetch(ANALYZE_URL, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF_TOKEN,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ type: 'all', week: weekParam }),
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('ai-loading').style.display = 'none';
        document.getElementById('ai-footer').style.display = 'flex';
        if (data.error) {
            document.getElementById('ai-error-msg').textContent = data.error;
            document.getElementById('ai-error').style.display = 'block';
        } else {
            document.getElementById('ai-modal-body').style.display = 'block';
            document.getElementById('ai-modal-body').innerHTML = markdownToHtml(data.result || '');
        }
    })
    .catch(err => {
        document.getElementById('ai-loading').style.display = 'none';
        document.getElementById('ai-footer').style.display = 'flex';
        document.getElementById('ai-error-msg').textContent = '웍스 분석 요청 중 오류가 발생했습니다.';
        document.getElementById('ai-error').style.display = 'block';
    });
}
function closeAiModal() {
    const panel = document.getElementById('ai-modal-panel');
    panel.style.opacity = '0';
    panel.style.transform = 'scale(.96) translateY(10px)';
    setTimeout(() => { document.getElementById('ai-modal-backdrop').style.display = 'none'; }, 200);
}

// ── 마크다운 → HTML (간단 변환) ──────────────────────────────────
function markdownToHtml(text) {
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

// ── 배경 클릭 닫기 ───────────────────────────────────────────────
document.getElementById('dl-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeDlModal();
});
document.getElementById('ai-modal-backdrop').addEventListener('click', function(e) {
    if (e.target === this) closeAiModal();
});
</script>
@endpush

@endsection
