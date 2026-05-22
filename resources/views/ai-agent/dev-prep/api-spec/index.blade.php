@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
<style>
/* ── Layout ────────────────────────────────────────────────── */
.spec-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.spec-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.spec-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.spec-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────── */
.spec-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.spec-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.spec-btn.primary:hover { background:var(--t700,#6d28d9); }
.spec-btn.primary:disabled, .spec-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.spec-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.spec-btn.secondary:hover { background:#e2e8f0; }
.spec-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.spec-btn.ghost:hover { background:#f5f3ff; }
.spec-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Cards ──────────────────────────────────────────────────── */
.spec-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.spec-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Info grid ──────────────────────────────────────────────── */
.spec-info-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:10px; margin-bottom:16px; }
.spec-info-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.spec-info-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.spec-info-value { font-size:18px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Progress ───────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }

/* ── Swagger UI overrides ────────────────────────────────────── */
.swagger-ui-wrap { border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; margin-bottom:18px; }
.swagger-ui-toolbar { display:flex; align-items:center; gap:8px; padding:10px 14px; border-bottom:1px solid #ede8ff; background:#fafafe; }
.swagger-ui-toolbar-title { font-size:13px; font-weight:700; color:#1e1b2e; flex:1; }
#swagger-ui .swagger-ui .info { margin:10px 0; }
#swagger-ui .swagger-ui .scheme-container { box-shadow:none; border-bottom:1px solid #e2e8f0; }
#swagger-ui .swagger-ui .opblock { border-radius:8px; margin-bottom:6px; }
#swagger-ui .swagger-ui .wrapper { padding:10px 14px; }
.swagger-container { background:#fff; padding:0; }

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
<script type="application/json" id="api-spec-data">
{
    "hasErd": {{ $hasErd ? 'true' : 'false' }},
    "hasSpec": {{ $hasSpec ? 'true' : 'false' }},
    "screenCount": {{ $screenCount }},
    "reqCount": {{ $reqCount }},
    "endpointsCount": {{ $endpointsCount }},
    "schemasCount": {{ $schemasCount }},
    "spec": {{ json_encode($specData['spec'] ?? null) }},
    "designNotes": {{ json_encode($specData['design_notes'] ?? '') }},
    "relatedReqs": {{ json_encode($specData['related_requirements'] ?? []) }},
    "relatedScreens": {{ json_encode($specData['related_screens'] ?? []) }},
    "startUrl": "{{ $startUrl }}",
    "sseUrlTpl": "{{ $sseUrlTpl }}",
    "saveUrl": "{{ $saveUrl }}",
    "exportUrl": "{{ $exportUrl }}",
    "regenerateUrl": "{{ $regenerateUrl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="apiSpecIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="spec-header">
        <div class="spec-header-left">
            <h1>API 명세서 — OpenAPI 3.0</h1>
            <p>ERD·요구사항·화면 정보를 분석하여 RESTful API 명세서를 자동 생성하고 Swagger UI로 렌더링합니다.</p>
        </div>
        <div class="spec-header-right" x-show="hasSpec">
            <div style="position:relative;" x-data="{ open:false }">
                <button class="spec-btn secondary sm" @click="open=!open">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    내보내기 ▾
                </button>
                <div x-show="open" @click.outside="open=false"
                     style="position:absolute;right:0;top:calc(100% + 4px);background:#fff;border:1.5px solid #e2e8f0;border-radius:10px;min-width:160px;z-index:20;box-shadow:0 4px 12px rgba(0,0,0,.08);">
                    <a :href="cfg.exportUrl + '?format=yaml'" class="spec-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">YAML (.yaml)</a>
                    <a :href="cfg.exportUrl + '?format=json'" class="spec-btn ghost sm" style="border:none;border-radius:0;display:block;text-align:left;">JSON (.json)</a>
                </div>
            </div>
            @if($historyUrl)
            <a href="{{ $historyUrl }}" class="spec-btn ghost sm">버전 이력</a>
            @endif
        </div>
    </div>

    {{-- ── 상태 A: 생성 전 ── --}}
    <template x-if="!hasSpec && !isGenerating">
        <div>
            <div class="spec-section">
                <div class="spec-section-title">사전 조건</div>
                <div class="prereq-item {{ $hasErd ? 'ok' : 'miss' }}">
                    <span>{{ $hasErd ? '✅' : '⚠️' }}</span>
                    <span>ERD (데이터 모델)</span>
                    @if(!$hasErd)<span style="font-size:11.5px;color:#92400e;margin-left:auto;">
                        <a href="{{ route('ai-agent.projects.pre-dev.erd', $project) }}" style="color:#92400e;font-weight:700;">→ ERD 생성하러 가기</a>
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
                    <h3>API 명세서 자동 생성 (웍스 Tool Use)</h3>
                    <p>ERD·요구사항·화면 정보를 분석하여 OpenAPI 3.0 명세서를 자동 설계합니다. 생성 후 Swagger UI로 확인하고 편집할 수 있습니다.</p>
                </div>
                <button class="proceed-start-btn" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    API 명세서 자동 생성
                </button>
            </div>
        </div>
    </template>

    {{-- ── 상태 B: 생성 중 ── --}}
    <template x-if="isGenerating">
        <div class="spec-section">
            <div class="spec-section-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                API 명세서 설계 중...
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progressPct}%`"></div>
            </div>
            <div style="font-size:12.5px;color:#64748b;" x-text="statusMessage"></div>
        </div>
    </template>

    {{-- ── 상태 C: 완료 ── --}}
    <template x-if="hasSpec && !isGenerating">
        <div>
            {{-- 통계 --}}
            <div class="spec-info-grid" style="margin-bottom:18px;">
                <div class="spec-info-card">
                    <div class="spec-info-label">엔드포인트</div>
                    <div class="spec-info-value" x-text="endpointsCount"></div>
                </div>
                <div class="spec-info-card">
                    <div class="spec-info-label">스키마</div>
                    <div class="spec-info-value" x-text="schemasCount"></div>
                </div>
                <div class="spec-info-card">
                    <div class="spec-info-label">출처 요구사항</div>
                    <div class="spec-info-value" x-text="relatedReqs.length + '건'"></div>
                </div>
                <div class="spec-info-card">
                    <div class="spec-info-label">출처 화면</div>
                    <div class="spec-info-value" x-text="relatedScreens.length + '건'"></div>
                </div>
            </div>

            {{-- Swagger UI --}}
            <div class="swagger-ui-wrap">
                <div class="swagger-ui-toolbar">
                    <span class="swagger-ui-toolbar-title">Swagger UI — API 문서</span>
                    <span style="font-size:11.5px;color:#94a3b8;" x-text="spec ? spec.info?.title + ' v' + spec.info?.version : ''"></span>
                </div>
                <div class="swagger-container">
                    <div id="swagger-ui"></div>
                </div>
            </div>

            {{-- 설계 결정사항 --}}
            <template x-if="designNotes">
                <div class="spec-section">
                    <div class="spec-section-title">설계 결정사항</div>
                    <div class="design-notes" x-text="designNotes"></div>
                </div>
            </template>

            {{-- 액션 --}}
            <div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:4px;">
                <button class="spec-btn primary" @click="startGeneration()" :disabled="isGenerating">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    재생성
                </button>
                <a :href="cfg.exportUrl + '?format=yaml'" class="spec-btn secondary">YAML 다운로드</a>
                <a :href="cfg.exportUrl + '?format=json'" class="spec-btn ghost">JSON 다운로드</a>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script>
function apiSpecIndex() {
    const raw = JSON.parse(document.getElementById('api-spec-data').textContent);

    return {
        cfg:            raw,
        hasSpec:        raw.hasSpec,
        isGenerating:   false,
        progressPct:    0,
        statusMessage:  '',
        spec:           raw.spec  || null,
        designNotes:    raw.designNotes   || '',
        relatedReqs:    raw.relatedReqs   || [],
        relatedScreens: raw.relatedScreens || [],
        endpointsCount: raw.endpointsCount || 0,
        schemasCount:   raw.schemasCount   || 0,
        eventSource:    null,
        swaggerUi:      null,

        init() {
            if (this.hasSpec && this.spec) {
                this.$nextTick(() => this.renderSwagger(this.spec));
            }
        },

        async startGeneration() {
            this.isGenerating  = true;
            this.progressPct   = 0;
            this.statusMessage = '생성 준비 중...';

            try {
                const res = await fetch(this.cfg.startUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '시작 실패');
                this.connectSse(data.sessionId);
            } catch (e) {
                this.isGenerating = false;
                alert('API 명세서 생성 시작 실패: ' + e.message);
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
                alert('API 명세서 생성 오류: ' + (d.message || '알 수 없는 오류'));
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
            this.isGenerating   = false;
            this.progressPct    = 100;
            this.hasSpec        = true;
            this.spec           = data.spec          || null;
            this.designNotes    = data.design_notes  || '';
            this.endpointsCount = data.endpoints_count || 0;
            this.schemasCount   = data.schemas_count   || 0;

            this.$nextTick(() => {
                if (this.spec) this.renderSwagger(this.spec);
            });
        },

        renderSwagger(spec) {
            const el = document.getElementById('swagger-ui');
            if (!el || typeof SwaggerUIBundle === 'undefined') return;
            try {
                this.swaggerUi = SwaggerUIBundle({
                    spec:     spec,
                    domNode:  el,
                    presets:  [SwaggerUIBundle.presets.apis],
                    layout:   'BaseLayout',
                    deepLinking: true,
                    defaultModelsExpandDepth: 1,
                    defaultModelExpandDepth:  1,
                });
            } catch (e) {
                console.warn('Swagger UI render error', e);
            }
        },
    };
}
</script>
@endpush
