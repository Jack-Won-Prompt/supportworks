@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────────────── */
.ia-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.ia-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.ia-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.ia-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────────────── */
.ia-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.ia-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.ia-btn.primary:hover { background:var(--t700,#6d28d9); }
.ia-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.ia-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.ia-btn.secondary:hover { background:#e2e8f0; }
.ia-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.ia-btn.ghost:hover { background:#f5f3ff; }
.ia-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Cards ─────────────────────────────────────────────────────────── */
.ia-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.ia-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Info grid ──────────────────────────────────────────────────────── */
.ia-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(140px, 1fr)); gap:10px; margin-bottom:16px; }
.ia-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.ia-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.ia-info-value { font-size:18px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Progress ───────────────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:16px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.section-list { list-style:none; margin:0; padding:0; }
.section-list li { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; font-size:13px; }
.section-list li:hover { background:#fdfcff; }
.section-status-icon { width:18px; text-align:center; flex-shrink:0; }
.section-title { flex:1; color:#374151; }

/* ── Stats ──────────────────────────────────────────────────────────── */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px; }
.stat-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:10px 14px; text-align:center; }
.stat-value { font-size:18px; font-weight:800; color:var(--t600,#7c3aed); }
.stat-label { font-size:11px; color:#64748b; font-weight:600; }

/* ── Mermaid diagrams ───────────────────────────────────────────────── */
.diagram-block { border:1.5px solid #ede8ff; border-radius:12px; overflow:hidden; margin-bottom:16px; }
.diagram-toolbar { display:flex; align-items:center; gap:8px; padding:10px 14px; border-bottom:1px solid #ede8ff; background:#fafafe; }
.diagram-toolbar-title { font-size:13px; font-weight:700; color:#1e1b2e; flex:1; }
.diagram-render { padding:20px; background:#fff; overflow-x:auto; }
.mermaid-container { text-align:center; min-height:80px; }

/* ── Markdown edit area ─────────────────────────────────────────────── */
.ia-md-area { width:100%; min-height:240px; padding:14px 16px; font-size:12.5px; line-height:1.8; color:#374151; font-family:'Courier New',monospace; border:none; resize:vertical; background:#fafafa; box-sizing:border-box; }
.ia-md-area:focus { outline:2px solid var(--t300,#c4b5fd); }

/* ── Banners ────────────────────────────────────────────────────────── */
.proceed-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; }
.proceed-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.proceed-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.proceed-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 22px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.proceed-start-btn:hover { background:#f5f3ff; }
.proceed-start-btn:disabled { opacity:.5; cursor:not-allowed; }
.blocked-banner { background:#f1f5f9; border:1.5px solid #e2e8f0; border-radius:14px; padding:20px 24px; text-align:center; color:#475569; }
.blocked-banner h3 { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 6px; }
.blocked-banner p  { font-size:13px; margin:0; }

/* ── Failed alert ───────────────────────────────────────────────────── */
.failed-alert { background:#fef3c7; border:1.5px solid #f59e0b; border-radius:10px; padding:12px 16px; display:flex; align-items:flex-start; gap:10px; margin-bottom:14px; }
.failed-alert-body { font-size:12.5px; color:#92400e; }
</style>
@endpush

@section('ai-agent-content')

{{-- JSON 데이터 아일랜드 --}}
<script type="application/json" id="ia-data">
{
    "initialState": "{{ $hasDocument ? ($hasIa ? 'complete' : 'ready') : 'no-doc' }}",
    "hasDocument": {{ $hasDocument ? 'true' : 'false' }},
    "hasIa": {{ $hasIa ? 'true' : 'false' }},
    "screenCount": {{ $screenCount }},
    "iaDiagram": {{ json_encode($meta['ia_diagram'] ?? '') }},
    "flowDiagram": {{ json_encode($meta['flow_diagram'] ?? '') }},
    "failedDiagrams": {{ json_encode($meta['failed_diagrams'] ?? []) }},
    "existingContent": {{ json_encode($artifact->content ?? '') }},
    "meta": {{ json_encode($meta) }},
    "startUrl": "{{ $startUrl }}",
    "sseUrlTpl": "{{ $sseUrlTpl }}",
    "saveUrl": "{{ $saveUrl }}",
    "exportUrl": "{{ $exportUrl }}",
    "regenerateUrl": "{{ $regenerateUrl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "documentUrl": "{{ $documentUrl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="iaIndex()" x-init="init()">

    {{-- ── 헤더 ─────────────────────────────────────────────────────────── --}}
    <div class="ia-header">
        <div class="ia-header-left">
            <h1>IA / 화면 흐름도</h1>
            <p>웍스 기획서를 기반으로 IA 구조도와 화면 흐름도를 자동 생성합니다.</p>
        </div>
        <div class="ia-header-right" x-show="state === 'complete'">
            <a :href="cfg.exportUrl" class="ia-btn secondary sm" title="Markdown 다운로드">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                내보내기
            </a>
            <a href="{{ $historyUrl }}" class="ia-btn ghost sm">버전 이력</a>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────────────── --}}
    {{-- 상태 A: 기획서 없음 ─────────────────────────────────────────────── --}}
    {{-- ─────────────────────────────────────────────────────────────────── --}}
    <template x-if="state === 'no-doc'">
        <div class="blocked-banner">
            <h3>기획서가 아직 준비되지 않았습니다</h3>
            <p>웍스 기획서를 먼저 작성해야 IA / 화면 흐름도를 생성할 수 있습니다.</p>
            <div style="margin-top:14px;">
                <a :href="cfg.documentUrl" class="ia-btn primary">웍스 기획서 작성하러 가기 →</a>
            </div>
        </div>
    </template>

    {{-- ─────────────────────────────────────────────────────────────────── --}}
    {{-- 상태 B: 생성 준비 ─────────────────────────────────────────────────  --}}
    {{-- ─────────────────────────────────────────────────────────────────── --}}
    <template x-if="state === 'ready'">
        <div>
            <div class="ia-section">
                <div class="ia-section-title">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    생성 정보
                </div>
                <div class="ia-info-grid">
                    <div class="ia-info-card">
                        <div class="ia-info-label">생성할 다이어그램</div>
                        <div class="ia-info-value">2개</div>
                    </div>
                    <div class="ia-info-card">
                        <div class="ia-info-label">화면 수</div>
                        <div class="ia-info-value">{{ $screenCount }}개</div>
                    </div>
                    <div class="ia-info-card">
                        <div class="ia-info-label">입력 소스</div>
                        <div class="ia-info-value" style="font-size:13px;">웍스 기획서</div>
                    </div>
                    <div class="ia-info-card">
                        <div class="ia-info-label">렌더링</div>
                        <div class="ia-info-value" style="font-size:13px;">Mermaid</div>
                    </div>
                </div>
            </div>

            <div class="proceed-banner">
                <div class="proceed-banner-text">
                    <h3>IA / 화면 흐름도 자동 생성</h3>
                    <p>웍스 기획서를 분석하여 IA 구조도와 화면 흐름도(Mermaid)를 생성합니다.</p>
                </div>
                <button class="proceed-start-btn" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    자동 생성 시작
                </button>
            </div>
        </div>
    </template>

    {{-- ─────────────────────────────────────────────────────────────────── --}}
    {{-- 상태 C: 생성 중 ──────────────────────────────────────────────────  --}}
    {{-- ─────────────────────────────────────────────────────────────────── --}}
    <template x-if="state === 'generating'">
        <div class="ia-section">
            <div class="ia-section-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                생성 중...
            </div>

            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progressPct}%`"></div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-bottom:14px;" x-text="statusMessage"></div>

            <ul class="section-list">
                <template x-for="s in sections" :key="s.key">
                    <li>
                        <span class="section-status-icon">
                            <template x-if="s.status === 'pending'">
                                <span style="color:#cbd5e1;">○</span>
                            </template>
                            <template x-if="s.status === 'processing'">
                                <span style="color:#a78bfa;animation:pulse 1s infinite;">◉</span>
                            </template>
                            <template x-if="s.status === 'done'">
                                <span style="color:#22c55e;">✓</span>
                            </template>
                            <template x-if="s.status === 'failed'">
                                <span style="color:#ef4444;">✗</span>
                            </template>
                        </span>
                        <span class="section-title" x-text="s.title"></span>
                    </li>
                </template>
            </ul>

            <div style="margin-top:14px;text-align:right;">
                <button class="ia-btn secondary sm" @click="cancelGeneration()">취소</button>
            </div>
        </div>
    </template>

    {{-- ─────────────────────────────────────────────────────────────────── --}}
    {{-- 상태 D: 생성 완료 ────────────────────────────────────────────────  --}}
    {{-- ─────────────────────────────────────────────────────────────────── --}}
    <template x-if="state === 'complete'">
        <div>
            {{-- 통계 --}}
            <div class="ia-section">
                <div class="ia-section-title">생성 완료</div>
                <div class="stats-grid">
                    <div class="stat-card"><div class="stat-value" x-text="completionStats.total ?? 2"></div><div class="stat-label">생성된 다이어그램</div></div>
                    <div class="stat-card"><div class="stat-value" x-text="completionStats.tokens_in ?? cfg.meta?.tokens_in ?? '-'"></div><div class="stat-label">입력 토큰</div></div>
                    <div class="stat-card"><div class="stat-value" x-text="completionStats.tokens_out ?? cfg.meta?.tokens_out ?? '-'"></div><div class="stat-label">출력 토큰</div></div>
                    <div class="stat-card"><div class="stat-value" x-text="'$' + (completionStats.cost_usd ?? cfg.meta?.cost_usd ?? 0).toFixed(4)"></div><div class="stat-label">비용</div></div>
                </div>
            </div>

            {{-- 실패한 다이어그램 재생성 --}}
            <template x-if="Object.keys(failedDiagrams).length > 0">
                <div class="failed-alert">
                    <span style="font-size:18px;">⚠️</span>
                    <div class="failed-alert-body">
                        <strong>일부 다이어그램 생성에 실패했습니다.</strong>
                        <ul style="margin:6px 0 0;padding-left:16px;">
                            <template x-for="[key, msg] in Object.entries(failedDiagrams)" :key="key">
                                <li>
                                    <span x-text="key === 'ia_diagram' ? 'IA 구조도' : '화면 흐름도'"></span>:
                                    <button class="ia-btn ghost sm" style="margin-left:6px;" @click="regenerate(key)" :disabled="regenerating[key]">
                                        <span x-show="!regenerating[key]">재생성</span>
                                        <span x-show="regenerating[key]">생성 중...</span>
                                    </button>
                                </li>
                            </template>
                        </ul>
                    </div>
                </div>
            </template>

            {{-- IA 구조도 --}}
            <div class="ia-section" x-show="iaDiagram">
                <div class="ia-section-title">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 11a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zM10 4a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1V4zM10 11a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1h-2a1 1 0 01-1-1v-2zM3 18a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z"/></svg>
                    IA 구조도
                    <button class="ia-btn ghost sm" style="margin-left:auto;" @click="regenerate('ia_diagram')" :disabled="regenerating.ia_diagram">
                        <span x-show="!regenerating.ia_diagram">재생성</span>
                        <span x-show="regenerating.ia_diagram">생성 중...</span>
                    </button>
                </div>
                <div class="diagram-render">
                    <div class="mermaid-container" id="ia-diagram-render"></div>
                </div>
            </div>

            {{-- 화면 흐름도 --}}
            <div class="ia-section" x-show="flowDiagram">
                <div class="ia-section-title">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                    화면 흐름도
                    <button class="ia-btn ghost sm" style="margin-left:auto;" @click="regenerate('flow_diagram')" :disabled="regenerating.flow_diagram">
                        <span x-show="!regenerating.flow_diagram">재생성</span>
                        <span x-show="regenerating.flow_diagram">생성 중...</span>
                    </button>
                </div>
                <div class="diagram-render">
                    <div class="mermaid-container" id="flow-diagram-render"></div>
                </div>
            </div>

            {{-- 마크다운 편집 --}}
            <div class="ia-section">
                <div class="ia-section-title">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    원본 Markdown 편집
                    <span style="font-size:11px;color:#94a3b8;font-weight:400;margin-left:4px;">(Ctrl+S 저장)</span>
                    <span style="margin-left:auto;font-size:11.5px;color:#64748b;font-weight:400;" x-show="saveStatus" x-text="saveStatus"></span>
                </div>
                <textarea class="ia-md-area" x-model="editContent" @keydown.ctrl.s.prevent="saveContent()"></textarea>
                <div style="margin-top:10px;display:flex;gap:8px;justify-content:flex-end;">
                    <button class="ia-btn secondary sm" @click="startGeneration()" :disabled="isGenerating">전체 재생성</button>
                    <button class="ia-btn primary sm" @click="saveContent()" :disabled="saving">
                        <span x-show="!saving">저장</span>
                        <span x-show="saving">저장 중...</span>
                    </button>
                </div>
            </div>

        </div>
    </template>

</div>

{{-- Mermaid CDN --}}
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>

@push('scripts')
<style>
@keyframes spin { to { transform: rotate(360deg); } }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:.4; } }
</style>
<script>
function iaIndex() {
    const cfg = JSON.parse(document.getElementById('ia-data').textContent);

    return {
        cfg,
        state: cfg.initialState,
        isGenerating: false,
        statusMessage: '',
        sections: [
            { key: 'ia_diagram',   title: 'IA 구조도',  status: 'pending' },
            { key: 'flow_diagram', title: '화면 흐름도', status: 'pending' },
        ],
        progressDone: 0,
        completionStats: {},
        iaDiagram:   cfg.iaDiagram,
        flowDiagram: cfg.flowDiagram,
        failedDiagrams: cfg.failedDiagrams || {},
        editContent: cfg.existingContent || '',
        saving: false,
        saveStatus: '',
        regenerating: { ia_diagram: false, flow_diagram: false },
        _es: null,

        get progressPct() {
            const total = this.sections.length;
            return total > 0 ? Math.min(100, Math.round((this.progressDone / total) * 100)) : 0;
        },

        init() {
            mermaid.initialize({ startOnLoad: false, theme: 'default', securityLevel: 'loose' });
            if (this.state === 'complete') {
                this.$nextTick(() => this.renderDiagrams());
            }
        },

        async renderDiagrams() {
            if (this.iaDiagram) {
                await this.renderOne('ia-diagram-render', this.iaDiagram);
            }
            if (this.flowDiagram) {
                await this.renderOne('flow-diagram-render', this.flowDiagram);
            }
        },

        async renderOne(containerId, code) {
            const el = document.getElementById(containerId);
            if (!el || !code.trim()) return;
            try {
                const id = 'mermaid-' + containerId + '-' + Date.now();
                const { svg } = await mermaid.render(id, code);
                el.innerHTML = svg;
            } catch (e) {
                el.innerHTML = '<pre style="font-size:12px;color:#ef4444;padding:8px;">다이어그램 렌더링 오류: ' + e.message + '</pre>';
            }
        },

        async startGeneration() {
            if (this.isGenerating) return;
            this.isGenerating = true;

            // POST to start
            let res;
            try {
                res = await fetch(this.cfg.startUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                });
                const json = await res.json();
                if (!json.success) {
                    alert(json.message || '시작 실패');
                    this.isGenerating = false;
                    return;
                }
                var sessionId = json.sessionId;
            } catch(e) {
                alert('서버 오류: ' + e.message);
                this.isGenerating = false;
                return;
            }

            this.state = 'generating';
            this.progressDone = 0;
            this.statusMessage = '연결 중...';
            this.sections.forEach(s => s.status = 'pending');

            const sseUrl = this.cfg.sseUrlTpl.replace('SESSION_ID', sessionId);
            const es = new EventSource(sseUrl);
            this._es = es;

            es.addEventListener('status', e => {
                const d = JSON.parse(e.data);
                this.statusMessage = d.message || '';
            });

            es.addEventListener('section_progress', e => {
                const d = JSON.parse(e.data);
                this.statusMessage = (d.status === 'processing' ? '생성 중: ' : '완료: ') + d.title;
                const sec = this.sections.find(s => s.key === d.key);
                if (sec) sec.status = d.status;
                this.progressDone = d.done || 0;
            });

            es.addEventListener('complete', e => {
                es.close();
                this._es = null;
                this.isGenerating = false;
                const d = JSON.parse(e.data);
                this.completionStats = d;
                this.state = 'complete';
                // Reload to get fresh artifact data (diagrams)
                window.location.reload();
            });

            es.addEventListener('error', e => {
                es.close();
                this._es = null;
                this.isGenerating = false;
                let msg = '생성 중 오류 발생';
                try { const d = JSON.parse(e.data); msg = d.message || msg; } catch {}
                alert(msg);
                this.state = this.cfg.hasIa ? 'complete' : 'ready';
            });

            es.onerror = () => {
                if (es.readyState === EventSource.CLOSED) {
                    this.isGenerating = false;
                    if (this.state === 'generating') {
                        this.state = this.cfg.hasIa ? 'complete' : 'ready';
                    }
                }
            };
        },

        cancelGeneration() {
            if (this._es) { this._es.close(); this._es = null; }
            this.isGenerating = false;
            this.state = this.cfg.hasIa ? 'complete' : 'ready';
        },

        async regenerate(key) {
            this.regenerating[key] = true;
            try {
                const res = await fetch(this.cfg.regenerateUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body: JSON.stringify({ diagram_key: key }),
                });
                const json = await res.json();
                if (json.success) {
                    window.location.reload();
                } else {
                    alert(json.message || '재생성 실패');
                }
            } catch(e) {
                alert('오류: ' + e.message);
            }
            this.regenerating[key] = false;
        },

        async saveContent() {
            if (this.saving) return;
            this.saving = true;
            this.saveStatus = '';
            try {
                const res = await fetch(this.cfg.saveUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body: JSON.stringify({ content: this.editContent }),
                });
                const json = await res.json();
                this.saveStatus = json.success ? '저장됨 (v' + json.version + ')' : '저장 실패';
            } catch(e) {
                this.saveStatus = '오류: ' + e.message;
            }
            this.saving = false;
            setTimeout(() => this.saveStatus = '', 3000);
        },
    };
}
</script>
@endpush

@endsection
