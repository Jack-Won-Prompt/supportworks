@extends('layouts.ai-agent')
@section('title', '화면 매핑 — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ── */
.sm-wrap       { display:flex; flex-direction:column; gap:0; height:calc(100vh - 120px); min-height:500px; }
.sm-toolbar    { display:flex; align-items:center; gap:10px; padding:14px 0 10px; flex-wrap:wrap; }
.sm-layout     { display:grid; grid-template-columns:1fr 360px; gap:14px; flex:1; overflow:hidden; }

/* ── Progress bar ── */
.sm-progress-bar { height:6px; border-radius:3px; background:#e2e8f0; overflow:hidden; }
.sm-progress-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#a78bfa); border-radius:3px; transition:width .4s ease; }

/* ── Panels ── */
.sm-panel      { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; }
.sm-panel-hdr  { padding:14px 18px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:8px; flex-shrink:0; }
.sm-panel-title{ font-size:13px; font-weight:700; color:#374151; flex:1; }
.sm-panel-body { flex:1; overflow-y:auto; }

/* ── SCR list ── */
.sm-scr-item   { display:flex; align-items:center; gap:10px; padding:10px 14px; border-bottom:1px solid #f8fafc; cursor:pointer; transition:background .1s; }
.sm-scr-item:hover { background:#faf8ff; }
.sm-scr-item.active { background:#f5f3ff; border-left:3px solid #7c3aed; padding-left:11px; }
.sm-scr-id     { font-size:12px; font-weight:800; color:#7c3aed; font-family:monospace; white-space:nowrap; }
.sm-scr-name   { font-size:13px; color:#374151; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sm-mapped-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.sm-mapped-dot.yes { background:#22c55e; }
.sm-mapped-dot.no  { background:#d1d5db; }

/* ── Figma frame cards ── */
.sm-frame-card { border:1.5px solid #e2e8f0; border-radius:10px; overflow:hidden; cursor:pointer; transition:all .15s; }
.sm-frame-card:hover { border-color:#a78bfa; box-shadow:0 2px 8px rgba(124,58,237,.12); }
.sm-frame-card.selected { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.15); }
.sm-frame-card.is-mapped { opacity:.45; cursor:default; }
.sm-frame-preview { width:100%; height:80px; object-fit:cover; background:#f8fafc; display:block; }
.sm-frame-preview-ph { width:100%; height:80px; background:#f8fafc; display:flex; align-items:center; justify-content:center; }
.sm-frame-info { padding:7px 10px 8px; }
.sm-frame-name { font-size:11.5px; font-weight:600; color:#374151; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sm-frame-meta { font-size:10.5px; color:#94a3b8; margin-top:1px; }
.sm-frame-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; padding:14px; }

/* ── Detail / action panel ── */
.sm-detail     { padding:14px 16px; }
.sm-detail-hdr { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin-bottom:10px; }
.sm-mapping-box{ border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; background:#faf8ff; margin-bottom:12px; }
.sm-mapping-row{ display:flex; gap:6px; align-items:center; margin-bottom:5px; font-size:12.5px; }
.sm-mapping-label{ color:#94a3b8; font-weight:600; min-width:72px; }
.sm-mapping-val  { color:#374151; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.sm-figma-link { display:inline-flex; align-items:center; gap:4px; font-size:12px; color:#7c3aed; text-decoration:none; }
.sm-figma-link:hover { text-decoration:underline; }

/* ── Suggestion chip ── */
.sm-suggest-chip { display:flex; align-items:center; gap:8px; padding:7px 10px; border:1.5px solid #ede8ff; border-radius:8px; margin-bottom:6px; cursor:pointer; transition:background .1s; }
.sm-suggest-chip:hover { background:#f5f3ff; }
.sm-suggest-chip .sim { font-size:10.5px; font-weight:700; color:#7c3aed; background:#ede8ff; border-radius:4px; padding:1px 5px; white-space:nowrap; }

/* ── Buttons ── */
.sm-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; transition:all .15s; border:none; }
.sm-btn-primary { background:#7c3aed; color:#fff; }
.sm-btn-primary:hover { background:#6d28d9; }
.sm-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.sm-btn-outline:hover { border-color:#a78bfa; color:#7c3aed; }
.sm-btn-danger  { background:#fff; color:#dc2626; border:1.5px solid #fca5a5; }
.sm-btn-danger:hover { background:#fef2f2; }
.sm-btn-sm { padding:4px 10px; font-size:11.5px; }
.sm-btn:disabled { opacity:.5; cursor:not-allowed; }

/* ── Misc ── */
.sm-empty { padding:40px 20px; text-align:center; color:#94a3b8; font-size:13px; }
.sm-badge  { display:inline-flex; align-items:center; gap:3px; font-size:11px; font-weight:600; padding:2px 8px; border-radius:5px; }
.sm-badge-green { background:#f0fdf4; color:#166534; }
.sm-badge-gray  { background:#f8fafc; color:#64748b; }
.sm-badge-purple{ background:#f5f3ff; color:#6d28d9; }
.sm-toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:500; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.sm-toast.show { opacity:1; transform:translateY(0); }

/* ── Suggest modal ── */
.sm-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:1000; display:flex; align-items:center; justify-content:center; }
.sm-modal { background:#fff; border-radius:16px; width:min(680px, 95vw); max-height:80vh; overflow:hidden; display:flex; flex-direction:column; box-shadow:0 24px 64px rgba(0,0,0,.18); }
.sm-modal-hdr  { padding:18px 22px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:8px; }
.sm-modal-body { flex:1; overflow-y:auto; padding:18px 22px; }
.sm-modal-foot { padding:14px 22px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:8px; }
.sm-suggest-row{ display:grid; grid-template-columns:1fr auto 1fr auto; gap:8px; align-items:center; padding:8px 0; border-bottom:1px solid #f8fafc; }
</style>
@endpush

@section('page-actions')
<a href="{{ route('ai-agent.projects.design.index', $project) }}" class="sm-btn sm-btn-outline">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    디자인 단계
</a>
@endsection

@section('ai-agent-content')
@php
$_smData = [
    'projectId'    => $project->id,
    'hasPat'       => $hasPat,
    'status'       => $status,
    'screens'      => $screens,
    'loadUrl'      => route('ai-agent.projects.design.screens.load-figma',  $project),
    'suggestUrl'   => route('ai-agent.projects.design.screens.suggestions', $project),
    'applyUrl'     => route('ai-agent.projects.design.screens.apply',       $project),
    'applyBatchUrl'=> route('ai-agent.projects.design.screens.apply-batch', $project),
    'exportUrl'    => route('ai-agent.projects.design.screens.export',      $project),
    'unmapBase'    => route('ai-agent.projects.design.screens.unmap',       [$project, 0]),
    'csrfToken'    => csrf_token(),
];
@endphp
<div x-data="smPage(@json($_smData))" class="sm-wrap">

    {{-- Toast --}}
    <div class="sm-toast" :class="{ show: toast.show }" x-text="toast.msg"></div>

    {{-- Toolbar --}}
    <div class="sm-toolbar">
        {{-- Figma URL 입력 --}}
        @if($hasPat)
        <input type="url" x-model="figmaUrl" placeholder="Figma 파일 URL 입력..."
               style="flex:1;min-width:280px;padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;"
               @keydown.enter="loadFigma()">
        <button class="sm-btn sm-btn-primary" @click="loadFigma()" :disabled="loading || !figmaUrl">
            <template x-if="!loading">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </template>
            <template x-if="loading">
                <svg class="animate-spin" width="13" height="13" fill="none" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" style="opacity:.25"/><path fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" style="opacity:.75"/></svg>
            </template>
            Figma 로드
        </button>
        <button class="sm-btn sm-btn-outline" @click="openSuggestModal()" :disabled="!fileKey || suggestLoading">
            자동 제안
        </button>
        @else
        <div style="padding:8px 14px;background:#fef9c3;border:1.5px solid #fde68a;border-radius:8px;font-size:12.5px;color:#92400e;">
            Figma PAT 미설정 — <a href="{{ route('ai-agent.projects.design.tokens', $project) }}" style="color:#7c3aed;font-weight:600;">설정 페이지</a>에서 토큰을 등록하세요.
        </div>
        @endif

        {{-- 매핑 진척도 --}}
        <div style="margin-left:auto;display:flex;align-items:center;gap:10px;flex-shrink:0;">
            <div style="font-size:12px;color:#64748b;">
                매핑: <strong x-text="status.mapped"></strong>/<strong x-text="status.total"></strong>
                (<span x-text="status.percent"></span>%)
            </div>
            <div class="sm-progress-bar" style="width:120px;">
                <div class="sm-progress-fill" :style="{ width: status.percent + '%' }"></div>
            </div>
            <a :href="cfg.exportUrl" class="sm-btn sm-btn-outline sm-btn-sm">내보내기</a>
        </div>
    </div>

    {{-- Two-panel layout --}}
    <div class="sm-layout">

        {{-- Left: SCR 목록 --}}
        <div class="sm-panel">
            <div class="sm-panel-hdr">
                <span class="sm-panel-title">화면 목록 (SCR-XXX)</span>
                <span class="sm-badge sm-badge-purple" x-text="screens.length + '개'"></span>
            </div>
            <div class="sm-panel-body" id="scr-list-container"
                 hx-get="{{ route('ai-agent.projects.design.screens', $project) }}"
                 hx-trigger="none">

                <template x-if="screens.length === 0 && !screensLoading">
                    <div class="sm-empty">
                        화면 데이터 없음<br>
                        <small style="font-size:11.5px;">기획 단계에서 SCR-XXX 화면을 먼저 등록하세요.</small>
                    </div>
                </template>

                <template x-for="scr in screens" :key="scr.id">
                    <div class="sm-scr-item"
                         :class="{ active: selected?.id === scr.id }"
                         @click="selectScreen(scr)">
                        <div class="sm-mapped-dot" :class="scr.figma_frame_id ? 'yes' : 'no'"></div>
                        <span class="sm-scr-id" x-text="scr.screen_id"></span>
                        <span class="sm-scr-name" x-text="scr.title"></span>
                        <template x-if="scr.figma_frame_id">
                            <span class="sm-badge sm-badge-green" style="font-size:10px;">연결됨</span>
                        </template>
                    </div>
                </template>

            </div>
        </div>

        {{-- Right: Figma 프레임 목록 + 선택 화면 상세 --}}
        <div style="display:flex;flex-direction:column;gap:12px;overflow:hidden;">

            {{-- 선택된 SCR 상세 --}}
            <div class="sm-panel" style="flex-shrink:0;" x-show="selected">
                <div class="sm-panel-hdr">
                    <span class="sm-panel-title" x-text="selected?.screen_id + ' — ' + selected?.title"></span>
                </div>
                <div class="sm-detail" x-show="selected">
                    <template x-if="selected?.figma_frame_id">
                        <div>
                            <div class="sm-detail-hdr">Figma 연결</div>
                            <div class="sm-mapping-box">
                                <div class="sm-mapping-row">
                                    <span class="sm-mapping-label">프레임</span>
                                    <span class="sm-mapping-val" x-text="selected?.figma_frame_name"></span>
                                </div>
                                <div class="sm-mapping-row">
                                    <span class="sm-mapping-label">파일 키</span>
                                    <span class="sm-mapping-val" style="font-family:monospace;font-size:11px;" x-text="selected?.figma_file_key"></span>
                                </div>
                                <div class="sm-mapping-row" style="margin-bottom:0;">
                                    <span class="sm-mapping-label">링크</span>
                                    <a :href="selected?.figma_url" target="_blank" class="sm-figma-link">
                                        Figma 열기
                                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>
                            <button class="sm-btn sm-btn-danger sm-btn-sm" @click="doUnmap(selected)">매핑 해제</button>
                        </div>
                    </template>
                    <template x-if="!selected?.figma_frame_id">
                        <div>
                            <div class="sm-detail-hdr">Figma 프레임 연결</div>
                            <template x-if="selectedFrame">
                                <div>
                                    <div class="sm-mapping-box">
                                        <div class="sm-mapping-row">
                                            <span class="sm-mapping-label">선택 프레임</span>
                                            <span class="sm-mapping-val" x-text="selectedFrame?.name"></span>
                                        </div>
                                    </div>
                                    <button class="sm-btn sm-btn-primary sm-btn-sm" @click="doApply()" :disabled="applying">
                                        <span x-text="applying ? '적용 중...' : '매핑 적용'"></span>
                                    </button>
                                </div>
                            </template>
                            <template x-if="!selectedFrame">
                                <div style="font-size:12px;color:#94a3b8;">오른쪽에서 Figma 프레임을 클릭하여 선택하세요.</div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Figma 프레임 그리드 --}}
            <div class="sm-panel" style="flex:1;overflow:hidden;">
                <div class="sm-panel-hdr">
                    <span class="sm-panel-title">Figma 프레임</span>
                    <span class="sm-badge sm-badge-gray" x-text="frames.length + '개'" x-show="frames.length > 0"></span>
                </div>
                <div class="sm-panel-body">
                    <template x-if="frames.length === 0 && !loading">
                        <div class="sm-empty">
                            Figma URL을 입력하고 로드하세요.<br>
                            <small style="font-size:11.5px;">상단 입력창에 Figma 파일 URL을 붙여넣으세요.</small>
                        </div>
                    </template>
                    <div class="sm-frame-grid" x-show="frames.length > 0">
                        <template x-for="frame in frames" :key="frame.node_id">
                            <div class="sm-frame-card"
                                 :class="{
                                     selected:  selectedFrame?.node_id === frame.node_id,
                                     'is-mapped': frame.is_mapped,
                                 }"
                                 @click="!frame.is_mapped && selectFrame(frame)">
                                <template x-if="frame.preview_url">
                                    <img :src="frame.preview_url" class="sm-frame-preview" :alt="frame.name">
                                </template>
                                <template x-if="!frame.preview_url">
                                    <div class="sm-frame-preview-ph">
                                        <svg width="24" height="24" fill="none" stroke="#d1d5db" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="1.5"/><path d="M3 9h18M9 21V9" stroke-width="1.5"/></svg>
                                    </div>
                                </template>
                                <div class="sm-frame-info">
                                    <div class="sm-frame-name" x-text="frame.name"></div>
                                    <div class="sm-frame-meta">
                                        <template x-if="frame.is_mapped">
                                            <span style="color:#22c55e;font-weight:600;">연결됨</span>
                                        </template>
                                        <template x-if="!frame.is_mapped">
                                            <span x-text="frame.node_id"></span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 자동 제안 모달 --}}
    <div class="sm-modal-overlay" x-show="suggestModal" x-cloak @click.self="suggestModal = false">
        <div class="sm-modal" @click.stop>
            <div class="sm-modal-hdr">
                <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span style="font-size:14px;font-weight:700;color:#1e1b2e;flex:1;">자동 매핑 제안</span>
                <button @click="suggestModal = false" style="color:#94a3b8;background:none;border:none;cursor:pointer;padding:2px;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="sm-modal-body">
                <template x-if="suggestLoading">
                    <div class="sm-empty">제안 생성 중...</div>
                </template>
                <template x-if="!suggestLoading && suggestions.length === 0">
                    <div class="sm-empty">매핑 가능한 제안이 없습니다.<br><small>임계값(70%) 이상의 유사 프레임이 없거나 모두 매핑되었습니다.</small></div>
                </template>
                <template x-if="!suggestLoading && suggestions.length > 0">
                    <div>
                        <div style="font-size:12px;color:#64748b;margin-bottom:12px;">
                            <strong x-text="suggestions.length"></strong>개의 화면이 Figma 프레임과 이름이 유사합니다. 체크박스로 선택 후 일괄 적용하세요.
                        </div>
                        <div style="display:grid;gap:6px;">
                            <template x-for="(sug, idx) in suggestions" :key="idx">
                                <label class="sm-suggest-chip" style="cursor:pointer;">
                                    <input type="checkbox" :value="idx" x-model="selectedSugs" style="width:14px;height:14px;accent-color:#7c3aed;">
                                    <div style="flex:1;min-width:0;">
                                        <div style="font-size:12.5px;font-weight:600;color:#374151;" x-text="sug.screen_screen_id + ' — ' + sug.screen_name"></div>
                                        <div style="font-size:11.5px;color:#64748b;" x-text="'→ ' + sug.figma_frame_name"></div>
                                    </div>
                                    <span class="sim" x-text="Math.round(sug.similarity * 100) + '%'"></span>
                                    <template x-if="sug.preview_url">
                                        <img :src="sug.preview_url" style="width:44px;height:30px;object-fit:cover;border-radius:4px;border:1px solid #e2e8f0;" :alt="sug.figma_frame_name">
                                    </template>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="sm-modal-foot">
                <button class="sm-btn sm-btn-outline" @click="suggestModal = false">취소</button>
                <button class="sm-btn sm-btn-outline sm-btn-sm" @click="selectedSugs = suggestions.map((_,i) => i)">전체 선택</button>
                <button class="sm-btn sm-btn-primary"
                        @click="applyBatch()"
                        :disabled="selectedSugs.length === 0 || applying">
                    <span x-text="applying ? '적용 중...' : '선택 적용 (' + selectedSugs.length + ')'"></span>
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
async function smPage(cfg) {
    return {
        cfg,
        figmaUrl:      '',
        fileKey:       null,
        loading:       false,
        screensLoading:false,
        suggestLoading:false,
        applying:      false,
        screens:       cfg.screens,
        frames:        [],
        suggestions:   [],
        selectedSugs:  [],
        selected:      null,   // active SCR
        selectedFrame: null,   // active Figma frame
        suggestModal:  false,
        status:        cfg.status,
        toast:         { show: false, msg: '' },

        init() {},

        showToast(msg) {
            this.toast = { show: true, msg };
            setTimeout(() => { this.toast.show = false; }, 3000);
        },

        selectScreen(scr) {
            this.selected      = scr;
            this.selectedFrame = null;
        },

        selectFrame(frame) {
            if (frame.is_mapped) return;
            this.selectedFrame = frame;
        },

        async loadFigma() {
            if (!this.figmaUrl) return;
            this.loading = true;
            this.frames  = [];
            this.selectedFrame = null;
            try {
                const res = await axios.post(this.cfg.loadUrl, {
                    figma_url: this.figmaUrl,
                    _token: this.cfg.csrfToken,
                });
                if (res.data.success) {
                    this.fileKey = res.data.file_key;
                    this.frames  = res.data.frames;
                    this.showToast(`Figma 프레임 ${res.data.count}개 로드 완료`);
                } else {
                    this.showToast(res.data.message);
                }
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '로드 중 오류 발생');
            }
            this.loading = false;
        },

        async openSuggestModal() {
            this.suggestModal   = true;
            this.suggestions    = [];
            this.selectedSugs   = [];
            this.suggestLoading = true;
            try {
                const res = await axios.get(this.cfg.suggestUrl, {
                    params: { figma_url: this.figmaUrl },
                });
                if (res.data.success) {
                    this.suggestions = res.data.suggestions;
                    this.selectedSugs = this.suggestions.map((_, i) => i);
                } else {
                    this.showToast(res.data.message);
                }
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '오류 발생');
            }
            this.suggestLoading = false;
        },

        async doApply() {
            if (!this.selected || !this.selectedFrame) return;
            this.applying = true;
            try {
                const res = await axios.post(this.cfg.applyUrl, {
                    screen_id:        this.selected.id,
                    figma_file_key:   this.fileKey,
                    figma_node_id:    this.selectedFrame.node_id,
                    figma_frame_name: this.selectedFrame.name,
                    _token: this.cfg.csrfToken,
                });
                if (res.data.success) {
                    this.selected.figma_frame_id   = this.selectedFrame.node_id;
                    this.selected.figma_frame_name = this.selectedFrame.name;
                    this.selected.figma_file_key   = this.fileKey;
                    this.selected.figma_url        = `https://www.figma.com/file/${this.fileKey}/?node-id=${encodeURIComponent(this.selectedFrame.node_id)}`;
                    this.selectedFrame.is_mapped   = true;
                    this.selectedFrame             = null;
                    this.status                    = res.data.status;
                    this.showToast(res.data.message);
                } else {
                    this.showToast(res.data.message);
                }
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '매핑 오류');
            }
            this.applying = false;
        },

        async applyBatch() {
            if (this.selectedSugs.length === 0) return;
            this.applying = true;
            const toApply = this.selectedSugs.map(i => this.suggestions[i]);
            try {
                const res = await axios.post(this.cfg.applyBatchUrl, {
                    suggestions: toApply,
                    _token: this.cfg.csrfToken,
                });
                if (res.data.success) {
                    this.status      = res.data.status;
                    this.suggestModal= false;
                    this.showToast(res.data.message);
                    // reload page to reflect updated mappings
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    this.showToast(res.data.message);
                }
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '일괄 매핑 오류');
            }
            this.applying = false;
        },

        async doUnmap(scr) {
            if (!await __confirm(`${scr.screen_id} 매핑을 해제하시겠습니까?`)) return;
            const url = this.cfg.unmapBase.replace(/\/0$/, '/' + scr.id);
            try {
                const res = await axios.delete(url, {
                    data: { _token: this.cfg.csrfToken },
                });
                if (res.data.success) {
                    scr.figma_frame_id   = null;
                    scr.figma_frame_name = null;
                    scr.figma_file_key   = null;
                    scr.figma_url        = null;
                    if (this.selected?.id === scr.id) {
                        this.selected = { ...scr };
                    }
                    this.status = res.data.status;
                    this.showToast(res.data.message);
                }
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '해제 오류');
            }
        },
    };
}
</script>
@endpush

@endsection
