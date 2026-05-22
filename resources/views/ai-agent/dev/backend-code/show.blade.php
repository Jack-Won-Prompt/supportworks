@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Header ──────────────────────────────────────────────────────── */
.bks-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.bks-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 3px; }
.bks-header-left p  { font-size:13px; color:#64748b; margin:0; }
.bks-meta { display:flex; gap:8px; flex-wrap:wrap; margin-top:6px; }
.bks-meta-chip { display:inline-flex; align-items:center; gap:4px; font-size:11.5px; padding:2px 10px; border-radius:99px; background:#f1f5f9; color:#475569; font-weight:600; }

/* ── Layout ──────────────────────────────────────────────────────── */
.bks-layout { display:grid; grid-template-columns:220px 1fr 300px; gap:16px; align-items:start; }
@media(max-width:1100px) { .bks-layout { grid-template-columns:200px 1fr; } }
@media(max-width:700px)  { .bks-layout { grid-template-columns:1fr; } }

/* ── Panel base ──────────────────────────────────────────────────── */
.bks-panel { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; overflow:hidden; }
.bks-panel-title { font-size:11.5px; font-weight:700; color:#7c3aed; text-transform:uppercase; letter-spacing:.06em; padding:10px 14px 8px; border-bottom:1.5px solid #ede8ff; }

/* ── File tree ───────────────────────────────────────────────────── */
.bks-tree { padding:8px 0; }
.bks-tree-dir { font-size:11px; font-weight:700; color:#94a3b8; padding:6px 14px 3px; text-transform:uppercase; letter-spacing:.05em; }
.bks-tree-item { display:flex; align-items:center; gap:7px; padding:6px 14px; cursor:pointer; font-size:12.5px; color:#475569; font-family:monospace; transition:background .1s; border-radius:0; }
.bks-tree-item:hover { background:#f5f3ff; }
.bks-tree-item.active { background:#ede9fe; color:#7c3aed; font-weight:700; }

/* ── Code panel ──────────────────────────────────────────────────── */
.bks-code-wrap { min-height:400px; }
.bks-code-toolbar { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; border-bottom:1.5px solid #ede8ff; background:#f8fafc; }
.bks-code-path { font-size:12px; font-family:monospace; color:#475569; font-weight:600; }
.bks-code-actions { display:flex; gap:6px; }
.bks-textarea { width:100%; min-height:420px; font-family:monospace; font-size:12.5px; color:#1e293b; background:#0f172a; border:none; resize:vertical; padding:16px; line-height:1.6; tab-size:4; box-sizing:border-box; }
.bks-textarea:read-only { color:#94a3b8; cursor:default; }
.bks-textarea:focus { outline:none; }

/* ── Spec preview panel ──────────────────────────────────────────── */
.bks-spec { }
.bks-spec-section { padding:12px 14px; border-bottom:1px solid #f1f5f9; }
.bks-spec-section:last-child { border-bottom:none; }
.bks-spec-section-title { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; margin:0 0 8px; }
.bks-route { display:flex; align-items:flex-start; gap:8px; padding:5px 0; font-size:12px; }
.bks-method { display:inline-block; padding:1px 7px; border-radius:4px; font-size:10.5px; font-weight:700; min-width:44px; text-align:center; }
.bks-method.GET    { background:#dbeafe; color:#1d4ed8; }
.bks-method.POST   { background:#dcfce7; color:#15803d; }
.bks-method.PUT,.bks-method.PATCH { background:#fef3c7; color:#92400e; }
.bks-method.DELETE { background:#fee2e2; color:#b91c1c; }
.bks-route-uri { font-family:monospace; color:#475569; font-size:11.5px; flex:1; word-break:break-all; }

/* ── Todo ────────────────────────────────────────────────────────── */
.bks-todo-wrap { margin-top:16px; }
.bks-todo-title { font-size:12px; font-weight:700; color:#1e1b2e; margin-bottom:8px; }
.bks-todo-item { display:flex; align-items:flex-start; gap:8px; padding:8px 12px; border-radius:8px; margin-bottom:6px; font-size:12.5px; }
.bks-todo-item.security_check { background:#fef2f2; border:1px solid #fca5a5; color:#b91c1c; }
.bks-todo-item.manual_test    { background:#fff7ed; border:1px solid #fed7aa; color:#9a3412; }
.bks-todo-item.review_required{ background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
.bks-todo-item.env_var_needed { background:#f0f9ff; border:1px solid #bae6fd; color:#0369a1; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.btn-primary   { display:inline-flex;align-items:center;gap:5px;padding:7px 15px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
.btn-primary:hover   { opacity:.9; }
.btn-secondary { display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fff;color:#7c3aed;border:1.5px solid #c4b5fd;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-secondary:hover { background:#f5f3ff; }
.btn-sm { padding:5px 11px; font-size:12px; }
.btn-danger    { display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#fff;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s; }
.btn-danger:hover { background:#fef2f2; }
.spinner { display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

{{-- Alpine component --}}
<div x-data="backendCodeShow()" x-init="init()">

<div class="bks-header">
    <div class="bks-header-left">
        <h1><code style="font-size:18px;color:#7c3aed;">{{ $tableName }}</code> — Backend Code</h1>
        <p>{{ $resourceName }} · Laravel 리소스</p>
        @if($hasCode && $decoded)
        <div class="bks-meta">
            <span class="bks-meta-chip">v{{ $artifact->version }}</span>
            <span class="bks-meta-chip">{{ count($decoded['files'] ?? []) }} 파일</span>
            <span class="bks-meta-chip">{{ count($decoded['routes'] ?? []) }} 라우트</span>
            @if(isset($decoded['$metadata']['model']))
            <span class="bks-meta-chip">{{ $decoded['$metadata']['model'] }}</span>
            @endif
            @if(isset($decoded['$metadata']['cost']))
            <span class="bks-meta-chip">${{ number_format($decoded['$metadata']['cost'], 3) }}</span>
            @endif
        </div>
        @endif
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <a href="{{ $indexUrl }}" class="btn-secondary btn-sm">← 목록</a>
        @if($historyUrl)
            <a href="{{ $historyUrl }}" class="btn-secondary btn-sm">버전 이력</a>
        @endif
        @if($hasCode)
            <a href="{{ $downloadUrl }}" class="btn-secondary btn-sm">Zip 다운로드</a>
            <button class="btn-danger btn-sm" @click="confirmDelete()">삭제</button>
        @endif
    </div>
</div>

@if(!$hasCode)
{{-- No code yet --}}
<div style="background:#fff;border:1.5px solid #ede8ff;border-radius:14px;padding:48px;text-align:center;">
    <div style="font-size:36px;margin-bottom:12px;">🗄️</div>
    <div style="font-size:15px;font-weight:700;color:#1e1b2e;margin-bottom:6px;">{{ $tableName }} — 코드 미생성</div>
    <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">웍스가 Laravel 코드(Model/Migration/Controller/Policy)를 자동 생성합니다.</div>
    <button class="btn-primary" :class="{ 'btn-loading': generating }" @click="generate()">
        <span x-show="!generating">코드 생성</span>
        <span x-show="generating" class="spinner"></span>
        <span x-show="generating">생성 중...</span>
    </button>
    <div x-show="genError" x-cloak style="margin-top:12px;font-size:13px;color:#b91c1c;" x-text="genError"></div>
</div>

@else
{{-- Has code --}}

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
    <div></div>
    <button class="btn-primary btn-sm" :class="{ 'btn-loading': generating }" @click="generate()">
        <span x-show="!generating">재생성</span>
        <span x-show="generating" class="spinner"></span>
        <span x-show="generating">생성 중...</span>
    </button>
</div>

<div class="bks-layout">

    {{-- ─ 파일 트리 ─────────────────────────────────────────────────── --}}
    <div class="bks-panel">
        <div class="bks-panel-title">파일 트리</div>
        <div class="bks-tree">
            @php
                $files = $decoded['files'] ?? [];
                $grouped = collect($files)->groupBy(fn($f) => dirname($f['path']) === '.' ? '' : dirname($f['path']));
            @endphp
            @foreach($grouped as $dir => $dirFiles)
                @if($dir)
                    <div class="bks-tree-dir">{{ $dir }}</div>
                @endif
                @foreach($dirFiles as $file)
                    @php
                        $fileName = basename($file['path']);
                        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
                        $icon = match($ext) { 'php' => '🐘', 'json' => '{}', 'yaml','yml' => '📄', default => '📄' };
                    @endphp
                    <div class="bks-tree-item" :class="{ active: selectedPath === '{{ addslashes($file['path']) }}' }"
                         @click="selectFile('{{ addslashes($file['path']) }}')">
                        <span>{{ $icon }}</span>
                        <span>{{ $fileName }}</span>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>

    {{-- ─ 코드 뷰어 ────────────────────────────────────────────────── --}}
    <div class="bks-panel bks-code-wrap">
        <div class="bks-code-toolbar">
            <span class="bks-code-path" x-text="selectedPath || '파일을 선택하세요'"></span>
            <div class="bks-code-actions">
                <button x-show="!editing && selectedPath" class="btn-secondary btn-sm" @click="startEdit()">편집</button>
                <button x-show="editing" class="btn-primary btn-sm" @click="saveEdit()">저장</button>
                <button x-show="editing" class="btn-secondary btn-sm" @click="cancelEdit()">취소</button>
            </div>
        </div>
        <textarea class="bks-textarea"
                  :readonly="!editing"
                  x-model="selectedContent"
                  placeholder="← 왼쪽에서 파일을 선택하세요"
                  spellcheck="false"></textarea>
    </div>

    {{-- ─ 명세 미리보기 + TODO ─────────────────────────────────────── --}}
    <div>
        {{-- Routes --}}
        <div class="bks-panel bks-spec" style="margin-bottom:14px;">
            <div class="bks-panel-title">라우트 ({{ count($decoded['routes'] ?? []) }})</div>
            @if(!empty($decoded['routes']))
            <div class="bks-spec-section">
                @foreach($decoded['routes'] as $route)
                <div class="bks-route">
                    <span class="bks-method {{ $route['method'] }}">{{ $route['method'] }}</span>
                    <span class="bks-route-uri">{{ $route['uri'] }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div style="padding:12px 14px;font-size:12px;color:#94a3b8;">라우트 정보 없음</div>
            @endif
        </div>

        {{-- Dependencies --}}
        @if(!empty($decoded['dependencies']))
        <div class="bks-panel" style="margin-bottom:14px;">
            <div class="bks-panel-title">패키지</div>
            <div style="padding:10px 14px;display:flex;flex-wrap:wrap;gap:8px;">
                @foreach($decoded['dependencies'] as $dep)
                <span style="display:inline-flex;align-items:center;gap:4px;font-size:11.5px;padding:3px 10px;border-radius:99px;background:#ede9fe;color:#6d28d9;font-weight:600;font-family:monospace;">
                    {{ $dep['name'] }}{{ isset($dep['version']) ? ':' . $dep['version'] : '' }}
                </span>
                @endforeach
            </div>
        </div>
        @endif

        {{-- TODO --}}
        @if(!empty($decoded['todo_items']))
        <div class="bks-todo-wrap">
            <div class="bks-todo-title">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
                TODO ({{ count($decoded['todo_items']) }})
            </div>
            @foreach($decoded['todo_items'] as $todo)
            <div class="bks-todo-item {{ $todo['type'] }}">
                <span>{{ match($todo['type'] ?? '') {
                    'security_check'  => '🔴',
                    'manual_test'     => '🟡',
                    'review_required' => '🟠',
                    'env_var_needed'  => '🔵',
                    default           => '⚪'
                } }}</span>
                <div>
                    <div style="font-weight:700;font-size:11.5px;">{{ ucfirst(str_replace('_', ' ', $todo['type'])) }}</div>
                    <div>{{ $todo['description'] }}</div>
                    @if(!empty($todo['file']))
                        <div style="font-size:11px;opacity:.75;font-family:monospace;margin-top:2px;">{{ $todo['file'] }}</div>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

</div>
@endif

</div>{{-- /x-data --}}

{{-- Error toast --}}
<div id="saveError" style="display:none;position:fixed;bottom:20px;right:20px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;padding:12px 18px;font-size:13px;color:#b91c1c;z-index:9999;"></div>

@endsection

@push('scripts')
<script>
const _BACKEND_FILES = @json($decoded['files'] ?? []);
const _GENERATE_URL  = '{{ $generateUrl }}';
const _UPDATE_URL    = '{{ $updateFileUrl }}';
const _DESTROY_URL   = '{{ $destroyUrl }}';
const _INDEX_URL     = '{{ $indexUrl }}';
const _CSRF          = document.querySelector('meta[name="csrf-token"]').content;

async function backendCodeShow() {
    return {
        fullFiles:       _BACKEND_FILES,
        selectedPath:    '',
        selectedContent: '',
        editing:         false,
        editBuffer:      '',
        generating:      false,
        genError:        '',

        init() {
            if (this.fullFiles.length > 0) {
                this.selectFile(this.fullFiles[0].path);
            }
        },

        selectFile(path) {
            const f = this.fullFiles.find(x => x.path === path);
            if (!f) return;
            if (this.editing) {
                if (!await __confirm('편집 중인 내용이 저장되지 않습니다. 계속할까요?')) return;
                this.editing = false;
            }
            this.selectedPath    = path;
            this.selectedContent = f.content;
        },

        startEdit() {
            this.editBuffer = this.selectedContent;
            this.editing    = true;
        },

        cancelEdit() {
            this.selectedContent = this.editBuffer;
            this.editing         = false;
        },

        async saveEdit() {
            try {
                const res = await fetch(_UPDATE_URL, {
                    method:  'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
                    body: JSON.stringify({ path: this.selectedPath, content: this.selectedContent }),
                });
                const data = await res.json();
                if (data.success) {
                    const f = this.fullFiles.find(x => x.path === this.selectedPath);
                    if (f) f.content = this.selectedContent;
                    this.editing = false;
                    this.showSaveMsg('저장되었습니다 (v' + data.version + ')');
                } else {
                    this.showSaveMsg(data.message || '저장 실패', true);
                }
            } catch (e) {
                this.showSaveMsg('저장 오류: ' + e.message, true);
            }
        },

        async generate() {
            this.generating = true;
            this.genError   = '';
            try {
                const res = await fetch(_GENERATE_URL, {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.genError   = data.message || '생성 실패';
                    this.generating = false;
                }
            } catch (e) {
                this.genError   = '오류: ' + e.message;
                this.generating = false;
            }
        },

        async confirmDelete() {
            if (!await __confirm('이 리소스의 코드를 삭제하시겠습니까?')) return;
            try {
                await fetch(_DESTROY_URL, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': _CSRF, 'Accept': 'application/json' },
                });
                window.location.href = _INDEX_URL;
            } catch (e) {
                alert('삭제 오류: ' + e.message);
            }
        },

        showSaveMsg(msg, isErr = false) {
            const el = document.getElementById('saveError');
            el.textContent = msg;
            el.style.display = 'block';
            el.style.background = isErr ? '#fef2f2' : '#f0fdf4';
            el.style.borderColor = isErr ? '#fca5a5' : '#bbf7d0';
            el.style.color       = isErr ? '#b91c1c' : '#166534';
            setTimeout(() => { el.style.display = 'none'; }, 3000);
        },
    };
}
</script>
@endpush
