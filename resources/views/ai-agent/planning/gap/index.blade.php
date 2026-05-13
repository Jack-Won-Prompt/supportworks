@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.gap-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.gap-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.gap-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.gap-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

.gap-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.gap-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.gap-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.gap-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.gap-btn.primary:hover   { background:var(--t700,#6d28d9); }
.gap-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.gap-btn.secondary:hover { background:#e2e8f0; }
.gap-btn.danger    { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.gap-btn.danger:hover    { background:#fee2e2; }
.gap-btn:disabled  { opacity:.45; cursor:not-allowed; }
.gap-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Prerequisites ───────────────────────────────────────────────── */
.gap-prereq { display:flex; align-items:flex-start; gap:10px; padding:12px 16px; border-radius:10px; border:1.5px solid; }
.gap-prereq.ready   { background:#f0fdf4; border-color:#bbf7d0; }
.gap-prereq.missing { background:#fef2f2; border-color:#fca5a5; }
.gap-prereq-icon { font-size:18px; flex-shrink:0; }
.gap-prereq-label { font-size:13px; font-weight:600; }
.gap-prereq-hint  { font-size:12px; color:#64748b; margin-top:2px; }

/* ── Status stat bar ─────────────────────────────────────────────── */
.gap-status-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.gap-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 16px; }
.gap-stat-num   { font-size:20px; font-weight:800; color:var(--t600,#7c3aed); }
.gap-stat-label { font-size:11.5px; color:#64748b; line-height:1.4; }

/* ── Summary ─────────────────────────────────────────────────────── */
.gap-result-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:20px; }
.gap-result-header { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid #f3eeff; flex-wrap:wrap; }
.gap-result-title  { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; }
.gap-result-body   { padding:20px 22px; }
.gap-summary-text  { font-size:14px; color:#374151; line-height:1.8; white-space:pre-line; }
.gap-summary-textarea { width:100%; min-height:120px; border:1.5px solid #c4b5fd; border-radius:10px; padding:12px 14px; font-size:14px; line-height:1.8; color:#374151; resize:vertical; font-family:inherit; }

/* ── Severity / effort badges ────────────────────────────────────── */
.gap-severity { display:inline-flex; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:.05em; }
.gap-severity.high   { background:#fef2f2; color:#dc2626; }
.gap-severity.medium { background:#fffbeb; color:#d97706; }
.gap-severity.low    { background:#f8fafc; color:#64748b; }
.gap-effort.high   { background:#fef2f2; color:#dc2626; }
.gap-effort.medium { background:#fffbeb; color:#d97706; }
.gap-effort.low    { background:#f0fdf4; color:#166534; }
.gap-category-badge { display:inline-flex; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:4px; background:#f5f3ff; color:var(--t700,#6d28d9); white-space:nowrap; }

/* ── Gap count cards ─────────────────────────────────────────────── */
.gap-count-cards { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.gap-count-card { border-radius:10px; padding:10px 16px; min-width:80px; text-align:center; }
.gap-count-card.high   { background:#fef2f2; border:1.5px solid #fca5a5; }
.gap-count-card.medium { background:#fffbeb; border:1.5px solid #fde68a; }
.gap-count-card.low    { background:#f8fafc; border:1.5px solid #e2e8f0; }
.gap-count-num   { font-size:22px; font-weight:800; }
.gap-count-label { font-size:10.5px; font-weight:700; text-transform:uppercase; }
.gap-count-card.high   .gap-count-num { color:#dc2626; }
.gap-count-card.medium .gap-count-num { color:#d97706; }
.gap-count-card.low    .gap-count-num { color:#64748b; }

/* ── Gap table ───────────────────────────────────────────────────── */
.gap-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.gap-filter-btn { padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#fff; color:#475569; transition:all .12s; }
.gap-filter-btn.active { border-color:var(--t400,#a78bfa); background:var(--t50,#f5f3ff); color:var(--t700,#6d28d9); }

.gap-table { width:100%; border-collapse:collapse; font-size:13px; }
.gap-table th { background:#f8f5ff; color:#6b5fa0; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:8px 12px; text-align:left; border-bottom:1.5px solid #ede8ff; }
.gap-table td { padding:10px 12px; border-bottom:1px solid #f3eeff; vertical-align:top; }
.gap-table tr:last-child td { border-bottom:none; }
.gap-table tr:hover td { background:#fdfcff; }
.gap-id-badge { font-family:monospace; font-size:11.5px; font-weight:700; color:var(--t700,#6d28d9); }
.gap-title-main { font-weight:600; color:#1e1b2e; }
.gap-req-tags { display:flex; flex-wrap:wrap; gap:4px; margin-top:4px; }
.gap-req-tag  { font-size:10.5px; color:#0369a1; background:#f0f9ff; border:1px solid #bae6fd; border-radius:4px; padding:1px 6px; }

/* ── Inline edit ─────────────────────────────────────────────────── */
.gap-edit-row td { background:#faf5ff !important; padding:14px 12px !important; }
.gap-edit-input  { width:100%; border:1.5px solid #c4b5fd; border-radius:8px; padding:6px 10px; font-size:12.5px; font-family:inherit; color:#1e1b2e; background:#fff; }
.gap-edit-input:focus { outline:none; border-color:var(--t600,#7c3aed); }
.gap-edit-select { border:1.5px solid #c4b5fd; border-radius:8px; padding:5px 8px; font-size:12.5px; background:#fff; color:#1e1b2e; cursor:pointer; }

/* ── Detail panel ────────────────────────────────────────────────── */
.gap-detail-panel { background:#faf5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:14px 16px; margin-top:8px; }
.gap-detail-label { font-size:11px; font-weight:700; color:#6b5fa0; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.gap-detail-text  { font-size:12.5px; color:#374151; line-height:1.7; }

/* ── Risk matrix ─────────────────────────────────────────────────── */
.gap-risk-matrix { display:grid; grid-template-columns:60px 1fr 1fr 1fr; gap:2px; margin-bottom:14px; }
.gap-matrix-cell { border-radius:6px; padding:6px 8px; text-align:center; font-size:11px; font-weight:600; min-height:50px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:2px; }
.gap-matrix-header { background:#f8f5ff; color:#6b5fa0; }
.gap-matrix-body.hh { background:#fee2e2; }
.gap-matrix-body.hm { background:#fed7aa; }
.gap-matrix-body.hl { background:#fef9c3; }
.gap-matrix-body.mh { background:#fed7aa; }
.gap-matrix-body.mm { background:#fef9c3; }
.gap-matrix-body.ml { background:#f0fdf4; }
.gap-matrix-body.lh { background:#fef9c3; }
.gap-matrix-body.lm { background:#f0fdf4; }
.gap-matrix-body.ll { background:#f0fdf4; }
.gap-risk-dot { width:20px; height:20px; border-radius:50%; background:#6b5fa0; color:#fff; font-size:9px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; }

/* ── Opps / Recs ─────────────────────────────────────────────────── */
.gap-opp-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:12px; }
.gap-opp-card { background:#faf5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:14px 16px; }
.gap-opp-title { font-size:13px; font-weight:700; color:#1e1b2e; margin-bottom:6px; }
.gap-opp-desc  { font-size:12.5px; color:#475569; line-height:1.6; margin-bottom:8px; }
.gap-opp-benefit { font-size:11.5px; color:#166534; background:#f0fdf4; border-radius:5px; padding:3px 8px; }

.gap-rec-list { display:flex; flex-direction:column; gap:8px; }
.gap-rec-item { display:flex; align-items:flex-start; gap:8px; padding:10px 14px; background:#faf5ff; border-radius:8px; border:1px solid #ede8ff; }
.gap-rec-num  { width:22px; height:22px; border-radius:50%; background:var(--t600,#7c3aed); color:#fff; font-size:11px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; flex-shrink:0; }
.gap-rec-text { font-size:13px; color:#374151; line-height:1.6; }

/* ── Add form ────────────────────────────────────────────────────── */
.gap-add-section { background:#f8f5ff; border:1.5px dashed #c4b5fd; border-radius:12px; padding:16px 18px; margin-top:14px; }
.gap-add-grid { display:grid; grid-template-columns:2fr 1fr 1fr 1fr; gap:10px; }

/* ── Misc ────────────────────────────────────────────────────────── */
.gap-empty { text-align:center; padding:40px 20px; color:#94a3b8; }
.gap-empty-icon { font-size:40px; margin-bottom:10px; }
.gap-empty-text { font-size:14px; font-weight:600; color:#64748b; margin-bottom:4px; }
.gap-empty-hint { font-size:12.5px; color:#94a3b8; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="gapPage()" x-init="init()">

{{-- ── Header ───────────────────────────────────────────────────────────── --}}
<div class="gap-header">
    <div class="gap-header-left">
        <h1>Gap 분석</h1>
        <p>AS-IS 현황과 TO-BE 목표를 비교하여 개선 과제(Gap)를 도출합니다.</p>
    </div>
    <div class="gap-header-right">
        @if($artifact->version > 1)
        <x-ai-agent.version-history
            :artifact-id="$artifact->id"
            artifact-title="Gap 분석"
            :current-version="$artifact->version"
            :history-url="$historyUrl"
            :version-url-tpl="$versionUrlTpl"
            :restore-url-tpl="$restoreUrlTpl"
            allow-restore
        />
        @endif
        <x-ai-agent.traceability-viewer
            source-type="artifact"
            :source-id="$artifact->id"
            source-ref="GAP-ANALYSIS#{{ $artifact->id }}"
            :links-url="$traceLinksUrl"
            :impact-url="$traceImpactUrl"
        />
        <a href="{{ $exportUrl }}" x-show="hasGaps" x-cloak class="gap-btn secondary" title="Markdown 내보내기">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            내보내기
        </a>
    </div>
</div>

{{-- ── Status stat bar ───────────────────────────────────────────────────── --}}
<div class="gap-status-bar">
    <div class="gap-stat">
        <div class="gap-stat-num" x-text="gaps.length"></div>
        <div class="gap-stat-label">전체 Gap</div>
    </div>
    <div class="gap-stat">
        <div class="gap-stat-num" style="color:#dc2626" x-text="severityCounts.high"></div>
        <div class="gap-stat-label">HIGH</div>
    </div>
    <div class="gap-stat">
        <div class="gap-stat-num" style="color:#d97706" x-text="severityCounts.medium"></div>
        <div class="gap-stat-label">MEDIUM</div>
    </div>
    <div class="gap-stat">
        <div class="gap-stat-num" style="color:#64748b" x-text="severityCounts.low"></div>
        <div class="gap-stat-label">LOW</div>
    </div>
    @if($prereqs['as_is_ready'])
    <div class="gap-stat" style="border-color:#bbf7d0;">
        <div class="gap-stat-num" style="color:#166534;font-size:14px;">{{ $prereqs['issue_count'] }}</div>
        <div class="gap-stat-label">AS-IS 이슈</div>
    </div>
    @endif
    @if($prereqs['to_be_ready'])
    <div class="gap-stat" style="border-color:#bae6fd;">
        <div class="gap-stat-num" style="color:#0369a1;font-size:14px;">{{ $prereqs['requirements_count'] }}</div>
        <div class="gap-stat-label">TO-BE 요구사항</div>
    </div>
    @endif
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;">
    {{ session('success') }}
</div>
@endif

{{-- ── Reanalysis warning modal ──────────────────────────────────────────── --}}
<template x-if="showReanalysisWarning">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:460px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
            <div style="font-size:16px;font-weight:800;color:#1e1b2e;margin-bottom:10px;">⚠️ 기존 Gap 삭제 경고</div>
            <p style="font-size:13.5px;color:#475569;line-height:1.7;margin-bottom:20px;">
                이미 <strong x-text="gaps.length"></strong>개의 Gap이 존재합니다.<br>
                재분석하면 기존 Gap이 <strong style="color:#dc2626;">모두 삭제</strong>되고 새로 생성됩니다.<br>
                계속하시겠습니까?
            </p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button @click="showReanalysisWarning=false" class="gap-btn secondary">취소</button>
                <button @click="confirmReanalyze()" class="gap-btn danger">삭제하고 재분석</button>
            </div>
        </div>
    </div>
</template>

{{-- ── Prerequisites + Analysis ──────────────────────────────────────────── --}}
<div class="gap-section">
    <div class="gap-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        사전 조건 확인 및 웍스 분석
        @if($artifact->meta['analyzed_at'] ?? false)
        <span style="font-size:11px;font-weight:500;color:#94a3b8;margin-left:4px;">마지막 분석: {{ \Carbon\Carbon::parse($artifact->meta['analyzed_at'])->diffForHumans() }}</span>
        @endif
    </div>

    <div style="display:flex;flex-direction:column;gap:10px;margin-bottom:16px;">
        {{-- AS-IS --}}
        <div class="gap-prereq {{ $prereqs['as_is_ready'] ? 'ready' : 'missing' }}">
            <span class="gap-prereq-icon">{{ $prereqs['as_is_ready'] ? '✅' : '❌' }}</span>
            <div>
                <div class="gap-prereq-label">AS-IS 분석</div>
                @if($prereqs['as_is_ready'])
                    <div class="gap-prereq-hint">이슈 {{ $prereqs['issue_count'] }}건 분석 완료</div>
                @else
                    <div class="gap-prereq-hint">AS-IS 분석이 완료되지 않았습니다.
                        <a href="{{ $asIsUrl }}" style="color:var(--t600,#7c3aed);font-weight:600;">→ AS-IS 분석으로 이동</a>
                    </div>
                @endif
            </div>
        </div>
        {{-- TO-BE --}}
        <div class="gap-prereq {{ $prereqs['to_be_ready'] ? 'ready' : 'missing' }}">
            <span class="gap-prereq-icon">{{ $prereqs['to_be_ready'] ? '✅' : '❌' }}</span>
            <div>
                <div class="gap-prereq-label">TO-BE 요구사항 분석</div>
                @if($prereqs['to_be_ready'])
                    <div class="gap-prereq-hint">요구사항 {{ $prereqs['requirements_count'] }}건 등록됨</div>
                @else
                    <div class="gap-prereq-hint">TO-BE 요구사항 분석이 완료되지 않았습니다.
                        <a href="{{ $toBeUrl }}" style="color:var(--t600,#7c3aed);font-weight:600;">→ TO-BE 분석으로 이동</a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($prereqs['as_is_ready'] && $prereqs['to_be_ready'])
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <button @click="triggerAnalyze()" class="gap-btn primary" :disabled="analyzing">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span x-text="analyzing ? '분석 중...' : (hasGaps ? '재분석' : 'Gap 분석 시작')"></span>
        </button>
        <span style="font-size:12px;color:#94a3b8;">재분석 시 기존 Gap은 삭제 후 재생성됩니다.</span>
    </div>

    <x-ai-agent.ai-progress
        mode="streaming"
        :start-url="$startUrl"
        :sse-url-tpl="$sseUrlTpl"
        :cancel-url-tpl="$cancelUrlTpl"
        :show-output="false"
        label="Gap 분석 실행"
        on-complete="handleGapAnalysisComplete"
        on-error="handleGapAnalysisError"
        x-ref="aiProgress"
    />
    @endif
</div>

{{-- ── Results ─────────────────────────────────────────────────────────────── --}}
<div x-show="hasGaps" x-cloak>

    {{-- Severity counts --}}
    <div class="gap-count-cards">
        <div class="gap-count-card high">
            <div class="gap-count-num" x-text="severityCounts.high"></div>
            <div class="gap-count-label" style="color:#dc2626;">HIGH</div>
        </div>
        <div class="gap-count-card medium">
            <div class="gap-count-num" x-text="severityCounts.medium"></div>
            <div class="gap-count-label" style="color:#d97706;">MEDIUM</div>
        </div>
        <div class="gap-count-card low">
            <div class="gap-count-num" x-text="severityCounts.low"></div>
            <div class="gap-count-label" style="color:#64748b;">LOW</div>
        </div>
    </div>

    {{-- Executive Summary --}}
    <div class="gap-result-section">
        <div class="gap-result-header">
            <div class="gap-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                종합 요약
            </div>
            <button x-show="!editSummaryMode" @click="startEditSummary()" class="gap-btn secondary sm">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                편집
            </button>
            <template x-if="editSummaryMode">
                <div style="display:flex;gap:6px;">
                    <button @click="saveSummary()" :disabled="savingSummary" class="gap-btn primary sm">
                        <span x-text="savingSummary ? '저장 중...' : '저장'"></span>
                    </button>
                    <button @click="cancelEditSummary()" class="gap-btn secondary sm">취소</button>
                </div>
            </template>
        </div>
        <div class="gap-result-body">
            <p x-show="!editSummaryMode" class="gap-summary-text" x-text="executiveSummary ?? ''"></p>
            <textarea x-show="editSummaryMode" x-cloak class="gap-summary-textarea"
                      x-model="editedSummary" placeholder="종합 요약을 입력하세요..."></textarea>
        </div>
    </div>

    {{-- Gap table --}}
    <div class="gap-result-section">
        <div class="gap-result-header">
            <div class="gap-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#dc2626"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Gap 목록
                <span style="font-size:12px;font-weight:500;color:#94a3b8;margin-left:6px;"
                      x-text="'(' + filteredGaps.length + '/' + gaps.length + '건)'"></span>
            </div>
            <button @click="showAddForm = !showAddForm" class="gap-btn secondary sm">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span x-text="showAddForm ? '닫기' : '수동 추가'"></span>
            </button>
        </div>
        <div class="gap-result-body">
            {{-- Filters --}}
            <div class="gap-filters">
                <button class="gap-filter-btn" :class="filterSeverity==='all'?'active':''" @click="filterSeverity='all'">전체</button>
                <button class="gap-filter-btn" :class="filterSeverity==='high'?'active':''" @click="filterSeverity='high'"
                        style="border-color:#fca5a5;color:#dc2626;"
                        :style="filterSeverity==='high' ? 'background:#fef2f2;border-color:#dc2626;' : ''">HIGH</button>
                <button class="gap-filter-btn" :class="filterSeverity==='medium'?'active':''" @click="filterSeverity='medium'"
                        style="border-color:#fde68a;color:#d97706;"
                        :style="filterSeverity==='medium' ? 'background:#fffbeb;border-color:#d97706;' : ''">MEDIUM</button>
                <button class="gap-filter-btn" :class="filterSeverity==='low'?'active':''" @click="filterSeverity='low'">LOW</button>
                <span style="font-size:11px;color:#94a3b8;margin-left:4px;align-self:center;">|</span>
                <template x-for="cat in uniqueCategories" :key="cat">
                    <button class="gap-filter-btn" :class="filterCategory===cat?'active':''"
                            @click="filterCategory = filterCategory===cat ? 'all' : cat"
                            x-text="cat"></button>
                </template>
            </div>

            <div style="overflow-x:auto;">
                <table class="gap-table">
                    <thead>
                        <tr>
                            <th style="width:90px;">ID</th>
                            <th>제목 / 관련 요구사항</th>
                            <th style="width:80px;">카테고리</th>
                            <th style="width:70px;">심각도</th>
                            <th style="width:65px;">노력</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(gap, idx) in filteredGaps" :key="gap.id">
                            <template x-if="editingId !== gap.id">
                                <tr @click="toggleDetail(gap.id)" style="cursor:pointer;">
                                    <td><span class="gap-id-badge" x-text="gap.gap_id"></span></td>
                                    <td>
                                        <div class="gap-title-main" x-text="gap.title"></div>
                                        <div class="gap-req-tags" x-show="gap.related_requirement_ids?.length > 0">
                                            <template x-for="rid in (gap.related_requirement_ids ?? [])" :key="rid">
                                                <span class="gap-req-tag" x-text="rid"></span>
                                            </template>
                                        </div>
                                        {{-- Detail panel --}}
                                        <div class="gap-detail-panel" x-show="detailId === gap.id" x-cloak @click.stop>
                                            <div x-show="gap.current_state" style="margin-bottom:10px;">
                                                <div class="gap-detail-label">현재 상태 (AS-IS)</div>
                                                <div class="gap-detail-text" x-text="gap.current_state"></div>
                                            </div>
                                            <div x-show="gap.target_state" style="margin-bottom:10px;">
                                                <div class="gap-detail-label">목표 상태 (TO-BE)</div>
                                                <div class="gap-detail-text" x-text="gap.target_state"></div>
                                            </div>
                                            <div x-show="gap.recommended_actions?.length > 0">
                                                <div class="gap-detail-label">권장 조치</div>
                                                <template x-for="(action, ai) in (gap.recommended_actions ?? [])" :key="ai">
                                                    <div class="gap-detail-text" style="display:flex;gap:6px;margin-bottom:3px;">
                                                        <span style="color:var(--t600,#7c3aed);font-weight:700;" x-text="(ai+1) + '.'"></span>
                                                        <span x-text="action"></span>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="gap-category-badge" x-text="gap.category"></span></td>
                                    <td><span class="gap-severity" :class="gap.severity" x-text="gap.severity?.toUpperCase()"></span></td>
                                    <td>
                                        <span x-show="gap.estimated_effort" class="gap-severity gap-effort" :class="gap.estimated_effort" x-text="gap.estimated_effort?.toUpperCase()"></span>
                                        <span x-show="!gap.estimated_effort" style="color:#94a3b8;font-size:11px;">—</span>
                                    </td>
                                    <td @click.stop>
                                        <div style="display:flex;gap:4px;">
                                            <button @click="startEdit(gap)" class="gap-btn secondary sm" title="편집">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button @click="deleteGap(gap)" class="gap-btn danger sm" title="삭제">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="editingId === gap.id">
                                <tr class="gap-edit-row">
                                    <td><span class="gap-id-badge" x-text="gap.gap_id"></span></td>
                                    <td>
                                        <input class="gap-edit-input" x-model="editForm.title" placeholder="Gap 제목" style="margin-bottom:6px;">
                                        <textarea class="gap-edit-input" x-model="editForm.current_state" placeholder="현재 상태 (AS-IS)" rows="2" style="resize:vertical;margin-bottom:4px;"></textarea>
                                        <textarea class="gap-edit-input" x-model="editForm.target_state" placeholder="목표 상태 (TO-BE)" rows="2" style="resize:vertical;"></textarea>
                                    </td>
                                    <td>
                                        <select class="gap-edit-select" x-model="editForm.category">
                                            <option value="보안">보안</option>
                                            <option value="기능">기능</option>
                                            <option value="UX">UX</option>
                                            <option value="성능">성능</option>
                                            <option value="데이터">데이터</option>
                                            <option value="인프라">인프라</option>
                                            <option value="기타">기타</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="gap-edit-select" x-model="editForm.severity">
                                            <option value="high">HIGH</option>
                                            <option value="medium">MEDIUM</option>
                                            <option value="low">LOW</option>
                                        </select>
                                    </td>
                                    <td>
                                        <select class="gap-edit-select" x-model="editForm.estimated_effort">
                                            <option value="">—</option>
                                            <option value="high">HIGH</option>
                                            <option value="medium">MED</option>
                                            <option value="low">LOW</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <button @click="saveEdit(gap)" :disabled="savingEdit" class="gap-btn primary sm">
                                                <span x-text="savingEdit ? '저장 중' : '저장'"></span>
                                            </button>
                                            <button @click="cancelEdit()" class="gap-btn secondary sm">취소</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>
                        <tr x-show="filteredGaps.length === 0">
                            <td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">
                                해당 필터에 맞는 Gap이 없습니다.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Add form --}}
            <div class="gap-add-section" x-show="showAddForm" x-cloak>
                <div style="font-size:12px;font-weight:700;color:var(--t700,#6d28d9);margin-bottom:12px;">+ Gap 수동 추가</div>
                <div class="gap-add-grid">
                    <div>
                        <input class="gap-edit-input" x-model="addForm.title" placeholder="Gap 제목 *" style="width:100%;">
                    </div>
                    <div>
                        <select class="gap-edit-select" x-model="addForm.category" style="width:100%;">
                            <option value="보안">보안</option>
                            <option value="기능">기능</option>
                            <option value="UX">UX</option>
                            <option value="성능">성능</option>
                            <option value="데이터">데이터</option>
                            <option value="인프라">인프라</option>
                            <option value="기타">기타</option>
                        </select>
                    </div>
                    <div>
                        <select class="gap-edit-select" x-model="addForm.severity" style="width:100%;">
                            <option value="high">HIGH</option>
                            <option value="medium">MEDIUM</option>
                            <option value="low">LOW</option>
                        </select>
                    </div>
                    <div>
                        <select class="gap-edit-select" x-model="addForm.estimated_effort" style="width:100%;">
                            <option value="">노력 —</option>
                            <option value="high">HIGH</option>
                            <option value="medium">MEDIUM</option>
                            <option value="low">LOW</option>
                        </select>
                    </div>
                    <div style="grid-column:1/-1;">
                        <textarea class="gap-edit-input" x-model="addForm.current_state" placeholder="현재 상태 (AS-IS)" rows="2" style="resize:vertical;width:100%;margin-bottom:6px;"></textarea>
                        <textarea class="gap-edit-input" x-model="addForm.target_state" placeholder="목표 상태 (TO-BE)" rows="2" style="resize:vertical;width:100%;"></textarea>
                    </div>
                </div>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button @click="storeGap()" :disabled="addingGap" class="gap-btn primary sm">
                        <span x-text="addingGap ? '추가 중...' : '추가'"></span>
                    </button>
                    <button @click="showAddForm=false;resetAddForm()" class="gap-btn secondary sm">취소</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Improvement Opportunities --}}
    <div class="gap-result-section" x-show="opportunities.length > 0">
        <div class="gap-result-header">
            <div class="gap-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#0369a1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                개선 기회 (<span x-text="opportunities.length"></span>)
            </div>
        </div>
        <div class="gap-result-body">
            <div class="gap-opp-grid">
                <template x-for="(opp, oi) in opportunities" :key="oi">
                    <div class="gap-opp-card">
                        <div class="gap-opp-title" x-text="opp.title"></div>
                        <div class="gap-opp-desc" x-text="opp.description"></div>
                        <span class="gap-opp-benefit" x-text="'💡 ' + opp.expected_benefit"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Risk Matrix --}}
    <div class="gap-result-section" x-show="risks.length > 0">
        <div class="gap-result-header">
            <div class="gap-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#dc2626"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                리스크 매트릭스 (<span x-text="risks.length"></span>)
            </div>
        </div>
        <div class="gap-result-body">
            {{-- 2D matrix --}}
            <div class="gap-risk-matrix" style="margin-bottom:20px;">
                <div class="gap-matrix-cell gap-matrix-header" style="font-size:10px;color:#6b5fa0;">영향도 ↑<br>발생가능성→</div>
                <div class="gap-matrix-cell gap-matrix-header">낮음</div>
                <div class="gap-matrix-cell gap-matrix-header">중간</div>
                <div class="gap-matrix-cell gap-matrix-header">높음</div>
                <div class="gap-matrix-cell gap-matrix-header">높음</div>
                <div class="gap-matrix-cell gap-matrix-body hl" x-html="riskDots('low','high')"></div>
                <div class="gap-matrix-cell gap-matrix-body mh" x-html="riskDots('medium','high')"></div>
                <div class="gap-matrix-cell gap-matrix-body hh" x-html="riskDots('high','high')"></div>
                <div class="gap-matrix-cell gap-matrix-header">중간</div>
                <div class="gap-matrix-cell gap-matrix-body lm" x-html="riskDots('low','medium')"></div>
                <div class="gap-matrix-cell gap-matrix-body mm" x-html="riskDots('medium','medium')"></div>
                <div class="gap-matrix-cell gap-matrix-body hm" x-html="riskDots('high','medium')"></div>
                <div class="gap-matrix-cell gap-matrix-header">낮음</div>
                <div class="gap-matrix-cell gap-matrix-body ll" x-html="riskDots('low','low')"></div>
                <div class="gap-matrix-cell gap-matrix-body ml" x-html="riskDots('medium','low')"></div>
                <div class="gap-matrix-cell gap-matrix-body lh" x-html="riskDots('high','low')"></div>
            </div>

            {{-- Risk list --}}
            <div style="display:flex;flex-direction:column;gap:10px;">
                <template x-for="(risk, ri) in risks" :key="ri">
                    <div style="border:1.5px solid #ede8ff;border-radius:10px;padding:14px 16px;background:#fdfcff;">
                        <div style="display:flex;align-items:flex-start;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                            <span class="gap-risk-dot" x-text="ri+1"></span>
                            <span style="font-size:13px;font-weight:700;color:#1e1b2e;flex:1;" x-text="risk.title"></span>
                            <span class="gap-severity" :class="risk.likelihood" x-text="'가능성: ' + risk.likelihood?.toUpperCase()"></span>
                            <span class="gap-severity" :class="risk.impact" x-text="'영향도: ' + risk.impact?.toUpperCase()"></span>
                        </div>
                        <p style="font-size:12.5px;color:#475569;line-height:1.6;margin-bottom:6px;" x-text="risk.description"></p>
                        <div x-show="risk.mitigation" style="font-size:12px;color:#166534;background:#f0fdf4;border-radius:5px;padding:4px 10px;">
                            🛡️ <span x-text="risk.mitigation"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Recommendations --}}
    <div class="gap-result-section" x-show="recommendations && (recommendations.priority_actions?.length > 0 || recommendations.phasing_strategy)">
        <div class="gap-result-header">
            <div class="gap-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#166534"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                권장사항
            </div>
        </div>
        <div class="gap-result-body">
            <div x-show="recommendations?.priority_actions?.length > 0" style="margin-bottom:16px;">
                <div style="font-size:12px;font-weight:700;color:#166534;margin-bottom:10px;">우선 실행 항목</div>
                <div class="gap-rec-list">
                    <template x-for="(action, ai) in (recommendations?.priority_actions ?? [])" :key="ai">
                        <div class="gap-rec-item">
                            <span class="gap-rec-num" x-text="ai+1"></span>
                            <span class="gap-rec-text" x-text="action"></span>
                        </div>
                    </template>
                </div>
            </div>
            <div x-show="recommendations?.phasing_strategy">
                <div style="font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;">단계별 전략</div>
                <p style="font-size:13px;color:#374151;line-height:1.8;white-space:pre-line;" x-text="recommendations?.phasing_strategy"></p>
            </div>
        </div>
    </div>

</div>{{-- /hasGaps --}}

{{-- ── Empty state ──────────────────────────────────────────────────────────── --}}
@if(!$prereqs['as_is_ready'] || !$prereqs['to_be_ready'])
{{-- prereqs missing — already shown above --}}
@elseif($gaps->count() === 0)
<div class="gap-section" x-show="!hasGaps" x-cloak>
    <div class="gap-empty">
        <div class="gap-empty-icon">🔍</div>
        <div class="gap-empty-text">아직 Gap 분석을 실행하지 않았습니다</div>
        <div class="gap-empty-hint">위의 [Gap 분석 시작] 버튼을 눌러 AS-IS와 TO-BE 데이터를 기반으로 분석을 시작하세요.</div>
    </div>
</div>
@endif

</div>{{-- /x-data --}}

@push('scripts')
<script id="gap-page-data" type="application/json">{!! json_encode([
    'gaps' => $gaps->map(fn($g) => [
        'id'                     => $g->id,
        'gap_id'                 => $g->gap_id,
        'title'                  => $g->title,
        'current_state'          => $g->current_state,
        'target_state'           => $g->target_state,
        'category'               => $g->category,
        'severity'               => $g->severity,
        'estimated_effort'       => $g->estimated_effort,
        'recommended_actions'    => $g->recommended_actions ?? [],
        'related_requirement_ids'=> $g->related_requirement_ids ?? [],
    ])->values()->all(),
    'executiveSummary'        => $content['executive_summary'] ?? null,
    'opportunities'           => $content['improvement_opportunities'] ?? [],
    'risks'                   => $content['risks'] ?? [],
    'recommendations'         => $content['recommendations'] ?? null,
    'saveUrl'                 => $saveUrl,
    'gapStoreUrl'             => $gapStoreUrl,
    'gapUpdateUrlTpl'         => $gapUpdateUrlTpl,
    'gapDestroyUrlTpl'        => $gapDestroyUrlTpl,
]) !!}</script>

<script>
(async function () {
    const _d = JSON.parse(document.getElementById('gap-page-data').textContent);
    const _csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const _json = (url, method, body) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': _csrf() },
        body: JSON.stringify(body),
    }).then(r => r.json());

    async function gapPage() {
        return {
            gaps:                  _d.gaps,
            executiveSummary:      _d.executiveSummary,
            opportunities:         _d.opportunities,
            risks:                 _d.risks,
            recommendations:       _d.recommendations,
            analyzing:             false,
            showReanalysisWarning: false,
            editSummaryMode:       false,
            editedSummary:         '',
            savingSummary:         false,
            editingId:             null,
            editForm:              {},
            savingEdit:            false,
            showAddForm:           false,
            addForm:               { title:'', current_state:'', target_state:'', category:'기타', severity:'medium', estimated_effort:'' },
            addingGap:             false,
            filterSeverity:        'all',
            filterCategory:        'all',
            detailId:              null,

            get hasGaps() { return this.gaps.length > 0; },

            get filteredGaps() {
                return this.gaps.filter(g =>
                    (this.filterSeverity === 'all' || g.severity === this.filterSeverity) &&
                    (this.filterCategory === 'all' || g.category === this.filterCategory)
                );
            },

            get uniqueCategories() {
                return [...new Set(this.gaps.map(g => g.category).filter(Boolean))];
            },

            get severityCounts() {
                const c = { high:0, medium:0, low:0 };
                this.gaps.forEach(g => { if (c[g.severity] !== undefined) c[g.severity]++; });
                return c;
            },

            init() { window._gapPage = this; },

            riskDots(likelihood, impact) {
                const matches = this.risks
                    .map((r, i) => ({ r, i }))
                    .filter(({ r }) => r.likelihood === likelihood && r.impact === impact);
                if (!matches.length) return '';
                return matches.map(({ i }) =>
                    '<span class="gap-risk-dot">' + (i + 1) + '</span>'
                ).join(' ');
            },

            toggleDetail(id) {
                this.detailId = this.detailId === id ? null : id;
            },

            triggerAnalyze() {
                if (this.gaps.length > 0) {
                    this.showReanalysisWarning = true;
                } else {
                    this._doAnalyze();
                }
            },

            confirmReanalyze() {
                this.showReanalysisWarning = false;
                this._doAnalyze();
            },

            _doAnalyze() {
                this.analyzing = true;
                this.$refs.aiProgress.start('', {});
            },

            startEditSummary() {
                this.editedSummary   = this.executiveSummary ?? '';
                this.editSummaryMode = true;
            },

            cancelEditSummary() { this.editSummaryMode = false; },

            async saveSummary() {
                this.savingSummary = true;
                try {
                    const data = await _json(_d.saveUrl, 'POST', { executive_summary: this.editedSummary });
                    if (data.success) {
                        this.executiveSummary = this.editedSummary;
                        this.editSummaryMode  = false;
                    }
                } catch {}
                this.savingSummary = false;
            },

            startEdit(gap) {
                this.editingId = gap.id;
                this.detailId  = null;
                this.editForm  = {
                    title:             gap.title,
                    current_state:     gap.current_state ?? '',
                    target_state:      gap.target_state ?? '',
                    category:          gap.category,
                    severity:          gap.severity,
                    estimated_effort:  gap.estimated_effort ?? '',
                };
            },

            cancelEdit() { this.editingId = null; },

            async saveEdit(gap) {
                this.savingEdit = true;
                try {
                    const url  = _d.gapUpdateUrlTpl.replace('GAPID', gap.id);
                    const data = await _json(url, 'PATCH', this.editForm);
                    if (data.success) {
                        const idx = this.gaps.findIndex(g => g.id === gap.id);
                        if (idx !== -1) Object.assign(this.gaps[idx], this.editForm);
                        this.editingId = null;
                    }
                } catch {}
                this.savingEdit = false;
            },

            async deleteGap(gap) {
                if (!await __confirm('[' + gap.gap_id + '] ' + gap.title + '\n\n이 Gap을 삭제하시겠습니까?')) return;
                try {
                    const url  = _d.gapDestroyUrlTpl.replace('GAPID', gap.id);
                    const data = await _json(url, 'DELETE', {});
                    if (data.success) this.gaps = this.gaps.filter(g => g.id !== gap.id);
                } catch {}
            },

            async storeGap() {
                if (!this.addForm.title.trim()) { alert('제목을 입력해주세요.'); return; }
                this.addingGap = true;
                try {
                    const data = await _json(_d.gapStoreUrl, 'POST', this.addForm);
                    if (data.success) {
                        const g = data.gap;
                        this.gaps.push({
                            id:                      g.id,
                            gap_id:                  g.gap_id,
                            title:                   g.title,
                            current_state:           g.current_state,
                            target_state:            g.target_state,
                            category:                g.category,
                            severity:                g.severity,
                            estimated_effort:        g.estimated_effort,
                            recommended_actions:     g.recommended_actions ?? [],
                            related_requirement_ids: g.related_requirement_ids ?? [],
                        });
                        this.resetAddForm();
                        this.showAddForm = false;
                    }
                } catch {}
                this.addingGap = false;
            },

            resetAddForm() {
                this.addForm = { title:'', current_state:'', target_state:'', category:'기타', severity:'medium', estimated_effort:'' };
            },

            onComplete() {
                this.analyzing = false;
                window.location.reload();
            },

            onError() { this.analyzing = false; },
        };
    }

    window.gapPage = gapPage;
    window.handleGapAnalysisComplete = async function () { if (window._gapPage) window._gapPage.onComplete(); };
    window.handleGapAnalysisError    = async function () { if (window._gapPage) window._gapPage.onError(); };
})();
</script>
@endpush
@endsection
