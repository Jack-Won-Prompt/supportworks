@extends('layouts.app')

@section('title', '웍스 프롬프트')

@push('styles')
<style>
    /* ── 페이지 전용 main 오버라이드 (풀스크린 챗 UX) ─────── */
    main:has(> .wp-app) {
        padding: 0 !important;
        background: #faf9f7 !important;
        overflow: hidden !important;
        display: flex !important; flex-direction: column !important;
    }
    main:has(> .wp-app) > nav { display: none; }

    /* ── 레이아웃 ───────────────────────────── */
    .wp-app {
        display:flex; flex-direction:column;
        flex: 1;
        min-height: 0;
        background: #faf9f7;
        position: relative;
    }
    .wp-header {
        flex-shrink:0;
        display:flex; align-items:center; gap:12px;
        padding: 10px 20px;
        background: #fff;
        border-bottom: 1px solid #ece9e3;
    }
    .wp-thread {
        flex:1; min-height:0; overflow-y:auto;
        padding: 20px 0 12px;
        scroll-behavior: smooth;
    }
    .wp-thread-inner {
        max-width: 760px; margin: 0 auto; padding: 0 20px;
    }
    .wp-footer {
        flex-shrink:0;
        padding: 12px 20px 16px;
        background: #faf9f7;
        border-top: 1px solid #ece9e3;
    }
    .wp-input-wrap {
        max-width: 820px; margin: 0 auto;
        display: grid;
        grid-template-columns: auto 1fr auto;
        align-items: end;
        gap: 10px;
        background: #fff;
        border: 1px solid #e7e4dc;
        border-radius: 20px;
        padding: 10px 14px 10px 12px;
        box-shadow: 0 2px 12px rgba(20,18,12,.04), 0 0 0 1px rgba(255,255,255,.5) inset;
        transition: box-shadow .15s ease, border-color .15s ease, transform .15s ease;
    }
    .wp-input-wrap:hover {
        border-color: #d6d3cd;
    }
    .wp-input-wrap:focus-within {
        border-color: #a78bfa;
        box-shadow: 0 4px 20px rgba(124,58,237,.10), 0 0 0 3px rgba(167,139,250,.12);
    }

    /* 프로젝트 셀렉터 (좌측 칩) */
    .wp-project-pill {
        position: relative;
        display: flex; align-items: center;
        flex-shrink: 0;
    }
    .wp-project-pill::before {
        content: '';
        position: absolute; left: 0; top: 50%;
        transform: translateY(-50%);
        width: 14px; height: 14px;
        background: url("data:image/svg+xml,%3Csvg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z'/%3E%3C/svg%3E") no-repeat center;
        pointer-events: none;
        margin-left: 10px;
    }
    .wp-project-select {
        width: 200px; height: 34px;
        font-size: 12.5px; font-weight: 500;
        color: #3f3f46;
        border: 1px solid transparent;
        border-radius: 12px;
        padding: 0 26px 0 32px;
        background-color: #f7f5f0;
        background-image: url("data:image/svg+xml,%3Csvg width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%2371717a' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        cursor: pointer; outline: none;
        appearance: none; -webkit-appearance: none;
        text-overflow: ellipsis; overflow: hidden; white-space: nowrap;
        transition: background-color .12s ease, border-color .12s ease;
    }
    .wp-project-select:hover { background-color: #efece4; }
    .wp-project-select:focus { border-color: #a78bfa; background-color: #fff; }

    /* 입력 텍스트박스 */
    .wp-input-textarea {
        width: 100%; min-width: 0;
        border: none; outline: none; resize: none;
        background: transparent;
        font-size: 15px; line-height: 1.6;
        color: #1e1b2e;
        font-family: inherit;
        min-height: 28px; max-height: 200px;
        padding: 4px 0 4px;
    }
    .wp-input-textarea::placeholder { color: #b8b3a8; }

    /* 전송 버튼 */
    .wp-send-btn {
        flex-shrink: 0;
        background: linear-gradient(135deg, #8b5cf6, #7c3aed);
        color: #fff; border: none;
        border-radius: 12px;
        padding: 0;
        cursor: pointer;
        transition: transform .12s ease, box-shadow .12s ease, background .12s ease, opacity .12s ease;
        display: flex; align-items: center; justify-content: center;
        width: 34px; height: 34px;
        box-shadow: 0 2px 6px rgba(124,58,237,.25);
    }
    .wp-send-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(124,58,237,.35);
    }
    .wp-send-btn:active:not(:disabled) { transform: translateY(0); }
    .wp-send-btn:disabled {
        background: #e7e4dc;
        box-shadow: none;
        cursor: not-allowed;
        color: #c4c1b8;
    }

    /* 입력창 아래 메타 */
    .wp-input-meta {
        max-width: 820px; margin: 8px auto 0;
        display: flex; align-items: center; gap: 10px;
        padding: 0 8px;
        min-height: 18px;
    }
    .wp-inline-badge {
        font-size: 10.5px; padding: 2.5px 8px; border-radius: 999px;
        background: #ecfdf5; color: #047857; font-weight: 600;
        display: none; flex-shrink: 0;
        border: 1px solid #a7f3d0;
    }
    .wp-inline-badge.no-plan { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .wp-inline-badge.error   { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .wp-input-hint {
        flex: 1; font-size: 11px; color: #b8b3a8; text-align: left;
        letter-spacing: .01em;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .wp-key-hint {
        flex-shrink: 0;
        font-size: 11px; color: #a1a1aa;
        display: flex; align-items: center; gap: 4px;
    }
    .wp-key-hint kbd {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 16px; height: 18px;
        padding: 0 5px;
        border: 1px solid #dcd9d1;
        border-bottom-width: 2px;
        border-radius: 4px;
        background: #fff;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        font-size: 10.5px;
        color: #52525b;
        line-height: 1;
    }
    @media (max-width: 640px) {
        .wp-input-hint { display: none; }
    }

    @media (max-width: 640px) {
        .wp-project-select { width: 140px; padding-left: 30px; }
        .wp-input-textarea { font-size: 14px; }
        .wp-input-wrap { padding: 8px 10px 8px 10px; }
    }

    /* ── 메시지 ───────────────────────────── */
    .wp-msg { margin-bottom: 22px; animation: wpFadeIn .25s ease; }
    @keyframes wpFadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }
    .wp-msg-user { display:flex; justify-content:flex-end; }
    .wp-msg-user-bubble {
        background:#7c3aed; color:#fff;
        padding:11px 16px; border-radius:18px 18px 4px 18px;
        max-width: 80%;
        font-size:14.5px; line-height:1.55;
        white-space:pre-wrap; word-break:break-word;
    }
    .wp-msg-ai { display:flex; gap:12px; align-items:flex-start; }
    .wp-avatar {
        flex-shrink:0; width:30px; height:30px; border-radius:50%;
        background:linear-gradient(135deg,#fcd34d,#f59e0b);
        display:flex; align-items:center; justify-content:center;
        color:#fff; font-size:13px; font-weight:700;
    }
    .wp-msg-ai-body { flex:1; min-width:0; }
    .wp-msg-ai-content {
        font-size:14.5px; line-height:1.6; color:#1f1f1f;
    }
    .wp-msg-ai-content p { margin:0 0 10px; }
    .wp-msg-ai-content p:last-child { margin-bottom:0; }
    .wp-msg-ai-content ul,
    .wp-msg-ai-content ol { margin:0 0 10px; padding-left:22px; }
    .wp-msg-ai-content li { margin-bottom:3px; }
    .wp-inline-code {
        background:#f3f0eb; padding:1.5px 5px; border-radius:4px;
        font-size:12.8px; color:#7c3aed;
        font-family: 'JetBrains Mono', ui-monospace, 'SF Mono', Menlo, monospace;
    }
    .wp-code-block {
        background:#1e1b2e; color:#e4e4e7; border-radius:10px;
        padding:14px 16px; margin:8px 0;
        overflow-x:auto; font-size:12.8px; line-height:1.55;
        font-family: 'JetBrains Mono', ui-monospace, 'SF Mono', Menlo, monospace;
    }
    .wp-code-block code { background:none; color:inherit; padding:0; }

    /* GFM 표 */
    .wp-md-table {
        border-collapse: collapse;
        margin: 10px 0;
        font-size: 13px;
        width: 100%;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 0 1px #ece9e3;
    }
    .wp-md-table th,
    .wp-md-table td {
        padding: 8px 12px;
        text-align: left;
        border-bottom: 1px solid #ece9e3;
        vertical-align: top;
        line-height: 1.5;
    }
    .wp-md-table th {
        background: #f7f5f0;
        font-weight: 600;
        color: #1e1b2e;
        font-size: 12.5px;
        letter-spacing: .01em;
    }
    .wp-md-table tbody tr:last-child td { border-bottom: none; }
    .wp-md-table tbody tr:hover td { background: #faf9f5; }
    .wp-md-table code {
        background: #f3f0eb; padding: 1px 5px; border-radius: 4px;
        font-size: 12px; color: #7c3aed;
    }
    .wp-msg-meta {
        margin-top:6px; display:flex; gap:8px; align-items:center;
        font-size:11px; color:#a1a1aa;
    }
    .wp-meta-btn {
        background:transparent; border:none; padding:3px 7px;
        border-radius:5px; cursor:pointer; color:#71717a; font-size:11.5px;
        display:inline-flex; align-items:center; gap:4px;
        transition: background .12s;
    }
    .wp-meta-btn:hover { background:#ece9e3; color:#3f3f46; }
    .wp-mode-tag {
        font-size:10.5px; padding:1px 7px; border-radius:4px;
        font-weight:600; letter-spacing:.02em;
    }
    .wp-mode-tag.project { background:#dcfce7; color:#15803d; }
    .wp-mode-tag.general { background:#fef3c7; color:#a16207; }
    .wp-details {
        margin-top:10px; padding:10px 12px; background:#f5f3ee;
        border-left: 3px solid #d6d3cd; border-radius:6px;
        font-size:12.5px; color:#52525b;
    }
    .wp-details summary { cursor:pointer; font-weight:600; color:#7c3aed; }
    .wp-details ul { margin:8px 0 0; padding-left:20px; }
    .wp-thinking {
        display:flex; gap:5px; align-items:center; color:#a1a1aa;
        font-size:13px; padding:4px 0;
    }
    .wp-thinking-dot {
        width:6px; height:6px; border-radius:50%;
        background:#a78bfa; animation: wpBounce 1.2s infinite ease-in-out;
    }
    .wp-thinking-dot:nth-child(2) { animation-delay: .15s; }
    .wp-thinking-dot:nth-child(3) { animation-delay: .3s; }
    @keyframes wpBounce {
        0%,80%,100% { transform: scale(.7); opacity:.5; }
        40% { transform: scale(1); opacity:1; }
    }

    /* ── 헤더 ───────────────────────────── */
    .wp-h-title {
        font-size:14.5px; font-weight:700; color:#1e1b2e; flex-shrink:0;
        display:flex; align-items:center; gap:6px;
    }
    .wp-h-spacer { flex:1; }
    .wp-h-select {
        font-size:12.5px; border:1px solid #e7e4dc; border-radius:8px;
        padding:6px 10px; color:#3f3f46; background:#fff; cursor:pointer;
        outline:none; min-width: 200px;
    }
    .wp-h-btn {
        background:#fff; border:1px solid #e7e4dc; border-radius:8px;
        padding:6px 12px; font-size:12.5px; color:#3f3f46; cursor:pointer;
        display:inline-flex; align-items:center; gap:4px;
        transition: background .12s;
    }
    .wp-h-btn:hover { background:#f5f3ee; }
    .wp-h-btn.primary { background:#7c3aed; color:#fff; border-color:#7c3aed; }
    .wp-h-btn.primary:hover { background:#6d28d9; }

    /* ── 빈 화면 ───────────────────────────── */
    .wp-welcome {
        max-width:560px; margin: 40px auto; text-align:center;
        padding: 20px;
    }
    .wp-welcome-icon {
        width:56px; height:56px; border-radius:14px;
        background:linear-gradient(135deg,#fcd34d,#f59e0b);
        display:inline-flex; align-items:center; justify-content:center;
        margin-bottom:18px;
    }
    .wp-welcome h2 { font-size:22px; font-weight:700; color:#1e1b2e; margin:0 0 8px; }
    .wp-welcome p { font-size:14px; color:#71717a; margin:0 0 22px; line-height:1.6; }
    .wp-suggestion {
        display:inline-block; padding:9px 14px;
        border:1px solid #e7e4dc; border-radius:10px;
        font-size:13px; color:#3f3f46; background:#fff;
        cursor:pointer; margin: 4px;
        transition: all .12s;
    }
    .wp-suggestion:hover { background:#f5f3ee; border-color:#a78bfa; }

    /* ── 드로어 ───────────────────────────── */
    .wp-drawer {
        position:fixed; top:0; right:0; height:100vh; width:380px;
        background:#fff; box-shadow: -8px 0 24px rgba(0,0,0,.08);
        transform: translateX(100%); transition: transform .25s ease;
        z-index: 70; display:flex; flex-direction:column;
    }
    .wp-drawer.open { transform: translateX(0); }
    .wp-drawer-backdrop {
        position:fixed; inset:0; background:rgba(0,0,0,.18);
        opacity:0; pointer-events:none; transition: opacity .25s ease;
        z-index: 69;
    }
    .wp-drawer-backdrop.open { opacity:1; pointer-events:auto; }
    .wp-drawer-h {
        padding:14px 18px; border-bottom:1px solid #ece9e3;
        display:flex; align-items:center; justify-content:space-between;
    }
    .wp-drawer-body { flex:1; overflow-y:auto; padding: 14px 18px; }

    .wp-error {
        background:#fef2f2; border:1px solid #fecaca;
        border-radius:9px; padding:10px 14px; color:#dc2626;
        font-size:13px; margin: 0 auto 12px; max-width:760px;
    }
</style>
@endpush

@section('content')
<div class="wp-app">

    {{-- 헤더 --}}
    <div class="wp-header">
        <span class="wp-h-title">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
            </svg>
            웍스 프롬프트
        </span>

        <span class="wp-h-spacer"></span>

        <button class="wp-h-btn" onclick="worksPrompt.newChat()" title="새 대화 시작">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            새 대화
        </button>
        <button class="wp-h-btn" onclick="worksPrompt.toggleDrawer()" title="이전 대화">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            이력
        </button>
    </div>

    {{-- 스레드 --}}
    <div class="wp-thread" id="thread">
        <div class="wp-thread-inner">
            <div id="error-msg" class="wp-error" style="display:none;"></div>

            <div id="welcome-area" class="wp-welcome">
                <div class="wp-welcome-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                    </svg>
                </div>
                <h2>무엇을 도와드릴까요?</h2>
                <p>프로젝트를 선택하면 해당 기획서와 이전 대화 이력을 컨텍스트로 답변합니다.<br>선택하지 않으면 일반 Q&amp;A로 답합니다.</p>
            </div>

            <div id="messages-area"></div>
        </div>
    </div>

    {{-- 입력 --}}
    <div class="wp-footer">
        <div class="wp-input-wrap">
            <div class="wp-project-pill">
                <select id="project-select" class="wp-project-select" onchange="worksPrompt.onProjectChange()" title="프로젝트 선택 (선택 사항)">
                    <option value="">프로젝트 선택 안 함</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>

            <textarea
                id="user-input"
                class="wp-input-textarea"
                rows="1"
                maxlength="5000"
                placeholder="웍스에게 질문하기..."
                oninput="worksPrompt.onInputChange()"
                onkeydown="worksPrompt.onInputKeydown(event)"></textarea>

            <button id="send-btn" class="wp-send-btn" onclick="worksPrompt.submit()" title="전송 (Enter)">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="19" x2="12" y2="5"/>
                    <polyline points="5 12 12 5 19 12"/>
                </svg>
            </button>
        </div>
        <div class="wp-input-meta">
            <span id="plan-badge" class="wp-inline-badge"></span>
            <span class="wp-input-hint">프로젝트 선택 시 기획서 · 이전 대화 · 이슈 · 일정 · 멤버 등을 컨텍스트로 답합니다.</span>
            <span class="wp-key-hint" title="Enter로 전송, Shift+Enter로 줄바꿈">
                <kbd>↵</kbd> 전송 · <kbd>⇧</kbd>+<kbd>↵</kbd> 줄바꿈
            </span>
        </div>
    </div>

</div>

{{-- 이력 드로어 --}}
<div id="drawer-backdrop" class="wp-drawer-backdrop" onclick="worksPrompt.toggleDrawer()"></div>
<aside id="history-drawer" class="wp-drawer">
    <div class="wp-drawer-h">
        <strong style="font-size:14px;color:#1e1b2e;">이전 대화 이력</strong>
        <button onclick="worksPrompt.toggleDrawer()" style="background:none;border:none;cursor:pointer;color:#71717a;font-size:18px;">×</button>
    </div>
    <div class="wp-drawer-body">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <select id="history-mode-filter" onchange="worksPrompt.loadHistory(true)"
                style="font-size:12px;border:1px solid #e7e4dc;border-radius:7px;padding:4px 10px;color:#3f3f46;background:#fff;cursor:pointer;outline:none;">
                <option value="all">전체</option>
                <option value="project">프로젝트</option>
                <option value="general">일반</option>
            </select>
        </div>
        <div id="history-list" style="display:flex;flex-direction:column;gap:6px;"></div>
        <div id="history-empty" style="display:none;padding:24px;text-align:center;color:#a1a1aa;font-size:13px;">아직 이력이 없습니다.</div>
        <div id="history-loading" style="display:none;padding:16px;text-align:center;">
            <div style="display:inline-block;width:18px;height:18px;border:2px solid #ddd6fe;border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite;"></div>
        </div>
        <div id="history-more-btn" style="display:none;margin-top:10px;text-align:center;">
            <button onclick="worksPrompt.loadHistory(false)" class="wp-h-btn">더 보기</button>
        </div>
    </div>
</aside>

{{-- 이력 상세 모달 (재사용) --}}
<div id="history-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.4);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:22px;max-width:720px;width:92vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 16px 48px rgba(0,0,0,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <h4 style="font-size:15px;font-weight:700;color:#1e1b2e;margin:0;">이력 상세</h4>
            <button onclick="worksPrompt.closeHistoryModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;line-height:1;padding:4px;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;">
            <div id="modal-meta" style="font-size:12px;color:#a1a1aa;margin-bottom:12px;"></div>
            <p style="font-size:12px;font-weight:600;color:#7c3aed;margin:0 0 6px;">질문</p>
            <p id="modal-input" style="font-size:13px;color:#3f3f46;background:#faf8ff;border-radius:8px;padding:10px 12px;margin:0 0 14px;white-space:pre-wrap;"></p>
            <p id="modal-prompt-label" style="font-size:12px;font-weight:600;color:#7c3aed;margin:0 0 6px;">답변</p>
            <div id="modal-prompt" style="font-size:13px;color:#1e1b2e;background:#faf8ff;border:1px solid #ede9fe;border-radius:8px;padding:12px;white-space:pre-wrap;word-break:break-word;margin:0;max-height:380px;overflow-y:auto;line-height:1.6;"></div>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;padding-top:12px;border-top:1px solid #f0eeff;">
            <button onclick="worksPrompt.deleteHistory()" class="wp-h-btn" style="border-color:#fecaca;color:#dc2626;background:#fff5f5;">삭제</button>
            <button id="modal-copy-btn" onclick="worksPrompt.copyModalPrompt()" class="wp-h-btn primary">복사</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    window.worksPromptRoutes = {
        refine:           '{{ route("works-prompt.refine") }}',
        history:          '{{ route("works-prompt.history") }}',
        historyShow:      '{{ url("/works-prompt/history") }}',
        projectPlanBase:  '{{ url("/works-prompt/projects") }}',
        csrfToken:        '{{ csrf_token() }}',
    };
</script>
<script src="{{ asset('js/works-prompt.js') }}?v={{ filemtime(public_path('js/works-prompt.js')) }}"></script>
@endpush
