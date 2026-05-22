@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────── */
.erd-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.erd-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.erd-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.erd-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────── */
.erd-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.erd-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.erd-btn.primary:hover { background:var(--t700,#6d28d9); }
.erd-btn.primary:disabled, .erd-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.erd-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.erd-btn.secondary:hover { background:#e2e8f0; }
.erd-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.erd-btn.ghost:hover { background:#f5f3ff; }
.erd-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Cards ──────────────────────────────────────────────────── */
.erd-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.erd-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Info grid ──────────────────────────────────────────────── */
.erd-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px; }
.erd-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.erd-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.erd-info-value { font-size:18px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Progress ───────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }

/* ── Mermaid ────────────────────────────────────────────────── */
.diagram-block { border:1.5px solid #ede8ff; border-radius:12px; overflow:hidden; margin-bottom:18px; }
.diagram-toolbar { display:flex; align-items:center; gap:8px; padding:10px 14px; border-bottom:1px solid #ede8ff; background:#fafafe; }
.diagram-toolbar-title { font-size:13px; font-weight:700; color:#1e1b2e; flex:1; }
.diagram-render { padding:20px; background:#fff; overflow-x:auto; text-align:center; min-height:80px; }

/* ── Table card ─────────────────────────────────────────────── */
.tbl-card { border:1.5px solid #ede8ff; border-radius:12px; margin-bottom:14px; overflow:hidden; }
.tbl-card-header { display:flex; align-items:center; justify-content:space-between; padding:10px 14px; background:#fafafe; border-bottom:1px solid #ede8ff; cursor:pointer; }
.tbl-card-name { font-size:13px; font-weight:800; color:#1e1b2e; font-family:monospace; }
.tbl-card-desc { font-size:11.5px; color:#64748b; margin-left:8px; }
.tbl-card-body { padding:0; }
.tbl-col-row { display:grid; grid-template-columns:180px 160px 1fr; gap:0; border-bottom:1px solid #f1f5f9; font-size:12px; }
.tbl-col-row:last-child { border-bottom:none; }
.tbl-col-cell { padding:6px 12px; }
.tbl-col-cell.name { font-family:monospace; font-weight:700; color:#1e1b2e; }
.tbl-col-cell.type { color:#7c3aed; font-family:monospace; }
.tbl-col-cell.flags { color:#64748b; display:flex; gap:4px; flex-wrap:wrap; align-items:center; }
.tbl-flag { font-size:10px; font-weight:700; padding:1px 6px; border-radius:99px; }
.tbl-flag.pk { background:#fef3c7; color:#92400e; }
.tbl-flag.fk { background:#e0e7ff; color:#4338ca; }
.tbl-flag.uk { background:#d1fae5; color:#065f46; }
.tbl-flag.nn { background:#f1f5f9; color:#64748b; }
.tbl-derived { font-size:11px; color:#94a3b8; padding:6px 12px 8px; }

/* ── Design notes ───────────────────────────────────────────── */
.design-notes { background:#f8f5ff; border-radius:10px; padding:14px 16px; font-size:13px; color:#374151; line-height:1.7; white-space:pre-wrap; }

/* ── Prereq / Banner ────────────────────────────────────────── */
.prereq-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; border:1.5px solid #e2e8f0; margin-bottom:8px; font-size:13px; }
.prereq-item.ok   { background:#f0fdf4; border-color:#bbf7d0; }
.prereq-item.miss { background:#fffbeb; border-color:#fde68a; }
.prereq-item.none { background:#f8fafc; border-color:#e2e8f0; }
.proceed-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; }
.proceed-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.proceed-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.proceed-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 22px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.proceed-start-btn:hover { background:#f5f3ff; }
.proceed-start-btn:disabled { opacity:.5; cursor:not-allowed; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

{{-- JSON 데이터 아일랜드 --}}
<script type="application/json" id="erd-data">
{
    "hasDoc": {{ $hasDoc ? 'true' : 'false' }},
    "hasErd": {{ $hasErd ? 'true' : 'false' }},
    "screenCount": {{ $screenCount }},
    "reqCount": {{ $reqCount }},
    "tablesCount": {{ $tablesCount }},
    "mermaid": {{ json_encode($erdData['mermaid_diagram'] ?? '') }},
    "tables": {{ json_encode(array_values($erdData['tables'] ?? [])) }},
    "relationships": {{ json_encode($erdData['relationships'] ?? []) }},
    "designNotes": {{ json_encode($erdData['design_notes'] ?? '') }},
    "relatedReqs": {{ json_encode($erdData['related_requirements'] ?? []) }},
    "relatedScreens": {{ json_encode($erdData['related_screens'] ?? []) }},
    "startUrl": "{{ $startUrl }}",
    "sseUrlTpl": "{{ $sseUrlTpl }}",
    "saveUrl": "{{ $saveUrl }}",
    "exportUrl": "{{ $exportUrl }}",
    "regenerateUrl": "{{ $regenerateUrl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="erdIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="erd-header">
        <div class="erd-header-left">
            <h1>ERD — 데이터 모델</h1>
            <p>기획서·요구사항·화면 정보를 분석하여 정규화된 데이터 모델을 자동 설계합니다.</p>
        </div>
        <div class="erd-header-right" x-show="hasErd">
            <div style="position:relative;" x-data="{ open:false }">
                <button class="erd-btn secondary sm" @click="open=!open">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    내보내기 ▾
                </button>
                <div x-show="open" @click.outside="open=false"
                     style="position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;min-width:160px;z-index:20;box-shadow:0 4px 12px rgba(0,0,0,.08);">
                    <a :href="cfg.exportUrl + '?format=mermaid'" class="erd-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">Mermaid (.md)</a>
                    <a :href="cfg.exportUrl + '?format=sql'"     class="erd-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">SQL (.sql)</a>
                    <a :href="cfg.exportUrl + '?format=dbml'"    class="erd-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">DBML (.dbml)</a>
                </div>
            </div>
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="erd-btn ghost sm">버전 이력</a>
            @endif
        </div>
    </div>

    {{-- ── 상태 A: 생성 전 ── --}}
    <template x-if="!hasErd && !isGenerating">
        <div>
            <div class="erd-section">
                <div class="erd-section-title">사전 조건</div>
                <div class="prereq-item {{ $hasDoc ? 'ok' : 'miss' }}">
                    <span>{{ $hasDoc ? '✅' : '⚠️' }}</span>
                    <span>웍스 기획서</span>
                    @if(!$hasDoc)<span style="font-size:11.5px;color:#92400e;margin-left:auto;">
                        <a href="{{ route('ai-agent.projects.planning.document', $project) }}" style="color:#92400e;font-weight:700;">→ 작성하러 가기</a>
                    </span>@endif
                </div>
                <div class="prereq-item {{ $reqCount > 0 ? 'ok' : 'none' }}">
                    <span>{{ $reqCount > 0 ? '✅' : '⚠️' }}</span>
                    <span>TO-BE 요구사항</span>
                    <span style="font-size:12px;color:#64748b;margin-left:auto;">{{ $reqCount }}건</span>
                </div>
                <div class="prereq-item {{ $screenCount > 0 ? 'ok' : 'none' }}">
                    <span>{{ $screenCount > 0 ? '✅' : '⚠️' }}</span>
                    <span>화면 목록</span>
                    <span style="font-size:12px;color:#64748b;margin-left:auto;">{{ $screenCount }}건</span>
                </div>
            </div>

            <div class="proceed-banner">
                <div class="proceed-banner-text">
                    <h3>ERD 자동 생성 (웍스 Tool Use)</h3>
                    <p>기획서·요구사항·화면 정보를 분석하여 테이블·컬럼·인덱스·외래키를 자동 설계합니다.</p>
                </div>
                <button class="proceed-start-btn" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    ERD 자동 생성
                </button>
            </div>
        </div>
    </template>

    {{-- ── 상태 B: 생성 중 ── --}}
    <template x-if="isGenerating">
        <div class="erd-section">
            <div class="erd-section-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                ERD 설계 중...
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progressPct}%`"></div>
            </div>
            <div style="font-size:12.5px;color:#64748b;" x-text="statusMessage"></div>
        </div>
    </template>

    {{-- ── 상태 C: 완료 ── --}}
    <template x-if="hasErd && !isGenerating">
        <div>
            {{-- 통계 --}}
            <div class="erd-info-grid" style="margin-bottom:18px;">
                <div class="erd-info-card">
                    <div class="erd-info-label">테이블 수</div>
                    <div class="erd-info-value" x-text="tables.length"></div>
                </div>
                <div class="erd-info-card">
                    <div class="erd-info-label">관계 수</div>
                    <div class="erd-info-value" x-text="relationships.length"></div>
                </div>
                <div class="erd-info-card">
                    <div class="erd-info-label">출처 요구사항</div>
                    <div class="erd-info-value" x-text="relatedReqs.length + '건'"></div>
                </div>
                <div class="erd-info-card">
                    <div class="erd-info-label">출처 화면</div>
                    <div class="erd-info-value" x-text="relatedScreens.length + '건'"></div>
                </div>
            </div>

            {{-- Mermaid 다이어그램 --}}
            <div class="diagram-block" x-show="mermaid">
                <div class="diagram-toolbar">
                    <span class="diagram-toolbar-title">ERD 다이어그램 (Mermaid)</span>
                    <button class="erd-btn secondary sm" @click="copyMermaid()">복사</button>
                </div>
                <div class="diagram-render">
                    <div class="mermaid" x-ref="mermaidContainer" x-text="'```mermaid\n' + mermaid + '\n```'"></div>
                </div>
            </div>

            {{-- 테이블 목록 --}}
            <div class="erd-section">
                <div class="erd-section-title" style="margin-bottom:12px;">
                    테이블 목록 (<span x-text="tables.length"></span>개)
                </div>
                <template x-for="tbl in tables" :key="tbl.name">
                    <div class="tbl-card" x-data="{ open: false }">
                        <div class="tbl-card-header" @click="open = !open">
                            <div style="display:flex;align-items:center;gap:0;">
                                <span class="tbl-card-name" x-text="tbl.name"></span>
                                <span class="tbl-card-desc" x-text="tbl.description ? '— ' + tbl.description : ''"></span>
                            </div>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <span style="font-size:11.5px;color:#94a3b8;" x-text="(tbl.columns?.length || 0) + '컬럼'"></span>
                                <svg :style="open ? 'transform:rotate(180deg)' : ''" style="transition:transform .15s;" width="12" height="12" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <div class="tbl-card-body" x-show="open">
                            {{-- Column header --}}
                            <div class="tbl-col-row" style="background:#f8fafc;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;">
                                <div class="tbl-col-cell">컬럼명</div>
                                <div class="tbl-col-cell">타입</div>
                                <div class="tbl-col-cell">속성</div>
                            </div>
                            <template x-for="col in (tbl.columns || [])" :key="col.name">
                                <div class="tbl-col-row">
                                    <div class="tbl-col-cell name" x-text="col.name"></div>
                                    <div class="tbl-col-cell type" x-text="col.type"></div>
                                    <div class="tbl-col-cell flags">
                                        <span x-show="col.primary_key"    class="tbl-flag pk">PK</span>
                                        <span x-show="col.unique && !col.primary_key" class="tbl-flag uk">UK</span>
                                        <span x-show="!col.nullable && !col.primary_key" class="tbl-flag nn">NOT NULL</span>
                                        <span x-show="col.comment" style="font-size:11px;color:#64748b;" x-text="col.comment"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-for="fk in (tbl.foreign_keys || [])" :key="fk.column">
                                <div style="padding:5px 12px;font-size:11.5px;color:#4338ca;background:#f5f3ff;border-top:1px solid #e0e7ff;">
                                    FK: <span style="font-family:monospace;" x-text="fk.column + ' → ' + (fk.references?.table || '') + '.' + (fk.references?.column || '') + ' (' + (fk.on_delete || 'RESTRICT') + ')'"></span>
                                </div>
                            </template>
                            <template x-if="tbl.derived_from && tbl.derived_from.length > 0">
                                <div class="tbl-derived">출처: <span x-text="tbl.derived_from.join(', ')"></span></div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- 설계 결정사항 --}}
            <template x-if="designNotes">
                <div class="erd-section">
                    <div class="erd-section-title">설계 결정사항</div>
                    <div class="design-notes" x-text="designNotes"></div>
                </div>
            </template>

            {{-- 액션 --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;">
                <button class="erd-btn primary" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    재생성
                </button>
                <a :href="cfg.exportUrl + '?format=sql'"     class="erd-btn secondary">SQL 다운로드</a>
                <a :href="cfg.exportUrl + '?format=mermaid'" class="erd-btn secondary">Mermaid 다운로드</a>
                <a :href="cfg.exportUrl + '?format=dbml'"    class="erd-btn ghost">DBML 다운로드</a>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
<script>
mermaid.initialize({ startOnLoad: false, theme: 'default', er: { diagramPadding: 20 } });

function erdIndex() {
    const raw = JSON.parse(document.getElementById('erd-data').textContent);

    return {
        cfg:           raw,
        hasErd:        raw.hasErd,
        isGenerating:  false,
        progressPct:   0,
        statusMessage: '',
        mermaid:       raw.mermaid || '',
        tables:        raw.tables  || [],
        relationships: raw.relationships || [],
        designNotes:   raw.designNotes   || '',
        relatedReqs:   raw.relatedReqs   || [],
        relatedScreens: raw.relatedScreens || [],
        eventSource:   null,

        init() {
            if (this.hasErd && this.mermaid) {
                this.$nextTick(() => this.renderMermaid(this.mermaid));
            }
        },

        async startGeneration() {
            this.isGenerating  = true;
            this.progressPct   = 0;
            this.statusMessage = '생성 준비 중...';

            try {
                const res = await fetch(this.cfg.startUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '시작 실패');
                this.connectSse(data.sessionId);
            } catch (e) {
                this.isGenerating = false;
                alert('ERD 생성 시작 실패: ' + e.message);
            }
        },

        connectSse(sessionId) {
            const url = this.cfg.sseUrlTpl.replace('SESSION_ID', sessionId);
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('status',   (e) => this.onStatus(JSON.parse(e.data)));
            this.eventSource.addEventListener('progress', (e) => this.onProgress(JSON.parse(e.data)));
            this.eventSource.addEventListener('complete', (e) => this.onComplete(JSON.parse(e.data)));
            this.eventSource.addEventListener('error',    (e) => {
                const d = JSON.parse(e.data || '{}');
                this.isGenerating = false;
                this.eventSource?.close();
                alert('ERD 생성 오류: ' + (d.message || '알 수 없는 오류'));
            });
        },

        onStatus(data) {
            this.progressPct   = data.progress || this.progressPct;
            this.statusMessage = data.message  || '';
        },

        onProgress(data) {
            this.progressPct   = data.progress || this.progressPct;
            this.statusMessage = data.message  || '';
        },

        onComplete(data) {
            this.eventSource?.close();
            this.isGenerating  = false;
            this.progressPct   = 100;
            this.hasErd        = true;
            this.tables        = data.tables        || [];
            this.relationships = (this.cfg.relationships) || [];
            this.mermaid       = data.mermaid       || '';
            this.designNotes   = data.design_notes  || '';

            this.$nextTick(() => {
                if (this.mermaid) this.renderMermaid(this.mermaid);
            });
        },

        renderMermaid(code) {
            const el = this.$refs.mermaidContainer;
            if (!el) return;
            el.removeAttribute('data-processed');
            el.textContent = code;
            try {
                mermaid.run({ nodes: [el] });
            } catch (e) {
                console.warn('Mermaid render error', e);
            }
        },

        copyMermaid() {
            navigator.clipboard?.writeText(this.mermaid).then(() => alert('Mermaid 코드가 복사되었습니다.'));
        },
    };
}
</script>
@endpush
