@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────── */
.fcs-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:16px; }
.fcs-header-left { display:flex; flex-direction:column; gap:4px; }
.fcs-breadcrumb { font-size:12px; color:#94a3b8; display:flex; align-items:center; gap:5px; }
.fcs-breadcrumb a { color:#7c3aed; text-decoration:none; }
.fcs-breadcrumb a:hover { text-decoration:underline; }
.fcs-title { font-size:20px; font-weight:800; color:#1e1b2e; display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.fcs-subtitle { font-size:13px; color:#64748b; margin-top:2px; }

/* ── Buttons ─────────────────────────────────────────── */
.fc-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.fc-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.fc-btn.primary:hover { background:var(--t700,#6d28d9); }
.fc-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.fc-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.fc-btn.secondary:hover { background:#e2e8f0; }
.fc-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.fc-btn.ghost:hover { background:#f5f3ff; }
.fc-btn.danger    { background:#fee2e2; color:#b91c1c; border:1.5px solid #fecaca; }
.fc-btn.danger:hover { background:#fecaca; }
.fc-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Meta chips ──────────────────────────────────────── */
.fcs-meta-row { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.fcs-chip { display:inline-flex; align-items:center; gap:4px; font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:99px; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.fcs-chip.model   { background:#f5f3ff; color:#5b21b6; border-color:#ddd6fe; }
.fcs-chip.cost    { background:#fff7ed; color:#c2410c; border-color:#fed7aa; }
.fcs-chip.tokens  { background:#f0fdf4; color:#15803d; border-color:#bbf7d0; }
.fcs-chip.version { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }

/* ── Stack badge ──────────────────────────────────────── */
.stack-badge { font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; }
.stack-badge.html  { background:#fef3c7; color:#92400e; }
.stack-badge.react { background:#dbeafe; color:#1e40af; }
.stack-badge.vue   { background:#d1fae5; color:#065f46; }

/* ── Workspace layout ───────────────────────────────── */
.fcs-workspace { display:grid; grid-template-columns:220px 1fr; gap:0; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; background:#fff; min-height:520px; margin-bottom:16px; }

/* ── File tree ───────────────────────────────────────── */
.fcs-tree { border-right:1.5px solid #ede8ff; background:#fafaf9; display:flex; flex-direction:column; min-height:520px; }
.fcs-tree-header { padding:10px 12px 8px; font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid #ede8ff; background:#f5f3ff; flex-shrink:0; }
.fcs-tree-body   { flex:1; overflow-y:auto; padding:6px 0; }
.fcs-tree-dir    { padding:4px 10px 4px 10px; font-size:11.5px; color:#64748b; font-weight:600; display:flex; align-items:center; gap:4px; }
.fcs-tree-file   { padding:5px 10px 5px 18px; font-size:12px; color:#334155; cursor:pointer; display:flex; align-items:center; gap:5px; border-radius:0; transition:background .1s; line-height:1.3; border-left:3px solid transparent; }
.fcs-tree-file:hover { background:#ede8ff; }
.fcs-tree-file.active { background:#f5f3ff; color:#7c3aed; font-weight:700; border-left-color:#7c3aed; }
.fcs-tree-empty  { padding:24px 12px; text-align:center; color:#94a3b8; font-size:12px; }

/* ── Code panel ──────────────────────────────────────── */
.fcs-code-panel { display:flex; flex-direction:column; min-height:520px; }
.fcs-code-toolbar { padding:8px 14px; border-bottom:1px solid #ede8ff; display:flex; align-items:center; gap:8px; flex-wrap:wrap; background:#f8f7ff; flex-shrink:0; }
.fcs-code-filepath { font-family:monospace; font-size:12px; color:#7c3aed; font-weight:600; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.fcs-code-purpose { font-size:11px; color:#94a3b8; }
.fcs-code-area    { flex:1; position:relative; min-height:400px; }
.fcs-code-textarea { width:100%; height:100%; min-height:400px; padding:16px; font-family:'Consolas','Monaco',monospace; font-size:12.5px; line-height:1.6; color:#1e1b2e; background:#fdfcff; border:none; resize:none; outline:none; box-sizing:border-box; }
.fcs-code-textarea[readonly] { background:#fdfcff; cursor:default; }
.fcs-code-textarea:not([readonly]) { background:#fff; border-top:2px solid #7c3aed; }
.fcs-code-placeholder { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; min-height:400px; color:#b0b8c9; gap:8px; }
.fcs-code-placeholder svg { opacity:.35; }

/* ── Preview panel ───────────────────────────────────── */
.fcs-preview-wrap { border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:16px; background:#fff; }
.fcs-preview-bar  { padding:10px 16px; background:#f8f7ff; border-bottom:1px solid #ede8ff; display:flex; align-items:center; gap:8px; }
.fcs-preview-bar-title { font-size:12.5px; font-weight:700; color:#1e1b2e; flex:1; }
.fcs-preview-iframe { width:100%; border:none; display:block; }

/* ── Section ──────────────────────────────────────────── */
.fcs-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:14px; }
.fcs-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 12px; display:flex; align-items:center; gap:8px; }

/* ── TODO items ──────────────────────────────────────── */
.todo-list { display:flex; flex-direction:column; gap:8px; }
.todo-item { display:flex; align-items:flex-start; gap:10px; padding:10px 14px; border-radius:10px; border:1.5px solid #ede8ff; }
.todo-badge { flex-shrink:0; font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:99px; white-space:nowrap; }
.todo-badge.review_required  { background:#dbeafe; color:#1d4ed8; }
.todo-badge.env_var_needed   { background:#fef3c7; color:#92400e; }
.todo-badge.manual_test      { background:#f0fdf4; color:#15803d; }
.todo-badge.security_check   { background:#fee2e2; color:#b91c1c; }
.todo-badge.default          { background:#f1f5f9; color:#475569; }
.todo-desc { font-size:13px; color:#334155; flex:1; }
.todo-file { font-family:monospace; font-size:11px; color:#7c3aed; margin-top:3px; }

/* ── Dependencies ────────────────────────────────────── */
.dep-list { display:flex; flex-wrap:wrap; gap:8px; }
.dep-tag  { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; background:#f5f3ff; border:1.5px solid #ddd6fe; border-radius:8px; font-size:12px; color:#5b21b6; font-weight:600; }
.dep-version { font-size:10.5px; font-weight:400; color:#94a3b8; }

/* ── Notes ───────────────────────────────────────────── */
.note-item { font-size:13px; color:#334155; padding:8px 12px; background:#f8f7ff; border-left:3px solid #c4b5fd; border-radius:0 6px 6px 0; margin-bottom:6px; }

/* ── Alerts ──────────────────────────────────────────── */
.fcs-no-code { text-align:center; padding:60px 24px; color:#94a3b8; }
.fcs-no-code h3 { font-size:16px; font-weight:700; color:#475569; margin-bottom:8px; }
.fcs-no-code p  { font-size:13.5px; margin-bottom:20px; }

/* ── Delete modal ────────────────────────────────────── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.modal-box { background:#fff; border-radius:16px; padding:28px; max-width:400px; width:100%; }
.modal-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 12px; }
.modal-box p  { font-size:13.5px; color:#64748b; margin:0 0 20px; }
.modal-actions { display:flex; gap:8px; justify-content:flex-end; }

@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

{{-- Page data for Alpine --}}
<script type="application/json" id="fcs-data">
{
    "hasCode": {{ $hasCode ? 'true' : 'false' }},
    "generateUrl": "{{ $generateUrl }}",
    "previewUrl": "{{ $previewUrl }}",
    "updateFileUrl": "{{ $updateFileUrl }}",
    "destroyUrl": "{{ $destroyUrl }}",
    "indexUrl": "{{ $indexUrl }}",
    "csrfToken": "{{ csrf_token() }}",
    "files": @if($decoded) {{ Illuminate\Support\Js::from(collect($decoded['files'] ?? [])->map(fn($f) => ['path' => $f['path'], 'purpose' => $f['purpose'] ?? '', 'lines' => $f['lines'] ?? (substr_count($f['content'] ?? '', "\n") + 1)])) }} @else [] @endif,
    "firstFilePath": @if($decoded && !empty($decoded['files'])) "{{ addslashes($decoded['files'][0]['path'] ?? '') }}" @else null @endif,
    "mainFilePath": @if($decoded) {{ Illuminate\Support\Js::from($decoded['main_file_path'] ?? null) }} @else null @endif,
    "fullFiles": @if($decoded) {{ Illuminate\Support\Js::from($decoded['files'] ?? []) }} @else [] @endif
}
</script>

<div x-data="frontendCodeShow()" x-init="init()">

    {{-- 헤더 --}}
    <div class="fcs-header">
        <div class="fcs-header-left">
            <div class="fcs-breadcrumb">
                <a href="{{ $indexUrl }}">Frontend Code</a>
                <span>/</span>
                <span>{{ $screen->screen_id }}</span>
            </div>
            <div class="fcs-title">
                <span class="stack-badge {{ $stack->value }}">{{ $stack->label() }}</span>
                [{{ $screen->screen_id }}] {{ $screen->title }}
            </div>
            @if($screen->description)
            <div class="fcs-subtitle">{{ $screen->description }}</div>
            @endif
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-start;padding-top:2px;">
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="fc-btn secondary sm" target="_blank">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                버전 이력
            </a>
            @endif
            @if($hasCode)
            <a href="{{ $downloadUrl }}" class="fc-btn secondary sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                ZIP 다운로드
            </a>
            <button class="fc-btn ghost sm" :disabled="isGenerating" @click="generate()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isGenerating ? 'animation:spin 1s linear infinite' : ''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                <span x-text="isGenerating ? '생성 중...' : '재생성'"></span>
            </button>
            <button class="fc-btn danger sm" @click="showDeleteConfirm = true">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                삭제
            </button>
            @else
            <button class="fc-btn primary" :disabled="isGenerating" @click="generate()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isGenerating ? 'animation:spin 1s linear infinite' : ''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span x-text="isGenerating ? '생성 중...' : '웍스 코드 생성'"></span>
            </button>
            @endif
        </div>
    </div>

    {{-- 생성 메시지 --}}
    <template x-if="generateMessage">
        <div :class="generateOk ? 'fcs-section' : 'fcs-section'" :style="generateOk ? 'border-color:#bbf7d0;background:#f0fdf4' : 'border-color:#fecaca;background:#fff1f2'" style="padding:12px 16px;margin-bottom:14px;">
            <span style="font-size:13px;" x-text="generateMessage"></span>
        </div>
    </template>

    @if($hasCode && $artifact)
    {{-- 메타 정보 --}}
    @php
        $meta = $decoded['$metadata'] ?? [];
        $generatedAt = $meta['generated_at'] ?? $artifact->meta['generated_at'] ?? null;
    @endphp
    <div class="fcs-meta-row">
        @if($artifact->version > 1)
        <span class="fcs-chip version">v{{ $artifact->version }}</span>
        @endif
        @if(!empty($meta['model']))
        <span class="fcs-chip model">{{ Str::after($meta['model'], 'claude-') }}</span>
        @endif
        @if(!empty($meta['tokens_in']))
        <span class="fcs-chip tokens">↑{{ number_format($meta['tokens_in']) }} / ↓{{ number_format($meta['tokens_out'] ?? 0) }} tok</span>
        @endif
        @if(!empty($meta['cost_usd']))
        <span class="fcs-chip cost">${{ number_format($meta['cost_usd'], 4) }}</span>
        @endif
        @if($generatedAt)
        <span class="fcs-chip">{{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i') }}</span>
        @endif
        @if(!empty($decoded['files']))
        <span class="fcs-chip">{{ count($decoded['files']) }}개 파일</span>
        @endif
        @if($screen->figma_node_id ?? false)
        <a href="#" class="fcs-chip" style="color:#7c3aed;text-decoration:none;background:#f5f3ff;border-color:#c4b5fd;" target="_blank">
            <svg width="10" height="10" viewBox="0 0 38 57" fill="none"><path d="M19 28.5a9.5 9.5 0 1 1 19 0 9.5 9.5 0 0 1-19 0z" fill="#1ABCFE"/><path d="M0 47.5A9.5 9.5 0 0 1 9.5 38H19v9.5a9.5 9.5 0 0 1-19 0z" fill="#0ACF83"/><path d="M19 0v19h9.5a9.5 9.5 0 0 0 0-19H19z" fill="#FF7262"/><path d="M0 9.5A9.5 9.5 0 0 0 9.5 19H19V0H9.5A9.5 9.5 0 0 0 0 9.5z" fill="#F24E1E"/><path d="M0 28.5A9.5 9.5 0 0 0 9.5 38H19V19H9.5A9.5 9.5 0 0 0 0 28.5z" fill="#A259FF"/></svg>
            Figma
        </a>
        @endif
    </div>

    {{-- 파일 트리 + 코드 뷰어 --}}
    <div class="fcs-workspace">
        {{-- 파일 트리 --}}
        <div class="fcs-tree">
            <div class="fcs-tree-header">파일 목록</div>
            <div class="fcs-tree-body">
                @if(!empty($decoded['files']))
                    @php
                        $treeItems = collect($decoded['files'])->map(function ($f) {
                            $parts = explode('/', $f['path']);
                            return [
                                'path'    => $f['path'],
                                'name'    => end($parts),
                                'dir'     => count($parts) > 1 ? implode('/', array_slice($parts, 0, -1)) : null,
                                'purpose' => $f['purpose'] ?? '',
                                'lines'   => $f['lines'] ?? (substr_count($f['content'] ?? '', "\n") + 1),
                            ];
                        });
                        $grouped = $treeItems->groupBy('dir');
                    @endphp
                    @foreach($grouped as $dir => $files)
                        @if($dir)
                        <div class="fcs-tree-dir">
                            <svg width="12" height="12" fill="#c4b5fd" viewBox="0 0 24 24"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                            {{ $dir }}
                        </div>
                        @endif
                        @foreach($files as $file)
                        <div class="fcs-tree-file"
                             :class="selectedPath === '{{ addslashes($file['path']) }}' ? 'active' : ''"
                             @click="selectFile('{{ addslashes($file['path']) }}')">
                            <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $file['name'] }}</span>
                            <span style="font-size:10px;color:#94a3b8;flex-shrink:0;">{{ $file['lines'] }}L</span>
                        </div>
                        @endforeach
                    @endforeach
                @else
                <div class="fcs-tree-empty">파일이 없습니다</div>
                @endif
            </div>
        </div>

        {{-- 코드 패널 --}}
        <div class="fcs-code-panel">
            <template x-if="selectedPath">
                <div style="display:flex;flex-direction:column;height:100%;">
                    <div class="fcs-code-toolbar">
                        <span class="fcs-code-filepath" x-text="selectedPath"></span>
                        <span class="fcs-code-purpose" x-text="selectedPurpose"></span>
                        <div style="display:flex;gap:8px;flex-shrink:0;">
                            <button class="fc-btn secondary sm" @click="copyCode()">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="9" y="9" width="13" height="13" rx="2" ry="2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                                <span x-text="copied ? '복사됨!' : '복사'"></span>
                            </button>
                            <template x-if="!isEditing">
                                <button class="fc-btn ghost sm" @click="startEdit()">편집</button>
                            </template>
                            <template x-if="isEditing">
                                <div style="display:flex;gap:4px;">
                                    <button class="fc-btn primary sm" :disabled="isSaving" @click="saveEdit()">
                                        <span x-text="isSaving ? '저장 중...' : '저장'"></span>
                                    </button>
                                    <button class="fc-btn secondary sm" @click="cancelEdit()">취소</button>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="fcs-code-area">
                        <textarea class="fcs-code-textarea"
                                  x-ref="codeTextarea"
                                  :readonly="!isEditing"
                                  x-model="editContent"
                                  spellcheck="false"></textarea>
                    </div>
                </div>
            </template>
            <template x-if="!selectedPath">
                <div class="fcs-code-placeholder">
                    <svg width="40" height="40" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span style="font-size:13px;color:#94a3b8;">왼쪽에서 파일을 선택하세요</span>
                </div>
            </template>
        </div>
    </div>

    {{-- 미리보기 --}}
    <div class="fcs-preview-wrap">
        <div class="fcs-preview-bar">
            <span class="fcs-preview-bar-title">
                베스트에포트 미리보기
                <span style="font-size:11px;font-weight:400;color:#94a3b8;margin-left:6px;">(실제 빌드 환경과 다를 수 있습니다)</span>
            </span>
            <div style="display:flex;gap:8px;">
                <button class="fc-btn secondary sm" @click="reloadPreview()">새로고침</button>
                <button class="fc-btn ghost sm" @click="togglePreview()" x-text="showPreview ? '숨기기' : '미리보기 열기'"></button>
            </div>
        </div>
        <div x-show="showPreview" x-cloak>
            <iframe x-ref="previewFrame"
                    class="fcs-preview-iframe"
                    :src="previewSrc"
                    sandbox="allow-scripts allow-same-origin"
                    style="height:500px;"
                    @load="previewLoaded = true"></iframe>
        </div>
    </div>

    {{-- TODO 항목 --}}
    @if(!empty($decoded['todo_items']))
    <div class="fcs-section">
        <div class="fcs-section-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            TODO 항목
            <span style="font-size:11.5px;font-weight:400;color:#94a3b8;">{{ count($decoded['todo_items']) }}개</span>
        </div>
        <div class="todo-list">
            @foreach($decoded['todo_items'] as $todo)
            @php
                $badgeTypes = ['review_required', 'env_var_needed', 'manual_test', 'security_check'];
                $badgeClass = in_array($todo['type'] ?? '', $badgeTypes) ? $todo['type'] : 'default';
                $badgeLabels = [
                    'review_required' => '코드 리뷰 필요',
                    'env_var_needed'  => '환경변수 필요',
                    'manual_test'     => '수동 테스트',
                    'security_check'  => '보안 확인',
                    'default'         => $todo['type'] ?? 'TODO',
                ];
                $badgeLabel = $badgeLabels[$badgeClass] ?? ($todo['type'] ?? 'TODO');
            @endphp
            <div class="todo-item">
                <span class="todo-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                <div>
                    <div class="todo-desc">{{ $todo['description'] ?? '' }}</div>
                    @if(!empty($todo['file']))
                    <div class="todo-file">
                        {{ $todo['file'] }}@if(!empty($todo['line'])):{{ $todo['line'] }}@endif
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 의존성 --}}
    @if(!empty($decoded['dependencies']))
    <div class="fcs-section">
        <div class="fcs-section-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            의존성 (Dependencies)
            <span style="font-size:11.5px;font-weight:400;color:#94a3b8;">{{ count($decoded['dependencies']) }}개</span>
        </div>
        <div class="dep-list">
            @foreach($decoded['dependencies'] as $dep)
            <div class="dep-tag" title="{{ $dep['purpose'] ?? '' }}">
                {{ $dep['name'] ?? '' }}
                @if(!empty($dep['version']))
                <span class="dep-version">{{ $dep['version'] }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- 구현 노트 --}}
    @if(!empty($decoded['implementation_notes']))
    <div class="fcs-section">
        <div class="fcs-section-title">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            구현 노트
        </div>
        @foreach($decoded['implementation_notes'] as $note)
        <div class="note-item">{{ $note }}</div>
        @endforeach
    </div>
    @endif

    @else
    {{-- 코드 없음 --}}
    <div class="fcs-section">
        <div class="fcs-no-code">
            <h3>아직 코드가 생성되지 않았습니다</h3>
            <p>웍스가 이 화면의 프로덕션 수준 Frontend 코드를 생성합니다.<br>ERD, API 명세, RBAC, 디자인 시스템을 모두 통합하여 생성합니다.</p>
            <button class="fc-btn primary" style="margin:0 auto;" :disabled="isGenerating" @click="generate()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isGenerating ? 'animation:spin 1s linear infinite' : ''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span x-text="isGenerating ? '생성 중...' : '웍스 코드 생성 시작'"></span>
            </button>
        </div>
    </div>
    @endif

    {{-- 삭제 확인 모달 --}}
    <template x-if="showDeleteConfirm">
        <div class="modal-overlay" @click.self="showDeleteConfirm = false">
            <div class="modal-box">
                <h3>코드 산출물 삭제</h3>
                <p>[{{ $screen->screen_id }}] {{ $screen->title }}의 Frontend 코드를 삭제합니다. 이 작업은 되돌릴 수 없습니다.</p>
                <div class="modal-actions">
                    <button class="fc-btn secondary" @click="showDeleteConfirm = false">취소</button>
                    <button class="fc-btn danger" :disabled="isDeleting" @click="confirmDelete()">
                        <span x-text="isDeleting ? '삭제 중...' : '삭제 확인'"></span>
                    </button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function frontendCodeShow() {
    return {
        cfg: {},
        files: [],
        fullFiles: [],

        selectedPath: null,
        selectedPurpose: '',
        editContent: '',
        originalContent: '',
        isEditing: false,
        isSaving: false,
        copied: false,

        showPreview: false,
        previewSrc: '',
        previewLoaded: false,

        isGenerating: false,
        generateMessage: null,
        generateOk: false,

        showDeleteConfirm: false,
        isDeleting: false,

        init() {
            const raw = document.getElementById('fcs-data')?.textContent;
            if (raw) {
                this.cfg       = JSON.parse(raw);
                this.files     = this.cfg.files     || [];
                this.fullFiles = this.cfg.fullFiles || [];
            }

            const startPath = this.cfg.mainFilePath || this.cfg.firstFilePath;
            if (startPath) this.selectFile(startPath);
        },

        selectFile(path) {
            this.selectedPath    = path;
            const meta           = this.files.find(f => f.path === path);
            this.selectedPurpose = meta?.purpose || '';
            const full           = this.fullFiles.find(f => f.path === path);
            this.editContent     = full?.content || '';
            this.originalContent = this.editContent;
            this.isEditing       = false;
        },

        startEdit() {
            this.isEditing = true;
            this.$nextTick(() => this.$refs.codeTextarea?.focus());
        },

        cancelEdit() {
            this.editContent = this.originalContent;
            this.isEditing   = false;
        },

        async saveEdit() {
            if (this.isSaving) return;
            this.isSaving = true;

            try {
                const res = await fetch(this.cfg.updateFileUrl, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ path: this.selectedPath, content: this.editContent }),
                });
                const data = await res.json();
                if (data.success) {
                    this.originalContent = this.editContent;
                    const idx = this.fullFiles.findIndex(f => f.path === this.selectedPath);
                    if (idx !== -1) this.fullFiles[idx].content = this.editContent;
                    this.isEditing = false;
                    this.showMsg('저장되었습니다. (v' + data.version + ')', true);
                } else {
                    this.showMsg('저장 실패: ' + (data.message || '알 수 없는 오류'), false);
                }
            } catch (e) {
                this.showMsg('저장 실패: ' + e.message, false);
            } finally {
                this.isSaving = false;
            }
        },

        async copyCode() {
            if (!this.editContent) return;
            try {
                await navigator.clipboard.writeText(this.editContent);
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 2000);
            } catch {}
        },

        togglePreview() {
            this.showPreview = !this.showPreview;
            if (this.showPreview && !this.previewSrc) {
                this.previewSrc = this.cfg.previewUrl + '?t=' + Date.now();
            }
        },

        reloadPreview() {
            if (!this.cfg.previewUrl) return;
            this.previewSrc    = '';
            this.previewLoaded = false;
            this.$nextTick(() => {
                this.previewSrc = this.cfg.previewUrl + '?t=' + Date.now();
            });
        },

        async generate() {
            if (this.isGenerating) return;
            this.isGenerating    = true;
            this.generateMessage = null;

            try {
                const res = await fetch(this.cfg.generateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();

                if (data.success) {
                    this.showMsg(
                        `생성 완료! ${data.files_count}개 파일 (v${data.version}) · ↑${(data.tokens_in||0).toLocaleString()} / ↓${(data.tokens_out||0).toLocaleString()} tok · $${data.cost_usd}`,
                        true
                    );
                    // Update local state
                    this.fullFiles = data.files || [];
                    this.files     = this.fullFiles.map(f => ({
                        path:    f.path,
                        purpose: f.purpose || '',
                        lines:   f.lines || ((f.content || '').split('\n').length),
                    }));
                    const startPath = data.files?.[0]?.path;
                    if (startPath) this.selectFile(startPath);

                    // Refresh preview if open
                    if (this.showPreview) this.reloadPreview();
                } else {
                    this.showMsg('생성 실패: ' + (data.message || '알 수 없는 오류'), false);
                }
            } catch (e) {
                this.showMsg('오류: ' + e.message, false);
            } finally {
                this.isGenerating = false;
            }
        },

        async confirmDelete() {
            if (this.isDeleting) return;
            this.isDeleting = true;

            try {
                const res = await fetch(this.cfg.destroyUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = this.cfg.indexUrl;
                } else {
                    this.showDeleteConfirm = false;
                    this.showMsg('삭제 실패: ' + (data.message || '알 수 없는 오류'), false);
                }
            } catch (e) {
                this.showDeleteConfirm = false;
                this.showMsg('삭제 오류: ' + e.message, false);
            } finally {
                this.isDeleting = false;
            }
        },

        showMsg(text, ok) {
            this.generateMessage = text;
            this.generateOk      = ok;
        },
    };
}
</script>
@endpush
