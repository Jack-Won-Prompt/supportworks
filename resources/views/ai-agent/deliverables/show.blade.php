@extends('layouts.app')

@php
    $totalSteps = count($typeDef['steps']);
    $pct = $totalSteps ? round(($stepNo - 1) / $totalSteps * 100) : 0;
    $catColor = match($typeDef['category']) {
        'security'    => '#dc2626',
        'operations'  => '#0891b2',
        'test_deploy' => '#059669',
        'contract'    => '#d97706',
        default       => '#7c3aed',
    };
@endphp

@push('styles')
<style>
/* 산출물 작성 화면: 페이지 자체 스크롤 차단 → 항상 viewport 내부에 layout 유지 */
html, body { overflow: hidden !important; height: 100% !important; }
/* main 을 flex column 으로 — pn-wrap(고정 높이) + workspace(나머지 채움) 가 안전하게 분할 */
main { padding: 0 !important; overflow: hidden !important; min-height: 0 !important; display: flex !important; flex-direction: column !important; }
.dlv-pn-wrap { padding: 20px 24px 0; flex-shrink: 0; }

/* ── 산출물 작성 화면 (3분할) ─────────────────────
   workspace 는 main 의 남은 공간 전체를 차지 → footer 항상 viewport 내부 */
.dlv-workspace { display:flex; flex:1 1 0; min-height:0; overflow:hidden; }

/* 좌: 사이드바 — 펼침/닫힘 토글, 기본은 닫힘 */
.dlv-left { width:200px; flex-shrink:0; background:#faf5ff; border-right:1.5px solid #ede8ff; overflow-y:auto; display:flex; flex-direction:column; border-top:1.5px solid #ede8ff; transition: width .22s ease; }
.dlv-left.is-collapsed { width:0; min-width:0; border-right:none; overflow:hidden; }
.dlv-left.is-collapsed > * { display:none; }

/* 좌측 패널 토글 버튼 (중앙 헤더 좌측에 배치) */
.dlv-left-toggle { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:7px; border:1.5px solid #e2e8f0; background:#fff; color:#64748b; cursor:pointer; transition: all .12s; flex-shrink:0; }
.dlv-left-toggle:hover { border-color:var(--t400); color:var(--t600); background:#faf5ff; }
.dlv-left-back { display:flex; align-items:center; gap:6px; padding:12px 14px; font-size:11.5px; font-weight:700; color:#7c3aed; text-decoration:none; border-bottom:1px solid #ede8ff; transition:background .12s; }
.dlv-left-back:hover { background:#f3eeff; }

.dlv-sidebar-cat { padding:10px 14px 4px; font-size:10px; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:#94a3b8; }
.dlv-sidebar-item { display:flex; align-items:center; gap:6px; padding:6px 14px; text-decoration:none; font-size:11.5px; color:#64748b; border-left:2px solid transparent; transition:all .12s; cursor:pointer; }
.dlv-sidebar-item:hover { background:#f3eeff; color:var(--t600); }
.dlv-sidebar-item.is-current { background:#ede8ff; color:var(--t700); font-weight:700; border-left-color:var(--t500); }
.dlv-sidebar-item .dlv-resp { font-size:9px; padding:1px 5px; }
.dlv-sidebar-item .dlv-dot { width:6px; height:6px; border-radius:50%; flex-shrink:0; }
.dlv-sidebar-item .dlv-dot.completed   { background:#16a34a; }
.dlv-sidebar-item .dlv-dot.in_progress { background:var(--t500); }
.dlv-sidebar-item .dlv-dot.not_started { background:#e2e8f0; }
/* 좌측 사이드바 하단 "+ 산출물" 버튼 — 사이드바가 스크롤돼도 항상 보이도록 sticky */
.dlv-sidebar-add { display:flex; align-items:center; gap:6px; padding:10px 14px; font-size:11.5px; color:var(--t600); font-weight:600; cursor:pointer; border-top:1px solid #ede8ff; margin-top:auto; transition:background .12s; position:sticky; bottom:0; background:#faf5ff; flex-shrink:0; z-index:5; }
.dlv-sidebar-add:hover { background:#f3eeff; }

/* 중: 도구 작업 영역 */
.dlv-center { flex:1; min-width:0; display:flex; flex-direction:column; overflow:hidden; border-top:1.5px solid #ede8ff; }
.dlv-center-header { padding:12px 20px; background:#fff; border-bottom:1.5px solid #ede8ff; flex-shrink:0; }
.dlv-center-header-top { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.dlv-center-title { font-size:14px; font-weight:800; color:#1e1b2e; flex:1; }
.dlv-step-dots { display:flex; align-items:center; gap:4px; }
.dlv-step-dot { width:8px; height:8px; border-radius:50%; background:#e2e8f0; flex-shrink:0; transition:background .2s; }
.dlv-step-dot.is-done   { background:#16a34a; }
.dlv-step-dot.is-active { background:var(--t500); width:20px; border-radius:4px; }
.dlv-center-body { flex:1; overflow-y:auto; padding:20px; }

/* 입력 폼 */
.dlv-step-card { background:#fff; border:1.5px solid #e8e0ff; border-radius:14px; padding:20px; margin-bottom:16px; }
.dlv-step-card h3 { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 4px; }
.dlv-step-card p  { font-size:12.5px; color:#64748b; margin:0 0 14px; }
.dlv-field-label  { font-size:11.5px; font-weight:700; color:#374151; margin-bottom:5px; display:block; }
.dlv-textarea { width:100%; padding:10px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; color:#1e1b2e; resize:vertical; overflow-y:auto; line-height:1.6; transition:border-color .15s; box-sizing:border-box; }
.dlv-textarea:focus { outline:none; border-color:var(--t400); }
.dlv-input { width:100%; padding:8px 12px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; color:#1e1b2e; transition:border-color .15s; box-sizing:border-box; }
.dlv-input:focus { outline:none; border-color:var(--t400); }

/* 도구 슬롯 */
.dlv-tool-slots { display:flex; flex-direction:column; gap:10px; margin-top:10px; }
.dlv-tool-slot { border:1.5px dashed #ddd6fe; border-radius:10px; padding:16px; background:#faf5ff; position:relative; }
.dlv-tool-slot-header { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.dlv-tool-slot-name { font-size:12px; font-weight:700; color:var(--t700); flex:1; }
.dlv-tool-placeholder { text-align:center; padding:28px 16px; }
.dlv-tool-placeholder-icon { width:40px; height:40px; border-radius:10px; background:#ede8ff; display:flex; align-items:center; justify-content:center; margin:0 auto 10px; }
.dlv-tool-placeholder p { font-size:12px; color:#94a3b8; margin:0; }
.dlv-tool-placeholder .tool-id { font-size:10px; font-weight:700; color:var(--t500); background:var(--t100); padding:2px 7px; border-radius:4px; margin-bottom:6px; display:inline-block; }

/* 리뷰/업로드 단계 */
.dlv-review-box { background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:10px; padding:16px; }
.dlv-upload-box { background:#f8fafc; border:1.5px dashed #cbd5e1; border-radius:10px; padding:24px; text-align:center; cursor:pointer; transition:all .15s; }
.dlv-upload-box:hover { border-color:var(--t400); background:#faf5ff; }

/* 하단 액션 바 — STEP 콘텐츠 위에 항상 고정, 버튼 많을 때 자동 줄바꿈 */
.dlv-center-footer {
    padding:10px 20px;
    background:#fff;
    border-top:1.5px solid #ede8ff;
    flex-shrink:0;
    display:flex;
    flex-wrap:wrap;
    align-items:center;
    gap:8px 6px;
    position:sticky;
    bottom:0;
    z-index:20;
    box-shadow:0 -2px 6px rgba(15,23,42,.04);
}
.dlv-center-footer > .dlv-spacer { flex:1 1 0; min-width:0; }
.dlv-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-size:12.5px; font-weight:700; border:none; cursor:pointer; transition:all .15s; }
.dlv-btn-primary { background:var(--t600); color:#fff; }
.dlv-btn-primary:hover { background:var(--t700); }
.dlv-btn-outline { background:#fff; color:#64748b; border:1.5px solid #e2e8f0; }
.dlv-btn-outline:hover { border-color:var(--t400); color:var(--t600); }
.dlv-btn-ghost  { background:transparent; color:#94a3b8; }
.dlv-btn-ghost:hover { color:var(--t600); }

/* 우: 웍스 패널 */
.dlv-right { width:260px; flex-shrink:0; background:#fff; border-left:1.5px solid #ede8ff; border-top:1.5px solid #ede8ff; display:flex; flex-direction:column; overflow:hidden; }
.dlv-ai-header { padding:12px 14px; border-bottom:1px solid #ede8ff; display:flex; align-items:center; gap:7px; flex-shrink:0; }
.dlv-ai-header-title { font-size:12px; font-weight:800; color:#1e1b2e; flex:1; }
.dlv-ai-badge { background:var(--t100); color:var(--t700); font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px; }
.dlv-ai-body { flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:8px; }
.dlv-ai-action-btn { width:100%; display:flex; align-items:center; gap:8px; padding:9px 12px; background:#f8f4ff; border:1.5px solid #ede8ff; border-radius:8px; font-size:11.5px; font-weight:600; color:var(--t700); cursor:pointer; transition:all .15s; text-align:left; }
.dlv-ai-action-btn:hover { background:#ede8ff; border-color:var(--t400); }
.dlv-ai-action-btn svg { flex-shrink:0; }
.dlv-ai-divider { border:none; border-top:1px solid #f3eeff; margin:4px 0; }
.dlv-ai-msg { font-size:11.5px; color:#64748b; line-height:1.6; padding:10px 12px; background:#f8fafc; border-radius:8px; }
/* 우측 AI 입력 영역 — 위 본문이 길어도 항상 viewport 내부에 고정 */
.dlv-ai-input-area { padding:10px 12px; border-top:1px solid #ede8ff; flex-shrink:0; background:#fff; position:sticky; bottom:0; z-index:5; }
.dlv-ai-input { width:100%; padding:8px 10px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:12px; resize:none; min-height:60px; box-sizing:border-box; transition:border-color .15s; }
.dlv-ai-input:focus { outline:none; border-color:var(--t400); }
.dlv-ai-send { width:100%; margin-top:6px; padding:7px; background:var(--t600); color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; transition:background .15s; }
.dlv-ai-send:hover { background:var(--t700); }

/* 반영하기 버튼 */
.dlv-apply-btn { display:inline-flex; align-items:center; gap:5px; margin-top:8px; padding:5px 13px; background:linear-gradient(135deg,#059669,#10b981); color:#fff; border:none; border-radius:7px; font-size:11.5px; font-weight:700; cursor:pointer; transition:opacity .15s; }
.dlv-apply-btn:hover { opacity:.85; }
.dlv-apply-btn:disabled { opacity:.5; cursor:not-allowed; }

/* 진행 바 */
.dlv-prog-bar { height:3px; background:#f1f5f9; }
.dlv-prog-fill { height:100%; transition:width .5s; }

/* 저장 알림 토스트 */
.dlv-toast { position:fixed; bottom:24px; left:50%; transform:translateX(-50%) translateY(20px); background:#1e1b2e; color:#fff; padding:10px 20px; border-radius:10px; font-size:13px; font-weight:600; opacity:0; transition:all .3s; z-index:9999; pointer-events:none; }
.dlv-toast.show { opacity:1; transform:translateX(-50%) translateY(0); }

/* ── Markdown 에디터 탭 ─────────────────────────── */
.md-editor { border:1.5px solid #e2e8f0; border-radius:8px; overflow:hidden; }
.md-tabs { display:flex; background:#f8fafc; border-bottom:1px solid #e2e8f0; padding:0 8px; gap:2px; }
.md-tab { padding:5px 14px; font-size:11.5px; font-weight:600; color:#94a3b8; background:transparent; border:none; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-1px; transition:color .12s,border-color .12s; }
.md-tab.is-active { color:var(--t600); border-bottom-color:var(--t500); }
.md-tab:hover:not(.is-active) { color:#475569; }
.md-tab-tr { margin-left:auto; display:inline-flex; align-items:center; gap:3px; padding:3px 9px; font-size:10.5px; font-weight:700; color:#059669; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:5px; cursor:pointer; transition:all .12s; align-self:center; }
.md-tab-tr:hover { background:#dcfce7; border-color:#86efac; }
.md-tab-tr:disabled { opacity:.5; cursor:not-allowed; }
.md-tr-badge { display:inline-block; font-size:10px; color:#059669; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:4px; padding:2px 8px; margin-bottom:8px; }
.md-edit-pane .dlv-textarea { border:none !important; border-radius:0 !important; box-shadow:none !important; }
.md-edit-pane .dlv-textarea:focus { border:none !important; }
.md-preview-pane { padding:12px 14px; font-size:13px; color:#1e1b2e; line-height:1.75; background:#fff; overflow-y:auto; resize:vertical; }
.md-preview-pane > * { margin-top:0; }
.md-preview-pane h1 { font-size:17px; font-weight:800; margin:0.9em 0 0.4em; border-bottom:1px solid #e2e8f0; padding-bottom:4px; }
.md-preview-pane h2 { font-size:15px; font-weight:700; margin:0.8em 0 0.35em; }
.md-preview-pane h3 { font-size:13px; font-weight:700; margin:0.7em 0 0.3em; }
.md-preview-pane p  { margin:0.45em 0; }
.md-preview-pane ul,.md-preview-pane ol { padding-left:20px; margin:0.4em 0; }
.md-preview-pane li { margin:0.2em 0; }
.md-preview-pane code { background:#f1f5f9; padding:1px 5px; border-radius:4px; font-size:12px; font-family:monospace; }
.md-preview-pane pre { background:#f1f5f9; padding:10px 13px; border-radius:7px; overflow-x:auto; margin:0.6em 0; }
.md-preview-pane pre code { background:none; padding:0; font-size:12px; }
.md-preview-pane blockquote { border-left:3px solid var(--t400); margin:0.5em 0; padding:4px 12px; color:#64748b; background:#faf5ff; border-radius:0 6px 6px 0; }
.md-preview-pane table { border-collapse:collapse; width:100%; font-size:12.5px; margin:0.6em 0; }
.md-preview-pane th,.md-preview-pane td { border:1px solid #e2e8f0; padding:5px 10px; text-align:left; }
.md-preview-pane th { background:#f8fafc; font-weight:700; }
.md-preview-pane hr { border:none; border-top:1px solid #e2e8f0; margin:1em 0; }
.md-preview-pane strong { font-weight:700; }
.md-preview-pane em { font-style:italic; color:#475569; }
.md-preview-empty { color:#94a3b8; font-style:italic; font-size:12px; }

/* ── 뷰어 주석 툴 버튼 (간트 ann-tool-btn 동일) ─── */
.dlv-ann-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px;
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1);
    border-radius:6px; color:#9ca3af; cursor:pointer;
    transition:background .15s, color .15s, border-color .15s;
    padding:0; flex-shrink:0;
}
.dlv-ann-btn:hover  { background:rgba(196,181,253,.15); color:#c4b5fd; }
.dlv-ann-btn.active { background:rgba(196,181,253,.28); color:#c4b5fd; border-color:rgba(196,181,253,.45); }

@keyframes dlvSpin { to { transform: rotate(360deg); } }

/* ── 테이블 빌더 ─────────────────────────────────── */
.dlv-tbl-toolbar { display:flex; align-items:center; gap:4px; margin-bottom:6px; flex-wrap:wrap; }
.dlv-tbl-btn { font-size:11px; padding:3px 8px; gap:3px; }
.dlv-tbl-sep { width:1px; height:18px; background:#e2e8f0; margin:0 2px; }
.dlv-tbl-scroll { overflow:auto; max-height:340px; border:1.5px solid #e2e8f0; border-radius:7px; }
.dlv-tbl-edit { border-collapse:collapse; min-width:100%; font-size:12.5px; table-layout:auto; }
.dlv-tbl-edit th,
.dlv-tbl-edit td { border:1px solid #e2e8f0; padding:0; min-width:90px; }
.dlv-tbl-edit th { background:#f8f4ff; }
.dlv-tbl-cell { width:100%; padding:5px 8px; border:none; outline:none; font-size:12.5px; font-family:inherit; background:transparent; color:#1e1b2e; resize:none; overflow:hidden; box-sizing:border-box; min-height:30px; line-height:1.5; }
.dlv-tbl-cell:focus { background:#faf5ff; }

/* ── 질의응답 폼 빌더 (FORM-QA) ─────────────────────── */
.dlv-qa-section { border:1.5px solid #e2e8f0; border-radius:9px; margin-bottom:10px; overflow:hidden; }
.dlv-qa-sec-hd { display:flex; align-items:center; gap:8px; padding:8px 10px; background:#f8f4ff; border-bottom:1.5px solid #e2e8f0; }
.dlv-qa-sec-num { min-width:22px; height:22px; border-radius:50%; background:var(--t500); color:#fff; font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.dlv-qa-sec-title { flex:1; border:none; background:transparent; font-size:12.5px; font-weight:700; color:#1e1b2e; outline:none; padding:0; }
.dlv-qa-sec-title::placeholder { color:#c4b5fd; font-weight:400; }
.dlv-qa-sec-btns { display:flex; align-items:center; gap:4px; }
.dlv-qa-sec-btn { font-size:10.5px; padding:2px 7px; border-radius:5px; border:1px solid #e2e8f0; background:#fff; color:#64748b; cursor:pointer; font-weight:600; transition:all .12s; white-space:nowrap; }
.dlv-qa-sec-btn:hover { border-color:var(--t400); color:var(--t600); }
.dlv-qa-sec-btn.danger:hover { border-color:#fca5a5; color:#dc2626; background:#fff5f5; }
.dlv-qa-items { padding:8px 10px; display:flex; flex-direction:column; gap:8px; }
.dlv-qa-items.collapsed { display:none; }
.dlv-qa-item { background:#fafafa; border:1px solid #e2e8f0; border-radius:7px; overflow:hidden; }
.dlv-qa-item-hd { display:flex; align-items:center; gap:7px; padding:5px 9px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; }
.dlv-qa-item-num { font-size:9.5px; font-weight:800; color:#64748b; letter-spacing:.05em; }
.dlv-qa-item-del { margin-left:auto; background:none; border:none; cursor:pointer; color:#cbd5e1; font-size:15px; line-height:1; padding:0 2px; transition:color .1s; }
.dlv-qa-item-del:hover { color:#dc2626; }
.dlv-qa-item-body { padding:8px 10px; display:flex; flex-direction:column; gap:7px; }
.dlv-qa-lbl { font-size:10px; font-weight:700; color:#64748b; letter-spacing:.05em; text-transform:uppercase; margin-bottom:2px; }
.dlv-qa-cell { width:100%; border:1px solid #e2e8f0; border-radius:5px; padding:6px 8px; font-size:12px; font-family:inherit; color:#1e1b2e; background:#fff; resize:none; overflow:hidden; box-sizing:border-box; min-height:32px; line-height:1.5; outline:none; transition:border-color .12s; }
.dlv-qa-cell:focus { border-color:var(--t400); background:#faf5ff; }
.dlv-qa-meta { display:flex; gap:8px; align-items:flex-start; }
.dlv-qa-risk-wrap { flex-shrink:0; }
.dlv-qa-risk-pills { display:flex; gap:3px; flex-wrap:wrap; margin-top:2px; }
.dlv-qa-risk-pill { font-size:10px; font-weight:700; padding:2px 8px; border-radius:20px; border:1.5px solid transparent; cursor:pointer; transition:all .12s; }
.dlv-qa-risk-pill[data-risk="none"]     { background:#f1f5f9; color:#64748b; border-color:#e2e8f0; }
.dlv-qa-risk-pill[data-risk="low"]      { background:#f0fdf4; color:#16a34a; border-color:#bbf7d0; }
.dlv-qa-risk-pill[data-risk="medium"]   { background:#fffbeb; color:#d97706; border-color:#fde68a; }
.dlv-qa-risk-pill[data-risk="high"]     { background:#fff7ed; color:#ea580c; border-color:#fed7aa; }
.dlv-qa-risk-pill[data-risk="critical"] { background:#fef2f2; color:#dc2626; border-color:#fecaca; }
.dlv-qa-risk-pill.is-active[data-risk="none"]     { background:#64748b; color:#fff; border-color:#64748b; }
.dlv-qa-risk-pill.is-active[data-risk="low"]      { background:#16a34a; color:#fff; border-color:#16a34a; }
.dlv-qa-risk-pill.is-active[data-risk="medium"]   { background:#d97706; color:#fff; border-color:#d97706; }
.dlv-qa-risk-pill.is-active[data-risk="high"]     { background:#ea580c; color:#fff; border-color:#ea580c; }
.dlv-qa-risk-pill.is-active[data-risk="critical"] { background:#dc2626; color:#fff; border-color:#dc2626; }
.dlv-qa-notes-wrap { flex:1; min-width:0; }
.dlv-qa-empty { text-align:center; padding:28px; color:#94a3b8; font-size:12px; border:1.5px dashed #e2e8f0; border-radius:9px; }

/* ── 다이어그램 빌더 ──────────────────────────────── */
.dlv-dgr-panes { display:flex; gap:8px; height:280px; }
.dlv-dgr-code-pane { flex:1; display:flex; flex-direction:column; min-width:0; }
.dlv-dgr-textarea { flex:1; width:100%; padding:8px 10px; border:1.5px solid #e2e8f0; border-radius:7px; font-size:12px; font-family:'Courier New',monospace; resize:none; box-sizing:border-box; color:#1e1b2e; background:#fafafa; transition:border-color .15s; }
.dlv-dgr-textarea:focus { outline:none; border-color:var(--t400); }
.dlv-dgr-preview-pane { flex:1; display:flex; flex-direction:column; min-width:0; }
.dlv-dgr-preview { flex:1; min-height:0; overflow:hidden; border:1.5px solid #e2e8f0; border-radius:7px; background:#fff; position:relative; }
.dlv-dgr-preview > svg { position:absolute; left:0; top:0; }
.dlv-dgr-zoom-ind { position:absolute; bottom:5px; right:6px; font-size:10px; font-weight:700; color:#7c3aed; background:rgba(237,232,255,.92); border:1px solid #ddd6fe; border-radius:4px; padding:1px 6px; pointer-events:none; user-select:none; opacity:0; transition:opacity .3s; z-index:2; }
.dlv-dgr-err { font-size:11px; color:#ef4444; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:8px 10px; margin-top:4px; white-space:pre-wrap; }
.dlv-dgr-preview:has(svg):hover { border-color:var(--t400); box-shadow:0 0 0 2px rgba(124,58,237,.15); }
.dlv-dgr-preview:has(svg) { cursor:grab; }
.dlv-dgr-pv-wrap { position:relative; flex:1; min-height:0; display:flex; flex-direction:column; min-width:0; }
.dlv-dgr-open-lb-btn { position:absolute; top:6px; right:6px; z-index:3; display:inline-flex; align-items:center; gap:4px; padding:5px 10px; background:rgba(124,58,237,.82); color:#fff; border:none; border-radius:6px; font-size:11px; font-weight:700; cursor:pointer; opacity:0; transition:opacity .2s; pointer-events:none; backdrop-filter:blur(2px); }
.dlv-dgr-pv-wrap:hover .dlv-dgr-open-lb-btn { opacity:1; pointer-events:auto; }
#dlv-dgr-lb-dl:hover { opacity:.85; }
#dlv-dgr-lb-dl:active { transform:scale(.97); }
.dlv-word-pop { position:fixed; z-index:99999; background:#fff; border:1.5px solid #e2e8f0; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.13); min-width:196px; overflow:hidden; animation:dlvPopIn .1s ease; }
@keyframes dlvPopIn { from { opacity:0; transform:translateY(-4px); } to { opacity:1; transform:translateY(0); } }
.dlv-word-pop-item { display:flex; align-items:center; gap:8px; padding:9px 14px; font-size:12.5px; font-weight:600; color:#374151; cursor:pointer; transition:background .1s; white-space:nowrap; }
.dlv-word-pop-item:hover { background:#f5f3ff; color:#7c3aed; }
.dlv-word-pop-item + .dlv-word-pop-item { border-top:1px solid #f1f5f9; }

/* ── 산출물 → 파일 등록 다이얼로그: 좌우 2열 (이력 사이드) ── */
#dlv-reg-modal .dlv-reg-box { width:720px !important; max-width:96vw; height:auto; max-height:90vh; display:flex; flex-direction:column; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 20px 50px rgba(0,0,0,.25); }
#dlv-reg-modal .dlv-reg-head { padding:16px 22px 12px; border-bottom:1px solid #f1f5f9; flex-shrink:0; }
#dlv-reg-modal .dlv-reg-body { flex:1 1 auto; min-height:0; display:flex !important; flex-direction:row !important; }
#dlv-reg-modal .dlv-reg-side { width:260px; flex-shrink:0; background:#fafafa; border-right:1px solid #f1f5f9; display:flex; flex-direction:column; min-height:0; }
#dlv-reg-modal .dlv-reg-side-head { padding:10px 14px; font-size:11.5px; font-weight:700; color:#475569; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
#dlv-reg-modal .dlv-reg-side-list { flex:1 1 0; min-height:0; overflow-y:auto; padding:4px 0; }
#dlv-reg-modal .dlv-reg-main { flex:1; min-width:0; overflow:visible; padding:14px 20px 16px; }
#dlv-reg-modal .dlv-reg-foot { padding:11px 22px 14px; border-top:1px solid #f1f5f9; flex-shrink:0; display:flex; justify-content:flex-end; gap:8px; background:#fff; }
#dlv-reg-modal .dlv-reg-field { margin-bottom:14px; }
#dlv-reg-modal .dlv-reg-field-label { display:block; font-size:12px; font-weight:600; color:#334155; margin-bottom:6px; }
#dlv-reg-modal .dlv-reg-lang-row { display:flex; gap:6px; }
#dlv-reg-modal .dlv-reg-lang-opt { flex:1; display:flex; align-items:center; gap:6px; padding:7px 10px; border:1.5px solid #e2e8f0; border-radius:7px; cursor:pointer; font-size:12.5px; }
#dlv-reg-modal .dlv-reg-textarea { width:100%; padding:8px 10px; font-size:13px; border:1.5px solid #e2e8f0; border-radius:8px; resize:vertical; box-sizing:border-box; line-height:1.5; outline:none; }
#dlv-reg-modal .dlv-reg-textarea:focus { border-color:#a78bfa; }
#dlv-reg-modal .dlv-reg-search { width:100%; padding:7px 10px 7px 28px; font-size:12.5px; border:1.5px solid #e2e8f0; border-radius:7px; box-sizing:border-box; outline:none; }
#dlv-reg-modal .dlv-reg-search-wrap { position:relative; }
#dlv-reg-modal .dlv-reg-search-icon { position:absolute; left:9px; top:50%; transform:translateY(-50%); pointer-events:none; }
/* 파일 목록 = 드롭다운 (검색창 focus 시 표시, 선택/외부클릭 시 닫힘) */
#dlv-reg-modal .dlv-reg-filelist {
    position:absolute; left:0; right:0; top:calc(100% + 4px); z-index:50;
    border:1.5px solid #c4b5fd; border-radius:8px; max-height:240px; overflow-y:auto;
    background:#fff; box-shadow:0 10px 28px rgba(15,23,42,.16); display:none;
}
#dlv-reg-modal .dlv-reg-filelist.is-open { display:block; }
#dlv-reg-modal .dlv-reg-selected { margin-top:6px; display:none; align-items:center; gap:6px; padding:7px 10px; background:#f5f3ff; border:1.5px solid #ddd6fe; border-radius:7px; font-size:12.5px; color:#0f172a; }
#dlv-reg-modal .dlv-reg-selected.is-shown { display:flex; }
#dlv-reg-modal .dlv-reg-selected-clear { margin-left:auto; background:none; border:none; color:#94a3b8; font-size:14px; cursor:pointer; padding:0 4px; line-height:1; }

/* row 공통 (사이드 이력 / 메인 파일 목록 / 등록 대상 옵션) */
#dlv-reg-modal .dlv-reg-row { display:flex !important; flex-direction:row !important; align-items:flex-start; gap:8px; padding:8px 10px; border-bottom:1px solid #f1f5f9; cursor:pointer; }
#dlv-reg-modal .dlv-reg-row:hover { background:#f5f3ff; }
#dlv-reg-modal .dlv-reg-row > .dlv-reg-row-lead { flex:0 0 auto; display:flex; align-items:flex-start; }
#dlv-reg-modal .dlv-reg-row > .dlv-reg-row-body { flex:1 1 0; min-width:0; display:flex; flex-direction:column; gap:2px; }
#dlv-reg-modal .dlv-reg-row-title { display:flex; align-items:center; gap:6px; min-width:0; color:#0f172a; font-weight:600; font-size:13px; }
#dlv-reg-modal .dlv-reg-row-title-name { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:0; }
#dlv-reg-modal .dlv-reg-row-meta { color:#94a3b8; font-size:11px; }
#dlv-reg-modal .dlv-reg-row-vbadge { font-weight:700; color:#7c3aed; font-size:11px; min-width:24px; padding-top:1px; }
#dlv-reg-modal .dlv-reg-target-opt { display:flex !important; flex-direction:row !important; align-items:flex-start; gap:8px; padding:10px; border:1.5px solid #e2e8f0; border-radius:8px; margin-bottom:8px; cursor:pointer; }
#dlv-reg-modal .dlv-reg-target-opt > input[type="radio"] { flex-shrink:0; margin-top:3px; }
#dlv-reg-modal .dlv-reg-target-opt > .dlv-reg-row-body { flex:1 1 0; min-width:0; }
#dlv-reg-modal .dlv-reg-cat-badge { display:inline-block; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; flex-shrink:0; }
</style>
@endpush

@section('content')
<div class="dlv-pn-wrap">
    @include('partials.project-nav', ['project' => $project, 'active' => 'deliverables'])
</div>
<div class="dlv-toast" id="dlv-toast">{{ __('deliverables.toast_saved') }}</div>

<div class="dlv-workspace">

    {{-- ── 좌: 사이드바 (기본 닫힘, 토글 버튼으로 펼침) ──────── --}}
    <div class="dlv-left is-collapsed" id="dlv-left">
        <a href="{{ route('ai-agent.projects.deliverables.index', $project) }}" class="dlv-left-back">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            {{ __('deliverables.back_dashboard') }}
        </a>

        @foreach(config('deliverables.categories') as $catKey => $cat)
            @php
                $catItems = collect($allTypes)->filter(fn($t) => $t['category'] === $catKey)->sortBy('no');
            @endphp
            @if($catItems->count())
            <div class="dlv-sidebar-cat">{{ __('deliverables.cat_' . $catKey, [], null) ?: $cat['label'] }}</div>
            @foreach($catItems as $tid => $tdef)
                @php
                    // deliverables는 뷰에서 직접 DB 조회하지 않고 typeId 비교만
                    $isCurrent = $tid === $typeId;
                @endphp
                <a href="{{ route('ai-agent.projects.deliverables.show', [$project, $tid]) }}"
                   class="dlv-sidebar-item {{ $isCurrent ? 'is-current' : '' }}">
                    <span class="dlv-dot not_started"></span>
                    <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $tdef['name'] }}">{{ $tdef['shortName'] }}</span>
                    <span class="dlv-resp {{ $tdef['responsibility'] === 'A+B' ? 'ab' : 'b' }}">{{ $tdef['responsibility'] }}</span>
                </a>
            @endforeach
            @endif
        @endforeach

        <div class="dlv-sidebar-add" onclick="alert('{{ __('deliverables.add_custom') }}')">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            {{ __('deliverables.new_deliverable') }}
        </div>
    </div>

    {{-- ── 중: 도구 작업 영역 ─────────────────────────── --}}
    <div class="dlv-center">
        {{-- 헤더 --}}
        <div class="dlv-center-header">
            <div class="dlv-center-header-top">
                <button class="dlv-left-toggle" type="button" onclick="dlvToggleLeft()" title="산출물 목록 펼치기/접기" aria-label="toggle deliverable list">
                    <svg id="dlv-left-toggle-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="dlv-center-title">
                    <span style="font-size:11px;color:#94a3b8;display:block;margin-bottom:1px;">{{ $typeDef['shortName'] }}</span>
                    {{ $typeDef['name'] }}
                </div>
                <span class="dlv-resp {{ $typeDef['responsibility'] === 'A+B' ? 'ab' : 'b' }}" style="font-size:11px;padding:2px 8px;">{{ $typeDef['responsibility'] }}</span>
                <span style="font-size:11px;color:#94a3b8;">{{ $stepNo }}/{{ $totalSteps }}</span>
            </div>
            {{-- 단계 점 인디케이터 --}}
            <div class="dlv-step-dots">
                @foreach($typeDef['steps'] as $s)
                    <div class="dlv-step-dot {{ $s['order'] < $stepNo ? 'is-done' : ($s['order'] === $stepNo ? 'is-active' : '') }}"
                         title="{{ __('deliverables.step_tooltip', ['order' => $s['order'], 'title' => $s['title']]) }}"
                         onclick="goStep({{ $s['order'] }})"
                         style="cursor:pointer;"></div>
                @endforeach
            </div>
        </div>

        {{-- 진행 바 --}}
        <div class="dlv-prog-bar">
            <div class="dlv-prog-fill" style="width:{{ $pct }}%;background:{{ $catColor }};"></div>
        </div>

        {{-- 본문 --}}
        <div class="dlv-center-body" id="step-body">
            @if($currentStep)
            <form id="step-form" onsubmit="return false;">
                <div class="dlv-step-card">
                    <h3>{{ $currentStep['order'] }}. {{ $currentStep['title'] }}</h3>
                    <p>{{ $currentStep['description'] }}</p>

                    {{-- 필드 렌더링 --}}
                    @foreach($currentStep['fields'] as $field)
                        <div style="margin-bottom:12px;">
                            <label class="dlv-field-label">{{ $field['label'] }}</label>

                            @if($field['type'] === 'textarea')
                                @php
                                    $fieldMinHeight = match($field['key']) {
                                        'background'           => 'height:360px;',
                                        'scope', 'objectives'  => 'height:280px;',
                                        default                => 'height:200px;',
                                    };
                                    $enData = $deliverable->getStepEnData($stepNo, $field['key']);
                                @endphp
                                <div class="md-editor">
                                    <div class="md-tabs">
                                        <button type="button" class="md-tab is-active" onclick="mdSwitchTab(this,'preview')">{{ __('deliverables.tab_preview') }}</button>
                                        <button type="button" class="md-tab" onclick="mdSwitchTab(this,'edit')">{{ __('deliverables.tab_edit') }}</button>
                                        <button type="button" class="md-tab-tr" onclick="mdTranslate(this)" title="{{ __('deliverables.tab_translate_title') }}">🌐 {{ __('deliverables.tab_translate') }}</button>
                                    </div>
                                    <div class="md-edit-pane" style="display:none;">
                                        <textarea class="dlv-textarea"
                                                  name="fields[{{ $field['key'] }}]"
                                                  style="{{ $fieldMinHeight }}"
                                                  data-field-key="{{ $field['key'] }}"
                                                  data-has-en="{{ $enData['valid'] ? 'true' : 'false' }}"
                                                  data-en-value="{{ $enData['valid'] ? $enData['en_value'] : '' }}"
                                                  placeholder="{{ __('deliverables.textarea_ph', ['label' => $field['label']]) }}">{{ $deliverable->getStepValue($stepNo, $field['key']) }}</textarea>
                                    </div>
                                    <div class="md-preview-pane" style="{{ $fieldMinHeight }}"></div>
                                </div>

                            @elseif($field['type'] === 'upload')
                                <div class="dlv-upload-box" onclick="document.getElementById('upload-{{ $field['key'] }}').click()">
                                    <svg width="28" height="28" fill="none" stroke="#94a3b8" viewBox="0 0 24 24" style="margin:0 auto 8px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                                    <p style="font-size:12.5px;color:#64748b;margin:0;">{{ __('deliverables.upload_click') }}</p>
                                    <p style="font-size:11px;color:#94a3b8;margin:4px 0 0;">{{ __('deliverables.upload_types') }}</p>
                                    <input type="file" id="upload-{{ $field['key'] }}" style="display:none" multiple>
                                </div>

                            @elseif($field['type'] === 'table')
                                {{-- 간단한 동적 테이블 --}}
                                <div id="tbl-{{ $field['key'] }}" class="dlv-tool-slot">
                                    <div class="dlv-tool-slot-header">
                                        <svg width="14" height="14" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M3 6h18M3 14h18M3 18h18"/></svg>
                                        <span class="dlv-tool-slot-name">TABLE-DATA</span>
                                        <span style="font-size:10px;color:#94a3b8;">{{ __('deliverables.table_slot_hint') }}</span>
                                    </div>
                                    <div class="md-editor">
                                        <div class="md-tabs">
                                            <button type="button" class="md-tab is-active" onclick="mdSwitchTab(this,'preview')">{{ __('deliverables.tab_preview') }}</button>
                                            <button type="button" class="md-tab" onclick="mdSwitchTab(this,'edit')">{{ __('deliverables.tab_edit') }}</button>
                                            <button type="button" class="md-tab-tr" onclick="mdTranslate(this)" title="{{ __('deliverables.tab_translate_title') }}">🌐 {{ __('deliverables.tab_translate') }}</button>
                                        </div>
                                        <div class="md-edit-pane" style="display:none;">
                                            @php $enDataTbl = $deliverable->getStepEnData($stepNo, $field['key']); @endphp
                                            <textarea class="dlv-textarea" name="fields[{{ $field['key'] }}]"
                                                      style="height:200px;"
                                                      data-field-key="{{ $field['key'] }}"
                                                      data-has-en="{{ $enDataTbl['valid'] ? 'true' : 'false' }}"
                                                      data-en-value="{{ $enDataTbl['valid'] ? $enDataTbl['en_value'] : '' }}"
                                                      placeholder="{!! str_replace(chr(10), '&#10;', e(__('deliverables.table_ph'))) !!}">{{ $deliverable->getStepValue($stepNo, $field['key']) }}</textarea>
                                        </div>
                                        <div class="md-preview-pane" style="height:200px;"></div>
                                    </div>
                                </div>

                            @else
                                <input class="dlv-input" type="text" name="fields[{{ $field['key'] }}]"
                                       placeholder="{{ $field['label'] }}"
                                       value="{{ $deliverable->getStepValue($stepNo, $field['key']) }}">
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- 도구 슬롯 --}}
                @php $stepHasExport = collect($currentStep['tools'])->contains('toolId', 'EXPORT'); @endphp
                <div class="dlv-tool-slots">
                    @foreach($currentStep['tools'] as $tb)
                        @php
                            $toolDef = $tools[$tb['toolId']] ?? null;
                            $toolResult = $deliverable->getToolResult($stepNo, $tb['toolId']);
                        @endphp
                        <div class="dlv-tool-slot" id="tool-{{ $tb['toolId'] }}">
                            <div class="dlv-tool-slot-header">
                                @include('ai-agent.deliverables.partials.tool-icon', ['toolId' => $tb['toolId']])
                                <span class="dlv-tool-slot-name">{{ $toolDef['name'] ?? $tb['toolId'] }}</span>
                                <span style="font-size:10px;padding:2px 6px;background:#f1f5f9;color:#64748b;border-radius:4px;font-weight:600;">{{ $tb['toolId'] }}</span>
                            </div>
                            @include('ai-agent.deliverables.partials.tool-widget', [
                                'toolId'         => $tb['toolId'],
                                'toolDef'        => $toolDef,
                                'toolResult'     => $toolResult,
                                'stepNo'         => $stepNo,
                                'project'        => $project,
                                'deliverable'    => $deliverable,
                                'typeId'         => $typeId,
                                'projectMembers' => $projectMembers,
                                'stepApproval'   => $stepApproval,
                            ])
                        </div>
                    @endforeach
                    {{-- 다중 포맷 출력: 모든 단계에 항상 표시 (config에 없는 경우 자동 추가) --}}
                    @if(!$stepHasExport)
                    <div class="dlv-tool-slot" id="tool-EXPORT">
                        <div class="dlv-tool-slot-header">
                            @include('ai-agent.deliverables.partials.tool-icon', ['toolId' => 'EXPORT'])
                            <span class="dlv-tool-slot-name">{{ $tools['EXPORT']['name'] ?? '다중 포맷 출력' }}</span>
                            <span style="font-size:10px;padding:2px 6px;background:#f1f5f9;color:#64748b;border-radius:4px;font-weight:600;">EXPORT</span>
                        </div>
                        @include('ai-agent.deliverables.partials.tool-widget', [
                            'toolId'         => 'EXPORT',
                            'toolDef'        => $tools['EXPORT'] ?? null,
                            'toolResult'     => null,
                            'stepNo'         => $stepNo,
                            'project'        => $project,
                            'deliverable'    => $deliverable,
                            'typeId'         => $typeId,
                            'projectMembers' => $projectMembers,
                            'stepApproval'   => $stepApproval,
                        ])
                    </div>
                    @endif
                </div>
            </form>
            @endif
        </div>

        {{-- 하단 액션 바 --}}
        <div class="dlv-center-footer">
            @if($stepNo > 1)
            <button class="dlv-btn dlv-btn-outline" onclick="goStep({{ $stepNo - 1 }})">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('deliverables.btn_prev_step') }}
            </button>
            @endif

            <button class="dlv-btn dlv-btn-outline" onclick="saveStep(false)">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                {{ __('deliverables.btn_save') }}
            </button>

            <div class="dlv-spacer"></div>

            @if($stepNo < $totalSteps)
            <button class="dlv-btn dlv-btn-primary" onclick="saveStep(true)">
                {{ __('deliverables.btn_show_tool') }}
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            <button class="dlv-btn dlv-btn-primary" onclick="saveAndNext()">
                {{ __('deliverables.btn_next_step') }}
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
            @else
            <button id="dlv-tr-all-btn" class="dlv-btn dlv-btn-outline" onclick="dlvTranslateAll()" style="color:#0284c7;border-color:#bae6fd;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                모든 STEP 영문 변환
            </button>
            <button class="dlv-btn dlv-btn-primary" onclick="completeDeliverable()" style="background:#16a34a;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('deliverables.btn_complete') }}
            </button>
            @endif

            <button class="dlv-btn dlv-btn-ghost" title="{{ __('deliverables.btn_skip') }}" onclick="skipStep()">{{ __('deliverables.btn_skip') }}</button>

            {{-- 버전 이력 --}}
            <button onclick="dlvOpenVersions()" title="STEP 버전 이력" style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:8px;font-size:12.5px;font-weight:700;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                버전 이력
            </button>

            {{-- Word 다운로드 팝오버 --}}
            <button id="word-dl-btn2" onclick="dlvWordPopover(this)" style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:8px;font-size:12.5px;font-weight:700;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Word 다운로드
            </button>

            {{-- 파일로 등록 --}}
            <button onclick="dlvRegisterAsFile()" title="현재 산출물을 프로젝트 파일로 등록 (재등록 시 파일 버전 자동 증가)" style="display:inline-flex;align-items:center;gap:5px;padding:7px 13px;border-radius:8px;font-size:12.5px;font-weight:700;border:1.5px solid #e2e8f0;background:#fff;color:#64748b;cursor:pointer;transition:all .15s;" onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'" onmouseout="this.style.borderColor='#e2e8f0';this.style.color='#64748b'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                파일로 등록
            </button>
        </div>
    </div>

    {{-- ── 우: 웍스 패널 ──────────────────────────────── --}}
    <div class="dlv-right">
        <div class="dlv-ai-header">
            <svg width="14" height="14" fill="none" stroke="var(--t500)" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
            <span class="dlv-ai-header-title">{{ __('deliverables.ai_title') }}</span>
            <span class="dlv-ai-badge">{{ __('deliverables.ai_badge') }}</span>
        </div>

        <div class="dlv-ai-body" id="ai-body">
            <div class="dlv-ai-msg">
                {!! __('deliverables.ai_greeting', ['short' => e($typeDef['shortName'])]) !!}<br><br>
                {!! __('deliverables.ai_current_step', ['order' => $currentStep['order'] ?? 1, 'title' => e($currentStep['title'] ?? '')]) !!}
            </div>

            <button class="dlv-ai-action-btn" onclick="aiAction('draft')">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                {{ __('deliverables.ai_btn_draft') }}
            </button>
            <button class="dlv-ai-action-btn" onclick="aiAction('validate')">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('deliverables.ai_btn_validate') }}
            </button>
            <button class="dlv-ai-action-btn" onclick="aiAction('suggest')">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ __('deliverables.ai_btn_suggest') }}
            </button>
            <button class="dlv-ai-action-btn" onclick="aiAction('standard')">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                {{ __('deliverables.ai_btn_standard') }}
            </button>
            <hr class="dlv-ai-divider">
            <button class="dlv-ai-action-btn" onclick="aiAction('tool-generate')">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/></svg>
                {{ __('deliverables.ai_btn_tool') }}
            </button>
        </div>

        <div class="dlv-ai-input-area">
            <textarea class="dlv-ai-input" id="ai-question" placeholder="{{ __('deliverables.ai_input_ph') }}"
                      onkeydown="if(event.ctrlKey&&event.key==='Enter'){sendAiMessage();}"></textarea>
            <button class="dlv-ai-send" onclick="sendAiMessage()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                {{ __('deliverables.ai_send') }}
            </button>
        </div>
    </div>

</div>

{{-- ── 텍스트 주석 입력 팝업 (간트 ann-text-popup 동일) ──────── --}}
<div id="dlv-text-popup" style="display:none;position:fixed;z-index:10010;background:#fff;border:2px solid #a78bfa;border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);min-width:280px;max-width:360px;">
    <div style="font-size:11px;font-weight:700;color:#6d28d9;margin-bottom:8px;">{{ __('viewer.ann_text_title') }}</div>
    <textarea id="dlv-text-input" rows="4" placeholder="{{ __('viewer.ann_text_placeholder') }}"
           style="width:100%;border:1.5px solid #e5e7eb;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;line-height:1.5;font-family:inherit;transition:border-color .15s;"
           onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button onclick="dlvConfirmText()" style="flex:1;padding:6px 0;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">{{ __('viewer.ann_confirm') }}</button>
        <button onclick="dlvCancelText()"  style="flex:1;padding:6px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">{{ __('viewer.ann_cancel') }}</button>
    </div>
</div>

{{-- ── 산출물 뷰어 팝업 ──────────────────────────────────── --}}
<div id="dlv-viewer-modal" style="display:none;position:fixed;inset:0;z-index:9900;background:rgba(10,8,20,.75);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:32px;">
<div style="width:100%;max-width:1200px;height:calc(100vh - 64px);max-height:820px;background:#1a1730;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6);border:1px solid rgba(196,181,253,.15);">

    {{-- 상단바 --}}
    <div style="height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:12px;padding:0 16px;flex-shrink:0;border-radius:16px 16px 0 0;">
        <button onclick="dlvCloseViewer()" style="display:inline-flex;align-items:center;gap:6px;color:#c4b5fd;font-size:13px;font-weight:600;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;transition:background .15s;" onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            닫기
        </button>
        <span id="dlv-viewer-step-title" style="flex:1;overflow:hidden;font-size:13px;font-weight:600;color:#e5e7eb;white-space:nowrap;text-overflow:ellipsis;"></span>
        <span style="font-size:11px;font-weight:700;padding:3px 9px;border-radius:5px;background:#2d1b5e;color:#c4b5fd;flex-shrink:0;">{{ $typeDef['shortName'] }}</span>
        <button onclick="dlvWordPopover(this)" style="display:inline-flex;align-items:center;gap:5px;color:#a5b4fc;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(165,180,252,.25);border-radius:7px;background:none;cursor:pointer;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Word 다운로드
        </button>
        <button id="dlv-ann-dl-btn" style="display:none;align-items:center;gap:5px;color:#c4b5fd;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(196,181,253,.25);border-radius:7px;flex-shrink:0;background:none;cursor:pointer;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            주석 포함 다운로드
        </button>
    </div>

    {{-- 주석 툴바 (뷰어 열면 자동 표시, 간트 동일) --}}
    <div id="dlv-ann-toolbar" style="display:none;height:42px;background:rgba(12,9,26,.98);border-bottom:1px solid rgba(196,181,253,.08);align-items:center;gap:4px;padding:0 14px;flex-shrink:0;">
        <span style="font-size:10px;font-weight:600;color:#6b7280;letter-spacing:.4px;margin-right:4px;">도형 주석</span>
        <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 4px;"></div>
        <button id="dlv-ann-btn-number" onclick="dlvSetAnnTool('number')" data-tool="number" title="순서 번호" class="dlv-ann-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.5" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
        <button id="dlv-ann-btn-rect"   onclick="dlvSetAnnTool('rect')"   data-tool="rect"   title="사각형"   class="dlv-ann-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
        <button id="dlv-ann-btn-circle" onclick="dlvSetAnnTool('circle')" data-tool="circle" title="원"       class="dlv-ann-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
        <button id="dlv-ann-btn-line"   onclick="dlvSetAnnTool('line')"   data-tool="line"   title="화살표 선" class="dlv-ann-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
        <button id="dlv-ann-btn-text"   onclick="dlvSetAnnTool('text')"   data-tool="text"   title="텍스트"   class="dlv-ann-btn" style="font-size:13px;font-weight:700;line-height:1;">T</button>
        <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 6px;"></div>
        <span style="font-size:10px;color:#6b7280;margin-right:4px;">색상</span>
        <button onclick="dlvSetAnnColor('#ef4444')" data-color="#ef4444" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#ef4444;border:none;cursor:pointer;padding:0;outline:2px solid #fff;outline-offset:2px;flex-shrink:0;"></button>
        <button onclick="dlvSetAnnColor('#f97316')" data-color="#f97316" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#f97316;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="dlvSetAnnColor('#eab308')" data-color="#eab308" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#eab308;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="dlvSetAnnColor('#22c55e')" data-color="#22c55e" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#22c55e;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="dlvSetAnnColor('#3b82f6')" data-color="#3b82f6" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#3b82f6;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <button onclick="dlvSetAnnColor('#a855f7')" data-color="#a855f7" class="dlv-ann-color" style="width:16px;height:16px;border-radius:50%;background:#a855f7;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
        <div style="flex:1;"></div>
        <span style="font-size:10px;color:#4b5563;">{{ __('viewer.ann_hint') }}</span>
    </div>

    {{-- 본문 (뷰어 + 의견) --}}
    <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

        {{-- 뷰어 영역 (간트와 동일한 레이어 구조) --}}
        <div style="flex:1;min-width:0;position:relative;background:#1f2937;overflow:hidden;display:flex;flex-direction:column;">

            {{-- 공통 로딩 오버레이 [z-index:2] --}}
            <div id="dlv-viewer-loading" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:14px;gap:12px;z-index:2;background:#1f2937;">
                <div style="width:36px;height:36px;border:3px solid rgba(196,181,253,.2);border-top-color:#9b8afb;border-radius:50%;animation:dlvSpin .8s linear infinite;"></div>
                <span>불러오는 중…</span>
            </div>

            {{-- 콘텐츠 뷰어 [z-index:1, position:absolute;inset:0] --}}
            <div id="dlv-viewer-wrap" style="display:none;position:absolute;inset:0;z-index:1;">
                {{-- 스크롤 콘텐츠 영역 (하단 44px 컨트롤바 제외) --}}
                <div id="dlv-scroll-wrap" style="position:absolute;top:0;left:0;right:0;bottom:44px;overflow:auto;background:#1f2937;cursor:default;user-select:none;">
                    <div id="dlv-content-inner" style="transform-origin:top center;padding:28px 36px;">
                        @foreach($typeDef['steps'] as $step)
                        <div class="dlv-step-page" data-step="{{ $step['order'] }}" data-title="{{ e($step['title']) }}" style="display:none;">
                            @php $stepHasContent = false; @endphp
                            @foreach($step['fields'] ?? [] as $field)
                                @php
                                    $rawVal = $deliverable->getStepValue($step['order'], $field['key']);
                                    $isTable = ($field['type'] ?? '') === 'table';
                                    $hasRows = false;
                                    $tableRows = [];
                                    if ($isTable && $rawVal !== null) {
                                        $decoded = json_decode($rawVal, true);
                                        if (is_array($decoded)) {
                                            $tableRows = $decoded;
                                            $hasRows = count(array_filter($tableRows, fn($r) => is_array($r) && !empty(array_filter($r, fn($c) => trim((string)$c) !== '')))) > 0;
                                            $displayVal = $hasRows ? $tableRows : null;
                                        } else {
                                            $displayVal = trim((string)$rawVal) !== '' ? $rawVal : null;
                                            $isTable = false;
                                        }
                                    } else {
                                        $displayVal = $rawVal !== null && trim((string)$rawVal) !== '' ? $rawVal : null;
                                    }
                                @endphp
                                @if($displayVal !== null)
                                    @php $stepHasContent = true; @endphp
                                    <div style="margin-bottom:18px;">
                                        <div style="font-size:11px;font-weight:700;color:#8b5cf6;margin-bottom:8px;letter-spacing:.04em;text-transform:uppercase;">{{ $field['label'] }}</div>
                                        @if($isTable && $hasRows)
                                            <div style="overflow-x:auto;border-radius:8px;border:1px solid rgba(196,181,253,.15);">
                                                <table style="width:100%;border-collapse:collapse;font-size:12px;">
                                                    @foreach($tableRows as $ri => $row)
                                                    @if(is_array($row))
                                                    <tr style="border-bottom:1px solid rgba(196,181,253,.08);">
                                                        @foreach($row as $cell)
                                                        <td style="padding:7px 12px;color:#d1d5db;white-space:pre-wrap;word-break:break-word;vertical-align:top;@if($ri===0)font-weight:600;background:rgba(124,58,237,.15);@else background:rgba(255,255,255,.03);@endif">{{ $cell }}</td>
                                                        @endforeach
                                                    </tr>
                                                    @endif
                                                    @endforeach
                                                </table>
                                            </div>
                                        @else
                                            <div style="font-size:13px;color:#d1d5db;line-height:1.75;white-space:pre-wrap;word-break:break-word;padding:14px 18px;background:rgba(255,255,255,.04);border-radius:8px;border:1px solid rgba(196,181,253,.12);">{{ $displayVal }}</div>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            @if(!$stepHasContent)
                                <div style="text-align:center;padding:48px 20px;color:#6b7280;font-size:13px;font-style:italic;">(미입력)</div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                {{-- 하단 컨트롤바 (position:absolute;bottom:0, 간트 동일) --}}
                <div style="position:absolute;bottom:0;left:0;right:0;height:44px;display:flex;align-items:center;justify-content:center;gap:8px;padding:0 16px;background:#111827;border-top:1px solid rgba(255,255,255,.07);">
                    <button id="dlv-btn-prev" onclick="dlvPrevStep()" disabled
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="if(!this.disabled)this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                        이전
                    </button>
                    <span id="dlv-step-counter" style="font-size:13px;font-weight:600;color:#e5e7eb;min-width:80px;text-align:center;">— / —</span>
                    <button id="dlv-btn-next" onclick="dlvNextStep()"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="if(!this.disabled)this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        다음
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                    <button onclick="dlvZoomOut()" style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;" onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">−</button>
                    <span id="dlv-zoom-label" style="font-size:12px;color:#9ca3af;min-width:40px;text-align:center;">100%</span>
                    <button onclick="dlvZoomIn()" style="padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;" onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">+</button>
                    <button onclick="dlvZoomFit()" style="padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;" onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">맞춤</button>
                    <button onclick="dlvZoomOriginal()" style="padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;" onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">원본</button>
                </div>
            </div>

            {{-- SVG 주석 오버레이 [z-index:20, inset:0, 간트 동일] --}}
            <svg id="dlv-ann-svg" xmlns="http://www.w3.org/2000/svg"
                 style="position:absolute;inset:0;width:100%;height:100%;z-index:20;pointer-events:none;overflow:visible;"
                 onmousedown="dlvAnnDown(event)" onmousemove="dlvAnnMove(event)" onmouseup="dlvAnnUp(event)"></svg>
        </div>

        {{-- 의견 패널 (간트 동일: background:#fff, border-left:#e5e7eb) --}}
        <div style="width:260px;flex-shrink:0;background:#fff;border-left:1px solid #e5e7eb;display:flex;flex-direction:column;">

            {{-- 패널 헤더 --}}
            <div style="padding:12px 16px 10px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
                <div style="font-size:14px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px;">
                    <svg width="15" height="15" fill="none" stroke="#6d28d9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    의견
                    <span id="dlv-cm-count" style="font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700;"></span>
                </div>
                <div style="display:flex;gap:6px;margin-top:7px;">
                    <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:#ede9fe;color:#6d28d9;font-weight:600;">화면주석</span>
                    <span style="font-size:10px;padding:2px 7px;border-radius:4px;background:#f0fdf4;color:#065f46;font-weight:600;">일반</span>
                </div>
                {{-- 단계 필터 바 --}}
                <div id="dlv-cmt-filter-bar" style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <span id="dlv-cmt-filter-label" style="font-size:11px;color:#6b7280;"></span>
                    <button onclick="dlvToggleCmtFilter()" id="dlv-cmt-filter-btn"
                            style="font-size:11px;color:#7c3aed;background:none;border:1px solid #ede9fe;border-radius:5px;cursor:pointer;padding:2px 8px;font-weight:600;white-space:nowrap;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='none'"></button>
                </div>
            </div>

            {{-- 의견 목록 --}}
            <div id="dlv-cm-list" style="flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:10px;min-height:0;">
                <div id="dlv-cm-empty" style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">의견이 없습니다.</div>
            </div>

            {{-- 의견 작성 폼 (간트 동일 구조) --}}
            <div style="padding:12px 14px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9;">
                {{-- 단계 번호 선택 --}}
                <div id="dlv-step-input-wrap" style="margin-bottom:8px;">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                        <label style="font-size:11px;font-weight:600;color:#6b7280;white-space:nowrap;">단계</label>
                        <div style="display:flex;align-items:center;border:1.5px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff;">
                            <button type="button" onclick="dlvAdjustStep(-1)" style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">−</button>
                            <input type="number" id="dlv-cm-step" min="1" max="{{ count($typeDef['steps']) }}" placeholder="—"
                                   style="width:48px;text-align:center;border:none;outline:none;font-size:13px;font-weight:600;color:#1f2937;padding:4px 0;background:transparent;">
                            <button type="button" onclick="dlvAdjustStep(1)" style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">+</button>
                        </div>
                    </div>
                    <span style="font-size:10px;color:#9ca3af;">비워두면 현재 단계에 등록</span>
                </div>
                <textarea id="dlv-cm-input" rows="3" placeholder="의견을 입력하세요…"
                          style="width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;resize:none;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:inherit;background:#fff;"
                          onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"
                          onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();dlvSubmitComment();}"></textarea>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:7px;">
                    <span style="font-size:11px;color:#9ca3af;">Ctrl+Enter 등록</span>
                    <button id="dlv-cm-submit" onclick="dlvSubmitComment()"
                            style="padding:7px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;">
                        등록
                    </button>
                </div>
            </div>
        </div>

    </div>

</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/marked@9/marked.min.js"></script>
<script>
const PROJECT_ID   = {{ $project->id }};
const TYPE_ID      = '{{ $typeId }}';
const STEP_NO      = {{ $stepNo }};
const TOTAL_STEPS  = {{ $totalSteps }};
const SAVE_URL          = '{{ route("ai-agent.projects.deliverables.save-step",        [$project, $typeId]) }}';
const TRANSLATE_SAVE_URL = '{{ route("ai-agent.projects.deliverables.save-translation", [$project, $typeId]) }}';
const DRAFT_URL         = '{{ route("ai-agent.projects.deliverables.generate-draft",        [$project, $typeId]) }}';
const DRAFT_STREAM_BASE = '{{ route("ai-agent.projects.deliverables.generate-draft-stream", [$project, $typeId]) }}';
const ANALYZE_URL       = '{{ route("ai-agent.projects.deliverables.analyze-step",          [$project, $typeId]) }}';
const SAVE_TOOL_URL       = '{{ route("ai-agent.projects.deliverables.save-tool",             [$project, $typeId]) }}';
const ALL_STEP_FIELDS_URL = '{{ route("ai-agent.projects.deliverables.all-step-fields",       [$project, $typeId]) }}';
const TRANSLATE_URL       = '{{ route("translate") }}';
const VERSIONS_INDEX_URL  = '{{ route("ai-agent.projects.deliverables.versions.index",       [$project, $typeId]) }}';
const VERSIONS_SHOW_URL   = '{{ route("ai-agent.projects.deliverables.versions.show",        [$project, $typeId, "__ID__"]) }}';
const VERSIONS_RESTORE_URL= '{{ route("ai-agent.projects.deliverables.versions.restore",     [$project, $typeId, "__ID__"]) }}';
const REGISTER_FILE_URL   = '{{ route("ai-agent.projects.deliverables.register-as-file",     [$project, $typeId]) }}';
const REGISTERABLE_FILES_URL = '{{ route("ai-agent.projects.deliverables.registerable-files", [$project, $typeId]) }}';
const FILE_REGISTRATIONS_URL = '{{ route("ai-agent.projects.deliverables.file-registrations", [$project, $typeId]) }}';

const LANG = {
    toast_saved:      '{{ addslashes(__('deliverables.toast_saved')) }}',
    toast_completed:  '{{ addslashes(__('deliverables.toast_completed')) }}',
    ai_validate:      '{{ addslashes(__('deliverables.ai_btn_validate')) }}',
    ai_suggest:       '{{ addslashes(__('deliverables.ai_btn_suggest')) }}',
    ai_standard:      '{{ addslashes(__('deliverables.ai_btn_standard')) }}',
    ai_tool_gen:      '{{ addslashes(__('deliverables.ai_btn_tool')) }}',
    ai_analyzing:     '{{ addslashes(__('deliverables.ai_analyzing', ['label' => ':label'])) }}',
    ai_drafting:      '{{ addslashes(__('deliverables.ai_drafting')) }}',
    ai_answering:     '{{ addslashes(__('deliverables.ai_answering')) }}',
    ai_answer_title:  '{{ addslashes(__('deliverables.ai_answer_title')) }}',
    ai_apply_btn:     '{{ addslashes(__('deliverables.ai_apply_btn')) }}',
    ai_applied_ok:    '{{ addslashes(__('deliverables.ai_applied_ok', ['count' => ':count'])) }}',
    ai_applied_none:  '{{ addslashes(__('deliverables.ai_applied_none')) }}',
    ai_draft_ok:      '{{ addslashes(__('deliverables.ai_draft_ok', ['count' => ':count'])) }}',
    ai_draft_empty:   '{{ addslashes(__('deliverables.ai_draft_empty')) }}',
    ai_error_network: '{{ addslashes(__('deliverables.ai_error_network')) }}',
    ai_error_prefix:  '{{ addslashes(__('deliverables.ai_error_prefix')) }}',
    ai_error_draft:   '{{ addslashes(__('deliverables.ai_error_draft')) }}',
    ai_error_analyze: '{{ addslashes(__('deliverables.ai_error_analyze')) }}',
    ai_error_answer:  '{{ addslashes(__('deliverables.ai_error_answer')) }}',
    ai_draft_saving:  '{{ addslashes(__('deliverables.ai_draft_saving')) }}',
    ai_draft_saved:   '{{ addslashes(__('deliverables.ai_draft_saved')) }}',
    ai_draft_sfail:   '{{ addslashes(__('deliverables.ai_draft_save_fail')) }}',
    md_empty:         '{{ addslashes(__('deliverables.md_empty')) }}',
    error_occurred:   '{{ addslashes(__('deliverables.error_occurred')) }}',
    reject_prompt:    '{{ addslashes(__('deliverables.approve_reject_prompt')) }}',
    link_copied:      '{{ addslashes(__('deliverables.link_copied')) }}',
};
const MD_TR_BTN_HTML = '🌐 {{ addslashes(__('deliverables.tab_translate')) }}';

/* ── 미리보기 탭 초기 렌더링 ───────────────────────── */
async function mdInitPreviews() {
    if (typeof marked === 'undefined') return;
    document.querySelectorAll('.md-editor').forEach(editor => {
        const editPane    = editor.querySelector('.md-edit-pane');
        const previewPane = editor.querySelector('.md-preview-pane');
        const textarea    = editor.querySelector('textarea');
        if (!textarea || !editPane || !previewPane) return;
        const raw = textarea.value.trim();
        previewPane.innerHTML = raw
            ? marked.parse(raw)
            : `<span class="md-preview-empty">${LANG.md_empty}</span>`;
        editPane.style.display    = 'none';
        previewPane.style.display = 'block';
    });
}
mdInitPreviews();

// ── 좌측 패널 펼침/닫힘 (localStorage 기억, 기본 닫힘) ──
(function() {
    const left = document.getElementById('dlv-left');
    if (!left) return;
    const open = localStorage.getItem('dlv-left-open') === '1';
    if (open) left.classList.remove('is-collapsed');
})();

function dlvToggleLeft() {
    const left = document.getElementById('dlv-left');
    if (!left) return;
    left.classList.toggle('is-collapsed');
    const isOpen = !left.classList.contains('is-collapsed');
    localStorage.setItem('dlv-left-open', isOpen ? '1' : '0');
}

function getFormData() {
    const form = document.getElementById('step-form');
    const data = { step: STEP_NO, fields: {} };
    if (!form) return data;
    new FormData(form).forEach((v, k) => {
        const m = k.match(/^fields\[(.+)\]$/);
        if (m) data.fields[m[1]] = v;
    });
    return data;
}

// 내부 저장 호출 — version_mode/change_note 를 받아 SAVE_URL 로 POST
async function _postSaveStep(extra = {}) {
    const payload = { ...getFormData(), ...extra };
    const res = await fetch(SAVE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error('save failed');
    return res.json();
}

// 명시적 저장 버튼: 버전 선택 모달
async function saveStep(moveNext) {
    if (moveNext) {
        // moveNext 호환: 도구 보기 등 진행성 호출은 auto 모드로 즉시 저장
        try { await _postSaveStep({ version_mode: 'auto' }); showToast(LANG.toast_saved); } catch(e){ console.error(e); }
        return;
    }
    showVersionModal(async ({ mode, note }) => {
        try {
            const r = await _postSaveStep({ version_mode: mode, change_note: note });
            const v = r.version;
            if (v && v.version_no) {
                showToast(LANG.toast_saved + ' (v' + v.version_no + ')');
            } else {
                showToast(LANG.toast_saved);
            }
        } catch(e) { console.error(e); }
    });
}

async function saveAndNext() {
    try { await _postSaveStep({ version_mode: 'auto' }); } catch(e){ console.error(e); }
    if (STEP_NO < TOTAL_STEPS) goStep(STEP_NO + 1);
}

async function completeDeliverable() {
    try { await _postSaveStep({ version_mode: 'auto', complete: true }); } catch(e) { console.error(e); }
    showToast(LANG.toast_completed);
    setTimeout(() => location.href = '{{ route("ai-agent.projects.deliverables.index", $project) }}', 1200);
}

// ── 버전 선택 모달 ───────────────────────────────
function showVersionModal(onConfirm) {
    document.getElementById('dlv-ver-modal')?.remove();
    const wrap = document.createElement('div');
    wrap.id = 'dlv-ver-modal';
    wrap.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    wrap.innerHTML = `
      <div style="background:#fff;border-radius:12px;width:420px;max-width:92vw;padding:22px 22px 18px;box-shadow:0 20px 50px rgba(0,0,0,.25);">
        <div style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:14px;">STEP ${STEP_NO} 저장 방식 선택</div>
        <label style="display:flex;align-items:flex-start;gap:8px;padding:10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:8px;cursor:pointer;">
          <input type="radio" name="dlv-ver-mode" value="new" checked style="margin-top:3px;">
          <span><strong style="color:#0f172a;">새 버전으로 저장</strong><div style="font-size:12px;color:#64748b;margin-top:2px;">이전 버전 보존 + 새 스냅샷 생성</div></span>
        </label>
        <label style="display:flex;align-items:flex-start;gap:8px;padding:10px;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:12px;cursor:pointer;">
          <input type="radio" name="dlv-ver-mode" value="overwrite" style="margin-top:3px;">
          <span><strong style="color:#0f172a;">현재 버전 덮어쓰기</strong><div style="font-size:12px;color:#64748b;margin-top:2px;">가장 최근 버전 스냅샷만 갱신</div></span>
        </label>
        <textarea id="dlv-ver-note" rows="2" placeholder="변경 메모 (선택)" style="width:100%;padding:8px;font-size:13px;border:1px solid #e2e8f0;border-radius:8px;resize:vertical;"></textarea>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
          <button id="dlv-ver-cancel" style="padding:7px 14px;font-size:13px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;">취소</button>
          <button id="dlv-ver-ok" style="padding:7px 14px;font-size:13px;border:none;border-radius:8px;background:var(--t600,#4f46e5);color:#fff;font-weight:600;cursor:pointer;">저장</button>
        </div>
      </div>`;
    document.body.appendChild(wrap);
    wrap.addEventListener('click', e => { if (e.target === wrap) wrap.remove(); });
    wrap.querySelector('#dlv-ver-cancel').addEventListener('click', () => wrap.remove());
    wrap.querySelector('#dlv-ver-ok').addEventListener('click', async () => {
        const mode = wrap.querySelector('input[name="dlv-ver-mode"]:checked').value;
        const note = wrap.querySelector('#dlv-ver-note').value.trim();
        wrap.remove();
        await onConfirm({ mode, note });
    });
}

// ── 버전 이력 패널 ───────────────────────────────
async function dlvOpenVersions() {
    document.getElementById('dlv-ver-panel')?.remove();
    const wrap = document.createElement('div');
    wrap.id = 'dlv-ver-panel';
    wrap.style.cssText = 'position:fixed;top:0;right:0;bottom:0;width:380px;max-width:92vw;background:#fff;box-shadow:-10px 0 30px rgba(0,0,0,.15);z-index:9998;display:flex;flex-direction:column;';
    wrap.innerHTML = `
      <div style="padding:14px 16px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
        <div style="font-size:14px;font-weight:700;color:#0f172a;">STEP ${STEP_NO} 버전 이력</div>
        <button id="dlv-ver-close" style="background:none;border:none;font-size:18px;cursor:pointer;color:#64748b;">×</button>
      </div>
      <div id="dlv-ver-list" style="flex:1;overflow:auto;padding:10px 14px;font-size:13px;color:#334155;">불러오는 중…</div>`;
    document.body.appendChild(wrap);
    wrap.querySelector('#dlv-ver-close').addEventListener('click', () => wrap.remove());

    try {
        const res = await fetch(VERSIONS_INDEX_URL + '?step=' + STEP_NO, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        const list = wrap.querySelector('#dlv-ver-list');
        if (!data.versions?.length) {
            list.innerHTML = '<div style="color:#94a3b8;text-align:center;padding:30px 0;">저장된 버전이 없습니다.</div>';
            return;
        }
        list.innerHTML = data.versions.map(v => `
          <div style="border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;margin-bottom:8px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
              <strong style="color:#0f172a;">v${v.version_no}</strong>
              <span style="font-size:11px;color:#94a3b8;">${v.created_at ?? ''}</span>
            </div>
            <div style="font-size:12px;color:#475569;margin-bottom:6px;">${(v.creator || '-') + (v.change_note ? ' · ' + v.change_note : '')}</div>
            <button onclick="dlvRestoreVersion(${v.id}, ${v.version_no})" style="font-size:12px;padding:4px 10px;border:1px solid #cbd5e1;border-radius:6px;background:#fff;cursor:pointer;">이 버전으로 복원</button>
          </div>`).join('');
    } catch(e) {
        wrap.querySelector('#dlv-ver-list').textContent = '오류: ' + e.message;
    }
}

async function dlvRestoreVersion(id, vno) {
    if (!confirm('v' + vno + ' 내용으로 현재 작업본을 복원하시겠습니까?')) return;
    const url = VERSIONS_RESTORE_URL.replace('__ID__', id);
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        });
        const data = await res.json();
        if (data.ok) {
            showToast(data.message || '복원 완료');
            setTimeout(() => location.reload(), 800);
        } else {
            alert(data.message || '복원 실패');
        }
    } catch(e) { alert('복원 실패: ' + e.message); }
}

// ── 산출물 → 파일로 등록 (커스텀 다이얼로그 + 파일 선택) ──────────────
async function dlvRegisterAsFile() {
    document.getElementById('dlv-reg-modal')?.remove();
    const wrap = document.createElement('div');
    wrap.id = 'dlv-reg-modal';
    wrap.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,.5);z-index:9999;display:flex;align-items:center;justify-content:center;';
    wrap.innerHTML = `
      <div class="dlv-reg-box">
        {{-- 헤더 --}}
        <div class="dlv-reg-head">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
            <svg width="18" height="18" fill="none" stroke="var(--t600,#4f46e5)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <div style="font-size:15px;font-weight:700;color:#0f172a;">산출물을 파일로 등록</div>
          </div>
          <div style="font-size:12px;color:#64748b;line-height:1.55;">
            왼쪽 이력의 항목을 누르면 그 파일에 새 버전이 추가됩니다.
          </div>
        </div>

        {{-- 본문: 좌(이력) + 우(폼) --}}
        <div class="dlv-reg-body">
          {{-- ① 좌: 등록 이력 --}}
          <aside class="dlv-reg-side">
            <div class="dlv-reg-side-head">
              <span>최근 등록 이력</span>
              <span id="dlv-reg-hist-count" style="font-size:10px;color:#94a3b8;font-weight:600;"></span>
            </div>
            <div id="dlv-reg-history" class="dlv-reg-side-list">
              <div style="padding:18px 10px;text-align:center;color:#94a3b8;font-size:12px;">불러오는 중…</div>
            </div>
          </aside>

          {{-- ② 우: 등록 폼 --}}
          <section class="dlv-reg-main">
            <div class="dlv-reg-field">
              <span class="dlv-reg-field-label">등록 대상</span>
              <label class="dlv-reg-target-opt">
                <input type="radio" name="dlv-reg-target" value="new" checked>
                <div class="dlv-reg-row-body">
                  <strong style="color:#0f172a;font-size:13px;">새 파일로 등록 (v1)</strong>
                  <div style="font-size:11.5px;color:#64748b;margin-top:2px;">프로젝트 파일에 새 항목으로 추가합니다.</div>
                </div>
              </label>
              <label class="dlv-reg-target-opt">
                <input type="radio" name="dlv-reg-target" value="existing">
                <div class="dlv-reg-row-body">
                  <strong style="color:#0f172a;font-size:13px;">기존 파일에 버전 추가</strong>
                  <div style="font-size:11.5px;color:#64748b;margin-top:2px;">대상 파일을 검색해서 선택하세요.</div>
                </div>
              </label>
            </div>

            {{-- 기존 파일 선택 시에만 표시 --}}
            <div id="dlv-reg-filelist-wrap" class="dlv-reg-field" style="display:none;">
              <span class="dlv-reg-field-label">대상 파일</span>
              <div class="dlv-reg-search-wrap">
                <svg class="dlv-reg-search-icon" width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.3-4.3M11 17a6 6 0 1 1 0-12 6 6 0 0 1 0 12Z"/></svg>
                <input id="dlv-reg-filesearch" type="text" class="dlv-reg-search" placeholder="대상 파일 검색 — 클릭하여 목록 열기" autocomplete="off">
                <button type="button" id="dlv-reg-filesearch-clear" style="display:none;position:absolute;right:6px;top:50%;transform:translateY(-50%);background:none;border:none;color:#94a3b8;font-size:14px;cursor:pointer;padding:2px 6px;line-height:1;">×</button>
                <div id="dlv-reg-filelist" class="dlv-reg-filelist">
                  <div style="padding:14px;text-align:center;color:#94a3b8;font-size:12.5px;">파일 목록을 불러오는 중…</div>
                </div>
                <div id="dlv-reg-filelist-empty" style="display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);z-index:50;padding:12px;text-align:center;color:#94a3b8;font-size:12px;background:#fff;border:1.5px solid #c4b5fd;border-radius:8px;box-shadow:0 10px 28px rgba(15,23,42,.16);">검색 결과가 없습니다.</div>
              </div>
              {{-- 선택된 파일 표시 --}}
              <div id="dlv-reg-selected" class="dlv-reg-selected">
                <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span id="dlv-reg-selected-name" style="flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-weight:600;"></span>
                <button type="button" id="dlv-reg-selected-clear" class="dlv-reg-selected-clear" title="선택 해제">×</button>
              </div>
            </div>

            <div class="dlv-reg-field">
              <span class="dlv-reg-field-label">변경 메모 <span style="color:#94a3b8;font-weight:400;">(선택)</span></span>
              <textarea id="dlv-reg-note" rows="3" class="dlv-reg-textarea" placeholder="이번 버전에 포함된 주요 변경 내용을 적어주세요."></textarea>
            </div>

            <div class="dlv-reg-field" style="margin-bottom:0;">
              <span class="dlv-reg-field-label">출력 언어</span>
              <div class="dlv-reg-lang-row">
                <label class="dlv-reg-lang-opt"><input type="radio" name="dlv-reg-lang" value="ko" checked> 한글</label>
                <label class="dlv-reg-lang-opt"><input type="radio" name="dlv-reg-lang" value="en"> English</label>
              </div>
            </div>
          </section>
        </div>

        {{-- 푸터 --}}
        <div class="dlv-reg-foot">
          <button id="dlv-reg-cancel" style="padding:7px 14px;font-size:13px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;color:#64748b;font-weight:600;cursor:pointer;">취소</button>
          <button id="dlv-reg-ok" style="padding:7px 16px;font-size:13px;border:none;border-radius:8px;background:var(--t600,#4f46e5);color:#fff;font-weight:700;cursor:pointer;">파일로 등록</button>
        </div>
      </div>`;
    document.body.appendChild(wrap);

    const close = () => wrap.remove();
    wrap.addEventListener('click', e => { if (e.target === wrap) close(); });
    wrap.querySelector('#dlv-reg-cancel').addEventListener('click', close);

    const fileListWrap  = wrap.querySelector('#dlv-reg-filelist-wrap');
    const fileListEl    = wrap.querySelector('#dlv-reg-filelist');
    const fileEmptyEl   = wrap.querySelector('#dlv-reg-filelist-empty');
    const fileSearchEl  = wrap.querySelector('#dlv-reg-filesearch');
    const fileSearchClr = wrap.querySelector('#dlv-reg-filesearch-clear');
    const fileSearchWrap= wrap.querySelector('.dlv-reg-search-wrap');
    const selectedBox   = wrap.querySelector('#dlv-reg-selected');
    const selectedName  = wrap.querySelector('#dlv-reg-selected-name');
    const targetRadios  = wrap.querySelectorAll('input[name="dlv-reg-target"]');
    let selectedFileId  = null;
    let filesLoaded     = false;

    // ── 파일 목록 드롭다운 열기/닫기 ──
    function openFileDropdown() {
        if (!filesLoaded) return;
        fileListEl.classList.add('is-open');
        dlvApplyFileSearch();
    }
    function closeFileDropdown() {
        fileListEl.classList.remove('is-open');
        fileEmptyEl.style.display = 'none';
    }

    // 파일 이름 검색 + 빈 결과 처리 (드롭다운 열려있을 때만 empty 표시)
    function dlvApplyFileSearch() {
        const q = fileSearchEl.value.trim().toLowerCase();
        fileSearchClr.style.display = q ? 'block' : 'none';
        const rows = fileListEl.querySelectorAll('[data-fname]');
        let visible = 0;
        rows.forEach(r => {
            const name = (r.dataset.fname || '').toLowerCase();
            const show = !q || name.includes(q);
            r.style.display = show ? '' : 'none';
            if (show) visible++;
        });
        const isOpen = fileListEl.classList.contains('is-open');
        fileEmptyEl.style.display = (isOpen && rows.length && visible === 0) ? 'block' : 'none';
    }

    // 선택된 파일 표시
    function setSelectedFile(id, name) {
        selectedFileId = id;
        if (id) {
            selectedName.textContent = name || '';
            selectedBox.classList.add('is-shown');
        } else {
            selectedBox.classList.remove('is-shown');
        }
    }

    fileSearchEl.addEventListener('input', () => { openFileDropdown(); dlvApplyFileSearch(); });
    fileSearchEl.addEventListener('focus', openFileDropdown);
    fileSearchClr.addEventListener('click', () => { fileSearchEl.value = ''; openFileDropdown(); dlvApplyFileSearch(); fileSearchEl.focus(); });
    wrap.querySelector('#dlv-reg-selected-clear').addEventListener('click', () => {
        setSelectedFile(null);
        wrap.querySelectorAll('input[name="dlv-reg-file"]').forEach(fr => fr.checked = false);
    });
    // 검색창/드롭다운 바깥 클릭 시 닫기
    document.addEventListener('mousedown', e => {
        if (fileSearchWrap && !fileSearchWrap.contains(e.target)) closeFileDropdown();
    });

    // 파일 등록 이력 로드 (좌측 사이드)
    const historyEl    = wrap.querySelector('#dlv-reg-history');
    const historyCount = wrap.querySelector('#dlv-reg-hist-count');
    (async () => {
        try {
            const res = await fetch(FILE_REGISTRATIONS_URL, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!data.registrations?.length) {
                historyEl.innerHTML = '<div style="padding:18px 10px;text-align:center;color:#94a3b8;font-size:12px;">아직 등록 이력이 없습니다.</div>';
                if (historyCount) historyCount.textContent = '';
                return;
            }
            if (historyCount) historyCount.textContent = data.registrations.length + '건';
            historyEl.innerHTML = data.registrations.map(r => {
                const note = r.change_note ? `<span style="color:#475569;"> · ${String(r.change_note).replace(/[<>"']/g,'')}</span>` : '';
                const fname = String(r.file_name || '(삭제됨)').replace(/[<>"']/g,'');
                const langTag = r.lang === 'en' ? '<span style="font-size:10px;color:#0284c7;background:#e0f2fe;padding:1px 5px;border-radius:3px;margin-left:4px;">EN</span>' : '';
                return `
                  <div data-pf="${r.project_file_id}" class="dlv-reg-row dlv-reg-hist-row">
                    <span class="dlv-reg-row-lead dlv-reg-row-vbadge">v${r.file_version}</span>
                    <div class="dlv-reg-row-body">
                      <div class="dlv-reg-row-title"><span class="dlv-reg-row-title-name">${fname}</span>${langTag}</div>
                      <div class="dlv-reg-row-meta">${r.creator ?? '-'} · ${r.created_at ?? ''}${note}</div>
                    </div>
                  </div>`;
            }).join('');
            historyEl.querySelectorAll('.dlv-reg-hist-row').forEach(row => {
                row.addEventListener('mouseenter', () => row.style.background = '#f5f3ff');
                row.addEventListener('mouseleave', () => row.style.background = '');
                row.addEventListener('click', async () => {
                    // 이력 클릭 시 → 같은 파일에 새 버전 추가 모드로 자동 선택
                    const pfId = row.dataset.pf;
                    const existingRadio = wrap.querySelector('input[name="dlv-reg-target"][value="existing"]');
                    existingRadio.checked = true;
                    existingRadio.dispatchEvent(new Event('change'));
                    // 파일 목록 로드 완료까지 잠시 대기 후 해당 파일 선택
                    const trySelect = () => {
                        const fileRadio = wrap.querySelector(`input[name="dlv-reg-file"][value="${pfId}"]`);
                        if (fileRadio) {
                            fileRadio.checked = true;
                            fileRadio.dispatchEvent(new Event('change'));
                            fileRadio.scrollIntoView({ block: 'nearest' });
                        } else {
                            setTimeout(trySelect, 80);
                        }
                    };
                    setTimeout(trySelect, 80);
                });
            });
        } catch (e) {
            historyEl.innerHTML = '<div style="padding:14px;color:#dc2626;">이력 조회 실패: ' + e.message + '</div>';
        }
    })();

    // 등록 대상 선택 토글
    targetRadios.forEach(r => r.addEventListener('change', async () => {
        if (r.value === 'existing' && r.checked) {
            fileListWrap.style.display = 'block';
            if (!filesLoaded) {
                filesLoaded = true;
                try {
                    const res = await fetch(REGISTERABLE_FILES_URL, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    if (!data.files?.length) {
                        fileListEl.innerHTML = '<div style="padding:14px;text-align:center;color:#94a3b8;font-size:12.5px;">등록된 파일이 없습니다.</div>';
                        return;
                    }
                    fileListEl.innerHTML = data.files.map(f => {
                        const sizeKb = f.size ? Math.round(f.size / 1024) : 0;
                        const sizeText = sizeKb >= 1024 ? (Math.round(sizeKb / 102.4) / 10) + ' MB' : sizeKb + ' KB';
                        const rawName  = String(f.original_name || '');
                        const safeName = rawName.replace(/[<>"']/g, '');
                        const safeAttr = rawName.replace(/"/g, '&quot;');
                        let catBadge = '';
                        if (f.category_name) {
                            const c    = String(f.category_color || '#7c3aed').replace(/[^#0-9a-fA-F]/g, '') || '#7c3aed';
                            const name = String(f.category_name).replace(/[<>"']/g, '');
                            catBadge = `<span class="dlv-reg-cat-badge" style="color:${c};background:${c}1a;">${name}</span>`;
                        }
                        return `
                          <label class="dlv-reg-row" data-fname="${safeAttr}">
                            <input type="radio" name="dlv-reg-file" value="${f.id}" class="dlv-reg-row-lead" style="margin-top:3px;">
                            <div class="dlv-reg-row-body">
                              <div class="dlv-reg-row-title">${catBadge}<span class="dlv-reg-row-title-name">${safeName}</span></div>
                              <div class="dlv-reg-row-meta">다음 버전 v${f.next_version} · ${sizeText} · ${f.updated_at ?? ''}</div>
                            </div>
                          </label>`;
                    }).join('');
                    fileListEl.querySelectorAll('input[name="dlv-reg-file"]').forEach(fr => {
                        fr.addEventListener('change', () => {
                            const label = fr.closest('label');
                            setSelectedFile(fr.value, label?.dataset.fname || '');
                            fileSearchEl.value = '';
                            closeFileDropdown();
                        });
                    });
                    dlvApplyFileSearch();
                    fileSearchEl.focus();
                } catch (e) {
                    fileListEl.innerHTML = '<div style="padding:14px;color:#dc2626;font-size:12.5px;">목록 조회 실패: ' + e.message + '</div>';
                }
            } else {
                fileSearchEl.focus();
            }
        } else {
            fileListWrap.style.display = 'none';
            closeFileDropdown();
            setSelectedFile(null);
        }
    }));

    wrap.querySelector('#dlv-reg-ok').addEventListener('click', async () => {
        const targetMode = wrap.querySelector('input[name="dlv-reg-target"]:checked').value;
        if (targetMode === 'existing' && !selectedFileId) {
            alert('대상 파일을 선택하세요.');
            return;
        }
        const note = wrap.querySelector('#dlv-reg-note').value.trim();
        const lang = wrap.querySelector('input[name="dlv-reg-lang"]:checked').value;
        const payload = { change_note: note, lang };
        if (targetMode === 'existing') payload.target_file_id = selectedFileId;

        const okBtn = wrap.querySelector('#dlv-reg-ok');
        okBtn.disabled = true;
        okBtn.textContent = '등록 중…';
        try {
            const res = await fetch(REGISTER_FILE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(payload),
            });
            const data = await res.json();
            close();
            if (data.ok) {
                showToast(data.message);
            } else {
                alert(data.message || '파일 등록 실패');
            }
        } catch(e) {
            close();
            alert('파일 등록 실패: ' + e.message);
        }
    });
}

async function goStep(n) {
    const url = new URL(location.href);
    url.searchParams.set('step', n);
    location.href = url.toString();
}

async function skipStep() {
    if (STEP_NO < TOTAL_STEPS) goStep(STEP_NO + 1);
}

async function showToast(msg) {
    const t = document.getElementById('dlv-toast');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 2500);
}

function addAiMsg(html, returnEl = false) {
    const body = document.getElementById('ai-body');
    const div  = document.createElement('div');
    div.className = 'dlv-ai-msg';
    div.innerHTML = html;
    body.appendChild(div);
    body.scrollTop = body.scrollHeight;
    return returnEl ? div : undefined;
}

function applyFieldsToForm(fields) {
    let applied = 0;
    for (const [key, value] of Object.entries(fields)) {
        if (!value) continue;
        const el = document.querySelector(`[data-field-key="${key}"]`) ||
                   document.querySelector(`[name="fields[${key}]"]`);
        if (el) { el.value = value; applied++; }
    }
    return applied;
}

function addApplyButton(containerEl, fields) {
    if (!fields || Object.keys(fields).length === 0) return;
    const btn = document.createElement('button');
    btn.className = 'dlv-apply-btn';
    btn.innerHTML = `<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg> ${LANG.ai_apply_btn}`;
    btn.onclick = () => {
        const count = applyFieldsToForm(fields);
        btn.disabled = true;
        btn.textContent = count > 0
            ? LANG.ai_applied_ok.replace(':count', count)
            : LANG.ai_applied_none;
        if (count > 0) showToast(LANG.ai_applied_ok.replace(':count', count));
    };
    containerEl.appendChild(btn);
    document.getElementById('ai-body').scrollTop = document.getElementById('ai-body').scrollHeight;
}

async function aiAction(type) {
    if (type === 'draft') { await generateDraft(); return; }
    await analyzeAction(type);
}

async function analyzeAction(action) {
    const labels = { validate: LANG.ai_validate, suggest: LANG.ai_suggest, standard: LANG.ai_standard, 'tool-generate': LANG.ai_tool_gen };
    const label  = labels[action] ?? action;

    const msgEl = addAiMsg(LANG.ai_analyzing.replace(':label', label), true);

    const fields = {};
    document.querySelectorAll('#step-form [name^="fields["]').forEach(el => {
        const m = el.name.match(/^fields\[(.+)\]$/);
        if (m) fields[m[1]] = el.value;
    });

    try {
        const res  = await fetch(ANALYZE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ action, step: STEP_NO, fields }),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            msgEl.innerHTML = LANG.ai_error_prefix + (data.error ?? LANG.ai_error_analyze);
            return;
        }

        const providerBadge = data.provider ? ` <span style="font-size:10px;background:#ede8ff;color:#7c3aed;padding:1px 5px;border-radius:3px;">${data.provider}</span>` : '';
        msgEl.innerHTML = `<strong>📋 ${label}${providerBadge}</strong><br><br>` + escapeHtmlToBr(data.text);
        addApplyButton(msgEl, data.fields);
    } catch (e) {
        msgEl.innerHTML = LANG.ai_error_network;
        console.error(e);
    }
}

// 스트리밍 중 누적 텍스트에서 부분 JSON 필드값을 추출해 textarea에 실시간 반영
function applyPartialStream(accumulated) {
    document.querySelectorAll('#step-form textarea[name^="fields["]').forEach(el => {
        const m = el.name.match(/^fields\[(.+)\]$/);
        if (!m) return;
        const key   = m[1];
        // "key": "value... 패턴 (value는 미완성일 수 있음)
        const regex = new RegExp('"' + key + '"\\s*:\\s*"((?:[^"\\\\]|\\\\.)*)', 'u');
        const hit   = accumulated.match(regex);
        if (hit) {
            const partial = hit[1]
                .replace(/\\n/g, '\n')
                .replace(/\\t/g, '\t')
                .replace(/\\"/g, '"')
                .replace(/\\\\/g, '\\');
            if (el.value !== partial) {
                el.value = partial;
                el.style.borderColor = 'var(--t400)';
            }
        }
    });
}

function resetStreamHighlight() {
    document.querySelectorAll('#step-form textarea[name^="fields["]').forEach(el => {
        el.style.borderColor = '';
    });
}

async function generateDraft() {
    const msgEl = addAiMsg(LANG.ai_drafting, true);

    // 웍스 패널 진행 표시 (작은 상태 텍스트)
    const statusEl = document.createElement('div');
    statusEl.style.cssText = 'margin-top:6px;font-size:10.5px;color:#94a3b8;display:none;';
    msgEl.appendChild(statusEl);

    const streamUrl = DRAFT_STREAM_BASE + '?step=' + STEP_NO;

    let res;
    try {
        res = await fetch(streamUrl, {
            method: 'GET',
            headers: { 'Accept': 'text/event-stream', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        });
    } catch (e) {
        msgEl.innerHTML = LANG.ai_error_network;
        console.error('[generateDraft] fetch error:', e);
        return;
    }

    if (!res.ok) {
        msgEl.innerHTML = LANG.ai_error_prefix + `HTTP ${res.status}`;
        return;
    }

    const reader  = res.body.getReader();
    const decoder = new TextDecoder();
    let   buffer      = '';
    let   eventName   = '';
    let   accumulated = '';

    try {
        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop();

            for (const line of lines) {
                if (line.startsWith('event: ')) {
                    eventName = line.slice(7).trim();
                } else if (line.startsWith('data: ')) {
                    let data;
                    try { data = JSON.parse(line.slice(6)); } catch { continue; }

                    if (eventName === 'chunk' && data.text) {
                        accumulated += data.text;
                        statusEl.style.display = 'block';
                        statusEl.textContent   = '✍️ 작성 중…';
                        // 중앙 편집 영역 실시간 반영
                        applyPartialStream(accumulated);
                    }

                    if (eventName === 'status' && data.text) {
                        statusEl.style.display = 'block';
                        statusEl.textContent   = data.text;
                    }

                    if (eventName === 'done' && data.ok) {
                        resetStreamHighlight();
                        statusEl.remove();
                        let fields = data.fields ?? {};

                        // 서버 디버그 정보 (필드 빈 경우)
                        if (data._debug) {
                            console.warn('[generateDraft] 필드 매핑 실패. 디버그:', data._debug);
                        }

                        // 서버 파싱 실패 시 클라이언트 측 fallback (literal 줄바꿈 자동 수정 포함)
                        if (Object.keys(fields).length === 0 && accumulated) {
                            try {
                                let jsonStr = accumulated;
                                const m1 = accumulated.match(/```(?:json)?\s*([\s\S]*?)```/);
                                const m2 = accumulated.match(/(\{[\s\S]*\})/s);
                                if (m1) jsonStr = m1[1];
                                else if (m2) jsonStr = m2[1];
                                // JSON 문자열 값 내 literal 줄바꿈 → \n 이스케이프 수정
                                const fixed = jsonStr.trim().replace(
                                    /"(?:[^"\\]|\\.)*"/gs,
                                    m => m.replace(/\r\n|\r|\n/g, '\\n')
                                );
                                const parsed = JSON.parse(fixed);
                                if (parsed && typeof parsed === 'object' && !Array.isArray(parsed)) {
                                    fields = parsed;
                                    console.log('[generateDraft] 클라이언트 fallback 파싱 성공:', Object.keys(fields));
                                }
                            } catch (e) {
                                console.warn('[generateDraft] 클라이언트 fallback 파싱 실패:', e.message);
                            }
                        }

                        let applied  = 0;
                        for (const [key, value] of Object.entries(fields)) {
                            const el = document.querySelector(`[data-field-key="${key}"]`) ||
                                       document.querySelector(`[name="fields[${key}]"]`);
                            if (el && value) { el.value = value; applied++; }
                        }

                        // 스트리밍으로 이미 채워진 경우 카운트 (서버·클라이언트 파싱 모두 실패 시 fallback)
                        if (applied === 0) {
                            document.querySelectorAll('#step-form [name^="fields["]').forEach(el => {
                                if (el.value && el.value.trim()) applied++;
                            });
                        }
                        const providerBadge = data.provider
                            ? ` <span style="font-size:10px;background:#ede8ff;color:#7c3aed;padding:1px 5px;border-radius:3px;">${data.provider}</span>`
                            : '';
                        if (applied > 0) {
                            msgEl.innerHTML = LANG.ai_draft_ok.replace(':count', applied) + providerBadge
                                + `<br><span style="color:#94a3b8;font-size:10.5px;">${LANG.ai_draft_saving}</span>`;
                            try {
                                await fetch(SAVE_URL, {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    body: JSON.stringify(getFormData()),
                                });
                                msgEl.innerHTML = LANG.ai_draft_ok.replace(':count', applied) + providerBadge
                                    + `<br><span style="color:#16a34a;font-size:10.5px;">${LANG.ai_draft_saved}</span>`;
                            } catch (_) {
                                msgEl.innerHTML = LANG.ai_draft_ok.replace(':count', applied) + providerBadge
                                    + `<br><span style="color:#f59e0b;font-size:10.5px;">${LANG.ai_draft_sfail}</span>`;
                            }
                        } else {
                            msgEl.innerHTML = LANG.ai_draft_empty;
                        }
                    }

                    if (eventName === 'error' && data.error) {
                        resetStreamHighlight();
                        statusEl.remove();
                        msgEl.innerHTML = LANG.ai_error_prefix + data.error;
                    }

                    eventName = '';
                }
            }
        }
    } catch (e) {
        resetStreamHighlight();
        statusEl.remove();
        msgEl.innerHTML = LANG.ai_error_prefix + (e.message ?? LANG.ai_error_draft);
        console.error('[generateDraft] stream error:', e);
    }
}

async function sendAiMessage() {
    const input = document.getElementById('ai-question');
    const q = input.value.trim();
    if (!q) return;

    addAiMsg('💬 ' + q);
    input.value = '';

    const msgEl = addAiMsg(LANG.ai_answering, true);

    const fields = {};
    document.querySelectorAll('#step-form [name^="fields["]').forEach(el => {
        const m = el.name.match(/^fields\[(.+)\]$/);
        if (m) fields[m[1]] = el.value;
    });

    try {
        const res  = await fetch(ANALYZE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ action: 'question', step: STEP_NO, fields, question: q }),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            msgEl.innerHTML = LANG.ai_error_prefix + (data.error ?? LANG.ai_error_answer);
            return;
        }

        const providerBadge = data.provider ? ` <span style="font-size:10px;background:#ede8ff;color:#7c3aed;padding:1px 5px;border-radius:3px;">${data.provider}</span>` : '';
        msgEl.innerHTML = `<strong>${LANG.ai_answer_title}${providerBadge}</strong><br><br>` + escapeHtmlToBr(data.text);
        addApplyButton(msgEl, data.fields);
    } catch (e) {
        msgEl.innerHTML = LANG.ai_error_network;
        console.error(e);
    }
}

function escapeHtmlToBr(text) {
    return text
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\n/g, '<br>');
}

// 드래그 중 SVG 밖에서 마우스를 놓아도 정리
document.addEventListener('mouseup', () => {
    if (_dlvDrag !== null) {
        _dlvDrag = null;
        const svg = document.getElementById('dlv-ann-svg');
        if (svg && !_dlvAnnTool) svg.style.pointerEvents = 'none';
    }
});

// ESC 키로 뷰어 모달 닫기 / 주석 툴 해제
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (_dlvAnnTool) { dlvSetAnnTool(null); return; }
        dlvCloseViewer();
    }
});

/* ── Markdown 탭 전환 ─────────────────────────────── */
async function mdSwitchTab(btn, mode) {
    const editor      = btn.closest('.md-editor');
    const editPane    = editor.querySelector('.md-edit-pane');
    const previewPane = editor.querySelector('.md-preview-pane');
    const textarea    = editor.querySelector('textarea');

    editor.querySelectorAll('.md-tab').forEach(t => t.classList.remove('is-active'));
    btn.classList.add('is-active');

    if (mode === 'preview') {
        const raw = textarea.value.trim();
        previewPane.innerHTML = raw
            ? marked.parse(raw)
            : `<span class="md-preview-empty">${LANG.md_empty}</span>`;
        previewPane.style.height    = textarea.offsetHeight + 'px';
        previewPane.style.overflowY = 'auto';
        editPane.style.display      = 'none';
        previewPane.style.display   = 'block';
    } else {
        previewPane.style.height    = '';
        previewPane.style.overflowY = '';
        editPane.style.display      = 'block';
        previewPane.style.display   = 'none';
    }
    // 탭 전환 시 번역 버튼 초기화
    const trBtn = editor.querySelector('.md-tab-tr');
    if (trBtn && trBtn.dataset.translated === 'true') {
        trBtn.dataset.translated = 'false';
        trBtn.innerHTML = MD_TR_BTN_HTML;
    }
}

/* ── 영문 번역 (캐시 우선) ──────────────────────────── */
function _mdShowTranslated(editor, textarea, translated) {
    const editPane    = editor.querySelector('.md-edit-pane');
    const previewPane = editor.querySelector('.md-preview-pane');
    // 현재 보이는 패널 높이 우선 (previewPane이 hidden이면 textarea 사용)
    const paneH = previewPane.offsetHeight || textarea.offsetHeight || 200;
    previewPane.innerHTML =
        `<div class="md-tr-badge">🌐 {{ addslashes(__('deliverables.translate_badge')) }}</div>` +
        marked.parse(translated);
    previewPane.style.height    = paneH + 'px';
    previewPane.style.overflowY = 'auto';
    editPane.style.display      = 'none';
    previewPane.style.display   = 'block';
    editor.querySelectorAll('.md-tab').forEach(t => t.classList.remove('is-active'));
    const firstTab = editor.querySelector('.md-tab');
    if (firstTab) firstTab.classList.add('is-active');
    // 버튼 → 한국어 (다시 클릭 시 복원)
    const trBtn = editor.querySelector('.md-tab-tr');
    if (trBtn) { trBtn.dataset.translated = 'true'; trBtn.innerHTML = '한국어'; }
}

function _mdRestoreKorean(editor, textarea) {
    const previewPane = editor.querySelector('.md-preview-pane');
    const editPane    = editor.querySelector('.md-edit-pane');
    const paneH = previewPane.offsetHeight || (textarea ? textarea.offsetHeight : 0) || 200;
    const raw = textarea ? textarea.value.trim() : '';
    previewPane.innerHTML = raw ? marked.parse(raw) : `<span class="md-preview-empty">${LANG.md_empty}</span>`;
    previewPane.style.height    = paneH + 'px';
    previewPane.style.overflowY = 'auto';
    editPane.style.display      = 'none';
    previewPane.style.display   = 'block';
    editor.querySelectorAll('.md-tab').forEach(t => t.classList.remove('is-active'));
    const firstTab = editor.querySelector('.md-tab');
    if (firstTab) firstTab.classList.add('is-active');
    // 버튼 → 🌐 EN번역
    const trBtn = editor.querySelector('.md-tab-tr');
    if (trBtn) { trBtn.dataset.translated = 'false'; trBtn.innerHTML = MD_TR_BTN_HTML; }
}

async function mdTranslate(btn) {
    const editor   = btn.closest('.md-editor');
    const textarea = editor.querySelector('textarea');

    // 이미 번역된 상태면 한국어로 복원
    if (btn.dataset.translated === 'true') {
        _mdRestoreKorean(editor, textarea);
        return;
    }

    const text = textarea ? textarea.value.trim() : '';
    if (!text) return;

    const fieldKey = textarea.dataset.fieldKey || '';
    const hasEn    = textarea.dataset.hasEn === 'true';
    const enValue  = textarea.dataset.enValue || '';

    // 캐시된 번역이 유효하면 API 호출 없이 즉시 표시
    if (hasEn && enValue) {
        _mdShowTranslated(editor, textarea, enValue);
        return;
    }

    btn.disabled  = true;
    btn.innerHTML = '⏳';

    try {
        const res  = await fetch('{{ route("translate") }}', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body:    JSON.stringify({ text, target: 'en' }),
        });
        const data = await res.json();
        if (!res.ok || !data.ok) throw new Error(data.error || data.message || 'HTTP ' + res.status);
        const translated = data.translated;

        _mdShowTranslated(editor, textarea, translated);

        // DB에 번역 캐시 저장
        if (fieldKey) {
            fetch(TRANSLATE_SAVE_URL, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body:    JSON.stringify({ step: STEP_NO, field_key: fieldKey, en_value: translated }),
            }).catch(() => {});
            textarea.dataset.hasEn   = 'true';
            textarea.dataset.enValue = translated;
        }
    } catch (e) {
        alert('{{ addslashes(__('deliverables.translate_error')) }}\n' + e.message);
        btn.innerHTML = MD_TR_BTN_HTML;
    } finally {
        btn.disabled = false;
    }
}

/* ── 전체 STEP 영문 번역 ─────────────────────────────── */
async function dlvTranslateAll() {
    const btn  = document.getElementById('dlv-tr-all-btn');
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const orig = btn.innerHTML;

    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> 필드 목록 로딩 중…';

    try {
        const fRes = await fetch(ALL_STEP_FIELDS_URL, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        const fData = await fRes.json();
        if (!fData.ok) throw new Error('필드 목록 로드 실패');

        const todo  = fData.fields.filter(f => !f.has_en && f.value.trim() !== '');
        const total = todo.length;
        const skip  = fData.fields.length - total;

        if (total === 0) {
            showToast(skip > 0 ? `모두 번역됨 (${skip}개 캐시)` : '번역할 저장 내용이 없습니다.');
            return;
        }

        let done = 0, failed = 0;

        for (const field of todo) {
            btn.innerHTML = `<span>⏳</span> 번역 중… (${done + 1}/${total})`;
            try {
                const tRes = await fetch(TRANSLATE_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body:    JSON.stringify({ text: field.value, target: 'en' }),
                });
                const tData = await tRes.json();
                if (!tRes.ok || !tData.ok) throw new Error(tData.error || 'HTTP ' + tRes.status);

                await fetch(TRANSLATE_SAVE_URL, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body:    JSON.stringify({ step: field.step, field_key: field.field_key, en_value: tData.translated }),
                });

                // 현재 단계 DOM 동기화
                if (field.step === STEP_NO) {
                    const ta = document.querySelector(`textarea[data-field-key="${field.field_key}"]`);
                    if (ta) { ta.dataset.hasEn = 'true'; ta.dataset.enValue = tData.translated; }
                }
                done++;
            } catch (e) {
                failed++;
                console.warn('번역 실패:', field.field_key, e.message);
            }
        }

        const msg = failed > 0
            ? `번역 완료: ${done}개 성공, ${failed}개 실패` + (skip > 0 ? ` (${skip}개 기존 캐시)` : '')
            : `${done}개 필드 영문 번역 완료` + (skip > 0 ? ` (+${skip}개 기존 캐시)` : '');
        showToast(msg);
    } catch (e) {
        alert('번역 중 오류가 발생했습니다:\n' + e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = orig;
    }
}

/* ── 승인 워크플로 ──────────────────────────────────── */
const APPROVAL_REQUEST_URL = '{{ route("ai-agent.projects.deliverables.approval-request", [$project, $typeId]) }}';
const APPROVAL_RESPOND_URL = '{{ route("ai-agent.projects.deliverables.approval-respond",  [$project, $typeId]) }}';
const TOGGLE_SHARE_URL     = '{{ route("ai-agent.projects.deliverables.toggle-share",      [$project, $typeId]) }}';

async function dlvApprovalRequest(stepNo) {
    const sel = document.getElementById('approver-select-' + stepNo);
    if (!sel || !sel.value) { alert('{{ __('deliverables.approve_select_ph') }}'); return; }
    const approverId = sel.value;

    const res = await fetch(APPROVAL_REQUEST_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ step: stepNo, approver_id: approverId }),
    });
    const json = await res.json();
    if (json.ok) {
        alert(json.message);
        location.reload();
    } else {
        alert(json.message || LANG.error_occurred);
    }
}

async function dlvApprovalRespond(approvalId, action) {
    const res = await fetch(APPROVAL_RESPOND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ approval_id: approvalId, action }),
    });
    const json = await res.json();
    if (json.ok) {
        alert(json.message);
        location.reload();
    } else {
        alert(json.message || LANG.error_occurred);
    }
}

async function dlvApprovalRejectPrompt(approvalId) {
    const note = await __prompt(LANG.reject_prompt);
    if (note === null) return;
    fetch(APPROVAL_RESPOND_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ approval_id: approvalId, action: 'rejected', note }),
    }).then(r => r.json()).then(json => {
        if (json.ok) { alert(json.message); location.reload(); }
        else alert(json.message || LANG.error_occurred);
    });
}

/* ── 뷰어 모달 ────────────────────────────────────────── */
const DLV_CM_INDEX_URL   = '{{ route("ai-agent.projects.deliverables.viewer-comments.index",   [$project, $typeId]) }}';
const DLV_CM_STORE_URL   = '{{ route("ai-agent.projects.deliverables.viewer-comments.store",   [$project, $typeId]) }}';
const DLV_CM_DESTROY_TPL = '{{ route("ai-agent.projects.deliverables.viewer-comments.destroy", [$project, $typeId, "__ID__"]) }}';
const DLV_TOTAL          = {{ count($typeDef['steps']) }};
const _dlvCsrf           = () => document.querySelector('meta[name=csrf-token]').content;

let _dlvPage       = 1;
let _dlvZoom       = 1.0;
let _dlvAnnTool    = null;
let _dlvAnnClr     = '#ef4444';
let _dlvAnns       = {};
let _dlvAnnNum     = {};
let _dlvDrw        = false;
let _dlvDrag       = null;
let _dlvSX = 0, _dlvSY = 0;
let _dlvCur        = null;
let _dlvCmShowAll  = false;
let _dlvTextPos    = null;

async function dlvResetViewer() {
    document.getElementById('dlv-viewer-loading').style.display = 'flex';
    document.getElementById('dlv-viewer-wrap').style.display = 'none';
    document.getElementById('dlv-cm-list').innerHTML = '<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">불러오는 중…</div>';
    document.getElementById('dlv-cm-count').textContent = '';
    document.getElementById('dlv-cm-input').value = '';
    _dlvDrw = false; _dlvDrag = null; _dlvCur = null;
    _dlvAnnTool = null; _dlvCmShowAll = false;
    const svg = document.getElementById('dlv-ann-svg');
    while (svg.lastChild) svg.removeChild(svg.lastChild);
    svg.style.pointerEvents = 'none'; svg.style.cursor = 'default';
    document.querySelectorAll('.dlv-ann-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('dlv-text-popup').style.display = 'none';
    document.getElementById('dlv-text-input').value = '';
    _dlvTextPos = null;
    document.getElementById('dlv-ann-toolbar').style.display = 'none';
}
async function dlvOpenViewer() {
    document.getElementById('dlv-viewer-modal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    dlvResetViewer();
    dlvSetAnnColor(_dlvAnnClr);
    document.getElementById('dlv-ann-toolbar').style.display = 'flex';
    dlvGoToStep(1);
}
async function dlvCloseViewer() {
    document.getElementById('dlv-viewer-modal').style.display = 'none';
    document.body.style.overflow = '';
    dlvResetViewer();
}

/* ── 페이징 ── */
async function dlvGoToStep(n) {
    n = Math.max(1, Math.min(n, DLV_TOTAL));
    _dlvPage = n;
    document.querySelectorAll('.dlv-step-page').forEach(el => {
        el.style.display = (parseInt(el.dataset.step) === n) ? 'block' : 'none';
    });
    const active = document.querySelector(`.dlv-step-page[data-step="${n}"]`);
    document.getElementById('dlv-viewer-step-title').textContent = active ? active.dataset.title : '';
    document.getElementById('dlv-step-counter').textContent = `${n} / ${DLV_TOTAL}`;
    document.getElementById('dlv-btn-prev').disabled = n <= 1;
    document.getElementById('dlv-btn-next').disabled = n >= DLV_TOTAL;
    document.getElementById('dlv-btn-prev').style.opacity = n <= 1 ? '.35' : '1';
    document.getElementById('dlv-btn-next').style.opacity = n >= DLV_TOTAL ? '.35' : '1';
    // 단계 입력 동기화
    const stepInput = document.getElementById('dlv-cm-step');
    if (stepInput) stepInput.value = n;
    _dlvCmShowAll = false;
    dlvLoadComments(n);
    dlvRenderAnns();
    document.getElementById('dlv-viewer-loading').style.display = 'none';
    document.getElementById('dlv-viewer-wrap').style.display = 'block';
}
async function dlvPrevStep() { dlvGoToStep(_dlvPage - 1); }
async function dlvNextStep() { dlvGoToStep(_dlvPage + 1); }

/* ── 확대/축소 ── */
async function dlvSetZoom(z) {
    z = Math.max(0.4, Math.min(3.0, z));
    _dlvZoom = z;
    const inner = document.getElementById('dlv-content-inner');
    inner.style.transform = 'none';
    inner.style.marginBottom = '0';
    const natH = inner.offsetHeight;
    inner.style.transform = `scale(${z})`;
    inner.style.transformOrigin = 'top center';
    inner.style.marginBottom = z > 1 ? `${natH * (z - 1)}px` : '0';
    document.getElementById('dlv-zoom-label').textContent = `${Math.round(z * 100)}%`;
}
async function dlvZoomIn()       { dlvSetZoom(_dlvZoom + 0.1); }
async function dlvZoomOut()      { dlvSetZoom(_dlvZoom - 0.1); }
async function dlvZoomFit()      { dlvSetZoom(1.0); }
async function dlvZoomOriginal() { dlvSetZoom(1.0); }

/* ── 도형/주석 ── */
async function dlvSetAnnTool(tool) {
    _dlvAnnTool = tool;
    const svg = document.getElementById('dlv-ann-svg');
    svg.style.pointerEvents = tool ? 'all' : 'none';
    svg.style.cursor = tool ? 'crosshair' : 'default';
    document.querySelectorAll('.dlv-ann-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.tool === tool);
    });
}
async function dlvSetAnnColor(c) {
    _dlvAnnClr = c;
    document.querySelectorAll('.dlv-ann-color').forEach(b => {
        b.style.outline = b.dataset.color === c ? '2px solid #fff' : 'none';
        b.style.outlineOffset = '2px';
    });
}
async function dlvClearAnnotations() {
    _dlvAnns[_dlvPage] = [];
    _dlvAnnNum[_dlvPage] = 0;
    dlvRenderAnns();
}
async function _dlvStepAnns() {
    if (!_dlvAnns[_dlvPage]) _dlvAnns[_dlvPage] = [];
    return _dlvAnns[_dlvPage];
}
async function dlvRenderAnns() {
    const svg = document.getElementById('dlv-ann-svg');
    while (svg.lastChild) svg.removeChild(svg.lastChild);
    (_dlvAnns[_dlvPage] || []).forEach((a, i) => _dlvDrawAnn(svg, a, i));
}
async function _dlvDrawAnn(svg, a, i) {
    const ns = 'http://www.w3.org/2000/svg';
    const rm = () => { _dlvAnns[_dlvPage].splice(i, 1); dlvRenderAnns(); };

    // 드래그 시작 (포인터 모드일 때만)
    const onDragStart = (el) => {
        el.addEventListener('mousedown', e => {
            if (_dlvAnnTool) return;
            e.stopPropagation(); e.preventDefault();
            const r = svg.getBoundingClientRect();
            _dlvDrag = { idx: i, startX: e.clientX - r.left, startY: e.clientY - r.top, origA: JSON.parse(JSON.stringify(a)) };
            svg.style.pointerEvents = 'all'; // 드래그 중 SVG가 mousemove/mouseup 수신
        });
    };

    // 도형 공통: 이동 커서 + 이벤트 수신 + 드래그
    const interactive = (el) => {
        el.style.cursor = 'move';
        el.style.pointerEvents = 'all';
        onDragStart(el);
    };

    if (a.type === 'rect') {
        const el = document.createElementNS(ns, 'rect');
        el.setAttribute('x', Math.min(a.x1,a.x2)); el.setAttribute('y', Math.min(a.y1,a.y2));
        el.setAttribute('width', Math.abs(a.x2-a.x1)); el.setAttribute('height', Math.abs(a.y2-a.y1));
        el.setAttribute('fill','transparent'); el.setAttribute('stroke',a.c); el.setAttribute('stroke-width','2'); el.setAttribute('rx','2');
        interactive(el); svg.appendChild(el);
        _dlvAnnDeleteBtn(svg, Math.max(a.x1,a.x2), Math.min(a.y1,a.y2), rm);
    } else if (a.type === 'circle') {
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('cx',(a.x1+a.x2)/2); el.setAttribute('cy',(a.y1+a.y2)/2);
        el.setAttribute('rx',Math.abs(a.x2-a.x1)/2); el.setAttribute('ry',Math.abs(a.y2-a.y1)/2);
        el.setAttribute('fill','transparent'); el.setAttribute('stroke',a.c); el.setAttribute('stroke-width','2');
        interactive(el); svg.appendChild(el);
        _dlvAnnDeleteBtn(svg, Math.max(a.x1,a.x2), Math.min(a.y1,a.y2), rm);
    } else if (a.type === 'line') {
        const g = document.createElementNS(ns, 'g');
        const ln = document.createElementNS(ns, 'line');
        ln.setAttribute('x1',a.x1); ln.setAttribute('y1',a.y1); ln.setAttribute('x2',a.x2); ln.setAttribute('y2',a.y2);
        ln.setAttribute('stroke',a.c); ln.setAttribute('stroke-width','2'); ln.setAttribute('stroke-linecap','round');
        const hit = document.createElementNS(ns, 'line'); // 투명 두꺼운 선 (클릭 영역 확보)
        hit.setAttribute('x1',a.x1); hit.setAttribute('y1',a.y1); hit.setAttribute('x2',a.x2); hit.setAttribute('y2',a.y2);
        hit.setAttribute('stroke','transparent'); hit.setAttribute('stroke-width','12'); hit.setAttribute('stroke-linecap','round');
        g.appendChild(ln); g.appendChild(hit);
        interactive(g); svg.appendChild(g);
        _dlvAnnDeleteBtn(svg, Math.max(a.x1,a.x2), Math.min(a.y1,a.y2), rm);
    } else if (a.type === 'number') {
        const g = document.createElementNS(ns, 'g');
        const ci = document.createElementNS(ns, 'circle');
        ci.setAttribute('cx',a.x); ci.setAttribute('cy',a.y); ci.setAttribute('r','12'); ci.setAttribute('fill',a.c);
        const tx = document.createElementNS(ns, 'text');
        tx.setAttribute('x',a.x); tx.setAttribute('y',a.y); tx.setAttribute('text-anchor','middle');
        tx.setAttribute('dominant-baseline','central'); tx.setAttribute('fill','#fff');
        tx.setAttribute('font-size','10'); tx.setAttribute('font-weight','700'); tx.textContent = a.num;
        g.appendChild(ci); g.appendChild(tx);
        interactive(g); svg.appendChild(g);
        _dlvAnnDeleteBtn(svg, a.x + 14, a.y - 14, rm);
    } else if (a.type === 'text') {
        const el = document.createElementNS(ns, 'text');
        el.setAttribute('x',a.x); el.setAttribute('y',a.y); el.setAttribute('fill',a.c);
        el.setAttribute('font-size','14'); el.setAttribute('font-family','sans-serif'); el.textContent = a.t;
        interactive(el); svg.appendChild(el);
        _dlvAnnDeleteBtn(svg, a.x + (a.t ? a.t.length * 4 + 12 : 40), a.y - 6, rm);
    }
}

// 각 도형 우상단에 빨간 × 삭제 버튼 렌더링
async function _dlvAnnDeleteBtn(svg, bx, by, rm) {
    const ns = 'http://www.w3.org/2000/svg';
    const g = document.createElementNS(ns, 'g');
    g.style.cursor = 'pointer';
    g.style.pointerEvents = 'all';
    g.addEventListener('mousedown', e => e.stopPropagation()); // 드래그 방지
    g.addEventListener('click', e => { e.stopPropagation(); rm(); });
    const ci = document.createElementNS(ns, 'circle');
    ci.setAttribute('cx', bx); ci.setAttribute('cy', by); ci.setAttribute('r', '8');
    ci.setAttribute('fill', '#ef4444'); ci.setAttribute('stroke', '#fff'); ci.setAttribute('stroke-width', '1.5');
    const tx = document.createElementNS(ns, 'text');
    tx.setAttribute('x', bx); tx.setAttribute('y', by);
    tx.setAttribute('text-anchor', 'middle'); tx.setAttribute('dominant-baseline', 'central');
    tx.setAttribute('fill', '#fff'); tx.setAttribute('font-size', '11'); tx.setAttribute('font-weight', '700');
    tx.textContent = '×'; tx.style.pointerEvents = 'none';
    g.appendChild(ci); g.appendChild(tx);
    svg.appendChild(g);
}
async function _dlvPreview(svg, s) {
    const old = svg.getElementById('dlv-prv'); if (old) old.remove();
    const ns = 'http://www.w3.org/2000/svg'; let el;
    if (s.type==='rect') {
        el = document.createElementNS(ns,'rect');
        el.setAttribute('x',Math.min(s.x1,s.x2)); el.setAttribute('y',Math.min(s.y1,s.y2));
        el.setAttribute('width',Math.abs(s.x2-s.x1)); el.setAttribute('height',Math.abs(s.y2-s.y1));
        el.setAttribute('fill','none'); el.setAttribute('stroke',s.c); el.setAttribute('stroke-width','2');
        el.setAttribute('stroke-dasharray','4,3'); el.setAttribute('rx','2');
    } else if (s.type==='circle') {
        el = document.createElementNS(ns,'ellipse');
        el.setAttribute('cx',(s.x1+s.x2)/2); el.setAttribute('cy',(s.y1+s.y2)/2);
        el.setAttribute('rx',Math.abs(s.x2-s.x1)/2); el.setAttribute('ry',Math.abs(s.y2-s.y1)/2);
        el.setAttribute('fill','none'); el.setAttribute('stroke',s.c); el.setAttribute('stroke-width','2'); el.setAttribute('stroke-dasharray','4,3');
    } else if (s.type==='line') {
        el = document.createElementNS(ns,'line');
        el.setAttribute('x1',s.x1); el.setAttribute('y1',s.y1); el.setAttribute('x2',s.x2); el.setAttribute('y2',s.y2);
        el.setAttribute('stroke',s.c); el.setAttribute('stroke-width','2'); el.setAttribute('stroke-linecap','round'); el.setAttribute('stroke-dasharray','4,3');
    }
    if (el) { el.setAttribute('id','dlv-prv'); el.style.pointerEvents='none'; svg.appendChild(el); }
}
async function dlvAnnDown(e) {
    if (!_dlvAnnTool) return;
    const r = e.currentTarget.getBoundingClientRect();
    _dlvSX = e.clientX - r.left; _dlvSY = e.clientY - r.top;
    if (_dlvAnnTool === 'number') {
        if (!_dlvAnnNum[_dlvPage]) _dlvAnnNum[_dlvPage] = 0;
        _dlvAnnNum[_dlvPage]++;
        _dlvStepAnns().push({type:'number', x:_dlvSX, y:_dlvSY, num:_dlvAnnNum[_dlvPage], c:_dlvAnnClr});
        dlvRenderAnns(); dlvSetAnnTool(null); return;
    }
    if (_dlvAnnTool === 'text') {
        _dlvTextPos = { x: _dlvSX, y: _dlvSY };
        const popup = document.getElementById('dlv-text-popup');
        popup.style.left = `${Math.min(e.clientX, window.innerWidth - 380)}px`;
        popup.style.top  = `${Math.min(e.clientY, window.innerHeight - 210)}px`;
        popup.style.display = 'block';
        setTimeout(() => document.getElementById('dlv-text-input').focus(), 50);
        return;
    }
    _dlvDrw = true;
    _dlvCur = {type:_dlvAnnTool, x1:_dlvSX, y1:_dlvSY, x2:_dlvSX, y2:_dlvSY, c:_dlvAnnClr};
}
async function dlvAnnMove(e) {
    const r = e.currentTarget.getBoundingClientRect();
    const mx = e.clientX - r.left, my = e.clientY - r.top;
    // 도형 이동 (드래그 모드)
    if (_dlvDrag !== null) {
        const dx = mx - _dlvDrag.startX, dy = my - _dlvDrag.startY;
        const ann = _dlvAnns[_dlvPage][_dlvDrag.idx];
        const o = _dlvDrag.origA;
        if (o.x1 !== undefined) { ann.x1 = o.x1+dx; ann.y1 = o.y1+dy; ann.x2 = o.x2+dx; ann.y2 = o.y2+dy; }
        if (o.x  !== undefined) { ann.x  = o.x+dx;  ann.y  = o.y+dy; }
        dlvRenderAnns(); return;
    }
    // 도형 그리기
    if (!_dlvDrw || !_dlvCur) return;
    _dlvCur.x2 = mx; _dlvCur.y2 = my;
    dlvRenderAnns(); _dlvPreview(document.getElementById('dlv-ann-svg'), _dlvCur);
}
async function dlvAnnUp(e) {
    // 도형 이동 종료
    if (_dlvDrag !== null) {
        _dlvDrag = null;
        if (!_dlvAnnTool) document.getElementById('dlv-ann-svg').style.pointerEvents = 'none';
        return;
    }
    // 도형 그리기 종료
    if (!_dlvDrw || !_dlvCur) return;
    _dlvDrw = false;
    if (Math.abs(_dlvCur.x2-_dlvCur.x1)>4 || Math.abs(_dlvCur.y2-_dlvCur.y1)>4)
        _dlvStepAnns().push({..._dlvCur});
    _dlvCur = null; dlvRenderAnns();
    dlvSetAnnTool(null);
}

/* ── 의견 ── */
async function dlvLoadComments(step) {
    const list = document.getElementById('dlv-cm-list');
    list.innerHTML = '<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">불러오는 중…</div>';
    // 단계 필터 바 업데이트
    const filterLabel = document.getElementById('dlv-cmt-filter-label');
    const filterBtn   = document.getElementById('dlv-cmt-filter-btn');
    if (filterLabel) filterLabel.textContent = _dlvCmShowAll ? '전체 단계' : `단계 ${step}`;
    if (filterBtn)   filterBtn.textContent   = _dlvCmShowAll ? '현재 단계만' : '전체 보기';
    try {
        const url = _dlvCmShowAll ? DLV_CM_INDEX_URL : `${DLV_CM_INDEX_URL}?step=${step}`;
        const r = await fetch(url, {headers:{'Accept':'application/json','X-CSRF-TOKEN':_dlvCsrf()}});
        const data = await r.json();
        document.getElementById('dlv-cm-count').textContent = data.length;
        dlvRenderComments(data);
    } catch { list.innerHTML = '<div style="color:#ef4444;font-size:12px;padding:12px;text-align:center;">불러오기 실패</div>'; }
}
async function dlvRenderComments(list) {
    const el = document.getElementById('dlv-cm-list');
    if (!list.length) {
        el.innerHTML = '<div style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">의견이 없습니다.</div>';
        return;
    }
    el.innerHTML = list.map(c => `
        <div style="background:#f9fafb;border:1px solid #f3f4f6;border-radius:10px;padding:10px 12px;">
            <div style="display:flex;align-items:center;gap:5px;margin-bottom:5px;">
                <span style="font-size:11px;font-weight:700;color:#6d28d9;">${c.user_name}</span>
                <span style="font-size:10px;padding:1px 5px;border-radius:3px;background:#ede9fe;color:#6d28d9;font-weight:600;">단계 ${c.step_order ?? '?'}</span>
                <span style="font-size:10px;color:#9ca3af;flex:1;">${c.created_at}</span>
                ${c.mine ? `<button onclick="dlvDeleteComment(${c.id})" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:15px;padding:0 2px;line-height:1;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#9ca3af'">×</button>` : ''}
            </div>
            <div style="font-size:13px;color:#1f2937;line-height:1.6;white-space:pre-wrap;word-break:break-word;">${c.body.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')}</div>
        </div>`).join('');
}
async function dlvToggleCmtFilter() {
    _dlvCmShowAll = !_dlvCmShowAll;
    await dlvLoadComments(_dlvPage);
}
async function dlvAdjustStep(delta) {
    const inp = document.getElementById('dlv-cm-step');
    const max = {{ count($typeDef['steps']) }};
    const cur = parseInt(inp.value) || _dlvPage;
    inp.value = Math.max(1, Math.min(max, cur + delta));
}
async function dlvSubmitComment() {
    const input = document.getElementById('dlv-cm-input');
    const body = input.value.trim(); if (!body) return;
    const stepInput = document.getElementById('dlv-cm-step');
    const step = (stepInput && stepInput.value) ? parseInt(stepInput.value) : _dlvPage;
    const btn = document.getElementById('dlv-cm-submit'); btn.disabled = true;
    try {
        const r = await fetch(DLV_CM_STORE_URL, {
            method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':_dlvCsrf()},
            body: JSON.stringify({step, body})
        });
        const data = await r.json();
        if (data.ok) { input.value = ''; await dlvLoadComments(_dlvPage); }
    } finally { btn.disabled = false; }
}
async function dlvDeleteComment(id) {
    if (!await __confirm('의견을 삭제하시겠습니까?')) return;
    await fetch(DLV_CM_DESTROY_TPL.replace('__ID__', id), {method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':_dlvCsrf()}});
    dlvLoadComments(_dlvPage);
}

/* ── 텍스트 주석 팝업 ── */
async function dlvConfirmText() {
    const val = document.getElementById('dlv-text-input').value.trim();
    document.getElementById('dlv-text-popup').style.display = 'none';
    document.getElementById('dlv-text-input').value = '';
    if (val && _dlvTextPos) {
        _dlvStepAnns().push({ type: 'text', x: _dlvTextPos.x, y: _dlvTextPos.y, t: val, c: _dlvAnnClr });
        dlvRenderAnns();
    }
    _dlvTextPos = null;
    dlvSetAnnTool(null);
}
async function dlvCancelText() {
    document.getElementById('dlv-text-popup').style.display = 'none';
    document.getElementById('dlv-text-input').value = '';
    _dlvTextPos = null;
    dlvSetAnnTool(null);
}
document.getElementById('dlv-text-input').addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); dlvConfirmText(); }
    if (e.key === 'Escape') { e.preventDefault(); dlvCancelText(); }
});

/* ── 링크 공유 토글 ──────────────────────────────────── */
async function dlvToggleShare() {
    const res  = await fetch(TOGGLE_SHARE_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
    });
    const json = await res.json();
    const box  = document.getElementById('share-url-box');
    const inp  = document.getElementById('share-url-input');
    if (json.active) {
        inp.value          = json.url;
        box.style.display  = 'flex';
    } else {
        box.style.display  = 'none';
        inp.value          = '';
    }
}

async function dlvCopyShareUrl() {
    const inp = document.getElementById('share-url-input');
    navigator.clipboard.writeText(inp.value).then(() => alert(LANG.link_copied));
}

/* ── Word 다운로드 팝오버 ── */
(function () {
    const KO_URL = '{{ route("ai-agent.projects.deliverables.export-word", [$project, $typeId]) }}';
    const EN_URL = KO_URL + '?lang=en';
    const DL_ICON = '<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>';

    window.dlvWordPopover = function (btn) {
        const existing = document.getElementById('dlv-word-pop');
        if (existing) {
            existing.remove();
            if (existing._srcBtn === btn) return;
        }

        const pop = document.createElement('div');
        pop.id = 'dlv-word-pop';
        pop._srcBtn = btn;
        pop.className = 'dlv-word-pop';

        [
            { label: '한글 Word 다운로드', url: KO_URL },
            { label: 'English Word Download', url: EN_URL },
        ].forEach(({ label, url }) => {
            const item = document.createElement('div');
            item.className = 'dlv-word-pop-item';
            item.innerHTML = DL_ICON + label;
            item.addEventListener('click', () => { window.location.href = url; pop.remove(); });
            pop.appendChild(item);
        });

        document.body.appendChild(pop);

        const r = btn.getBoundingClientRect();
        pop.style.left = r.left + 'px';

        requestAnimationFrame(() => {
            const pw = pop.offsetWidth;
            const ph = pop.offsetHeight;
            pop.style.top = (r.top - ph - 5) + 'px';
            if (r.left + pw > window.innerWidth - 8) {
                pop.style.left = Math.max(8, r.right - pw) + 'px';
            }
        });

        setTimeout(() => {
            document.addEventListener('click', function close(e) {
                if (!pop.contains(e.target) && e.target !== btn) {
                    pop.remove();
                    document.removeEventListener('click', close);
                }
            });
        }, 0);
    };
})();

/* ══════════════════════════════════════════════════
   테이블 빌더 (TABLE-*)
══════════════════════════════════════════════════ */
function tblGetBuilder(el) {
    return el.closest('.dlv-tbl-builder');
}

function tblGetTable(builder) {
    return builder.querySelector('.dlv-tbl-edit');
}

function tblAutoHeight(inp) {
    inp.style.height = 'auto';
    inp.style.height = inp.scrollHeight + 'px';
}

function tblResizeAll(builder) {
    builder.querySelectorAll('.dlv-tbl-cell').forEach(ta => tblAutoHeight(ta));
}

function tblMakeRow(colCount, isHeader) {
    const tr = document.createElement('tr');
    for (let i = 0; i < colCount; i++) {
        const cell = document.createElement(isHeader ? 'th' : 'td');
        const inp = document.createElement('textarea');
        inp.className = 'dlv-tbl-cell';
        inp.rows = 1;
        inp.addEventListener('input', function () {
            tblAutoHeight(this);
            tblSync(tblGetBuilder(this));
        });
        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const cells = Array.from(tblGetBuilder(this).querySelectorAll('.dlv-tbl-cell'));
                const idx = cells.indexOf(this);
                const next = cells[idx + (e.shiftKey ? -1 : 1)];
                if (next) next.focus();
            }
        });
        cell.appendChild(inp);
        tr.appendChild(cell);
    }
    return tr;
}

function tblToMarkdown(builder) {
    const tbl = tblGetTable(builder);
    const rows = Array.from(tbl.rows);
    if (!rows.length) return '';
    const toMd = row => '| ' + Array.from(row.cells).map(c => (c.querySelector('textarea')?.value ?? '').replace(/\|/g, '\\|').replace(/\n/g, ' ')).join(' | ') + ' |';
    const header = toMd(rows[0]);
    const sep = '| ' + Array.from(rows[0].cells).map(() => '------').join(' | ') + ' |';
    const body = rows.slice(1).map(toMd);
    return [header, sep, ...body].join('\n');
}

function tblParseMd(md) {
    const lines = md.trim().split('\n').map(l => l.trim()).filter(l => l.startsWith('|'));
    if (!lines.length) return { headers: [], rows: [] };
    const parse = line => line.slice(1, -1).split('|').map(c => c.trim().replace(/\\n/g, '\n').replace(/\\\|/g, '|'));
    const headers = parse(lines[0]);
    const rows = lines.slice(2).map(parse);
    return { headers, rows };
}

function tblSync(builder) {
    const fieldKey = builder.dataset.fieldKey;
    if (!fieldKey) return;
    const md = tblToMarkdown(builder);
    const ta = document.querySelector(`textarea[name="fields[${fieldKey}]"]`);
    if (ta) ta.value = md;
}

function tblInit(builder) {
    const md = builder.dataset.initMd || '';
    const tbl = tblGetTable(builder);
    tbl.innerHTML = '';
    if (md.trim()) {
        const { headers, rows } = tblParseMd(md);
        if (headers.length) {
            const hRow = tblMakeRow(headers.length, true);
            headers.forEach((h, i) => {
                hRow.cells[i].querySelector('textarea').value = h;
            });
            tbl.appendChild(hRow);
            rows.forEach(row => {
                const tr = tblMakeRow(headers.length, false);
                row.forEach((v, i) => {
                    if (tr.cells[i]) tr.cells[i].querySelector('textarea').value = v;
                });
                tbl.appendChild(tr);
            });
        }
    } else {
        // 기본 3×3
        tbl.appendChild(tblMakeRow(3, true));
        tbl.appendChild(tblMakeRow(3, false));
        tbl.appendChild(tblMakeRow(3, false));
    }
    tblSync(builder);
    // 모든 셀 높이를 내용에 맞게 조정 (스크롤 없이)
    requestAnimationFrame(() => tblResizeAll(builder));
}

function tblAddRow(btn) {
    const b = tblGetBuilder(btn);
    const tbl = tblGetTable(b);
    const colCount = tbl.rows[0] ? tbl.rows[0].cells.length : 3;
    tbl.appendChild(tblMakeRow(colCount, false));
    tblSync(b);
    requestAnimationFrame(() => tblResizeAll(b));
}

function tblAddCol(btn) {
    const b = tblGetBuilder(btn);
    const tbl = tblGetTable(b);
    Array.from(tbl.rows).forEach((row, ri) => {
        const cell = document.createElement(ri === 0 ? 'th' : 'td');
        const inp = document.createElement('textarea');
        inp.className = 'dlv-tbl-cell';
        inp.rows = 1;
        inp.addEventListener('input', function () {
            tblAutoHeight(this);
            tblSync(tblGetBuilder(this));
        });
        inp.addEventListener('keydown', function (e) {
            if (e.key === 'Tab') {
                e.preventDefault();
                const cells = Array.from(tblGetBuilder(this).querySelectorAll('.dlv-tbl-cell'));
                const idx = cells.indexOf(this);
                const next = cells[idx + (e.shiftKey ? -1 : 1)];
                if (next) next.focus();
            }
        });
        cell.appendChild(inp);
        row.appendChild(cell);
    });
    tblSync(b);
    requestAnimationFrame(() => tblResizeAll(b));
}

function tblDelRow(btn) {
    const b = tblGetBuilder(btn);
    const tbl = tblGetTable(b);
    if (tbl.rows.length > 1) tbl.deleteRow(tbl.rows.length - 1);
    tblSync(b);
}

function tblDelCol(btn) {
    const b = tblGetBuilder(btn);
    const tbl = tblGetTable(b);
    Array.from(tbl.rows).forEach(row => {
        if (row.cells.length > 1) row.deleteCell(row.cells.length - 1);
    });
    tblSync(b);
}

async function tblSave(btn) {
    const b = tblGetBuilder(btn);
    const toolId  = b.dataset.toolId;
    const stepNo  = parseInt(b.dataset.step);
    const md = tblToMarkdown(b);
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '저장 중…';
    try {
        const res = await fetch(SAVE_TOOL_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ step: stepNo, tool_id: toolId, result: { markdown: md } }),
        });
        const json = await res.json();
        if (json.ok) {
            btn.innerHTML = '✓ 저장됨';
            setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 1500);
        } else {
            alert(json.message || '저장 실패');
            btn.innerHTML = origHtml; btn.disabled = false;
        }
    } catch (e) {
        alert('저장 중 오류: ' + e.message);
        btn.innerHTML = origHtml; btn.disabled = false;
    }
}

async function tblAiGen(btn) {
    const b = tblGetBuilder(btn);
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '웍스 생성 중…';
    try {
        const res = await fetch(ANALYZE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ action: 'table', step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, fields: getFormData().fields }),
        });
        const json = await res.json();
        const md = json.markdown || json.result?.markdown || json.text || null;
        if (md) {
            b.dataset.initMd = md;
            tblInit(b);
            btn.innerHTML = '저장 중…';
            // tool result 저장 (markdown)
            await fetch(SAVE_TOOL_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, result: { markdown: md } }),
            });
            // step data 저장 (fields textarea 동기화 값)
            await fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify(getFormData()),
            });
            btn.innerHTML = '✓ 생성 완료';
            setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 1800);
            return;
        } else if (json.message) {
            alert(json.message);
        }
    } catch (e) {
        alert('웍스 생성 중 오류: ' + e.message);
    }
    btn.innerHTML = origHtml; btn.disabled = false;
}

/* ══════════════════════════════════════════════════
   다이어그램 빌더 (DIAGRAM-*)
══════════════════════════════════════════════════ */
function dgrGetBuilder(el) {
    return el.closest('.dlv-dgr-builder');
}

function dgrRender(btn) {
    const b = btn ? dgrGetBuilder(btn) : document.querySelector('.dlv-dgr-builder');
    if (!b) return;
    const code = b.querySelector('.dlv-dgr-textarea').value.trim();
    const preview = b.querySelector('.dlv-dgr-preview');
    const errEl   = b.querySelector('.dlv-dgr-err');
    if (errEl) errEl.remove();
    if (!code) { preview.innerHTML = '<span style="color:#94a3b8;font-size:12px;">코드를 입력하세요</span>'; return; }
    try {
        preview.innerHTML = '';
        preview.removeAttribute('data-processed');
        preview.className = 'dlv-dgr-preview mermaid';
        preview.textContent = code;
        if (window.mermaid) {
            mermaid.run({ nodes: [preview] }).then(() => {
                b._pzW = 0; b._pzH = 0;
                setTimeout(() => _dgrApplyZoom(b), 60);
            }).catch(err => {
                preview.innerHTML = '';
                const d = document.createElement('div');
                d.className = 'dlv-dgr-err';
                d.textContent = '렌더링 오류: ' + err.message;
                preview.parentElement.appendChild(d);
            });
        }
    } catch (e) {
        preview.innerHTML = '';
        const d = document.createElement('div');
        d.className = 'dlv-dgr-err';
        d.textContent = '렌더링 오류: ' + e.message;
        preview.parentElement.appendChild(d);
    }
}

/* ── SVG → PNG base64 공통 헬퍼 ── */
async function _svgElToPng(svgEl) {
    if (!svgEl) return null;
    return new Promise((resolve) => {
        try {
            // 원본 전체 크기: viewBox 기준 (transform/zoom 상태와 무관)
            let W = 0, H = 0;
            const vb = svgEl.getAttribute('viewBox');
            if (vb) {
                const p = vb.trim().split(/[\s,]+/);
                if (p.length >= 4 && +p[2] > 0 && +p[3] > 0) { W = Math.round(+p[2]); H = Math.round(+p[3]); }
            }
            if (!W || !H) {
                // fallback: transform 을 일시 제거 후 측정
                const saved = svgEl.style.cssText;
                svgEl.style.cssText = '';
                const bb = svgEl.getBoundingClientRect();
                W = Math.max(Math.round(bb.width), 400);
                H = Math.max(Math.round(bb.height), 200);
                svgEl.style.cssText = saved;
            }
            const clone = svgEl.cloneNode(true);
            clone.setAttribute('width',  W);
            clone.setAttribute('height', H);
            clone.style.cssText    = '';        // pan-zoom 인라인 스타일 완전 제거
            clone.style.background = '#ffffff';
            const svgStr = new XMLSerializer().serializeToString(clone);
            const blob   = new Blob([svgStr], { type: 'image/svg+xml;charset=utf-8' });
            const url    = URL.createObjectURL(blob);
            const img    = new Image();
            img.onload = () => {
                const scale = 2;
                const cv    = document.createElement('canvas');
                cv.width = W * scale; cv.height = H * scale;
                const ctx = cv.getContext('2d');
                ctx.scale(scale, scale);
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, W, H);
                ctx.drawImage(img, 0, 0, W, H);
                URL.revokeObjectURL(url);
                resolve(cv.toDataURL('image/png').replace('data:image/png;base64,', ''));
            };
            img.onerror = () => { URL.revokeObjectURL(url); resolve(null); };
            img.src = url;
        } catch { resolve(null); }
    });
}

async function dgrSvgToPng(builder) {
    const preview = builder.querySelector('.dlv-dgr-preview');
    return _svgElToPng(preview?.querySelector('svg'));
}

/* ══════════════════════════════════════════════════
   질의응답 폼 빌더 (FORM-QA)
══════════════════════════════════════════════════ */
const _QA_RISKS = [
    { key:'none',     label:'해당없음' },
    { key:'low',      label:'낮음'   },
    { key:'medium',   label:'보통'   },
    { key:'high',     label:'높음'   },
    { key:'critical', label:'위험'   },
];

function _qaMakeRiskPills(selected) {
    return _QA_RISKS.map(r =>
        `<button type="button" class="dlv-qa-risk-pill${r.key === selected ? ' is-active' : ''}" data-risk="${r.key}"
                 onclick="_qaSelectRisk(this)">${r.label}</button>`
    ).join('');
}

function _qaSelectRisk(pill) {
    pill.closest('.dlv-qa-risk-pills').querySelectorAll('.dlv-qa-risk-pill').forEach(p => p.classList.remove('is-active'));
    pill.classList.add('is-active');
}

function _qaMakeItem(data) {
    data = data || {};
    const d = document.createElement('div');
    d.className = 'dlv-qa-item';
    d.innerHTML = `
        <div class="dlv-qa-item-hd">
            <span class="dlv-qa-item-num"></span>
            <button type="button" class="dlv-qa-item-del" onclick="qaRemoveItem(this)" title="질문 삭제">×</button>
        </div>
        <div class="dlv-qa-item-body">
            <div>
                <div class="dlv-qa-lbl">질문</div>
                <textarea class="dlv-qa-cell dlv-qa-q-input" placeholder="질문을 입력하세요" rows="1"
                          oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                >${_qaEsc(data.question ?? '')}</textarea>
            </div>
            <div>
                <div class="dlv-qa-lbl">답변</div>
                <textarea class="dlv-qa-cell dlv-qa-a-input" placeholder="답변을 입력하세요" rows="2"
                          oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                >${_qaEsc(data.answer ?? '')}</textarea>
            </div>
            <div class="dlv-qa-meta">
                <div class="dlv-qa-risk-wrap">
                    <div class="dlv-qa-lbl">위험 수준</div>
                    <div class="dlv-qa-risk-pills">${_qaMakeRiskPills(data.risk ?? 'none')}</div>
                </div>
                <div class="dlv-qa-notes-wrap">
                    <div class="dlv-qa-lbl">비고</div>
                    <textarea class="dlv-qa-cell dlv-qa-n-input" placeholder="비고 (선택)" rows="1"
                              oninput="this.style.height='auto';this.style.height=this.scrollHeight+'px'"
                    >${_qaEsc(data.notes ?? '')}</textarea>
                </div>
            </div>
        </div>`;
    return d;
}

function _qaMakeSection(data) {
    data = data || {};
    const wrap = document.createElement('div');
    wrap.className = 'dlv-qa-section';
    wrap.innerHTML = `
        <div class="dlv-qa-sec-hd">
            <span class="dlv-qa-sec-num"></span>
            <input type="text" class="dlv-qa-sec-title" placeholder="섹션 제목을 입력하세요"
                   value="${_qaEsc(data.title ?? '')}">
            <div class="dlv-qa-sec-btns">
                <button type="button" class="dlv-qa-sec-btn" onclick="qaAddItem(this)">+ 질문</button>
                <button type="button" class="dlv-qa-sec-btn" onclick="_qaToggle(this)" title="접기/펼치기">▲</button>
                <button type="button" class="dlv-qa-sec-btn danger" onclick="qaRemoveSection(this)">삭제</button>
            </div>
        </div>
        <div class="dlv-qa-items${data.collapsed ? ' collapsed' : ''}"></div>`;
    const itemsEl = wrap.querySelector('.dlv-qa-items');
    (data.items || [{ question:'', answer:'', risk:'none', notes:'' }]).forEach(item => {
        itemsEl.appendChild(_qaMakeItem(item));
    });
    return wrap;
}

function _qaEsc(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function _qaToggle(btn) {
    const items = btn.closest('.dlv-qa-section').querySelector('.dlv-qa-items');
    const collapsed = items.classList.toggle('collapsed');
    btn.textContent = collapsed ? '▼' : '▲';
}

function _qaRenumber(builder) {
    builder.querySelectorAll('.dlv-qa-section').forEach((sec, si) => {
        sec.querySelector('.dlv-qa-sec-num').textContent = si + 1;
        sec.querySelectorAll('.dlv-qa-item').forEach((item, qi) => {
            item.querySelector('.dlv-qa-item-num').textContent = 'Q' + (qi + 1);
        });
    });
    _qaToggleEmpty(builder);
}

function _qaToggleEmpty(builder) {
    const hasSections = builder.querySelectorAll('.dlv-qa-section').length > 0;
    builder.querySelector('.dlv-qa-empty').style.display   = hasSections ? 'none' : '';
    builder.querySelector('.dlv-qa-sections').style.display = hasSections ? '' : 'none';
}

function qaInit(builder) {
    let data;
    try { data = JSON.parse(builder.dataset.init || '{}'); } catch { data = {}; }
    const sections = data.sections || [];
    const sectionsEl = builder.querySelector('.dlv-qa-sections');
    sections.forEach(sec => sectionsEl.appendChild(_qaMakeSection(sec)));
    _qaRenumber(builder);
    // textarea 높이 초기화
    builder.querySelectorAll('textarea').forEach(ta => {
        ta.style.height = 'auto';
        ta.style.height = ta.scrollHeight + 'px';
    });
}

function qaAddSection(btn) {
    const builder = btn.closest('.dlv-qa-builder');
    const sec = _qaMakeSection({ title: '', items: [{ question:'', answer:'', risk:'none', notes:'' }] });
    builder.querySelector('.dlv-qa-sections').appendChild(sec);
    _qaRenumber(builder);
    sec.querySelector('.dlv-qa-sec-title').focus();
}

function qaRemoveSection(btn) {
    const sec = btn.closest('.dlv-qa-section');
    if (!confirm('이 섹션을 삭제할까요?')) return;
    sec.remove();
    _qaRenumber(btn.closest('.dlv-qa-builder'));
}

function qaAddItem(btn) {
    const sec = btn.closest('.dlv-qa-section');
    const itemsEl = sec.querySelector('.dlv-qa-items');
    if (itemsEl.classList.contains('collapsed')) {
        itemsEl.classList.remove('collapsed');
        sec.querySelector('[onclick="_qaToggle(this)"]').textContent = '▲';
    }
    const item = _qaMakeItem();
    itemsEl.appendChild(item);
    _qaRenumber(btn.closest('.dlv-qa-builder'));
    item.querySelector('.dlv-qa-q-input').focus();
}

function qaRemoveItem(btn) {
    const item = btn.closest('.dlv-qa-item');
    const builder = item.closest('.dlv-qa-builder');
    item.remove();
    _qaRenumber(builder);
}

function _qaCollect(builder) {
    const sections = [];
    builder.querySelectorAll('.dlv-qa-section').forEach(sec => {
        const items = [];
        sec.querySelectorAll('.dlv-qa-item').forEach(item => {
            items.push({
                question: item.querySelector('.dlv-qa-q-input').value,
                answer:   item.querySelector('.dlv-qa-a-input').value,
                risk:     item.querySelector('.dlv-qa-risk-pill.is-active')?.dataset.risk ?? 'none',
                notes:    item.querySelector('.dlv-qa-n-input').value,
            });
        });
        sections.push({ title: sec.querySelector('.dlv-qa-sec-title').value, items });
    });
    return { sections };
}

async function qaSave(btn) {
    const builder  = btn.closest('.dlv-qa-builder');
    const result   = _qaCollect(builder);
    const origHtml = btn.innerHTML;
    btn.disabled  = true;
    btn.textContent = '저장 중…';
    try {
        const r = await fetch(SAVE_TOOL_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ step: +builder.dataset.step, tool_id: 'FORM-QA', result }),
        });
        const d = await r.json();
        if (!d.ok) throw new Error(d.message || '저장 실패');
        btn.textContent = '저장됨 ✓';
        setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 1200);
        return;
    } catch (e) {
        alert('저장 실패: ' + e.message);
    }
    btn.innerHTML = origHtml;
    btn.disabled  = false;
}

async function qaAiGen(btn) {
    const builder = btn.closest('.dlv-qa-builder');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = '생성 중…';
    try {
        const r = await fetch(ANALYZE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ action: 'qa', step: +builder.dataset.step, tool_id: 'FORM-QA', fields: getFormData().fields }),
        });
        const d = await r.json();
        if (!d.ok || !d.result?.sections) throw new Error(d.message || '생성 실패');
        builder.querySelector('.dlv-qa-sections').innerHTML = '';
        d.result.sections.forEach(sec => {
            builder.querySelector('.dlv-qa-sections').appendChild(_qaMakeSection(sec));
        });
        _qaRenumber(builder);
        builder.querySelectorAll('textarea').forEach(ta => {
            ta.style.height = 'auto';
            ta.style.height = ta.scrollHeight + 'px';
        });
    } catch (e) {
        alert('웍스 초안 생성 실패: ' + e.message);
    }
    btn.innerHTML = origHtml;
    btn.disabled  = false;
}

/* ══════════════════════════════════════════════════
   테이블 Excel 다운로드
══════════════════════════════════════════════════ */
async function tblExcel(btn) {
    const b    = tblGetBuilder(btn);
    const tbl  = tblGetTable(b);
    const allRows = Array.from(tbl.rows).map(row =>
        Array.from(row.cells).map(cell => cell.querySelector('textarea')?.value ?? '')
    );
    if (!allRows.length) { alert('테이블 내용이 없습니다.'); return; }

    const origHtml = btn.innerHTML;
    btn.disabled  = true;
    btn.innerHTML = '변환 중…';
    try {
        if (!window.ExcelJS) {
            await new Promise((res, rej) => {
                const s = document.createElement('script');
                s.src = 'https://unpkg.com/exceljs@4.4.0/dist/exceljs.min.js';
                s.onload = res;
                s.onerror = () => rej(new Error('ExcelJS 로드 실패'));
                document.head.appendChild(s);
            });
        }

        const wb = new ExcelJS.Workbook();
        wb.creator = 'SupportWorks';
        wb.created = new Date();

        const sheetName = (b.dataset.toolId ?? 'table').replace(/[\\/*?[\]:]/g, '').slice(0, 31) || 'Sheet1';
        const ws = wb.addWorksheet(sheetName, {
            views: [{ state: 'frozen', ySplit: 1 }]
        });

        const colCount = allRows[0].length;

        // 컬럼 너비: 내용 길이 기반 자동 계산
        ws.columns = Array.from({ length: colCount }, (_, ci) => ({
            width: Math.min(52, Math.max(14, ...allRows.map(r => String(r[ci] ?? '').length + 3)))
        }));

        // ── 헤더 행 ──
        const headerRow = ws.addRow(allRows[0]);
        headerRow.height = 22;
        headerRow.eachCell({ includeEmpty: true }, (cell) => {
            cell.value     = cell.value ?? '';
            cell.font      = { name: 'Calibri', bold: true, size: 11, color: { argb: 'FFFFFFFF' } };
            cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF7C3AED' } };
            cell.alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
            cell.border    = {
                top:    { style: 'thin', color: { argb: 'FF6D28D9' } },
                left:   { style: 'thin', color: { argb: 'FF6D28D9' } },
                bottom: { style: 'medium', color: { argb: 'FF5B21B6' } },
                right:  { style: 'thin', color: { argb: 'FF6D28D9' } },
            };
        });

        // 헤더 자동 필터
        ws.autoFilter = { from: { row: 1, column: 1 }, to: { row: 1, column: colCount } };

        // ── 데이터 행 ──
        allRows.slice(1).forEach((rowData, ri) => {
            const row = ws.addRow(rowData);
            row.height = 18;
            const bgArgb = ri % 2 === 0 ? 'FFFFFFFF' : 'FFF5F3FF';
            row.eachCell({ includeEmpty: true }, (cell) => {
                cell.value     = cell.value ?? '';
                cell.font      = { name: 'Calibri', size: 10, color: { argb: 'FF1E1B2E' } };
                cell.fill      = { type: 'pattern', pattern: 'solid', fgColor: { argb: bgArgb } };
                cell.alignment = { vertical: 'middle', wrapText: true };
                cell.border    = {
                    top:    { style: 'hair', color: { argb: 'FFE2E8F0' } },
                    left:   { style: 'thin', color: { argb: 'FFE2E8F0' } },
                    bottom: { style: 'hair', color: { argb: 'FFE2E8F0' } },
                    right:  { style: 'thin', color: { argb: 'FFE2E8F0' } },
                };
            });
        });

        // 마지막 행 하단 테두리 강조
        const lastRow = ws.lastRow;
        if (lastRow && lastRow.number > 1) {
            lastRow.eachCell({ includeEmpty: true }, (cell) => {
                cell.border = { ...cell.border, bottom: { style: 'thin', color: { argb: 'FFCBCBCB' } } };
            });
        }

        const buf  = await wb.xlsx.writeBuffer();
        const blob = new Blob([buf], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
        const url  = URL.createObjectURL(blob);
        const a    = document.createElement('a');
        a.href     = url;
        a.download = (b.dataset.toolId ?? 'table') + '_' + new Date().toISOString().slice(0, 10) + '.xlsx';
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        alert('Excel 다운로드 실패: ' + e.message);
    } finally {
        btn.innerHTML = origHtml;
        btn.disabled  = false;
    }
}

/* ══════════════════════════════════════════════════
   다이어그램 PNG 다운로드 + 라이트박스 팝업
══════════════════════════════════════════════════ */
async function _dgrSaveAsPng(svgEl, name) {
    const base64 = await _svgElToPng(svgEl);
    if (!base64) { alert('PNG 변환에 실패했습니다. 미리보기를 먼저 확인해 주세요.'); return; }
    const a = document.createElement('a');
    a.href     = 'data:image/png;base64,' + base64;
    a.download = name + '_' + new Date().toISOString().slice(0, 10) + '.png';
    a.click();
}

async function dgrDownloadPng(btn) {
    const b     = dgrGetBuilder(btn);
    const svgEl = b.querySelector('.dlv-dgr-preview svg');
    if (!svgEl) { alert('렌더링된 다이어그램이 없습니다. 미리보기 후 다시 시도하세요.'); return; }
    await _dgrSaveAsPng(svgEl, b.dataset.toolId ?? 'diagram');
}

/* ── 다이어그램 줌 / 라이트박스 ── */
let _dgrLbSvg  = null;
let _dgrLbNW   = 0;
let _dgrLbNH   = 0;
let _dgrLbZoom = 1.0;
let _dgrLbPanX = 0;
let _dgrLbPanY = 0;

function _dgrGetSvgNaturalSize(svgEl) {
    if (!svgEl) return { w: 400, h: 300 };
    const vb = svgEl.getAttribute('viewBox');
    if (vb) {
        const p = vb.trim().split(/[\s,]+/);
        if (p.length >= 4 && +p[2] > 0 && +p[3] > 0) return { w: +p[2], h: +p[3] };
    }
    const bb = svgEl.getBoundingClientRect();
    return { w: Math.max(bb.width || 0, 100), h: Math.max(bb.height || 0, 80) };
}

function _dgrApplyZoom(builder) {
    if (!builder) return;
    const preview = builder.querySelector('.dlv-dgr-preview');
    const svg = preview?.querySelector('svg');
    if (!svg) return;

    // 첫 렌더 또는 리셋: SVG 크기 측정 후 중앙 배치 초기화
    if (!builder._pzW || !builder._pzH) {
        // position:absolute 설정 (직접 child SVG만)
        svg.style.position = 'absolute';
        svg.style.left     = '0';
        svg.style.top      = '0';
        // Mermaid 인라인 max-width 제거 전, viewBox로 자연 크기 파악
        const vb = svg.getAttribute('viewBox');
        let natW = 0, natH = 0;
        if (vb) {
            const p = vb.trim().split(/[\s,]+/);
            if (p.length >= 4 && +p[2] > 0 && +p[3] > 0) { natW = +p[2]; natH = +p[3]; }
        }
        const pb = preview.getBoundingClientRect();
        if (natW && natH) {
            // viewBox 비율을 유지하며 preview에 맞게 fit
            const scaleW = pb.width  / natW;
            const scaleH = pb.height / natH;
            const fit    = Math.min(scaleW, scaleH, 1.0);
            builder._pzW = Math.round(natW * fit);
            builder._pzH = Math.round(natH * fit);
        } else {
            // fallback: 현재 렌더 크기 사용
            svg.style.maxWidth = 'none';
            const sb = svg.getBoundingClientRect();
            builder._pzW = Math.round(sb.width)  || Math.round(pb.width  * 0.9);
            builder._pzH = Math.round(sb.height) || Math.round(pb.height * 0.9);
        }
        builder._pzZ = 1.0;
        builder._pzX = (pb.width  - builder._pzW) / 2;
        builder._pzY = (pb.height - builder._pzH) / 2;
        svg.style.maxWidth = 'none';
        svg.style.width    = builder._pzW + 'px';
        svg.style.height   = builder._pzH + 'px';
    }

    svg.style.transformOrigin = '0 0';
    svg.style.transform = `translate(${builder._pzX}px,${builder._pzY}px) scale(${builder._pzZ})`;

    let ind = preview.querySelector('.dlv-dgr-zoom-ind');
    if (!ind) { ind = document.createElement('span'); ind.className = 'dlv-dgr-zoom-ind'; preview.appendChild(ind); }
    ind.textContent = Math.round(builder._pzZ * 100) + '%';
    ind.style.opacity = '1';
    clearTimeout(builder._dgrZoomFade);
    builder._dgrZoomFade = setTimeout(() => { if (ind) ind.style.opacity = '0'; }, 1500);
}

function _dgrInitScrollZoom(builder) {
    if (builder._dgrZoomInited) return;
    builder._dgrZoomInited = true;
    const preview = builder.querySelector('.dlv-dgr-preview');
    if (!preview) return;
    const pvWrap = builder.querySelector('.dlv-dgr-pv-wrap') ?? preview;

    // 휠: 커서 위치 기준 확대/축소
    pvWrap.addEventListener('wheel', (e) => {
        if (!preview.querySelector('svg') || !builder._pzW) return;
        e.preventDefault();
        const rect  = preview.getBoundingClientRect();
        const cx    = e.clientX - rect.left;
        const cy    = e.clientY - rect.top;
        const delta = e.deltaY !== undefined ? e.deltaY : (e.detail * 40);
        const ratio = delta < 0 ? 1.15 : 1 / 1.15;
        const newZ  = Math.max(0.1, Math.min(8, builder._pzZ * ratio));
        // 커서 아래 지점이 고정되도록 pan 재계산
        builder._pzX = cx - (cx - builder._pzX) * (newZ / builder._pzZ);
        builder._pzY = cy - (cy - builder._pzY) * (newZ / builder._pzZ);
        builder._pzZ = newZ;
        _dgrApplyZoom(builder);
    }, { passive: false });

    // 더블클릭: 초기 상태로 리셋
    preview.addEventListener('dblclick', () => {
        if (!preview.querySelector('svg')) return;
        builder._pzW = 0; builder._pzH = 0;
        _dgrApplyZoom(builder);
    });

    // 드래그: 상하좌우 팬
    pvWrap.addEventListener('mousedown', (e) => {
        if (e.button !== 0 || !preview.querySelector('svg') || !builder._pzW) return;
        if (e.target.closest('.dlv-dgr-open-lb-btn')) return;
        _dgrDragState = { builder, preview, lastX: e.clientX, lastY: e.clientY };
        preview.style.cursor = 'grabbing';
        e.preventDefault();
    });
}

let _dgrDragState = null;
let _dgrLbDragState = null;
document.addEventListener('mousemove', (e) => {
    if (_dgrDragState) {
        const { builder, lastX, lastY } = _dgrDragState;
        builder._pzX += e.clientX - lastX;
        builder._pzY += e.clientY - lastY;
        _dgrDragState.lastX = e.clientX;
        _dgrDragState.lastY = e.clientY;
        _dgrApplyZoom(builder);
    }
    if (_dgrLbDragState) {
        _dgrLbPanX += e.clientX - _dgrLbDragState.lastX;
        _dgrLbPanY += e.clientY - _dgrLbDragState.lastY;
        _dgrLbDragState.lastX = e.clientX;
        _dgrLbDragState.lastY = e.clientY;
        _dgrApplyLbZoom();
    }
});
document.addEventListener('mouseup', () => {
    if (_dgrDragState) {
        _dgrDragState.preview.style.cursor = '';
        _dgrDragState = null;
    }
    if (_dgrLbDragState) {
        const lb = document.getElementById('dlv-dgr-lb-body');
        if (lb) lb.style.cursor = 'grab';
        _dgrLbDragState = null;
    }
});

function _dgrApplyLbZoom() {
    const el = document.getElementById('dlv-dgr-lb-svg');
    if (!el) return;
    el.style.transform = `translate(${_dgrLbPanX}px,${_dgrLbPanY}px) scale(${_dgrLbZoom})`;
    const zEl = document.getElementById('dlv-dgr-lb-zoom');
    if (zEl) zEl.textContent = Math.round(_dgrLbZoom * 100) + '%';
}

function _dgrLbWheelZoom(e) {
    const lb = document.getElementById('dlv-dgr-lb');
    if (!lb || lb.style.display === 'none') return;
    e.preventDefault();
    const body  = document.getElementById('dlv-dgr-lb-body');
    const rect  = body.getBoundingClientRect();
    const cx    = e.clientX - rect.left;
    const cy    = e.clientY - rect.top;
    const delta = e.deltaY !== undefined ? e.deltaY : (e.detail * 40);
    const ratio = delta < 0 ? 1.15 : 1 / 1.15;
    const newZ  = Math.max(0.1, Math.min(10, _dgrLbZoom * ratio));
    _dgrLbPanX  = cx - (cx - _dgrLbPanX) * (newZ / _dgrLbZoom);
    _dgrLbPanY  = cy - (cy - _dgrLbPanY) * (newZ / _dgrLbZoom);
    _dgrLbZoom  = newZ;
    _dgrApplyLbZoom();
}

function _dgrCreateLightbox() {
    if (document.getElementById('dlv-dgr-lb')) return;
    const m = document.createElement('div');
    m.id = 'dlv-dgr-lb';
    m.style.cssText = 'display:none;position:fixed;inset:0;z-index:10500;background:rgba(8,10,22,.88);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:20px;';
    m.innerHTML = `
        <div style="background:#13152a;border:1px solid rgba(255,255,255,.08);border-radius:16px;padding:20px 24px 0;width:min(92vw,1400px);height:90vh;display:flex;flex-direction:column;box-shadow:0 32px 100px rgba(0,0,0,.65);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-shrink:0;padding-bottom:14px;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:9.5px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:#7c6eff;background:rgba(124,110,255,.12);padding:2px 8px;border-radius:4px;">DIAGRAM</span>
                    <span id="dlv-dgr-lb-title" style="font-size:13px;font-weight:700;color:#e2e8f0;"></span>
                    <span id="dlv-dgr-lb-zoom" style="font-size:10.5px;font-weight:700;color:#a78bfa;background:rgba(124,110,255,.12);border-radius:4px;padding:1px 8px;min-width:42px;text-align:center;display:inline-block;">100%</span>
                    <span style="font-size:10px;color:#4b5563;">휠: 확대/축소 · 드래그: 이동 · 더블클릭: 초기화</span>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-shrink:0;">
                    <button id="dlv-dgr-lb-dl" onclick="dgrLbDownload()"
                        style="display:inline-flex;align-items:center;gap:5px;padding:7px 15px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        PNG
                    </button>
                    <button onclick="dgrCloseLb()" style="width:34px;height:34px;display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.06);color:#9ca3af;border:1px solid rgba(255,255,255,.1);border-radius:8px;font-size:20px;line-height:1;cursor:pointer;">&times;</button>
                </div>
            </div>
            <div id="dlv-dgr-lb-body" style="flex:1;min-height:0;overflow:hidden;position:relative;background:#ffffff;border-radius:10px 10px 0 0;cursor:grab;"></div>
        </div>`;
    m.addEventListener('click', e => { if (e.target === m) dgrCloseLb(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') dgrCloseLb(); });
    const lbBody = m.querySelector('#dlv-dgr-lb-body');
    lbBody.addEventListener('wheel', _dgrLbWheelZoom, { passive: false });
    lbBody.addEventListener('dblclick', () => {
        const bW = lbBody.clientWidth, bH = lbBody.clientHeight;
        const fitW = _dgrLbNW > 0 ? bW / _dgrLbNW : 1;
        const fitH = _dgrLbNH > 0 ? bH / _dgrLbNH : 1;
        _dgrLbZoom = Math.max(0.1, Math.min(1.0, fitW, fitH));
        _dgrLbPanX = (bW - _dgrLbNW * _dgrLbZoom) / 2;
        _dgrLbPanY = (bH - _dgrLbNH * _dgrLbZoom) / 2;
        _dgrApplyLbZoom();
    });
    lbBody.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        _dgrLbDragState = { lastX: e.clientX, lastY: e.clientY };
        lbBody.style.cursor = 'grabbing';
        e.preventDefault();
    });
    document.body.appendChild(m);
}

function dgrOpenLb(previewEl) {
    _dgrCreateLightbox();
    const svgEl = previewEl.querySelector('svg');
    if (!svgEl) return;
    _dgrLbSvg = svgEl;
    const builder = previewEl.closest('.dlv-dgr-builder');
    document.getElementById('dlv-dgr-lb-title').textContent = builder?.dataset.toolId ?? '다이어그램';

    const sz = _dgrGetSvgNaturalSize(svgEl);
    _dgrLbNW = sz.w; _dgrLbNH = sz.h; _dgrLbZoom = 1.0;

    // pan-zoom 인라인 스타일을 제거한 클린 클론을 SVG 문자열로 직렬화 → <img> 표시
    // → ID 충돌·CSS 충돌·XML 파싱 오류를 근본적으로 회피
    const clean = svgEl.cloneNode(true);
    clean.style.cssText = '';
    clean.removeAttribute('width');
    clean.removeAttribute('height');
    const svgStr = clean.outerHTML;
    const dataUrl = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgStr);

    const img = document.createElement('img');
    img.id                    = 'dlv-dgr-lb-svg';
    img.src                   = dataUrl;
    img.style.position        = 'absolute';
    img.style.left            = '0';
    img.style.top             = '0';
    img.style.transformOrigin = '0 0';
    img.style.display         = 'block';
    img.style.width           = _dgrLbNW + 'px';
    img.style.height          = _dgrLbNH + 'px';

    const body = document.getElementById('dlv-dgr-lb-body');
    body.innerHTML = '';
    body.appendChild(img);
    document.getElementById('dlv-dgr-lb').style.display = 'flex';
    document.body.style.overflow = 'hidden';

    requestAnimationFrame(() => {
        const bW = body.clientWidth;
        const bH = body.clientHeight;
        const fitW = _dgrLbNW > 0 ? bW / _dgrLbNW : 1;
        const fitH = _dgrLbNH > 0 ? bH / _dgrLbNH : 1;
        _dgrLbZoom = Math.max(0.1, Math.min(1.0, fitW, fitH));
        _dgrLbPanX = (bW - _dgrLbNW * _dgrLbZoom) / 2;
        _dgrLbPanY = (bH - _dgrLbNH * _dgrLbZoom) / 2;
        _dgrApplyLbZoom();
    });
}

function dgrCloseLb() {
    const m = document.getElementById('dlv-dgr-lb');
    if (m) m.style.display = 'none';
    document.body.style.overflow = '';
    _dgrLbSvg = null;
}

async function dgrLbDownload() {
    if (!_dgrLbSvg) return;
    const title = document.getElementById('dlv-dgr-lb-title')?.textContent ?? 'diagram';
    await _dgrSaveAsPng(_dgrLbSvg, title);
}

async function dgrSave(btn) {
    const b      = dgrGetBuilder(btn);
    const toolId = b.dataset.toolId;
    const stepNo = parseInt(b.dataset.step);
    const code   = b.querySelector('.dlv-dgr-textarea').value.trim();
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '저장 중…';
    try {
        const png    = await dgrSvgToPng(b);
        const result = { mermaid: code };
        if (png) result.png = png;

        const res  = await fetch(SAVE_TOOL_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ step: stepNo, tool_id: toolId, result }),
        });
        const json = await res.json();
        if (json.ok) {
            btn.innerHTML = '✓ 저장됨';
            setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 1500);
        } else {
            alert(json.message || '저장 실패');
            btn.innerHTML = origHtml; btn.disabled = false;
        }
    } catch (e) {
        alert('저장 중 오류: ' + e.message);
        btn.innerHTML = origHtml; btn.disabled = false;
    }
}

async function dgrAiGen(btn) {
    const b = dgrGetBuilder(btn);
    btn.disabled = true;
    const origHtml = btn.innerHTML;
    btn.innerHTML = '웍스 생성 중…';
    try {
        const res = await fetch(ANALYZE_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify({ action: 'diagram', step: parseInt(b.dataset.step), tool_id: b.dataset.toolId, fields: getFormData().fields }),
        });
        const json = await res.json();
        const code = json.mermaid || json.result?.mermaid;
        if (code) {
            b.querySelector('.dlv-dgr-textarea').value = code;
            dgrRender(btn);
        } else if (json.message) {
            alert(json.message);
        }
    } catch (e) {
        alert('웍스 생성 중 오류: ' + e.message);
    } finally {
        btn.innerHTML = origHtml; btn.disabled = false;
    }
}

/* ── 빌더 초기화 (DOMContentLoaded) ── */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dlv-tbl-builder').forEach(b => tblInit(b));
    document.querySelectorAll('.dlv-dgr-builder').forEach(b => _dgrInitScrollZoom(b));
    document.querySelectorAll('.dlv-qa-builder').forEach(b => qaInit(b));
    if (typeof marked !== 'undefined') mdInitPreviews();
});
</script>
@endpush

@push('scripts')
<script type="module">
import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs';
mermaid.initialize({ startOnLoad: false, theme: 'default', securityLevel: 'loose' });
window.mermaid = mermaid;
// 초기 렌더
document.querySelectorAll('.dlv-dgr-builder').forEach(b => {
    if (typeof window._dgrInitScrollZoom === 'function') window._dgrInitScrollZoom(b);
    const preview = b.querySelector('.dlv-dgr-preview');
    const code = b.querySelector('.dlv-dgr-textarea').value.trim();
    if (code) {
        preview.textContent = code;
        preview.className = 'dlv-dgr-preview mermaid';
        mermaid.run({ nodes: [preview] }).then(() => {
            b._pzW = 0; b._pzH = 0;
            setTimeout(() => window._dgrApplyZoom && window._dgrApplyZoom(b), 60);
        }).catch(() => {});
    }
    // textarea 입력 시 자동 렌더
    b.querySelector('.dlv-dgr-textarea').addEventListener('input', function () {
        clearTimeout(this._dgrTimer);
        this._dgrTimer = setTimeout(() => {
            const pv = b.querySelector('.dlv-dgr-preview');
            const errEl = b.querySelector('.dlv-dgr-err');
            if (errEl) errEl.remove();
            pv.innerHTML = '';
            pv.removeAttribute('data-processed');
            pv.className = 'dlv-dgr-preview mermaid';
            pv.textContent = this.value.trim();
            mermaid.run({ nodes: [pv] }).then(() => {
                b._pzW = 0; b._pzH = 0;
                setTimeout(() => window._dgrApplyZoom && window._dgrApplyZoom(b), 60);
            }).catch(err => {
                pv.innerHTML = '';
                const d = document.createElement('div');
                d.className = 'dlv-dgr-err';
                d.textContent = '렌더링 오류: ' + err.message;
                pv.parentElement.appendChild(d);
            });
        }, 600);
    });
});
</script>
@endpush
