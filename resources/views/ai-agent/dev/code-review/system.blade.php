@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.sys-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.sys-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.sys-header p  { font-size:13.5px; color:#64748b; margin:0; }

.sys-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.sys-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.sys-btn.secondary:hover { background:#e2e8f0; }
.sys-btn.ghost { background:transparent; color:#7c3aed; border:1.5px solid #c4b5fd; }
.sys-btn.ghost:hover { background:#f5f3ff; }

.sys-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px; margin-bottom:16px; }
.sys-section-title { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:8px; }

.sys-score-block { display:flex; align-items:center; gap:20px; flex-wrap:wrap; margin-bottom:16px; }
.sys-score-num { font-size:52px; font-weight:800; line-height:1; }
.sys-score-num.good { color:#15803d; }
.sys-score-num.warn { color:#a16207; }
.sys-score-num.bad  { color:#b91c1c; }
.sys-score-sub { font-size:18px; color:#94a3b8; }

.sys-summary { font-size:14px; color:#334155; line-height:1.75; padding:16px 20px; background:#f5f3ff; border-radius:10px; border-left:4px solid #7c3aed; margin-bottom:16px; }

.sys-arch { font-size:13.5px; color:#334155; line-height:1.7; padding:14px 16px; background:#f8fafc; border-radius:10px; border:1.5px solid #e2e8f0; }

.issue-card { border-radius:10px; padding:14px 16px; margin-bottom:10px; border-left:4px solid; }
.issue-card.critical { border-color:#f87171; background:#fff1f2; }
.issue-card.warning  { border-color:#fbbf24; background:#fffbeb; }
.issue-card.info     { border-color:#60a5fa; background:#eff6ff; }
.issue-title { font-size:13.5px; font-weight:700; color:#1e1b2e; margin-bottom:4px; }
.issue-desc  { font-size:13px; color:#475569; margin-bottom:6px; }
.issue-affected { font-size:11.5px; color:#7c3aed; }
.issue-suggestion { font-size:12.5px; color:#334155; margin-top:8px; padding:8px 12px; background:rgba(255,255,255,.7); border-radius:7px; border-left:2px solid #c4b5fd; }
.sev-badge { font-size:10.5px; font-weight:700; text-transform:uppercase; border-radius:4px; padding:2px 7px; display:inline-block; margin-right:6px; }
.sev-badge.critical { background:#fee2e2; color:#b91c1c; }
.sev-badge.warning  { background:#fef9c3; color:#a16207; }
.sev-badge.info     { background:#dbeafe; color:#1d4ed8; }

.concern-list { list-style:none; padding:0; margin:0; }
.concern-list li { display:flex; align-items:flex-start; gap:8px; font-size:13px; color:#334155; padding:6px 0; border-bottom:1px dashed #ede8ff; }
.concern-list li:last-child { border-bottom:none; }
.concern-list li::before { content:'•'; color:#7c3aed; font-weight:700; flex-shrink:0; margin-top:1px; }

.strength-list { list-style:none; padding:0; margin:0; }
.strength-list li { display:flex; align-items:flex-start; gap:10px; font-size:13px; color:#334155; padding:6px 0; border-bottom:1px dashed #dcfce7; }
.strength-list li:last-child { border-bottom:none; }
.strength-list li::before { content:'✓'; color:#4ade80; font-weight:700; flex-shrink:0; }

.meta-pill { display:inline-flex; align-items:center; gap:6px; font-size:11.5px; color:#64748b; background:#f8fafc; border:1px solid #e2e8f0; border-radius:99px; padding:3px 12px; margin-right:6px; margin-bottom:6px; }
</style>
@endpush

@section('ai-agent-content')
<div>
    {{-- Header --}}
    <div class="sys-header">
        <div>
            <h1>시스템 종합 코드 리뷰</h1>
            <p>전체 프로젝트 아키텍처 · 데이터 흐름 · 교차 관심사 평가</p>
        </div>
        <a href="{{ $indexUrl }}" class="sys-btn secondary">← 목록으로</a>
    </div>

    @if(!$decoded)
    <div class="sys-section" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:32px;margin-bottom:8px;">📊</div>
        <div style="font-weight:700;color:#475569;">시스템 종합 리뷰가 없습니다</div>
        <div style="font-size:13px;margin-top:6px;">화면별 리뷰 완료 후 배치 실행 시 자동으로 생성됩니다.</div>
        <div style="margin-top:16px;">
            <a href="{{ $indexUrl }}" class="sys-btn ghost">리뷰 목록으로</a>
        </div>
    </div>
    @else

    @php
        $overallScore = $decoded['overall_score'] ?? 0;
        $scoreCls = $overallScore >= 80 ? 'good' : ($overallScore >= 60 ? 'warn' : 'bad');
        $meta = $decoded['$metadata'] ?? [];
    @endphp

    {{-- Meta pills --}}
    <div style="margin-bottom:14px;">
        @if(!empty($meta['reviewed_at']))
        <span class="meta-pill">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><polyline stroke-width="2" points="12 6 12 12 16 14"/></svg>
            {{ \Carbon\Carbon::parse($meta['reviewed_at'])->format('Y-m-d H:i') }}
        </span>
        @endif
        @if(!empty($meta['screen_count']))
        <span class="meta-pill">화면 {{ $meta['screen_count'] }}개</span>
        @endif
        @if(!empty($meta['avg_screen_score']))
        <span class="meta-pill">화면 평균 {{ $meta['avg_screen_score'] }}점</span>
        @endif
        @if(!empty($meta['model']))
        <span class="meta-pill">{{ $meta['model'] }}</span>
        @endif
    </div>

    {{-- Score + Summary --}}
    <div class="sys-section">
        <div class="sys-score-block">
            <div>
                <div style="font-size:12px;font-weight:700;color:#7c6fa0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:6px;">시스템 종합 점수</div>
                <span class="sys-score-num {{ $scoreCls }}">{{ $overallScore }}</span>
                <span class="sys-score-sub">/100</span>
            </div>
        </div>

        @if(!empty($decoded['executive_summary']))
        <div class="sys-summary">{{ $decoded['executive_summary'] }}</div>
        @endif

        @if(!empty($decoded['architecture_assessment']))
        <div>
            <div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:8px;">아키텍처 평가</div>
            <div class="sys-arch">{{ $decoded['architecture_assessment'] }}</div>
        </div>
        @endif
    </div>

    {{-- Data flow issues --}}
    @if(!empty($decoded['data_flow_issues']))
    <div class="sys-section">
        <div class="sys-section-title">
            데이터 흐름 이슈
            <span style="font-size:11px;font-weight:500;color:#94a3b8;">{{ count($decoded['data_flow_issues']) }}건</span>
        </div>
        @foreach($decoded['data_flow_issues'] as $issue)
        @php $sev = $issue['severity'] ?? 'warning'; @endphp
        <div class="issue-card {{ $sev }}">
            <div>
                <span class="sev-badge {{ $sev }}">{{ strtoupper($sev) }}</span>
                <span class="issue-title">{{ $issue['title'] }}</span>
            </div>
            <div class="issue-desc">{{ $issue['description'] }}</div>
            @if(!empty($issue['affected_screens']))
            <div class="issue-affected">영향 화면: {{ implode(', ', $issue['affected_screens']) }}</div>
            @endif
            @if(!empty($issue['affected_resources']))
            <div class="issue-affected">영향 리소스: {{ implode(', ', $issue['affected_resources']) }}</div>
            @endif
            @if(!empty($issue['suggestion']))
            <div class="issue-suggestion">💡 {{ $issue['suggestion'] }}</div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- Cross-cutting concerns --}}
    @if(!empty($decoded['cross_cutting_concerns']))
    <div class="sys-section">
        <div class="sys-section-title">교차 관심사 (Cross-cutting concerns)</div>
        <ul class="concern-list">
            @foreach($decoded['cross_cutting_concerns'] as $concern)
            <li>{{ $concern }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- Strengths --}}
    @if(!empty($decoded['strengths']))
    <div class="sys-section" style="border-color:#bbf7d0;background:#f0fdf4;">
        <div class="sys-section-title" style="color:#15803d;">잘된 점 ✅</div>
        <ul class="strength-list">
            @foreach($decoded['strengths'] as $s)
            <li>{{ $s }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @endif
</div>
@endsection
