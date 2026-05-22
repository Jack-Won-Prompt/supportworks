@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.asis-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.asis-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.asis-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.asis-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.asis-screen-badge { display:inline-flex; align-items:center; gap:5px; background:#f5f3ff; border:1.5px solid #c4b5fd; color:var(--t700,#6d28d9); border-radius:8px; padding:4px 12px; font-size:12px; font-weight:700; font-family:monospace; }

.asis-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:20px; }
.asis-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

.asis-upload-zone { border:2px dashed #c4b5fd; border-radius:12px; padding:28px 20px; text-align:center; cursor:pointer; transition:all .15s; background:#faf5ff; }
.asis-upload-zone:hover, .asis-upload-zone.drag-over { border-color:var(--t600,#7c3aed); background:#f5f3ff; }
.asis-upload-input { display:none; }

.asis-file-list { display:flex; flex-direction:column; gap:8px; margin-top:14px; }
.asis-file-item { display:flex; align-items:center; gap:10px; padding:10px 14px; background:#faf5ff; border-radius:10px; border:1px solid #ede8ff; }
.asis-file-icon { width:32px; height:32px; border-radius:7px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.asis-file-info { flex:1; min-width:0; }
.asis-file-name { font-size:13px; font-weight:600; color:#1e1b2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.asis-file-meta { font-size:11.5px; color:#94a3b8; margin-top:1px; }
.asis-parse-badge { display:inline-flex; align-items:center; gap:4px; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:5px; white-space:nowrap; }
.asis-parse-badge.pending   { background:#f8fafc; color:#64748b; }
.asis-parse-badge.parsing   { background:#fffbeb; color:#d97706; }
.asis-parse-badge.completed { background:#f0fdf4; color:#166534; }
.asis-parse-badge.failed    { background:#fef2f2; color:#dc2626; }
.asis-file-del { display:inline-flex; align-items:center; padding:5px 8px; border-radius:6px; font-size:11.5px; border:1px solid #e2e8f0; cursor:pointer; background:#fff; color:#94a3b8; transition:all .12s; flex-shrink:0; }
.asis-file-del:hover { color:#dc2626; border-color:#fca5a5; }

.asis-status-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.asis-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 16px; }
.asis-stat-num   { font-size:20px; font-weight:800; color:var(--t600,#7c3aed); }
.asis-stat-label { font-size:11.5px; color:#64748b; line-height:1.4; }

.asis-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.asis-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.asis-btn.primary:hover   { background:var(--t700,#6d28d9); }
.asis-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.asis-btn.secondary:hover { background:#e2e8f0; }
.asis-btn:disabled  { opacity:.45; cursor:not-allowed; }

.asis-result-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:20px; }
.asis-result-header { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1px solid #f3eeff; flex-wrap:wrap; }
.asis-result-title  { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; }
.asis-result-body   { padding:20px 22px; }

.asis-summary-text { font-size:14px; color:#374151; line-height:1.8; white-space:pre-line; }
.asis-summary-textarea { width:100%; min-height:120px; border:1.5px solid #c4b5fd; border-radius:10px; padding:12px 14px; font-size:14px; line-height:1.8; color:#374151; resize:vertical; font-family:inherit; }

.asis-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:14px; }
.asis-filter-btn { padding:4px 12px; border-radius:20px; font-size:11.5px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#fff; color:#475569; transition:all .12s; }
.asis-filter-btn.active { border-color:var(--t400,#a78bfa); background:var(--t50,#f5f3ff); color:var(--t700,#6d28d9); }

.asis-issue-list { display:flex; flex-direction:column; gap:10px; }
.asis-issue { border:1.5px solid #ede8ff; border-radius:10px; padding:14px 16px; background:#fdfcff; }
.asis-issue.high   { border-left:4px solid #dc2626; }
.asis-issue.medium { border-left:4px solid #d97706; }
.asis-issue.low    { border-left:4px solid #64748b; }
.asis-issue-top { display:flex; align-items:flex-start; gap:8px; flex-wrap:wrap; margin-bottom:6px; }
.asis-severity { display:inline-flex; font-size:10px; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase; letter-spacing:.05em; }
.asis-severity.high   { background:#fef2f2; color:#dc2626; }
.asis-severity.medium { background:#fffbeb; color:#d97706; }
.asis-severity.low    { background:#f8fafc; color:#475569; }
.asis-category-badge { display:inline-flex; font-size:10.5px; font-weight:600; padding:2px 8px; border-radius:4px; background:#f0f9ff; color:#0369a1; }
.asis-issue-title { font-size:13px; font-weight:700; color:#1e1b2e; }
.asis-issue-desc  { font-size:12.5px; color:#475569; line-height:1.6; }
.asis-issue-files { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
.asis-file-tag { font-size:10.5px; color:#7c3aed; background:#f5f3ff; border:1px solid #ede8ff; border-radius:4px; padding:1px 6px; }

.asis-category-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }
.asis-category-card { background:#faf5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:14px 16px; }
.asis-category-name { font-size:12px; font-weight:700; color:var(--t700,#6d28d9); margin-bottom:8px; }
.asis-category-text { font-size:12.5px; color:#475569; line-height:1.7; }

.asis-mapping-list { display:flex; flex-direction:column; gap:10px; }
.asis-mapping-file { font-size:12px; font-weight:700; color:#1e1b2e; margin-bottom:6px; display:flex; align-items:center; gap:6px; }
.asis-mapping-findings { display:flex; flex-direction:column; gap:4px; padding-left:12px; border-left:2px solid #ede8ff; }
.asis-mapping-finding { font-size:12.5px; color:#475569; line-height:1.5; }

.asis-polling-bar { display:flex; align-items:center; gap:8px; font-size:12px; color:#d97706; background:#fffbeb; border:1px solid #fde68a; border-radius:8px; padding:8px 12px; margin-bottom:14px; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="asIsPage()" x-init="init()">

{{-- ── Header ─────────────────────────────────────────────────────────────── --}}
<div class="asis-header">
    <div class="asis-header-left">
        <h1>AS-IS 분석 &nbsp;<span class="asis-screen-badge">{{ $screen->screen_id }}</span></h1>
        <p>{{ $screen->title }} — 화면 단위 AS-IS 자료를 업로드하세요.</p>
    </div>
    <div class="asis-header-right">
        @if($artifact->version > 1)
        <x-ai-agent.version-history
            :artifact-id="$artifact->id"
            artifact-title="AS-IS 분석"
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
            source-ref="AS-IS#{{ $artifact->id }}"
            :links-url="$traceLinksUrl"
            :impact-url="$traceImpactUrl"
        />
        <a href="{{ $exportUrl }}" x-show="result !== null" x-cloak class="asis-btn secondary" title="Markdown 내보내기">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            내보내기
        </a>
        <a href="{{ route('ai-agent.projects.planning.screens.show', [$project, $screen]) }}"
           class="asis-btn secondary">
            ← 화면 상세
        </a>
    </div>
</div>

{{-- ── Status bar ──────────────────────────────────────────────────────────── --}}
<div class="asis-status-bar">
    <div class="asis-stat">
        <div class="asis-stat-num">{{ $files->count() }}</div>
        <div class="asis-stat-label">전체 파일</div>
    </div>
    <div class="asis-stat">
        <div class="asis-stat-num" style="color:#166534">{{ $files->where('parse_status','completed')->count() }}</div>
        <div class="asis-stat-label">파싱 완료</div>
    </div>
    @if($files->whereIn('parse_status',['pending','parsing'])->count() > 0)
    <div class="asis-stat">
        <div class="asis-stat-num" style="color:#d97706">{{ $files->whereIn('parse_status',['pending','parsing'])->count() }}</div>
        <div class="asis-stat-label">처리 중</div>
    </div>
    @endif
    @if($files->where('parse_status','failed')->count() > 0)
    <div class="asis-stat">
        <div class="asis-stat-num" style="color:#dc2626">{{ $files->where('parse_status','failed')->count() }}</div>
        <div class="asis-stat-label">오류</div>
    </div>
    @endif
    @if($artifact->meta['analyzed_at'] ?? false)
    <div class="asis-stat" style="border-color:#bbf7d0;">
        <div class="asis-stat-num" style="color:#166534;font-size:12px;line-height:1.3">분석 완료</div>
        <div class="asis-stat-label">{{ \Carbon\Carbon::parse($artifact->meta['analyzed_at'])->format('m/d H:i') }}</div>
    </div>
    @endif
</div>

{{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

{{-- Parse polling indicator --}}
@if($files->whereIn('parse_status',['pending','parsing'])->count() > 0)
<div class="asis-polling-bar">
    <div style="width:14px;height:14px;border:2px solid #fde68a;border-top-color:#d97706;border-radius:50%;animation:aip-spin .7s linear infinite;flex-shrink:0;"></div>
    <span>파일 파싱 중 — 완료되면 자동으로 새로고침됩니다.</span>
</div>
@endif

{{-- ── File upload ─────────────────────────────────────────────────────────── --}}
<div class="asis-section">
    <div class="asis-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
        자료 업로드
    </div>

    <form method="POST" action="{{ route('ai-agent.projects.planning.screens.as-is.upload', [$project, $screen]) }}"
          enctype="multipart/form-data" id="upload-form">
        @csrf
        <div class="asis-upload-zone" id="drop-zone" onclick="document.getElementById('file-input').click()">
            <div style="font-size:32px;margin-bottom:8px;">📁</div>
            <div style="font-size:13.5px;color:#475569;margin-bottom:4px;">파일을 드래그하거나 클릭하여 업로드</div>
            <div style="font-size:11.5px;color:#94a3b8;">Excel, PowerPoint, PDF, 이미지, 텍스트 — 최대 50MB / 최대 10개</div>
        </div>
        <input type="file" id="file-input" name="files[]" multiple class="asis-upload-input"
               accept=".xlsx,.xls,.pptx,.ppt,.pdf,.txt,.csv,.json,.md,.jpg,.jpeg,.png,.gif,.webp">
        <div id="selected-files" style="display:none;margin-top:12px;"></div>
        <button type="submit" id="upload-btn" style="display:none;margin-top:12px;padding:9px 20px;background:var(--t600,#7c3aed);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;">
            업로드
        </button>
    </form>
</div>

{{-- ── File list ────────────────────────────────────────────────────────────── --}}
@if($files->count() > 0)
<div class="asis-section">
    <div class="asis-section-title">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>
        업로드된 파일 ({{ $files->count() }})
    </div>
    <div class="asis-file-list">
        @foreach($files as $file)
        @php $icon = match($file->file_type) { 'excel'=>'📊','pptx'=>'📊','pdf'=>'📄','image'=>'🖼','text'=>'📝',default=>'📎' }; @endphp
        <div class="asis-file-item">
            <div class="asis-file-icon {{ $file->file_type }}">{{ $icon }}</div>
            <div class="asis-file-info">
                <div class="asis-file-name" title="{{ $file->file_name }}">{{ $file->file_name }}</div>
                <div class="asis-file-meta">{{ $file->formatted_size }} · {{ $file->mime_type }} · {{ $file->created_at->format('Y-m-d H:i') }}</div>
            </div>
            <span class="asis-parse-badge {{ $file->parse_status }}">
                @if($file->parse_status==='completed') ✓ 파싱 완료
                @elseif($file->parse_status==='parsing') ⟳ 파싱 중
                @elseif($file->parse_status==='failed') ✕ 오류
                @else ○ 대기 중 @endif
            </span>
            <form method="POST" action="{{ route('ai-agent.projects.planning.screens.as-is.file.delete', [$project, $screen, $file]) }}"
                  onsubmit="return confirm('파일을 삭제하시겠습니까?')">
                @csrf @method('DELETE')
                <button type="submit" class="asis-file-del" title="삭제">✕</button>
            </form>
        </div>
        @if($file->parse_status==='failed' && $file->parse_error)
        <div style="font-size:11.5px;color:#dc2626;padding:4px 14px;background:#fef2f2;border-radius:6px;margin-top:-4px;">
            오류: {{ $file->parse_error }}
        </div>
        @endif
        @endforeach
    </div>
</div>
@endif

{{-- ── 웍스 Analysis ──────────────────────────────────────────────────────────── --}}
@if($files->where('parse_status','completed')->count() > 0)
<div class="asis-section">
    <div class="asis-section-title" style="margin-bottom:10px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/></svg>
        웍스 분석
        @if($artifact->meta['analyzed_at'] ?? false)
        <span style="font-size:11px;font-weight:500;color:#94a3b8;margin-left:4px;">마지막 분석: {{ \Carbon\Carbon::parse($artifact->meta['analyzed_at'])->diffForHumans() }}</span>
        @endif
    </div>

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;">
        <button @click="triggerAnalyze()" class="asis-btn primary" :disabled="analyzing">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <span x-text="analyzing ? '분석 중...' : (result ? '재분석' : '웍스 분석 시작')"></span>
        </button>
        <span x-show="!analyzing && result === null" style="font-size:12.5px;color:#94a3b8;">
            {{ $files->where('parse_status','completed')->count() }}개 파일 준비됨
        </span>
    </div>

    <x-ai-agent.ai-progress
        mode="streaming"
        :start-url="$startUrl"
        :sse-url-tpl="$sseUrlTpl"
        :cancel-url-tpl="$cancelUrlTpl"
        :show-output="false"
        label="AS-IS 분석 실행"
        on-complete="handleAnalysisComplete"
        on-error="handleAnalysisError"
        x-ref="aiProgress"
    />
</div>
@endif

{{-- ── Result display ───────────────────────────────────────────────────────── --}}
<div x-show="result !== null" x-cloak>

    <div class="asis-result-section">
        <div class="asis-result-header">
            <div class="asis-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                현황 요약
            </div>
            <button x-show="!editMode" @click="startEdit()" class="asis-btn secondary" style="padding:5px 10px;font-size:12px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                편집
            </button>
            <template x-if="editMode">
                <div style="display:flex;gap:8px;">
                    <button @click="saveEdit()" :disabled="saving" class="asis-btn primary" style="padding:5px 12px;font-size:12px;">
                        <span x-text="saving ? '저장 중...' : '저장'"></span>
                    </button>
                    <button @click="cancelEdit()" class="asis-btn secondary" style="padding:5px 10px;font-size:12px;">취소</button>
                </div>
            </template>
        </div>
        <div class="asis-result-body">
            <p x-show="!editMode" class="asis-summary-text" x-text="result?.summary ?? ''"></p>
            <textarea x-show="editMode" x-cloak class="asis-summary-textarea" x-model="editedSummary" placeholder="현황 요약을 입력하세요..."></textarea>
        </div>
    </div>

    <div class="asis-result-section">
        <div class="asis-result-header">
            <div class="asis-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#dc2626"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                이슈 목록
                <span style="font-size:12px;font-weight:500;color:#94a3b8;margin-left:6px;"
                      x-text="'(' + filteredIssues.length + '/' + (result?.issues?.length ?? 0) + '건)'"></span>
            </div>
        </div>
        <div class="asis-result-body">
            <div class="asis-filters">
                <button class="asis-filter-btn" :class="filterSeverity==='all'?'active':''" @click="filterSeverity='all'">전체</button>
                <button class="asis-filter-btn" :class="filterSeverity==='high'?'active':''" @click="filterSeverity='high'"
                        :style="filterSeverity==='high' ? 'background:#fef2f2;border-color:#dc2626;color:#dc2626;' : 'border-color:#fca5a5;color:#dc2626;'">HIGH</button>
                <button class="asis-filter-btn" :class="filterSeverity==='medium'?'active':''" @click="filterSeverity='medium'"
                        :style="filterSeverity==='medium' ? 'background:#fffbeb;border-color:#d97706;color:#d97706;' : 'border-color:#fde68a;color:#d97706;'">MEDIUM</button>
                <button class="asis-filter-btn" :class="filterSeverity==='low'?'active':''" @click="filterSeverity='low'">LOW</button>
                <span style="font-size:11px;color:#94a3b8;margin-left:4px;align-self:center;">|</span>
                <template x-for="cat in uniqueCategories" :key="cat">
                    <button class="asis-filter-btn" :class="filterCategory===cat?'active':''" @click="filterCategory = filterCategory===cat ? 'all' : cat" x-text="cat"></button>
                </template>
            </div>
            <div class="asis-issue-list">
                <template x-for="(issue, idx) in filteredIssues" :key="idx">
                    <div class="asis-issue" :class="issue.severity">
                        <div class="asis-issue-top">
                            <span class="asis-severity" :class="issue.severity" x-text="issue.severity?.toUpperCase()"></span>
                            <span class="asis-category-badge" x-text="issue.category"></span>
                            <span class="asis-issue-title" x-text="issue.title"></span>
                        </div>
                        <p class="asis-issue-desc" x-text="issue.description"></p>
                        <div class="asis-issue-files" x-show="issue.source_files?.length > 0">
                            <template x-for="f in (issue.source_files ?? [])" :key="f">
                                <span class="asis-file-tag" x-text="f"></span>
                            </template>
                        </div>
                    </div>
                </template>
                <div x-show="filteredIssues.length === 0" style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">
                    해당 필터에 맞는 이슈가 없습니다.
                </div>
            </div>
        </div>
    </div>

    <div class="asis-result-section" x-show="Object.keys(result?.categories ?? {}).length > 0">
        <div class="asis-result-header">
            <div class="asis-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:var(--t600,#7c3aed)"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                카테고리별 분석
            </div>
        </div>
        <div class="asis-result-body">
            <div class="asis-category-grid">
                <template x-for="[cat, text] in Object.entries(result?.categories ?? {})" :key="cat">
                    <div class="asis-category-card">
                        <div class="asis-category-name" x-text="cat"></div>
                        <div class="asis-category-text" x-text="text"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <div class="asis-result-section" x-show="Object.keys(result?.source_mapping ?? {}).length > 0">
        <div class="asis-result-header">
            <div class="asis-result-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align:-2px;margin-right:5px;color:#0369a1"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                파일별 주요 발견사항
            </div>
        </div>
        <div class="asis-result-body">
            <div class="asis-mapping-list">
                <template x-for="[file, findings] in Object.entries(result?.source_mapping ?? {})" :key="file">
                    <div>
                        <div class="asis-mapping-file">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                            <span x-text="file"></span>
                        </div>
                        <div class="asis-mapping-findings">
                            <template x-for="(finding, fi) in findings" :key="fi">
                                <div class="asis-mapping-finding" x-text="'• ' + finding"></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>{{-- /result --}}

</div>{{-- /x-data --}}

@push('scripts')
<script id="asis-page-data" type="application/json">{!! json_encode([
    'result'     => $result,
    'saveUrl'    => $saveUrl,
    'statusUrl'  => $statusUrl,
    'hasPending' => $files->whereIn('parse_status', ['pending', 'parsing'])->count() > 0,
]) !!}</script>

<script>
(async function () {
    const _d = JSON.parse(document.getElementById('asis-page-data').textContent);

    async function asIsPage() {
        return {
            result:         _d.result,
            analyzing:      false,
            editMode:       false,
            editedSummary:  '',
            saving:         false,
            filterSeverity: 'all',
            filterCategory: 'all',
            saveUrl:        _d.saveUrl,

            get filteredIssues() {
                const issues = this.result?.issues ?? [];
                return issues.filter(i =>
                    (this.filterSeverity === 'all' || i.severity === this.filterSeverity) &&
                    (this.filterCategory === 'all' || i.category === this.filterCategory)
                );
            },

            get uniqueCategories() {
                const cats = (this.result?.issues ?? []).map(i => i.category).filter(Boolean);
                return [...new Set(cats)];
            },

            init() {
                window._asisPage = this;
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
                this.analyzing = true;
                this.$refs.aiProgress.start('', {});
            },

            startEdit() {
                this.editedSummary = this.result?.summary ?? '';
                this.editMode = true;
            },

            cancelEdit() { this.editMode = false; },

            async saveEdit() {
                this.saving = true;
                try {
                    const updated = { ...this.result, summary: this.editedSummary };
                    const res = await fetch(this.saveUrl, {
                        method:  'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body:    JSON.stringify({ result: updated }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.result   = updated;
                        this.editMode = false;
                    }
                } catch {}
                this.saving = false;
            },

            onComplete(data) {
                this.analyzing = false;
                if (data.result) this.result = data.result;
            },

            onError() {
                this.analyzing = false;
            },
        };
    }

    window.asIsPage = asIsPage;
    window.handleAnalysisComplete = async function (data) { if (window._asisPage) window._asisPage.onComplete(data); };
    window.handleAnalysisError    = async function ()      { if (window._asisPage) window._asisPage.onError(); };
})();
</script>

<script>
(async function () {
    const input    = document.getElementById('file-input');
    const dropZone = document.getElementById('drop-zone');
    const preview  = document.getElementById('selected-files');
    const btn      = document.getElementById('upload-btn');
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
