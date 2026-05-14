@extends('layouts.app')

@section('title', '논의사항')

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">논의사항</span>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.quilljs.com/1.3.7/quill.snow.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>
.dsc-card { background:#fff;border:1px solid #f3f4f6;border-radius:12px;padding:14px 16px;cursor:pointer;transition:background .12s,border-color .12s;display:flex;gap:14px;align-items:flex-start; }
.dsc-card:hover { background:#fafaff;border-color:#ddd6fe; }
.dsc-card-date {
    flex-shrink:0;width:62px;border-radius:10px;overflow:hidden;
    border:1.5px solid #ddd6fe;background:#fff;text-align:center;
    box-shadow:0 1px 3px rgba(0,0,0,.04);
}
.dsc-card-date .dcd-month { background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;font-size:10px;font-weight:700;padding:3px 0;letter-spacing:.08em;text-transform:uppercase; }
.dsc-card-date .dcd-day   { font-size:22px;font-weight:800;color:#5b21b6;line-height:1;padding:8px 0 4px; }
.dsc-card-date .dcd-year  { font-size:10px;color:#9ca3af;padding-bottom:5px;font-weight:600; }
.dsc-card-date.is-empty .dcd-month { background:#e5e7eb;color:#9ca3af; }
.dsc-card-date.is-empty .dcd-day   { color:#9ca3af;font-size:18px; }
.dsc-card-body { flex:1;min-width:0; }
.dsc-badge { display:inline-flex;align-items:center;font-size:11px;font-weight:700;padding:3px 10px;border-radius:20px; }
.dsc-modal-bg { display:none;position:fixed;inset:0;z-index:9999;background:rgba(15,12,30,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px; }
.dsc-modal-bg.is-open { display:flex; }
.dsc-modal { width:960px;max-width:calc(100vw - 48px);height:88vh;max-height:88vh;background:#fff;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3); }
.dsc-form-input { width:100%;padding:10px 13px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;color:#1f2937;outline:none;transition:border-color .15s;box-sizing:border-box; }
.dsc-form-input:focus { border-color:#a78bfa; }
.ql-editor { min-height:200px; font-size:14px; }
.dsc-att-pill { display:inline-flex;align-items:center;gap:6px;padding:6px 10px;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;color:#374151;text-decoration:none;transition:background .12s;max-width:240px; }
.dsc-att-pill:hover { background:#ede9fe;border-color:#c4b5fd; }
.dsc-att-name { overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:160px; }
.dsc-chip { display:inline-flex;align-items:center;gap:5px;padding:4px 9px;background:#ede9fe;color:#5b21b6;border-radius:14px;font-size:11px;font-weight:600; }
.dsc-status-btn { padding:5px 12px;font-size:12px;font-weight:600;border-radius:7px;border:1.5px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer;transition:all .12s; }
.dsc-status-btn:hover { border-color:#a78bfa;color:#7c3aed; }
.dsc-status-btn.active { background:#ede9fe;color:#5b21b6;border-color:#c4b5fd; }
.dsc-status-filter-btn { padding:6px 12px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;transition:all .12s; }
.dsc-status-filter-btn.active { background:#eef2ff;color:#4f46e5;border-color:#c7d2fe; }
.dsc-date-filter-btn { padding:5px 11px;font-size:11px;font-weight:600;border-radius:7px;border:1.5px solid #e5e7eb;background:#fff;color:#6b7280;cursor:pointer;transition:all .12s; }
.dsc-date-filter-btn.active { background:#ede9fe;color:#5b21b6;border-color:#c4b5fd; }

/* 뷰 토글 */
.dsc-view-btn { display:inline-flex;align-items:center;gap:4px;padding:5px 10px;font-size:11.5px;font-weight:600;background:none;border:none;color:#6b7280;border-radius:6px;cursor:pointer;transition:all .12s; }
.dsc-view-btn.active { background:#fff;color:#5b21b6;box-shadow:0 1px 3px rgba(0,0,0,.08); }
.dsc-view-btn:hover:not(.active) { color:#374151; }

/* 테이블뷰 */
.dsc-table { width:100%;border-collapse:collapse;font-size:13px; }
.dsc-table thead th { background:#fafafa;padding:10px 14px;text-align:left;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;border-bottom:1px solid #f3f4f6; }
.dsc-table tbody tr { border-bottom:1px solid #f9fafb;cursor:pointer;transition:background .1s; }
.dsc-table tbody tr:last-child { border-bottom:none; }
.dsc-table tbody tr:hover { background:#faf5ff; }
.dsc-table td { padding:10px 14px;vertical-align:middle; }

/* 페이지네이션 */
.dsc-pagination { display:flex;justify-content:center;align-items:center;gap:8px;margin-top:18px; }
.dsc-page-btn { display:inline-block;padding:7px 14px;font-size:12px;font-weight:600;color:#374151;background:#fff;border:1.5px solid #e5e7eb;border-radius:8px;text-decoration:none;transition:all .12s; }
.dsc-page-btn:hover { border-color:#a78bfa;color:#5b21b6;background:#faf5ff; }
.dsc-page-btn.disabled { color:#d1d5db;background:#fafafa;cursor:not-allowed;border-color:#f3f4f6; }
.dsc-page-info { font-size:12px;color:#6b7280;font-weight:600;padding:0 6px; }

/* 상세 팝업 — 인라인 날짜 편집 (칩 모양 input) */
.dsc-date-edit {
    display:inline-flex;align-items:center;gap:4px;
    padding:2px 8px;background:#eef2ff;border:1px solid #c7d2fe;border-radius:10px;
    font-size:11px;font-weight:600;color:#4f46e5;cursor:pointer;
    transition:background .12s, border-color .12s;
}
.dsc-date-edit:hover { background:#e0e7ff;border-color:#a5b4fc; }
.dsc-date-edit input[type="date"] {
    border:none;background:transparent;outline:none;
    color:inherit;font-size:11px;font-weight:600;font-family:inherit;
    padding:0;margin:0;cursor:pointer;
    width:auto;min-width:96px;
}
.dsc-date-edit input[type="date"]::-webkit-calendar-picker-indicator { cursor:pointer;opacity:.55;filter:invert(28%) sepia(89%) saturate(2476%) hue-rotate(232deg); }
.dsc-date-edit-hint { color:#9ca3af;font-weight:500; }
.dsc-comment { background:#f9fafb;border:1px solid #f3f4f6;border-radius:10px;padding:12px 14px; }
.dsc-comment-meta { display:flex;align-items:center;gap:6px;margin-bottom:5px; }
.dsc-comment-name { font-size:12px;font-weight:700;color:#1f2937; }
.dsc-comment-time { font-size:11px;color:#9ca3af; }
.dsc-status-pill-open        { background:#dbeafe;color:#1d4ed8; }
.dsc-status-pill-in_progress { background:#fef3c7;color:#b45309; }
.dsc-status-pill-resolved    { background:#dcfce7;color:#15803d; }

/* 파일 선택 버튼 (네이티브 input 숨김 + 라벨 스타일) */
.dsc-file-input { position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0 0 0 0);white-space:nowrap;border:0; }
.dsc-file-pick {
    display:inline-flex;align-items:center;gap:6px;
    padding:7px 13px;background:#fff;border:1.5px solid #e5e7eb;
    border-radius:8px;font-size:12px;font-weight:600;color:#6b7280;
    cursor:pointer;transition:all .15s;
}
.dsc-file-pick:hover { border-color:#a78bfa;color:#7c3aed;background:#faf5ff; }
.dsc-file-pick svg  { flex-shrink:0; }
.dsc-file-count { color:#7c3aed;font-weight:700; }
.dsc-file-count:empty { display:none; }

/* 메인 탭 (내용/결론/의견) */
.dsc-main-tab {
    display:inline-flex;align-items:center;gap:6px;
    padding:9px 16px;background:none;border:none;
    font-size:13px;font-weight:600;color:#9ca3af;cursor:pointer;
    border-bottom:2px solid transparent;margin-bottom:-1.5px;
    transition:color .12s, border-color .12s;
}
.dsc-main-tab:hover { color:#7c3aed; }
.dsc-main-tab.active { color:#7c3aed;border-bottom-color:#7c3aed;font-weight:700; }
.dsc-tab-pill {
    display:inline-flex;align-items:center;justify-content:center;
    min-width:18px;height:18px;padding:0 6px;border-radius:9px;
    background:#ede9fe;color:#5b21b6;font-size:10px;font-weight:700;
}
.dsc-main-tab.active .dsc-tab-pill { background:#7c3aed;color:#fff; }

/* 상태 세그먼트 버튼 */
.dsc-status-btn-seg {
    padding:5px 12px;font-size:11px;font-weight:600;
    border:none;border-right:1px solid #e5e7eb;background:#fff;color:#6b7280;
    cursor:pointer;transition:background .12s, color .12s;
}
.dsc-status-btn-seg:last-child { border-right:none; }
.dsc-status-btn-seg:hover { background:#fafafa;color:#374151; }
.dsc-status-btn-seg.active { background:#7c3aed;color:#fff; }

/* 편집/보기 탭 */
.dsc-md-tabs { display:flex;gap:0;border-bottom:1.5px solid #e5e7eb;margin-bottom:0; }
.dsc-md-tab {
    padding:7px 14px;background:none;border:none;border-bottom:2px solid transparent;
    font-size:12px;font-weight:600;color:#9ca3af;cursor:pointer;display:inline-flex;align-items:center;gap:5px;
    transition:color .12s, border-color .12s;
    margin-bottom:-1.5px;
}
.dsc-md-tab:hover { color:#7c3aed; }
.dsc-md-tab.active { color:#7c3aed;border-bottom-color:#7c3aed; }
.dsc-md-tab svg { flex-shrink:0; }

/* Plain Markdown textarea (기획서 편집과 동일) */
.dsc-md-source {
    width:100%;border:1.5px solid #e5e7eb;border-radius:9px;
    padding:12px 14px;font-family:'Courier New',Consolas,monospace;
    font-size:13px;line-height:1.7;color:#1f2937;background:#fff;
    outline:none;resize:vertical;box-sizing:border-box;
    transition:border-color .15s;
}
.dsc-md-source:focus { border-color:#a78bfa;box-shadow:0 0 0 3px rgba(196,181,253,.12); }

/* EasyMDE 컴팩트 스타일 */
.dsc-md-wrap .EasyMDEContainer { background:#fff; }
.dsc-md-wrap .editor-toolbar { padding:4px 6px;border:1.5px solid #e5e7eb;border-bottom:none;border-radius:8px 8px 0 0;background:#fafaff; }
.dsc-md-wrap .CodeMirror {
    min-height:180px !important;border:1.5px solid #e5e7eb;border-radius:0 0 8px 8px;
    font-size:13px;line-height:1.65;
    font-family:'Courier New', Consolas, 'D2Coding', monospace;  /* 모노스페이스로 raw Markdown 느낌 */
}
.dsc-md-wrap .CodeMirror-scroll { min-height:180px !important;max-height:520px; }
.dsc-md-wrap .editor-preview-side, .dsc-md-wrap .editor-preview { background:#faf5ff;padding:10px 14px;font-size:13px;line-height:1.65; }
.dsc-md-wrap .editor-preview-side img, .dsc-md-wrap .editor-preview img { max-width:100%;height:auto;border-radius:6px;margin:4px 0; }
.dsc-md-wrap .editor-statusbar { display:none; }
.dsc-md-wrap .editor-toolbar.fullscreen, .dsc-md-wrap .CodeMirror-fullscreen { z-index:10020; }

/* CodeMirror Markdown 구문 강조 톤다운 — 헤딩 글자가 크게 부풀지 않도록 */
.dsc-md-wrap .CodeMirror .cm-header        { font-size:inherit !important;line-height:inherit !important;color:#5b21b6;font-weight:700; }
.dsc-md-wrap .CodeMirror .cm-header-1      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-header-2      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-header-3      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-header-4      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-header-5      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-header-6      { font-size:inherit !important; }
.dsc-md-wrap .CodeMirror .cm-formatting-header { color:#a78bfa;font-weight:400; }
.dsc-md-wrap .CodeMirror .cm-strong        { font-weight:700;color:#1f2937; }
.dsc-md-wrap .CodeMirror .cm-em            { font-style:italic;color:#4b5563; }
.dsc-md-wrap .CodeMirror .cm-quote         { color:#6b7280;font-style:italic; }
.dsc-md-wrap .CodeMirror .cm-link, .dsc-md-wrap .CodeMirror .cm-url { color:#7c3aed; }
.dsc-md-wrap .CodeMirror .cm-comment       { color:#0891b2;background:#ecfeff;padding:0 3px;border-radius:3px; }

/* 의견 카드의 Markdown 렌더링 */
.dsc-md-rendered { font-size:13px;color:#374151;line-height:1.65;word-break:break-word; }
.dsc-md-rendered p { margin:0 0 6px; }
.dsc-md-rendered p:last-child { margin-bottom:0; }
.dsc-md-rendered h1, .dsc-md-rendered h2, .dsc-md-rendered h3, .dsc-md-rendered h4 { margin:8px 0 4px;font-weight:700;color:#18181b; }
.dsc-md-rendered h1 { font-size:16px; } .dsc-md-rendered h2 { font-size:15px; }
.dsc-md-rendered h3 { font-size:14px; } .dsc-md-rendered h4 { font-size:13px; }
.dsc-md-rendered ul, .dsc-md-rendered ol { padding-left:22px;margin:4px 0 6px; }
.dsc-md-rendered li { margin-bottom:2px; }
.dsc-md-rendered a { color:#7c3aed;text-decoration:underline; }
.dsc-md-rendered img { max-width:100%;height:auto;border-radius:6px;margin:4px 0; }
.dsc-md-rendered code { background:#f3f4f6;color:#5b21b6;padding:1px 5px;border-radius:4px;font-size:12px; }
.dsc-md-rendered pre { background:#1f2937;color:#e5e7eb;padding:9px 11px;border-radius:6px;overflow-x:auto;margin:6px 0; }
.dsc-md-rendered pre code { background:transparent;color:inherit;padding:0; }
.dsc-md-rendered blockquote { border-left:3px solid #c4b5fd;margin:6px 0;padding:2px 10px;color:#6b7280;background:#faf5ff; }
.dsc-md-rendered table { border-collapse:collapse;margin:6px 0; }
.dsc-md-rendered th, .dsc-md-rendered td { border:1px solid #e5e7eb;padding:5px 9px;font-size:12px; }
.dsc-md-rendered th { background:#f9fafb;font-weight:700; }
</style>
@endpush

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'discussions'])

<div style="display:flex;flex-direction:column;gap:16px;">

    {{-- 상단 헤더 --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #f3f4f6;padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <div>
            <h2 style="margin:0 0 4px;font-size:17px;font-weight:700;color:#18181b;display:flex;align-items:center;gap:8px;">
                <svg width="18" height="18" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                논의사항
            </h2>
            <p style="margin:0;font-size:12px;color:#6b7280;">프로젝트 관련 일반 주제·공유·의견을 자유롭게 등록하세요.</p>
        </div>
        <button onclick="dscOpenCreate()" style="display:inline-flex;align-items:center;gap:6px;padding:9px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;box-shadow:0 4px 14px rgba(124,58,237,.3);">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            논의 등록
        </button>
    </div>

    {{-- 상태 필터 --}}
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <button class="dsc-status-filter-btn active" data-filter="all"         onclick="dscFilter('all', this)">전체 ({{ $totalDiscussions }})</button>
        <button class="dsc-status-filter-btn"        data-filter="open"        onclick="dscFilter('open', this)">진행 전 ({{ $statusCounts['open'] ?? 0 }})</button>
        <button class="dsc-status-filter-btn"        data-filter="in_progress" onclick="dscFilter('in_progress', this)">진행 중 ({{ $statusCounts['in_progress'] ?? 0 }})</button>
        <button class="dsc-status-filter-btn"        data-filter="resolved"    onclick="dscFilter('resolved', this)">완료 ({{ $statusCounts['resolved'] ?? 0 }})</button>
    </div>

    {{-- 일자 필터 --}}
    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
        <span style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px;margin-right:4px;">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            일자
        </span>
        <button class="dsc-date-filter-btn active" data-date="all"   onclick="dscFilterDate('all',   this)">전체</button>
        <button class="dsc-date-filter-btn"        data-date="today" onclick="dscFilterDate('today', this)">오늘</button>
        <button class="dsc-date-filter-btn"        data-date="week"  onclick="dscFilterDate('week',  this)">이번 주</button>
        <button class="dsc-date-filter-btn"        data-date="month" onclick="dscFilterDate('month', this)">이번 달</button>
        <input type="date" id="dsc-date-from" onchange="dscApplyFilters()" style="padding:5px 8px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:11px;color:#374151;outline:none;">
        <span style="font-size:11px;color:#9ca3af;">~</span>
        <input type="date" id="dsc-date-to"   onchange="dscApplyFilters()" style="padding:5px 8px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:11px;color:#374151;outline:none;">
        <button onclick="dscClearDateRange()" style="padding:4px 10px;font-size:11px;color:#6b7280;background:none;border:1px solid #e5e7eb;border-radius:6px;cursor:pointer;">초기화</button>
        <div style="margin-left:auto;display:inline-flex;align-items:center;gap:8px;">
            <span style="font-size:11px;color:#9ca3af;">{{ $discussions->total() }}건 중 {{ $discussions->firstItem() ?? 0 }}–{{ $discussions->lastItem() ?? 0 }}</span>
            <div id="dsc-view-toggle" style="display:inline-flex;background:#f3f4f6;border:1px solid #e5e7eb;border-radius:8px;padding:2px;">
                <button type="button" id="dsc-view-card" onclick="dscSwitchView('card')" class="dsc-view-btn active" title="카드뷰">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    카드
                </button>
                <button type="button" id="dsc-view-table" onclick="dscSwitchView('table')" class="dsc-view-btn" title="테이블뷰">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M3 6h18M3 18h18"/></svg>
                    테이블
                </button>
            </div>
        </div>
    </div>

    {{-- 목록 - 카드뷰 --}}
    <div id="dsc-list" style="display:flex;flex-direction:column;gap:10px;">
        @if($discussions->isEmpty())
        <div style="padding:48px 0;text-align:center;color:#9ca3af;font-size:13px;background:#fff;border-radius:12px;border:1px dashed #e5e7eb;">
            등록된 논의가 없습니다. 첫 논의를 등록해 보세요.
        </div>
        @else
        @foreach($discussions as $d)
        <div class="dsc-card" data-status="{{ $d->status }}" data-date="{{ $d->discussion_date?->format('Y-m-d') ?: '' }}" onclick="dscOpenDetail({{ $d->id }})">
            {{-- 좌측 캘린더 블록 (논의 일정) --}}
            @if($d->discussion_date)
                @php $_mAbbr = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'][$d->discussion_date->month - 1]; @endphp
                <div class="dsc-card-date" title="논의 일정">
                    <div class="dcd-month">{{ $_mAbbr }}</div>
                    <div class="dcd-day">{{ $d->discussion_date->format('d') }}</div>
                    <div class="dcd-year">{{ $d->discussion_date->format('Y') }}</div>
                </div>
            @else
                <div class="dsc-card-date is-empty" title="논의 일정 미지정">
                    <div class="dcd-month">미정</div>
                    <div class="dcd-day">–</div>
                    <div class="dcd-year">&nbsp;</div>
                </div>
            @endif

            {{-- 본문 --}}
            <div class="dsc-card-body">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                <span class="dsc-badge dsc-status-pill-{{ $d->status }}">{{ $d->status_label }}</span>
                @if($d->reflection_status === 'reflected')
                    <span class="dsc-badge" style="background:#dcfce7;color:#15803d;display:inline-flex;align-items:center;gap:3px;"
                          title="{{ optional($d->reflectedPlanningDoc)->title ? '기획서: '.$d->reflectedPlanningDoc->title : '기획서 반영됨' }}">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/></svg>
                        기획서 반영
                    </span>
                @elseif($d->reflection_status === 'rejected')
                    <span class="dsc-badge" style="background:#fee2e2;color:#b91c1c;display:inline-flex;align-items:center;gap:3px;"
                          title="{{ $d->reflection_note ?: '반영하지 않기로 결정' }}">
                        <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        반영하지 않음
                    </span>
                @endif
                <span style="font-size:11px;color:#9ca3af;">{{ $d->updated_at->diffForHumans() }} 업데이트</span>
            </div>
            <div style="font-size:15px;font-weight:600;color:#18181b;margin-bottom:6px;">{{ $d->title }}</div>
            <div style="font-size:13px;color:#6b7280;line-height:1.5;max-height:42px;overflow:hidden;text-overflow:ellipsis;">{{ Str::limit(strip_tags($d->content), 200) }}</div>
            <div style="display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap;">
                <span style="font-size:11px;color:#9ca3af;">작성: <b style="color:#374151;">{{ $d->author->name }}</b></span>
                <span style="font-size:11px;color:#9ca3af;display:inline-flex;align-items:center;gap:3px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    {{ $d->comments_count }}
                </span>
                @if($d->attachments_count > 0)
                <span style="font-size:11px;color:#9ca3af;display:inline-flex;align-items:center;gap:3px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    {{ $d->attachments_count }}
                </span>
                @endif
                @if($d->participants->isNotEmpty())
                <span style="font-size:11px;color:#9ca3af;">공유: {{ $d->participants->take(3)->pluck('name')->join(', ') }}{{ $d->participants->count() > 3 ? ' 외 '.($d->participants->count()-3).'명' : '' }}</span>
                @endif
            </div>
            </div>{{-- /dsc-card-body --}}
        </div>
        @endforeach
        @endif
    </div>

    {{-- 목록 - 테이블뷰 --}}
    <div id="dsc-table-wrap" style="display:none;background:#fff;border:1px solid #f3f4f6;border-radius:12px;overflow:hidden;">
        @if($discussions->isEmpty())
        <div style="padding:48px 0;text-align:center;color:#9ca3af;font-size:13px;">
            등록된 논의가 없습니다. 첫 논의를 등록해 보세요.
        </div>
        @else
        <table class="dsc-table">
            <thead>
                <tr>
                    <th style="width:104px;">논의 일정</th>
                    <th>제목</th>
                    <th style="width:90px;">상태</th>
                    <th style="width:114px;">반영</th>
                    <th style="width:96px;">작성자</th>
                    <th style="width:60px;text-align:center;">의견</th>
                    <th style="width:60px;text-align:center;">첨부</th>
                    <th style="width:130px;">업데이트</th>
                </tr>
            </thead>
            <tbody>
            @foreach($discussions as $d)
                <tr class="dsc-row" data-status="{{ $d->status }}" data-date="{{ $d->discussion_date?->format('Y-m-d') ?: '' }}" onclick="dscOpenDetail({{ $d->id }})">
                    <td>{{ $d->discussion_date?->format('Y-m-d') ?: '—' }}</td>
                    <td style="max-width:0;">
                        <div style="font-weight:600;color:#18181b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $d->title }}</div>
                        <div style="font-size:11px;color:#9ca3af;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ Str::limit(strip_tags($d->content), 120) }}</div>
                    </td>
                    <td><span class="dsc-badge dsc-status-pill-{{ $d->status }}">{{ $d->status_label }}</span></td>
                    <td>
                        @if($d->reflection_status === 'reflected')
                            <span class="dsc-badge" style="background:#dcfce7;color:#15803d;display:inline-flex;align-items:center;gap:3px;"
                                  title="{{ optional($d->reflectedPlanningDoc)->title ? '기획서: '.$d->reflectedPlanningDoc->title : '기획서 반영됨' }}">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/></svg>
                                반영
                            </span>
                        @elseif($d->reflection_status === 'rejected')
                            <span class="dsc-badge" style="background:#fee2e2;color:#b91c1c;display:inline-flex;align-items:center;gap:3px;"
                                  title="{{ $d->reflection_note ?: '반영하지 않기로 결정' }}">
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                미반영
                            </span>
                        @else
                            <span style="font-size:11px;color:#9ca3af;">미결정</span>
                        @endif
                    </td>
                    <td style="color:#374151;font-size:12px;">{{ $d->author->name }}</td>
                    <td style="text-align:center;color:#6b7280;">{{ $d->comments_count }}</td>
                    <td style="text-align:center;color:#6b7280;">{{ $d->attachments_count > 0 ? $d->attachments_count : '—' }}</td>
                    <td style="font-size:11px;color:#9ca3af;">{{ $d->updated_at->format('Y-m-d H:i') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- 페이지네이션 --}}
    @if($discussions->hasPages())
    <div class="dsc-pagination">
        @if($discussions->onFirstPage())
            <span class="dsc-page-btn disabled">‹ 이전</span>
        @else
            <a href="{{ $discussions->previousPageUrl() }}" class="dsc-page-btn">‹ 이전</a>
        @endif
        <span class="dsc-page-info">{{ $discussions->currentPage() }} / {{ $discussions->lastPage() }} 페이지</span>
        @if($discussions->hasMorePages())
            <a href="{{ $discussions->nextPageUrl() }}" class="dsc-page-btn">다음 ›</a>
        @else
            <span class="dsc-page-btn disabled">다음 ›</span>
        @endif
    </div>
    @endif
</div>

{{-- ════ 등록/수정 모달 ════ --}}
<div id="dsc-create-modal" class="dsc-modal-bg" onclick="if(event.target===this)dscCloseCreate()">
    <div class="dsc-modal">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;gap:10px;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                <h3 style="margin:0;font-size:15px;font-weight:700;color:#18181b;white-space:nowrap;">새 논의 등록</h3>
                <span style="display:inline-flex;align-items:center;gap:5px;padding:3px 10px;background:#eef2ff;color:#4f46e5;border-radius:20px;font-size:11px;font-weight:700;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:360px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"/></svg>
                    {{ $project->name }}
                </span>
            </div>
            <button onclick="dscCloseCreate()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:22px;line-height:1;padding:2px 4px;flex-shrink:0;">×</button>
        </div>
        <div style="padding:20px 22px;overflow-y:auto;flex:1;">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="display:grid;grid-template-columns:1fr 180px;gap:10px;align-items:end;">
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">제목 <span style="color:#ef4444;">*</span></label>
                        <input id="dsc-title" type="text" class="dsc-form-input" placeholder="논의 제목" maxlength="255">
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">논의 일자</label>
                        <input id="dsc-date" type="date" class="dsc-form-input">
                    </div>
                </div>

                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label style="font-size:12px;font-weight:700;color:#374151;">내용 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(Markdown 지원 · 이미지 붙여넣기 가능)</span></label>
                        <button id="dsc-ai-refine-btn" type="button" onclick="dscAiRefine()" style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;">
                            <svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                            웍스 정제
                        </button>
                    </div>
                    <div class="dsc-md-tabs">
                        <button type="button" id="dsc-body-tab-edit" class="dsc-md-tab active" onclick="dscSwitchBodyTab('edit')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            편집
                        </button>
                        <button type="button" id="dsc-body-tab-view" class="dsc-md-tab" onclick="dscSwitchBodyTab('view')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            보기
                        </button>
                    </div>
                    <div class="dsc-md-wrap">
                        <textarea id="dsc-editor" placeholder="Markdown 지원 · 이미지는 Ctrl+V로 붙여넣기"></textarea>
                    </div>
                </div>

                {{-- 결론 영역 --}}
                <div>
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <label style="font-size:12px;font-weight:700;color:#374151;">결론 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(선택 · Markdown 지원 · 이미지 붙여넣기 가능)</span></label>
                    </div>
                    <div class="dsc-md-tabs">
                        <button type="button" id="dsc-concl-tab-edit" class="dsc-md-tab active" onclick="dscSwitchConclTab('edit')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            편집
                        </button>
                        <button type="button" id="dsc-concl-tab-view" class="dsc-md-tab" onclick="dscSwitchConclTab('view')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            보기
                        </button>
                    </div>
                    <div class="dsc-md-wrap">
                        <textarea id="dsc-conclusion" placeholder="결론·합의사항·결정 내용을 작성하세요. (선택)"></textarea>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">파일 첨부 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(여러 개 가능 · 각 50MB)</span></label>
                    <input id="dsc-files" type="file" multiple class="dsc-file-input" onchange="dscUpdateFileLabel(this,'dsc-files-label')">
                    <label for="dsc-files" class="dsc-file-pick">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        파일 선택
                        <span id="dsc-files-label" class="dsc-file-count"></span>
                    </label>
                </div>

                <div>
                    <label style="display:block;font-size:12px;font-weight:700;color:#374151;margin-bottom:6px;">의견 공유 <span style="font-weight:500;color:#9ca3af;font-size:11px;">(프로젝트 구성원만 표시 · 선택한 사용자에게 이메일·SMS 알림)</span></label>
                    <div style="max-height:180px;overflow-y:auto;border:1.5px solid #e5e7eb;border-radius:9px;padding:8px 10px;display:flex;flex-direction:column;gap:5px;">
                        @forelse($shareableUsers as $u)
                        <label style="display:flex;align-items:center;gap:8px;padding:5px;cursor:pointer;border-radius:6px;transition:background .1s;" onmouseover="this.style.background='#faf5ff'" onmouseout="this.style.background=''">
                            <input type="checkbox" name="dsc-participants" value="{{ $u->id }}" style="accent-color:#7c3aed;">
                            <span style="font-size:13px;font-weight:600;color:#1f2937;">{{ $u->name }}</span>
                            <span style="font-size:11px;color:#9ca3af;">{{ $u->email }}</span>
                        </label>
                        @empty
                        <div style="text-align:center;font-size:12px;color:#9ca3af;padding:14px 0;">이 프로젝트에 본인 외 다른 구성원이 없습니다.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div style="padding:14px 22px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;background:#fafafa;">
            <button onclick="dscCloseCreate()" type="button" style="padding:9px 18px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
            <button id="dsc-create-submit" onclick="dscSubmit()" type="button" style="padding:9px 22px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">등록</button>
        </div>
    </div>
</div>

{{-- ════ 기획서 선택 다이얼로그 ════ --}}
<div id="dsc-reflect-picker" onclick="if(event.target===this)dscCloseReflectPicker()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(15,12,30,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:520px;max-width:calc(100vw - 48px);max-height:80vh;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                기획서에 반영
            </h3>
            <button type="button" onclick="dscCloseReflectPicker()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>
        <div style="padding:14px 22px;font-size:12.5px;color:#6b7280;border-bottom:1px solid #f3f4f6;background:#fffefb;">
            결정 후에는 변경할 수 없으며, 참여자 전원에게 결과 메일이 발송됩니다.
        </div>
        <div id="dsc-reflect-doc-list" style="padding:14px 22px;overflow-y:auto;flex:1;min-height:0;"></div>
        <div style="padding:12px 22px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="dscCloseReflectPicker()" style="padding:7px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
            <button type="button" id="dsc-reflect-confirm-btn" onclick="dscDoReflect()" style="padding:7px 18px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">반영</button>
        </div>
    </div>
</div>

{{-- ════ 반영하지 않음 사유 입력 다이얼로그 ════ --}}
<div id="dsc-reject-modal" onclick="if(event.target===this)dscCloseRejectModal()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(15,12,30,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
    <div style="background:#fff;width:480px;max-width:calc(100vw - 48px);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;">
        <div style="padding:16px 22px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#b91c1c" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                반영하지 않음
            </h3>
            <button type="button" onclick="dscCloseRejectModal()" style="background:none;border:none;font-size:22px;color:#9ca3af;cursor:pointer;line-height:1;padding:0;">×</button>
        </div>
        <div style="padding:18px 22px;">
            <p style="margin:0 0 10px;font-size:13px;color:#374151;">반영하지 않기로 결정한 사유를 입력해주세요. (필수)</p>
            <textarea id="dsc-reject-note" rows="5" placeholder="예: 다음 분기로 보류 / 별도 진행 예정 / 범위 외 등"
                      style="width:100%;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-family:inherit;line-height:1.55;resize:vertical;box-sizing:border-box;outline:none;"
                      onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
            <p style="margin:10px 0 0;font-size:11.5px;color:#9ca3af;">결정 후에는 변경할 수 없으며, 참여자 전원에게 결과 메일이 발송됩니다.</p>
        </div>
        <div style="padding:12px 22px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="dscCloseRejectModal()" style="padding:7px 16px;background:#fff;color:#374151;border:1px solid #e5e7eb;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
            <button type="button" id="dsc-reject-confirm-btn" onclick="dscDoReject()" style="padding:7px 18px;background:#b91c1c;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">확정</button>
        </div>
    </div>
</div>

{{-- ════ 상세 보기 팝업 ════ --}}
<div id="dsc-detail-modal" class="dsc-modal-bg" onclick="if(event.target===this)dscCloseDetail()">
    <div class="dsc-modal" style="width:1040px;max-width:calc(100vw - 48px);height:88vh;max-height:88vh;">
        <div id="dsc-detail-loading" style="padding:48px 0;text-align:center;color:#9ca3af;font-size:13px;">불러오는 중…</div>
        <div id="dsc-detail-body" style="display:none;flex-direction:column;flex:1;overflow:hidden;"></div>
    </div>
</div>

{{-- ════ 웍스 정제 미리보기 모달 (별도 오버레이) ════ --}}
<div id="dsc-refine-modal" style="display:none;position:fixed;inset:0;z-index:10010;background:rgba(15,12,30,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;" onclick="if(event.target===this)dscCloseRefinePreview()">
    <div style="width:100%;max-width:720px;max-height:84vh;background:#fff;border-radius:14px;border:1.5px solid #ddd6fe;box-shadow:0 20px 60px rgba(0,0,0,.3);display:flex;flex-direction:column;overflow:hidden;">
        <div style="padding:14px 18px;background:linear-gradient(135deg,#faf5ff,#ede9fe);border-bottom:1px solid #ddd6fe;display:flex;align-items:center;justify-content:space-between;gap:6px;flex-shrink:0;">
            <span style="font-size:13px;font-weight:700;color:#5b21b6;display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                웍스 정제 미리보기
            </span>
            <button onclick="dscCloseRefinePreview()" type="button" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:2px 4px;">×</button>
        </div>
        <div style="flex:1;overflow-y:auto;padding:18px;display:flex;flex-direction:column;gap:14px;">
            <div>
                <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;">원본</p>
                <div id="dsc-refine-orig" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:12px 14px;font-size:13px;color:#374151;line-height:1.65;white-space:pre-wrap;word-break:break-word;max-height:220px;overflow-y:auto;"></div>
            </div>
            <div>
                <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#5b21b6;letter-spacing:.04em;text-transform:uppercase;display:flex;align-items:center;gap:5px;">
                    <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                    정제 결과
                </p>
                <div id="dsc-refine-result" style="background:#faf5ff;border:1.5px solid #ddd6fe;border-radius:9px;padding:12px 14px;font-size:13px;color:#1f2937;line-height:1.65;white-space:pre-wrap;word-break:break-word;max-height:320px;overflow-y:auto;"></div>
            </div>
        </div>
        <div style="padding:12px 18px;background:#fafafa;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;flex-shrink:0;">
            <button onclick="dscCloseRefinePreview()" type="button" style="padding:8px 16px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
            <button id="dsc-refine-apply" type="button" style="padding:8px 18px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">적용 (정제본으로 등록)</button>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script>
const DSC_BASE = '{{ url("projects/" . $project->id . "/discussions") }}';
const DSC_CSRF = '{{ csrf_token() }}';
const DSC_PROJECT_ID = {{ $project->id }};
const DSC_MY_ID = {{ auth()->id() }};

const DSC_SHAREABLE = @json($shareableUsers->map(fn($u) => ['id'=>$u->id,'name'=>$u->name,'email'=>$u->email])->values());

let _dscEditingId  = null;
let _dscBodyMDE    = null;   // 등록 모달 본문 EasyMDE 인스턴스
let _dscConclMDE   = null;   // 등록 모달 결론 EasyMDE 인스턴스

function dscEsc(s){ return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function dscUpdateFileLabel(input, labelId) {
    const lbl = document.getElementById(labelId);
    if (!lbl) return;
    const n = input.files?.length || 0;
    lbl.textContent = n > 0 ? `${n}개 선택` : '';
}

function dscOpenCreate() {
    _dscEditingId = null;
    document.getElementById('dsc-create-modal').classList.add('is-open');
    document.getElementById('dsc-title').value = '';
    const dateEl = document.getElementById('dsc-date');
    if (dateEl) dateEl.value = new Date().toISOString().slice(0,10);
    document.getElementById('dsc-files').value = '';
    const lbl1 = document.getElementById('dsc-files-label'); if (lbl1) lbl1.textContent = '';
    document.querySelectorAll('input[name="dsc-participants"]').forEach(c => c.checked = false);

    // 기존 인스턴스 정리 + 매번 새로 마운트 (모달 재오픈 안정)
    if (_dscBodyMDE) {
        try { _dscBodyMDE.toTextArea(); } catch (_) {}
        _dscBodyMDE = null;
    }
    if (_dscConclMDE) {
        try { _dscConclMDE.toTextArea(); } catch (_) {}
        _dscConclMDE = null;
    }
    document.getElementById('dsc-body-tab-edit')?.classList.add('active');
    document.getElementById('dsc-body-tab-view')?.classList.remove('active');
    document.getElementById('dsc-concl-tab-edit')?.classList.add('active');
    document.getElementById('dsc-concl-tab-view')?.classList.remove('active');

    requestAnimationFrame(() => {
        const ta = document.getElementById('dsc-editor');
        if (!ta || typeof EasyMDE === 'undefined') return;

        _dscBodyMDE = new EasyMDE({
            element: ta,
            spellChecker: false,
            forceSync: true,
            status: false,
            minHeight: '360px',
            placeholder: '배경 · 목적 · 핵심 안건 · 다음 단계… Markdown 지원, 이미지 붙여넣기 가능',
            toolbar: [
                'bold','italic','heading','|',
                'quote','unordered-list','ordered-list','|',
                'link','image','table','code','|',
                { name: 'preview',     action: EasyMDE.togglePreview,    className: 'fa fa-eye',     title: '내용 미리보기' },
                { name: 'side-by-side',action: EasyMDE.toggleSideBySide, className: 'fa fa-columns', title: '편집/내용 동시보기' },
                'fullscreen','|','guide',
            ],
            previewClass: ['editor-preview','dsc-md-rendered'],
            previewRender: (plain) => dscRenderMarkdown(plain),
        });

        // 이미지 paste → base64 임베드 + 인라인 위젯
        _dscBodyMDE.codemirror.on('paste', async (cm, ev) => {
            const items = (ev.clipboardData || ev.originalEvent?.clipboardData)?.items || [];
            for (const item of items) {
                if (item.type && item.type.startsWith('image/')) {
                    ev.preventDefault();
                    const file = item.getAsFile();
                    if (!file) continue;
                    await dscUploadBodyImageMDE(_dscBodyMDE, file);
                    return;
                }
            }
        });
        // 본문 변경 시 새 이미지가 있으면 위젯으로 렌더 (디바운스)
        let _bodyImgTimer = null;
        _dscBodyMDE.codemirror.on('change', () => {
            clearTimeout(_bodyImgTimer);
            _bodyImgTimer = setTimeout(() => dscRenderInlineImages(_dscBodyMDE.codemirror), 150);
        });
        dscRenderInlineImages(_dscBodyMDE.codemirror);

        // ── 결론 에디터 마운트 ──
        const tac = document.getElementById('dsc-conclusion');
        if (tac) {
            _dscConclMDE = new EasyMDE({
                element: tac,
                spellChecker: false,
                forceSync: true,
                status: false,
                minHeight: '280px',
                placeholder: '결론·합의사항·결정 내용… Markdown 지원, 이미지 붙여넣기 가능',
                toolbar: [
                    'bold','italic','heading','|',
                    'quote','unordered-list','ordered-list','|',
                    'link','image','table','code','|',
                    { name: 'preview',     action: EasyMDE.togglePreview,    className: 'fa fa-eye',     title: '내용 미리보기' },
                    { name: 'side-by-side',action: EasyMDE.toggleSideBySide, className: 'fa fa-columns', title: '편집/내용 동시보기' },
                    'fullscreen','|','guide',
                ],
                previewClass: ['editor-preview','dsc-md-rendered'],
                previewRender: (plain) => dscRenderMarkdown(plain),
            });
            _dscConclMDE.codemirror.on('paste', async (cm, ev) => {
                const items = (ev.clipboardData || ev.originalEvent?.clipboardData)?.items || [];
                for (const item of items) {
                    if (item.type && item.type.startsWith('image/')) {
                        ev.preventDefault();
                        const file = item.getAsFile();
                        if (!file) continue;
                        await dscUploadBodyImageMDE(_dscConclMDE, file);
                        return;
                    }
                }
            });
            let _conclImgTimer = null;
            _dscConclMDE.codemirror.on('change', () => {
                clearTimeout(_conclImgTimer);
                _conclImgTimer = setTimeout(() => dscRenderInlineImages(_dscConclMDE.codemirror), 150);
            });
        }
    });
}

function dscCloseCreate() {
    document.getElementById('dsc-create-modal').classList.remove('is-open');
    if (_dscBodyMDE) {
        try { _dscBodyMDE.toTextArea(); } catch (_) {}
        _dscBodyMDE = null;
    }
    if (_dscConclMDE) {
        try { _dscConclMDE.toTextArea(); } catch (_) {}
        _dscConclMDE = null;
    }
}

function dscSwitchConclTab(mode) {
    dscSwitchTabForMDE(_dscConclMDE, 'dsc-concl-tab-edit', 'dsc-concl-tab-view', mode);
}

/**
 * paste된 이미지를 base64 data URL로 변환 → Markdown에 직접 임베드.
 * 임베드 후 dscRenderInlineImages 로 CodeMirror 안에서 실제 이미지 위젯으로 표시.
 */
function dscEmbedImageBase64(mde, file) {
    return new Promise((resolve) => {
        const cm = mde.codemirror;
        cm.replaceSelection(`\n![embedding...](pending)\n`);
        const reader = new FileReader();
        reader.onload = (e) => {
            const dataUrl = e.target.result;
            const cur = mde.value();
            const alt = (file.name || 'image').replace(/[\[\]]/g, '');
            mde.value(cur.replace('![embedding...](pending)', `![${alt}](${dataUrl})`));
            dscRenderInlineImages(cm);
            resolve();
        };
        reader.onerror = () => {
            mde.value(mde.value().replace('![embedding...](pending)', `[이미지 임베드 실패]`));
            resolve();
        };
        reader.readAsDataURL(file);
    });
}

/**
 * CodeMirror 안에서 `![alt](data:... or http://...)` 패턴을 실제 <img> 위젯으로 치환.
 * 사용자는 편집기 안에서 곧바로 이미지를 본다. (소스 보기는 markdown source mode와 동일.)
 */
function dscRenderInlineImages(cm) {
    if (!cm) return;
    const re = /!\[([^\]]*)\]\((data:image\/[^)]+|https?:\/\/[^\s)]+)\)/g;
    const last = cm.lineCount();
    for (let line = 0; line < last; line++) {
        const text = cm.getLine(line) || '';
        let m;
        re.lastIndex = 0;
        while ((m = re.exec(text)) !== null) {
            const from = { line, ch: m.index };
            const to   = { line, ch: m.index + m[0].length };
            // 이미 마크된 영역인지 검사
            const existing = cm.findMarks(from, to);
            if (existing.length > 0) continue;

            const wrap = document.createElement('span');
            wrap.style.cssText = 'display:inline-block;vertical-align:middle;margin:2px 0;';
            const img = document.createElement('img');
            img.src = m[2];
            img.alt = m[1] || 'image';
            img.title = '이미지를 삭제하려면 클릭 후 Delete 키';
            img.style.cssText = 'max-width:280px;max-height:200px;border-radius:6px;border:1px solid #ddd6fe;box-shadow:0 1px 4px rgba(0,0,0,.08);cursor:pointer;display:block;';
            wrap.appendChild(img);

            const marker = cm.markText(from, to, {
                replacedWith: wrap,
                clearOnEnter: false,
                atomic: true,    // 한 글자 단위로 삭제·이동되도록
                handleMouseEvents: false,
            });

            // 클릭하면 위젯 영역을 선택 상태로 만들어 Delete 키로 즉시 삭제 가능
            img.addEventListener('click', (e) => {
                e.stopPropagation();
                const range = marker.find();
                if (range) {
                    cm.focus();
                    cm.setSelection(range.from, range.to);
                }
            });
        }
    }
}

/* 기존 업로드 방식 (수동 첨부 등 다른 경로에서 재사용 가능 — 호환 보존) */
async function dscUploadBodyImageMDE(mde, file) {
    return dscEmbedImageBase64(mde, file);
}

async function dscSubmit() {
    const title = document.getElementById('dsc-title').value.trim();
    if (!title) { document.getElementById('dsc-title').focus(); return; }
    const content = _dscBodyMDE ? _dscBodyMDE.value() : (document.getElementById('dsc-editor')?.value || '');
    const btn = document.getElementById('dsc-create-submit');
    btn.disabled = true; btn.textContent = '등록 중…';

    const fd = new FormData();
    fd.append('title', title);
    fd.append('content', content);
    const conclusion = _dscConclMDE ? _dscConclMDE.value() : (document.getElementById('dsc-conclusion')?.value || '');
    if (conclusion) fd.append('conclusion', conclusion);
    const dateVal = document.getElementById('dsc-date')?.value;
    if (dateVal) fd.append('discussion_date', dateVal);
    document.querySelectorAll('input[name="dsc-participants"]:checked').forEach(c => fd.append('participant_ids[]', c.value));
    const files = document.getElementById('dsc-files').files;
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);

    try {
        const r = await fetch(DSC_BASE, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '등록 실패');
        location.reload();
    } catch (e) {
        alert('등록 실패: ' + e.message);
        btn.disabled = false; btn.textContent = '등록';
    }
}

async function dscAiRefine() {
    if (!_dscBodyMDE) return;
    const content = _dscBodyMDE.value();
    if (!content.trim()) { alert('정제할 내용을 먼저 입력하세요.'); return; }
    const btn = document.getElementById('dsc-ai-refine-btn');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '✨ 정제 중…';
    try {
        const r = await fetch(`${DSC_BASE}/refine`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ title: document.getElementById('dsc-title').value.trim(), content }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '정제 실패');
        _dscBodyMDE.value(d.refined);
        dscSwitchBodyTab('edit');
    } catch (e) {
        alert('웍스 정제 실패: ' + e.message);
    }
    btn.disabled = false; btn.innerHTML = orig;
}

/* 필터 상태 보관 */
let _dscFilterStatus = 'all';
let _dscFilterDate   = 'all';   // all|today|week|month|range
let _dscRangeFrom    = '';
let _dscRangeTo      = '';

function dscFilter(status, btnEl) {
    _dscFilterStatus = status;
    document.querySelectorAll('.dsc-status-filter-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    dscApplyFilters();
}

function dscFilterDate(mode, btnEl) {
    _dscFilterDate = mode;
    document.querySelectorAll('.dsc-date-filter-btn').forEach(b => b.classList.remove('active'));
    btnEl.classList.add('active');
    // 사전 정의 모드 선택 시 range 입력 초기화
    document.getElementById('dsc-date-from').value = '';
    document.getElementById('dsc-date-to').value   = '';
    _dscRangeFrom = ''; _dscRangeTo = '';
    dscApplyFilters();
}

function dscClearDateRange() {
    document.getElementById('dsc-date-from').value = '';
    document.getElementById('dsc-date-to').value   = '';
    _dscRangeFrom = ''; _dscRangeTo = '';
    // 전체로 복원
    _dscFilterDate = 'all';
    document.querySelectorAll('.dsc-date-filter-btn').forEach(b => b.classList.toggle('active', b.dataset.date === 'all'));
    dscApplyFilters();
}

function dscApplyFilters() {
    const from = document.getElementById('dsc-date-from')?.value || '';
    const to   = document.getElementById('dsc-date-to')?.value   || '';
    _dscRangeFrom = from; _dscRangeTo = to;
    if (from || to) {
        _dscFilterDate = 'range';
        document.querySelectorAll('.dsc-date-filter-btn').forEach(b => b.classList.remove('active'));
    }
    const today = new Date();
    const y = today.getFullYear(), m = today.getMonth();
    const fmt = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
    let lo = '', hi = '';
    if (_dscFilterDate === 'today') {
        lo = hi = fmt(today);
    } else if (_dscFilterDate === 'week') {
        const day = today.getDay(); // 0=Sun..6=Sat
        const monOff = (day === 0) ? -6 : 1 - day;
        const mon = new Date(today); mon.setDate(today.getDate() + monOff);
        const sun = new Date(mon);   sun.setDate(mon.getDate() + 6);
        lo = fmt(mon); hi = fmt(sun);
    } else if (_dscFilterDate === 'month') {
        lo = fmt(new Date(y, m, 1));
        hi = fmt(new Date(y, m + 1, 0));
    } else if (_dscFilterDate === 'range') {
        lo = from || ''; hi = to || '';
    }

    const items = document.querySelectorAll('#dsc-list .dsc-card, #dsc-table-wrap .dsc-row');
    items.forEach(el => {
        let show = true;
        if (_dscFilterStatus !== 'all' && el.dataset.status !== _dscFilterStatus) show = false;
        if (show && (lo || hi)) {
            const date = el.dataset.date || '';
            if (!date) { show = false; }
            else {
                if (lo && date < lo) show = false;
                if (hi && date > hi) show = false;
            }
        }
        if (el.classList.contains('dsc-row')) {
            el.style.display = show ? '' : 'none';
        } else {
            el.style.display = show ? 'block' : 'none';
        }
    });
}

/* 뷰 전환 (카드/테이블) - localStorage 보존 */
function dscSwitchView(mode) {
    const card  = document.getElementById('dsc-list');
    const table = document.getElementById('dsc-table-wrap');
    const cBtn  = document.getElementById('dsc-view-card');
    const tBtn  = document.getElementById('dsc-view-table');
    if (!card || !table) return;
    if (mode === 'table') {
        card.style.display = 'none';
        table.style.display = 'block';
        cBtn.classList.remove('active');
        tBtn.classList.add('active');
    } else {
        card.style.display = 'flex';
        table.style.display = 'none';
        cBtn.classList.add('active');
        tBtn.classList.remove('active');
    }
    try { localStorage.setItem('dsc.viewMode', mode); } catch(_) {}
}
(function dscInitView() {
    let saved = 'card';
    try { saved = localStorage.getItem('dsc.viewMode') || 'card'; } catch(_) {}
    if (saved === 'table') dscSwitchView('table');
})();

/* ════════ 상세 팝업 ════════ */
let _dscDetail = null;
function dscOpenDetail(id) {
    document.getElementById('dsc-detail-modal').classList.add('is-open');
    document.getElementById('dsc-detail-loading').style.display = 'block';
    document.getElementById('dsc-detail-body').style.display = 'none';
    dscLoadDetail(id);
}
function dscCloseDetail() {
    dscDestroyCommentEditor();
    if (_dscDetailBodyMDE) {
        try { _dscDetailBodyMDE.toTextArea(); } catch (_) {}
        _dscDetailBodyMDE = null;
    }
    if (_dscDetailConclMDE) {
        try { _dscDetailConclMDE.toTextArea(); } catch (_) {}
        _dscDetailConclMDE = null;
    }
    document.getElementById('dsc-detail-modal').classList.remove('is-open');
    _dscDetail = null;
}
async function dscLoadDetail(id) {
    try {
        const r = await fetch(`${DSC_BASE}/${id}`, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF } });
        const d = await r.json();
        _dscDetail = d;
        dscRenderDetail(d);
    } catch (e) {
        document.getElementById('dsc-detail-loading').textContent = '불러오기 실패';
    }
}

function dscRenderDetail(d) {
    const loading = document.getElementById('dsc-detail-loading');
    const body    = document.getElementById('dsc-detail-body');
    loading.style.display = 'none';
    body.style.display = 'flex';

    const partList = (d.participants || []).map(p => `<span class="dsc-chip">${dscEsc(p.name)}</span>`).join('');
    const partOptions = DSC_SHAREABLE.map(u => {
        const checked = (d.participants || []).some(p => p.id === u.id) ? 'checked' : '';
        return `<label style="display:flex;align-items:center;gap:8px;padding:5px;border-radius:6px;cursor:pointer;">
            <input type="checkbox" name="dsc-detail-participants" value="${u.id}" ${checked} style="accent-color:#7c3aed;">
            <span style="font-size:13px;font-weight:600;color:#1f2937;">${dscEsc(u.name)}</span>
            <span style="font-size:11px;color:#9ca3af;">${dscEsc(u.email)}</span>
        </label>`;
    }).join('');

    const atts = (d.attachments || []).map(a => `
        <a href="${a.download_url}" target="_blank" class="dsc-att-pill" title="${dscEsc(a.name)}">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span class="dsc-att-name">${dscEsc(a.name)}</span>
            <span style="color:#9ca3af;font-size:10px;">${a.formatted_size}</span>
        </a>`).join('');

    const comments = (d.comments || []).map(c => dscCommentHtml(c)).join('');

    const cmtCount = (d.comments || []).length;
    const attCount = (d.attachments || []).length;

    body.innerHTML = `
        <!-- ═══ 컴팩트 헤더 ═══ -->
        <div style="padding:14px 22px 0;border-bottom:1px solid #f3f4f6;flex-shrink:0;background:#fff;">
            <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:8px;">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                        <span class="dsc-badge dsc-status-pill-${d.status}">${dscEsc(d.status_label)}</span>
                        ${d.can_edit
                            ? `<label class="dsc-date-edit" title="논의 일정 수정">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <input type="date" id="dsc-detail-date" value="${d.discussion_date || ''}" onchange="dscUpdateDate(${d.id}, this.value)" />
                                    ${!d.discussion_date ? '<span class="dsc-date-edit-hint">일자 지정</span>' : ''}
                               </label>`
                            : (d.discussion_date
                                ? `<span style="display:inline-flex;align-items:center;gap:3px;font-size:11px;font-weight:600;color:#4f46e5;background:#eef2ff;padding:2px 8px;border-radius:10px;"><svg width="10" height="10" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${dscEsc(d.discussion_date)}</span>`
                                : '')}
                        <span style="font-size:11px;color:#9ca3af;">${dscEsc(d.author.name)} · ${dscEsc(d.created_at)}</span>
                    </div>
                    <h3 style="margin:0;font-size:17px;font-weight:700;color:#18181b;word-break:break-word;">${dscEsc(d.title)}</h3>
                </div>
                <div style="display:flex;gap:6px;flex-shrink:0;align-items:center;">
                    <button type="button" onclick="dscDownloadWord(${d.id}, this)" title="Word 다운로드"
                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;background:#fff;border:1.5px solid #bfdbfe;color:#1d4ed8;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Word 다운로드
                    </button>
                    ${d.can_delete ? `<button onclick="dscDeleteDiscussion(${d.id})" title="진행 전 상태에서만 삭제 가능" style="padding:6px 10px;background:#fff;border:1.5px solid #fecaca;color:#dc2626;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">삭제</button>` : ''}
                    <button onclick="dscCloseDetail()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:22px;line-height:1;padding:2px 4px;">×</button>
                </div>
            </div>

            <!-- 상태 토글 + 공유 정보 컴팩트 -->
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding-bottom:10px;">
                <div style="display:inline-flex;gap:0;border:1.5px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff;">
                    <button class="dsc-status-btn-seg ${d.status==='open'?'active':''}"        onclick="dscChangeStatus(${d.id},'open')">진행 전</button>
                    <button class="dsc-status-btn-seg ${d.status==='in_progress'?'active':''}" onclick="dscChangeStatus(${d.id},'in_progress')">진행 중</button>
                    <button class="dsc-status-btn-seg ${d.status==='resolved'?'active':''}"    onclick="dscChangeStatus(${d.id},'resolved')">완료</button>
                </div>
                <div style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:#6b7280;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    공유 ${(d.participants || []).length}명
                    <button onclick="dscToggleShareEdit()" style="font-size:10px;font-weight:600;color:#7c3aed;background:#faf5ff;border:1px solid #ddd6fe;border-radius:5px;cursor:pointer;padding:2px 7px;margin-left:2px;">관리</button>
                </div>
            </div>

            <!-- 공유 추가 패널 (토글) -->
            <div id="dsc-share-edit" style="display:none;margin-bottom:10px;background:#fff;border:1px solid #ede9fe;border-radius:8px;padding:8px 10px;">
                <div style="margin-bottom:5px;font-size:11px;font-weight:700;color:#5b21b6;">공유 대상</div>
                <div style="max-height:140px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;">
                    ${partList || '<span style="font-size:12px;color:#9ca3af;">공유된 사용자 없음</span>'}
                </div>
                <div style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;">
                    <div style="margin-bottom:5px;font-size:11px;font-weight:700;color:#6b7280;">추가</div>
                    <div style="max-height:140px;overflow-y:auto;display:flex;flex-direction:column;gap:4px;">
                        ${partOptions || '<div style="text-align:center;font-size:12px;color:#9ca3af;padding:10px 0;">공유 가능한 동료가 없습니다.</div>'}
                    </div>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                    <button onclick="dscToggleShareEdit()" type="button" style="padding:5px 12px;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">취소</button>
                    <button onclick="dscSaveShare(${d.id})" type="button" style="padding:5px 12px;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">저장 & 알림</button>
                </div>
            </div>

            <!-- 메인 탭 -->
            <div style="display:flex;align-items:flex-end;justify-content:space-between;gap:10px;flex-wrap:wrap;border-bottom:1.5px solid #e5e7eb;margin:0 -22px;padding:0 22px;">
                <div style="display:flex;gap:0;">
                    <button type="button" id="dsc-tab-main-content"    class="dsc-main-tab active"  onclick="dscSwitchMainTab('content')">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        내용 ${attCount ? `<span class="dsc-tab-pill">📎 ${attCount}</span>` : ''}
                    </button>
                    <button type="button" id="dsc-tab-main-comments"   class="dsc-main-tab"         onclick="dscSwitchMainTab('comments')">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        의견 <span id="dsc-comments-count" class="dsc-tab-pill">${cmtCount}</span>
                    </button>
                    <button type="button" id="dsc-tab-main-conclusion" class="dsc-main-tab"         onclick="dscSwitchMainTab('conclusion')">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        결론
                    </button>
                </div>
            </div>
        </div>

        <!-- ═══ 탭 영역: 내용 ═══ -->
        <div id="dsc-pane-content" class="dsc-main-pane" style="flex:1;overflow-y:auto;padding:14px 22px 18px;background:#fff;">
            ${d.can_edit ? `
            <div class="dsc-md-tabs" style="margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                <div style="display:flex;">
                    <button type="button" id="dsc-detail-tab-view" class="dsc-md-tab active" onclick="dscSwitchDetailBodyTab('view',${d.id})">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        보기
                    </button>
                    <button type="button" id="dsc-detail-tab-edit" class="dsc-md-tab" onclick="dscSwitchDetailBodyTab('edit',${d.id})">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        편집
                    </button>
                </div>
                <div id="dsc-detail-edit-actions" style="display:none;align-items:center;gap:5px;padding-bottom:3px;">
                    <button type="button" onclick="dscAiRefineDetailBody(${d.id})" id="dsc-detail-refine-btn" style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">
                        <svg width="10" height="10" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                        웍스 정제
                    </button>
                    <button type="button" onclick="dscCancelBodyEdit()" style="padding:5px 11px;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:11px;font-weight:600;cursor:pointer;">취소</button>
                    <button type="button" onclick="dscSaveBodyEdit(${d.id})" id="dsc-body-save-btn" style="padding:5px 14px;background:linear-gradient(135deg,#059669,#10b981);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">저장</button>
                </div>
            </div>` : ''}
            <div id="dsc-detail-body-view" class="dsc-md-rendered">${d.content ? dscRenderMarkdown(d.content) : '<p style="color:#9ca3af;">(내용 없음)</p>'}</div>
            ${d.can_edit ? `
            <div id="dsc-detail-body-edit" style="display:none;">
                <div class="dsc-md-wrap">
                    <textarea id="dsc-detail-body-editor" placeholder="Markdown 지원 · 이미지는 Ctrl+V로 붙여넣기"></textarea>
                </div>
            </div>` : ''}
            ${atts ? `
            <div style="margin-top:18px;padding-top:14px;border-top:1px solid #f3f4f6;">
                <div style="font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;text-transform:uppercase;margin-bottom:8px;display:inline-flex;align-items:center;gap:5px;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    첨부 파일 (${attCount})
                </div>
                <div id="dsc-attachments-row" style="display:flex;flex-wrap:wrap;gap:6px;">${atts}</div>
            </div>` : ''}
        </div>

        <!-- ═══ 탭 영역: 결론 ═══ -->
        <div id="dsc-pane-conclusion" class="dsc-main-pane" style="display:none;flex:1;overflow-y:auto;padding:14px 22px 18px;background:#fffefb;">
            <div class="dsc-md-tabs" style="margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                ${d.can_edit ? `
                <button type="button" id="dsc-concldet-tab-view" class="dsc-md-tab active" onclick="dscSwitchDetailConclTab('view',${d.id})">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    보기
                </button>
                <button type="button" id="dsc-concldet-tab-edit" class="dsc-md-tab" onclick="dscSwitchDetailConclTab('edit',${d.id})">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    편집
                </button>` : ''}
                <div id="dsc-reflect-actions" style="margin-left:auto;display:flex;gap:6px;align-items:center;">
                    ${dscReflectActionsHtml(d)}
                </div>
            </div>
            <div id="dsc-detail-concl-view" class="dsc-md-rendered">${d.conclusion ? dscRenderMarkdown(d.conclusion) : '<p style="color:#9ca3af;font-size:13px;">(결론이 아직 작성되지 않았습니다)</p>'}</div>
            ${d.can_edit ? `
            <div id="dsc-detail-concl-edit" style="display:none;margin-top:8px;">
                <div class="dsc-md-wrap">
                    <textarea id="dsc-detail-concl-editor" placeholder="결론·합의사항·결정 내용을 작성하세요. Markdown 지원"></textarea>
                </div>
                <div style="display:flex;justify-content:flex-end;gap:6px;margin-top:8px;">
                    <button type="button" onclick="dscCancelConclEdit()" style="padding:6px 14px;background:#f3f4f6;color:#374151;border:none;border-radius:7px;font-size:12px;font-weight:600;cursor:pointer;">취소</button>
                    <button type="button" onclick="dscSaveConclEdit(${d.id})" id="dsc-concl-save-btn" style="padding:6px 16px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">저장</button>
                </div>
            </div>` : ''}
        </div>

        <!-- ═══ 탭 영역: 의견 ═══ -->
        <div id="dsc-pane-comments" class="dsc-main-pane" style="display:none;flex:1;min-height:0;flex-direction:column;background:#fff;">
            <!-- 의견 sub-tabs (리스트/작성) + 요약 버튼 -->
            <div style="padding:8px 22px 0;border-bottom:1px solid #f3f4f6;flex-shrink:0;background:#fafafa;">
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px;">
                    <div style="display:flex;gap:0;">
                        <button type="button" id="dsc-cmtarea-tab-list" class="dsc-md-tab active" onclick="dscSwitchCommentArea('list')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            의견 (${cmtCount})
                        </button>
                        <button type="button" id="dsc-cmtarea-tab-write" class="dsc-md-tab" onclick="dscSwitchCommentArea('write')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            작성
                        </button>
                    </div>
                    <button type="button" id="dsc-summarize-btn" onclick="dscSummarizeComments(${d.id})" title="의견 전체를 AI로 요약"
                        style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:11px;font-weight:700;cursor:pointer;margin-bottom:3px;">
                        <svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                        의견 요약
                    </button>
                </div>
            </div>

            <!-- 의견 리스트 -->
            <div id="dsc-cmtarea-list" style="flex:1;min-height:140px;overflow-y:auto;padding:14px 22px;display:flex;flex-direction:column;gap:10px;">
                <div id="dsc-summary-box" style="${d.comments_summary ? '' : 'display:none;'}background:linear-gradient(135deg,#faf5ff,#ede9fe);border:1.5px solid #c4b5fd;border-radius:10px;padding:12px 14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:6px;">
                        <span style="font-size:11px;font-weight:700;color:#5b21b6;letter-spacing:.04em;text-transform:uppercase;display:inline-flex;align-items:center;gap:4px;">
                            <svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                            AI 의견 요약
                        </span>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span id="dsc-summary-meta" style="font-size:10px;color:#7c3aed;font-weight:600;">${d.comments_summary_at ? `${dscEsc(d.comments_summary_at)} · ${d.comments_summary_count || 0}건 기준` : ''}</span>
                            <button onclick="dscHideSummary()" type="button" title="접기" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:14px;line-height:1;padding:0 4px;">×</button>
                        </div>
                    </div>
                    <div id="dsc-summary-content" class="dsc-md-rendered" style="font-size:12.5px;line-height:1.6;">${d.comments_summary ? dscRenderMarkdown(d.comments_summary) : ''}</div>
                </div>
                <div id="dsc-comments-list" style="display:flex;flex-direction:column;gap:10px;">
                    ${comments || '<div style="font-size:13px;color:#9ca3af;text-align:center;padding:18px 0;">아직 의견이 없습니다.</div>'}
                </div>
            </div>

            <!-- 의견 작성 (sub-tab) -->
            <div id="dsc-cmtarea-write" style="display:none;flex:1;min-height:140px;overflow-y:auto;padding:12px 22px 14px;background:#fafafa;">
                <div class="dsc-md-tabs">
                    <button type="button" id="dsc-cmt-tab-edit" class="dsc-md-tab active" onclick="dscSwitchCommentTab('edit')">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        편집
                    </button>
                    <button type="button" id="dsc-cmt-tab-view" class="dsc-md-tab" onclick="dscSwitchCommentTab('view')">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        보기
                    </button>
                </div>
                <div class="dsc-md-wrap">
                    <textarea id="dsc-comment-input" placeholder="의견을 입력하세요. **Markdown** 지원, 이미지 붙여넣기(Ctrl+V) 가능"></textarea>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:7px;gap:6px;flex-wrap:wrap;">
                    <div>
                        <input id="dsc-comment-files" type="file" multiple class="dsc-file-input" onchange="dscUpdateFileLabel(this,'dsc-comment-files-label')">
                        <label for="dsc-comment-files" class="dsc-file-pick">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            파일 첨부
                            <span id="dsc-comment-files-label" class="dsc-file-count"></span>
                        </label>
                    </div>
                    <div style="display:flex;gap:6px;">
                        <button id="dsc-comment-refine" onclick="dscRefineComment(${d.id})" type="button" style="display:inline-flex;align-items:center;gap:5px;padding:7px 12px;background:#fff;color:#7c3aed;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                            <svg width="11" height="11" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L9.91 8.91L3 11l6.91 2.09L12 20l2.09-6.91L21 11l-6.91-2.09L12 2z"/></svg>
                            의견 정제하기
                        </button>
                        <button onclick="dscSubmitComment(${d.id})" id="dsc-comment-submit" style="padding:7px 18px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">의견 등록</button>
                    </div>
                </div>
            </div>
        </div>`;

    // 기본 메인 탭: 내용
    dscSwitchMainTab('content');
}

// 메인 탭 전환 (내용 / 의견 / 결론)
function dscSwitchMainTab(name) {
    ['content','comments','conclusion'].forEach(k => {
        const tab = document.getElementById('dsc-tab-main-' + k);
        const pane = document.getElementById('dsc-pane-' + k);
        if (!tab || !pane) return;
        const active = (k === name);
        tab.classList.toggle('active', active);
        pane.style.display = active ? (k === 'comments' ? 'flex' : 'block') : 'none';
    });
    if (name === 'comments') {
        requestAnimationFrame(dscScrollCommentsToBottom);
    }
}

// 헤더 메뉴 토글
function dscToggleHeaderMenu(ev) {
    if (ev) ev.stopPropagation();
    const m = document.getElementById('dsc-header-menu');
    if (!m) return;
    m.style.display = m.style.display === 'none' ? 'block' : 'none';
    if (m.style.display === 'block') {
        const close = (e) => { if (!m.contains(e.target)) { m.style.display = 'none'; document.removeEventListener('mousedown', close, true); } };
        setTimeout(() => document.addEventListener('mousedown', close, true), 0);
    }
}

let _dscEasyMDE = null;

function dscInitCommentEditor(discussionId) {
    dscDestroyCommentEditor();
    const ta = document.getElementById('dsc-comment-input');
    if (!ta || typeof EasyMDE === 'undefined') return;

    _dscEasyMDE = new EasyMDE({
        element: ta,
        spellChecker: false,
        autoDownloadFontAwesome: true,
        forceSync: true,
        status: false,
        minHeight: '240px',
        placeholder: '의견을 입력하세요. **Markdown** 지원, 이미지 붙여넣기(Ctrl+V) 가능',
        toolbar: [
            'bold', 'italic', 'heading', '|',
            'quote', 'unordered-list', 'ordered-list', '|',
            'link', 'image', 'table', 'code', '|',
            { name: 'preview', action: EasyMDE.togglePreview, className: 'fa fa-eye', title: '내용 미리보기' },
            { name: 'side-by-side', action: EasyMDE.toggleSideBySide, className: 'fa fa-columns', title: '편집/내용 동시보기' },
            'fullscreen', '|', 'guide',
        ],
        previewClass: ['editor-preview', 'dsc-md-rendered'],
        previewRender: (plain) => dscRenderMarkdown(plain),
    });

    // 이미지 paste → 즉시 업로드 → Markdown 삽입
    const cm = _dscEasyMDE.codemirror;
    cm.on('paste', async (cm, event) => {
        const items = (event.clipboardData || event.originalEvent?.clipboardData)?.items || [];
        for (const item of items) {
            if (item.type && item.type.startsWith('image/')) {
                event.preventDefault();
                const file = item.getAsFile();
                if (!file) continue;
                await dscUploadInlineImage(discussionId, file);
                return;
            }
        }
    });

    // Ctrl+Enter 단축키 → 등록
    cm.setOption('extraKeys', {
        'Ctrl-Enter': () => dscSubmitComment(discussionId),
        'Cmd-Enter':  () => dscSubmitComment(discussionId),
    });

    // 이미지 인라인 위젯 (CodeMirror 안에서 실제 <img> 표시)
    let _cmtImgTimer = null;
    cm.on('change', () => {
        clearTimeout(_cmtImgTimer);
        _cmtImgTimer = setTimeout(() => dscRenderInlineImages(cm), 150);
    });
    dscRenderInlineImages(cm);
}

function dscDestroyCommentEditor() {
    if (_dscEasyMDE) {
        try { _dscEasyMDE.toTextArea(); } catch (_) {}
        _dscEasyMDE = null;
    }
}

async function dscUploadInlineImage(discussionId, file) {
    // 의견 본문에도 base64 임베드 — 이미지 자체가 의견에 포함됨
    return dscEmbedImageBase64(_dscEasyMDE, file);
}

/* ════════ 편집/보기 탭 전환 ════════ */
function dscSwitchTabForMDE(mde, editBtnId, viewBtnId, mode) {
    if (!mde) return;
    const editBtn = document.getElementById(editBtnId);
    const viewBtn = document.getElementById(viewBtnId);
    if (!editBtn || !viewBtn) return;

    const isPreviewOn = mde.isPreviewActive();
    if (mode === 'view' && !isPreviewOn)      EasyMDE.togglePreview(mde);
    else if (mode === 'edit' && isPreviewOn)  EasyMDE.togglePreview(mde);

    editBtn.classList.toggle('active', mode === 'edit');
    viewBtn.classList.toggle('active', mode === 'view');
}
function dscSwitchBodyTab(mode) {
    dscSwitchTabForMDE(_dscBodyMDE, 'dsc-body-tab-edit', 'dsc-body-tab-view', mode);
}
function dscSwitchCommentTab(mode) {
    dscSwitchTabForMDE(_dscEasyMDE, 'dsc-cmt-tab-edit', 'dsc-cmt-tab-view', mode);
}

/* 의견 요약 — AI(Claude → OpenAI 폴백)로 전체 의견 요약 + 자동 저장 */
async function dscSummarizeComments(discussionId) {
    if (!_dscDetail || !_dscDetail.id) return;
    const cmtCount = (_dscDetail.comments || []).length;
    if (cmtCount === 0) { alert('요약할 의견이 없습니다.'); return; }

    const btn = document.getElementById('dsc-summarize-btn');
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity=".25"/><path d="M12 2a10 10 0 0110 10"/></svg> 요약 중…';
    try {
        const r = await fetch(`${DSC_BASE}/${discussionId}/comments/summarize`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/json' },
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '요약 실패');

        // 캐시 갱신
        if (_dscDetail) {
            _dscDetail.comments_summary       = d.summary;
            _dscDetail.comments_summary_at    = d.summary_at;
            _dscDetail.comments_summary_count = d.summary_count;
        }

        // UI 갱신
        const box     = document.getElementById('dsc-summary-box');
        const content = document.getElementById('dsc-summary-content');
        const meta    = document.getElementById('dsc-summary-meta');
        if (content) content.innerHTML = dscRenderMarkdown(d.summary);
        if (meta)    meta.textContent  = `${d.summary_at} · ${d.summary_count}건 기준`;
        if (box)     box.style.display = 'block';

        // 의견 리스트 탭이면 요약이 보이도록 자동 스크롤
        dscSwitchCommentArea('list');
        const listArea = document.getElementById('dsc-cmtarea-list');
        if (listArea) listArea.scrollTop = 0;
    } catch (e) {
        alert('요약 실패: ' + e.message);
    }
    btn.disabled = false;
    btn.innerHTML = orig;
}

function dscHideSummary() {
    const box = document.getElementById('dsc-summary-box');
    if (box) box.style.display = 'none';
}

/* 의견 리스트/작성 영역 탭 전환 */
function dscSwitchCommentArea(mode) {
    const listArea = document.getElementById('dsc-cmtarea-list');
    const writeArea = document.getElementById('dsc-cmtarea-write');
    const listTab  = document.getElementById('dsc-cmtarea-tab-list');
    const writeTab = document.getElementById('dsc-cmtarea-tab-write');
    if (!listArea || !writeArea) return;

    if (mode === 'write') {
        listArea.style.display  = 'none';
        writeArea.style.display = 'flex';
        writeArea.style.flexDirection = 'column';
        listTab?.classList.remove('active');
        writeTab?.classList.add('active');
        // 작성 탭으로 처음 들어올 때 EasyMDE 마운트
        if (!_dscEasyMDE && _dscDetail) {
            dscInitCommentEditor(_dscDetail.id);
        }
        requestAnimationFrame(() => _dscEasyMDE?.codemirror?.focus());
    } else {
        writeArea.style.display = 'none';
        listArea.style.display  = 'flex';
        listArea.style.flexDirection = 'column';
        listTab?.classList.add('active');
        writeTab?.classList.remove('active');
        dscScrollCommentsToBottom();
    }
}

/* 상세 팝업 본문 편집/보기 — EasyMDE 리치 에디터 + 이미지 paste */
let _dscDetailBodyMDE = null;

function dscSwitchDetailBodyTab(mode, discussionId) {
    const viewArea = document.getElementById('dsc-detail-body-view');
    const editArea = document.getElementById('dsc-detail-body-edit');
    const viewBtn  = document.getElementById('dsc-detail-tab-view');
    const editBtn  = document.getElementById('dsc-detail-tab-edit');
    const actions  = document.getElementById('dsc-detail-edit-actions');
    const attRow   = document.getElementById('dsc-attachments-row');
    if (!viewArea || !editArea) return;

    if (mode === 'edit') {
        viewArea.style.display = 'none';
        editArea.style.display = 'block';
        if (attRow) attRow.style.display = 'none';
        if (actions) actions.style.display = 'flex';
        viewBtn?.classList.remove('active');
        editBtn?.classList.add('active');
        dscInitDetailBodyEditor();
    } else {
        // 편집 중인 내용을 보기 영역 + 메모리에 모두 반영 (저장 전이라도 편집 탭 복귀 시 유지)
        if (_dscDetailBodyMDE) {
            const cur = _dscDetailBodyMDE.value();
            if (_dscDetail) _dscDetail.content = cur;
            viewArea.innerHTML = cur ? dscRenderMarkdown(cur) : '<p style="color:#9ca3af;">(내용 없음)</p>';
            try { _dscDetailBodyMDE.toTextArea(); } catch (_) {}
            _dscDetailBodyMDE = null;
        }
        editArea.style.display = 'none';
        viewArea.style.display = 'block';
        if (attRow) attRow.style.display = 'flex';
        if (actions) actions.style.display = 'none';
        viewBtn?.classList.add('active');
        editBtn?.classList.remove('active');
    }
}

function dscInitDetailBodyEditor() {
    if (_dscDetailBodyMDE) return;
    const ta = document.getElementById('dsc-detail-body-editor');
    if (!ta || typeof EasyMDE === 'undefined') return;
    ta.value = _dscDetail?.content || '';

    _dscDetailBodyMDE = new EasyMDE({
        element: ta,
        spellChecker: false,
        forceSync: true,
        status: false,
        minHeight: '380px',
        placeholder: 'Markdown 지원 · 이미지 붙여넣기 가능',
        toolbar: [
            'bold','italic','heading','|',
            'quote','unordered-list','ordered-list','|',
            'link','image','table','code','|',
            { name: 'preview',     action: EasyMDE.togglePreview,    className: 'fa fa-eye',     title: '내용 미리보기' },
            { name: 'side-by-side',action: EasyMDE.toggleSideBySide, className: 'fa fa-columns', title: '편집/내용 동시보기' },
            'fullscreen','|','guide',
        ],
        previewClass: ['editor-preview','dsc-md-rendered'],
        previewRender: (plain) => dscRenderMarkdown(plain),
    });

    _dscDetailBodyMDE.codemirror.on('paste', async (cm, ev) => {
        const items = (ev.clipboardData || ev.originalEvent?.clipboardData)?.items || [];
        for (const it of items) {
            if (it.type && it.type.startsWith('image/')) {
                ev.preventDefault();
                const file = it.getAsFile();
                if (!file) continue;
                await dscUploadBodyImageMDE(_dscDetailBodyMDE, file);
                return;
            }
        }
    });
    let _detailImgTimer = null;
    _dscDetailBodyMDE.codemirror.on('change', () => {
        clearTimeout(_detailImgTimer);
        _detailImgTimer = setTimeout(() => dscRenderInlineImages(_dscDetailBodyMDE.codemirror), 150);
    });
    dscRenderInlineImages(_dscDetailBodyMDE.codemirror);
}

function dscCancelBodyEdit() {
    dscSwitchDetailBodyTab('view');
}

async function dscAiRefineDetailBody(discussionId) {
    if (!_dscDetailBodyMDE) return;
    const content = _dscDetailBodyMDE.value();
    if (!content.trim()) { alert('정제할 내용을 먼저 입력하세요.'); return; }
    const btn = document.getElementById('dsc-detail-refine-btn');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '✨ 정제 중…';
    try {
        const r = await fetch(`${DSC_BASE}/refine`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ title: _dscDetail?.title || '', content }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '정제 실패');
        _dscDetailBodyMDE.value(d.refined);
    } catch (e) {
        alert('웍스 정제 실패: ' + e.message);
    }
    btn.disabled = false; btn.innerHTML = orig;
}

/* ── 논의사항 → 기획서 반영 / 반영하지 않음 ────────────── */
let _dscReflectCurrentId = null;

function dscReflectActionsHtml(d) {
    const r = d.reflection;
    if (r && r.status === 'reflected') {
        const tooltip = `반영 기획서: ${(r.planning_doc?.title || '')} · ${r.decided_by?.name || ''} · ${r.decided_at || ''}`;
        const href = r.planning_doc?.url || '#';
        return `<a href="${href}" target="_blank" title="${dscEscHtml(tooltip)}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;border-radius:7px;font-size:11.5px;font-weight:700;text-decoration:none;cursor:pointer;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12l5 5L20 7"/></svg>
                    기획서에 반영됨
                </a>`;
    }
    if (r && r.status === 'rejected') {
        const tooltip = `사유: ${r.note || '(없음)'} · ${r.decided_by?.name || ''} · ${r.decided_at || ''}`;
        return `<span title="${dscEscHtml(tooltip)}"
                   style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#fee2e2;border:1px solid #fecaca;color:#b91c1c;border-radius:7px;font-size:11.5px;font-weight:700;cursor:help;">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    반영하지 않음
                </span>`;
    }
    const hasConclusion = !!(d.conclusion && d.conclusion.trim());
    const titleHint = hasConclusion ? '' : '결론을 먼저 작성하세요';
    return `
        <button type="button" onclick="dscTryReflect(${d.id})" title="${titleHint || '기획서에 반영'}"
                style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:linear-gradient(135deg,#d97706,#f59e0b);color:#fff;border:none;border-radius:7px;font-size:11.5px;font-weight:700;cursor:pointer;${hasConclusion ? '' : 'opacity:.55;'}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            기획서에 반영
        </button>
        <button type="button" onclick="dscTryReject(${d.id})" title="${titleHint || '반영하지 않음'}"
                style="display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#fff;color:#6b7280;border:1.5px solid #e5e7eb;border-radius:7px;font-size:11.5px;font-weight:700;cursor:pointer;${hasConclusion ? '' : 'opacity:.55;'}">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            반영하지 않음
        </button>
    `;
}

function dscRefreshReflectActions() {
    const box = document.getElementById('dsc-reflect-actions');
    if (box && _dscDetail) box.innerHTML = dscReflectActionsHtml(_dscDetail);
}

function dscTryReflect(discussionId) {
    const conclusion = (_dscDetail?.conclusion || '').trim();
    if (!conclusion) { alert('결론을 먼저 작성한 후 반영해주세요.'); return; }
    dscOpenReflectPicker(discussionId);
}
function dscTryReject(discussionId) {
    const conclusion = (_dscDetail?.conclusion || '').trim();
    if (!conclusion) { alert('결론을 먼저 작성한 후 결정해주세요.'); return; }
    dscOpenRejectModal(discussionId);
}

function dscEscHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function dscOpenReflectPicker(discussionId) {
    _dscReflectCurrentId = discussionId;
    try {
        const url = `${DSC_BASE}/${discussionId}/reflect-targets`;
        const r = await fetch(url, { headers: {'Accept':'application/json'} });
        const text = await r.text();
        let data = {};
        try { data = JSON.parse(text); } catch (_) {
            console.error('[reflectTargets] 비 JSON 응답', r.status, text.slice(0, 600));
            alert(`서버 오류 (${r.status})\n${text.slice(0, 200)}\n자세한 내용은 콘솔 확인`);
            return;
        }
        if (!r.ok) {
            console.error('[reflectTargets] HTTP error', r.status, data);
            alert(data.message || `기획서 목록 조회 실패 (HTTP ${r.status})`);
            return;
        }
        if (!data.docs || data.docs.length === 0) {
            alert('기획서가 없습니다. 먼저 프로젝트 기획서를 생성해주세요.');
            return;
        }
        const list = document.getElementById('dsc-reflect-doc-list');
        list.innerHTML = data.docs.map(doc => `
            <label style="display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;margin-bottom:6px;transition:border-color .12s;"
                   onmouseover="this.style.borderColor='#a78bfa'" onmouseout="this.style.borderColor='#e5e7eb'">
                <input type="radio" name="reflect-doc" value="${doc.id}" style="margin:0;">
                <div style="flex:1;min-width:0;">
                    <div style="font-size:13px;font-weight:600;color:#1f2937;">${dscEscHtml(doc.title)}</div>
                    <div style="font-size:11px;color:#9ca3af;margin-top:2px;">상태: ${dscEscHtml(doc.status)} · 수정: ${doc.updated_at || ''}</div>
                </div>
            </label>
        `).join('');
        if (data.docs.length === 1) list.querySelector('input[type=radio]').checked = true;
        document.getElementById('dsc-reflect-picker').style.display = 'flex';
    } catch (e) {
        console.error('[reflectTargets] 예외', e);
        alert('네트워크/스크립트 오류: ' + (e?.message || e));
    }
}

function dscCloseReflectPicker() {
    document.getElementById('dsc-reflect-picker').style.display = 'none';
    _dscReflectCurrentId = null;
}

async function dscDoReflect() {
    const did = _dscReflectCurrentId;
    const chosen = document.querySelector('#dsc-reflect-doc-list input[name=reflect-doc]:checked');
    if (!did || !chosen) { alert('반영할 기획서를 선택하세요.'); return; }
    const btn = document.getElementById('dsc-reflect-confirm-btn');
    btn.disabled = true; btn.textContent = '반영 중...';
    try {
        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('planning_doc_id', chosen.value);
        const r = await fetch(`${DSC_BASE}/${did}/reflect-to-planning`, {
            method:'POST',
            headers:{'Accept':'application/json'},
            body: fd,
        });
        const data = await r.json();
        if (!r.ok) { alert(data.message || '반영 실패'); btn.disabled = false; btn.textContent = '반영'; return; }
        dscCloseReflectPicker();
        alert(data.message + '\n참여자에게 결과 메일이 발송되었습니다.');
        dscOpenDetail(did); // 상세 다시 로드해서 상태 갱신
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
        btn.disabled = false; btn.textContent = '반영';
    }
}

function dscOpenRejectModal(discussionId) {
    _dscReflectCurrentId = discussionId;
    document.getElementById('dsc-reject-note').value = '';
    document.getElementById('dsc-reject-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('dsc-reject-note').focus(), 50);
}

function dscCloseRejectModal() {
    document.getElementById('dsc-reject-modal').style.display = 'none';
    _dscReflectCurrentId = null;
}

async function dscDoReject() {
    const did = _dscReflectCurrentId;
    if (!did) return;
    const note = document.getElementById('dsc-reject-note').value.trim();
    if (!note) { alert('결정 사유를 입력하세요.'); return; }
    const btn = document.getElementById('dsc-reject-confirm-btn');
    btn.disabled = true; btn.textContent = '처리 중...';
    try {
        const fd = new FormData();
        fd.append('_token', '{{ csrf_token() }}');
        fd.append('note', note);
        const r = await fetch(`${DSC_BASE}/${did}/reject-reflection`, {
            method:'POST',
            headers:{'Accept':'application/json'},
            body: fd,
        });
        const data = await r.json();
        if (!r.ok) { alert(data.message || '처리 실패'); btn.disabled = false; btn.textContent = '확정'; return; }
        dscCloseRejectModal();
        alert(data.message + '\n참여자에게 결과 메일이 발송되었습니다.');
        dscOpenDetail(did);
    } catch (e) {
        alert('네트워크 오류가 발생했습니다.');
        btn.disabled = false; btn.textContent = '확정';
    }
}

/* 상세 팝업 — 결론 편집/보기 */
let _dscDetailConclMDE = null;

function dscSwitchDetailConclTab(mode, discussionId) {
    const viewArea = document.getElementById('dsc-detail-concl-view');
    const editArea = document.getElementById('dsc-detail-concl-edit');
    const viewBtn  = document.getElementById('dsc-concldet-tab-view');
    const editBtn  = document.getElementById('dsc-concldet-tab-edit');
    if (!viewArea || !editArea) return;

    if (mode === 'edit') {
        viewArea.style.display = 'none';
        editArea.style.display = 'block';
        viewBtn?.classList.remove('active');
        editBtn?.classList.add('active');
        dscInitDetailConclEditor();
    } else {
        // 편집 중인 내용을 보기 영역 + 메모리에 모두 반영 (저장 전이라도 편집 탭 복귀 시 유지)
        if (_dscDetailConclMDE) {
            const cur = _dscDetailConclMDE.value();
            if (_dscDetail) _dscDetail.conclusion = cur;
            viewArea.innerHTML = cur ? dscRenderMarkdown(cur) : '<p style="color:#9ca3af;font-size:13px;">(결론이 아직 작성되지 않았습니다)</p>';
            try { _dscDetailConclMDE.toTextArea(); } catch (_) {}
            _dscDetailConclMDE = null;
        }
        editArea.style.display = 'none';
        viewArea.style.display = 'block';
        viewBtn?.classList.add('active');
        editBtn?.classList.remove('active');
    }
}

function dscInitDetailConclEditor() {
    if (_dscDetailConclMDE) return;
    const ta = document.getElementById('dsc-detail-concl-editor');
    if (!ta || typeof EasyMDE === 'undefined') return;
    ta.value = _dscDetail?.conclusion || '';

    _dscDetailConclMDE = new EasyMDE({
        element: ta,
        spellChecker: false,
        forceSync: true,
        status: false,
        minHeight: '320px',
        placeholder: '결론·합의사항·결정 내용… Markdown 지원, 이미지 붙여넣기 가능',
        toolbar: [
            'bold','italic','heading','|',
            'quote','unordered-list','ordered-list','|',
            'link','image','table','code','|',
            { name: 'preview',     action: EasyMDE.togglePreview,    className: 'fa fa-eye',     title: '내용 미리보기' },
            { name: 'side-by-side',action: EasyMDE.toggleSideBySide, className: 'fa fa-columns', title: '편집/내용 동시보기' },
            'fullscreen','|','guide',
        ],
        previewClass: ['editor-preview','dsc-md-rendered'],
        previewRender: (plain) => dscRenderMarkdown(plain),
    });

    _dscDetailConclMDE.codemirror.on('paste', async (cm, ev) => {
        const items = (ev.clipboardData || ev.originalEvent?.clipboardData)?.items || [];
        for (const it of items) {
            if (it.type && it.type.startsWith('image/')) {
                ev.preventDefault();
                const file = it.getAsFile();
                if (!file) continue;
                await dscUploadBodyImageMDE(_dscDetailConclMDE, file);
                return;
            }
        }
    });
    let _conclImgT = null;
    _dscDetailConclMDE.codemirror.on('change', () => {
        clearTimeout(_conclImgT);
        _conclImgT = setTimeout(() => dscRenderInlineImages(_dscDetailConclMDE.codemirror), 150);
    });
    dscRenderInlineImages(_dscDetailConclMDE.codemirror);
}

function dscCancelConclEdit() {
    dscSwitchDetailConclTab('view');
}

async function dscSaveConclEdit(discussionId) {
    if (!_dscDetailConclMDE) return;
    const conclusion = _dscDetailConclMDE.value();
    const btn = document.getElementById('dsc-concl-save-btn');
    btn.disabled = true; const orig = btn.textContent; btn.textContent = '저장 중…';
    try {
        const r = await fetch(`${DSC_BASE}/${discussionId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ conclusion }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '저장 실패');
        if (_dscDetail) _dscDetail.conclusion = conclusion;
        const viewArea = document.getElementById('dsc-detail-concl-view');
        if (viewArea) viewArea.innerHTML = conclusion ? dscRenderMarkdown(conclusion) : '<p style="color:#9ca3af;font-size:13px;">(결론이 아직 작성되지 않았습니다)</p>';
        dscRefreshReflectActions();
        dscSwitchDetailConclTab('view');
    } catch (e) {
        alert('저장 실패: ' + e.message);
    }
    btn.disabled = false; btn.textContent = orig;
}

async function dscSaveBodyEdit(discussionId) {
    if (!_dscDetailBodyMDE) return;
    const content = _dscDetailBodyMDE.value();
    const btn = document.getElementById('dsc-body-save-btn');
    btn.disabled = true; const orig = btn.textContent; btn.textContent = '저장 중…';
    try {
        const r = await fetch(`${DSC_BASE}/${discussionId}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ content }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '저장 실패');
        if (_dscDetail) _dscDetail.content = content;
        const viewArea = document.getElementById('dsc-detail-body-view');
        if (viewArea) viewArea.innerHTML = content ? dscRenderMarkdown(content) : '<p style="color:#9ca3af;">(내용 없음)</p>';
        dscSwitchDetailBodyTab('view');
    } catch (e) {
        alert('저장 실패: ' + e.message);
    }
    btn.disabled = false; btn.textContent = orig;
}

function dscRenderMarkdown(src) {
    if (typeof marked === 'undefined') return src;
    try {
        marked.setOptions({ breaks: true, gfm: true });
        const html = marked.parse(src || '');
        return (typeof DOMPurify !== 'undefined') ? DOMPurify.sanitize(html) : html;
    } catch (_) {
        return src;
    }
}

function dscScrollCommentsToBottom() {
    const list = document.getElementById('dsc-comments-list');
    if (list) list.scrollTop = list.scrollHeight;
}

function dscCommentHtml(c) {
    const atts = (c.attachments || []).map(a => `
        <a href="${a.download_url}" target="_blank" class="dsc-att-pill" title="${dscEsc(a.name)}">
            <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span class="dsc-att-name">${dscEsc(a.name)}</span>
        </a>`).join('');
    const shareActive = !!c.share_token;
    return `<div class="dsc-comment" id="dsc-cmt-${c.id}" data-share-token="${c.share_token || ''}" data-share-url="${dscEsc(c.share_url || '')}">
        <div class="dsc-comment-meta">
            <span class="dsc-comment-name">${dscEsc(c.user_name)}</span>
            <span class="dsc-comment-time">${dscEsc(c.created_at)}</span>
            <div style="margin-left:auto;display:flex;align-items:center;gap:4px;">
                <button onclick="dscToggleCommentShare(event, ${c.id}, this)" class="dsc-cmt-share-btn ${shareActive ? 'is-active' : ''}" title="${shareActive ? '공유 중 — 클릭하면 링크 보기/해제' : '의견 공유 링크 생성'}"
                        style="background:${shareActive ? '#ede9fe' : 'none'};border:none;cursor:pointer;color:${shareActive ? '#7c3aed' : '#9ca3af'};font-size:11px;font-weight:600;padding:3px 7px;border-radius:5px;display:inline-flex;align-items:center;gap:3px;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    ${shareActive ? '공유중' : '공유'}
                </button>
                ${c.can_delete ? `<button onclick="dscDeleteComment(${c.id})" style="background:none;border:none;color:#d1d5db;cursor:pointer;font-size:14px;line-height:1;padding:0 4px;">×</button>` : ''}
            </div>
        </div>
        <div class="dsc-md-rendered">${dscRenderMarkdown(c.content || '')}</div>
        ${atts ? `<div style="margin-top:7px;display:flex;flex-wrap:wrap;gap:5px;">${atts}</div>` : ''}
    </div>`;
}

function dscToggleShareEdit() {
    const el = document.getElementById('dsc-share-edit');
    el.style.display = (el.style.display === 'none' || !el.style.display) ? 'block' : 'none';
}

async function dscSaveShare(id) {
    const ids = Array.from(document.querySelectorAll('input[name="dsc-detail-participants"]:checked')).map(c => +c.value);
    if (!ids.length) { alert('공유할 사용자를 선택하세요.'); return; }
    try {
        const r = await fetch(`${DSC_BASE}/${id}/share`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ participant_ids: ids }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '실패');
        alert(`${d.notified}명에게 알림을 전송했습니다.`);
        dscLoadDetail(id);
    } catch (e) {
        alert('실패: ' + e.message);
    }
}

async function dscUpdateDate(id, dateStr) {
    try {
        const r = await fetch(`${DSC_BASE}/${id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ discussion_date: dateStr || null }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error('실패');
        // 캐시·목록 카드 동기화
        if (_dscDetail) _dscDetail.discussion_date = dateStr || null;
        const card = document.querySelector(`.dsc-card[onclick*="dscOpenDetail(${id})"]`);
        if (card) {
            card.dataset.date = dateStr || '';
            const dateBox = card.querySelector('.dsc-card-date');
            if (dateBox) {
                if (dateStr) {
                    const dt = new Date(dateStr + 'T00:00:00');
                    const months = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
                    dateBox.classList.remove('is-empty');
                    dateBox.title = '논의 일정';
                    dateBox.querySelector('.dcd-month').textContent = months[dt.getMonth()];
                    dateBox.querySelector('.dcd-day').textContent   = String(dt.getDate()).padStart(2,'0');
                    dateBox.querySelector('.dcd-year').textContent  = dt.getFullYear();
                } else {
                    dateBox.classList.add('is-empty');
                    dateBox.title = '논의 일정 미지정';
                    dateBox.querySelector('.dcd-month').textContent = '미정';
                    dateBox.querySelector('.dcd-day').textContent   = '–';
                    dateBox.querySelector('.dcd-year').innerHTML    = '&nbsp;';
                }
            }
        }
        // 힌트 라벨 제거
        document.querySelector('.dsc-date-edit-hint')?.remove();
    } catch (e) {
        alert('일정 저장 실패');
    }
}

async function dscChangeStatus(id, status) {
    const r = await fetch(`${DSC_BASE}/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
        body: JSON.stringify({ status }),
    });
    const d = await r.json();
    if (!d.ok) { alert('상태 변경 실패'); return; }
    dscLoadDetail(id);
    // 목록 카드의 status도 업데이트
    const card = document.querySelector(`.dsc-card[onclick*="dscOpenDetail(${id})"]`);
    if (card) card.dataset.status = status;
}

async function dscDeleteDiscussion(id) {
    if (!confirm('논의를 삭제하시겠습니까?')) return;
    const r = await fetch(`${DSC_BASE}/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
    });
    if (r.ok) { dscCloseDetail(); location.reload(); return; }
    let msg = '삭제 실패';
    try {
        const d = await r.json();
        if (d?.message) msg = d.message;
    } catch (_) {}
    alert(msg);
}

async function dscSubmitComment(id) {
    const content = (_dscEasyMDE ? _dscEasyMDE.value() : document.getElementById('dsc-comment-input')?.value || '').trim();
    if (!content) { _dscEasyMDE?.codemirror?.focus(); return; }
    const btn = document.getElementById('dsc-comment-submit');
    btn.disabled = true; btn.textContent = '등록 중…';

    const fd = new FormData();
    fd.append('content', content);
    const files = document.getElementById('dsc-comment-files').files;
    for (let i = 0; i < files.length; i++) fd.append('files[]', files[i]);

    try {
        const r = await fetch(`${DSC_BASE}/${id}/comments`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/json' },
            body: fd,
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '등록 실패');
        if (_dscEasyMDE) _dscEasyMDE.value('');
        const cf = document.getElementById('dsc-comment-files');
        if (cf) cf.value = '';
        const cfLbl = document.getElementById('dsc-comment-files-label');
        if (cfLbl) cfLbl.textContent = '';
        dscCloseRefinePreview();
        // 등록 후 자동으로 의견 리스트 탭으로 전환
        dscSwitchCommentArea('list');
        const list = document.getElementById('dsc-comments-list');
        const empty = list.querySelector('div[style*="아직 의견"]');
        if (empty) empty.remove();
        list.insertAdjacentHTML('beforeend', dscCommentHtml(d.comment));
        // 의견 카운트 + 자동 스크롤 (의견 영역만)
        const cnt = document.getElementById('dsc-comments-count');
        if (cnt) {
            const m = cnt.textContent.match(/\((\d+)\)/);
            const next = (m ? +m[1] : 0) + 1;
            cnt.textContent = `(${next})`;
        }
        list.scrollTo({ top: list.scrollHeight, behavior: 'smooth' });
    } catch (e) {
        alert('등록 실패: ' + e.message);
    }
    btn.disabled = false; btn.textContent = '의견 등록';
}

/* ════════ 의견 정제 ════════ */
async function dscRefineComment(id) {
    const original = (_dscEasyMDE ? _dscEasyMDE.value() : '').trim();
    if (!original) { _dscEasyMDE?.codemirror?.focus(); alert('정제할 의견을 먼저 입력하세요.'); return; }
    const btn = document.getElementById('dsc-comment-refine');
    const orig = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '✨ 정제 중…';
    try {
        const r = await fetch(`${DSC_BASE}/${id}/comments/refine`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
            body: JSON.stringify({ content: original }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '정제 실패');
        document.getElementById('dsc-refine-orig').textContent   = original;
        document.getElementById('dsc-refine-result').textContent = d.refined;
        const applyBtn = document.getElementById('dsc-refine-apply');
        applyBtn.onclick = () => dscApplyRefine(id);
        document.getElementById('dsc-refine-modal').style.display = 'flex';
    } catch (e) {
        alert('웍스 정제 실패: ' + e.message);
    }
    btn.disabled = false; btn.innerHTML = orig;
}

function dscCloseRefinePreview() {
    const el = document.getElementById('dsc-refine-modal');
    if (el) el.style.display = 'none';
}

async function dscApplyRefine(id) {
    const refined = document.getElementById('dsc-refine-result')?.textContent?.trim();
    if (!refined) return;
    if (_dscEasyMDE) _dscEasyMDE.value(refined);
    else { const ta = document.getElementById('dsc-comment-input'); if (ta) ta.value = refined; }
    dscCloseRefinePreview();
    await dscSubmitComment(id);
}

/* ════════ Word 다운로드 (화면 변화 없이) ════════ */
async function dscDownloadWord(id, btn) {
    const orig = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin .8s linear infinite;"><circle cx="12" cy="12" r="10" stroke-opacity=".25"/><path d="M12 2a10 10 0 0110 10"/></svg> 생성중…';
    try {
        const r = await fetch(`${DSC_BASE}/${id}/download-word`, {
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' },
        });
        if (!r.ok) {
            const err = await r.text();
            throw new Error('서버 오류 (' + r.status + ')');
        }
        const blob = await r.blob();

        // 파일명 추출
        let filename = 'discussion.docx';
        const cd = r.headers.get('Content-Disposition') || '';
        const m1 = cd.match(/filename\*=UTF-8''([^;]+)/i);
        const m2 = cd.match(/filename="?([^";]+)"?/i);
        if (m1) filename = decodeURIComponent(m1[1]);
        else if (m2) filename = m2[1];

        // 숨김 anchor 트리거 — 페이지 이동·새 탭 없음
        const url = URL.createObjectURL(blob);
        const a   = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        a.remove();
        setTimeout(() => URL.revokeObjectURL(url), 4000);
    } catch (e) {
        alert('Word 다운로드 실패: ' + e.message);
    }
    btn.disabled = false;
    btn.innerHTML = orig;
}

/* ════════ 의견 공유 링크 ════════ */
async function dscToggleCommentShare(ev, commentId, btn) {
    ev.preventDefault(); ev.stopPropagation();
    const did = _dscDetail?.id;
    if (!did) return;

    const card = btn.closest('.dsc-comment');
    const wasActive = card?.dataset.shareToken && card.dataset.shareToken.length > 0;

    // 이미 활성: 팝오버로 링크 보기 + 해제 옵션
    if (wasActive) {
        const url = card.dataset.shareUrl || `${location.origin}/share/discussion-comment/${card.dataset.shareToken}`;
        dscShowSharePopover(btn, url, commentId);
        return;
    }

    // 비활성: 토큰 발급 후 팝오버 표시
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '생성중…';
    try {
        const r = await fetch(`${DSC_BASE}/${did}/comments/${commentId}/toggle-share`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/json' },
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '실패');
        if (d.active && d.url) {
            card.dataset.shareToken = d.token;
            card.dataset.shareUrl   = d.url;
            btn.classList.add('is-active');
            btn.style.background = '#ede9fe';
            btn.style.color = '#7c3aed';
            btn.title = '공유 중 — 클릭하면 링크 보기/해제';
            btn.innerHTML = `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg> 공유중`;
            dscShowSharePopover(btn, d.url, commentId);
        }
    } catch (e) {
        alert('공유 링크 생성 실패: ' + e.message);
        btn.innerHTML = origHtml;
    }
    btn.disabled = false;
}

function dscShowSharePopover(anchor, url, commentId) {
    document.querySelectorAll('.dsc-share-pop').forEach(p => p.remove());

    const pop = document.createElement('div');
    pop.className = 'dsc-share-pop';
    pop.style.cssText = 'position:fixed;z-index:10100;background:#fff;border:1.5px solid #ddd6fe;border-radius:10px;padding:10px 12px;box-shadow:0 8px 28px rgba(0,0,0,.18);min-width:300px;max-width:380px;font-family:inherit;';
    pop.innerHTML = `
        <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:8px;">
            <span style="font-size:11px;font-weight:700;color:#5b21b6;letter-spacing:.04em;text-transform:uppercase;display:flex;align-items:center;gap:4px;">
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                공유 링크
            </span>
            <button onclick="this.closest('.dsc-share-pop').remove()" type="button" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:16px;line-height:1;padding:0 4px;">×</button>
        </div>
        <div style="display:flex;gap:5px;margin-bottom:7px;">
            <input id="dsc-share-url" type="text" readonly value="${url}" style="flex:1;min-width:0;padding:6px 9px;border:1.5px solid #e5e7eb;border-radius:6px;font-size:11px;color:#374151;background:#fafafa;outline:none;">
            <button onclick="dscCopyShareUrl(this)" type="button" style="padding:6px 10px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;white-space:nowrap;">복사</button>
        </div>
        <div style="display:flex;gap:5px;">
            <a href="${url}" target="_blank" style="flex:1;text-align:center;padding:6px 10px;background:#fff;border:1.5px solid #ddd6fe;color:#7c3aed;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;text-decoration:none;">새 탭에서 열기</a>
            <button onclick="dscRevokeShare(${commentId}, this)" type="button" style="flex:1;padding:6px 10px;background:#fff;border:1.5px solid #fecaca;color:#dc2626;border-radius:6px;font-size:11px;font-weight:700;cursor:pointer;">공유 해제</button>
        </div>`;
    document.body.appendChild(pop);

    const r = anchor.getBoundingClientRect();
    requestAnimationFrame(() => {
        const ph = pop.offsetHeight, pw = pop.offsetWidth;
        let top = r.bottom + 6;
        if (top + ph > innerHeight - 8) top = Math.max(8, r.top - ph - 6);
        let left = r.right - pw;
        if (left < 8) left = 8;
        if (left + pw > innerWidth - 8) left = innerWidth - pw - 8;
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
    });

    const outside = (e) => { if (!pop.contains(e.target) && e.target !== anchor) { pop.remove(); document.removeEventListener('mousedown', outside, true); } };
    setTimeout(() => document.addEventListener('mousedown', outside, true), 0);
}

function dscCopyShareUrl(btn) {
    const input = document.getElementById('dsc-share-url');
    if (!input) return;
    const url = input.value;
    const done = () => { const o = btn.textContent; btn.textContent = '✓ 복사됨'; setTimeout(() => btn.textContent = o, 1400); };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done).catch(fallback);
    } else fallback();
    function fallback() { input.select(); try { document.execCommand('copy'); done(); } catch (_) { prompt('복사할 URL', url); } }
}

async function dscRevokeShare(commentId, btn) {
    const did = _dscDetail?.id;
    if (!did) return;
    btn.disabled = true; btn.textContent = '해제 중…';
    try {
        const r = await fetch(`${DSC_BASE}/${did}/comments/${commentId}/toggle-share`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': DSC_CSRF, 'Accept': 'application/json' },
        });
        const d = await r.json();
        if (!d.ok) throw new Error('실패');
        // 카드 상태 업데이트
        const card = document.getElementById(`dsc-cmt-${commentId}`);
        if (card) {
            card.dataset.shareToken = '';
            card.dataset.shareUrl   = '';
            const shareBtn = card.querySelector('.dsc-cmt-share-btn');
            if (shareBtn) {
                shareBtn.classList.remove('is-active');
                shareBtn.style.background = 'none';
                shareBtn.style.color = '#9ca3af';
                shareBtn.title = '의견 공유 링크 생성';
                shareBtn.innerHTML = `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg> 공유`;
            }
        }
        // 팝오버 닫기
        document.querySelectorAll('.dsc-share-pop').forEach(p => p.remove());
    } catch (e) {
        alert('해제 실패');
        btn.disabled = false; btn.textContent = '공유 해제';
    }
}

async function dscDeleteComment(id) {
    if (!confirm('의견을 삭제하시겠습니까?')) return;
    const did = _dscDetail?.id;
    if (!did) return;
    const r = await fetch(`${DSC_BASE}/${did}/comments/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': DSC_CSRF },
    });
    if (r.ok) document.getElementById(`dsc-cmt-${id}`)?.remove();
    else alert('삭제 실패');
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        const refineOpen = document.getElementById('dsc-refine-modal')?.style.display === 'flex';
        if (refineOpen) { dscCloseRefinePreview(); return; }
        if (document.getElementById('dsc-detail-modal').classList.contains('is-open')) dscCloseDetail();
        else if (document.getElementById('dsc-create-modal').classList.contains('is-open')) dscCloseCreate();
    }
});

// ?open=ID 쿼리로 자동 오픈 (이메일에서 진입)
(function autoOpen() {
    const params = new URLSearchParams(location.search);
    const openId = params.get('open');
    if (openId) dscOpenDetail(+openId);
})();
</script>
@endpush
@endsection
