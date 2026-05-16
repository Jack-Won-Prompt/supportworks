@extends(request()->has('popup') ? 'layouts.popup' : 'layouts.app')
@section('title', $doc->title . ' — ' . __('work.planning_title'))

@if(!request()->has('popup'))
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('common.list') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('work.planning_title') }}</span>
@endsection
@endif

@section('header-actions')@endsection

@if(!request()->has('popup'))
@section('page-actions')
@php
    $statusColors = ['draft'=>['#f3f4f6','#6b7280'],'ai_processed'=>['#eff6ff','#3b82f6'],'pending_review'=>['#fef3c7','#d97706'],'approved'=>['#d1fae5','#059669'],'rejected'=>['#fee2e2','#dc2626']];
    [$sbg,$stc] = $statusColors[$doc->status] ?? ['#f3f4f6','#6b7280'];
@endphp
<span style="padding:5px 10px;font-size:12px;font-weight:600;border-radius:20px;background:{{ $sbg }};color:{{ $stc }};">{{ $doc->status_label }}</span>
<span style="padding:5px 8px;font-size:12px;color:#9ca3af;border:1px solid #e4e4e7;border-radius:7px;">v{{ $doc->version }}</span>
@endsection
@endif

@section('content')
@if(!request()->has('popup'))
@include('partials.project-nav', ['project' => $project, 'active' => 'planning'])
@endif
<style>
#plan-wrap { display:flex; gap:20px; height:calc(100vh - 130px); }
#plan-left  { flex:1; display:flex; flex-direction:column; min-width:0; }
#plan-right { width:360px; flex-shrink:0; display:flex; flex-direction:column; }

.plan-card { background:#fff; border:1px solid #e4e4e7; border-radius:12px; overflow:hidden; }

/* Tabs */
.tab-bar { display:flex; border-bottom:1px solid #f4f4f5; padding:0 16px; }
.tab-btn { padding:10px 14px; font-size:13px; font-weight:500; color:#9ca3af; cursor:pointer; border:none; background:none; border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .12s; }
.tab-btn.active { color:var(--tText); border-bottom-color:var(--t500); font-weight:600; }
.tab-panel { display:none; flex:1; overflow-y:auto; }
.tab-panel.active { display:flex; flex-direction:column; }

/* Markdown render */
.md-render { padding:24px 28px; flex:1; font-size:14px; line-height:1.8; color:#1f2937; overflow-y:auto; }
.md-render h1 { font-size:22px; font-weight:800; color:#1e1b4b; margin:0 0 20px; padding-bottom:10px; border-bottom:3px solid #c7d2fe; }
.md-render h2 { font-size:17px; font-weight:700; color:#4338ca; margin:32px 0 12px; padding-bottom:6px; border-bottom:1px solid #e0e7ff; }
.md-render h3 { font-size:15px; font-weight:700; color:#0891b2; margin:20px 0 8px; padding-left:10px; border-left:3px solid #67e8f9; }
.md-render h4 { font-size:14px; font-weight:700; color:#374151; margin:14px 0 6px; }
.md-render p  { margin:0 0 12px; }
.md-render ul { margin:0 0 12px 4px; padding-left:20px; }
.md-render ol { margin:0 0 12px 4px; padding-left:22px; }
.md-render li { margin-bottom:5px; }
.md-render li > ul, .md-render li > ol { margin-top:4px; margin-bottom:4px; }
.md-render hr { border:none; border-top:2px solid #e0e7ff; margin:28px 0; }
.md-render strong { font-weight:700; color:#111827; }
.md-render em { font-style:italic; color:#4b5563; }
.md-render a { color:#4f46e5; text-decoration:underline; word-break:break-all; }
.md-render blockquote { margin:14px 0; padding:12px 16px; border-left:4px solid #6366f1; background:#f5f3ff; border-radius:0 8px 8px 0; color:#4c1d95; }
.md-render blockquote p { margin:0; }
.md-render code { font-family:'Courier New',monospace; font-size:12px; background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#0369a1; border:1px solid #e2e8f0; }
.md-render pre { background:#1e293b; border-radius:10px; padding:16px 18px; overflow-x:auto; margin:14px 0; }
.md-render pre code { background:none; color:#e2e8f0; padding:0; font-size:12.5px; border:none; }
.md-render table { width:100%; border-collapse:collapse; margin:14px 0; font-size:13px; border-radius:8px; overflow:hidden; box-shadow:0 0 0 1px #e2e8f0; }
.md-render table th { background:#eff6ff; padding:9px 14px; text-align:left; font-weight:700; color:#1e40af; border-bottom:2px solid #bfdbfe; border-right:1px solid #dbeafe; }
.md-render table th:last-child { border-right:none; }
.md-render table td { padding:8px 14px; border-bottom:1px solid #f1f5f9; border-right:1px solid #f1f5f9; color:#374151; }
.md-render table td:last-child { border-right:none; }
.md-render table tr:last-child td { border-bottom:none; }
.md-render table tr:nth-child(even) td { background:#f8fafc; }
.md-render table tr:hover td { background:#eff6ff; transition:background .1s; }

/* Editor */
#doc-editor { width:100%; flex:1; border:none; resize:none; font-size:13px; font-family:monospace; line-height:1.7; padding:24px 28px; outline:none; color:#1f2937; box-sizing:border-box; }

/* 웍스 result */
.ai-section { padding:16px; border-bottom:1px solid #f4f4f5; }
.ai-label { font-size:11px; font-weight:700; color:#7c3aed; letter-spacing:.05em; text-transform:uppercase; margin-bottom:8px; display:flex; align-items:center; gap:6px; }
.ai-content { font-size:13px; color:#374151; line-height:1.6; white-space:pre-wrap; }
.ai-conflict { background:#fef3c7; border-radius:6px; padding:8px 12px; font-size:12px; color:#92400e; }
.ai-suggest  { background:#eff6ff; border-radius:6px; padding:8px 12px; font-size:12px; color:#1e40af; }

/* Input form */
.input-type-btn { padding:6px 12px; font-size:12px; font-weight:500; border:1px solid #e4e4e7; border-radius:20px; cursor:pointer; background:#fff; color:#6b7280; transition:all .12s; }
.input-type-btn.active { background:var(--t500); color:#fff; border-color:var(--t500); }

/* Pending input item */
.pi-item { padding:12px 14px; border-bottom:1px solid #f4f4f5; font-size:13px; }
.pi-item:last-child { border-bottom:none; }

/* History item */
.hist-item { padding:12px 16px; border-bottom:1px solid #f4f4f5; }
.hist-item:last-child { border-bottom:none; }

@keyframes spin { to { transform:rotate(360deg); } }

/* 웍스 pending banner */
#ai-pending-banner { background:linear-gradient(135deg,#ede9fe,#dbeafe); border:1px solid #c4b5fd; border-radius:12px; padding:16px 20px; margin-bottom:16px; }

/* 적용 위치 하이라이트 */
@keyframes reqHighlight { 0%,10% { background:#fef9c3; outline:2px solid #f59e0b; outline-offset:4px; border-radius:4px; } 100% { background:transparent; outline:2px solid transparent; } }
.req-highlight { animation:reqHighlight 2.2s ease-out forwards; }
</style>

<div id="plan-wrap">

{{-- ── 왼쪽: 기획서 본문 ── --}}
<div id="plan-left" class="plan-card">
    <div class="tab-bar" style="display:flex;align-items:center;">
        <button class="tab-btn active" onclick="switchTab('view')">{{ __('work.planning_tab_view') }}</button>
        <button class="tab-btn" onclick="switchTab('edit')">{{ __('work.planning_tab_edit') }}</button>
        <button class="tab-btn" onclick="switchTab('aiwrite')" style="color:#7c3aed;">
            {{ __('work.planning_tab_aiwrite') }}
        </button>
        @if($doc->pending_content)
        <button class="tab-btn" onclick="switchTab('ai')" style="color:#d97706;">{{ __('work.planning_tab_ai') }}</button>
        @endif
        <div style="margin-left:auto;display:flex;align-items:center;gap:6px;">
            <button onclick="openWriteModal()"
                style="display:flex;align-items:center;gap:4px;padding:4px 11px;font-size:12px;font-weight:600;color:#4f46e5;border:1px solid #c7d2fe;border-radius:6px;background:#eef2ff;cursor:pointer;white-space:nowrap;"
                onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>{{ __('planning.write_doc') }}
            </button>
            <button onclick="openPlanEmailModal()"
               style="display:flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px;color:#4f46e5;border:1px solid #c7d2fe;border-radius:6px;background:#eef2ff;cursor:pointer;white-space:nowrap;"
               onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>{{ __('planning.send_email') }}
            </button>
            <button id="btn-plan-share" onclick="openShareModal()"
               style="display:flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px;font-weight:600;border:1.5px solid {{ $doc->share_token ? '#059669' : '#d1d5db' }};border-radius:6px;background:{{ $doc->share_token ? '#d1fae5' : '#fff' }};color:{{ $doc->share_token ? '#065f46' : '#6b7280' }};cursor:pointer;white-space:nowrap;transition:all .15s;"
               data-active="{{ $doc->share_token ? 'true' : 'false' }}"
               data-url="{{ $doc->share_token ? route('planning.public-share', $doc->share_token) : '' }}">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                <span id="btn-plan-share-txt">{{ $doc->share_token ? __('planning.sharing') : __('planning.share_link') }}</span>
            </button>
            <a href="{{ route('projects.planning.download', [$project, $doc]) }}"
               style="display:flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px;color:#52525b;border:1px solid #e4e4e7;border-radius:6px;text-decoration:none;background:#fff;white-space:nowrap;"
               onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>Word
            </a>
            @if(auth()->user()?->isAdmin())
            <button onclick="openResetModal()"
               style="display:flex;align-items:center;gap:4px;padding:4px 10px;font-size:12px;color:#b91c1c;border:1px solid #fca5a5;border-radius:6px;background:#fff;cursor:pointer;white-space:nowrap;"
               onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>{{ __('planning.reset') }}
            </button>
            @endif
        </div>
    </div>

    {{-- 보기 탭 --}}
    <div id="tab-view" class="tab-panel active">
        <div class="md-render" id="md-view">
            @if(!$doc->content)
                <p style="color:#9ca3af;font-style:italic;">{{ __('work.planning_no_content') }}</p>
            @endif
        </div>
    </div>

    {{-- 편집 탭 --}}
    <div id="tab-edit" class="tab-panel" style="flex-direction:column;">
        <div style="padding:8px 16px;background:#f8fafc;border-bottom:1px solid #f4f4f5;display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <span style="font-size:12px;color:#9ca3af;">{{ __('work.planning_md_hint') }}</span>
            <div style="display:flex;align-items:center;gap:6px;">
                <button id="btn-ai-cleanup" onclick="runAiCleanup()"
                    style="display:flex;align-items:center;gap:5px;padding:5px 12px;font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:7px;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    {{ __('work.planning_ai_cleanup') }}
                </button>
                <button id="btn-cleanup-undo" onclick="undoCleanup()"
                    style="display:none;padding:5px 10px;font-size:12px;font-weight:600;color:#9ca3af;background:#fff;border:1px solid #e4e4e7;border-radius:7px;cursor:pointer;">
                    {{ __('work.planning_revert') }}
                </button>
                <button onclick="saveContent()" style="padding:5px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">{{ __('common.save') }}</button>
            </div>
        </div>
        <textarea id="doc-editor" placeholder="# {{ __('work.planning_title') }}&#10;&#10;## 1. ">{{ $doc->content }}</textarea>
    </div>

    {{-- 웍스 작성 탭 --}}
    <div id="tab-aiwrite" class="tab-panel" style="flex-direction:column;overflow-y:auto;">
        {{-- 헤더 --}}
        <div style="padding:14px 20px;background:#f5f3ff;border-bottom:1px solid #ede9fe;">
            <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:2px;display:flex;align-items:center;gap:6px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                {{ __('planning.ai_write_heading') }}
            </div>
            <p style="font-size:12px;color:#7c3aed;margin:4px 0 0;">{{ __('planning.ai_write_desc') }}</p>
            @if($doc->content)
            <div style="margin-top:8px;padding:6px 10px;background:#ede9fe;border-radius:6px;font-size:12px;color:#5b21b6;line-height:1.5;">
                {!! __('planning.ai_write_existing_hint') !!}
            </div>
            @endif
        </div>

        {{-- 작성 폼 --}}
        <div style="padding:16px 20px;border-bottom:1px solid #f4f4f5;">
            <div style="display:flex;gap:8px;margin-bottom:12px;">
                <button id="mode-btn-enhance" onclick="setWriteMode('enhance')"
                    style="padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid {{ $doc->content ? '#7c3aed' : '#e4e4e7' }};background:{{ $doc->content ? '#7c3aed' : '#fff' }};color:{{ $doc->content ? '#fff' : '#6b7280' }};cursor:pointer;">{{ __('planning.mode_enhance') }}</button>
                <button id="mode-btn-new" onclick="setWriteMode('new')"
                    style="padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid {{ $doc->content ? '#e4e4e7' : '#7c3aed' }};background:{{ $doc->content ? '#fff' : '#7c3aed' }};color:{{ $doc->content ? '#6b7280' : '#fff' }};cursor:pointer;">{{ __('planning.mode_new') }}</button>
            </div>

            <textarea id="ai-write-prompt" rows="5"
                placeholder="{{ $doc->content ? __('planning.ai_write_placeholder_enhance') : __('planning.ai_write_placeholder_new') }}"
                style="width:100%;padding:10px 13px;border:1.5px solid #e4e4e7;border-radius:10px;font-size:13px;resize:vertical;box-sizing:border-box;line-height:1.6;font-family:inherit;"
                onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'"></textarea>

            <div style="display:flex;align-items:center;justify-content:flex-end;margin-top:10px;">
                <button id="btn-ai-write" onclick="runAiWrite()"
                    style="display:flex;align-items:center;gap:6px;padding:8px 20px;font-size:13px;font-weight:700;color:#fff;background:linear-gradient(135deg,#7c3aed,#4f46e5);border:none;border-radius:9px;cursor:pointer;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ __('work.planning_ai_write_start') }}
                </button>
            </div>
        </div>

        {{-- 로딩 --}}
        <div id="ai-write-loading" style="display:none;padding:40px 20px;text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:10px;padding:14px 24px;background:#f5f3ff;border-radius:12px;border:1px solid #ddd6fe;">
                <svg style="animation:spin 1s linear infinite;flex-shrink:0;" width="18" height="18" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span style="font-size:13px;font-weight:600;color:#5b21b6;">{{ __('work.planning_ai_writing') }}</span>
            </div>
        </div>

        {{-- 결과 영역 --}}
        <div id="ai-write-result" style="display:none;flex:1;flex-direction:column;">
            <div style="padding:12px 20px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;display:flex;align-items:center;justify-content:space-between;gap:10px;">
                <div>
                    <span style="font-size:13px;font-weight:700;color:#065f46;">{{ __('work.planning_ai_done') }}</span>
                    <span id="ai-write-summary" style="font-size:12px;color:#047857;margin-left:8px;"></span>
                </div>
                <div style="display:flex;gap:8px;flex-shrink:0;">
                    <button onclick="applyToEditor()"
                        style="padding:6px 14px;font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1.5px solid #c4b5fd;border-radius:8px;cursor:pointer;">{{ __('work.planning_apply_editor') }}</button>
                    <button onclick="saveAiWritten()"
                        style="padding:6px 16px;font-size:12px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;">{{ __('work.planning_save_ai') }}</button>
                    <button onclick="resetAiWrite()"
                        style="padding:6px 12px;font-size:12px;color:#9ca3af;background:#fff;border:1px solid #e4e4e7;border-radius:8px;cursor:pointer;">{{ __('work.planning_rewrite') }}</button>
                </div>
            </div>
            <div id="ai-write-preview" class="md-render" style="flex:1;overflow-y:auto;"></div>
        </div>
    </div>

    @if($doc->pending_content)
    {{-- 웍스 통합 결과 탭 --}}
    <div id="tab-ai" class="tab-panel" style="flex-direction:column;">
        <div style="padding:12px 16px;background:#f5f3ff;border-bottom:1px solid #e0e7ff;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <span style="font-size:13px;font-weight:600;color:#7c3aed;">{{ __('work.planning_ai_integrate_tab_label') }}</span>
            <div style="display:flex;gap:8px;flex-shrink:0;">
                <button onclick="approveAi()" style="padding:6px 16px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;">{{ __('work.planning_approve') }}</button>
                <button onclick="rejectAi()" style="padding:6px 14px;font-size:13px;font-weight:600;color:#dc2626;background:#fff;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;">{{ __('work.planning_reject') }}</button>
            </div>
        </div>
        <div class="md-render" id="md-ai" style="flex:1;" data-raw="{{ $doc->pending_content ?? '' }}"></div>
    </div>
    @endif
</div>

{{-- ── 오른쪽 패널 ── --}}
<div id="plan-right" style="display:flex;flex-direction:column;gap:16px;overflow-y:auto;">

    @if($doc->pending_content)
    {{-- 웍스 결과 요약 --}}
    <div id="ai-pending-banner">
        <div style="font-size:13px;font-weight:700;color:#5b21b6;margin-bottom:10px;display:flex;align-items:center;gap:6px;">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            {{ __('work.planning_ai_pending') }}
        </div>
        @if($doc->ai_summary)
        <p style="font-size:13px;color:#4c1d95;line-height:1.6;margin-bottom:10px;">{{ $doc->ai_summary }}</p>
        @endif
        @if($doc->ai_conflicts)
        <div class="ai-conflict" style="margin-bottom:8px;"><strong>{{ __('work.planning_conflict') }}</strong><br>{{ $doc->ai_conflicts }}</div>
        @endif
        @if($doc->ai_suggestions)
        <div class="ai-suggest"><strong>{{ __('work.planning_suggestion') }}</strong><br>{{ $doc->ai_suggestions }}</div>
        @endif
        <div style="display:flex;gap:8px;margin-top:12px;">
            <button onclick="approveAi()" style="flex:1;padding:7px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;">{{ __('work.planning_approve') }}</button>
            <button onclick="rejectAi()" style="flex:1;padding:7px;font-size:13px;font-weight:600;color:#dc2626;background:#fff;border:1px solid #fca5a5;border-radius:8px;cursor:pointer;">{{ __('work.planning_reject') }}</button>
        </div>
    </div>
    @endif

    {{-- 내용 추가 카드 --}}
    <div class="plan-card" style="flex-shrink:0;">
        <div style="padding:14px 16px;border-bottom:1px solid #f4f4f5;font-size:13px;font-weight:700;color:#18181b;">{{ __('work.planning_content_add') }}</div>

        {{-- 입력 유형 선택 --}}
        <div style="padding:12px 16px 0;display:flex;gap:6px;flex-wrap:wrap;">
            <button class="input-type-btn active" onclick="setInputType('text', this)">{{ __('work.planning_input_text') }}</button>
            <button class="input-type-btn" onclick="setInputType('memo', this)">{{ __('work.planning_input_memo') }}</button>
            <button class="input-type-btn" onclick="setInputType('requirement', this)">{{ __('work.planning_input_requirement') }}</button>
            <button class="input-type-btn" onclick="setInputType('file', this)">{{ __('work.planning_input_file') }}</button>
        </div>

        <div style="padding:12px 16px 16px;">
            <textarea id="input-content" rows="4" placeholder="{{ __('work.planning_input_placeholder') }}"
                style="width:100%;padding:9px 12px;border:1px solid #e4e4e7;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            <div id="file-input-wrap" style="display:none;margin-top:8px;">
                <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px dashed #d1d5db;border-radius:8px;cursor:pointer;background:#fafafa;transition:border-color .15s;"
                    onmouseover="this.style.borderColor='var(--t400)'" onmouseout="this.style.borderColor='#d1d5db'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;color:#9ca3af;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <span id="file-label-text" style="font-size:13px;color:#6b7280;">{{ __('work.planning_file_select') }}</span>
                    <input type="file" id="input-file" style="display:none;" onchange="updateFileLabel(this)">
                </label>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;">
                <span id="pending-count-badge" style="font-size:12px;color:#7c3aed;font-weight:500;">
                    @if($pendingInputs->count() > 0) ⏳ {{ __('work.planning_pending_badge', ['count' => $pendingInputs->count()]) }} @endif
                </span>
                <button onclick="submitInput()" style="padding:7px 16px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">{{ __('common.add') }}</button>
            </div>
        </div>

        {{-- 대기 중인 입력 목록 --}}
        @if($pendingInputs->count() > 0)
        <div style="border-top:1px solid #f4f4f5;">
            <div style="padding:10px 16px;display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:12px;font-weight:600;color:#7c3aed;">{{ __('work.planning_pending_list') }}</span>
                <button onclick="runAiIntegrate()" id="btn-ai" style="display:flex;align-items:center;gap:5px;padding:6px 14px;font-size:12px;font-weight:600;color:#fff;background:linear-gradient(135deg,#7c3aed,#4f46e5);border:none;border-radius:8px;cursor:pointer;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    {{ __('work.planning_ai_integrate') }}
                </button>
            </div>
            <div id="pending-list">
                @foreach($pendingInputs as $pi)
                <div class="pi-item" id="pi-{{ $pi->id }}">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                        <div style="flex:1;min-width:0;">
                            <span style="display:inline-block;padding:2px 7px;font-size:11px;font-weight:600;background:#ede9fe;color:#7c3aed;border-radius:4px;margin-bottom:5px;">{{ $pi->input_type_label }}</span>
                            <span style="font-size:11px;color:#9ca3af;margin-left:6px;">{{ $pi->creator->name }}</span>
                            <p style="font-size:13px;color:#374151;margin:0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $pi->content ?? $pi->file_name }}</p>
                        </div>
                        <button onclick="deleteInput({{ $pi->id }})" style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#d1d5db;padding:2px;" title="{{ __('common.delete') }}">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @else
        <div id="pending-list" style="padding:0 16px 14px;font-size:12px;color:#9ca3af;">{{ __('work.planning_no_pending') }}</div>
        @endif
    </div>

    {{-- 웍스 기능 추천 카드 --}}
    @php
        $fsActive      = $featureSuggestions->whereNull('deleted_at')->where('is_applied', false)->values();
        $fsApplied     = $featureSuggestions->whereNull('deleted_at')->where('is_applied', true)->values();
        $fsActiveCount = $featureSuggestions->whereNull('deleted_at')->count();
    @endphp
    <div class="plan-card" id="feature-suggest-card" style="flex-shrink:0;">

        {{-- 헤더 --}}
        <div style="padding:10px 14px;border-bottom:1px solid #f4f4f5;display:flex;align-items:center;justify-content:space-between;gap:8px;">
            <div style="display:flex;align-items:center;gap:6px;">
                <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span style="font-size:13px;font-weight:700;color:#18181b;">{{ __('planning.feature_suggest') }}</span>
                <span id="fs-count-badge" style="font-size:11px;font-weight:600;padding:1px 6px;border-radius:10px;background:#ede9fe;color:#7c3aed;">{{ $fsActiveCount }}/5</span>
            </div>
            <button id="btn-suggest" onclick="runSuggestFeatures()"
                style="display:flex;align-items:center;gap:4px;padding:4px 10px;font-size:11px;font-weight:600;color:#fff;background:linear-gradient(135deg,#7c3aed,#4f46e5);border:none;border-radius:6px;cursor:pointer;"
                {{ $fsActiveCount >= 5 ? 'disabled style="opacity:.5;cursor:not-allowed;"' : '' }}>
                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ $fsActiveCount > 0 ? __('planning.re_suggest') : __('planning.do_suggest') }}
            </button>
        </div>

        {{-- 로딩 --}}
        <div id="fs-loading" style="display:none;padding:16px;text-align:center;">
            <div style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#f5f3ff;border-radius:10px;border:1px solid #ddd6fe;">
                <svg style="animation:spin 1s linear infinite;" width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span style="font-size:12px;font-weight:600;color:#5b21b6;">{{ __('planning.ai_analyzing') }}</span>
            </div>
        </div>

        {{-- 미니 탭바 --}}
        <div style="display:flex;border-bottom:1px solid #f4f4f5;padding:0 10px;gap:2px;">
            <button id="fs-tab-ai" onclick="switchFsTab('ai')"
                style="padding:8px 10px;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid #7c3aed;color:#7c3aed;display:flex;align-items:center;gap:4px;">
                {{ __('planning.fs_tab_ai') }}
                <span id="fs-tab-ai-cnt" style="padding:1px 5px;border-radius:8px;background:#ede9fe;color:#7c3aed;font-size:10px;">{{ $fsActive->count() }}</span>
            </button>
            <button id="fs-tab-applied" onclick="switchFsTab('applied')"
                style="padding:8px 10px;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:#6b7280;display:flex;align-items:center;gap:4px;">
                {{ __('planning.fs_tab_applied') }}
                <span id="fs-tab-applied-cnt" style="padding:1px 5px;border-radius:8px;background:#f3f4f6;color:#6b7280;font-size:10px;">{{ $fsApplied->count() }}</span>
            </button>
        </div>

        {{-- 웍스 추천 패널 --}}
        <div id="fs-panel-ai">
            @forelse($fsActive as $fs)
            <div class="fs-item fs-ai-item" id="fs-{{ $fs->id }}" style="padding:12px 14px;border-bottom:1px solid #f4f4f5;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:5px;">
                    <span class="fs-title" style="font-size:13px;font-weight:700;color:#1e1b4b;flex:1;">{{ $fs->title }}</span>
                    <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                        <button onclick="deleteSuggestion({{ $fs->id }})" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:1px;" title="{{ __('planning.delete') }}">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>
                <p style="font-size:12px;color:#374151;margin:0 0 5px;line-height:1.5;">{{ $fs->description }}</p>
                @if($fs->reason)
                <p style="font-size:11px;color:#7c3aed;margin:0 0 7px;font-style:italic;">💡 {{ $fs->reason }}</p>
                @endif
                <button onclick="applyFeature({{ $fs->id }}, '{{ addslashes($fs->title) }}')"
                    style="font-size:11px;font-weight:600;color:#4f46e5;background:#eef2ff;border:1px solid #c7d2fe;border-radius:6px;padding:3px 10px;cursor:pointer;"
                    onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'">
                    {{ __('planning.apply_to_doc') }}
                </button>
            </div>
            @empty
            <div id="fs-ai-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">{{ __('planning.fs_empty') }}</div>
            @endforelse
        </div>

        {{-- 추천 반영 패널 --}}
        <div id="fs-panel-applied" style="display:none;">
            @forelse($fsApplied as $fs)
            <div class="fs-item fs-applied-item" id="fs-ap-{{ $fs->id }}" data-heading="{{ $fs->title }}" style="padding:12px 14px;border-bottom:1px solid #f4f4f5;background:#f0fdf4;">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:5px;">
                    <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;">
                        <span style="font-size:13px;font-weight:700;color:#065f46;flex:1;">{{ $fs->title }}</span>
                        <span style="flex-shrink:0;font-size:10px;font-weight:700;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;border-radius:4px;padding:1px 6px;">{{ __('planning.applied_badge') }}</span>
                    </div>
                    <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                        <button onclick="scrollToReqInDoc(this)"
                                style="padding:3px 8px;font-size:11px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">{{ __('planning.location') }}</button>
                        <button onclick="deleteSuggestion({{ $fs->id }})" style="background:#fff0f0;border:1px solid #fca5a5;color:#dc2626;font-size:11px;font-weight:600;cursor:pointer;padding:3px 8px;border-radius:5px;white-space:nowrap;" title="{{ __('planning.revert_apply') }}">{{ __('planning.revert_apply') }}</button>
                    </div>
                </div>
                <p style="font-size:12px;color:#374151;margin:0 0 4px;line-height:1.5;">{{ $fs->description }}</p>
                <span style="font-size:11px;color:#6b7280;">{{ __('planning.applied_at', ['time' => $fs->applied_at?->format('m.d H:i')]) }}</span>
            </div>
            @empty
            <div style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">{{ __('planning.fs_applied_empty') }}</div>
            @endforelse
        </div>


    </div>

    {{-- 적용된 요구사항 카드 --}}
    @php
        $planApps          = $doc->planApplications()->with(['requirement', 'appliedBy'])->get();
        $planAppsActive    = $planApps->where('is_completed', false)->values();
        $planAppsCompleted = $planApps->where('is_completed', true)->values();
    @endphp
    <div class="plan-card" id="pa-card" style="flex-shrink:0;">
        {{-- 헤더 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;border-bottom:1px solid #f4f4f5;">
            <span style="font-size:13px;font-weight:700;color:#18181b;">{{ __('planning.plan_applied_reqs') }}</span>
            <span style="font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;padding:2px 8px;border-radius:10px;">{{ $planApps->count() }}</span>
        </div>

        {{-- 미니 탭바 --}}
        <div style="display:flex;border-bottom:1px solid #f4f4f5;padding:0 10px;gap:2px;">
            <button id="pa-tab-active" onclick="switchPaTab('active')"
                style="padding:8px 10px;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid #7c3aed;color:#7c3aed;display:flex;align-items:center;gap:4px;">
                {{ __('planning.pa_tab_active') }}
                <span id="pa-tab-active-cnt" style="padding:1px 5px;border-radius:8px;background:#ede9fe;color:#7c3aed;font-size:10px;">{{ $planAppsActive->count() }}</span>
            </button>
            <button id="pa-tab-completed" onclick="switchPaTab('completed')"
                style="padding:8px 10px;font-size:11px;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;color:#6b7280;display:flex;align-items:center;gap:4px;">
                {{ __('planning.pa_tab_completed') }}
                <span id="pa-tab-completed-cnt" style="padding:1px 5px;border-radius:8px;background:#f3f4f6;color:#6b7280;font-size:10px;">{{ $planAppsCompleted->count() }}</span>
            </button>
        </div>

        {{-- 적용 대상 패널 --}}
        <div id="pa-panel-active">
            @forelse($planAppsActive as $app)
            @php
                $reqHeading = trim(preg_replace('/^#+\s*/', '', explode("\n", $app->inserted_markdown ?? '')[0] ?? ''));
                $appActiveData = json_encode([
                    'id'          => $app->id,
                    'req_id'      => $app->requirement_id,
                    'title'       => $app->requirement?->title ?? '#'.$app->requirement_id,
                    'description' => $app->requirement?->description ?? '',
                    'markdown'    => $app->inserted_markdown ?? '',
                    'applied_at'  => $app->applied_at?->format('Y.m.d H:i') ?? '',
                    'applied_by'  => $app->appliedBy?->name ?? '',
                    'show_url'    => route('projects.requirements.show', [$project, $app->requirement_id]),
                    'status'      => 'active',
                ]);
            @endphp
            <div id="pa-{{ $app->id }}" data-heading="{{ $reqHeading }}" data-app="{{ $appActiveData }}"
                 onclick="openPaDetailModal(JSON.parse(this.dataset.app))"
                 style="padding:10px 14px;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;cursor:pointer;transition:background .1s;"
                 onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background=''">
                <div style="flex:1;min-width:0;">
                    <span style="font-size:12px;font-weight:600;color:var(--t500);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">
                        {{ $app->requirement?->title ?? '#' . $app->requirement_id }}
                    </span>
                    <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">{{ $app->appliedBy?->name }} · {{ $app->applied_at?->format('m.d H:i') }}</p>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                    <button onclick="event.stopPropagation(); scrollToReqInDoc(this)"
                            style="padding:2px 7px;font-size:10px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;"
                            onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">{{ __('planning.pa_tab_active') }}</button>
                    <button onclick="event.stopPropagation(); openSchModal({{ json_encode(['title'=>$app->requirement?->title??'','description'=>$app->requirement?->description??'','assignee_id'=>$app->requirement?->assignee_id]) }}, this)"
                            style="padding:2px 7px;font-size:10px;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;background:#faf5ff;cursor:pointer;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">{{ __('planning.schedule_add') }}</button>
                    <button onclick="event.stopPropagation(); revertPlanApp({{ $app->id }}, this)"
                            style="padding:2px 7px;font-size:10px;color:#ef4444;border:1px solid #fecaca;border-radius:5px;background:#fff;cursor:pointer;"
                            onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">{{ __('planning.cancel_apply') }}</button>
                </div>
            </div>
            @empty
            <div id="pa-active-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">{{ __('planning.pa_active_empty') }}</div>
            @endforelse
        </div>

        {{-- 적용 완료 패널 --}}
        <div id="pa-panel-completed" style="display:none;">
            @forelse($planAppsCompleted as $app)
            @php
                $reqHeading = trim(preg_replace('/^#+\s*/', '', explode("\n", $app->inserted_markdown ?? '')[0] ?? ''));
                $appData = json_encode([
                    'id'           => $app->id,
                    'req_id'       => $app->requirement_id,
                    'title'        => $app->requirement?->title ?? '#'.$app->requirement_id,
                    'description'  => $app->requirement?->description ?? '',
                    'markdown'     => $app->inserted_markdown ?? '',
                    'applied_at'   => $app->applied_at?->format('Y.m.d H:i') ?? '',
                    'completed_at' => $app->completed_at?->format('Y.m.d H:i') ?? '',
                    'applied_by'   => $app->appliedBy?->name ?? '',
                    'show_url'     => route('projects.requirements.show', [$project, $app->requirement_id]),
                    'status'       => 'completed',
                ]);
            @endphp
            <div id="pa-{{ $app->id }}" data-heading="{{ $reqHeading }}" data-app="{{ $appData }}"
                 onclick="openPaDetailModal(JSON.parse(this.dataset.app))"
                 style="padding:10px 14px;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;background:#f0fdf4;cursor:pointer;transition:background .1s;"
                 onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:12px;font-weight:600;color:#065f46;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            {{ $app->requirement?->title ?? '#' . $app->requirement_id }}
                        </span>
                        <span style="flex-shrink:0;font-size:10px;font-weight:700;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;border-radius:4px;padding:1px 5px;">{{ __('planning.completed_badge') }}</span>
                    </div>
                    <p style="font-size:11px;color:#6b7280;margin:2px 0 0;">{{ __('planning.completed_at', ['time' => $app->completed_at?->format('m.d H:i')]) }}</p>
                </div>
                <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                    <button onclick="event.stopPropagation(); scrollToReqInDoc(this)"
                            style="padding:2px 7px;font-size:10px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;"
                            onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">{{ __('planning.location') }}</button>
                    <button onclick="event.stopPropagation(); completePlanApp({{ $app->id }}, this)"
                            style="padding:2px 7px;font-size:10px;color:#6b7280;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;"
                            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.revert') }}</button>
                </div>
            </div>
            @empty
            <div id="pa-completed-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">{{ __('planning.pa_completed_empty') }}</div>
            @endforelse
        </div>
    </div>

    {{-- 변경 이력 카드 --}}
    <div class="plan-card" style="flex-shrink:0;">
        <div style="padding:14px 16px;border-bottom:1px solid #f4f4f5;font-size:13px;font-weight:700;color:#18181b;">{{ __('work.planning_history') }}</div>
        @if($histories->isEmpty())
        <div style="padding:16px;font-size:13px;color:#9ca3af;">{{ __('work.planning_no_history') }}</div>
        @else
        <div style="max-height:320px;overflow-y:auto;">
            @foreach($histories as $h)
            @php
                $hColors = ['user_add'=>'#dbeafe','user_edit'=>'#e0e7ff','ai_integrate'=>'#ede9fe','ai_suggest'=>'#fce7f3','approved'=>'#d1fae5','rejected'=>'#fee2e2'];
                $hTextColors = ['user_add'=>'#1e40af','user_edit'=>'#4338ca','ai_integrate'=>'#7c3aed','ai_suggest'=>'#be185d','approved'=>'#065f46','rejected'=>'#991b1b'];
                $hc = $hColors[$h->change_type] ?? '#f3f4f6';
                $htc = $hTextColors[$h->change_type] ?? '#374151';
            @endphp
            <div class="hist-item">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px;">
                    <span style="padding:2px 7px;font-size:11px;font-weight:600;border-radius:4px;background:{{ $hc }};color:{{ $htc }};">{{ $h->change_type_label }}</span>
                    <span style="font-size:11px;color:#9ca3af;">v{{ $h->version }} · {{ $h->created_at->format('m.d H:i') }}</span>
                </div>
                <p style="font-size:12px;color:#374151;margin:0 0 4px;">{{ $h->summary }}</p>
                <div style="display:flex;align-items:center;">
                    <span style="font-size:11px;color:#9ca3af;">{{ $h->changedBy->name }}</span>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>{{-- /plan-right --}}
</div>{{-- /plan-wrap --}}

{{-- ── 리셋 확인 모달 ── --}}
<div id="reset-overlay" onclick="closeResetModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.55);z-index:6000;backdrop-filter:blur(3px);"></div>

<div id="reset-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            width:460px;max-width:94vw;z-index:6001;
            background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.18);overflow:hidden;">
    <div style="padding:20px 24px 16px;border-bottom:1px solid #fee2e2;background:#fef2f2;display:flex;align-items:center;gap:10px;">
        <svg width="20" height="20" fill="none" stroke="#b91c1c" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        <span style="font-size:15px;font-weight:700;color:#b91c1c;">{{ __('planning.reset_modal_title') }}</span>
    </div>
    <div style="padding:20px 24px;">
        <p style="font-size:13px;color:#374151;line-height:1.6;margin:0 0 14px;">
            {!! __('planning.reset_modal_desc') !!}
        </p>
        <ul style="font-size:12px;color:#6b7280;line-height:1.9;margin:0 0 18px;padding-left:18px;">
            <li>{{ __('planning.reset_item_reqs') }}</li>
            <li>{{ __('planning.reset_item_req_status') }}</li>
            <li>{{ __('planning.reset_item_features') }}</li>
            <li>{{ __('planning.reset_item_applied_reqs') }}</li>
            <li>{{ __('planning.reset_item_subtasks') }}</li>
            <li>{{ __('planning.reset_item_gantt') }}</li>
        </ul>
        <p style="font-size:12px;color:#dc2626;font-weight:600;margin:0 0 18px;">{{ __('planning.reset_irreversible') }}</p>
        <div style="display:flex;gap:8px;justify-content:flex-end;">
            <button onclick="closeResetModal()"
                style="padding:8px 18px;font-size:13px;color:#6b7280;border:1px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
                {{ __('common.cancel') }}
            </button>
            <button id="btn-reset-confirm" onclick="confirmReset()"
                style="padding:8px 20px;font-size:13px;font-weight:700;color:#fff;background:#dc2626;border:none;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                {{ __('planning.reset_run') }}
            </button>
        </div>
    </div>
</div>

{{-- ── 외부 공유 링크 팝업 ── --}}
<div id="share-overlay" onclick="closeShareModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.5);z-index:6000;backdrop-filter:blur(3px);"></div>

<div id="share-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            width:480px;max-width:94vw;
            z-index:6001;background:#fff;border-radius:16px;
            box-shadow:0 24px 80px rgba(15,15,35,.3);overflow:hidden;">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:center;gap:10px;padding:0 20px;height:52px;border-bottom:1px solid #f4f4f5;background:#fff;">
        <svg width="16" height="16" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        <span style="font-size:14px;font-weight:700;color:#111827;flex:1;">{{ __('planning.share_modal_title') }}</span>
        <button onclick="closeShareModal()" style="width:30px;height:30px;display:flex;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:7px;background:#fff;cursor:pointer;color:#6b7280;font-size:16px;"
            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">✕</button>
    </div>

    {{-- 상태 안내 --}}
    <div id="share-modal-body" style="padding:20px;">

        {{-- 비공유 상태 --}}
        <div id="share-state-off" style="display:none;">
            <p style="font-size:13px;color:#374151;line-height:1.6;margin-bottom:16px;">
                {!! __('planning.share_off_desc') !!}
            </p>
            <button id="btn-share-create" onclick="createShareLink()"
                style="width:100%;padding:10px;font-size:13px;font-weight:700;color:#fff;background:#059669;border:none;border-radius:9px;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px;"
                onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                {{ __('planning.create_share_link') }}
            </button>
        </div>

        {{-- 공유 중 상태 --}}
        <div id="share-state-on" style="display:none;">
            <div style="margin-bottom:14px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;display:flex;align-items:center;gap:6px;">
                <svg width="13" height="13" fill="#059669" viewBox="0 0 20 20"><circle cx="10" cy="10" r="10" opacity=".15"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span style="font-size:12px;font-weight:600;color:#065f46;">{{ __('planning.share_active') }}</span>
            </div>

            {{-- URL 입력+복사 --}}
            <div style="display:flex;gap:6px;margin-bottom:14px;">
                <input id="share-url-input" type="text" readonly
                    style="flex:1;padding:8px 12px;font-size:12px;color:#374151;background:#f9fafb;border:1px solid #e4e4e7;border-radius:8px;outline:none;font-family:monospace;min-width:0;"
                    onclick="this.select()">
                <button onclick="copyShareLink()"
                    style="padding:8px 14px;font-size:12px;font-weight:700;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;white-space:nowrap;flex-shrink:0;"
                    onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
                    {{ __('planning.copy_link') }}
                </button>
            </div>

            {{-- 새 창 열기 + PDF 다운로드 --}}
            <div style="display:flex;gap:6px;margin-bottom:14px;">
                <a id="share-open-link" href="#" target="_blank"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;font-size:12px;color:#4f46e5;border:1px solid #c7d2fe;border-radius:8px;text-decoration:none;background:#eef2ff;"
                   onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    {{ __('planning.open_preview') }}
                </a>
                <a id="share-pdf-link" href="#" target="_blank"
                   style="flex:1;display:flex;align-items:center;justify-content:center;gap:5px;padding:8px;font-size:12px;font-weight:600;color:#dc2626;border:1px solid #fca5a5;border-radius:8px;text-decoration:none;background:#fff;"
                   onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    {{ __('planning.pdf_download') }}
                </a>
            </div>

            {{-- 공유 취소 --}}
            <div style="border-top:1px solid #f4f4f5;padding-top:14px;">
                <p style="font-size:12px;color:#9ca3af;margin-bottom:10px;">{{ __('planning.share_cancel_desc') }}</p>
                <button onclick="cancelShareLink()"
                    style="width:100%;padding:8px;font-size:12px;font-weight:600;color:#dc2626;background:#fff;border:1.5px solid #fca5a5;border-radius:8px;cursor:pointer;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                    {{ __('planning.cancel_share_link') }}
                </button>
            </div>
        </div>

    </div>
</div>

{{-- ── 기획서 작성 팝업 모달 ── --}}
<div id="wm-overlay" onclick="closeWriteModal()"
     style="display:none;position:fixed;inset:0;background:rgba(15,15,35,.6);z-index:5000;backdrop-filter:blur(2px);"></div>

<div id="wm-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            width:92vw;max-width:1400px;height:88vh;
            z-index:5001;flex-direction:column;
            background:#fff;border-radius:16px;
            box-shadow:0 24px 80px rgba(15,15,35,.35);
            overflow:hidden;">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:center;gap:10px;padding:0 20px;height:54px;border-bottom:1px solid #e4e4e7;flex-shrink:0;background:#fff;border-radius:16px 16px 0 0;">
        <svg width="16" height="16" fill="none" stroke="#4f46e5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        <span style="font-size:14px;font-weight:700;color:#1e1b4b;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ __('planning.write_modal_title', ['title' => $doc->title]) }}</span>

        {{-- 뷰 모드 토글 --}}
        <div style="display:flex;gap:2px;padding:3px;background:#f4f4f5;border-radius:8px;flex-shrink:0;">
            <button id="wm-btn-split"   onclick="setWriteView('split')"
                style="padding:4px 12px;font-size:11px;font-weight:600;border:none;border-radius:6px;cursor:pointer;background:#4f46e5;color:#fff;transition:all .1s;">{{ __('planning.view_split') }}</button>
            <button id="wm-btn-editor"  onclick="setWriteView('editor')"
                style="padding:4px 12px;font-size:11px;font-weight:600;border:none;border-radius:6px;cursor:pointer;background:transparent;color:#6b7280;transition:all .1s;">{{ __('planning.view_editor') }}</button>
            <button id="wm-btn-preview" onclick="setWriteView('preview')"
                style="padding:4px 12px;font-size:11px;font-weight:600;border:none;border-radius:6px;cursor:pointer;background:transparent;color:#6b7280;transition:all .1s;">{{ __('planning.view_preview') }}</button>
        </div>

        <span style="font-size:11px;color:#9ca3af;flex-shrink:0;">Ctrl+S</span>

        <button id="wm-save-btn" onclick="saveWriteModal()"
            style="display:flex;align-items:center;gap:5px;padding:7px 18px;font-size:13px;font-weight:700;color:#fff;background:#4f46e5;border:none;border-radius:8px;cursor:pointer;flex-shrink:0;white-space:nowrap;"
            onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>{{ __('common.save') }}
        </button>
        <button onclick="closeWriteModal()"
            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border:1px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;color:#6b7280;font-size:16px;flex-shrink:0;"
            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'" title="{{ __('planning.close_esc') }}">✕</button>
    </div>

    {{-- 툴바 --}}
    <div style="display:flex;align-items:center;gap:2px;padding:6px 14px;border-bottom:1px solid #f0f0f0;background:#fafafa;flex-shrink:0;flex-wrap:wrap;row-gap:4px;">
        <button onclick="wmTool('# ','')"              title="{{ __('planning.tool_h1') }}"      style="padding:3px 8px;font-size:11px;font-weight:700;color:#1e1b4b;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">H1</button>
        <button onclick="wmTool('## ','')"             title="{{ __('planning.tool_h2') }}"      style="padding:3px 8px;font-size:11px;font-weight:700;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">H2</button>
        <button onclick="wmTool('### ','')"            title="{{ __('planning.tool_h3') }}"      style="padding:3px 8px;font-size:11px;font-weight:700;color:#6b7280;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">H3</button>
        <div style="width:1px;height:18px;background:#e4e4e7;margin:0 3px;"></div>
        <button onclick="wmTool('**','**')"            title="{{ __('planning.tool_bold') }}" style="padding:3px 8px;font-size:12px;font-weight:800;color:#111827;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;font-family:serif;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">B</button>
        <button onclick="wmTool('*','*')"              title="{{ __('planning.tool_italic') }}"      style="padding:3px 8px;font-size:12px;font-weight:600;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;font-style:italic;font-family:serif;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">I</button>
        <button onclick="wmTool('~~','~~')"            title="{{ __('planning.tool_strike') }}"      style="padding:3px 8px;font-size:11px;font-weight:600;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;text-decoration:line-through;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">S</button>
        <div style="width:1px;height:18px;background:#e4e4e7;margin:0 3px;"></div>
        <button onclick="wmTool('[','](url)')"         title="{{ __('planning.tool_link') }}"        style="padding:3px 8px;font-size:11px;color:#4f46e5;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_link_label') }}</button>
        <button onclick="wmTool('\`','\`')"            title="{{ __('planning.tool_code') }}" style="padding:3px 8px;font-size:11px;color:#0369a1;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;font-family:monospace;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">code</button>
        <button onclick="wmTool('> ','')"              title="{{ __('planning.tool_quote') }}"      style="padding:3px 8px;font-size:11px;color:#6d28d9;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_quote_label') }}</button>
        <div style="width:1px;height:18px;background:#e4e4e7;margin:0 3px;"></div>
        <button onclick="wmTool('- ','')"              title="{{ __('planning.tool_ul') }}" style="padding:3px 8px;font-size:11px;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_ul_label') }}</button>
        <button onclick="wmTool('1. ','')"             title="{{ __('planning.tool_ol') }}"   style="padding:3px 8px;font-size:11px;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_ol_label') }}</button>
        <button onclick="wmInsertTable()"              title="{{ __('planning.tool_table') }}"     style="padding:3px 8px;font-size:11px;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_table_label') }}</button>
        <button onclick="wmTool('\n\n---\n\n','')"     title="{{ __('planning.tool_hr') }}"      style="padding:3px 8px;font-size:11px;color:#374151;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;" onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('planning.tool_hr_label') }}</button>
        <div style="margin-left:auto;font-size:11px;color:#9ca3af;white-space:nowrap;" id="wm-charcount">{{ __('planning.char_count', ['count' => 0]) }}</div>
    </div>

    {{-- 본문 영역 --}}
    <div id="wm-body" style="display:grid;grid-template-columns:1fr 1fr;flex:1;min-height:0;overflow:hidden;">

        {{-- 에디터 --}}
        <div id="wm-editor-pane" style="display:flex;flex-direction:column;border-right:1px solid #e8e8ed;min-height:0;background:#fff;">
            <div style="padding:6px 16px 4px;background:#f8f8fb;border-bottom:1px solid #f0f0f0;font-size:10px;font-weight:600;color:#9ca3af;letter-spacing:.05em;">{{ __('planning.markdown_edit') }}</div>
            <textarea id="wm-editor"
                oninput="wmUpdatePreview();wmUpdateCount();"
                style="flex:1;border:none;outline:none;resize:none;font-size:14px;font-family:'D2Coding','Consolas','Courier New',monospace;line-height:1.8;padding:20px 24px;color:#1f2937;background:#fff;box-sizing:border-box;"
                placeholder="{{ __('planning.editor_placeholder') }}"></textarea>
        </div>

        {{-- 미리보기 --}}
        <div id="wm-preview-pane" style="display:flex;flex-direction:column;overflow:hidden;background:#fafbff;">
            <div style="padding:6px 16px 4px;background:#f0f4ff;border-bottom:1px solid #e0e7ff;font-size:10px;font-weight:600;color:#6366f1;letter-spacing:.05em;">{{ __('planning.view_preview') }}</div>
            <div style="overflow-y:auto;flex:1;">
                <div id="wm-preview" class="md-render" style="padding:20px 28px;"></div>
            </div>
        </div>
    </div>

    {{-- 하단 상태바 --}}
    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 20px;border-top:1px solid #f0f0f0;background:#fafafa;flex-shrink:0;font-size:11px;color:#9ca3af;border-radius:0 0 16px 16px;">
        <span>{{ __('planning.write_modal_footer') }}</span>
        <span id="wm-status" style="font-weight:600;"></span>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
const AI_URL         = '{{ route("projects.planning.aiIntegrate", [$project, $doc]) }}';
const APPROVE_URL    = '{{ route("projects.planning.approve",     [$project, $doc]) }}';
const REJECT_URL     = '{{ route("projects.planning.reject",      [$project, $doc]) }}';
const UPDATE_URL     = '{{ route("projects.planning.update",      [$project, $doc]) }}';
const INPUT_URL      = '{{ route("projects.planning.addInput",    [$project, $doc]) }}';
const AIWRITE_URL        = '{{ route("projects.planning.aiWrite",       [$project, $doc]) }}';
const AIWRITE_STREAM_URL = '{{ route("projects.planning.aiWriteStream", [$project, $doc]) }}';
const AICLEANUP_URL  = '{{ route("projects.planning.aiCleanup",   [$project, $doc]) }}';
const DEL_INPUT_BASE   = '{{ url("projects/" . $project->id . "/planning/" . $doc->id . "/input") }}';
const SUGGEST_URL        = '{{ route("projects.planning.suggestFeatures",        [$project, $doc]) }}';
const SUGGEST_STREAM_URL = '{{ route("projects.planning.suggestFeaturesStream", [$project, $doc]) }}';
const FEAT_APPLY_BASE  = '{{ url("projects/" . $project->id . "/planning/" . $doc->id . "/feature") }}';
const FEAT_DEL_BASE    = '{{ url("projects/" . $project->id . "/planning/feature") }}';
const SEND_EMAIL_URL      = '{{ route("projects.planning.sendEmail",   [$project, $doc]) }}';
const TOGGLE_SHARE_URL    = '{{ route("projects.planning.toggleShare", [$project, $doc]) }}';
const RESET_URL           = '{{ route("projects.planning.reset",       [$project, $doc]) }}';
const DOC_TITLE           = @json($doc->title);

const _PLAN_STR = {
    noContent:       '{{ __("work.planning_no_content") }}',
    aiNoContent:     '{{ __("work.planning_ai_no_content") }}',
    inputRequired:   '{{ __("work.planning_input_required") }}',
    processing:      '{{ __("work.planning_processing") }}',
    writing:         '{{ __("work.planning_writing") }}',
    addFailed:       '{{ __("work.planning_add_failed") }}',
    aiFailed:        '{{ __("work.planning_ai_failed") }}',
    aiWriteFailed:   '{{ __("work.planning_ai_write_failed") }}',
    saveFailed:      '{{ __("work.planning_save_failed") }}',
    toastSaved:      '{{ __("work.planning_toast_saved") }}',
    toastApplied:    '{{ __("work.planning_toast_applied") }}',
    toastDocSaved:   '{{ __("work.planning_toast_doc_saved") }}',
    toastRestored:   '{{ __("work.planning_toast_restored") }}',
    noEditorContent: '{{ __("work.planning_no_editor_content") }}',
    describeHint:    '{{ __("work.planning_describe_hint") }}',
    errorOccurred:   '{{ __("work.planning_error_occurred") }}',
    cleanupDone:     '{{ __("work.planning_cleanup_done") }}',
    cleanupFailed:   '{{ __("work.planning_cleanup_failed") }}',
    confirmApprove:  '{{ __("work.planning_confirm_approve") }}',
    confirmReject:   '{{ __("work.planning_confirm_reject") }}',
    confirmDelete:   '{{ __("work.planning_confirm_delete") }}',
    fileSelect:      '{{ __("work.planning_file_select") }}',
    aiWriteStart:    '{{ __("work.planning_ai_write_start") }}',
    aiIntegrate:     '{{ __("work.planning_ai_integrate") }}',
};

const _PLAN_T = {
    previewEmpty:        @json(__('planning.preview_empty')),
    saving:              @json(__('planning.saving')),
    saveComplete:        @json(__('planning.save_complete')),
    saveFailed:          @json(__('planning.save_failed')),
    toolSampleText:      @json(__('planning.tool_sample_text')),
    tableTemplate:       @json(__('planning.table_template')),
    charCount:           @json(__('planning.char_count')),
    featureSuggested:    @json(__('planning.feature_suggested')),
    featuresSuggested:   @json(__('planning.features_suggested')),
    suggestError:        @json(__('planning.suggest_error')),
    confirmRevertFeature:@json(__('planning.confirm_revert_feature')),
    revertFeatureFailed: @json(__('planning.revert_feature_failed')),
    featureReverted:     @json(__('planning.feature_reverted')),
    revertFeatureError:  @json(__('planning.revert_feature_error')),
    applyFeatureFailed:  @json(__('planning.apply_feature_failed')),
    featureApplied:      @json(__('planning.feature_applied')),
    applyFeatureError:   @json(__('planning.apply_feature_error')),
    fsEmpty:             @json(__('planning.fs_empty')),
    appliedBadge:        @json(__('planning.applied_badge')),
    revertApply:         @json(__('planning.revert_apply')),
    location:            @json(__('planning.location')),
    appliedAt:           @json(__('planning.applied_at')),
    noLocationInfo:      @json(__('planning.no_location_info')),
    locationNotFound:    @json(__('planning.location_not_found')),
    processError:        @json(__('planning.process_error')),
    movedToCompleted:    @json(__('planning.moved_to_completed')),
    movedToActive:       @json(__('planning.moved_to_active')),
    completedBadge:      @json(__('planning.completed_badge')),
    completedAt:         @json(__('planning.completed_at')),
    revert:              @json(__('planning.revert')),
    paTabActive:         @json(__('planning.pa_tab_active')),
    cancelApply:         @json(__('planning.cancel_apply')),
    paActiveEmpty:       @json(__('planning.pa_active_empty')),
    paCompletedEmpty:    @json(__('planning.pa_completed_empty')),
    paModalCompleted:    @json(__('planning.pa_modal_completed')),
    paModalApplying:     @json(__('planning.pa_modal_applying')),
    paMetaAppliedBy:     @json(__('planning.pa_meta_applied_by')),
    paMetaApplied:       @json(__('planning.pa_meta_applied')),
    paMetaCompleted:     @json(__('planning.pa_meta_completed')),
    confirmRevertPa:     @json(__('planning.confirm_revert_pa')),
    revertPaFailed:      @json(__('planning.revert_pa_failed')),
    paReverted:          @json(__('planning.pa_reverted')),
    paRevertBtn:         @json(__('planning.pa_revert_btn')),
    paCloseBtn:          @json(__('planning.pa_close_btn')),
    schLoading:          @json(__('planning.sch_loading')),
    schSelectGroup:      @json(__('planning.sch_select_group')),
    schNoGroups:         @json(__('planning.sch_no_groups')),
    schGroupLoadFailed:  @json(__('planning.sch_group_load_failed')),
    schNoAssignee:       @json(__('planning.sch_no_assignee')),
    schSelectGroupAlert: @json(__('planning.sch_select_group_alert')),
    schEnterTitle:       @json(__('planning.sch_enter_title')),
    schSelectStart:      @json(__('planning.sch_select_start')),
    schSelectEnd:        @json(__('planning.sch_select_end')),
    schRegistering:      @json(__('planning.sch_registering')),
    schRegisterFailed:   @json(__('planning.sch_register_failed')),
    schRegistered:       @json(__('planning.sch_registered')),
    schRegister:         @json(__('planning.sch_register')),
    schAdded:            @json(__('planning.sch_added')),
    emailNoContent:      @json(__('planning.email_no_content')),
    emailSelectRecipient:@json(__('planning.email_select_recipient_alert')),
    emailSending:        @json(__('planning.email_sending')),
    emailSend:           @json(__('planning.email_send')),
    emailSendFailed:     @json(__('planning.email_send_failed')),
    emailSent:           @json(__('planning.email_sent')),
    emailNetworkError:   @json(__('planning.email_network_error')),
    shareCreateFailed:   @json(__('planning.share_create_failed')),
    shareError:          @json(__('planning.share_error')),
    shareLinkCopied:     @json(__('planning.share_link_copied')),
    confirmCancelShare:  @json(__('planning.confirm_cancel_share')),
    shareCancelFailed:   @json(__('planning.share_cancel_failed')),
    shareCancelled:      @json(__('planning.share_cancelled')),
    creating:            @json(__('planning.creating')),
    createShareLink:     @json(__('planning.create_share_link')),
    sharing:             @json(__('planning.sharing')),
    shareLink:           @json(__('planning.share_link')),
    resetting:           @json(__('planning.resetting')),
    resetRun:            @json(__('planning.reset_run')),
    resetFailed:         @json(__('planning.reset_failed')),
    autoApplyMessage:    @json(__('planning.auto_apply_message')),
    applyToDoc:          @json(__('planning.apply_to_doc')),
    delete:              @json(__('planning.delete')),
};

let activeInputType = 'text';

if (typeof marked !== 'undefined') {
    marked.setOptions({ breaks: true, gfm: true });

    const renderer = new marked.Renderer();

    renderer.heading = function(token) {
        const text  = typeof token === 'object' ? token.text  : token;
        const depth = typeof token === 'object' ? token.depth : arguments[1];
        const id = text.replace(/[^\w가-힣]/g, '-').toLowerCase();
        return `<h${depth} id="${id}">${text}</h${depth}>\n`;
    };

    marked.use({ renderer });
}

function _sanitizeHtml(html) {
    return html
        .replace(/<(script|iframe|object|embed|frame|frameset|base|meta|link|form)[^>]*>[\s\S]*?<\/\1>/gi, '')
        .replace(/<(script|iframe|object|embed|frame|frameset|base|meta|link|input|button|form)[^>]*\/?>/gi, '');
}

async function updatePreview() {
    if (typeof marked === 'undefined') return;
    const content = document.getElementById('doc-editor').value;
    const viewEl  = document.getElementById('md-view');
    if (!viewEl) return;
    viewEl.innerHTML = content
        ? _sanitizeHtml(marked.parse(content))
        : `<p style="color:#9ca3af;font-style:italic;">${_PLAN_STR.noContent}</p>`;
}

async function renderAiTab() {
    if (typeof marked === 'undefined') return;
    const el = document.getElementById('md-ai');
    if (!el) return;
    const raw = el.getAttribute('data-raw') || '';
    el.innerHTML = raw
        ? _sanitizeHtml(marked.parse(raw))
        : `<p style="color:#9ca3af;">${_PLAN_STR.aiNoContent}</p>`;
}

document.getElementById('doc-editor').addEventListener('input', updatePreview);

async function switchTab(name) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    const panel = document.getElementById('tab-' + name);
    if (panel) panel.classList.add('active');
    event.currentTarget.classList.add('active');
    if (name === 'view') updatePreview();
}

async function setInputType(type, btn) {
    activeInputType = type;
    document.querySelectorAll('.input-type-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('file-input-wrap').style.display = type === 'file' ? 'block' : 'none';
    document.getElementById('input-content').style.display = type === 'file' ? 'none' : 'block';
}

async function updateFileLabel(input) {
    const label = document.getElementById('file-label-text');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
        label.style.color = '#374151';
    } else {
        label.textContent = _PLAN_STR.fileSelect;
        label.style.color = '#6b7280';
    }
}

async function submitInput() {
    const content = document.getElementById('input-content').value.trim();
    const fileEl  = document.getElementById('input-file');
    if (!content && (!fileEl.files || !fileEl.files[0])) {
        alert(_PLAN_STR.inputRequired);
        return;
    }
    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('input_type', activeInputType);
    fd.append('content', content);
    if (fileEl.files[0]) fd.append('file', fileEl.files[0]);

    fetch(INPUT_URL, { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => {
            if (d.ok) { location.reload(); }
            else alert(d.error || _PLAN_STR.addFailed);
        });
}

async function deleteInput(id) {
    if (!await __confirm(_PLAN_STR.confirmDelete)) return;
    fetch(DEL_INPUT_BASE + '/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    }).then(r => r.json()).then(d => {
        if (d.ok) document.getElementById('pi-' + id)?.remove();
    });
}

async function runAiIntegrate() {
    const btn = document.getElementById('btn-ai');
    btn.textContent = _PLAN_STR.processing;
    btn.disabled = true;

    fetch(AI_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            location.reload();
        } else {
            alert(d.error || _PLAN_STR.aiFailed);
            btn.disabled = false;
            btn.innerHTML = '✨ ' + _PLAN_STR.aiIntegrate;
        }
    })
    .catch(() => { btn.disabled = false; });
}

async function approveAi() {
    if (!await __confirm(_PLAN_STR.confirmApprove)) return;
    fetch(APPROVE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

async function rejectAi() {
    if (!await __confirm(_PLAN_STR.confirmReject)) return;
    fetch(REJECT_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(d => { if (d.ok) location.reload(); else alert(d.error); });
}

async function saveContent() {
    const content = document.getElementById('doc-editor').value;
    fetch(UPDATE_URL, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ content }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { updatePreview(); showStatus(_PLAN_STR.toastSaved, '#059669'); }
    });
}

// ── 웍스 작성 기능 ──────────────────────────────────────────────
let aiWriteMode    = @json($doc->content ? 'enhance' : 'new');
let aiWrittenText  = '';

async function setWriteMode(mode) {
    aiWriteMode = mode;
    const btnNew     = document.getElementById('mode-btn-new');
    const btnEnhance = document.getElementById('mode-btn-enhance');
    if (mode === 'new') {
        btnNew.style.cssText     = 'padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid #7c3aed;background:#7c3aed;color:#fff;cursor:pointer;';
        btnEnhance.style.cssText = 'padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid #e4e4e7;background:#fff;color:#6b7280;cursor:pointer;';
    } else {
        btnEnhance.style.cssText = 'padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid #7c3aed;background:#7c3aed;color:#fff;cursor:pointer;';
        btnNew.style.cssText     = 'padding:5px 14px;font-size:12px;font-weight:600;border-radius:20px;border:1.5px solid #e4e4e7;background:#fff;color:#6b7280;cursor:pointer;';
    }
}

async function runAiWrite() {
    const prompt = document.getElementById('ai-write-prompt').value.trim();
    if (!prompt) { alert(_PLAN_STR.describeHint); return; }

    const btn     = document.getElementById('btn-ai-write');
    const loading = document.getElementById('ai-write-loading');
    const result  = document.getElementById('ai-write-result');
    const preview = document.getElementById('ai-write-preview');
    const btnIcon = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg> ';

    btn.disabled = true;
    btn.innerHTML = _PLAN_STR.writing;
    result.style.display  = 'none';
    loading.style.display = 'block';
    aiWrittenText = '';

    const resetBtn = () => { btn.disabled = false; btn.innerHTML = btnIcon + _PLAN_STR.aiWriteStart; };

    try {
        const res = await fetch(AIWRITE_STREAM_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
            body:    JSON.stringify({ prompt, mode: aiWriteMode }),
        });

        const reader  = res.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';
        let   started = false;

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                let data;
                try { data = JSON.parse(line.slice(6)); } catch { continue; }

                if (data.error) {
                    loading.style.display = 'none';
                    resetBtn();
                    alert(data.error);
                    return;
                }

                if (data.chunk) {
                    if (!started) {
                        started = true;
                        loading.style.display = 'none';
                        result.style.display  = 'flex';
                        preview.innerHTML = '';
                    }
                    aiWrittenText += data.chunk;
                    if (typeof marked !== 'undefined') {
                        preview.innerHTML = _sanitizeHtml(marked.parse(aiWrittenText));
                    } else {
                        preview.textContent = aiWrittenText;
                    }
                }

                if (data.done) {
                    const match = aiWrittenText.match(/^#\s+(.+)$/m);
                    document.getElementById('ai-write-summary').textContent = match ? match[1] : '';
                    resetBtn();
                }
            }
        }
    } catch (e) {
        loading.style.display = 'none';
        resetBtn();
        alert(_PLAN_STR.errorOccurred);
    }
}

async function applyToEditor() {
    document.getElementById('doc-editor').value = aiWrittenText;
    updatePreview();
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-edit').classList.add('active');
    document.querySelector('.tab-btn[onclick*="edit"]').classList.add('active');
    showStatus(_PLAN_STR.toastApplied, '#059669');
}

async function saveAiWritten() {
    if (!aiWrittenText) return;
    fetch(UPDATE_URL, {
        method: 'PUT',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ content: aiWrittenText }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('doc-editor').value = aiWrittenText;
            updatePreview();
            showStatus(_PLAN_STR.toastDocSaved, '#059669');
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('tab-view').classList.add('active');
            document.querySelector('.tab-btn[onclick*="view"]').classList.add('active');
        } else {
            alert(d.error || _PLAN_STR.saveFailed);
        }
    });
}

async function resetAiWrite() {
    aiWrittenText = '';
    document.getElementById('ai-write-result').style.display = 'none';
    document.getElementById('ai-write-prompt').value = '';
    document.getElementById('ai-write-prompt').focus();
}

// ── 웍스 정리 ─────────────────────────────────────
let cleanupOriginal = null;
let cleanupUndoTimer = null;

async function runAiCleanup() {
    const editor  = document.getElementById('doc-editor');
    const content = editor.value.trim();
    if (!content) { showStatus(_PLAN_STR.noEditorContent, '#9ca3af'); return; }

    const btn     = document.getElementById('btn-ai-cleanup');
    const undoBtn = document.getElementById('btn-cleanup-undo');

    cleanupOriginal = content;
    btn.disabled = true;
    btn.innerHTML = '<svg style="animation:spin 1s linear infinite" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> ' + _PLAN_STR.processing;

    fetch(AICLEANUP_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ content }),
    })
    .then(r => r.json())
    .then(d => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg> ' + _PLAN_STR.aiCleanup;

        if (!d.ok) { showStatus(d.error || _PLAN_STR.cleanupFailed, '#ef4444'); return; }

        editor.value = d.content;
        updatePreview();

        undoBtn.style.display = 'block';
        clearTimeout(cleanupUndoTimer);
        cleanupUndoTimer = setTimeout(() => {
            undoBtn.style.display = 'none';
            cleanupOriginal = null;
        }, 10000);

        showStatus('✓ ' + (d.summary || _PLAN_STR.cleanupDone), '#7c3aed');
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg> ' + _PLAN_STR.aiCleanup;
        showStatus(_PLAN_STR.errorOccurred, '#ef4444');
    });
}

async function undoCleanup() {
    if (!cleanupOriginal) return;
    document.getElementById('doc-editor').value = cleanupOriginal;
    updatePreview();
    document.getElementById('btn-cleanup-undo').style.display = 'none';
    clearTimeout(cleanupUndoTimer);
    cleanupOriginal = null;
    showStatus(_PLAN_STR.toastRestored, '#6b7280');
}

async function showStatus(msg, color) {
    const icons = {
        '#059669': '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>',
        '#dc2626': '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>',
        '#6b7280': '<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
    };
    const icon = icons[color] ?? icons['#6b7280'];
    const el = document.createElement('div');
    el.style.cssText = `position:fixed;bottom:24px;right:24px;display:flex;align-items:center;gap:9px;padding:11px 16px;background:#fff;color:#111827;border-radius:10px;font-size:13px;font-weight:600;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.12);border-left:4px solid ${color};min-width:200px;`;
    el.innerHTML = `<span style="color:${color};flex-shrink:0;">${icon}</span><span>${msg}</span>`;
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 2500);
}

// ── 리셋 모달 ──────────────────────────────────────────────────
async function openResetModal() {
    document.getElementById('reset-overlay').style.display = 'block';
    document.getElementById('reset-modal').style.display   = 'block';
}
async function closeResetModal() {
    document.getElementById('reset-overlay').style.display = 'none';
    document.getElementById('reset-modal').style.display   = 'none';
}
async function confirmReset() {
    const btn = document.getElementById('btn-reset-confirm');
    btn.disabled    = true;
    btn.textContent = _PLAN_T.resetting;

    try {
        const res = await fetch(RESET_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (!res.ok) throw new Error(await res.text());

        closeResetModal();
        location.reload();
    } catch (e) {
        btn.disabled    = false;
        btn.textContent = _PLAN_T.resetRun;
        alert(_PLAN_T.resetFailed.replace(':message', e.message));
    }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeResetModal();
});

// ── 외부 공유 링크 팝업 ─────────────────────────────────────────
async function openShareModal() {
    const btn      = document.getElementById('btn-plan-share');
    const isActive = btn.dataset.active === 'true';
    const stateOff = document.getElementById('share-state-off');
    const stateOn  = document.getElementById('share-state-on');

    stateOff.style.display = isActive ? 'none' : 'block';
    stateOn.style.display  = isActive ? 'block' : 'none';

    if (isActive) {
        const url = btn.dataset.url;
        document.getElementById('share-url-input').value = url;
        document.getElementById('share-open-link').href  = url;
        document.getElementById('share-pdf-link').href   = url + '/print';
    }

    document.getElementById('share-overlay').style.display = 'block';
    document.getElementById('share-modal').style.display   = 'block';
    document.body.style.overflow = 'hidden';
}

async function closeShareModal() {
    document.getElementById('share-overlay').style.display = 'none';
    document.getElementById('share-modal').style.display   = 'none';
    document.body.style.overflow = '';
}

async function createShareLink() {
    const createBtn = document.getElementById('btn-share-create');
    createBtn.disabled    = true;
    createBtn.textContent = _PLAN_T.creating;

    fetch(TOGGLE_SHARE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { showStatus(_PLAN_T.shareCreateFailed, '#dc2626'); return; }

        const btn = document.getElementById('btn-plan-share');
        btn.dataset.active   = 'true';
        btn.dataset.url      = d.url;
        btn.style.border     = '1.5px solid #059669';
        btn.style.background = '#d1fae5';
        btn.style.color      = '#065f46';
        document.getElementById('btn-plan-share-txt').textContent = _PLAN_T.sharing;

        document.getElementById('share-url-input').value = d.url;
        document.getElementById('share-open-link').href  = d.url;
        document.getElementById('share-pdf-link').href   = d.url + '/print';
        document.getElementById('share-state-off').style.display = 'none';
        document.getElementById('share-state-on').style.display  = 'block';
    })
    .catch(() => showStatus(_PLAN_T.shareError, '#dc2626'))
    .finally(() => {
        createBtn.disabled    = false;
        createBtn.innerHTML   = '<svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg> ' + _PLAN_T.createShareLink;
    });
}

async function copyShareLink() {
    const url = document.getElementById('share-url-input').value;
    navigator.clipboard.writeText(url).then(() => showStatus(_PLAN_T.shareLinkCopied, '#059669'));
}

async function cancelShareLink() {
    if (!await __confirm(_PLAN_T.confirmCancelShare)) return;

    fetch(TOGGLE_SHARE_URL, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { showStatus(_PLAN_T.shareCancelFailed, '#dc2626'); return; }

        const btn = document.getElementById('btn-plan-share');
        btn.dataset.active   = 'false';
        btn.dataset.url      = '';
        btn.style.border     = '1.5px solid #d1d5db';
        btn.style.background = '#fff';
        btn.style.color      = '#6b7280';
        document.getElementById('btn-plan-share-txt').textContent = _PLAN_T.shareLink;

        closeShareModal();
        showStatus(_PLAN_T.shareCancelled, '#6b7280');
    })
    .catch(() => showStatus(_PLAN_T.shareError, '#dc2626'));
}

// Ctrl+S 저장
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        if (document.getElementById('wm-modal').style.display === 'flex') {
            e.preventDefault();
            saveWriteModal();
        } else if (document.getElementById('tab-edit').classList.contains('active')) {
            e.preventDefault();
            saveContent();
        }
    }
    if (e.key === 'Escape') {
        if (document.getElementById('pa-detail-modal').style.display === 'flex') closePaDetailModal();
        else if (document.getElementById('share-modal').style.display === 'block') closeShareModal();
        else if (document.getElementById('wm-modal').style.display === 'flex') closeWriteModal();
    }
});

// ── 기획서 작성 팝업 ─────────────────────────────────────────────
let _wmView = 'split';

async function openWriteModal() {
    const wm = document.getElementById('wm-modal');
    const ov = document.getElementById('wm-overlay');
    document.getElementById('wm-editor').value = document.getElementById('doc-editor').value;
    wm.style.display = 'flex';
    ov.style.display = 'block';
    document.body.style.overflow = 'hidden';
    setWriteView(_wmView);
    wmUpdatePreview();
    wmUpdateCount();
    setTimeout(() => document.getElementById('wm-editor').focus(), 50);
}

async function closeWriteModal() {
    document.getElementById('wm-modal').style.display = 'none';
    document.getElementById('wm-overlay').style.display = 'none';
    document.body.style.overflow = '';
}

async function setWriteView(mode) {
    _wmView = mode;
    const edPane = document.getElementById('wm-editor-pane');
    const pvPane = document.getElementById('wm-preview-pane');
    const body   = document.getElementById('wm-body');
    const btns   = { split: 'wm-btn-split', editor: 'wm-btn-editor', preview: 'wm-btn-preview' };

    Object.keys(btns).forEach(k => {
        const el = document.getElementById(btns[k]);
        const active = k === mode;
        el.style.background = active ? '#4f46e5' : 'transparent';
        el.style.color      = active ? '#fff'    : '#6b7280';
    });

    if (mode === 'split') {
        body.style.gridTemplateColumns = '1fr 1fr';
        edPane.style.display = 'flex';
        pvPane.style.display = 'block';
    } else if (mode === 'editor') {
        body.style.gridTemplateColumns = '1fr';
        edPane.style.display = 'flex';
        pvPane.style.display = 'none';
    } else {
        body.style.gridTemplateColumns = '1fr';
        edPane.style.display = 'none';
        pvPane.style.display = 'block';
        wmUpdatePreview();
    }
}

async function wmUpdatePreview() {
    if (typeof marked === 'undefined') return;
    const content = document.getElementById('wm-editor').value;
    const pv = document.getElementById('wm-preview');
    pv.innerHTML = content
        ? _sanitizeHtml(marked.parse(content))
        : `<p style="color:#9ca3af;font-style:italic;">${_PLAN_T.previewEmpty}</p>`;
}

async function wmUpdateCount() {
    const len = document.getElementById('wm-editor').value.length;
    document.getElementById('wm-charcount').textContent = _PLAN_T.charCount.replace(':count', len.toLocaleString());
}

async function wmTool(before, after) {
    const ta = document.getElementById('wm-editor');
    const s  = ta.selectionStart;
    const e  = ta.selectionEnd;
    const sel = ta.value.substring(s, e) || _PLAN_T.toolSampleText;
    const ins = before + sel + after;
    ta.value = ta.value.substring(0, s) + ins + ta.value.substring(e);
    ta.focus();
    ta.setSelectionRange(s + before.length, s + before.length + sel.length);
    wmUpdatePreview();
    wmUpdateCount();
}

async function wmInsertTable() {
    const tpl = _PLAN_T.tableTemplate;
    const ta  = document.getElementById('wm-editor');
    const s   = ta.selectionStart;
    ta.value  = ta.value.substring(0, s) + tpl + ta.value.substring(ta.selectionEnd);
    ta.focus();
    ta.setSelectionRange(s + tpl.length, s + tpl.length);
    wmUpdatePreview();
    wmUpdateCount();
}

async function saveWriteModal() {
    const content = document.getElementById('wm-editor').value;
    const btn     = document.getElementById('wm-save-btn');
    const status  = document.getElementById('wm-status');
    btn.disabled  = true;
    btn.textContent = _PLAN_T.saving;
    status.textContent = '';

    try {
        const res  = await fetch(UPDATE_URL, {
            method: 'PUT',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ content }),
        });
        const data = await res.json();
        if (data.ok) {
            document.getElementById('doc-editor').value = content;
            updatePreview();
            status.textContent = _PLAN_T.saveComplete;
            status.style.color = '#059669';
            showStatus(_PLAN_STR.toastSaved, '#059669');
            setTimeout(() => { status.textContent = ''; }, 3000);
        } else {
            status.textContent = _PLAN_T.saveFailed;
            status.style.color = '#dc2626';
        }
    } catch {
        status.textContent = _PLAN_T.saveFailed;
        status.style.color = '#dc2626';
    }

    btn.disabled = false;
    btn.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' + @json(__('common.save'));
}

updatePreview();
renderAiTab();

// ── 웍스 기능 추천 ──────────────────────────────────────────────────────────────
async function switchFsTab(tab) {
    ['ai','applied'].forEach(t => {
        const panel  = document.getElementById('fs-panel-' + t);
        const tabBtn = document.getElementById('fs-tab-' + t);
        const cnt    = tabBtn ? tabBtn.querySelector('span') : null;
        const active = t === tab;
        if (panel)  panel.style.display = active ? 'block' : 'none';
        if (tabBtn) {
            tabBtn.style.borderBottom = active ? '2px solid #7c3aed' : '2px solid transparent';
            tabBtn.style.color        = active ? '#7c3aed' : '#6b7280';
        }
        if (cnt) { cnt.style.background = active ? '#ede9fe' : '#f3f4f6'; cnt.style.color = active ? '#7c3aed' : '#6b7280'; }
    });
}

async function _fsUpdateCnt(elId, delta) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.textContent = Math.max(0, (parseInt(el.textContent) || 0) + delta);
}

async function _renderSuggestionItem(item) {
    const aiPanel = document.getElementById('fs-panel-ai');
    aiPanel.querySelector('#fs-ai-empty, .fs-empty')?.remove();

    const el = document.createElement('div');
    el.className = 'fs-item fs-ai-item';
    el.id = 'fs-' + item.id;
    el.style.cssText = 'padding:12px 14px;border-bottom:1px solid #f4f4f5;';
    el.innerHTML = `
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:4px;">
            <span class="fs-title" style="font-size:13px;font-weight:700;color:#1e1b4b;flex:1;">${escHtml(item.title)}</span>
            <div style="display:flex;align-items:center;gap:2px;flex-shrink:0;">
                <button onclick="deleteSuggestion(${item.id})" style="background:none;border:none;cursor:pointer;color:#d1d5db;padding:1px;" title="${escAttr(_PLAN_T.delete)}">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <p style="font-size:12px;color:#374151;margin:0 0 5px;line-height:1.5;">${escHtml(item.description)}</p>
        ${item.reason ? `<p style="font-size:11px;color:#7c3aed;font-style:italic;margin:0 0 8px;">💡 ${escHtml(item.reason)}</p>` : ''}
        <button onclick="applyFeature(${item.id}, '${escAttr(item.title)}')"
            style="font-size:11px;font-weight:600;color:#4f46e5;background:#eef2ff;border:1px solid #c7d2fe;border-radius:6px;padding:3px 10px;cursor:pointer;"
            onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'">
            ${escHtml(_PLAN_T.applyToDoc)}
        </button>`;
    aiPanel.prepend(el);

    _fsUpdateCnt('fs-tab-ai-cnt', 1);
}

async function runSuggestFeatures() {
    const btn     = document.getElementById('btn-suggest');
    const loading = document.getElementById('fs-loading');
    const badge   = document.getElementById('fs-count-badge');

    btn.disabled = true;
    loading.style.display = 'block';

    let addedCount = 0;

    try {
        const res = await fetch(SUGGEST_STREAM_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'text/event-stream' },
        });

        const reader  = res.body.getReader();
        const decoder = new TextDecoder();
        let   buffer  = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (!line.startsWith('data: ')) continue;
                let data;
                try { data = JSON.parse(line.slice(6)); } catch { continue; }

                if (data.error) {
                    loading.style.display = 'none';
                    btn.disabled = false;
                    showStatus(data.error, '#ef4444');
                    return;
                }

                if (data.item) {
                    _renderSuggestionItem(data.item);
                    addedCount++;
                    showStatus(_PLAN_T.featureSuggested.replace(':title', data.item.title), '#7c3aed');
                }

                if (data.done) {
                    loading.style.display = 'none';
                    badge.textContent = data.total + '/5';
                    btn.disabled = data.total >= 5;
                    btn.style.opacity = data.total >= 5 ? '.5' : '';
                    if (addedCount > 0) showStatus(_PLAN_T.featuresSuggested.replace(':count', addedCount), '#7c3aed');
                }
            }
        }
    } catch (e) {
        loading.style.display = 'none';
        btn.disabled = false;
        showStatus(_PLAN_T.suggestError, '#ef4444');
    }
}

async function deleteSuggestion(id) {
    if (!await __confirm(_PLAN_T.confirmRevertFeature)) return;
    fetch(FEAT_DEL_BASE + '/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { showStatus(d.error || _PLAN_T.revertFeatureFailed, '#ef4444'); return; }

        if (d.content != null) {
            const editor = document.getElementById('doc-editor');
            if (editor) { editor.value = d.content; updatePreview(); }
        }

        const aiItem = document.getElementById('fs-' + id);
        const apItem = document.getElementById('fs-ap-' + id);

        if (aiItem)   { aiItem.remove();   _fsUpdateCnt('fs-tab-ai-cnt', -1); }
        if (apItem)   { apItem.remove();   _fsUpdateCnt('fs-tab-applied-cnt', -1); }

        // Update active badge count
        const badge    = document.getElementById('fs-count-badge');
        const newTotal = Math.max(0, parseInt(badge.textContent) - 1);
        badge.textContent = newTotal + '/5';

        const btn = document.getElementById('btn-suggest');
        if (newTotal < 5) { btn.disabled = false; btn.style.opacity = ''; }

        const aiPanel = document.getElementById('fs-panel-ai');
        if (!aiPanel.querySelector('.fs-ai-item')) {
            aiPanel.innerHTML = `<div id="fs-ai-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">${escHtml(_PLAN_T.fsEmpty)}</div>`;
        }

        showStatus(_PLAN_T.featureReverted, '#f59e0b');
    })
    .catch(() => showStatus(_PLAN_T.revertFeatureError, '#ef4444'));
}

async function applyFeature(id, title) {
    fetch(FEAT_APPLY_BASE + '/' + id + '/apply', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { showStatus(d.error || _PLAN_T.applyFeatureFailed, '#ef4444'); return; }

        const editor = document.getElementById('doc-editor');
        if (editor) { editor.value = d.content; updatePreview(); }

        const aiItem = document.getElementById('fs-' + id);
        if (aiItem) {
            const itemTitle = aiItem.querySelector('.fs-title')?.textContent?.trim() ?? title;
            const itemDesc  = aiItem.querySelector('p')?.textContent?.trim() ?? '';

            // Remove from 웍스 panel and update count
            aiItem.remove();
            _fsUpdateCnt('fs-tab-ai-cnt', -1);

            // Show empty state if 웍스 panel is now empty
            const aiPanel = document.getElementById('fs-panel-ai');
            if (!aiPanel.querySelector('.fs-ai-item')) {
                aiPanel.innerHTML = `<div id="fs-ai-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">${escHtml(_PLAN_T.fsEmpty)}</div>`;
            }

            // Add to Applied panel
            const apPanel = document.getElementById('fs-panel-applied');
            apPanel.querySelector('div:not(.fs-applied-item)')?.remove(); // remove empty state if present
            const now    = new Date();
            const nowStr = String(now.getMonth()+1).padStart(2,'0') + '.' + String(now.getDate()).padStart(2,'0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
            const apEl   = document.createElement('div');
            apEl.className = 'fs-item fs-applied-item';
            apEl.id = 'fs-ap-' + id;
            apEl.dataset.heading = itemTitle;
            apEl.style.cssText = 'padding:12px 14px;border-bottom:1px solid #f4f4f5;background:#f0fdf4;';
            apEl.innerHTML = `
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:5px;">
                    <div style="display:flex;align-items:center;gap:6px;flex:1;min-width:0;">
                        <span style="font-size:13px;font-weight:700;color:#065f46;flex:1;">${escHtml(itemTitle)}</span>
                        <span style="flex-shrink:0;font-size:10px;font-weight:700;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;border-radius:4px;padding:1px 6px;">${escHtml(_PLAN_T.appliedBadge)}</span>
                    </div>
                    <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                        <button onclick="scrollToReqInDoc(this)" style="padding:3px 8px;font-size:11px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;font-weight:600;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">${escHtml(_PLAN_T.location)}</button>
                        <button onclick="deleteSuggestion(${id})" style="background:#fff0f0;border:1px solid #fca5a5;color:#dc2626;font-size:11px;font-weight:600;cursor:pointer;padding:3px 8px;border-radius:5px;white-space:nowrap;" title="${escAttr(_PLAN_T.revertApply)}">${escHtml(_PLAN_T.revertApply)}</button>
                    </div>
                </div>
                <p style="font-size:12px;color:#374151;margin:0 0 4px;line-height:1.5;">${escHtml(itemDesc)}</p>
                <span style="font-size:11px;color:#6b7280;">${escHtml(_PLAN_T.appliedAt.replace(':time', nowStr))}</span>`;
            apPanel.prepend(apEl);
            _fsUpdateCnt('fs-tab-applied-cnt', 1);
        }

        showStatus(_PLAN_T.featureApplied.replace(':title', title), '#059669');
    })
    .catch(() => showStatus(_PLAN_T.applyFeatureError, '#ef4444'));
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(str) {
    return String(str).replace(/'/g,"\\'").replace(/"/g,'&quot;');
}

// ── 기획서 적용 요구사항 탭 ───────────────────────────────────────
const PA_BASE = '{{ url("projects/{$project->id}/plan-applications") }}';

async function switchPaTab(tab) {
    ['active','completed'].forEach(t => {
        const panel  = document.getElementById('pa-panel-' + t);
        const tabBtn = document.getElementById('pa-tab-' + t);
        const cnt    = tabBtn ? tabBtn.querySelector('span') : null;
        const active = t === tab;
        if (panel)  panel.style.display = active ? 'block' : 'none';
        if (tabBtn) {
            tabBtn.style.borderBottom = active ? '2px solid #7c3aed' : '2px solid transparent';
            tabBtn.style.color        = active ? '#7c3aed' : '#6b7280';
        }
        if (cnt) { cnt.style.background = active ? '#ede9fe' : '#f3f4f6'; cnt.style.color = active ? '#7c3aed' : '#6b7280'; }
    });
}

async function scrollToReqInDoc(btn) {
    const heading = btn.closest('[data-heading]')?.dataset.heading?.trim();
    if (!heading) { showStatus(_PLAN_T.noLocationInfo, '#ef4444'); return; }

    // 보기 탭으로 전환
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => {
        const isView = b.getAttribute('onclick')?.includes("switchTab('view')");
        b.classList.toggle('active', !!isView);
    });
    document.getElementById('tab-view')?.classList.add('active');

    // #md-view 내에서 헤딩 탐색
    const mdView = document.getElementById('md-view');
    if (!mdView) return;

    const normalizedTarget = heading.toLowerCase();
    let targetEl = null;
    for (const el of mdView.querySelectorAll('h1,h2,h3,h4')) {
        if (el.textContent.trim().toLowerCase() === normalizedTarget) {
            targetEl = el;
            break;
        }
    }

    if (!targetEl) { showStatus(_PLAN_T.locationNotFound, '#ef4444'); return; }

    targetEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    targetEl.classList.remove('req-highlight');
    void targetEl.offsetWidth; // reflow to restart animation
    targetEl.classList.add('req-highlight');
    setTimeout(() => targetEl.classList.remove('req-highlight'), 2400);
}

async function completePlanApp(appId, btn) {
    const res = await fetch(PA_BASE + '/' + appId + '/complete', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    if (!res.ok) { showStatus(_PLAN_T.processError, '#ef4444'); return; }
    const d = await res.json();

    const item           = document.getElementById('pa-' + appId);
    const activePanel    = document.getElementById('pa-panel-active');
    const completedPanel = document.getElementById('pa-panel-completed');
    const activeCnt      = document.getElementById('pa-tab-active-cnt');
    const completedCnt   = document.getElementById('pa-tab-completed-cnt');

    if (!item) return;

    const now     = new Date();
    const nowStr  = String(now.getMonth()+1).padStart(2,'0') + '.' + String(now.getDate()).padStart(2,'0') + ' ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    const nowFull = now.getFullYear() + '.' + nowStr;

    if (d.is_completed) {
        // 적용 대상 → 적용 완료
        const oldData   = JSON.parse(item.dataset.app || '{}');
        const heading   = item.dataset.heading || '';
        const newData   = Object.assign({}, oldData, { status: 'completed', completed_at: nowFull });
        const titleText = oldData.title || item.querySelector('span')?.textContent?.trim() || '';

        item.remove();

        const newEl = document.createElement('div');
        newEl.id              = 'pa-' + appId;
        newEl.dataset.heading = heading;
        newEl.dataset.app     = JSON.stringify(newData);
        newEl.style.cssText   = 'padding:10px 14px;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;background:#f0fdf4;cursor:pointer;transition:background .1s;';
        newEl.innerHTML = `
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:6px;">
                    <span style="font-size:12px;font-weight:600;color:#065f46;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtml(titleText)}</span>
                    <span style="flex-shrink:0;font-size:10px;font-weight:700;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;border-radius:4px;padding:1px 5px;">${escHtml(_PLAN_T.completedBadge)}</span>
                </div>
                <p style="font-size:11px;color:#6b7280;margin:2px 0 0;">${escHtml(_PLAN_T.completedAt.replace(':time', nowStr))}</p>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                <button onclick="event.stopPropagation(); scrollToReqInDoc(this)"
                        style="padding:2px 7px;font-size:10px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;"
                        onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">${escHtml(_PLAN_T.location)}</button>
                <button onclick="event.stopPropagation(); completePlanApp(${appId}, this)"
                        style="padding:2px 7px;font-size:10px;color:#6b7280;border:1px solid #e4e4e7;border-radius:5px;background:#fff;cursor:pointer;"
                        onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">${escHtml(_PLAN_T.revert)}</button>
            </div>`;
        newEl.addEventListener('mouseenter', () => { newEl.style.background = '#dcfce7'; });
        newEl.addEventListener('mouseleave', () => { newEl.style.background = '#f0fdf4'; });
        newEl.addEventListener('click',      () => openPaDetailModal(JSON.parse(newEl.dataset.app)));

        completedPanel.querySelector('#pa-completed-empty')?.remove();
        completedPanel.prepend(newEl);

        activeCnt.textContent    = Math.max(0, (parseInt(activeCnt.textContent)   || 0) - 1);
        completedCnt.textContent = (parseInt(completedCnt.textContent) || 0) + 1;
        if (!activePanel.querySelector('[id^="pa-"]')) {
            activePanel.innerHTML = `<div id="pa-active-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">${escHtml(_PLAN_T.paActiveEmpty)}</div>`;
        }
        showStatus(_PLAN_T.movedToCompleted, '#059669');
    } else {
        // 적용 완료 → 적용 대상
        const oldData   = JSON.parse(item.dataset.app || '{}');
        const heading   = item.dataset.heading || '';
        const newData   = Object.assign({}, oldData, { status: 'active', completed_at: '' });
        const titleText = oldData.title || item.querySelector('span')?.textContent?.trim() || '';

        item.remove();

        const newEl = document.createElement('div');
        newEl.id              = 'pa-' + appId;
        newEl.dataset.heading = heading;
        newEl.dataset.app     = JSON.stringify(newData);
        newEl.style.cssText   = 'padding:10px 14px;border-bottom:1px solid #f9fafb;display:flex;align-items:flex-start;justify-content:space-between;gap:8px;cursor:pointer;transition:background .1s;';
        newEl.innerHTML = `
            <div style="flex:1;min-width:0;">
                <span style="font-size:12px;font-weight:600;color:var(--t500);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block;">${escHtml(titleText)}</span>
                <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">${escHtml(oldData.applied_by || '')} · ${escHtml(oldData.applied_at || '')}</p>
            </div>
            <div style="display:flex;gap:4px;flex-shrink:0;align-items:center;">
                <button onclick="event.stopPropagation(); scrollToReqInDoc(this)"
                        style="padding:2px 7px;font-size:10px;color:#059669;border:1px solid #a7f3d0;border-radius:5px;background:#f0fdf4;cursor:pointer;"
                        onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">${escHtml(_PLAN_T.paTabActive)}</button>
                <button onclick="event.stopPropagation(); revertPlanApp(${appId}, this)"
                        style="padding:2px 7px;font-size:10px;color:#ef4444;border:1px solid #fecaca;border-radius:5px;background:#fff;cursor:pointer;"
                        onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">${escHtml(_PLAN_T.cancelApply)}</button>
            </div>`;
        newEl.addEventListener('mouseenter', () => { newEl.style.background = '#f5f3ff'; });
        newEl.addEventListener('mouseleave', () => { newEl.style.background = ''; });
        newEl.addEventListener('click',      () => openPaDetailModal(JSON.parse(newEl.dataset.app)));

        activePanel.querySelector('#pa-active-empty')?.remove();
        activePanel.prepend(newEl);

        completedCnt.textContent = Math.max(0, (parseInt(completedCnt.textContent) || 0) - 1);
        activeCnt.textContent    = (parseInt(activeCnt.textContent) || 0) + 1;
        if (!completedPanel.querySelector('[id^="pa-"]')) {
            completedPanel.innerHTML = `<div id="pa-completed-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">${escHtml(_PLAN_T.paCompletedEmpty)}</div>`;
        }
        showStatus(_PLAN_T.movedToActive, '#6b7280');
    }
}

// ── 기획서 적용 요구사항 상세 팝업 ─────────────────────────────
async function openPaDetailModal(data) {
    const isCompleted = data.status === 'completed';

    document.getElementById('pa-modal-title').textContent = data.title || '';

    const badge = document.getElementById('pa-modal-badge');
    badge.innerHTML = isCompleted
        ? `<span style="font-size:11px;font-weight:700;color:#059669;background:#d1fae5;border:1px solid #a7f3d0;border-radius:4px;padding:2px 7px;">${escHtml(_PLAN_T.paModalCompleted)}</span>`
        : `<span style="font-size:11px;font-weight:600;color:#7c3aed;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:4px;padding:2px 7px;">${escHtml(_PLAN_T.paModalApplying)}</span>`;

    let meta = '';
    if (data.applied_by) meta += _PLAN_T.paMetaAppliedBy.replace(':name', data.applied_by);
    if (data.applied_at) meta += (meta ? ' · ' : '') + _PLAN_T.paMetaApplied.replace(':time', data.applied_at);
    if (isCompleted && data.completed_at) meta += ' · ' + _PLAN_T.paMetaCompleted.replace(':time', data.completed_at);
    document.getElementById('pa-modal-meta').textContent = meta;

    const descWrap = document.getElementById('pa-modal-desc-wrap');
    const descEl   = document.getElementById('pa-modal-desc');
    if (data.description) {
        descEl.textContent       = data.description;
        descWrap.style.display   = 'block';
    } else {
        descWrap.style.display   = 'none';
    }

    const mdWrap = document.getElementById('pa-modal-md-wrap');
    const mdEl   = document.getElementById('pa-modal-md');
    if (data.markdown) {
        mdEl.innerHTML       = typeof marked !== 'undefined' ? _sanitizeHtml(marked.parse(data.markdown)) : escHtml(data.markdown);
        mdWrap.style.display = 'block';
    } else {
        mdWrap.style.display = 'none';
    }

    const actEl = document.getElementById('pa-modal-actions');
    let html = '';
    if (isCompleted) {
        html += `<button onclick="closePaDetailModal(); completePlanApp(${data.id}, null)"
            style="padding:7px 14px;font-size:12px;font-weight:600;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">${escHtml(_PLAN_T.paRevertBtn)}</button>`;
    }
    html += `<button onclick="closePaDetailModal()"
        style="padding:7px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">${escHtml(_PLAN_T.paCloseBtn)}</button>`;
    actEl.innerHTML = html;

    document.getElementById('pa-detail-overlay').style.display = 'block';
    document.getElementById('pa-detail-modal').style.display   = 'flex';
}

async function openPaCompletedModal(data) {
    openPaDetailModal(Object.assign({}, data, { status: 'completed' }));
}

async function closePaDetailModal() {
    document.getElementById('pa-detail-overlay').style.display = 'none';
    document.getElementById('pa-detail-modal').style.display   = 'none';
}

async function revertPlanApp(appId, btn) {
    if (!await __confirm(_PLAN_T.confirmRevertPa)) return;
    const res = await fetch(PA_BASE + '/' + appId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
    });
    const d = await res.json();
    if (!d.ok) { showStatus(d.error || _PLAN_T.revertPaFailed, '#ef4444'); return; }

    if (d.content != null) {
        const editor = document.getElementById('doc-editor');
        if (editor) { editor.value = d.content; updatePreview(); }
    }

    const item = document.getElementById('pa-' + appId);
    if (item) {
        item.remove();
        const cnt = document.getElementById('pa-tab-active-cnt');
        if (cnt) cnt.textContent = Math.max(0, (parseInt(cnt.textContent) || 0) - 1);
        const panel = document.getElementById('pa-panel-active');
        if (!panel.querySelector('[id^="pa-"]')) {
            panel.innerHTML = `<div id="pa-active-empty" style="padding:16px;font-size:12px;color:#9ca3af;text-align:center;">${escHtml(_PLAN_T.paActiveEmpty)}</div>`;
        }
    }
    showStatus(_PLAN_T.paReverted, '#f59e0b');
}

// ── 간트 일정 등록 모달 ────────────────────────────────────────
const SCH_STORE = '{{ route('projects.sub-tasks.store', $project) }}';
const SCH_TREE  = '{{ route('projects.schedule-tree', $project) }}';
const SCH_MEMBERS = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]));

let _schBtn = null;

async function openSchModal(req, btn) {
    _schBtn = btn ?? null;
    document.getElementById('sch-title').value = req.title ?? '';
    document.getElementById('sch-desc').value  = req.description ?? '';
    document.getElementById('sch-start').value = '';
    document.getElementById('sch-end').value   = '';
    document.getElementById('sch-error').style.display = 'none';

    const assigneeSel = document.getElementById('sch-assignee');
    assigneeSel.innerHTML = `<option value="">${escHtml(_PLAN_T.schNoAssignee)}</option>` +
        SCH_MEMBERS.map(m =>
            `<option value="${m.id}" ${m.id == req.assignee_id ? 'selected' : ''}>${m.name}</option>`
        ).join('');

    const groupSel = document.getElementById('sch-group');
    groupSel.innerHTML = `<option value="">${escHtml(_PLAN_T.schLoading)}</option>`;
    try {
        const res  = await fetch(SCH_TREE, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const tree = await res.json();
        const opts = [];
        (tree.milestones ?? []).forEach(ms => {
            (ms.task_groups ?? ms.taskGroups ?? []).forEach(g => {
                opts.push(`<option value="${g.id}">[${ms.title}] ${g.title}</option>`);
            });
        });
        (tree.ungrouped ?? []).forEach(g => {
            opts.push(`<option value="${g.id}">${g.title}</option>`);
        });
        groupSel.innerHTML = opts.length
            ? `<option value="">${escHtml(_PLAN_T.schSelectGroup)}</option>` + opts.join('')
            : `<option value="">${escHtml(_PLAN_T.schNoGroups)}</option>`;
    } catch {
        groupSel.innerHTML = `<option value="">${escHtml(_PLAN_T.schGroupLoadFailed)}</option>`;
    }

    document.getElementById('sch-overlay').style.display = 'block';
    document.getElementById('sch-modal').style.display   = 'block';
}

async function closeSchModal() {
    document.getElementById('sch-overlay').style.display = 'none';
    document.getElementById('sch-modal').style.display   = 'none';
}

async function submitSchModal() {
    const groupId = document.getElementById('sch-group').value;
    const title   = document.getElementById('sch-title').value.trim();
    const start   = document.getElementById('sch-start').value;
    const end     = document.getElementById('sch-end').value;
    const errEl   = document.getElementById('sch-error');
    errEl.style.display = 'none';

    if (!groupId) { errEl.textContent = _PLAN_T.schSelectGroupAlert; errEl.style.display = 'block'; return; }
    if (!title)   { errEl.textContent = _PLAN_T.schEnterTitle;       errEl.style.display = 'block'; return; }
    if (!start)   { errEl.textContent = _PLAN_T.schSelectStart;      errEl.style.display = 'block'; return; }
    if (!end)     { errEl.textContent = _PLAN_T.schSelectEnd;        errEl.style.display = 'block'; return; }

    const btn = document.getElementById('sch-submit-btn');
    btn.disabled = true; btn.textContent = _PLAN_T.schRegistering;

    try {
        const body = new FormData();
        body.append('task_group_id', groupId);
        body.append('title',         title);
        body.append('start_date',    start);
        body.append('end_date',      end);
        body.append('assignee_id',   document.getElementById('sch-assignee').value || '');
        body.append('description',   document.getElementById('sch-desc').value     || '');
        body.append('status',        'not_started');

        const res  = await fetch(SCH_STORE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || _PLAN_T.schRegisterFailed);

        closeSchModal();
        alert(_PLAN_T.schAdded);
        if (_schBtn) {
            _schBtn.textContent = _PLAN_T.schRegistered;
            _schBtn.style.cssText = 'padding:2px 7px;font-size:10px;color:#16a34a;border:1px solid #bbf7d0;border-radius:5px;background:#f0fdf4;cursor:default;';
            _schBtn.disabled = true;
            _schBtn.onmouseover = null;
            _schBtn.onmouseout  = null;
        }
    } catch (e) {
        errEl.textContent = e.message;
        errEl.style.display = 'block';
    }
    btn.disabled = false; btn.textContent = _PLAN_T.schRegister;
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSchModal(); });

// ── 기획서 이메일 발송 모달 ──────────────────────────────────────────────────
async function openPlanEmailModal() {
    const hasContent = @json((bool)$doc->content);
    if (!hasContent) {
        showStatus(_PLAN_T.emailNoContent, '#ef4444');
        return;
    }
    document.getElementById('plan-email-error').style.display = 'none';
    document.getElementById('plan-email-overlay').style.display = 'block';
    document.getElementById('plan-email-modal').style.display = 'block';
}

async function closePlanEmailModal() {
    document.getElementById('plan-email-overlay').style.display = 'none';
    document.getElementById('plan-email-modal').style.display = 'none';
}

async function submitPlanEmail() {
    const checkedBoxes = document.querySelectorAll('input[name="plan_email_recipients[]"]:checked');
    if (checkedBoxes.length === 0) {
        document.getElementById('plan-email-error').textContent = _PLAN_T.emailSelectRecipient;
        document.getElementById('plan-email-error').style.display = 'block';
        return;
    }
    const recipients = [...checkedBoxes].map(cb => cb.value);
    const message    = document.getElementById('plan-email-message').value.trim();
    const btn        = document.getElementById('plan-email-submit');
    btn.disabled = true; btn.textContent = _PLAN_T.emailSending;
    document.getElementById('plan-email-error').style.display = 'none';
    try {
        const res  = await fetch(SEND_EMAIL_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ recipients, message }),
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
            closePlanEmailModal();
            showStatus(_PLAN_T.emailSent, '#059669');
        } else {
            document.getElementById('plan-email-error').textContent = data.message || _PLAN_T.emailSendFailed;
            document.getElementById('plan-email-error').style.display = 'block';
        }
    } catch {
        document.getElementById('plan-email-error').textContent = _PLAN_T.emailNetworkError;
        document.getElementById('plan-email-error').style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = _PLAN_T.emailSend;
    }
}
</script>

{{-- 기획서 이메일 발송 모달 --}}
<div id="plan-email-overlay" onclick="closePlanEmailModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10200;"></div>
<div id="plan-email-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10201;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:500px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ __('planning.email_modal_eyebrow') }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('planning.email_modal_title') }}</h3>
        </div>
        <button onclick="closePlanEmailModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>
    <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
        {{-- 문서 정보 --}}
        <div style="padding:10px 14px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;">
            <p style="font-size:11px;color:#7c3aed;font-weight:600;margin:0 0 2px;">{{ __('planning.email_attached_doc') }}</p>
            <p style="font-size:13px;font-weight:700;color:#1e1b4b;margin:0;">{{ $doc->title }}</p>
            <p style="font-size:11px;color:#9ca3af;margin:2px 0 0;">{{ $project->name }} · v{{ $doc->version }}</p>
        </div>

        {{-- 수신자 선택 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">{{ __('planning.email_select_recipients') }} <span style="color:#ef4444;">*</span></label>
            <div style="border:1.5px solid #e4e4e7;border-radius:8px;max-height:160px;overflow-y:auto;">
                @forelse($members as $m)
                <label style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid #f4f4f5;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background=''">
                    <input type="checkbox" name="plan_email_recipients[]" value="{{ $m->email }}"
                           style="width:14px;height:14px;accent-color:#4f46e5;flex-shrink:0;">
                    <div>
                        <span style="font-size:13px;font-weight:500;color:#374151;">{{ $m->name }}</span>
                        <span style="font-size:11px;color:#9ca3af;margin-left:6px;">{{ $m->email }}</span>
                    </div>
                </label>
                @empty
                <div style="padding:12px;font-size:12px;color:#9ca3af;text-align:center;">{{ __('planning.email_no_members') }}</div>
                @endforelse
            </div>
        </div>

        {{-- 추가 메시지 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.email_extra_message') }}</label>
            <textarea id="plan-email-message" rows="3" placeholder="{{ __('planning.email_message_placeholder') }}"
                style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;resize:none;outline:none;box-sizing:border-box;font-family:inherit;"
                onfocus="this.style.borderColor='#4f46e5'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
        </div>

        <div id="plan-email-error" style="display:none;padding:8px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:7px;font-size:12px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:4px;">
            <button id="plan-email-submit" onclick="submitPlanEmail()"
                style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:#4f46e5;border:none;border-radius:9px;cursor:pointer;"
                onmouseover="this.style.background='#4338ca'" onmouseout="this.style.background='#4f46e5'">{{ __('planning.email_send') }}</button>
            <button onclick="closePlanEmailModal()"
                style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
        </div>
    </div>
</div>

{{-- 간트 일정 등록 모달 --}}
<div id="sch-overlay" onclick="closeSchModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10300;"></div>
<div id="sch-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10301;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:480px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">{{ __('planning.sch_modal_title') }}</h3>
        <button onclick="closeSchModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:2px;line-height:1;">&times;</button>
    </div>
    <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
        <div id="sch-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;"></div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_group') }} <span style="color:#ef4444;">*</span></label>
            <select id="sch-group" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">{{ __('planning.sch_loading') }}</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_title') }} <span style="color:#ef4444;">*</span></label>
            <input id="sch-title" type="text" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                   onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_start') }} <span style="color:#ef4444;">*</span></label>
                <input id="sch-start" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_end') }} <span style="color:#ef4444;">*</span></label>
                <input id="sch-end" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_assignee') }}</label>
            <select id="sch-assignee" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">{{ __('planning.sch_no_assignee') }}</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">{{ __('planning.sch_desc') }}</label>
            <textarea id="sch-desc" rows="3" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;resize:vertical;font-family:inherit;box-sizing:border-box;"
                      onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
        </div>
    </div>
    <div style="padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeSchModal()" style="padding:8px 18px;font-size:13px;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('common.cancel') }}</button>
        <button onclick="submitSchModal()" id="sch-submit-btn" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('planning.sch_register') }}</button>
    </div>
</div>

{{-- 기획서 적용 요구사항 상세 팝업 --}}
<div id="pa-detail-overlay" onclick="closePaDetailModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10500;"></div>
<div id="pa-detail-modal"
     style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
            z-index:10501;background:#fff;border-radius:16px;
            box-shadow:0 20px 60px rgba(0,0,0,.2);
            width:560px;max-width:calc(100vw - 32px);max-height:85vh;
            flex-direction:column;overflow:hidden;">
    {{-- 헤더 --}}
    <div style="padding:16px 20px 12px;border-bottom:1px solid #f0f0f0;display:flex;align-items:flex-start;justify-content:space-between;gap:10px;flex-shrink:0;">
        <div style="flex:1;min-width:0;">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px;">
                <span id="pa-modal-title" style="font-size:15px;font-weight:700;color:#18181b;"></span>
                <span id="pa-modal-badge"></span>
            </div>
            <div id="pa-modal-meta" style="font-size:12px;color:#6b7280;"></div>
        </div>
        <button onclick="closePaDetailModal()"
                style="flex-shrink:0;background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;line-height:1;padding:0;">&times;</button>
    </div>
    {{-- 본문 --}}
    <div style="overflow-y:auto;flex:1;padding:16px 20px;">
        <div id="pa-modal-desc-wrap" style="display:none;margin-bottom:14px;">
            <div style="font-size:11px;font-weight:600;color:#374151;letter-spacing:.05em;margin-bottom:5px;">{{ __('planning.pa_req_content') }}</div>
            <div id="pa-modal-desc" style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;padding:10px 14px;background:#f8fafc;border:1px solid #e4e4e7;border-radius:8px;"></div>
        </div>
        <div id="pa-modal-md-wrap" style="display:none;">
            <div style="font-size:11px;font-weight:600;color:#7c3aed;letter-spacing:.05em;margin-bottom:6px;">{{ __('planning.pa_inserted_content') }}</div>
            <div id="pa-modal-md" class="md-render"
                 style="border:1px solid #e4e4e7;border-radius:8px;padding:12px 16px;font-size:12px;background:#fafafa;max-height:260px;overflow-y:auto;"></div>
        </div>
    </div>
    {{-- 하단 액션 --}}
    <div id="pa-modal-actions"
         style="padding:12px 20px;border-top:1px solid #f0f0f0;display:flex;gap:8px;justify-content:flex-end;flex-shrink:0;flex-wrap:wrap;"></div>
</div>

<script>
{{-- 요구사항→기획서 적용 후 자동 트리거 (from_apply=1 파라미터) --}}
(async function () {
    if (new URLSearchParams(window.location.search).get('from_apply') !== '1') return;
    history.replaceState({}, '', window.location.pathname);

    // 1) 기획서 적용 요구사항 탭 활성화
    switchPaTab('active');

    // 2) 완료 메시지 표시
    showStatus(_PLAN_T.autoApplyMessage, '#059669');

    // 3) 웍스 기능 추천 카드로 스크롤 후 자동 실행
    setTimeout(async function () {
        const suggestCard = document.getElementById('feature-suggest-card');
        if (suggestCard) suggestCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        setTimeout(async function () { runSuggestFeatures(); }, 600);
    }, 400);
})();
</script>

@endsection
