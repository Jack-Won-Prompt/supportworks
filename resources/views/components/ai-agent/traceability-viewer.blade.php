@props([
    'sourceType' => 'requirement',
    'sourceId'   => null,
    'sourceRef'  => '',
    'linksUrl'   => '',
    'impactUrl'  => '',
])

@once
@push('styles')
<style>
/* ── Traceability Viewer Side Panel (.atv-*) ────────────────────── */
.atv-trigger { display:inline-flex; align-items:center; gap:5px; padding:5px 10px; border-radius:7px; font-size:12px; font-weight:600; color:#0369a1; background:#f0f9ff; border:1px solid #bae6fd; cursor:pointer; transition:all .15s; }
.atv-trigger:hover { background:#e0f2fe; }
.atv-trigger svg { flex-shrink:0; }

.atv-backdrop { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1040; }
.atv-panel { position:fixed; top:0; right:0; bottom:0; width:520px; max-width:100vw; background:#fff; z-index:1041; display:flex; flex-direction:column; box-shadow:-4px 0 24px rgba(0,0,0,.12); }
.atv-header { display:flex; align-items:center; gap:10px; padding:16px 20px; border-bottom:1.5px solid #f1f5f9; flex-shrink:0; }
.atv-header-icon { width:32px; height:32px; background:#f0f9ff; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.atv-header-title { font-size:14px; font-weight:700; color:#1e1b2e; flex:1; min-width:0; }
.atv-header-sub { font-size:11px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.atv-close { width:28px; height:28px; border-radius:7px; border:none; background:#f8fafc; color:#64748b; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.atv-close:hover { background:#f1f5f9; }

/* tabs */
.atv-tabs { display:flex; gap:0; border-bottom:1.5px solid #f1f5f9; flex-shrink:0; }
.atv-tab { flex:1; padding:10px; font-size:12.5px; font-weight:600; color:#94a3b8; background:none; border:none; border-bottom:2.5px solid transparent; cursor:pointer; transition:all .15s; display:flex; align-items:center; justify-content:center; gap:5px; }
.atv-tab.active { color:#0369a1; border-bottom-color:#0369a1; }
.atv-tab:hover:not(.active) { color:#475569; }
.atv-tab-count { background:#e2e8f0; color:#64748b; font-size:10px; font-weight:700; padding:1px 6px; border-radius:10px; }
.atv-tab.active .atv-tab-count { background:#dbeafe; color:#1d4ed8; }

.atv-body { flex:1; overflow-y:auto; }

/* type badges */
.atv-type { display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:600; padding:1px 7px; border-radius:8px; white-space:nowrap; }
.atv-type.requirement  { background:#fef3c7; color:#92400e; }
.atv-type.screen       { background:#ede9fe; color:#6d28d9; }
.atv-type.component    { background:#dbeafe; color:#1e40af; }
.atv-type.api_endpoint { background:#dcfce7; color:#166534; }
.atv-type.code_file    { background:#f1f5f9; color:#475569; }
.atv-type.artifact     { background:#fce7f3; color:#9d174d; }

/* link type badges */
.atv-link { font-size:10px; font-weight:600; padding:1px 6px; border-radius:6px; background:#f1f5f9; color:#64748b; }
.atv-link.implements { background:#dcfce7; color:#166534; }
.atv-link.designs    { background:#ede9fe; color:#6d28d9; }
.atv-link.tests      { background:#dbeafe; color:#1e40af; }
.atv-link.documents  { background:#fef3c7; color:#92400e; }
.atv-link.depends_on { background:#fee2e2; color:#991b1b; }

/* section */
.atv-section { padding:16px 20px 0; }
.atv-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#94a3b8; margin-bottom:10px; display:flex; align-items:center; gap:6px; }
.atv-section-title::after { content:''; flex:1; height:1px; background:#f1f5f9; }

/* node cards */
.atv-node { display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1.5px solid #f1f5f9; border-radius:10px; margin-bottom:8px; background:#fff; transition:border-color .12s; }
.atv-node:hover { border-color:#e2e8f0; }
.atv-node-icon { width:28px; height:28px; border-radius:7px; background:#f8fafc; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.atv-node-body { flex:1; min-width:0; }
.atv-node-ref { font-size:12.5px; font-weight:600; color:#1e1b2e; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.atv-node-meta { display:flex; align-items:center; gap:6px; flex-wrap:wrap; }
.atv-node-dir { font-size:10px; color:#94a3b8; }

/* impact tree */
.atv-impact-list { padding:12px 20px; }
.atv-impact-node { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; margin-bottom:4px; background:#f8fafc; border:1px solid #f1f5f9; }
.atv-impact-indent { flex-shrink:0; color:#cbd5e1; font-size:12px; font-family:monospace; }
.atv-impact-ref { font-size:12px; font-weight:500; color:#374151; flex:1; min-width:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.atv-impact-depth { font-size:10px; color:#94a3b8; background:#f1f5f9; padding:1px 6px; border-radius:8px; flex-shrink:0; }

.atv-empty { text-align:center; padding:32px 20px; color:#94a3b8; font-size:13px; }
.atv-loading { display:flex; align-items:center; justify-content:center; gap:10px; padding:40px 20px; color:#94a3b8; font-size:13px; }
.atv-spinner { width:18px; height:18px; border:2px solid #e2e8f0; border-top-color:#0369a1; border-radius:50%; animation:atvSpin .7s linear infinite; }
@keyframes atvSpin { to { transform:rotate(360deg); } }

.atv-footer { padding:12px 20px; border-top:1.5px solid #f1f5f9; font-size:11.5px; color:#94a3b8; flex-shrink:0; display:flex; justify-content:space-between; align-items:center; }
</style>
@endpush
@endonce

@php
$cfg = json_encode([
    'sourceType' => $sourceType,
    'sourceId'   => $sourceId,
    'sourceRef'  => $sourceRef,
    'linksUrl'   => $linksUrl,
    'impactUrl'  => $impactUrl,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp

<div x-data="traceabilityViewer({{ $cfg }})">
    {{-- Trigger button --}}
    <button class="atv-trigger" @click="open()" title="추적성 보기">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
        </svg>
        추적성
    </button>

    {{-- Side panel (teleported to body) --}}
    <template x-teleport="body">
        <div x-show="isOpen" x-cloak style="display:none">
            <div class="atv-backdrop" @click="close()"></div>

            <div class="atv-panel">
                {{-- Header --}}
                <div class="atv-header">
                    <div class="atv-header-icon">
                        <svg width="16" height="16" fill="none" stroke="#0369a1" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <div class="atv-header-title">추적성 뷰어</div>
                        <div class="atv-header-sub">
                            <span x-text="typeLabel(cfg.sourceType)"></span> ·
                            <span x-text="cfg.sourceRef || '#' + cfg.sourceId"></span>
                        </div>
                    </div>
                    <button class="atv-close" @click="close()">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Tabs --}}
                <div class="atv-tabs">
                    <button class="atv-tab" :class="{ active: tab === 'links' }" @click="tab = 'links'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        링크
                        <span class="atv-tab-count" x-text="linksFrom.length + linksTo.length"></span>
                    </button>
                    <button class="atv-tab" :class="{ active: tab === 'impact' }" @click="switchToImpact()">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                        </svg>
                        영향 분석
                        <span class="atv-tab-count" x-text="impacts.length"></span>
                    </button>
                </div>

                {{-- Body --}}
                <div class="atv-body">
                    {{-- Loading --}}
                    <template x-if="loading">
                        <div class="atv-loading">
                            <div class="atv-spinner"></div>
                            <span>불러오는 중...</span>
                        </div>
                    </template>

                    {{-- Links tab --}}
                    <template x-if="!loading && tab === 'links'">
                        <div>
                            {{-- Links from (outgoing) --}}
                            <div class="atv-section">
                                <div class="atv-section-title">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg>
                                    이 항목이 참조하는 대상
                                    <span x-text="'(' + linksFrom.length + ')'"></span>
                                </div>
                                <template x-if="linksFrom.length === 0">
                                    <div class="atv-empty" style="padding:16px;">참조하는 항목이 없습니다.</div>
                                </template>
                                <template x-for="link in linksFrom" :key="link.id">
                                    <div class="atv-node">
                                        <div class="atv-node-icon">
                                            <svg width="14" height="14" fill="none" stroke="#64748b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <div class="atv-node-body">
                                            <div class="atv-node-ref" x-text="link.target_ref || '#' + link.target_id"></div>
                                            <div class="atv-node-meta">
                                                <span class="atv-type" :class="link.target_type" x-text="typeLabel(link.target_type)"></span>
                                                <span class="atv-link" :class="link.link_type" x-text="linkLabel(link.link_type)"></span>
                                                <span class="atv-node-dir">→ 참조</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            {{-- Links to (incoming) --}}
                            <div class="atv-section" style="padding-bottom:16px;">
                                <div class="atv-section-title">
                                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                                    이 항목을 참조하는 소스
                                    <span x-text="'(' + linksTo.length + ')'"></span>
                                </div>
                                <template x-if="linksTo.length === 0">
                                    <div class="atv-empty" style="padding:16px;">참조하는 소스가 없습니다.</div>
                                </template>
                                <template x-for="link in linksTo" :key="link.id">
                                    <div class="atv-node">
                                        <div class="atv-node-icon">
                                            <svg width="14" height="14" fill="none" stroke="#64748b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        </div>
                                        <div class="atv-node-body">
                                            <div class="atv-node-ref" x-text="link.source_ref || '#' + link.source_id"></div>
                                            <div class="atv-node-meta">
                                                <span class="atv-type" :class="link.source_type" x-text="typeLabel(link.source_type)"></span>
                                                <span class="atv-link" :class="link.link_type" x-text="linkLabel(link.link_type)"></span>
                                                <span class="atv-node-dir">← 역참조</span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Impact tab --}}
                    <template x-if="!loading && tab === 'impact'">
                        <div>
                            <div style="padding:12px 20px 0;">
                                <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:10px 14px;font-size:12px;color:#9a3412;margin-bottom:12px;">
                                    이 항목이 변경되면 영향을 받는 모든 상위 참조 항목을 BFS로 분석합니다.
                                </div>
                            </div>
                            <template x-if="impacts.length === 0">
                                <div class="atv-empty">영향받는 항목이 없습니다.</div>
                            </template>
                            <div class="atv-impact-list">
                                <template x-for="(node, i) in impacts" :key="i">
                                    <div class="atv-impact-node">
                                        <span class="atv-impact-indent" x-text="depthPrefix(node.depth)"></span>
                                        <span class="atv-type" :class="node.type" x-text="typeLabel(node.type)" style="flex-shrink:0;"></span>
                                        <span class="atv-impact-ref" x-text="node.ref || '#' + node.id"></span>
                                        <span class="atv-impact-depth" x-text="'depth ' + node.depth"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Footer --}}
                <div class="atv-footer">
                    <span>
                        <span class="atv-type" :class="cfg.sourceType" x-text="typeLabel(cfg.sourceType)" style="font-size:11px;"></span>
                        <span style="margin-left:6px;" x-text="cfg.sourceRef || '#' + cfg.sourceId"></span>
                    </span>
                    <span x-text="'링크 ' + (linksFrom.length + linksTo.length) + '개 · 영향 ' + impacts.length + '개'"></span>
                </div>
            </div>
        </div>
    </template>
</div>

@once
@push('scripts')
<script>
function traceabilityViewer(cfg) {
    return {
        cfg,
        isOpen: false,
        loading: false,
        tab: 'links',
        linksFrom: [],
        linksTo: [],
        impacts: [],
        impactsLoaded: false,

        open() {
            this.isOpen = true;
            this.tab = 'links';
            this.fetchLinks();
        },

        close() {
            this.isOpen = false;
        },

        fetchLinks() {
            if (!cfg.linksUrl) return;
            this.loading = true;
            fetch(cfg.linksUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.linksFrom = data.links_from || [];
                    this.linksTo   = data.links_to   || [];
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        switchToImpact() {
            this.tab = 'impact';
            if (!this.impactsLoaded) this.fetchImpact();
        },

        fetchImpact() {
            if (!cfg.impactUrl) return;
            this.loading = true;
            fetch(cfg.impactUrl, { headers: { Accept: 'application/json' } })
                .then(r => r.json())
                .then(data => {
                    this.impacts = data.impacts || [];
                    this.impactsLoaded = true;
                    this.loading = false;
                })
                .catch(() => { this.loading = false; });
        },

        typeLabel(type) {
            const map = {
                requirement:  '요구사항',
                screen:       '화면',
                component:    '컴포넌트',
                api_endpoint: 'API',
                code_file:    '코드파일',
                artifact:     '산출물',
            };
            return map[type] || type;
        },

        linkLabel(type) {
            const map = {
                implements: '구현',
                designs:    '설계',
                tests:      '테스트',
                documents:  '문서화',
                depends_on: '의존',
            };
            return map[type] || type;
        },

        depthPrefix(depth) {
            if (depth <= 1) return '├─';
            return '│  '.repeat(depth - 1) + '└─';
        },
    };
}
</script>
@endpush
@endonce
