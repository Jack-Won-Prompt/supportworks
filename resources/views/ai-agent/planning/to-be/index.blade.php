@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.tobe-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.tobe-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.tobe-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.tobe-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

.tobe-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.tobe-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Upload ──────────────────────────────────────────────────────── */
.tobe-upload-zone { border:2px dashed #c4b5fd; border-radius:12px; padding:28px 20px; text-align:center; cursor:pointer; transition:all .15s; background:#faf5ff; }
.tobe-upload-zone:hover, .tobe-upload-zone.drag-over { border-color:var(--t600,#7c3aed); background:#f5f3ff; }

/* ── File list ───────────────────────────────────────────────────── */
.tobe-file-list { display:flex; flex-direction:column; gap:8px; margin-top:14px; }
.tobe-file-item { display:flex; align-items:center; gap:10px; padding:10px 14px; background:#faf5ff; border-radius:10px; border:1px solid #ede8ff; }
.tobe-file-icon { width:32px; height:32px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.tobe-file-info { flex:1; min-width:0; }
.tobe-file-name { font-size:13px; font-weight:600; color:#1e1b2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.tobe-file-meta { font-size:11.5px; color:#94a3b8; margin-top:1px; }
.tobe-parse-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.tobe-parse-badge.pending   { background:#f8fafc; color:#64748b; }
.tobe-parse-badge.parsing   { background:#fffbeb; color:#d97706; }
.tobe-parse-badge.completed { background:#f0fdf4; color:#166534; }
.tobe-parse-badge.failed    { background:#fef2f2; color:#dc2626; }
.tobe-file-del { display:inline-flex; align-items:center; padding:5px 8px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0; cursor:pointer; background:#fff; color:#94a3b8; transition:all .12s; flex-shrink:0; }
.tobe-file-del:hover { color:#dc2626; border-color:#fca5a5; }

/* ── Status bar ──────────────────────────────────────────────────── */
.tobe-status-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.tobe-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 16px; }
.tobe-stat-num   { font-size:20px; font-weight:800; color:var(--t600,#7c3aed); }
.tobe-stat-label { font-size:11.5px; color:#64748b; line-height:1.4; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.tobe-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.tobe-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.tobe-btn.primary:hover   { background:var(--t700,#6d28d9); }
.tobe-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.tobe-btn.secondary:hover { background:#e2e8f0; }
.tobe-btn.danger    { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }
.tobe-btn.danger:hover    { background:#fee2e2; }
.tobe-btn:disabled  { opacity:.45; cursor:not-allowed; }
.tobe-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Overview ────────────────────────────────────────────────────── */
.tobe-result-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:20px; }
.tobe-result-header { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid #f3eeff; flex-wrap:wrap; }
.tobe-result-title  { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; }
.tobe-result-body   { padding:20px 22px; }
.tobe-overview-text { font-size:14px; color:#374151; line-height:1.8; white-space:pre-line; }
.tobe-overview-textarea { width:100%; min-height:120px; border:1.5px solid #c4b5fd; border-radius:10px; padding:12px 14px; font-size:14px; line-height:1.8; color:#374151; resize:vertical; font-family:inherit; }

/* ── Priority chips ──────────────────────────────────────────────── */
.tobe-priority { display:inline-flex; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:.05em; white-space:nowrap; }
.tobe-priority.must   { background:#fef2f2; color:#dc2626; }
.tobe-priority.should { background:#fffbeb; color:#d97706; }
.tobe-priority.could  { background:#f0f9ff; color:#0369a1; }
.tobe-priority.wont   { background:#f8fafc; color:#64748b; }

/* ── Status chips ────────────────────────────────────────────────── */
.tobe-status-chip { display:inline-flex; font-size:10px; font-weight:700; padding:2px 8px; border-radius:4px; }
.tobe-status-chip.draft     { background:#f8fafc; color:#64748b; }
.tobe-status-chip.confirmed { background:#f0fdf4; color:#166534; }
.tobe-status-chip.deferred  { background:#fffbeb; color:#d97706; }
.tobe-status-chip.removed   { background:#fef2f2; color:#dc2626; }

/* ── Priority summary ────────────────────────────────────────────── */
.tobe-priority-summary { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:18px; }
.tobe-ps-card { border-radius:10px; padding:10px 16px; min-width:80px; text-align:center; }
.tobe-ps-card.must   { background:#fef2f2; border:1.5px solid #fca5a5; }
.tobe-ps-card.should { background:#fffbeb; border:1.5px solid #fde68a; }
.tobe-ps-card.could  { background:#f0f9ff; border:1.5px solid #bae6fd; }
.tobe-ps-card.wont   { background:#f8fafc; border:1.5px solid #e2e8f0; }
.tobe-ps-num   { font-size:22px; font-weight:800; }
.tobe-ps-label { font-size:10.5px; font-weight:700; text-transform:uppercase; }
.tobe-ps-card.must   .tobe-ps-num { color:#dc2626; }
.tobe-ps-card.should .tobe-ps-num { color:#d97706; }
.tobe-ps-card.could  .tobe-ps-num { color:#0369a1; }
.tobe-ps-card.wont   .tobe-ps-num { color:#94a3b8; }

/* ── Filters ─────────────────────────────────────────────────────── */
.tobe-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.tobe-filter-btn { padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#fff; color:#475569; transition:all .12s; }
.tobe-filter-btn.active { border-color:var(--t400,#a78bfa); background:var(--t50,#f5f3ff); color:var(--t700,#6d28d9); }

/* ── Requirements table ──────────────────────────────────────────── */
.tobe-req-table { width:100%; border-collapse:collapse; font-size:13px; }
.tobe-req-table th { background:#f8f5ff; color:#6b5fa0; font-size:11.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:8px 12px; text-align:left; border-bottom:1.5px solid #ede8ff; }
.tobe-req-table td { padding:10px 12px; border-bottom:1px solid #f3eeff; vertical-align:top; }
.tobe-req-table tr:last-child td { border-bottom:none; }
.tobe-req-table tr:hover td { background:#fdfcff; }
.tobe-req-id   { font-family:monospace; font-size:11.5px; font-weight:700; color:var(--t700,#6d28d9); white-space:nowrap; }
.tobe-req-title { font-weight:600; color:#1e1b2e; }
.tobe-req-desc  { font-size:12px; color:#64748b; margin-top:3px; line-height:1.5; }
.tobe-req-cat   { display:inline-flex; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:4px; background:#f0f9ff; color:#0369a1; white-space:nowrap; }

/* ── Inline edit form ────────────────────────────────────────────── */
.tobe-edit-row td { background:#faf5ff !important; padding:14px 12px !important; }
.tobe-edit-input { width:100%; border:1.5px solid #c4b5fd; border-radius:8px; padding:6px 10px; font-size:12.5px; font-family:inherit; color:#1e1b2e; background:#fff; }
.tobe-edit-input:focus { outline:none; border-color:var(--t600,#7c3aed); }
.tobe-edit-select { border:1.5px solid #c4b5fd; border-radius:8px; padding:5px 8px; font-size:12.5px; background:#fff; color:#1e1b2e; cursor:pointer; }

/* ── Add row form ────────────────────────────────────────────────── */
.tobe-add-section { background:#f8f5ff; border:1.5px dashed #c4b5fd; border-radius:12px; padding:16px 18px; margin-top:14px; }
.tobe-add-title { font-size:12px; font-weight:700; color:var(--t700,#6d28d9); margin-bottom:12px; }
.tobe-add-grid  { display:grid; grid-template-columns:2fr 1fr 1fr; gap:10px; }
.tobe-add-desc-row { grid-column:1/-1; }

/* ── Misc ────────────────────────────────────────────────────────── */
.tobe-polling-bar { display:flex; align-items:center; gap:8px; font-size:12px; color:#d97706; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:8px 12px; margin-bottom:14px; }
.tobe-empty { text-align:center; padding:40px 20px; color:#94a3b8; }
.tobe-empty-icon { font-size:40px; margin-bottom:10px; }
.tobe-empty-text { font-size:14px; font-weight:600; color:#64748b; margin-bottom:4px; }
.tobe-empty-hint { font-size:12.5px; color:#94a3b8; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="toBePage()" x-init="init()">

{{-- ── Header ───────────────────────────────────────────────────────────── --}}
<div class="tobe-header">
    <div class="tobe-header-left">
        <h1>TO-BE 요구사항 분석</h1>
        <p>AS-IS 현황 자료를 바탕으로 TO-BE 요구사항을 웍스가 자동 도출합니다.</p>
    </div>
    <div class="tobe-header-right">
        @if($artifact->version > 1)
        <x-ai-agent.version-history
            :artifact-id="$artifact->id"
            artifact-title="TO-BE 요구사항"
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
            source-ref="TO-BE#{{ $artifact->id }}"
            :links-url="$traceLinksUrl"
            :impact-url="$traceImpactUrl"
        />
        <a href="{{ $exportUrl }}" x-show="hasRequirements" x-cloak
           class="tobe-btn secondary" title="Markdown 내보내기">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            내보내기
        </a>
    </div>
</div>

{{-- ── Parse status stats ────────────────────────────────────────────────── --}}
<div class="tobe-status-bar">
    <div class="tobe-stat">
        <div class="tobe-stat-num">{{ $files->count() }}</div>
        <div class="tobe-stat-label">전체 파일</div>
    </div>
    <div class="tobe-stat">
        <div class="tobe-stat-num" style="color:#166534">{{ $files->where('parse_status','completed')->count() }}</div>
        <div class="tobe-stat-label">파싱 완료</div>
    </div>
    @if($files->whereIn('parse_status',['pending','parsing'])->count() > 0)
    <div class="tobe-stat">
        <div class="tobe-stat-num" style="color:#d97706">{{ $files->whereIn('parse_status',['pending','parsing'])->count() }}</div>
        <div class="tobe-stat-label">처리 중</div>
    </div>
    @endif
    @if($files->where('parse_status','failed')->count() > 0)
    <div class="tobe-stat">
        <div class="tobe-stat-num" style="color:#dc2626">{{ $files->where('parse_status','failed')->count() }}</div>
        <div class="tobe-stat-label">오류</div>
    </div>
    @endif
    <div class="tobe-stat" x-show="hasRequirements" x-cloak>
        <div class="tobe-stat-num" x-text="requirements.length"></div>
        <div class="tobe-stat-label">요구사항</div>
    </div>
    @if($artifact->meta['analyzed_at'] ?? false)
    <div class="tobe-stat" style="border-color:#bbf7d0;">
        <div class="tobe-stat-num" style="color:#166534;font-size:12px;line-height:1.3">분석 완료</div>
        <div class="tobe-stat-label">{{ \Carbon\Carbon::parse($artifact->meta['analyzed_at'])->format('m/d H:i') }}</div>
    </div>
    @endif
</div>

@if(session('success'))
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#166534;">
    {{ session('success') }}
</div>
@endif

{{-- ── Parsing indicator ─────────────────────────────────────────────────── --}}
@if($files->whereIn('parse_status',['pending','parsing'])->count() > 0)
<div class="tobe-polling-bar" id="polling-bar">
    <div style="width:14px;height:14px;border:2px solid #fde68a;border-top-color:#d97706;border-radius:50%;animation:aip-spin .7s linear infinite;flex-shrink:0;"></div>
    <span>파일 파싱 중 — 완료되면 자동으로 새로고침됩니다.</span>
</div>
@endif

{{-- ── Reanalysis warning modal ──────────────────────────────────────────── --}}
<template x-if="showReanalysisWarning">
    <div style="position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9999;display:flex;align-items:center;justify-content:center;">
        <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:460px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
            <div style="font-size:16px;font-weight:800;color:#1e1b2e;margin-bottom:10px;">⚠️ 기존 요구사항 삭제 경고</div>
            <p style="font-size:13.5px;color:#475569;line-height:1.7;margin-bottom:20px;">
                이미 <strong x-text="requirements.length"></strong>개의 요구사항이 존재합니다.<br>
                재분석하면 기존 요구사항이 <strong style="color:#dc2626;">모두 삭제</strong>되고 새로 생성됩니다.<br>
                계속하시겠습니까?
            </p>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button @click="showReanalysisWarning=false" class="tobe-btn secondary">취소</button>
                <button @click="confirmReanalyze()" class="tobe-btn danger">삭제하고 재분석</button>
            </div>
        </div>
    </div>
</template>

{{-- ── File Upload ────────────────────────────────────────────────────────── --}}
<div class="tobe-section">
    <div class="tobe-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        자료 업로드
    </div>

    <form method="POST" action="{{ route('ai-agent.projects.planning.to-be.upload', $project) }}"
          enctype="multipart/form-data" id="tobe-upload-form">
        @csrf
        <div class="tobe-upload-zone" id="tobe-drop-zone" onclick="document.getElementById('tobe-file-input').click()">
            <div style="font-size:32px;margin-bottom:8px;">📁</div>
            <div style="font-size:13.5px;color:#475569;margin-bottom:4px;">파일을 드래그하거나 클릭하여 업로드</div>
            <div style="font-size:11.5px;color:#94a3b8;">AS-IS 분석 결과, 인터뷰 내용, 프로세스 문서 등 — 최대 50MB / 최대 10개</div>
        </div>
        <input type="file" id="tobe-file-input" name="files[]" multiple style="display:none;"
               accept=".xlsx,.xls,.pptx,.ppt,.pdf,.txt,.csv,.json,.md,.jpg,.jpeg,.png,.gif,.webp">

        <div id="tobe-selected-files" style="display:none;margin-top:12px;"></div>
        <button type="submit" id="tobe-upload-btn" style="display:none;margin-top:12px;padding:9px 20px;background:var(--t600,#7c3aed);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
            업로드
        </button>
    </form>
</div>

{{-- ── Uploaded files list ────────────────────────────────────────────────── --}}
@if($files->count() > 0)
<div class="tobe-section">
    <div class="tobe-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
        업로드된 파일 ({{ $files->count() }})
    </div>

    <div class="tobe-file-list">
        @foreach($files as $file)
        @php
            $icon = match($file->file_type) {
                'excel' => '📊', 'pptx' => '📊', 'pdf' => '📄',
                'image' => '🖼', 'text' => '📝', default => '📎',
            };
        @endphp
        <div class="tobe-file-item">
            <div class="tobe-file-icon">{{ $icon }}</div>
            <div class="tobe-file-info">
                <div class="tobe-file-name" title="{{ $file->file_name }}">{{ $file->file_name }}</div>
                <div class="tobe-file-meta">{{ $file->formatted_size }} · {{ $file->mime_type }} · {{ $file->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <span class="tobe-parse-badge {{ $file->parse_status }}">
                @if($file->parse_status === 'completed') ✓ 파싱 완료
                @elseif($file->parse_status === 'parsing') ⟳ 파싱 중
                @elseif($file->parse_status === 'failed') ✕ 오류
                @else ○ 대기 중
                @endif
            </span>
            <form method="POST" action="{{ route('ai-agent.projects.planning.to-be.file.delete', [$project, $file]) }}"
                  onsubmit="return confirm('파일을 삭제하시겠습니까?')">
                @csrf @method('DELETE')
                <button type="submit" class="tobe-file-del" title="삭제">✕</button>
            </form>
        </div>
        @if($file->parse_status === 'failed' && $file->parse_error)
        <div style="font-size:11.5px;color:#dc2626;padding:4px 14px;background:#fef2f2;border-radius:6px;margin-top:-4px;">
            오류: {{ $file->parse_error }}
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

{{-- ── 웍스 Analysis section ─────────────────────────────────────────────────── --}}
@if($files->where('parse_status','completed')->count() > 0)
<div class="tobe-section">
    <div class="tobe-section-title" style="margin-bottom:10px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        웍스 분석
        @if($artifact->meta['analyzed_at'] ?? false)
        <span style="font-size:11px;font-weight:500;color:#94a3b8;margin-left:4px;">마지막 분석: {{ \Carbon\Carbon::parse($artifact->meta['analyzed_at'])->diffForHumans() }}</span>
        @endif
    </div>

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <button @click="triggerAnalyze()" class="tobe-btn primary" :disabled="analyzing">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span x-text="analyzing ? '분석 중...' : (hasRequirements ? '재분석' : '웍스 분석 시작')"></span>
        </button>
        <span x-show="!analyzing && !hasRequirements" style="font-size:12.5px;color:#94a3b8;">
            {{ $files->where('parse_status','completed')->count() }}개 파일 준비됨
        </span>
    </div>

    <x-ai-agent.ai-progress
        mode="streaming"
        :start-url="$startUrl"
        :sse-url-tpl="$sseUrlTpl"
        :cancel-url-tpl="$cancelUrlTpl"
        :show-output="false"
        label="TO-BE 요구사항 분석 실행"
        on-complete="handleToBeAnalysisComplete"
        on-error="handleToBeAnalysisError"
        x-ref="aiProgress"
    />
</div>
@else
<div class="tobe-section">
    <div class="tobe-empty">
        <div class="tobe-empty-icon">📂</div>
        <div class="tobe-empty-text">분석할 파일이 없습니다</div>
        <div class="tobe-empty-hint">AS-IS 분석 결과 등 자료를 업로드하고 파싱이 완료되면 웍스 분석을 시작할 수 있습니다.</div>
    </div>
</div>
@endif

{{-- ── Results ─────────────────────────────────────────────────────────────── --}}
<div x-show="hasRequirements" x-cloak>

    {{-- Priority summary cards --}}
    <div class="tobe-priority-summary">
        <div class="tobe-ps-card must">
            <div class="tobe-ps-num" x-text="priorityCounts.must"></div>
            <div class="tobe-ps-label" style="color:#dc2626;">MUST</div>
        </div>
        <div class="tobe-ps-card should">
            <div class="tobe-ps-num" x-text="priorityCounts.should"></div>
            <div class="tobe-ps-label" style="color:#d97706;">SHOULD</div>
        </div>
        <div class="tobe-ps-card could">
            <div class="tobe-ps-num" x-text="priorityCounts.could"></div>
            <div class="tobe-ps-label" style="color:#0369a1;">COULD</div>
        </div>
        <div class="tobe-ps-card wont">
            <div class="tobe-ps-num" x-text="priorityCounts.wont"></div>
            <div class="tobe-ps-label" style="color:#94a3b8;">WONT</div>
        </div>
    </div>

    {{-- Overview --}}
    <div class="tobe-result-section">
        <div class="tobe-result-header">
            <div class="tobe-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                요구사항 개요
            </div>
            <button x-show="!editOverviewMode" @click="startEditOverview()" class="tobe-btn secondary sm">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                편집
            </button>
            <template x-if="editOverviewMode">
                <div style="display:flex;gap:8px;">
                    <button @click="saveOverview()" :disabled="savingOverview" class="tobe-btn primary sm">
                        <span x-text="savingOverview ? '저장 중...' : '저장'"></span>
                    </button>
                    <button @click="cancelEditOverview()" class="tobe-btn secondary sm">취소</button>
                </div>
            </template>
        </div>
        <div class="tobe-result-body">
            <p x-show="!editOverviewMode" class="tobe-overview-text" x-text="overview ?? ''"></p>
            <textarea x-show="editOverviewMode" x-cloak class="tobe-overview-textarea"
                      x-model="editedOverview" placeholder="요구사항 개요를 입력하세요..."></textarea>
        </div>
    </div>

    {{-- Requirements table --}}
    <div class="tobe-result-section">
        <div class="tobe-result-header">
            <div class="tobe-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                요구사항 목록
                <span style="font-size:12px;font-weight:500;color:#94a3b8;margin-left:6px;"
                      x-text="'(' + filteredRequirements.length + '/' + requirements.length + '건)'"></span>
            </div>
            <button @click="showAddForm = !showAddForm" class="tobe-btn secondary sm">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span x-text="showAddForm ? '닫기' : '수동 추가'"></span>
            </button>
        </div>
        <div class="tobe-result-body">
            {{-- Filters --}}
            <div class="tobe-filters">
                <button class="tobe-filter-btn" :class="filterPriority==='all'?'active':''" @click="filterPriority='all'">전체</button>
                <button class="tobe-filter-btn" :class="filterPriority==='must'?'active':''" @click="filterPriority='must'"
                        style="border-color:#fca5a5;color:#dc2626;"
                        :style="filterPriority==='must' ? 'background:#fef2f2;border-color:#dc2626;' : ''">MUST</button>
                <button class="tobe-filter-btn" :class="filterPriority==='should'?'active':''" @click="filterPriority='should'"
                        style="border-color:#fde68a;color:#d97706;"
                        :style="filterPriority==='should' ? 'background:#fffbeb;border-color:#d97706;' : ''">SHOULD</button>
                <button class="tobe-filter-btn" :class="filterPriority==='could'?'active':''" @click="filterPriority='could'"
                        style="border-color:#bae6fd;color:#0369a1;"
                        :style="filterPriority==='could' ? 'background:#f0f9ff;border-color:#0369a1;' : ''">COULD</button>
                <button class="tobe-filter-btn" :class="filterPriority==='wont'?'active':''" @click="filterPriority='wont'">WONT</button>
                <span style="font-size:11px;color:#94a3b8;margin-left:4px;align-self:center;">|</span>
                <template x-for="cat in uniqueCategories" :key="cat">
                    <button class="tobe-filter-btn" :class="filterCategory===cat?'active':''"
                            @click="filterCategory = filterCategory===cat ? 'all' : cat"
                            x-text="cat"></button>
                </template>
            </div>

            {{-- Table --}}
            <div style="overflow-x:auto;">
                <table class="tobe-req-table">
                    <thead>
                        <tr>
                            <th style="width:90px;">ID</th>
                            <th>요구사항</th>
                            <th style="width:80px;">우선순위</th>
                            <th style="width:110px;">카테고리</th>
                            <th style="width:80px;">상태</th>
                            <th style="width:80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(req, idx) in filteredRequirements" :key="req.id">
                            <template x-if="editingId !== req.id">
                                <tr>
                                    <td><span class="tobe-req-id" x-text="req.req_id"></span></td>
                                    <td>
                                        <div class="tobe-req-title" x-text="req.title"></div>
                                        <div class="tobe-req-desc" x-show="req.description" x-text="req.description"></div>
                                    </td>
                                    <td><span class="tobe-priority" :class="req.priority" x-text="req.priority?.toUpperCase()"></span></td>
                                    <td><span class="tobe-req-cat" x-text="req.category || '—'"></span></td>
                                    <td><span class="tobe-status-chip" :class="req.status" x-text="req.status"></span></td>
                                    <td>
                                        <div style="display:flex;gap:4px;">
                                            <button @click="startEdit(req)" class="tobe-btn secondary sm" title="편집">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            <button @click="deleteReq(req)" class="tobe-btn danger sm" title="삭제">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <template x-if="editingId === req.id">
                                <tr class="tobe-edit-row">
                                    <td><span class="tobe-req-id" x-text="req.req_id"></span></td>
                                    <td>
                                        <input class="tobe-edit-input" x-model="editForm.title" placeholder="요구사항 제목" style="margin-bottom:6px;">
                                        <textarea class="tobe-edit-input" x-model="editForm.description" placeholder="상세 설명" rows="2" style="resize:vertical;"></textarea>
                                        <textarea class="tobe-edit-input" x-model="editForm.rationale" placeholder="도출 근거" rows="2" style="resize:vertical;margin-top:4px;"></textarea>
                                    </td>
                                    <td>
                                        <select class="tobe-edit-select" x-model="editForm.priority">
                                            <option value="must">MUST</option>
                                            <option value="should">SHOULD</option>
                                            <option value="could">COULD</option>
                                            <option value="wont">WONT</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input class="tobe-edit-input" x-model="editForm.category" placeholder="카테고리">
                                    </td>
                                    <td>
                                        <select class="tobe-edit-select" x-model="editForm.status">
                                            <option value="draft">draft</option>
                                            <option value="confirmed">confirmed</option>
                                            <option value="deferred">deferred</option>
                                            <option value="removed">removed</option>
                                        </select>
                                    </td>
                                    <td>
                                        <div style="display:flex;flex-direction:column;gap:4px;">
                                            <button @click="saveEdit(req)" :disabled="savingEdit" class="tobe-btn primary sm">
                                                <span x-text="savingEdit ? '저장 중' : '저장'"></span>
                                            </button>
                                            <button @click="cancelEdit()" class="tobe-btn secondary sm">취소</button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </template>
                        <tr x-show="filteredRequirements.length === 0">
                            <td colspan="6" style="text-align:center;padding:24px;color:#94a3b8;font-size:13px;">
                                해당 필터에 맞는 요구사항이 없습니다.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Add form --}}
            <div class="tobe-add-section" x-show="showAddForm" x-cloak>
                <div class="tobe-add-title">+ 요구사항 수동 추가</div>
                <div class="tobe-add-grid">
                    <div>
                        <input class="tobe-edit-input" x-model="addForm.title" placeholder="요구사항 제목 *" style="width:100%;">
                    </div>
                    <div>
                        <select class="tobe-edit-select" x-model="addForm.priority" style="width:100%;">
                            <option value="must">MUST</option>
                            <option value="should">SHOULD</option>
                            <option value="could">COULD</option>
                            <option value="wont">WONT</option>
                        </select>
                    </div>
                    <div>
                        <input class="tobe-edit-input" x-model="addForm.category" placeholder="카테고리" style="width:100%;">
                    </div>
                    <div class="tobe-add-desc-row">
                        <textarea class="tobe-edit-input" x-model="addForm.description" placeholder="상세 설명" rows="2" style="resize:vertical;width:100%;"></textarea>
                    </div>
                    <div class="tobe-add-desc-row">
                        <textarea class="tobe-edit-input" x-model="addForm.rationale" placeholder="도출 근거" rows="2" style="resize:vertical;width:100%;"></textarea>
                    </div>
                </div>
                <div style="margin-top:10px;display:flex;gap:8px;">
                    <button @click="storeReq()" :disabled="addingReq" class="tobe-btn primary sm">
                        <span x-text="addingReq ? '추가 중...' : '추가'"></span>
                    </button>
                    <button @click="showAddForm=false;resetAddForm()" class="tobe-btn secondary sm">취소</button>
                </div>
            </div>

        </div>
    </div>

</div>{{-- /hasRequirements --}}

{{-- ── Empty state ──────────────────────────────────────────────────────────── --}}
@if($files->count() === 0)
<div class="tobe-section">
    <div class="tobe-empty">
        <div class="tobe-empty-icon">📋</div>
        <div class="tobe-empty-text">아직 업로드된 파일이 없습니다</div>
        <div class="tobe-empty-hint">AS-IS 분석 결과, 업무 프로세스 문서, 인터뷰 내용 등을 업로드해주세요.</div>
    </div>
</div>
@endif

</div>{{-- /x-data --}}

@push('scripts')
<script id="tobe-page-data" type="application/json">{!! json_encode([
    'requirements'     => $requirements->map(fn($r) => [
        'id'          => $r->id,
        'req_id'      => $r->req_id,
        'title'       => $r->title,
        'description' => $r->description,
        'rationale'   => $r->rationale,
        'source_files'=> $r->source_files,
        'priority'    => $r->priority instanceof \App\Enums\Agent\RequirementPriority ? $r->priority->value : $r->priority,
        'category'    => $r->category,
        'status'      => $r->status,
    ])->values()->all(),
    'overview'         => $content['overview'] ?? null,
    'saveUrl'          => $saveUrl,
    'statusUrl'        => $statusUrl,
    'reqStoreUrl'      => $reqStoreUrl,
    'reqUpdateUrlTpl'  => route('ai-agent.projects.planning.to-be.req.update',  [$project, 'REQID']),
    'reqDestroyUrlTpl' => route('ai-agent.projects.planning.to-be.req.destroy', [$project, 'REQID']),
    'hasPending'       => $files->whereIn('parse_status', ['pending', 'parsing'])->count() > 0,
]) !!}</script>

<script>
(async function () {
    const _d = JSON.parse(document.getElementById('tobe-page-data').textContent);
    const _csrf = () => document.querySelector('meta[name="csrf-token"]').content;
    const _json = (url, method, body) => fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': _csrf() },
        body: JSON.stringify(body),
    }).then(r => r.json());

    async function toBePage() {
        return {
            requirements:         _d.requirements,
            overview:             _d.overview,
            analyzing:            false,
            showReanalysisWarning: false,
            editOverviewMode:     false,
            editedOverview:       '',
            savingOverview:       false,
            editingId:            null,
            editForm:             {},
            savingEdit:           false,
            showAddForm:          false,
            addForm:              { title:'', description:'', rationale:'', priority:'should', category:'' },
            addingReq:            false,
            filterPriority:       'all',
            filterCategory:       'all',

            get hasRequirements() { return this.requirements.length > 0; },

            get filteredRequirements() {
                return this.requirements.filter(r =>
                    (this.filterPriority === 'all' || r.priority === this.filterPriority) &&
                    (this.filterCategory === 'all' || r.category === this.filterCategory)
                );
            },

            get uniqueCategories() {
                const cats = this.requirements.map(r => r.category).filter(Boolean);
                return [...new Set(cats)];
            },

            get priorityCounts() {
                const counts = { must:0, should:0, could:0, wont:0 };
                this.requirements.forEach(r => { if (counts[r.priority] !== undefined) counts[r.priority]++; });
                return counts;
            },

            init() {
                window._tobePage = this;
                if (_d.hasPending) this._startParsePolling();
            },

            _startParsePolling() {
                const timer = setInterval(async () => {
                    try {
                        const res  = await fetch(_d.statusUrl, { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        const c    = data.counts ?? {};
                        if ((c.pending ?? 0) === 0 && (c.parsing ?? 0) === 0) {
                            clearInterval(timer);
                            window.location.reload();
                        }
                    } catch {}
                }, 5000);
            },

            triggerAnalyze() {
                if (this.requirements.length > 0) {
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

            startEditOverview() {
                this.editedOverview   = this.overview ?? '';
                this.editOverviewMode = true;
            },

            cancelEditOverview() { this.editOverviewMode = false; },

            async saveOverview() {
                this.savingOverview = true;
                try {
                    const data = await _json(_d.saveUrl, 'POST', { overview: this.editedOverview });
                    if (data.success) {
                        this.overview         = this.editedOverview;
                        this.editOverviewMode = false;
                    }
                } catch {}
                this.savingOverview = false;
            },

            startEdit(req) {
                this.editingId = req.id;
                this.editForm  = {
                    title:       req.title,
                    description: req.description ?? '',
                    rationale:   req.rationale ?? '',
                    priority:    req.priority,
                    category:    req.category ?? '',
                    status:      req.status,
                };
            },

            cancelEdit() { this.editingId = null; },

            async saveEdit(req) {
                this.savingEdit = true;
                try {
                    const url  = _d.reqUpdateUrlTpl.replace('REQID', req.id);
                    const data = await _json(url, 'PATCH', this.editForm);
                    if (data.success) {
                        const idx = this.requirements.findIndex(r => r.id === req.id);
                        if (idx !== -1) {
                            this.requirements[idx] = {
                                ...this.requirements[idx],
                                ...this.editForm,
                            };
                        }
                        this.editingId = null;
                    }
                } catch {}
                this.savingEdit = false;
            },

            async deleteReq(req) {
                if (!await __confirm('[' + req.req_id + '] ' + req.title + '\n\n이 요구사항을 삭제하시겠습니까?')) return;
                try {
                    const url  = _d.reqDestroyUrlTpl.replace('REQID', req.id);
                    const data = await _json(url, 'DELETE', {});
                    if (data.success) {
                        this.requirements = this.requirements.filter(r => r.id !== req.id);
                    }
                } catch {}
            },

            async storeReq() {
                if (!this.addForm.title.trim()) { alert('제목을 입력해주세요.'); return; }
                this.addingReq = true;
                try {
                    const data = await _json(_d.reqStoreUrl, 'POST', this.addForm);
                    if (data.success) {
                        const r = data.requirement;
                        this.requirements.push({
                            id:          r.id,
                            req_id:      r.req_id,
                            title:       r.title,
                            description: r.description,
                            rationale:   r.rationale,
                            source_files:r.source_files,
                            priority:    r.priority?.value ?? r.priority,
                            category:    r.category,
                            status:      r.status,
                        });
                        this.resetAddForm();
                        this.showAddForm = false;
                    }
                } catch {}
                this.addingReq = false;
            },

            resetAddForm() {
                this.addForm = { title:'', description:'', rationale:'', priority:'should', category:'' };
            },

            onComplete(data) {
                this.analyzing = false;
                // Reload to get the freshly persisted requirements from DB
                window.location.reload();
            },

            onError() { this.analyzing = false; },
        };
    }

    window.toBePage = toBePage;
    window.handleToBeAnalysisComplete = async function (data) { if (window._tobePage) window._tobePage.onComplete(data); };
    window.handleToBeAnalysisError    = async function ()      { if (window._tobePage) window._tobePage.onError(); };
})();
</script>

<script>
// File upload drag-and-drop
(async function () {
    const input    = document.getElementById('tobe-file-input');
    const dropZone = document.getElementById('tobe-drop-zone');
    const preview  = document.getElementById('tobe-selected-files');
    const btn      = document.getElementById('tobe-upload-btn');
    if (!input) return;

    async function showFiles(files) {
        if (!files.length) { preview.style.display = 'none'; btn.style.display = 'none'; return; }
        preview.style.display = 'block';
        btn.style.display     = 'inline-block';
        preview.innerHTML = Array.from(files).map(f =>
            `<div style="font-size:12px;color:#475569;padding:3px 0;">📎 ${f.name} (${(f.size/1024).toFixed(1)} KB)</div>`
        ).join('');
    }

    input.addEventListener('change', () => showFiles(input.files));
    dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('drag-over');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        showFiles(dt.files);
    });
})();
</script>
@endpush
@endsection
