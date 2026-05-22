@extends('layouts.app')
@section('title', __('messages.messages'))

@section('header-actions')@endsection

@section('content')
<style>
/* ── 워크스페이스 쉘 (상단바 + msg-wrap 을 감싸는 외곽 컨테이너) */
#ws-shell {
    display: flex;
    flex-direction: column;
    margin: -20px -24px -24px;
    height: calc(100vh - 52px);
    background: var(--tBg);
    border-top: 1px solid var(--t100);
    overflow: hidden;
    box-sizing: border-box;
}

/* ── 워크스페이스 상단바 */
#ws-topbar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 7px 14px;
    background: #fff;
    border-bottom: 1px solid var(--t100);
    min-height: 44px;
}
.ws-tb-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.ws-tb-sel {
    height: 30px;
    padding: 0 28px 0 10px;
    border: 1.5px solid var(--t100);
    border-radius: 8px;
    background: #fff;
    color: #374151;
    font-size: 12.5px;
    font-weight: 500;
    cursor: pointer;
    outline: none;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='3'><path stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/></svg>");
    background-repeat: no-repeat;
    background-position: right 9px center;
    background-size: 9px;
    max-width: 220px;
    text-overflow: ellipsis;
}
.ws-tb-sel:focus { border-color: var(--t400); }
.ws-tb-sel:disabled { background-color: #f9fafb; color: #9ca3af; cursor: not-allowed; }
.ws-tb-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 30px;
    padding: 0 11px;
    border: 1.5px solid var(--t200);
    border-radius: 8px;
    background: #fff;
    color: var(--t500);
    font-size: 12.5px;
    font-weight: 600;
    cursor: pointer;
    transition: background .12s, border-color .12s, color .12s;
    white-space: nowrap;
}
.ws-tb-btn:hover { background: var(--t50); border-color: var(--t400); }
.ws-tb-btn.primary { background: linear-gradient(135deg, var(--t300), var(--t500)); color: #fff; border-color: transparent; }
.ws-tb-btn.primary:hover { filter: brightness(1.05); }
.ws-tb-btn.icon-only { padding: 0; width: 30px; justify-content: center; color: #6b7280; }
.ws-tb-btn[disabled] { opacity: .45; cursor: not-allowed; }
.ws-tb-btn[disabled]:hover { background: #fff; border-color: var(--t200); }
.ws-tb-sep { width: 1px; height: 22px; background: var(--t100); margin: 0 2px; }
.ws-tb-status { font-size: 11.5px; color: #9ca3af; padding: 0 6px; white-space: nowrap; }
.ws-tb-status.is-open { color: var(--t500); font-weight: 600; }

/* ── 플로팅 팝업 (메뉴 화면을 띄우는 떠있는 창) */
#ws-popup {
    display: none;
    position: fixed;
    z-index: 9500;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 14px 44px rgba(0,0,0,.22), 0 2px 10px rgba(0,0,0,.08);
    overflow: hidden;
    min-width: 320px;
    min-height: 120px;
    flex-direction: column;
    border: 1px solid var(--t100);
}
#ws-popup.is-open { display: flex; }
#ws-popup.is-dragging { transition: none; user-select: none; }
#ws-popup.is-resizing { transition: none; user-select: none; }
#ws-popup.is-minimized #ws-popup-body { display: none; }
#ws-popup.is-minimized { height: auto !important; min-height: 0; resize: none; }

#ws-popup-titlebar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px 7px 13px;
    background: linear-gradient(180deg, #fafafa, #f3f4f6);
    border-bottom: 1px solid var(--t100);
    cursor: move;
    user-select: none;
    flex-shrink: 0;
    min-height: 36px;
}
#ws-popup-title {
    flex: 1;
    font-size: 12.5px;
    font-weight: 600;
    color: #374151;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 6px;
}
#ws-popup-title svg { flex-shrink: 0; color: var(--t500); }
.ws-tt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px; height: 26px;
    border: none;
    background: transparent;
    border-radius: 6px;
    cursor: pointer;
    color: #6b7280;
    transition: background .12s, color .12s;
    flex-shrink: 0;
}
.ws-tt-btn:hover { background: rgba(0,0,0,.06); color: #1f2937; }
.ws-tt-btn.close-btn:hover { background: #fee2e2; color: #dc2626; }

#ws-popup-body {
    flex: 1;
    position: relative;
    background: #f5f3ff;
    overflow: hidden;
}
#ws-popup-iframe {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
}
#ws-popup-loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 12px;
    pointer-events: none;
}

/* 리사이즈 핸들 */
.ws-rs {
    position: absolute;
    z-index: 5;
}
.ws-rs-r  { top: 36px; right: 0; bottom: 12px; width: 6px; cursor: ew-resize; }
.ws-rs-b  { left: 12px; right: 12px; bottom: 0; height: 6px; cursor: ns-resize; }
.ws-rs-l  { top: 36px; left: 0; bottom: 12px; width: 6px; cursor: ew-resize; }
.ws-rs-t  { left: 12px; right: 12px; top: 0; height: 6px; cursor: ns-resize; display:none; /* 타이틀바와 겹쳐 비활성 */ }
.ws-rs-br { right: 0; bottom: 0; width: 14px; height: 14px; cursor: nwse-resize; }
.ws-rs-bl { left: 0; bottom: 0; width: 14px; height: 14px; cursor: nesw-resize; }
.ws-rs-tr { top: 0; right: 0; width: 14px; height: 14px; cursor: nesw-resize; display:none; }
.ws-rs-tl { top: 0; left: 0; width: 14px; height: 14px; cursor: nwse-resize; display:none; }
.ws-rs-br::after {
    content: '';
    position: absolute; right: 3px; bottom: 3px;
    width: 8px; height: 8px;
    border-right: 2px solid #c4b5fd;
    border-bottom: 2px solid #c4b5fd;
    border-bottom-right-radius: 3px;
}

#msg-wrap {
    flex: 1;
    display: flex;
    gap: 10px;
    padding: 10px;
    min-height: 0;
    overflow: hidden;
    box-sizing: border-box;
}

/* Left */
#conv-list {
    width: 272px; min-width: 272px;
    display: flex; flex-direction: column;
    background: #fff;
    border-radius: 14px;
    border: 1px solid var(--t100);
    box-shadow: 0 1px 4px rgba(99,102,241,.06);
    overflow: hidden;
}
#conv-list-hdr { padding:14px 14px 10px; border-bottom:1px solid var(--t50); flex-shrink:0; }
#conv-search { width:100%; padding:7px 12px; border:1px solid var(--t100); border-radius:8px; font-size:12.5px; background:var(--tBg); outline:none; color:#3f3f46; }
#conv-search:focus { border-color:var(--t300); background:#fff; }
#conv-list-body { flex:1; overflow-y:auto; }

.conv-item-wrap { position:relative; }
.conv-item-wrap:hover .conv-leave-btn { display:flex; }
.conv-item { display:flex; align-items:center; gap:10px; padding:11px 14px; padding-right:36px; cursor:pointer; border-bottom:1px solid var(--tBg); transition:background .1s; text-decoration:none; }
.conv-item:hover { background:var(--t50); }
.conv-item.active { background:var(--t100); border-left:3px solid var(--t500); padding-left:11px; }
.conv-leave-btn { display:none; position:absolute; right:10px; top:50%; transform:translateY(-50%); width:22px; height:22px; align-items:center; justify-content:center; background:rgba(239,68,68,.1); color:#ef4444; border-radius:5px; font-size:15px; line-height:1; cursor:pointer; z-index:5; transition:background .12s; border:none; padding:0; flex-shrink:0; }
.conv-leave-btn:hover { background:rgba(239,68,68,.22); }
.conv-avatar { width:38px; height:38px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:700; color:#fff; flex-shrink:0; position:relative; }
.conv-avatar.group { border-radius:12px; }
.conv-name { font-size:13px; font-weight:600; color:#1e1b2e; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-preview { font-size:12px; color:#a1a1aa; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.conv-badge { background:#ef4444; color:#fff; font-size:10px; font-weight:700; border-radius:10px; padding:1px 6px; flex-shrink:0; min-width:18px; text-align:center; }
.conv-time { font-size:11px; color:#a1a1aa; flex-shrink:0; }
.group-tag { font-size:9.5px; font-weight:700; background:var(--t100); color:var(--tText); padding:1px 5px; border-radius:4px; flex-shrink:0; }

/* Right */
#chat-area {
    flex: 1; display: flex; flex-direction: column; min-width: 0;
    background: #fff;
    border-radius: 14px;
    border: 1px solid var(--t100);
    box-shadow: 0 1px 4px rgba(99,102,241,.06);
    overflow: hidden;
}
#chat-hdr { padding:13px 20px; border-bottom:1px solid var(--t100); display:flex; align-items:center; gap:10px; flex-shrink:0; background:#fff; border-radius:14px 14px 0 0; }
#chat-hdr-members { font-size:11.5px; color:#a1a1aa; margin-top:2px; line-height:1.5; word-break:keep-all; }
.group-tag:hover { background:var(--t200); color:var(--tText); }
.hdr-member-chip { background:none; border:none; padding:0; margin:0; font:inherit; color:inherit; cursor:pointer; border-radius:3px; transition:color .12s, background .12s; }
.hdr-member-chip:hover { color:var(--t500); background:var(--t100); padding:0 4px; }
.file-with-actions { display:inline-block; max-width:100%; }
.file-share-email-btn {
    display:inline-flex; align-items:center; gap:5px;
    margin-top:6px;
    background:var(--t100); border:1px solid var(--t200);
    color:var(--t500); font-size:12px; font-weight:700;
    cursor:pointer; padding:4px 10px; border-radius:7px;
    opacity:0; transform:translateY(-2px);
    transition:opacity .15s, background .15s, transform .15s, box-shadow .15s, color .15s;
    box-shadow:0 1px 3px rgba(124,58,237,.08);
}
.file-with-actions:hover .file-share-email-btn { opacity:1; transform:translateY(0); }
.file-share-email-btn:hover {
    background:var(--t500); color:#fff; border-color:var(--t500);
    box-shadow:0 2px 8px rgba(124,58,237,.35);
}
.file-share-email-btn[disabled] { opacity:.5 !important; cursor:wait; }
#chat-messages { flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:12px; background:var(--tBg); }
#chat-input-area { padding:13px 20px; border-top:1px solid var(--t100); background:#fff; flex-shrink:0; border-radius:0 0 14px 14px; }

.msg-row { display:flex; gap:10px; align-items:flex-end; }
.msg-row.mine { flex-direction:row-reverse; }
.msg-avatar { width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; color:#fff; flex-shrink:0; }
.msg-bubble { max-width:100%; padding:10px 14px; border-radius:16px; font-size:13.5px; line-height:1.6; word-break:break-word; }
.msg-bubble.theirs { background:#fff; color:#1e1b2e; border:1px solid var(--t100); border-bottom-left-radius:4px; box-shadow:0 1px 4px rgba(0,0,0,.04); }
.msg-bubble.mine { background:linear-gradient(135deg,var(--t300),var(--t500)); color:#fff; border-bottom-right-radius:4px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
.msg-time { font-size:10.5px; color:#a1a1aa; flex-shrink:0; padding-bottom:2px; white-space:nowrap; }
.msg-read { font-size:10px; color:var(--t400); flex-shrink:0; padding-bottom:2px; white-space:nowrap; font-weight:500; line-height:1.3; text-align:right; }
.msg-read.unread { color:#a1a1aa; }
.msg-name { font-size:11px; color:#a1a1aa; margin-bottom:3px; font-weight:500; }

.file-img { max-width:240px; max-height:200px; border-radius:10px; display:block; margin-top:6px; cursor:pointer; }
.file-card { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:8px; margin-top:6px; text-decoration:none; }
.file-card.theirs { background:var(--tBg); color:#1e1b2e; }
.file-card.mine { background:rgba(255,255,255,.18); color:#fff; }
.file-card:hover { opacity:.85; }

#input-box { display:flex; gap:8px; align-items:flex-end; }
#msg-textarea { flex:1; resize:none; border:1.5px solid var(--t100); border-radius:12px; padding:10px 14px; font-size:13.5px; outline:none; font-family:inherit; max-height:120px; overflow-y:auto; line-height:1.5; transition:border-color .15s; background:#fff; }
#msg-textarea:focus { border-color:var(--t500); }
#file-preview-bar { display:none; flex-wrap:wrap; gap:6px; padding:8px; background:var(--tBg); border-radius:8px; margin-bottom:8px; border:1px solid var(--t100); }
.file-chip { display:flex; align-items:center; gap:6px; padding:5px 7px; background:#fff; border:1px solid var(--t100); border-radius:7px; font-size:12px; color:#52525b; max-width:230px; }
.file-chip-thumb { width:28px; height:28px; object-fit:cover; border-radius:5px; flex-shrink:0; }
.file-chip-icon { flex-shrink:0; }
.file-chip-name { font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.file-chip-size { color:#9e97c0; font-size:11px; flex-shrink:0; }
.file-chip-x { background:none; border:none; cursor:pointer; color:#9e97c0; font-size:16px; line-height:1; padding:0 2px; flex-shrink:0; }
.file-chip-x:hover { color:#ef4444; }

/* 이모지 피커 */
#emoji-picker-wrap {
    display: none;
    position: fixed;
    z-index: 9999;
    width: 348px;
    background: #fff;
    border: 1px solid var(--t100);
    border-radius: 18px;
    box-shadow: 0 16px 48px rgba(109,92,231,.16), 0 4px 16px rgba(0,0,0,.07);
    overflow: hidden;
    transform-origin: bottom right;
    transform: scale(.9) translateY(8px);
    opacity: 0;
    transition: transform .18s cubic-bezier(.34,1.4,.64,1), opacity .15s ease;
    pointer-events: none;
}
#emoji-picker-wrap.ep-open {
    transform: scale(1) translateY(0);
    opacity: 1;
    pointer-events: auto;
}
/* 말풍선 꼬리 */
#emoji-picker-wrap::after {
    content: '';
    position: absolute;
    bottom: -6px;
    right: 18px;
    width: 12px;
    height: 12px;
    background: #fff;
    border-right: 1px solid var(--t100);
    border-bottom: 1px solid var(--t100);
    transform: rotate(45deg);
    box-shadow: 2px 2px 4px rgba(0,0,0,.04);
}
/* 헤더 */
#ep-header {
    padding: 13px 14px 10px;
    border-bottom: 1px solid var(--t50);
}
#ep-search-wrap {
    position: relative;
}
#ep-search-wrap svg {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #c4b5fd;
    pointer-events: none;
}
#emoji-search {
    width: 100%;
    padding: 8px 12px 8px 34px;
    border: 1.5px solid var(--t100);
    border-radius: 10px;
    font-size: 13px;
    color: #3f3f46;
    outline: none;
    box-sizing: border-box;
    background: var(--tBg);
    transition: border-color .15s, background .15s;
    font-family: inherit;
}
#emoji-search:focus { border-color: var(--t400); background: #fff; }
#emoji-search::placeholder { color: #c4b5fd; }
/* 카테고리 탭 */
#emoji-cats {
    display: flex;
    gap: 0;
    padding: 0 10px;
    border-bottom: 1px solid var(--t50);
    overflow-x: auto;
    scrollbar-width: none;
}
#emoji-cats::-webkit-scrollbar { display: none; }
.ep-cat-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 9px 8px 8px;
    border: none;
    background: none;
    cursor: pointer;
    color: #c4b5fd;
    flex-shrink: 0;
    position: relative;
    transition: color .15s;
}
.ep-cat-btn::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 20%;
    right: 20%;
    height: 2px;
    border-radius: 2px 2px 0 0;
    background: var(--t500);
    transform: scaleX(0);
    transition: transform .18s ease;
}
.ep-cat-btn:hover { color: var(--t400); }
.ep-cat-btn.active { color: var(--t500); }
.ep-cat-btn.active::after { transform: scaleX(1); }
/* 이모지 그리드 */
#emoji-grid-wrap {
    height: 228px;
    overflow-y: auto;
    padding: 6px 10px 10px;
    scrollbar-width: thin;
    scrollbar-color: var(--t100) transparent;
}
#emoji-grid-wrap::-webkit-scrollbar { width: 4px; }
#emoji-grid-wrap::-webkit-scrollbar-thumb { background: var(--t200); border-radius: 4px; }
.ep-section-label {
    font-size: 10.5px;
    font-weight: 700;
    color: #b8b0d8;
    letter-spacing: .06em;
    text-transform: uppercase;
    padding: 6px 2px 4px;
}
#emoji-grid {
    display: grid;
    grid-template-columns: repeat(8, 1fr);
    gap: 1px;
}
.emoji-btn {
    font-size: 22px;
    border: none;
    background: none;
    cursor: pointer;
    padding: 5px 3px;
    border-radius: 8px;
    line-height: 1.25;
    transition: background .1s, transform .1s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.emoji-btn:hover {
    background: var(--t50);
    transform: scale(1.25);
}
.emoji-btn:active { transform: scale(1.05); }
/* 최근 사용 */
#ep-recent { margin-bottom: 4px; }
#ep-recent:empty { display: none; }

#chat-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:#a1a1aa; gap:10px; background:var(--tBg); }

/* 번역 언어 선택기 */
#translate-lang-wrap { position:relative; flex-shrink:0; }
#translate-lang-btn { display:flex;align-items:center;gap:4px;height:38px;padding:0 9px;border:1.5px solid var(--t100);border-radius:10px;cursor:pointer;color:#a1a1aa;background:#fff;font-size:11px;font-weight:700;font-family:inherit;transition:all .15s;white-space:nowrap; }
#translate-lang-btn.active { border-color:var(--t400);color:var(--t500);background:var(--t50); }
#translate-lang-picker { display:none;position:absolute;bottom:calc(100% + 6px);left:0;background:#fff;border:1px solid var(--t100);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);padding:5px;min-width:120px;z-index:200; }
#translate-lang-picker.open { display:block; }
.tlp-item { display:block;width:100%;padding:6px 10px;font-size:12px;font-weight:500;color:#374151;background:none;border:none;border-radius:7px;cursor:pointer;text-align:left;font-family:inherit;transition:background .1s; }
.tlp-item:hover { background:var(--t50); }
.tlp-item.selected { background:var(--t100);color:var(--t600);font-weight:700; }
.msg-translate-badge { display:inline-block;font-size:10px;font-weight:700;color:var(--t400);background:var(--t50);border:1px solid var(--t100);border-radius:4px;padding:1px 5px;margin-top:4px; }

/* 이미지 리뷰 라이트박스 */
#img-lightbox {
    display:none; position:fixed; inset:0; z-index:9000;
    background:rgba(0,0,0,.82); backdrop-filter:blur(4px);
    align-items:center; justify-content:center;
}
#img-lightbox.open { display:flex; }
@keyframes lb-in { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:scale(1); } }
@keyframes spin { to { transform:rotate(360deg); } }
#img-lb-container {
    display:flex; flex-direction:column; width:min(1100px,96vw); height:min(82vh,820px);
    background:#fff; border-radius:16px; overflow:hidden;
    box-shadow:0 24px 64px rgba(0,0,0,.45);
    animation:lb-in .18s ease;
}
/* 전체 창 모드 — Fullscreen API 가 외곽 overlay 만 키우면 안쪽이 그대로라
   사용자가 [전체 창] 눌러도 뷰어가 안 커보임. fullscreen 활성 시 컨테이너를
   100% 로 펼치고 둥근 모서리/그림자 제거. */
#img-lightbox:fullscreen #img-lb-container,
#img-lightbox:-webkit-full-screen #img-lb-container {
    width:100%; height:100%; max-width:none; max-height:none;
    border-radius:0; box-shadow:none;
}
#img-lightbox:fullscreen #img-lb-toolbar,
#img-lightbox:-webkit-full-screen #img-lb-toolbar {
    border-radius:0;
}
#img-lb-toolbar {
    display:flex; align-items:center; gap:4px; padding:0 14px;
    height:42px; background:rgba(12,9,26,.98); flex-shrink:0;
    border-bottom:1px solid rgba(255,255,255,.06); border-radius:16px 16px 0 0;
}
#img-lb-body { display:flex; flex:1; min-height:0; overflow:hidden; }
#img-lb-ann-svg { position:absolute; z-index:20; pointer-events:none; overflow:visible; }
.lb-ann-tool-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:28px; height:28px; background:rgba(255,255,255,.06);
    border:1px solid rgba(255,255,255,.1); border-radius:6px; color:#9ca3af;
    cursor:pointer; transition:background .15s,color .15s,border-color .15s; padding:0; flex-shrink:0;
}
.lb-ann-tool-btn:hover { background:rgba(196,181,253,.15); color:#c4b5fd; }
.lb-ann-tool-btn.active { background:rgba(196,181,253,.28); color:#c4b5fd; border-color:rgba(196,181,253,.45); }
.lb-ann-item { cursor:default; }
.lb-ann-item[data-can-delete="1"] { cursor:pointer; }
#img-lb-image-side {
    flex:1; background:#111; display:flex; flex-direction:column;
    position:relative; overflow:hidden; min-width:0;
}
#img-lb-scroll-wrap {
    flex:1; overflow:auto; min-height:0; cursor:grab; user-select:none;
    scrollbar-width:thin; scrollbar-color:#444 #111;
}
#img-lb-inner {
    display:flex; align-items:flex-start; min-height:100%; min-width:100%;
    padding:20px; box-sizing:border-box;
}
#img-lightbox-img {
    display:block; margin:auto; max-width:100%; max-height:100%; object-fit:contain;
}
#img-lb-zoom-bar {
    display:flex; align-items:center; justify-content:center; gap:8px;
    padding:6px 14px; background:#0d0d14; flex-shrink:0;
    border-top:1px solid rgba(255,255,255,.06);
}
.lb-zoom-btn {
    padding:4px 10px; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12);
    color:#d1d5db; border-radius:6px; font-size:13px; cursor:pointer; line-height:1;
    transition:background .15s;
}
.lb-zoom-btn:hover { background:rgba(255,255,255,.13); }
#lb-img-zoom-label { font-size:11px; color:#9ca3af; min-width:38px; text-align:center; }
#img-lightbox-name {
    position:absolute; bottom:44px; left:0; right:0; text-align:center;
    color:rgba(255,255,255,.5); font-size:11px; pointer-events:none;
}
#img-lb-close {
    background:rgba(255,255,255,.12); border:none; border-radius:7px;
    height:28px; padding:0 10px; color:rgba(255,255,255,.8); font-size:13px; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:5px; transition:background .15s; flex-shrink:0;
}
#img-lb-close:hover { background:rgba(255,255,255,.22); color:#fff; }
/* 의견 패널 */
#img-lb-review {
    width:300px; min-width:300px; display:flex; flex-direction:column;
    border-left:1px solid #e4e4e7;
    transition:width .22s ease, min-width .22s ease, border-left-width .22s ease;
    overflow:hidden;
}
#img-lb-review.lb-collapsed { width:0; min-width:0; border-left-width:0; }
#lb-review-tab {
    position:absolute; right:0; top:50%; transform:translateY(-50%); z-index:25;
    width:22px; height:64px;
    background:linear-gradient(180deg,var(--t400),var(--t600));
    border:none; border-radius:6px 0 0 6px;
    color:#fff; font-size:13px;
    cursor:pointer; display:flex; align-items:center; justify-content:center;
    box-shadow:-2px 0 10px rgba(0,0,0,.25);
    transition:width .15s, background .15s; line-height:1;
}
#lb-review-tab:hover {
    width:28px;
    background:linear-gradient(180deg,var(--t300),var(--t500));
}
#img-lb-review-hdr {
    padding:12px 14px 10px; border-bottom:1px solid #f1f5f9; flex-shrink:0;
    display:flex; align-items:center; gap:6px;
}
#lb-review-collapse-btn {
    margin-left:auto; background:none; border:none; cursor:pointer; color:#a1a1aa;
    font-size:13px; padding:2px 4px; line-height:1; border-radius:4px; transition:color .12s, background .12s;
}
#lb-review-collapse-btn:hover { color:#374151; background:#f1f5f9; }
#img-lb-review-title { font-size:13px; font-weight:700; color:#1e1b2e; }
#img-lb-comment-count { font-size:11px; color:#a1a1aa; font-weight:500; }
#img-lb-comments {
    flex:1; overflow-y:auto; padding:12px; display:flex; flex-direction:column; gap:8px;
    scrollbar-width:thin; scrollbar-color:var(--t100) transparent;
}
#img-lb-comments::-webkit-scrollbar { width:4px; }
#img-lb-comments::-webkit-scrollbar-thumb { background:var(--t200); border-radius:4px; }
.lb-comment {
    background:#f8fafc; border-radius:10px; padding:9px 11px; font-size:12px;
    border-left:3px solid var(--t200);
}
.lb-comment.mine { background:#f5f3ff; border-left-color:var(--t400); }
.lb-comment-name { font-weight:700; color:#1e1b2e; font-size:11.5px; margin-bottom:3px; display:flex; justify-content:space-between; align-items:center; }
.lb-comment-time { font-size:10px; color:#a1a1aa; font-weight:400; }
.lb-comment-body { color:#3f3f46; line-height:1.55; white-space:pre-wrap; }
.lb-del-btn { background:none; border:none; cursor:pointer; color:#d1d5db; font-size:14px; padding:0 2px; line-height:1; transition:color .1s; }
.lb-del-btn:hover { color:#ef4444; }
#img-lb-empty { color:#a1a1aa; font-size:12px; text-align:center; margin:auto; }
#img-lb-review-form {
    padding:10px 12px; border-top:1px solid #f1f5f9; flex-shrink:0;
    display:flex; flex-direction:column; gap:6px;
}
#img-lb-textarea {
    resize:none; border:1.5px solid var(--t100); border-radius:10px;
    padding:8px 11px; font-size:12.5px; outline:none; font-family:inherit;
    max-height:100px; overflow-y:auto; line-height:1.5; transition:border-color .15s; background:#fff; color:#1e1b2e;
}
#img-lb-textarea:focus { border-color:var(--t400); }
#img-lb-submit {
    align-self:flex-end; padding:6px 16px; background:linear-gradient(135deg,var(--t300),var(--t500));
    color:#fff; border:none; border-radius:8px; font-size:12px; font-weight:600;
    cursor:pointer; transition:opacity .15s;
}
#img-lb-submit:hover { opacity:.85; }
#img-lb-submit:disabled { opacity:.5; cursor:default; }

/* 메시지 액션 버튼 (말풍선 hover 시 우측 상단) */
.msg-bubble-wrap { position:relative; display:inline-block; max-width:100%; }
.msg-btn-group {
    display:none; position:absolute; top:-8px; right:-8px; z-index:10;
    align-items:center; gap:4px;
}
.msg-row:hover .msg-btn-group { display:inline-flex; }
.msg-ai-btn, .msg-pda-btn {
    display:inline-flex; align-items:center; gap:3px; padding:2px 7px 2px 5px;
    backdrop-filter:blur(4px); color:#fff; border:none; border-radius:6px;
    font-size:10.5px; font-weight:600; cursor:pointer; white-space:nowrap;
    font-family:inherit; transition:background .12s;
}
.msg-ai-btn { background:rgba(109,40,217,.88); }
.msg-ai-btn:hover { background:rgba(109,40,217,1); }
.msg-pda-btn { background:rgba(180,83,9,.9); }
.msg-pda-btn:hover { background:rgba(180,83,9,1); }
.msg-pda-btn.registered { background:rgba(22,163,74,.92); }
.msg-pda-btn.registered:hover { background:rgba(22,163,74,1); }


/* 답글 — 부모 말풍선 내부에 표시 */
.bubble-replies { display:flex; flex-direction:column; gap:3px; }
.bubble-replies:not(:empty) { margin-top:8px; padding-top:7px; border-top:1px solid rgba(0,0,0,.09); }
.msg-bubble.mine .bubble-replies:not(:empty) { border-top-color:rgba(255,255,255,.28); }
.bubble-reply-item { display:grid; grid-template-columns:1fr auto; row-gap:1px; column-gap:6px; padding:5px 8px; border-radius:7px; font-size:12px; background:rgba(0,0,0,.05); }
.msg-bubble.mine .bubble-reply-item { background:rgba(255,255,255,.16); }
.bubble-reply-sender { font-weight:700; color:var(--t600); font-size:10.5px; grid-column:1; }
.msg-bubble.mine .bubble-reply-sender { color:rgba(255,255,255,.92); }
.bubble-reply-time { font-size:10px; color:#a1a1aa; grid-column:2; grid-row:1/3; align-self:center; white-space:nowrap; }
.msg-bubble.mine .bubble-reply-time { color:rgba(255,255,255,.55); }
.bubble-reply-body { color:#3f3f46; grid-column:1; white-space:pre-wrap; word-break:break-word; line-height:1.4; }
.msg-bubble.mine .bubble-reply-body { color:rgba(255,255,255,.88); }

/* 우클릭 컨텍스트 메뉴 */
#msg-ctx-menu {
    display:none; position:fixed; z-index:99999;
    background:#fff; border:1px solid rgba(196,181,253,.25);
    border-radius:11px; padding:4px;
    box-shadow:0 10px 28px rgba(99,102,241,.18), 0 2px 8px rgba(0,0,0,.08);
    min-width:155px;
}
.ctx-item {
    display:flex; align-items:center; gap:9px;
    padding:7px 13px; border-radius:8px; cursor:pointer;
    font-size:13px; font-weight:500; color:#1e1b2e; transition:background .1s;
}
.ctx-item:hover { background:#f5f3ff; color:#7c3aed; }
.ctx-item svg { flex-shrink:0; }

/* 답글 미리보기 바 */
#reply-preview-bar {
    display:none; align-items:center; gap:8px;
    padding:8px 12px; background:#f5f3ff;
    border-radius:8px; margin-bottom:8px;
    border-left:3px solid #7c3aed; font-size:12.5px;
}
#reply-preview-name { font-weight:700; color:#7c3aed; margin-right:4px; font-size:11.5px; }
#reply-preview-text { flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; color:#4c1d95; }
#reply-preview-close { background:none; border:none; cursor:pointer; color:#9ca3af; font-size:18px; line-height:1; padding:0 2px; transition:color .1s; flex-shrink:0; }
#reply-preview-close:hover { color:#ef4444; }

/* 메시지 안 인용 블록 */
.msg-reply-quote {
    border-radius:6px; padding:5px 9px; margin-bottom:6px;
    font-size:12px; line-height:1.45; cursor:default;
    border-left:3px solid rgba(255,255,255,.55);
    background:rgba(255,255,255,.18);
}
.msg-bubble.theirs .msg-reply-quote {
    background:#ede9fe; border-left-color:#7c3aed;
}
.msg-reply-quote-name { font-weight:700; font-size:11px; margin-bottom:2px; opacity:.85; }
.msg-reply-quote-text { white-space:nowrap; overflow:hidden; text-overflow:ellipsis; opacity:.8; }

/* 번역 메시지 표시 */
.msg-translated-badge {
    display:inline-flex; align-items:center; gap:3px;
    margin-top:5px; font-size:10.5px; opacity:.6; font-style:italic;
}
.msg-original-note {
    margin-top:6px; padding-top:5px;
    border-top:1px solid rgba(0,0,0,.08);
    font-size:11.5px; color:rgba(0,0,0,.45); line-height:1.5;
}
.msg-bubble.mine .msg-original-note { border-top-color:rgba(255,255,255,.25); color:rgba(255,255,255,.55); }
.msg-original-label {
    display:inline-block; font-size:10px; font-weight:700;
    background:rgba(0,0,0,.07); border-radius:3px;
    padding:0 4px; margin-right:3px; vertical-align:middle;
}
.msg-bubble.mine .msg-original-label { background:rgba(255,255,255,.2); }

/* 웍스 분석 결과 패널 (fixed, chat-messages 영역 안에 표시) */
#ai-analysis-panel {
    display:none; position:fixed; z-index:500; width:300px;
    background:rgba(255,255,255,.97); backdrop-filter:blur(6px);
    border:1px solid #ddd6fe; border-radius:14px;
    box-shadow:0 8px 32px rgba(109,92,231,.18), 0 2px 8px rgba(0,0,0,.06);
    padding:13px 15px; max-height:240px; overflow-y:auto;
}
#ai-analysis-panel.visible { display:block; }
.ai-rc-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:9px; }
.ai-rc-title { font-size:11.5px; font-weight:700; color:#7c3aed; letter-spacing:.04em; }
.ai-rc-close-btn { cursor:pointer; color:#c4b5fd; font-size:18px; line-height:1; background:none; border:none; padding:0; }
.ai-rc-row { display:flex; gap:6px; margin-bottom:5px; font-size:12px; }
.ai-rc-label { font-weight:700; color:#7c3aed; flex-shrink:0; min-width:38px; }
.ai-rc-value { color:#312e81; line-height:1.5; }
.ai-rc-tags { display:flex; gap:4px; flex-wrap:wrap; }
.ai-rc-tag { background:#ddd6fe; color:#6d28d9; padding:1px 7px; border-radius:5px; font-size:11px; font-weight:600; }
.ai-action-list { display:flex; flex-direction:column; gap:7px; margin-top:10px; padding-top:9px; border-top:1px solid #ede9fe; }
.ai-action-heading { font-size:11.5px; font-weight:800; color:#6d28d9; }
.ai-action-card { border:1px solid #e9d5ff; background:#faf5ff; border-radius:10px; padding:8px 9px; display:flex; flex-direction:column; gap:5px; }
.ai-action-title { font-size:12px; font-weight:700; color:#2e1065; line-height:1.4; }
.ai-action-desc { font-size:11.5px; color:#6b21a8; line-height:1.45; }
.ai-action-meta { display:flex; gap:5px; flex-wrap:wrap; font-size:10.5px; color:#7c3aed; }
.ai-action-chip { background:#ede9fe; border-radius:999px; padding:1px 7px; }
.ai-action-create { align-self:flex-start; display:inline-flex; align-items:center; gap:4px; height:24px; padding:0 9px; border:0; border-radius:7px; background:#7c3aed; color:#fff; font-size:11px; font-weight:700; cursor:pointer; }
.ai-action-create:hover { background:#6d28d9; }
.ai-action-create[disabled] { opacity:.55; cursor:default; }

/* Modals */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(30,27,46,.35); z-index:9999; backdrop-filter:blur(2px); }
.modal-box { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:10000; background:#fff; border-radius:16px; padding:24px; width:460px; max-width:95vw; box-shadow:0 16px 48px rgba(0,0,0,.1); }
.modal-title { font-size:16px; font-weight:700; color:#1e1b2e; }
.modal-label { display:block; font-size:12px; font-weight:600; color:#52525b; margin-bottom:6px; }
.modal-input { width:100%; padding:9px 12px; border:1.5px solid var(--t100); border-radius:9px; font-size:13px; color:#1e1b2e; outline:none; background:#fff; box-sizing:border-box; transition:border-color .15s; }
.modal-input:focus { border-color:var(--t500); }

/* Member selector */
#member-list { display:flex; flex-direction:column; gap:4px; max-height:180px; overflow-y:auto; border:1.5px solid var(--t100); border-radius:9px; padding:6px; }
.member-row { display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:7px; cursor:pointer; transition:background .1s; user-select:none; }
.member-row:hover { background:var(--tBg); }
.member-row input[type=checkbox] { accent-color:var(--t500); width:15px; height:15px; flex-shrink:0; }
.member-avatar-sm { width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:#fff; flex-shrink:0; }
</style>

<div id="ws-shell">

    {{-- ── 워크스페이스 상단바 (프로젝트/메뉴 선택 + 팝업 컨트롤) ── --}}
    <div id="ws-topbar" role="toolbar" aria-label="Project workspace controls">
        <div class="ws-tb-group">
            <select id="ws-project-sel" class="ws-tb-sel" aria-label="{{ __('messages.ws_select_project') }}" disabled>
                <option value="">{{ __('messages.ws_select_project') }}</option>
            </select>
            <select id="ws-menu-sel" class="ws-tb-sel" aria-label="{{ __('messages.ws_select_menu') }}" disabled>
                <option value="">{{ __('messages.ws_select_menu') }}</option>
            </select>
            <button type="button" id="ws-open-btn" class="ws-tb-btn primary" onclick="wsOpenPopup()" disabled
                    title="{{ __('messages.ws_open_popup') }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                {{ __('messages.ws_open_popup') }}
            </button>
            <span id="ws-status" class="ws-tb-status"></span>
        </div>
        <div class="ws-tb-group">
            <button type="button" id="ws-min-btn" class="ws-tb-btn icon-only" onclick="wsMinimize()" disabled
                    title="{{ __('messages.ws_minimize') }}" aria-label="{{ __('messages.ws_minimize') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M5 19h14"/></svg>
            </button>
            <button type="button" id="ws-max-btn" class="ws-tb-btn icon-only" onclick="wsMaximize()" disabled
                    title="{{ __('messages.ws_maximize') }}" aria-label="{{ __('messages.ws_maximize') }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="4" y="4" width="16" height="16" rx="1.5"/></svg>
            </button>
            <span class="ws-tb-sep"></span>
            <button type="button" id="ws-close-btn" class="ws-tb-btn icon-only" onclick="wsClosePopup()" disabled
                    title="{{ __('messages.ws_close_popup') }}" aria-label="{{ __('messages.ws_close_popup') }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
    </div>

    {{-- ── 플로팅 팝업 (메뉴 화면용 떠있는 창) ── --}}
    <div id="ws-popup" role="dialog" aria-label="Project workspace popup">
        <div id="ws-popup-titlebar" ondblclick="wsMaximize()">
            <div id="ws-popup-title">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <span id="ws-popup-title-text"></span>
            </div>
            <button type="button" class="ws-tt-btn" onclick="wsMinimize()" title="{{ __('messages.ws_minimize') }}" aria-label="{{ __('messages.ws_minimize') }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M5 19h14"/></svg>
            </button>
            <button type="button" class="ws-tt-btn" onclick="wsMaximize()" title="{{ __('messages.ws_maximize') }}" aria-label="{{ __('messages.ws_maximize') }}">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><rect x="4" y="4" width="16" height="16" rx="1.5"/></svg>
            </button>
            <button type="button" class="ws-tt-btn close-btn" onclick="wsClosePopup()" title="{{ __('messages.ws_close_popup') }}" aria-label="{{ __('messages.ws_close_popup') }}">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>
        <div id="ws-popup-body">
            <iframe id="ws-popup-iframe" name="ws-popup-iframe" title="Project workspace"></iframe>
            <div id="ws-popup-loading" style="display:none;">{{ __('common.loading') ?? 'Loading...' }}</div>
        </div>
        <span class="ws-rs ws-rs-r"  data-rs="r"></span>
        <span class="ws-rs ws-rs-b"  data-rs="b"></span>
        <span class="ws-rs ws-rs-l"  data-rs="l"></span>
        <span class="ws-rs ws-rs-br" data-rs="br"></span>
        <span class="ws-rs ws-rs-bl" data-rs="bl"></span>
    </div>

<div id="msg-wrap">

    {{-- ── 대화 목록 ── --}}
    <div id="conv-list">
        <div id="conv-list-hdr">
            <p style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin:0 0 8px;">{{ __('messages.messages') }}</p>
            <div style="display:flex;gap:4px;margin-bottom:8px;">
                <button onclick="openNewModal('dm')"
                    style="flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:6px 0;font-size:12px;font-weight:600;color:var(--tText);background:var(--t50);border:1px solid var(--t200);border-radius:7px;cursor:pointer;"
                    onmouseover="this.style.background='var(--t100)'" onmouseout="this.style.background='var(--t50)'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    {{ __('messages.new_dm') }}
                </button>
                <button onclick="openNewModal('group')"
                    style="flex:1;display:flex;align-items:center;justify-content:center;gap:4px;padding:6px 0;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    {{ __('messages.group_chat') }}
                </button>
            </div>
            <input id="conv-search" type="text" placeholder="{{ __('messages.search_conv') }}" oninput="filterConvs(this.value)">
        </div>
        <div id="conv-list-body">
            @forelse($conversations as $conv)
                @php
                    $unread  = $conv->unreadCount(auth()->id());
                    $last    = $conv->lastMessage;
                    $isActive= isset($conversation) && $conversation->id === $conv->id;
                    $colors  = ['#a394f9','#7dd3fc','#6ee7b7','#fcd34d','#f9a8d4','#c4b5fd'];
                    $dispName = $conv->displayName(auth()->id());
                @endphp
                <div class="conv-item-wrap">
                <a href="{{ route('messages.show', $conv) }}"
                   class="conv-item {{ $isActive ? 'active' : '' }}"
                   data-name="{{ strtolower($dispName) }}"
                   data-conv-id="{{ $conv->id }}"
                   data-group="{{ $conv->is_group ? '1' : '0' }}">
                    @if($conv->is_group)
                        @php $cnt = $conv->participants->count(); @endphp
                        <div class="conv-avatar group" style="background:linear-gradient(135deg,var(--t300),var(--t500));font-size:13px;">
                            <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    @else
                        @php $other = $conv->otherParticipant(auth()->id()); $color = $colors[($other?->id ?? 0) % count($colors)]; @endphp
                        <div class="conv-avatar" style="background:{{ $color }};">{{ mb_substr($other?->name ?? '?', 0, 1) }}</div>
                    @endif
                    <div style="flex:1;min-width:0;padding-right:18px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:2px;">
                            <div style="display:flex;align-items:center;gap:4px;min-width:0;">
                                <span class="conv-name" style="{{ $unread ? 'font-weight:700;' : '' }}">{{ $dispName }}</span>
                                @if($conv->is_group)<span class="group-tag">{{ __('messages.group_tag') }}</span>@endif
                            </div>
                            @if($last)<span class="conv-time">{{ $last->created_at->diffForHumans(null, true) }}</span>@endif
                        </div>
                        <div style="display:flex;align-items:center;gap:4px;">
                            <span class="conv-preview" style="flex:1;" data-preview-conv="{{ $conv->id }}">
                                @if($last)
                                    @if($conv->is_group)<span style="color:var(--tText);font-weight:500;">{{ mb_substr($last->sender->name ?? '', 0, 4) }}</span>: @endif
                                    @if($last->file_name && !$last->body)📎 {{ $last->file_name }}
                                    @else{{ $last->body }}@endif
                                @else {{ __('messages.no_messages') }} @endif
                            </span>
                            <span class="conv-badge" data-badge-conv="{{ $conv->id }}" style="display:{{ $unread ? 'inline-block' : 'none' }};">{{ $unread ?: '' }}</span>
                        </div>
                    </div>
                </a>
                <button class="conv-leave-btn" onclick="leaveConv(event,{{ $conv->id }})" title="{{ __('messages.leave_conv') }}">×</button>
                </div>
            @empty
                <div style="padding:40px 16px;text-align:center;font-size:13px;color:var(--color-text-tertiary);line-height:2;">
                    {{ __('messages.no_conversations') }}<br>{{ __('messages.start_new_message') }}
                </div>
            @endforelse
        </div>
    </div>

    {{-- ── 채팅 영역 ── --}}
    <div id="chat-area">
        @if(isset($conversation))
            @php
                $colors = ['#a394f9','#7dd3fc','#6ee7b7','#fcd34d','#f9a8d4','#c4b5fd'];
                $isGroup = $conversation->is_group;
                $me = auth()->id();
            @endphp

            {{-- 헤더 --}}
            <div id="chat-hdr">
                @if($isGroup)
                    <div class="conv-avatar group" style="background:linear-gradient(135deg,var(--t300),var(--t500));width:36px;height:36px;">
                        <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                    <div style="position:relative;min-width:0;flex:1;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span style="font-size:15px;font-weight:700;color:var(--color-text-primary);">{{ $conversation->name }}</span>
                            <button type="button" id="group-tag-btn" onclick="toggleMembersPopover(event)"
                                    class="group-tag" style="border:none;cursor:pointer;display:inline-flex;align-items:center;gap:4px;"
                                    title="{{ __('messages.view_members') }}">
                                {{ __('messages.group_member_count', ['count' => $conversation->participants->count()]) }}
                                <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        <div id="chat-hdr-members">
                            @php $others = $conversation->participants->where('id', '!=', $me)->values(); @endphp
                            @foreach($others as $i => $m)
                                <button type="button" class="hdr-member-chip"
                                        onclick="mentionMemberInComposer(@js($m->name))"
                                        title="{{ __('messages.mention_add') }}">{{ $m->name }}</button>@if($i < $others->count() - 1)<span style="color:#cbd5e1;">, </span>@endif
                            @endforeach
                        </div>

                        {{-- 구성원 팝오버 --}}
                        <div id="members-popover" style="display:none;position:absolute;top:calc(100% + 8px);left:0;z-index:50;background:#fff;border:1px solid var(--color-border-default);border-radius:10px;box-shadow:0 12px 28px rgba(0,0,0,.12);min-width:260px;max-width:340px;max-height:360px;overflow:hidden;display:none;flex-direction:column;">
                            <div style="padding:10px 14px;border-bottom:1px solid var(--color-bg-muted);display:flex;align-items:center;justify-content:space-between;gap:8px;background:#fafafa;">
                                <span style="font-size:12px;font-weight:700;color:#1f2937;">{{ __('messages.member_count', ['count' => $conversation->participants->count()]) }}</span>
                                <button type="button" onclick="closeMembersPopover()" style="background:none;border:none;font-size:18px;line-height:1;color:var(--color-text-tertiary);cursor:pointer;padding:0;">×</button>
                            </div>
                            <ul style="list-style:none;margin:0;padding:6px 0;overflow-y:auto;flex:1;">
                                @foreach($conversation->participants as $m)
                                    @php $mColor = $colors[($m->id ?? 0) % count($colors)]; $isMe = $m->id === $me; @endphp
                                    <li>
                                        <button type="button"
                                            @if(!$isMe) onclick="mentionMemberInComposer(@js($m->name))" @endif
                                            @if($isMe) disabled title="{{ __('messages.mention_self_disabled') }}" @else title="{{ __('messages.mention_add') }}" @endif
                                            style="width:100%;display:flex;align-items:center;gap:8px;padding:7px 14px;background:none;border:none;cursor:{{ $isMe ? 'default' : 'pointer' }};text-align:left;{{ $isMe ? 'opacity:.6;' : '' }}"
                                            @if(!$isMe) onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='none'" @endif>
                                            <div style="width:28px;height:28px;border-radius:50%;background:{{ $mColor }};color:#fff;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                {{ mb_substr($m->name, 0, 1) }}
                                            </div>
                                            <div style="min-width:0;flex:1;">
                                                <div style="font-size:13px;font-weight:600;color:#1f2937;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $m->name }}{{ $isMe ? ' ('.__('messages.me').')' : '' }}
                                                </div>
                                                <div style="font-size:11px;color:var(--color-text-tertiary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                                    {{ $m->email }}
                                                </div>
                                            </div>
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    <button type="button" id="invite-btn" onclick="openInviteModal()" title="{{ __('messages.invite_to_room') }}"
                            style="margin-left:auto;display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:#fff;border:1.5px solid var(--t200);color:var(--t500);font-size:12px;font-weight:600;border-radius:8px;cursor:pointer;flex-shrink:0;transition:all .15s;"
                            onmouseover="this.style.background='var(--t100)';this.style.borderColor='var(--t500)'" onmouseout="this.style.background='#fff';this.style.borderColor='var(--t200)'">
                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        {{ __('messages.invite') }}
                    </button>
                @else
                    @php $other = $conversation->otherParticipant($me); $color = $colors[($other?->id ?? 0) % count($colors)]; @endphp
                    <div class="conv-avatar" style="background:{{ $color }};width:36px;height:36px;font-size:14px;">{{ mb_substr($other?->name ?? '?', 0, 1) }}</div>
                    <div>
                        <div style="font-size:15px;font-weight:700;color:var(--color-text-primary);">{{ $other?->name ?? __('messages.unknown_user') }}</div>
                        <div style="font-size:12px;color:var(--color-text-tertiary);">{{ $other?->email }}</div>
                    </div>
                @endif
            </div>

            {{-- 초대 모달 (그룹 채팅 한정) --}}
            @if($isGroup)
                @php
                    $existingMemberIds = $conversation->participants->pluck('id');
                    $inviteCandidates  = $users->whereNotIn('id', $existingMemberIds)->values();
                @endphp
                <div id="invite-overlay" class="modal-overlay" onclick="closeInviteModal()" style="display:none;"></div>
                <div id="invite-modal" class="modal-box" style="display:none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
                        <span class="modal-title">{{ __('messages.invite_to_room') }}</span>
                        <button onclick="closeInviteModal()" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);font-size:22px;line-height:1;">&times;</button>
                    </div>
                    <form id="invite-form" onsubmit="return submitInvite(event)">
                        @csrf
                        <div style="margin-bottom:10px;">
                            <input type="text" id="invite-search" class="modal-input" placeholder="{{ __('messages.search_name_email') }}" oninput="filterInviteCandidates(this.value)">
                        </div>
                        @if($inviteCandidates->isEmpty())
                            <div style="padding:32px 12px;text-align:center;color:var(--color-text-tertiary);font-size:13px;">
                                {{ __('messages.no_invite_candidates') }}<br>
                                <span style="font-size:11px;">{{ __('messages.no_invite_candidates_hint') }}</span>
                            </div>
                        @else
                            <div id="invite-list" style="max-height:280px;overflow-y:auto;border:1px solid var(--t100);border-radius:9px;padding:6px;">
                                @foreach($inviteCandidates as $u)
                                    @php $ac = $colors[$u->id % count($colors)]; @endphp
                                    <label class="member-row invite-row" data-name="{{ mb_strtolower($u->name) }}" data-email="{{ mb_strtolower($u->email) }}">
                                        <input type="checkbox" name="member_ids[]" value="{{ $u->id }}" onchange="updateInviteCount()">
                                        <div class="member-avatar-sm" style="background:{{ $ac }};">{{ mb_substr($u->name, 0, 1) }}</div>
                                        <div>
                                            <div style="font-size:13px;font-weight:500;color:var(--color-text-primary);">{{ $u->name }}</div>
                                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ $u->email }}</div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <div id="invite-selected-count" style="font-size:12px;color:var(--t500);margin-top:6px;font-weight:500;"></div>
                        @endif
                        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:14px;">
                            <button type="button" onclick="closeInviteModal()" style="padding:8px 16px;border:1.5px solid var(--t100);border-radius:9px;background:#fff;font-size:13px;font-weight:500;color:var(--color-text-secondary);cursor:pointer;">{{ __('common.cancel') }}</button>
                            <button type="submit" id="invite-submit-btn" @if($inviteCandidates->isEmpty()) disabled style="opacity:.4;cursor:not-allowed;" @endif
                                style="padding:8px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.1);">
                                {{ __('messages.invite') }}
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- 파일 메일 발송 다이얼로그 (구성원 선택 + 이메일 직접 입력) --}}
            @php
                $esfMembers = $conversation->participants->where('id', '!=', $me)->values();
            @endphp
            <div id="esf-modal" onclick="if(event.target===this)esfClose()" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(0,0,0,.5);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;">
                <div style="background:#fff;width:520px;max-width:calc(100vw - 48px);max-height:calc(100vh - 48px);border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;">
                    <div style="padding:16px 22px;border-bottom:1px solid var(--color-bg-muted);display:flex;align-items:center;justify-content:space-between;gap:12px;flex-shrink:0;">
                        <h3 style="margin:0;font-size:15px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--t500)" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            {{ __('messages.file_email_send') }}
                        </h3>
                        <button type="button" onclick="esfClose()" style="background:none;border:none;font-size:22px;color:var(--color-text-tertiary);cursor:pointer;line-height:1;padding:0;">×</button>
                    </div>
                    <div style="padding:16px 22px;overflow-y:auto;flex:1;">
                        <div id="esf-filename" style="background:#f9fafb;border:1px solid var(--color-bg-muted);border-radius:8px;padding:9px 12px;font-size:12.5px;color:var(--color-text-secondary);display:flex;align-items:center;gap:8px;margin-bottom:14px;">
                            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span id="esf-filename-text" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span>
                        </div>

                        {{-- 구성원 선택 --}}
                        <div style="margin-bottom:14px;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                                <label style="font-size:12px;font-weight:700;color:var(--color-text-secondary);">{{ __('messages.esf_members_label') }}</label>
                                @if($esfMembers->count() > 0)
                                <div style="display:flex;gap:8px;">
                                    <button type="button" onclick="esfToggleAllMembers(true)" style="background:none;border:none;font-size:11.5px;color:var(--t500);cursor:pointer;padding:0;">{{ __('messages.esf_select_all') }}</button>
                                    <span style="color:#d1d5db;">·</span>
                                    <button type="button" onclick="esfToggleAllMembers(false)" style="background:none;border:none;font-size:11.5px;color:var(--color-text-tertiary);cursor:pointer;padding:0;">{{ __('messages.esf_deselect_all') }}</button>
                                </div>
                                @endif
                            </div>
                            <div id="esf-members-list" style="border:1px solid var(--color-border-default);border-radius:8px;max-height:160px;overflow-y:auto;background:#fff;">
                                @forelse($esfMembers as $m)
                                    @php $hasEmail = (bool) filter_var($m->email ?? '', FILTER_VALIDATE_EMAIL); @endphp
                                    <label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border-bottom:1px solid var(--color-bg-muted);cursor:{{ $hasEmail ? 'pointer' : 'not-allowed' }};{{ $hasEmail ? '' : 'opacity:.55;' }}">
                                        <input type="checkbox" class="esf-member-cb" value="{{ $m->id }}" data-email="{{ $m->email }}" @if(!$hasEmail) disabled @endif style="width:15px;height:15px;accent-color:var(--t500);cursor:inherit;">
                                        <span style="flex:1;font-size:13px;color:#1f2937;font-weight:600;">{{ $m->name }}</span>
                                        <span style="font-size:11.5px;color:var(--color-text-tertiary);">{{ $hasEmail ? $m->email : __('messages.esf_no_email') }}</span>
                                    </label>
                                @empty
                                    <div style="padding:14px;text-align:center;font-size:12.5px;color:var(--color-text-tertiary);">{{ __('messages.esf_no_members') }}</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- 이메일 직접 입력 --}}
                        <div>
                            <label for="esf-extra-emails" style="display:block;font-size:12px;font-weight:700;color:var(--color-text-secondary);margin-bottom:6px;">
                                {{ __('messages.esf_extra_emails_label') }}
                            </label>
                            <textarea id="esf-extra-emails" rows="2" placeholder="{{ __('messages.esf_extra_emails_placeholder') }}"
                                style="width:100%;border:1.5px solid var(--color-border-default);border-radius:8px;padding:8px 11px;font-size:13px;outline:none;font-family:inherit;resize:vertical;line-height:1.5;box-sizing:border-box;transition:border-color .15s;"
                                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                            <div style="font-size:11px;color:var(--color-text-tertiary);margin-top:4px;">{{ __('messages.esf_extra_emails_hint') }}</div>
                        </div>
                    </div>
                    <div style="padding:12px 22px;background:#fafafa;border-top:1px solid var(--color-bg-muted);display:flex;align-items:center;justify-content:space-between;gap:8px;flex-shrink:0;">
                        <span id="esf-recipients-summary" style="font-size:11.5px;color:var(--color-text-secondary);"></span>
                        <div style="display:flex;gap:8px;">
                            <button type="button" onclick="esfClose()" style="padding:7px 16px;background:#fff;color:var(--color-text-secondary);border:1px solid var(--color-border-default);border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.cancel') }}</button>
                            <button type="button" id="esf-send-btn" onclick="esfDoSend()" style="padding:7px 18px;background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:700;cursor:pointer;">{{ __('messages.send_email') }}</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- 메시지 목록 --}}
            @php
                $isGroup = $conversation->is_group;
                $otherParticipants = $conversation->participants->where('id', '!=', $me)->values();
                $totalOthers = $otherParticipants->count();
                $langNames = ['ko' => '한국어', 'en' => 'English', 'ja' => '日本語', 'zh' => '中文'];
            @endphp
            <div id="chat-messages">
                @php
                    $allMsgs    = $conversation->messages;
                    $repliesMap = $allMsgs->filter(fn($m) => !!$m->reply_to_id)->groupBy('reply_to_id');
                    $getDescendants = function($msgId) use (&$getDescendants, $repliesMap) {
                        $direct = ($repliesMap->get($msgId) ?? collect())->sortBy('created_at');
                        $all = collect();
                        foreach ($direct as $r) {
                            $all->push($r);
                            $all = $all->merge($getDescendants($r->id));
                        }
                        return $all;
                    };
                    $topMsgs  = $allMsgs->filter(fn($m) => !$m->reply_to_id)->sortBy('created_at')->values();
                    $prevDate = null;
                @endphp

                @foreach($topMsgs as $msg)
                    @php
                        $isMine      = $msg->sender_id === $me;
                        $msgDate     = $msg->created_at->format('Y-m-d');
                        $senderColor = $colors[($msg->sender_id ?? 0) % count($colors)];
                        $readLabel   = '';
                        if ($isMine && isset($participantReadAt)) {
                            if ($isGroup) {
                                $readCount   = $otherParticipants->filter(fn($p) => $participantReadAt[$p->id] !== null && $participantReadAt[$p->id] >= $msg->created_at)->count();
                                $unreadCount = $totalOthers - $readCount;
                                $readLabel   = $unreadCount > 0 ? (string)$unreadCount : __('messages.read');
                            } else {
                                $other       = $otherParticipants->first();
                                $otherReadAt = $other ? ($participantReadAt[$other->id] ?? null) : null;
                                $readLabel   = ($otherReadAt && $otherReadAt >= $msg->created_at) ? __('messages.read') : '1';
                            }
                        }
                    @endphp

                    @if($msgDate !== $prevDate)
                        <div style="text-align:center;" data-date="{{ $msgDate }}">
                            <span style="display:inline-block;font-size:11.5px;color:var(--color-text-tertiary);background:var(--t100);padding:3px 12px;border-radius:20px;">
                                {{ $msg->created_at->format(__('messages.php_date_label_format')) }}
                            </span>
                        </div>
                        @php $prevDate = $msgDate; @endphp
                    @endif

                    @php $descendants = $getDescendants($msg->id); @endphp
                    <div id="message-{{ $msg->id }}" class="msg-row {{ $isMine ? 'mine' : '' }}" data-msg-id="{{ $msg->id }}" data-msg-at="{{ $msg->created_at->toIso8601String() }}">
                        @if(!$isMine)
                            <div class="msg-avatar" style="background:{{ $senderColor }};">{{ mb_substr($msg->sender->name, 0, 1) }}</div>
                        @endif
                        <div style="max-width:70%;">
                            @if(!$isMine)
                                <div class="msg-name">{{ $msg->sender->name }}</div>
                            @endif
                            @php
                                $hasTranslation  = $msg->translated_body && $msg->translate_lang;
                                $displayBody     = (!$isMine && $hasTranslation) ? $msg->translated_body : $msg->body;
                                $previewBody     = ($hasTranslation && !$isMine) ? Str::limit($msg->translated_body, 80) : ($msg->body ? Str::limit($msg->body, 80) : '');
                                $msgLangName     = $hasTranslation ? ($langNames[$msg->translate_lang] ?? $msg->translate_lang) : '';
                            @endphp
                            <div class="msg-bubble-wrap" data-msg-id="{{ $msg->id }}" data-msg-body="{{ $previewBody ?: ($msg->file_name ? '📎 '.$msg->file_name : '') }}" data-msg-sender="{{ $isMine ? __('messages.me') : $msg->sender->name }}">
                                <div class="msg-bubble {{ $isMine ? 'mine' : 'theirs' }}">
                                    @if($displayBody)<div style="white-space:pre-wrap;word-break:break-word;">{!! preg_replace('/(https?:\/\/[^\s<>"\']+)/', '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">$1</a>', e($displayBody)) !!}</div>@endif
                                    @if($hasTranslation && $isMine)
                                        <div class="msg-translated-badge">🌐{{ $langNames[$msg->translate_lang] ?? $msg->translate_lang }}</div>
                                    @elseif($hasTranslation && !$isMine && $msg->body && $msg->translate_lang !== app()->getLocale())
                                        <div class="msg-original-note"><span class="msg-original-label">{{ __('messages.original_text') }}</span>{{ Str::limit($msg->body, 120) }}</div>
                                    @endif
                                    @if($msg->file_path)
                                        <div class="file-with-actions">
                                            @if($msg->isImage())
                                                <img src="{{ $msg->fileUrl() }}" alt="{{ $msg->file_name }}" class="file-img" onclick="openLightbox(this.src,this.alt,{{ $msg->id }})">
                                            @else
                                                <a href="{{ $msg->fileUrl() }}" download="{{ $msg->file_name }}" class="file-card {{ $isMine ? 'mine' : 'theirs' }}">
                                                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                    <div><div style="font-size:12.5px;font-weight:600;">{{ $msg->file_name }}</div><div style="font-size:11px;opacity:.7;">{{ $msg->formattedSize() }}</div></div>
                                                </a>
                                            @endif
                                            <button type="button" class="file-share-email-btn" onclick="shareFileByEmail({{ $msg->id }}, this)" title="{{ __('messages.share_file_email_title') }}">
                                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l9 6 9-6M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                                {{ __('messages.send_email') }}
                                            </button>
                                        </div>
                                    @endif
                                    @if($descendants->count() > 0)
                                    <div class="bubble-replies" id="breply-{{ $msg->id }}">
                                        @foreach($descendants as $reply)
                                            @php $replyIsMine = $reply->sender_id === $me; @endphp
                                            <div class="bubble-reply-item{{ $replyIsMine ? ' mine' : '' }}" data-reply-id="{{ $reply->id }}">
                                                <span class="bubble-reply-sender">{{ $replyIsMine ? __('messages.me') : $reply->sender->name }}</span>
                                                <span class="bubble-reply-time">{{ $reply->created_at->format('H:i') }}</span>
                                                <span class="bubble-reply-body">{{ $reply->body ?: ($reply->file_name ? '📎 '.$reply->file_name : '') }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                @php $msgPdaId = ($messagePdaMap ?? [])[$msg->id] ?? ''; @endphp
                                <div class="msg-btn-group">
                                    <button class="msg-pda-btn{{ $msgPdaId ? ' registered' : '' }}" type="button"
                                            data-msg-id="{{ $msg->id }}" data-pda-id="{{ $msgPdaId }}"
                                            onclick="pdaOpenFromMessage(this)">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        {{ __('plan-do-acts.nav') }}
                                    </button>
                                    <button class="msg-ai-btn" type="button" onclick="analyzeMsg({{ $msg->id }}, this)">
                                        <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                        {{ __('messages.ai_analysis') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                        @if($isMine)
                        <div style="display:flex;flex-direction:column;align-items:flex-end;justify-content:flex-end;gap:4px;padding-bottom:2px;">
                            @if($readLabel !== '')
                            <span class="msg-read {{ $readLabel === __('messages.read') ? '' : 'unread' }}" data-read-badge>{{ $readLabel }}</span>
                            @endif
                            <span class="msg-time">{{ $msg->created_at->format(__('messages.php_datetime_format')) }}</span>
                        </div>
                        @else
                        <span class="msg-time">{{ $msg->created_at->format(__('messages.php_datetime_format')) }}</span>
                        @endif
                    </div>
                @endforeach
                <div id="bottom"></div>
            </div>

            {{-- 입력창 --}}
            <div id="chat-input-area">
                <form id="chat-form" method="POST" action="{{ route('messages.reply', $conversation) }}" enctype="multipart/form-data">
                    @csrf
                    <div id="reply-preview-bar">
                        <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        <span id="reply-preview-name"></span>
                        <span id="reply-preview-text"></span>
                        <button type="button" id="reply-preview-close" onclick="clearReply()" title="{{ __('messages.reply_cancel_title') }}">&times;</button>
                    </div>
                    {{-- 첨부파일 미리보기 (멀티) — JS가 파일 칩을 채움 --}}
                    <div id="file-preview-bar"></div>
                    <input type="hidden" name="reply_to_id" id="reply-to-id-input" value="">
                    <div id="input-box">
                        <label for="file-input" title="{{ __('messages.attach_file') }}"
                            style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border:1.5px solid var(--t100);border-radius:10px;cursor:pointer;color:var(--color-text-tertiary);flex-shrink:0;transition:all .15s;"
                            onmouseover="this.style.borderColor='var(--t500)';this.style.color='var(--t500)'" onmouseout="this.style.borderColor='var(--t100)';this.style.color='#a1a1aa'">
                            <svg width="17" height="17" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </label>
                        {{-- 번역 언어 선택기 --}}
                        <div id="translate-lang-wrap">
                            <button type="button" id="translate-lang-btn" title="{{ __('messages.translate_title') }}" onclick="toggleTranslatePicker(event)">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                                <span id="translate-lang-label">{{ __('messages.translate_label') }}</span>
                            </button>
                            <div id="translate-lang-picker">
                                <button type="button" class="tlp-item selected" data-lang="" data-label="{{ __('messages.translate_label') }}" onclick="setTranslateLang(this)">✕ {{ __('messages.translate_none') }}</button>
                                <button type="button" class="tlp-item" data-lang="en" data-label="English" onclick="setTranslateLang(this)">🇺🇸 English</button>
                                <button type="button" class="tlp-item" data-lang="ko" data-label="한국어" onclick="setTranslateLang(this)">🇰🇷 한국어</button>
                                <button type="button" class="tlp-item" data-lang="ja" data-label="日本語" onclick="setTranslateLang(this)">🇯🇵 日本語</button>
                                <button type="button" class="tlp-item" data-lang="zh" data-label="中文" onclick="setTranslateLang(this)">🇨🇳 中文</button>
                            </div>
                        </div>
                        {{-- 이모지 버튼 --}}
                        <div style="position:relative;flex-shrink:0;">
                            <button type="button" id="emoji-toggle" title="{{ __('messages.emoji_title') }}"
                                style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;border:1.5px solid var(--t100);border-radius:10px;cursor:pointer;color:var(--color-text-tertiary);background:#fff;transition:all .15s;"
                                onmouseover="this.style.borderColor='var(--t500)';this.style.color='var(--t500)'" onmouseout="this.style.borderColor='var(--t100)';this.style.color='#a1a1aa'">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/></svg>
                            </button>
                            <div id="emoji-picker-wrap">
                                <div id="ep-header">
                                    <div id="ep-search-wrap">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                                        <input id="emoji-search" type="text" placeholder="{{ __('common.placeholder_search') }}" autocomplete="off" spellcheck="false">
                                    </div>
                                </div>
                                <div id="emoji-cats"></div>
                                <div id="emoji-grid-wrap">
                                    <div id="ep-recent"></div>
                                    <div id="emoji-grid"></div>
                                </div>
                            </div>
                        </div>
                        <input id="file-input" name="files[]" type="file" multiple style="display:none;" onchange="onFileSelect(this)">
                        <textarea id="msg-textarea" name="body" rows="1" placeholder="{{ __('messages.msg_input_hint') }}"
                            onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();submitForm();}"
                            oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,120)+'px';"
                            >{{ old('body') }}</textarea>
                        <button type="button" id="send-btn" onclick="submitForm()"
                            style="display:flex;align-items:center;justify-content:center;width:38px;height:38px;background:linear-gradient(135deg,var(--t300),var(--t500));border:none;border-radius:10px;cursor:pointer;flex-shrink:0;box-shadow:0 2px 8px rgba(0,0,0,.12);transition:opacity .15s;"
                            onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                            <svg id="send-btn-icon" width="16" height="16" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                            <svg id="send-btn-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" style="display:none;animation:spin .8s linear infinite;"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </button>
                    </div>
                </form>
            </div>
            <div id="ai-analysis-panel">
                <div class="ai-rc-header">
                    <span class="ai-rc-title">{{ __('messages.ai_analysis_title') }}</span>
                    <button class="ai-rc-close-btn" onclick="closeAiPanel()">&times;</button>
                </div>
                <div id="ai-panel-body"></div>
            </div>
        @else
            <div id="chat-empty">
                <svg width="52" height="52" fill="none" stroke="var(--t200)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <p style="font-size:14px;font-weight:500;">{{ __('messages.select_conv_or_send') }}</p>
            </div>
        @endif
    </div>
</div>
</div>{{-- /#ws-shell --}}

{{-- Plan-Do-Act 등록/수정 팝업 --}}
@include('plan-do-acts._modal')

{{-- 우클릭 컨텍스트 메뉴 --}}
<div id="msg-ctx-menu">
    <div class="ctx-item" id="ctx-reply-btn" onclick="ctxReply()">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
        {{ __('messages.ctx_reply') }}
    </div>
    <div class="ctx-item" id="ctx-copy-btn" onclick="ctxCopy()">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        {{ __('messages.ctx_copy') }}
    </div>
</div>

{{-- 이미지 리뷰 라이트박스 --}}
<div id="img-lightbox" onclick="closeLightbox()">
    <div id="img-lb-container" onclick="event.stopPropagation()">
        {{-- 도형 주석 툴바 --}}
        <div id="img-lb-toolbar">
            <button id="img-lb-close" onclick="closeLightbox()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                {{ __('common.close') }}
            </button>
            <button id="img-lb-fullscreen" onclick="lbToggleFullscreen()" title="{{ __('files.fullscreen_title') }}"
                    style="background:rgba(255,255,255,.12);border:none;border-radius:7px;height:28px;padding:0 10px;color:rgba(255,255,255,.8);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:background .15s;flex-shrink:0;margin-left:6px;">
                <svg id="img-lb-fullscreen-icon" width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                <span id="img-lb-fullscreen-label">{{ __('files.fullscreen') }}</span>
            </button>
            <button id="img-lb-download" onclick="lbDownloadCurrent()" title="{{ __('common.download') }}"
                    style="background:rgba(255,255,255,.12);border:none;border-radius:7px;height:28px;padding:0 10px;color:rgba(255,255,255,.8);font-size:13px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:background .15s;flex-shrink:0;margin-left:6px;">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>{{ __('common.download') }}</span>
            </button>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 8px;"></div>
            <span style="font-size:10px;font-weight:600;color:var(--color-text-secondary);letter-spacing:.4px;margin-right:4px;">{{ __('messages.annotation_label') }}</span>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 4px;"></div>
            <button id="lb-ann-btn-number" onclick="lbSetAnnTool('number')" title="{{ __('viewer.ann_number') }}" class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.5" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
            <button id="lb-ann-btn-rect"   onclick="lbSetAnnTool('rect')"   title="{{ __('viewer.ann_rect') }}"   class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
            <button id="lb-ann-btn-circle" onclick="lbSetAnnTool('circle')" title="{{ __('viewer.ann_circle') }}" class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
            <button id="lb-ann-btn-line"   onclick="lbSetAnnTool('line')"   title="{{ __('viewer.ann_line') }}"   class="lb-ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
            <button id="lb-ann-btn-text"   onclick="lbSetAnnTool('text')"   title="{{ __('viewer.ann_text') }}"   class="lb-ann-tool-btn" style="font-size:13px;font-weight:700;line-height:1;">T</button>
            <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 6px;"></div>
            <span style="font-size:10px;color:var(--color-text-secondary);margin-right:4px;">{{ __('messages.annotation_color') }}</span>
            <button onclick="lbSetAnnColor('#ef4444')" data-lbcolor="#ef4444" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:var(--color-alert-warning-500);border:none;cursor:pointer;padding:0;outline:2px solid #fff;outline-offset:2px;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#f97316')" data-lbcolor="#f97316" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#f97316;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#eab308')" data-lbcolor="#eab308" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#eab308;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#22c55e')" data-lbcolor="#22c55e" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:var(--color-alert-success-500);border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#3b82f6')" data-lbcolor="#3b82f6" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#3b82f6;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <button onclick="lbSetAnnColor('#a855f7')" data-lbcolor="#a855f7" class="lb-ann-color-btn" style="width:16px;height:16px;border-radius:50%;background:#a855f7;border:none;cursor:pointer;padding:0;flex-shrink:0;"></button>
            <div style="flex:1;"></div>
            <span style="font-size:10px;color:#4b5563;">{{ __('messages.annotation_hint') }}</span>
        </div>
        {{-- 본문 (이미지 + 의견) --}}
        <div id="img-lb-body">
            {{-- 이미지 영역 --}}
            <div id="img-lb-image-side">
                <button id="lb-review-tab" onclick="toggleLbReview()" title="{{ __('viewer.panel_toggle_title') }}">
                    <svg id="lb-review-tab-icon" width="12" height="12" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <div id="img-lb-scroll-wrap">
                    <div id="img-lb-inner">
                        <img id="img-lightbox-img" src="" alt="">
                    </div>
                </div>
                <div id="img-lb-zoom-bar">
                    <button class="lb-zoom-btn" onclick="lbImgZoom(-0.25)">−</button>
                    <span id="lb-img-zoom-label">{{ __('messages.zoom_fit') }}</span>
                    <button class="lb-zoom-btn" onclick="lbImgZoom(0.25)">+</button>
                    <div style="width:1px;height:14px;background:rgba(255,255,255,.1);margin:0 2px;"></div>
                    <button class="lb-zoom-btn" onclick="lbImgZoomFit()" style="font-size:11px;">{{ __('messages.zoom_fit') }}</button>
                    <button class="lb-zoom-btn" onclick="lbImgZoomOriginal()" style="font-size:11px;">{{ __('messages.zoom_original') }}</button>
                </div>
                <svg id="img-lb-ann-svg" xmlns="http://www.w3.org/2000/svg"
                     style="position:absolute;z-index:20;pointer-events:none;overflow:visible;"></svg>
                <span id="img-lightbox-name"></span>
            </div>
            {{-- 의견 패널 --}}
            <div id="img-lb-review">
                <div id="img-lb-review-hdr">
                    <span id="img-lb-review-title">{{ __('messages.review_opinion') }}</span>
                    <span id="img-lb-comment-count"></span>
                    <button id="lb-review-collapse-btn" onclick="toggleLbReview()" title="{{ __('viewer.panel_collapse_title') }}">◀</button>
                </div>
                <div id="img-lb-comments">
                    <span id="img-lb-empty" style="display:none;">{{ __('messages.no_opinion_yet') }}</span>
                </div>
                <div id="img-lb-review-form">
                    <textarea id="img-lb-textarea" rows="3" placeholder="{{ __('messages.opinion_placeholder') }}"
                        onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();submitLbComment();}"></textarea>
                    <button id="img-lb-submit" onclick="submitLbComment()">{{ __('common.register') }}</button>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 라이트박스 텍스트 주석 입력 팝업 --}}
<div id="lb-ann-text-popup" style="display:none;position:fixed;z-index:10010;background:#fff;border:2px solid var(--t400);border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);min-width:280px;max-width:360px;">
    <div id="lb-ann-text-popup-title" style="font-size:11px;font-weight:700;color:var(--t700);margin-bottom:8px;">{{ __('messages.ann_text_title') }}</div>
    <textarea id="lb-ann-text-input" rows="4" placeholder="{{ __('messages.ann_text_placeholder') }}"
           style="width:100%;border:1.5px solid var(--color-border-default);border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;line-height:1.5;font-family:inherit;transition:border-color .15s;"
           onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button onclick="lbConfirmAnnText()" style="flex:1;padding:6px 0;background:var(--t600);color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">{{ __('common.confirm') }}</button>
        <button onclick="lbCancelAnnText()" style="flex:1;padding:6px 0;background:var(--color-bg-muted);color:var(--color-text-secondary);border:none;border-radius:6px;font-size:12px;cursor:pointer;">{{ __('common.cancel') }}</button>
    </div>
</div>

{{-- ── 1:1 메시지 모달 ── --}}
<div id="dm-overlay" class="modal-overlay" onclick="closeModal('dm')"></div>
<div id="dm-modal" class="modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <span class="modal-title">{{ __('messages.dm') }}</span>
        <button onclick="closeModal('dm')" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);font-size:22px;line-height:1;">&times;</button>
    </div>
    <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data">
        @csrf
        <div style="margin-bottom:14px;">
            <label class="modal-label">{{ __('messages.receiver') }}</label>
            <select name="receiver_id" required class="modal-input">
                <option value="">{{ __('messages.select_user') }}</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                @endforeach
            </select>
        </div>
        <div style="margin-bottom:14px;">
            <label class="modal-label">{{ __('messages.messages') }}</label>
            <textarea name="body" rows="3" class="modal-input" placeholder="{{ __('messages.message_placeholder') }}"></textarea>
        </div>
        <div style="margin-bottom:18px;">
            <label class="modal-label">{{ __('messages.file_attach_optional') }}</label>
            <input type="file" name="files[]" multiple style="font-size:13px;color:var(--color-text-secondary);">
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeModal('dm')" style="padding:8px 16px;border:1.5px solid var(--t100);border-radius:9px;background:#fff;font-size:13px;font-weight:500;color:var(--color-text-secondary);cursor:pointer;">{{ __('common.cancel') }}</button>
            <button type="submit" style="padding:8px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.1);">{{ __('messages.send_btn') }}</button>
        </div>
    </form>
</div>

{{-- ── 그룹 채팅 모달 ── --}}
<div id="group-overlay" class="modal-overlay" onclick="closeModal('group')"></div>
<div id="group-modal" class="modal-box">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
        <span class="modal-title">{{ __('messages.new_group') }}</span>
        <button onclick="closeModal('group')" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);font-size:22px;line-height:1;">&times;</button>
    </div>
    <form method="POST" action="{{ route('messages.group') }}" enctype="multipart/form-data">
        @csrf
        <div style="margin-bottom:14px;">
            <label class="modal-label">{{ __('messages.group_name') }} <span style="color:var(--color-alert-warning-500);">*</span></label>
            <input type="text" name="name" required class="modal-input" placeholder="{{ __('messages.group_name_placeholder') }}">
        </div>
        <div style="margin-bottom:14px;">
            <label class="modal-label">{{ __('messages.select_members') }} <span style="color:var(--color-alert-warning-500);">*</span></label>
            <div id="member-list">
                @foreach($users as $u)
                    @php $avatarColors = ['#a394f9','#7dd3fc','#6ee7b7','#fcd34d','#f9a8d4','#c4b5fd']; $ac = $avatarColors[$u->id % count($avatarColors)]; @endphp
                    <label class="member-row">
                        <input type="checkbox" name="member_ids[]" value="{{ $u->id }}">
                        <div class="member-avatar-sm" style="background:{{ $ac }};">{{ mb_substr($u->name, 0, 1) }}</div>
                        <div>
                            <div style="font-size:13px;font-weight:500;color:var(--color-text-primary);">{{ $u->name }}</div>
                            <div style="font-size:11px;color:var(--color-text-tertiary);">{{ $u->email }}</div>
                        </div>
                    </label>
                @endforeach
            </div>
            <div id="selected-count" style="font-size:12px;color:var(--t500);margin-top:6px;font-weight:500;"></div>
        </div>
        <div style="margin-bottom:14px;">
            <label class="modal-label">{{ __('messages.first_message') }} <span style="color:var(--color-alert-warning-500);">*</span></label>
            <textarea name="body" rows="3" class="modal-input" placeholder="{{ __('messages.first_message_placeholder') }}"></textarea>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;">
            <button type="button" onclick="closeModal('group')" style="padding:8px 16px;border:1.5px solid var(--t100);border-radius:9px;background:#fff;font-size:13px;font-weight:500;color:var(--color-text-secondary);cursor:pointer;">{{ __('common.cancel') }}</button>
            <button type="submit" style="padding:8px 20px;border:none;border-radius:9px;background:linear-gradient(135deg,var(--t300),var(--t500));color:#fff;font-size:13px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.1);">{{ __('messages.create_group') }}</button>
        </div>
    </form>
</div>

<script>
// ── 그룹 채팅 구성원 팝오버 ─────────────────────────
function toggleMembersPopover(ev) {
    ev?.stopPropagation();
    const pop = document.getElementById('members-popover');
    if (!pop) return;
    const open = pop.style.display === 'flex';
    pop.style.display = open ? 'none' : 'flex';
}
function closeMembersPopover() {
    const pop = document.getElementById('members-popover');
    if (pop) pop.style.display = 'none';
}
// ── 채팅방 초대 ─────────────────────────
function openInviteModal() {
    closeMembersPopover();
    const ov = document.getElementById('invite-overlay');
    const md = document.getElementById('invite-modal');
    if (!ov || !md) return;
    ov.style.display = 'block';
    md.style.display = 'block';
    updateInviteCount();
    setTimeout(() => document.getElementById('invite-search')?.focus(), 50);
}
function closeInviteModal() {
    const ov = document.getElementById('invite-overlay');
    const md = document.getElementById('invite-modal');
    if (ov) ov.style.display = 'none';
    if (md) md.style.display = 'none';
}
function filterInviteCandidates(q) {
    const term = (q || '').toLowerCase().trim();
    document.querySelectorAll('.invite-row').forEach(row => {
        const name  = row.dataset.name  || '';
        const email = row.dataset.email || '';
        row.style.display = (!term || name.includes(term) || email.includes(term)) ? '' : 'none';
    });
}
function updateInviteCount() {
    const checked = document.querySelectorAll('#invite-list input[type=checkbox]:checked').length;
    const el = document.getElementById('invite-selected-count');
    if (el) el.textContent = checked > 0 ? MSG_STR.nSelected.replace(':count', checked) : '';
}
function submitInvite(ev) {
    ev.preventDefault();
    const form = ev.target;
    const ids = Array.from(form.querySelectorAll('input[name="member_ids[]"]:checked')).map(c => c.value);
    if (!ids.length) { alert(MSG_STR.inviteSelectAtLeast); return false; }
    const btn = document.getElementById('invite-submit-btn');
    btn.disabled = true; btn.textContent = MSG_STR.inviting;

    const url = '{{ isset($conversation) ? route("messages.invite", $conversation) : "" }}';
    const fd = new FormData();
    ids.forEach(id => fd.append('member_ids[]', id));
    fetch(url, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,'Accept':'application/json'},
        body: fd,
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        if (!r.ok) { alert(d.message || MSG_STR.inviteFail); btn.disabled = false; btn.textContent = MSG_STR.invite; return; }
        // 페이지 리로드해서 멤버 목록 갱신
        location.reload();
    })
    .catch(() => { alert(MSG_STR.networkError); btn.disabled = false; btn.textContent = MSG_STR.invite; });
    return false;
}

// ── 파일 메시지 → 채팅방 구성원/이메일 수신자에게 메일 발송 ─────────────────────────
let _esfPendingMessageId = null;
let _esfPendingBtn = null;
const _esfEmailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
function _esfParseExtraEmails(raw) {
    if (!raw) return [];
    const seen = new Set(), out = [];
    raw.split(/[,;\n\r\s]+/).map(s => s.trim()).filter(Boolean).forEach(e => {
        const key = e.toLowerCase();
        if (!seen.has(key) && _esfEmailRe.test(e)) { seen.add(key); out.push(e); }
    });
    return out;
}
function _esfUpdateSummary() {
    const memberIds = Array.from(document.querySelectorAll('.esf-member-cb:checked')).map(cb => cb.value);
    const extras = _esfParseExtraEmails(document.getElementById('esf-extra-emails')?.value || '');
    const total = memberIds.length + extras.length;
    const summary = document.getElementById('esf-recipients-summary');
    if (summary) summary.textContent = (MSG_STR.esfSelectedCount || '').replace(':count', total);
    const sendBtn = document.getElementById('esf-send-btn');
    if (sendBtn && !sendBtn.dataset.sending) sendBtn.disabled = (total === 0);
}
function esfToggleAllMembers(checked) {
    document.querySelectorAll('.esf-member-cb:not([disabled])').forEach(cb => { cb.checked = !!checked; });
    _esfUpdateSummary();
}
function shareFileByEmail(messageId, btn) {
    _esfPendingMessageId = messageId;
    _esfPendingBtn = btn || null;

    // 파일명 추출: 같은 .file-with-actions 내 .file-img alt 또는 .file-card 내 텍스트
    let fileName = '';
    const wrap = btn?.closest('.file-with-actions');
    if (wrap) {
        const img = wrap.querySelector('img.file-img');
        if (img) fileName = img.alt || '';
        else {
            const nameEl = wrap.querySelector('.file-card div div');
            if (nameEl) fileName = nameEl.textContent.trim();
        }
    }
    document.getElementById('esf-filename-text').textContent = fileName || MSG_STR.attachedFileFallback;

    // 폼 초기화
    document.querySelectorAll('.esf-member-cb').forEach(cb => { cb.checked = false; });
    const extra = document.getElementById('esf-extra-emails');
    if (extra) extra.value = '';

    const sendBtn = document.getElementById('esf-send-btn');
    delete sendBtn.dataset.sending;
    sendBtn.textContent = MSG_STR.sendEmail;
    _esfUpdateSummary();
    document.getElementById('esf-modal').style.display = 'flex';
}
function esfClose() {
    document.getElementById('esf-modal').style.display = 'none';
    _esfPendingMessageId = null;
    _esfPendingBtn = null;
}
function esfDoSend() {
    const messageId = _esfPendingMessageId;
    if (!messageId) return;
    const userIds = Array.from(document.querySelectorAll('.esf-member-cb:checked')).map(cb => cb.value);
    const extraRaw = document.getElementById('esf-extra-emails')?.value || '';
    const extraEmails = _esfParseExtraEmails(extraRaw);

    if (extraRaw.trim() && extraEmails.length === 0) {
        alert(MSG_STR.esfInvalidEmail || 'Invalid email');
        return;
    }
    if (userIds.length === 0 && extraEmails.length === 0) {
        alert(MSG_STR.esfNoRecipients || 'Select at least one recipient');
        return;
    }

    const sendBtn = document.getElementById('esf-send-btn');
    const sourceBtn = _esfPendingBtn;
    sendBtn.dataset.sending = '1';
    sendBtn.disabled = true; sendBtn.textContent = MSG_STR.sending;
    if (sourceBtn) sourceBtn.disabled = true;

    fetch(`${LB_BASE}/messages/${messageId}/email-file`, {
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]').content,
            'Accept':'application/json',
            'Content-Type':'application/json'
        },
        body: JSON.stringify({ user_ids: userIds, extra_emails: extraEmails })
    })
    .then(async r => {
        const d = await r.json().catch(() => ({}));
        esfClose();
        alert(d.message || (r.ok ? MSG_STR.emailSent : MSG_STR.emailSendFail));
    })
    .catch(() => {
        esfClose();
        alert(MSG_STR.networkError);
    })
    .finally(() => {
        delete sendBtn.dataset.sending;
        if (sourceBtn) sourceBtn.disabled = false;
    });
}
document.addEventListener('change', e => { if (e.target?.classList?.contains('esf-member-cb')) _esfUpdateSummary(); });
document.addEventListener('input',  e => { if (e.target?.id === 'esf-extra-emails') _esfUpdateSummary(); });
// ESC로 다이얼로그 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const m = document.getElementById('esf-modal');
        if (m && m.style.display === 'flex') esfClose();
    }
});

function mentionMemberInComposer(name) {
    closeMembersPopover();
    const ta = document.getElementById('msg-textarea');
    if (!ta || !name) return;
    // 멘션 프리픽스 패턴: 번역 문자열의 :name 위치를 임의 이름으로 치환한 정규식
    const escapeRe = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const prefixRe = new RegExp('^' + escapeRe(MSG_STR.mentionPrefix).replace(':name', '[^\\n:]+?'));
    const body = ta.value.replace(prefixRe, '');
    const prefix = MSG_STR.mentionPrefix.replace(':name', name);
    ta.value = prefix + body;
    ta.focus();
    const caret = ta.value.length;
    ta.setSelectionRange(caret, caret);
    ta.style.height = 'auto';
    ta.style.height = Math.min(ta.scrollHeight, 120) + 'px';
}
document.addEventListener('click', function(e) {
    const pop = document.getElementById('members-popover');
    const btn = document.getElementById('group-tag-btn');
    if (!pop || pop.style.display !== 'flex') return;
    if (pop.contains(e.target) || (btn && btn.contains(e.target))) return;
    pop.style.display = 'none';
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMembersPopover();
});

const LB_BASE  = '{{ rtrim(url("/"), "/") }}';
const STR_READ = '{{ __("messages.read") }}';
const STR_ME   = '{{ __("messages.me") }}';
const MSG_LOCALE = '{{ app()->getLocale() }}';
const MSG_STR = {
    leaveConvConfirm:     '{{ __("messages.leave_conv_confirm") }}',
    aiAnalyzing:          '{{ __("messages.ai_analyzing") }}',
    aiError:              '{{ __("messages.ai_error") }}',
    aiNetworkError:       '{{ __("messages.ai_network_error") }}',
    aiLabelSummary:       '{{ __("messages.ai_label_summary") }}',
    aiLabelIntent:        '{{ __("messages.ai_label_intent") }}',
    aiLabelTone:          '{{ __("messages.ai_label_tone") }}',
    aiLabelKeywords:      '{{ __("messages.ai_label_keywords") }}',
    aiLabelContext:       '{{ __("messages.ai_label_context") }}',
    aiActionHeading:      @js(__('messages.ai_action_heading')),
    aiActionCreate:       @js(__('messages.ai_action_create')),
    aiActionCreated:      @js(__('messages.ai_action_created')),
    aiActionCreating:     @js(__('messages.ai_action_creating')),
    aiActionEmpty:        @js(__('messages.ai_action_empty')),
    aiActionDue:          @js(__('messages.ai_action_due')),
    aiActionAssignee:     @js(__('messages.ai_action_assignee')),
    aiActionPromoteFail:  @js(__('messages.ai_action_promote_fail')),
    lbLoading:            '{{ __("messages.lb_loading") }}',
    lbDeleteTitle:        '{{ __("messages.lb_delete_title") }}',
    lbCountSuffix:        '{{ __("messages.lb_count_suffix") }}',
    lbFullscreen:         @js(__('files.fullscreen')),
    lbFullscreenExit:     @js(__('files.fullscreen_exit')),
    noOpinionYet:         '{{ __("messages.no_opinion_yet") }}',
    confirmDeleteOpinion: '{{ __("viewer.confirm_delete_opinion") }}',
    confirmDeleteAnn:     '{{ __("viewer.confirm_delete_ann") }}',
    annTextTitle:         '{{ __("viewer.ann_text_title") }}',
    annTextEdit:          '{{ __("viewer.ann_text_edit") }}',
    zoomFit:              '{{ __("messages.zoom_fit") }}',
    inviteSelectAtLeast:  @json(__('messages.invite_select_at_least')),
    inviting:             @json(__('messages.inviting')),
    inviteFail:           @json(__('messages.invite_fail')),
    networkError:         @json(__('messages.ai_network_error')),
    attachedFileFallback: @json(__('messages.attached_file_fallback')),
    sendEmail:            @json(__('messages.send_email')),
    sending:              @json(__('messages.sending')),
    emailSent:            @json(__('messages.email_sent')),
    emailSendFail:        @json(__('messages.email_send_fail')),
    esfNoRecipients:      @json(__('messages.esf_no_recipients')),
    esfInvalidEmail:      @json(__('messages.esf_invalid_email')),
    esfSelectedCount:     @json(__('messages.esf_selected_count', ['count' => ':count'])),
    invite:               @json(__('messages.invite')),
    nSelected:            @json(__('messages.n_selected', ['count' => ':count'])),
    mentionPrefix:        @json(__('messages.mention_prefix', ['name' => ':name'])),
    translateFail:        @json(__('messages.translate_fail')),
    translateReqError:    @json(__('messages.translate_req_error')),
};
function fmtDate(d) {
    const y = d.getFullYear(), m = d.getMonth() + 1, day = d.getDate();
    if (MSG_LOCALE === 'ko') return `${y}년 ${m}월 ${day}일`;
    const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    return `${months[d.getMonth()]} ${day}, ${y}`;
}
const EMOJI_CATS = {
    recent:   '{{ __("messages.emoji_cat_recent") }}',
    face:     '{{ __("messages.emoji_cat_face") }}',
    hand:     '{{ __("messages.emoji_cat_hand") }}',
    heart:    '{{ __("messages.emoji_cat_heart") }}',
    animal:   '{{ __("messages.emoji_cat_animal") }}',
    food:     '{{ __("messages.emoji_cat_food") }}',
    activity: '{{ __("messages.emoji_cat_activity") }}',
    travel:   '{{ __("messages.emoji_cat_travel") }}',
    object:   '{{ __("messages.emoji_cat_object") }}',
};
const STR_EMOJI_NO_RECENT    = '{{ __("messages.emoji_no_recent") }}';
const STR_EMOJI_SEARCH_EMPTY = '{{ __("messages.emoji_search_empty") }}';
// ── 모달 ────────────────────────────────────────────────────
async function openNewModal(type) {
    document.getElementById(type+'-overlay').style.display = 'block';
    document.getElementById(type+'-modal').style.display   = 'block';
}
async function closeModal(type) {
    document.getElementById(type+'-overlay').style.display = 'none';
    document.getElementById(type+'-modal').style.display   = 'none';
}

// member selection count
document.querySelectorAll('#member-list input[type=checkbox]').forEach(cb => {
    cb.addEventListener('change', () => {
        const n = document.querySelectorAll('#member-list input:checked').length;
        const STR_N_SELECTED = '{{ __("messages.n_selected", ["count" => "__N__"]) }}';
        document.getElementById('selected-count').textContent = n ? STR_N_SELECTED.replace('__N__', n) : '';
        document.getElementById('selected-count').textContent = n ? '{{ __("messages.n_selected", ["count" => " "]) }}'.replace(' ', n) : '';
    });
});

// ── 번역 언어 선택기 ──────────────────────────────────────
let chatTranslateLang  = '';
let chatTranslateLabel = '';

async function toggleTranslatePicker(e) {
    e.stopPropagation();
    document.getElementById('translate-lang-picker').classList.toggle('open');
}
async function setTranslateLang(btn) {
    chatTranslateLang  = btn.dataset.lang;
    chatTranslateLabel = btn.dataset.label;
    document.getElementById('translate-lang-label').textContent = chatTranslateLabel || '{{ __('messages.translate_label') }}';
    const langBtn = document.getElementById('translate-lang-btn');
    langBtn.classList.toggle('active', !!chatTranslateLang);
    document.querySelectorAll('.tlp-item').forEach(b => b.classList.toggle('selected', b === btn));
    document.getElementById('translate-lang-picker').classList.remove('open');
}
document.addEventListener('click', () => {
    document.getElementById('translate-lang-picker')?.classList.remove('open');
});


async function showTranslateError(msg) {
    const area = document.getElementById('chat-input-area');
    let toast = document.getElementById('translate-error-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'translate-error-toast';
        toast.style.cssText = 'margin-bottom:6px;padding:6px 12px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;color:#dc2626;font-size:12px;display:flex;align-items:center;gap:6px;';
        area.insertBefore(toast, area.firstChild);
    }
    toast.innerHTML = '<svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>' + msg + ' <button onclick="this.parentElement.remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:var(--color-alert-warning-500);font-size:14px;line-height:1;padding:0;">&times;</button>';
    clearTimeout(toast._timer);
    toast._timer = setTimeout(() => toast.remove(), 8000);
}

// ── 파일 처리 ─────────────────────────────────────────────
async function submitForm() {
    @if(isset($conversation))
    const form      = document.getElementById('chat-form');
    const textarea  = document.getElementById('msg-textarea');
    const body      = textarea.value.trim();
    const hasFile   = _pendingFiles.length > 0;
    if (!body && !hasFile) return;

    let finalBody      = body;
    let translatedBody = null;
    let translateLang  = null;
    if (chatTranslateLang && body) {
        // 번역 중 로딩 표시
        const sendBtn  = document.getElementById('send-btn');
        const sendIcon = document.getElementById('send-btn-icon');
        const sendSpin = document.getElementById('send-btn-spin');
        if (sendBtn) { sendBtn.disabled = true; sendIcon.style.display = 'none'; sendSpin.style.display = ''; }
        let translateOk = false;
        try {
            const tResp = await fetch('{{ route('translate') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ text: body, target: chatTranslateLang }),
            });
            const tData = await tResp.json();
            if (tData.ok && tData.translated) {
                translatedBody = tData.translated;
                translateLang  = chatTranslateLang;
                translateOk    = true;
            } else {
                console.error('[번역 실패]', tData.error || 'unknown error');
                showTranslateError(tData.error || MSG_STR.translateFail);
            }
        } catch(e) {
            console.error('[번역 요청 오류]', e);
            showTranslateError(MSG_STR.translateReqError);
        } finally {
            if (sendBtn) { sendBtn.disabled = false; sendIcon.style.display = ''; sendSpin.style.display = 'none'; }
        }
        if (!translateOk) return;
    }

    const fd = new FormData(form);
    fd.set('body', finalBody);
    if (translatedBody) {
        fd.set('translated_body', translatedBody);
        fd.set('translate_lang', translateLang);
    }

    fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        body: fd,
    })
    .then(r => { if (!r.ok) throw r; return r.json(); })
    .then(() => {
        const now    = new Date();
        const isoNow = now.toISOString();

        // 답글 상태를 clearReply() 전에 캡처
        const replyIdRaw  = document.getElementById('reply-to-id-input').value;
        const replyToId   = replyIdRaw ? parseInt(replyIdRaw, 10) : null;
        const replyToName = document.getElementById('reply-preview-name').textContent || '';
        const replyToBody = document.getElementById('reply-preview-text').textContent || '';

        const files = _pendingFiles.slice();
        const base  = {
            sender_id: MY_ID, sender_name: STR_ME,
            file_size: null, file_path: null, file_name: null,
            is_image: false, file_url: null, formatted_size: '',
            created_at: isoNow, created_at_iso: isoNow, date: isoNow.slice(0,10),
            date_label: fmtDate(now), reply_to_file_name: null,
        };

        if (!files.length) {
            renderMessage({ ...base, body: finalBody,
                reply_to_id: replyToId, reply_to_sender: replyToName, reply_to_body: replyToBody,
                translated_body: translatedBody, translate_lang: translateLang });
        } else {
            // 파일 1건당 메시지 1건 — 본문·답글·번역은 첫 메시지에만
            files.forEach((pf, i) => {
                const first = i === 0;
                renderMessage({ ...base,
                    body: first ? finalBody : '',
                    file_path: '_local_',
                    file_name: pf.file.name,
                    is_image: !!pf.dataUrl,
                    file_url: pf.dataUrl || null,
                    formatted_size: _fmtSize(pf.file.size),
                    reply_to_id:     first ? replyToId : null,
                    reply_to_sender: first ? replyToName : '',
                    reply_to_body:   first ? replyToBody : '',
                    translated_body: first ? translatedBody : null,
                    translate_lang:  first ? translateLang : null,
                });
            });
        }
        textarea.value = '';
        textarea.style.height = 'auto';
        clearFile();
        clearReply();
    })
    .catch(() => { form.submit(); }); // fallback
    @else
    document.getElementById('chat-form')?.submit();
    @endif
}

// ── 멀티 파일 첨부 ────────────────────────────────────────
let _pendingFiles = [];          // [{ file, dataUrl }]
const MAX_FILES = 10;

function _escFile(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}
function _fmtSize(bytes) {
    const kb = bytes / 1024;
    return kb >= 1024 ? (kb/1024).toFixed(1)+' MB' : Math.round(kb)+' KB';
}
function _syncFileInput() {
    const dt = new DataTransfer();
    _pendingFiles.forEach(pf => dt.items.add(pf.file));
    const input = document.getElementById('file-input');
    if (input) input.files = dt.files;
}
function _addFiles(fileList) {
    let over = false;
    Array.from(fileList || []).forEach(file => {
        if (_pendingFiles.length >= MAX_FILES) { over = true; return; }
        const pf = { file, dataUrl: null };
        _pendingFiles.push(pf);
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = e => { pf.dataUrl = e.target.result; renderFilePreviews(); };
            reader.readAsDataURL(file);
        }
    });
    if (over) alert('첨부파일은 최대 ' + MAX_FILES + '개까지 가능합니다.');
    _syncFileInput();
    renderFilePreviews();
}
function renderFilePreviews() {
    const bar = document.getElementById('file-preview-bar');
    if (!bar) return;
    if (!_pendingFiles.length) { bar.style.display = 'none'; bar.innerHTML = ''; return; }
    bar.style.display = 'flex';
    bar.innerHTML = _pendingFiles.map((pf, i) => {
        const visual = (pf.file.type.startsWith('image/') && pf.dataUrl)
            ? `<img class="file-chip-thumb" src="${pf.dataUrl}" alt="">`
            : `<svg class="file-chip-icon" width="15" height="15" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>`;
        return `<div class="file-chip">
            ${visual}
            <span class="file-chip-name">${_escFile(pf.file.name)}</span>
            <span class="file-chip-size">${_fmtSize(pf.file.size)}</span>
            <button type="button" class="file-chip-x" onclick="removePendingFile(${i})" title="제거">&times;</button>
        </div>`;
    }).join('');
}
function removePendingFile(idx) {
    _pendingFiles.splice(idx, 1);
    _syncFileInput();
    renderFilePreviews();
}
function onFileSelect(input) {
    _addFiles(input.files);
}
function clearFile() {
    _pendingFiles = [];
    const input = document.getElementById('file-input');
    if (input) input.value = '';
    renderFilePreviews();
}

// ── 우클릭 컨텍스트 메뉴 ──────────────────────────────────
const ctxMenu = document.getElementById('msg-ctx-menu');
let ctxTargetWrap = null; // 현재 우클릭된 msg-bubble-wrap

document.getElementById('chat-messages')?.addEventListener('contextmenu', async function(e) {
    const wrap = e.target.closest('.msg-bubble-wrap');
    if (!wrap) return;
    e.preventDefault();

    ctxTargetWrap = wrap;

    // 복사 항목: 텍스트 없는 파일 전용 메시지면 숨김
    const hasText = !!(wrap.dataset.msgBody && wrap.dataset.msgBody.trim());
    document.getElementById('ctx-copy-btn').style.display = hasText ? '' : 'none';

    // 위치 결정 (뷰포트 경계 체크)
    const x = Math.min(e.clientX, window.innerWidth  - 175);
    const y = Math.min(e.clientY, window.innerHeight - 90);
    ctxMenu.style.left    = x + 'px';
    ctxMenu.style.top     = y + 'px';
    ctxMenu.style.display = 'block';
});

document.addEventListener('click',       () => ctxMenu.style.display = 'none');
document.addEventListener('contextmenu', (e) => {
    if (!e.target.closest('.msg-bubble-wrap')) ctxMenu.style.display = 'none';
});
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') ctxMenu.style.display = 'none'; });

async function ctxReply() {
    ctxMenu.style.display = 'none';
    if (!ctxTargetWrap) return;
    const msgId     = ctxTargetWrap.dataset.msgId;
    const sender    = ctxTargetWrap.dataset.msgSender || '';
    const body      = ctxTargetWrap.dataset.msgBody   || '';

    document.getElementById('reply-to-id-input').value = msgId;
    document.getElementById('reply-preview-name').textContent = sender;
    document.getElementById('reply-preview-text').textContent = body;
    document.getElementById('reply-preview-bar').style.display = 'flex';
    document.getElementById('msg-textarea')?.focus();
}

async function ctxCopy() {
    ctxMenu.style.display = 'none';
    if (!ctxTargetWrap) return;
    const body = ctxTargetWrap.dataset.msgBody || '';
    navigator.clipboard.writeText(body).catch(() => {});
}

async function clearReply() {
    document.getElementById('reply-to-id-input').value = '';
    document.getElementById('reply-preview-bar').style.display = 'none';
    document.getElementById('reply-preview-name').textContent = '';
    document.getElementById('reply-preview-text').textContent = '';
}

// ── 붙여넣기 ──────────────────────────────────────────────
function handlePaste(e) {
    // paste 리스너가 textarea·inputArea 양쪽에 있어 버블링으로 두 번 호출됨 → 중복 방지
    if (e._swPasteHandled) return;
    e._swPasteHandled = true;
    const items = e.clipboardData?.items;
    if (!items) return;
    const collected = [];
    for (const item of items) {
        if (item.kind !== 'file') continue;
        const raw = item.getAsFile();
        if (!raw) continue;
        const ext  = raw.type ? ((raw.type.split('/')[1] || 'bin').replace('jpeg','jpg')) : 'bin';
        const name = (raw.name && raw.name !== 'image.png') ? raw.name
                   : `paste-${Date.now()}-${collected.length}.${ext}`;
        collected.push(name === raw.name ? raw : new File([raw], name, { type: raw.type }));
    }
    if (collected.length) {
        e.preventDefault();
        _addFiles(collected);   // 멀티 파일 붙여넣기 지원
    }
}
const textarea  = document.getElementById('msg-textarea');
const inputArea = document.getElementById('chat-input-area');
if (textarea)  textarea.addEventListener('paste', handlePaste);
if (inputArea) inputArea.addEventListener('paste', handlePaste);

// ── 대화 목록 검색 ────────────────────────────────────────
async function filterConvs(q) {
    q = q.toLowerCase();
    document.querySelectorAll('.conv-item').forEach(el => {
        el.style.display = el.dataset.name.includes(q) ? '' : 'none';
    });
}

// ── 대화 나가기 ───────────────────────────────────────────
async function leaveConv(e, convId) {
    e.preventDefault();
    e.stopPropagation();
    if (!await __confirm(MSG_STR.leaveConvConfirm)) return;

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    fetch(`/messages/${convId}/leave`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        const anchor = document.querySelector(`.conv-item[data-conv-id="${convId}"]`);
        const item   = anchor?.closest('.conv-item-wrap') || anchor;
        if (item) item.remove();
        if (window.OPEN_CONV_ID === convId) {
            window.location.href = '{{ route("messages.index") }}';
        }
    })
    .catch(() => { window.location.reload(); });
}

// ── 이모티콘 → 이모지 변환 ───────────────────────────────
const EMOTICON_MAP = [
    [/:-?\)/g,  '😊'], [/;-?\)/g,  '😉'],
    [/:-?\(/g,  '😢'], [/:-?D\b/g, '😄'],
    [/:-?P\b/gi,'😛'], [/:-?O\b/gi,'😮'],
    [/\^[\^_]\^/g,'😊'],[/<3\b/g,  '❤️'],
    [/XD\b/gi,  '😆'], [/>_</g,    '😣'],
    [/T[._]T/g, '😢'],
];
function convertEmoticons(text) {
    return EMOTICON_MAP.reduce((s, [re, emoji]) => s.replace(re, emoji), text);
}
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function linkify(text) {
    return escHtml(text).replace(/(https?:\/\/[^\s<>"']+)/g, url =>
        `<a href="${url}" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">${url}</a>`
    );
}
function applyEmoticonsToEl(el) {
    el.childNodes.forEach(n => {
        if (n.nodeType === Node.TEXT_NODE) n.textContent = convertEmoticons(n.textContent);
    });
}

// ── 스크롤 ────────────────────────────────────────────────
const cm = document.getElementById('chat-messages');
if (cm) {
    cm.querySelectorAll('.msg-bubble').forEach(applyEmoticonsToEl);
    cm.scrollTop = cm.scrollHeight;
}
// 대화 목록 프리뷰에도 이모티콘 적용
document.querySelectorAll('.conv-preview').forEach(applyEmoticonsToEl);

// ── 실시간 메시지 수신 ────────────────────────────────────
const MY_ID    = window.MY_ID;
const COLORS   = ['#a394f9','#7dd3fc','#6ee7b7','#fcd34d','#f9a8d4','#c4b5fd'];
window.OPEN_CONV_ID = @isset($conversation){{ $conversation->id }}@else null @endisset;
const OPEN_CONV_ID  = window.OPEN_CONV_ID;

// 현재 열린 대화 전용 변수 & 함수
@if(isset($conversation))
const IS_GROUP   = {{ $conversation->is_group ? 'true' : 'false' }};
const CONV_ID    = {{ $conversation->id }};
const TOTAL_OTHERS = {{ $otherParticipants->count() }};
// 참여자별 last_read_at 초기값 (ISO 문자열 또는 null)
const participantReadAt = @json($participantReadAt->map(fn($v) => $v ? \Carbon\Carbon::parse($v)->toIso8601String() : null));

let lastDate = null;
(async function(){
    const ds = document.querySelectorAll('#chat-messages [data-date]');
    if (ds.length) lastDate = ds[ds.length-1].dataset.date;
})();

// ── 읽음 상태 업데이트 ────────────────────────────────────
async function updateReadReceipts(readerId, readAt) {
    participantReadAt[readerId] = readAt;
    const readTs = new Date(readAt).getTime();

    document.querySelectorAll('#chat-messages .msg-row[data-msg-at]').forEach(row => {
        if (!row.classList.contains('mine')) return;
        const msgTs = new Date(row.dataset.msgAt).getTime();
        const badge = row.querySelector('[data-read-badge]');

        if (IS_GROUP) {
            // 읽은 인원 수 재계산
            const readCount = Object.entries(participantReadAt)
                .filter(([uid, at]) => parseInt(uid) !== MY_ID && at && new Date(at).getTime() >= msgTs)
                .length;
            const unread = TOTAL_OTHERS - readCount;
            if (!badge) {
                // 뱃지가 없던 메시지에 추가
                const timeWrap = row.querySelector('div[style*="flex-direction:column"]');
                if (timeWrap && unread < TOTAL_OTHERS) {
                    const span = document.createElement('span');
                    span.className = 'msg-read' + (unread > 0 ? ' unread' : '');
                    span.dataset.readBadge = '';
                    span.textContent = unread > 0 ? String(unread) : STR_READ;
                    timeWrap.insertBefore(span, timeWrap.firstChild);
                }
            } else {
                badge.textContent = unread > 0 ? String(unread) : STR_READ;
                badge.className   = 'msg-read' + (unread > 0 ? ' unread' : '');
            }
        } else {
            // 1:1: 상대방(유일한 other)이 읽었는지
            const isRead = readTs >= msgTs;
            if (!badge) {
                if (isRead) {
                    const timeWrap = row.querySelector('div[style*="flex-direction:column"]');
                    if (timeWrap) {
                        const span = document.createElement('span');
                        span.className = 'msg-read';
                        span.dataset.readBadge = '';
                        span.textContent = STR_READ;
                        timeWrap.insertBefore(span, timeWrap.firstChild);
                    }
                }
            } else {
                if (isRead) {
                    badge.textContent = STR_READ;
                    badge.className   = 'msg-read';
                }
            }
        }
    });
}

function fmtTime(val) {
    if (!val) return '';
    const d = new Date(val);
    if (isNaN(d)) return val;
    const H = String(d.getHours()).padStart(2,'0'), M = String(d.getMinutes()).padStart(2,'0');
    return fmtDate(d) + ' ' + H + ':' + M;
}

async function renderMessage(data) {
    const cm = document.getElementById('chat-messages');
    if (!cm) return;
    const isMine  = data.sender_id === MY_ID;
    const isReply = !!data.reply_to_id;

    // 답글 → 부모 말풍선 내부에 compact item 추가
    if (isReply) {
        const pid = String(data.reply_to_id);
        const parentWrap = cm.querySelector(`.msg-bubble-wrap[data-msg-id="${pid}"]`);
        if (parentWrap) {
            const bubble = parentWrap.querySelector('.msg-bubble');
            if (bubble) {
                let repliesDiv = bubble.querySelector('.bubble-replies');
                if (!repliesDiv) {
                    repliesDiv = document.createElement('div');
                    repliesDiv.className = 'bubble-replies';
                    repliesDiv.id = 'breply-' + pid;
                    bubble.appendChild(repliesDiv);
                }
                const senderName = isMine ? STR_ME : (data.sender_name || '');
                const bodyText   = data.body || (data.file_name ? '📎 ' + data.file_name : '');
                const d = new Date(data.created_at_iso || data.created_at);
                const timeShort  = isNaN(d) ? '' : `${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
                const item = document.createElement('div');
                item.className = 'bubble-reply-item' + (isMine ? ' mine' : '');
                if (data.id) item.dataset.replyId = data.id;
                item.innerHTML = `<span class="bubble-reply-sender">${senderName}</span><span class="bubble-reply-time">${timeShort}</span><span class="bubble-reply-body">${convertEmoticons(bodyText).replace(/</g,'&lt;')}</span>`;
                repliesDiv.appendChild(item);
                parentWrap.closest('.msg-row')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        return;
    }

    // 날짜 구분선
    if (data.date !== lastDate) {
        lastDate = data.date;
        const d = document.createElement('div');
        d.style.textAlign = 'center'; d.dataset.date = data.date;
        d.innerHTML = `<span style="display:inline-block;font-size:11.5px;color:var(--color-text-tertiary);background:var(--t100);padding:3px 12px;border-radius:20px;">${data.date_label}</span>`;
        cm.insertBefore(d, document.getElementById('bottom'));
    }

    let fileHtml = '';
    if (data.file_path) {
        if (data.is_image) {
            fileHtml = `<img src="${data.file_url}" alt="${data.file_name}" class="file-img" data-msg-id="${data.id||''}" onclick="openLightbox(this.src,this.alt,${data.id||'null'})">`;
        } else {
            fileHtml = `<a href="${data.file_url}" download="${data.file_name}" class="file-card ${isMine?'mine':'theirs'}">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <div><div style="font-size:12.5px;font-weight:600;">${data.file_name}</div><div style="font-size:11px;opacity:.7;">${data.formatted_size}</div></div>
            </a>`;
        }
    }
    const nameHtml   = !isMine ? `<div class="msg-name">${data.sender_name}</div>` : '';
    const avatarHtml = !isMine ? `<div class="msg-avatar" style="background:${COLORS[data.sender_id%COLORS.length]};">${data.sender_name.charAt(0)}</div>` : '';

    // 읽음 뱃지
    let readBadgeHtml = '';
    if (isMine) {
        const msgTs = new Date(data.created_at_iso || data.created_at).getTime();
        let label = '';
        if (IS_GROUP) {
            const readCount = Object.entries(participantReadAt)
                .filter(([uid, at]) => parseInt(uid) !== MY_ID && at && new Date(at).getTime() >= msgTs).length;
            const unread = TOTAL_OTHERS - readCount;
            label = unread > 0 ? String(unread) : STR_READ;
        } else {
            const otherId = Object.keys(participantReadAt).find(id => parseInt(id) !== MY_ID);
            const otherAt = otherId ? participantReadAt[otherId] : null;
            label = (otherAt && new Date(otherAt).getTime() >= msgTs) ? STR_READ : '1';
        }
        readBadgeHtml = `<span class="msg-read ${label === STR_READ ? '' : 'unread'}" data-read-badge>${label}</span>`;
    }

    const displayTime = fmtTime(data.created_at_iso || data.created_at);
    const timeWrap = isMine
        ? `<div style="display:flex;flex-direction:column;align-items:flex-end;justify-content:flex-end;gap:4px;padding-bottom:2px;">${readBadgeHtml}<span class="msg-time">${displayTime}</span></div>`
        : `<span class="msg-time">${displayTime}</span>`;

    const LANG_NAMES = { ko:'한국어', en:'English', ja:'日本語', zh:'中文' };
    const hasTranslation = !!(data.translated_body && data.translate_lang);
    // 수신자이고 번역본이 있으면 항상 번역본 표시 (발신자가 명시적으로 선택한 번역 언어 우선)
    const shouldShowTranslation = !isMine && hasTranslation;
    const displayBody   = shouldShowTranslation ? data.translated_body : data.body;
    const msgPreview    = (shouldShowTranslation ? data.translated_body : data.body || (data.file_name ? '📎 '+data.file_name : '')).slice(0, 80);
    const bodyHtml      = displayBody ? `<div style="white-space:pre-wrap;word-break:break-word;">${linkify(convertEmoticons(displayBody))}</div>` : '';

    let translationHtml = '';
    if (hasTranslation) {
        if (isMine) {
            translationHtml = `<div class="msg-translated-badge">🌐${LANG_NAMES[data.translate_lang] || data.translate_lang}</div>`;
        } else if (data.body && data.translate_lang !== MSG_LOCALE) {
            const origEscaped = convertEmoticons(data.body).replace(/</g, '&lt;');
            translationHtml = `<div class="msg-original-note"><span class="msg-original-label">{{ __('messages.original_text') }}</span>${origEscaped}</div>`;
        }
    }

    const wrapAttrs  = `data-msg-id="${data.id||''}" data-msg-body="${msgPreview.replace(/"/g,'&quot;')}" data-msg-sender="${(data.sender_name||STR_ME).replace(/"/g,'&quot;')}"`;
    const pdaBtn     = `<button class="msg-pda-btn" type="button" data-msg-id="${data.id||''}" data-pda-id="" onclick="pdaOpenFromMessage(this)"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> ${PDA_MSG.nav}</button>`;
    const aiBtn      = `<button class="msg-ai-btn" type="button" onclick="analyzeMsg(${data.id||'null'},this)"><svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg> {{ __('messages.ai_analysis') }}</button>`;
    const btnGroup   = `<div class="msg-btn-group">${pdaBtn}${aiBtn}</div>`;

    const row = document.createElement('div');
    row.className     = `msg-row${isMine?' mine':''}`;
    if (data.id) row.id = `message-${data.id}`;
    row.dataset.msgId = data.id || '';
    row.dataset.msgAt = data.created_at_iso || data.created_at || '';
    row.innerHTML = `${avatarHtml}<div style="max-width:70%;">${nameHtml}<div class="msg-bubble-wrap" ${wrapAttrs}><div class="msg-bubble ${isMine?'mine':'theirs'}">${bodyHtml}${translationHtml}${fileHtml}</div>${btnGroup}</div></div>${timeWrap}`;

    cm.insertBefore(row, document.getElementById('bottom'));
    cm.scrollTop = cm.scrollHeight;
}

// ── ConversationRead 수신 ────────────────────────────────
async function setupReadReceipt() {
    window.Echo.private('conversation.' + CONV_ID)
        .listen('.ConversationRead', async function(data) {
            if (data.reader_id === MY_ID) return; // 내가 읽은 건 무시
            updateReadReceipts(data.reader_id, data.read_at);
        });
}
if (window.Echo) { setupReadReceipt(); }
else { window.addEventListener('echoReady', setupReadReceipt, { once: true }); }
@endif

// ── 대화 목록 뱃지 & 미리보기 ────────────────────────────
async function updateConvBadge(convId, delta) {
    const el = document.querySelector(`[data-badge-conv="${convId}"]`);
    if (!el) return;
    const next = Math.max(0, (parseInt(el.textContent) || 0) + delta);
    el.textContent = next || ''; el.style.display = next > 0 ? 'inline-block' : 'none';
}
async function updateConvPreview(convId, senderName, body, fileName) {
    const el = document.querySelector(`[data-preview-conv="${convId}"]`);
    if (!el) return;
    const isGrp = document.querySelector(`[data-conv-id="${convId}"]`)?.dataset.group === '1';
    const text  = (isGrp && senderName ? senderName + ': ' : '') + (body ? convertEmoticons(body) : (fileName ? `📎 ${fileName}` : ''));
    el.textContent = text;
}

// ── 전역 Echo에서 newChatMessage 수신 ────────────────────
window.addEventListener('newChatMessage', async function(e) {
    const { cid, data } = e.detail;
    if (cid === OPEN_CONV_ID) {
        if (typeof renderMessage === 'function') {
            // ISO 문자열을 created_at_iso로도 전달 (읽음 계산용)
            if (!data.created_at_iso) data.created_at_iso = data.created_at;
            renderMessage(data);
        }
    } else {
        updateConvBadge(cid, +1);
        // 수신자 입장: 번역문이 있으면 번역문으로 미리보기
        const previewBody = (data.sender_id !== MY_ID && data.translated_body) ? data.translated_body : data.body;
        updateConvPreview(cid, data.sender_name, previewBody, data.file_name);
    }
});

// ── 이모지 피커 ───────────────────────────────────────────
(async function() {
    // 카테고리 정의: [아이콘 SVG path, 레이블, 이모지 배열]
    const CATS = [
        {
            id: 'recent', label: EMOJI_CATS.recent,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            list: []
        },
        {
            id: 'face', label: EMOJI_CATS.face,
            icon: '<circle cx="12" cy="12" r="10"/><path d="M8 13s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
            list: ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','🙃','😉','😊','😇','🥰','😍','🤩','😘','😗','😙','🥲','😋','😛','😜','🤪','😝','🤑','🤗','🤭','🤫','🤔','🤐','🤨','😐','😑','😶','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤧','🥵','🥶','🥴','😵','🤯','😎','🥸','🤓','🧐','😕','😟','🙁','☹️','😮','😲','😳','🥺','😦','😧','😨','😰','😥','😢','😭','😱','😖','😣','😞','😓','😩','😫','🥱','😤','😡','😠','🤬','😈','👿','💀','☠️','💩','🤡','👹','👺','👻','👽','👾','🤖']
        },
        {
            id: 'hand', label: EMOJI_CATS.hand,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/>',
            list: ['👋','🤚','🖐️','✋','🖖','👌','🤌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦵','🦶','👂','🦻','👃','👀','👁️','👅','👄']
        },
        {
            id: 'heart', label: EMOJI_CATS.heart,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
            list: ['❤️','🧡','💛','💚','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💟','💯','💢','💥','💫','💦','💨','🕳️','💬','💭','🗯️','💤','🔥','✨','🌟','⭐','🌠','💎','🏆','🎯','🎉','🎊','🎈','🎁','🎀']
        },
        {
            id: 'animal', label: EMOJI_CATS.animal,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>',
            list: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🦋','🐌','🐞','🐜','🐢','🦎','🐍','🐙','🦑','🐬','🐳','🐋','🦈','🐊','🦁','🦒','🦘','🐘','🦛','🦏','🐪','🐫','🦙','🦌','🐕','🐩','🐈','🐓','🦃','🦚','🦜','🦢','🦩','🕊️','🐇','🐁','🐀','🐿️','🦔','🌵','🌲','🌳','🌴','🌾','🌿','🍀','🍁','🍂','🍃','🌺','🌸','🌼','🌻','🌹','🌷','🍄','🌰','🦀','🐡','🐟','🐠']
        },
        {
            id: 'food', label: EMOJI_CATS.food,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
            list: ['🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🧄','🧅','🥔','🥐','🍞','🥖','🧀','🥚','🍳','🧈','🥞','🥓','🍗','🍖','🌭','🍔','🍟','🍕','🥪','🧆','🌮','🌯','🍝','🍜','🍲','🍛','🍣','🍱','🥟','🍤','🍙','🍚','🍣','🧁','🍰','🎂','🍮','🍭','🍬','🍫','🍿','🍩','🍪','🌰','🥜','🍯','🧃','🥤','🧋','☕','🍵','🫖','🍺','🍻','🥂','🍷','🥃','🍸','🍹','🍾']
        },
        {
            id: 'activity', label: EMOJI_CATS.activity,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            list: ['⚽','🏀','🏈','⚾','🥎','🎾','🏐','🏉','🎱','🏓','🏸','🥊','🥋','⛳','🎣','🤿','🎿','⛷️','🏂','🏋️','🧘','🏄','🏊','🚵','🚴','🏆','🥇','🥈','🥉','🏅','🎖️','🎭','🩰','🎨','🎬','🎤','🎧','🎼','🎹','🥁','🎷','🎺','🎸','🎻','🎲','♟️','🎯','🎳','🎮','🎰','🧩']
        },
        {
            id: 'travel', label: EMOJI_CATS.travel,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
            list: ['🚗','🚕','🚙','🚌','🏎️','🚓','🚑','🚒','🚚','🚜','🏍️','🛵','🚲','🛴','✈️','🛩️','🚀','🛸','🚁','⛵','🚢','🚂','🏔️','🌋','🗺️','🏝️','🏜️','🏟️','🏛️','🕌','⛪','🕍','🏗️','🏘️','🏚️','🏠','🏡','🏢','🏣','🏤','🏥','🏦','🏨','🏩','🏪','🏫','🏬','🏭','🗼','🗽','🗿','⛽','🛞','🚧','⚓','🗺️','🧭','🌍','🌎','🌏','🌐','🌕','⭐','🌟','🌠','⛅','🌈','❄️','☃️','⛄','🌊','🌀','🌪️']
        },
        {
            id: 'object', label: EMOJI_CATS.object,
            icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>',
            list: ['💡','🔦','🕯️','📱','💻','⌨️','🖥️','🖨️','🖱️','💾','💿','📷','📸','📹','🎥','📞','☎️','📟','📠','📺','📻','🧭','⏰','⌚','📡','🔋','🔌','💡','🔦','🕯️','🧱','🪞','🛋️','🚽','🚿','🛁','🧴','🧷','🧹','🧺','🧻','🪣','🧼','🧽','🧰','🔧','🔨','⚒️','🛠️','🔗','⛓️','🧲','🔮','💈','🏺','🧿','🪬','📦','📫','📬','📭','📮','📯','📝','📌','📍','📎','🖇️','📏','📐','✂️','🗃️','🗑️','🔑','🗝️','🔒','🔓','🚪','🪤','💰','💴','💵','💶','💷','💸','💳','🪙']
        },
    ];

    // 최근 사용 이모지 로드 (localStorage)
    const RECENT_KEY = 'ep_recent';
    async function getRecent() {
        try { return JSON.parse(localStorage.getItem(RECENT_KEY) || '[]'); } catch { return []; }
    }
    async function addRecent(emoji) {
        let arr = getRecent().filter(e => e !== emoji);
        arr.unshift(emoji);
        arr = arr.slice(0, 24);
        localStorage.setItem(RECENT_KEY, JSON.stringify(arr));
    }
    CATS[0].list = getRecent();

    let currentCatId = 'face';
    let isOpen = false;

    const toggle   = document.getElementById('emoji-toggle');
    const wrap     = document.getElementById('emoji-picker-wrap');
    const grid     = document.getElementById('emoji-grid');
    const gridWrap = document.getElementById('emoji-grid-wrap');
    const search   = document.getElementById('emoji-search');
    const catsEl   = document.getElementById('emoji-cats');
    const recentEl = document.getElementById('ep-recent');
    const textarea = document.getElementById('msg-textarea');
    if (!toggle || !wrap || !textarea) return;

    // 카테고리 탭 렌더
    CATS.forEach(cat => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ep-cat-btn' + (cat.id === currentCatId ? ' active' : '');
        btn.title = cat.label;
        btn.dataset.cid = cat.id;
        btn.innerHTML = `<svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">${cat.icon}</svg>`;
        btn.onclick = () => {
            if (search.value) { search.value = ''; }
            currentCatId = cat.id;
            document.querySelectorAll('.ep-cat-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            renderCat(cat.id);
        };
        catsEl.appendChild(btn);
    });

    function emojiBtn(e) {
        return `<button type="button" class="emoji-btn" data-e="${e}" title="${e}">${e}</button>`;
    }

    async function renderCat(catId) {
        const cat = CATS.find(c => c.id === catId);
        if (!cat) return;
        recentEl.innerHTML = '';
        if (catId === 'recent') {
            const recent = getRecent();
            if (!recent.length) {
                grid.innerHTML = `<div style="grid-column:1/-1;padding:20px;text-align:center;font-size:12px;color:var(--color-text-tertiary);">${STR_EMOJI_NO_RECENT}</div>`;
            } else {
                grid.innerHTML = recent.map(emojiBtn).join('');
            }
        } else {
            // 최근 사용 미리보기 (다른 카테고리 볼 때도 표시)
            const recent = getRecent();
            if (recent.length) {
                recentEl.innerHTML = `<div class="ep-section-label">${EMOJI_CATS.recent}</div><div style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px;margin-bottom:6px;">${recent.slice(0,8).map(emojiBtn).join('')}</div>`;
            }
            grid.innerHTML = cat.list.map(emojiBtn).join('');
        }
        gridWrap.scrollTop = 0;
    }

    renderCat(currentCatId);

    // 이모지 클릭 (이벤트 위임)
    async function handleEmojiClick(e) {
        const btn = e.target.closest('.emoji-btn');
        if (!btn) return;
        const emoji = btn.dataset.e;
        insertEmoji(emoji);
    }
    grid.addEventListener('click', handleEmojiClick);
    recentEl.addEventListener('click', handleEmojiClick);

    async function insertEmoji(emoji) {
        const start = textarea.selectionStart;
        const end   = textarea.selectionEnd;
        const val   = textarea.value;
        textarea.value = val.slice(0, start) + emoji + val.slice(end);
        textarea.selectionStart = textarea.selectionEnd = start + [...emoji].length;
        textarea.focus();
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        addRecent(emoji);
        CATS[0].list = getRecent();
        // 최근 사용 탭 업데이트 (현재 열려있으면)
        if (currentCatId !== 'recent') {
            const recent = getRecent();
            if (recent.length) {
                recentEl.innerHTML = `<div class="ep-section-label">${EMOJI_CATS.recent}</div><div style="display:grid;grid-template-columns:repeat(8,1fr);gap:4px;margin-bottom:6px;">${recent.slice(0,8).map(emojiBtn).join('')}</div>`;
            }
        }
        closeEmojiPicker();
    }

    // 검색
    let searchTimer = null;
    search.addEventListener('input', async function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const q = this.value.trim();
            if (!q) {
                renderCat(currentCatId);
                return;
            }
            // 전체 이모지에서 앞부분 일치 우선 표시 (최대 64개)
            const all = CATS.slice(1).flatMap(c => c.list);
            const unique = [...new Set(all)];
            recentEl.innerHTML = '';
            grid.innerHTML = unique.slice(0, 64).map(emojiBtn).join('') ||
                `<div style="grid-column:1/-1;padding:20px;text-align:center;font-size:12px;color:var(--color-text-tertiary);">${STR_EMOJI_SEARCH_EMPTY}</div>`;
            gridWrap.scrollTop = 0;
        }, 100);
    });

    // 열기/닫기
    async function openEmojiPicker() {
        const rect = toggle.getBoundingClientRect();
        const pw   = 348;
        const left = Math.max(8, Math.min(rect.right - pw, window.innerWidth - pw - 8));
        wrap.style.left   = left + 'px';
        wrap.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
        wrap.style.top    = '';
        wrap.style.display = 'block';
        requestAnimationFrame(() => wrap.classList.add('ep-open'));
        search.value = '';
        renderCat(currentCatId);
        setTimeout(() => search.focus(), 60);
        isOpen = true;
    }
    async function closeEmojiPicker() {
        wrap.classList.remove('ep-open');
        setTimeout(() => { if (!isOpen) wrap.style.display = 'none'; }, 180);
        isOpen = false;
    }

    toggle.addEventListener('click', async function(e) {
        e.stopPropagation();
        isOpen ? closeEmojiPicker() : openEmojiPicker();
    });

    document.addEventListener('click', async function(e) {
        if (isOpen && !wrap.contains(e.target) && e.target !== toggle && !toggle.contains(e.target)) {
            closeEmojiPicker();
        }
    });

    document.addEventListener('keydown', async function(e) {
        if (e.key === 'Escape' && isOpen) closeEmojiPicker();
    });
})();

// ── 웍스 메시지 분석 ──────────────────────────────────────────
async function analyzeMsg(msgId, btn) {
    const panel = document.getElementById('ai-analysis-panel');
    const body  = document.getElementById('ai-panel-body');
    if (!panel) return;

    // 같은 메시지 클릭 시 토글
    if (panel.dataset.forMsg == msgId && panel.classList.contains('visible')) {
        closeAiPanel(); return;
    }

    // 클릭한 말풍선 위에 패널 위치
    const wrap    = btn.closest('.msg-bubble-wrap');
    const rect    = (wrap || btn).getBoundingClientRect();
    const chatBox = document.getElementById('chat-messages');
    const cr      = chatBox.getBoundingClientRect();
    const panelW  = 300;
    const panelH  = 240;

    // 수직: 말풍선 위, 공간 부족 시 아래
    let top = rect.top - panelH - 10;
    if (top < cr.top + 8) top = rect.bottom + 8;

    // 수평: 말풍선 우측 끝에 맞춤, 채팅창 밖으로 나가면 보정
    let left = rect.right - panelW;
    if (left < cr.left + 8) left = cr.left + 8;
    if (left + panelW > cr.right - 8) left = cr.right - panelW - 8;

    panel.style.top   = top + 'px';
    panel.style.left  = left + 'px';
    panel.style.right = 'auto';

    panel.dataset.forMsg = msgId;
    panel.classList.add('visible');
    body.innerHTML = `<span style="color:var(--color-text-tertiary);font-size:11.5px;">${MSG_STR.aiAnalyzing}</span>`;
    btn.disabled = true;

    fetch('{{ route("messages.analyze") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ message_id: msgId }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            body.innerHTML = `<span style="color:var(--color-alert-warning-500);font-size:12px;">⚠ ${escA(data.error||MSG_STR.aiError)}</span>`;
            return;
        }
        const r = data.result;
        const kw  = Array.isArray(r.keywords) ? r.keywords.map(k => `<span class="ai-rc-tag">${escA(k)}</span>`).join('') : '';
        const ctx = r.context_note ? `<div class="ai-rc-row"><span class="ai-rc-label">${MSG_STR.aiLabelContext}</span><span class="ai-rc-value">${escA(r.context_note)}</span></div>` : '';
        const actions = renderAiActionItems(msgId, r.action_items || []);
        body.innerHTML = `
            <div class="ai-rc-row"><span class="ai-rc-label">${MSG_STR.aiLabelSummary}</span><span class="ai-rc-value">${escA(r.summary||'-')}</span></div>
            <div class="ai-rc-row"><span class="ai-rc-label">${MSG_STR.aiLabelIntent}</span><span class="ai-rc-value">${escA(r.intent||'-')}</span></div>
            <div class="ai-rc-row"><span class="ai-rc-label">${MSG_STR.aiLabelTone}</span><span class="ai-rc-value">${escA(r.tone||'-')}</span></div>
            <div class="ai-rc-row"><span class="ai-rc-label">${MSG_STR.aiLabelKeywords}</span><div class="ai-rc-tags">${kw}</div></div>
            ${ctx}
            ${actions}`;
    })
    .catch(() => {
        body.innerHTML = `<span style="color:var(--color-alert-warning-500);font-size:12px;">${MSG_STR.aiNetworkError}</span>`;
    })
    .finally(() => { btn.disabled = false; });
}
async function closeAiPanel() {
    const p = document.getElementById('ai-analysis-panel');
    if (p) { p.classList.remove('visible'); p.dataset.forMsg = ''; }
}
function escA(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escAttr(s) { return escA(s).replace(/"/g,'&quot;'); }

// ── 메시지 → 실행 계획(Plan-Do-Act) ──────────────────────────────────────
const PDA_MSG = {
    nav:          @json(__('plan-do-acts.nav')),
    ref_message:  @json(__('plan-do-acts.ref_message')),
    chat_message: @json(__('plan-do-acts.chat_message')),
};

function pdaOpenFromMessage(btn) {
    const msgId = btn.dataset.msgId;
    const pdaId = btn.dataset.pdaId;
    if (pdaId) { window.pdaOpenEdit(pdaId); return; }

    const wrap   = btn.closest('.msg-bubble-wrap');
    const sender = wrap ? (wrap.dataset.msgSender || '') : '';
    let body = '';
    const bodyDiv = wrap ? wrap.querySelector('.msg-bubble div[style*="pre-wrap"]') : null;
    if (bodyDiv) body = (bodyDiv.innerText || '').trim();

    const title   = (body.replace(/\s+/g, ' ').trim().slice(0, 60)) || PDA_MSG.chat_message;
    const planRef = PDA_MSG.ref_message + ' ' + sender + '\n' + body + '\n\n';
    const excerpt = PDA_MSG.ref_message + ' ' + sender + '\n' + body;

    window.pdaOpenCreate(null, {
        source_message_id: msgId,
        title: title,
        plan: planRef,
        source_excerpt: excerpt,
    });
}

function _pdaUpdateMsgButton(msgId, pdaId) {
    document.querySelectorAll(`.msg-pda-btn[data-msg-id="${msgId}"]`).forEach(btn => {
        btn.dataset.pdaId = pdaId || '';
        btn.classList.toggle('registered', !!pdaId);
    });
}
window.pdaOnSaved = function(item, mode, sourceCommentId, sourceMessageId) {
    if (mode === 'create' && sourceMessageId && item) _pdaUpdateMsgButton(sourceMessageId, item.id);
};
window.pdaOnDeleted = function(id, sourceCommentId, sourceMessageId) {
    if (sourceMessageId) _pdaUpdateMsgButton(sourceMessageId, null);
};

function renderAiActionItems(msgId, items) {
    if (!Array.isArray(items) || !items.length) {
        return `<div class="ai-action-list"><div class="ai-action-heading">${MSG_STR.aiActionHeading}</div><div class="ai-action-desc">${MSG_STR.aiActionEmpty}</div></div>`;
    }

    const cards = items.slice(0, 3).map((item) => {
        const title = item.title || item.summary || '';
        if (!title) return '';

        const desc = item.description || '';
        const chips = [
            item.due_date ? `<span class="ai-action-chip">${MSG_STR.aiActionDue} ${escA(item.due_date)}</span>` : '',
            item.assignee_name ? `<span class="ai-action-chip">${MSG_STR.aiActionAssignee} ${escA(item.assignee_name)}</span>` : '',
        ].filter(Boolean).join('');

        return `<div class="ai-action-card">
            <div class="ai-action-title">${escA(title)}</div>
            ${desc ? `<div class="ai-action-desc">${escA(desc)}</div>` : ''}
            ${chips ? `<div class="ai-action-meta">${chips}</div>` : ''}
            <button type="button" class="ai-action-create"
                data-msg-id="${msgId}"
                data-title="${escAttr(title)}"
                data-description="${escAttr(desc)}"
                data-due-date="${escAttr(item.due_date || '')}"
                onclick="promoteAiActionItem(this)">
                ${MSG_STR.aiActionCreate}
            </button>
        </div>`;
    }).join('');

    return `<div class="ai-action-list"><div class="ai-action-heading">${MSG_STR.aiActionHeading}</div>${cards}</div>`;
}

async function promoteAiActionItem(btn) {
    const msgId = btn.dataset.msgId;
    if (!msgId) return;

    const original = btn.textContent;
    btn.disabled = true;
    btn.textContent = MSG_STR.aiActionCreating;

    try {
        const res = await fetch(`${LB_BASE}/messages/${msgId}/action-items`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                title: btn.dataset.title || '',
                description: btn.dataset.description || '',
                due_date: btn.dataset.dueDate || null,
            }),
        });

        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) throw new Error(data.message || 'failed');

        btn.textContent = MSG_STR.aiActionCreated;
        btn.style.background = '#16a34a';
    } catch (e) {
        btn.disabled = false;
        btn.textContent = original;
        alert(MSG_STR.aiActionPromoteFail);
    }
}

// ── 이미지 리뷰 라이트박스 ─────────────────────────────────
let lbMsgId = null;
const _lbRenderedCommentIds = new Set();

// ── 이미지 줌 상태 ────────────────────────────────────────
let _lbImgNatW   = 0;
let _lbImgNatH   = 0;
let lbImgScale   = 1.0;
let _lbImgFitMode = true;
let _lbSvgPosRaf  = null;

async function lbImgZoom(delta) {
    const wrap = document.getElementById('img-lb-scroll-wrap');
    if (!wrap || !_lbImgNatW) return;
    if (_lbImgFitMode) {
        const wW = wrap.clientWidth  - 40;
        const wH = wrap.clientHeight - 40;
        lbImgScale = Math.min(wW / _lbImgNatW, wH / _lbImgNatH, 1);
        _lbImgFitMode = false;
    }
    lbImgScale = Math.min(8, Math.max(0.1, lbImgScale + delta));
    _applyLbImgZoom();
}

async function lbImgZoomFit() {
    const img = document.getElementById('img-lightbox-img');
    if (!img) return;
    _lbImgFitMode = true; lbImgScale = 1.0;
    img.style.width = ''; img.style.height = '';
    img.style.maxWidth = '100%'; img.style.maxHeight = '100%';
    const label = document.getElementById('lb-img-zoom-label');
    if (label) label.textContent = MSG_STR.zoomFit;
    const wrap = document.getElementById('img-lb-scroll-wrap');
    if (wrap) { wrap.scrollLeft = 0; wrap.scrollTop = 0; wrap.style.cursor = 'grab'; }
    requestAnimationFrame(_lbUpdateSvgPosition);
}

async function lbImgZoomOriginal() {
    if (!_lbImgNatW) return;
    _lbImgFitMode = false; lbImgScale = 1.0;
    _applyLbImgZoom();
}

async function _applyLbImgZoom() {
    const img   = document.getElementById('img-lightbox-img');
    const wrap  = document.getElementById('img-lb-scroll-wrap');
    const label = document.getElementById('lb-img-zoom-label');
    if (!img || !_lbImgNatW) return;
    const w = Math.round(_lbImgNatW * lbImgScale);
    const h = Math.round(_lbImgNatH * lbImgScale);
    img.style.width = w + 'px'; img.style.height = h + 'px';
    img.style.maxWidth = 'none'; img.style.maxHeight = 'none';
    if (label) label.textContent = Math.round(lbImgScale * 100) + '%';
    if (wrap) wrap.style.cursor = 'grab';
    requestAnimationFrame(_lbUpdateSvgPosition);
}

// Ctrl+휠 줌 + 드래그 팬 + 스크롤 SVG 갱신
(async function() {
    const getWrap = () => document.getElementById('img-lb-scroll-wrap');

    // Ctrl+휠 줌
    document.addEventListener('wheel', e => {
        const w = getWrap();
        if (!w || !w.contains(e.target) || !e.ctrlKey) return;
        e.preventDefault();
        lbImgZoom(e.deltaY < 0 ? 0.15 : -0.15);
    }, { passive: false });

    // 스크롤 시 SVG 위치 갱신
    document.addEventListener('scroll', e => {
        const w = getWrap();
        if (!w || e.target !== w) return;
        if (_lbSvgPosRaf) cancelAnimationFrame(_lbSvgPosRaf);
        _lbSvgPosRaf = requestAnimationFrame(() => { _lbUpdateSvgPosition(); _lbSvgPosRaf = null; });
    }, true);

    // 드래그 팬 (도형 도구 미선택 시)
    let _panning = false, _panSX, _panSY, _panSL, _panST;
    document.addEventListener('mousedown', e => {
        const w = getWrap();
        if (!w || !w.contains(e.target)) return;
        _panning = true;
        _panSX = e.clientX; _panSY = e.clientY;
        _panSL = w.scrollLeft; _panST = w.scrollTop;
        w.style.cursor = 'grabbing';
        e.preventDefault();
    });
    document.addEventListener('mousemove', e => {
        if (!_panning) return;
        const w = getWrap();
        if (!w) return;
        w.scrollLeft = _panSL - (e.clientX - _panSX);
        w.scrollTop  = _panST - (e.clientY - _panSY);
    });
    document.addEventListener('mouseup', () => {
        if (!_panning) return;
        _panning = false;
        const w = getWrap();
        if (w) w.style.cursor = 'grab';
    });
    document.addEventListener('mouseleave', () => {
        if (!_panning) return;
        _panning = false;
        const w = getWrap();
        if (w) w.style.cursor = 'grab';
    });
})();

// ── 의견 패널 접힘/펼침 ───────────────────────────────────
let _lbReviewCollapsed = false;
async function _setLbReviewTabDir(collapsed) {
    const icon = document.getElementById('lb-review-tab-icon');
    if (icon) icon.querySelector('path').setAttribute('d', collapsed ? 'M9 18l6-6-6-6' : 'M15 18l-6-6 6-6');
    const btn = document.getElementById('lb-review-collapse-btn');
    if (btn) btn.textContent = collapsed ? '▶' : '◀';
}

async function toggleLbReview() {
    _lbReviewCollapsed = !_lbReviewCollapsed;
    const panel = document.getElementById('img-lb-review');
    panel.classList.toggle('lb-collapsed', _lbReviewCollapsed);
    _setLbReviewTabDir(_lbReviewCollapsed);
    // SVG 위치 패널 너비 변경 후 갱신
    setTimeout(() => requestAnimationFrame(_lbUpdateSvgPosition), 230);
}

// ── 도형 주석 상태 ────────────────────────────────────────
let lbAnnTool         = null;
let lbAnnColor        = '#ef4444';
let lbAnnNextNum      = 1;
let lbAnnList         = [];
let lbAnnSelected     = null;
let lbAnnDrawing      = false;
let lbAnnStartX       = 0;
let lbAnnStartY       = 0;
let lbAnnDragEl       = null;
let lbAnnMoveActive   = false;
let lbAnnMoveStartX   = 0;
let lbAnnMoveStartY   = 0;
let lbAnnMoveStartData = null;
let _lbAnnTextPct     = null;
let _lbAnnEditId      = null;

async function _lbCalcNextNum(list) {
    const nums = list.filter(a => a.type === 'number').map(a => a.data?.n ?? 0);
    return nums.length ? Math.max(...nums) + 1 : 1;
}

async function _lbResetAnnState() {
    lbAnnTool = null; lbAnnSelected = null; lbAnnList = [];
    lbAnnDrawing = false; lbAnnMoveActive = false;
    if (lbAnnDragEl) { lbAnnDragEl.remove(); lbAnnDragEl = null; }
    _lbAnnTextPct = null; _lbAnnEditId = null;
    _lbImgNatW = 0; _lbImgNatH = 0; lbImgScale = 1.0; _lbImgFitMode = true;
    const svg = document.getElementById('img-lb-ann-svg');
    if (svg) {
        svg.querySelectorAll('.lb-ann-item, #lb-ann-sel-overlay').forEach(el => el.remove());
        svg.style.cssText = 'position:absolute;z-index:20;pointer-events:none;overflow:visible;';
    }
    document.querySelectorAll('.lb-ann-tool-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('lb-ann-text-popup').style.display = 'none';
    lbImgZoomFit();
}

async function openLightbox(src, alt, msgId) {
    lbMsgId = msgId || null;
    _lbResetAnnState();
    document.getElementById('img-lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';
    const img = document.getElementById('img-lightbox-img');
    img.onload = () => {
        _lbImgNatW = img.naturalWidth;
        _lbImgNatH = img.naturalHeight;
        requestAnimationFrame(_lbUpdateSvgPosition);
    };
    img.src = src; img.alt = alt || '';
    if (img.complete && img.naturalWidth) {
        _lbImgNatW = img.naturalWidth; _lbImgNatH = img.naturalHeight;
        requestAnimationFrame(_lbUpdateSvgPosition);
    }
    document.getElementById('img-lightbox-name').textContent = alt || '';
    document.getElementById('img-lb-comments').innerHTML = `<span style="color:var(--color-text-tertiary);font-size:12px;text-align:center;margin:auto;">${MSG_STR.lbLoading}</span>`;
    document.getElementById('img-lb-comment-count').textContent = '';
    document.getElementById('img-lb-textarea').value = '';
    if (msgId) { loadLbComments(msgId); lbLoadAnnotations(msgId); }
}

async function closeLightbox() {
    // 전체창 활성 상태에서 닫으면 fullscreen 도 종료
    if (document.fullscreenElement) {
        try { await document.exitFullscreen(); } catch (e) {}
    }
    document.getElementById('img-lightbox').classList.remove('open');
    document.body.style.overflow = '';
    lbMsgId = null;
    _lbRenderedCommentIds.clear();
    _lbResetAnnState();
    // 패널 펼침 상태 초기화
    if (_lbReviewCollapsed) {
        _lbReviewCollapsed = false;
        document.getElementById('img-lb-review').classList.remove('lb-collapsed');
        _setLbReviewTabDir(false);
    }
}

/* 전체창 토글 — 브라우저 Fullscreen API 사용 */
function lbToggleFullscreen() {
    const el = document.getElementById('img-lightbox');
    if (!el) return;
    if (!document.fullscreenElement) {
        const req = el.requestFullscreen || el.webkitRequestFullscreen || el.msRequestFullscreen;
        if (req) req.call(el).catch(() => { /* 사용자 거부 / 미지원 무시 */ });
    } else {
        const exit = document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen;
        if (exit) exit.call(document).catch(() => {});
    }
}

/* 현재 표시 중인 이미지 다운로드 */
function lbDownloadCurrent() {
    const img = document.getElementById('img-lightbox-img');
    if (!img || !img.src) return;
    const a = document.createElement('a');
    a.href = img.src;
    a.download = (img.alt && img.alt.trim()) || 'image';
    a.rel = 'noopener';
    document.body.appendChild(a);
    a.click();
    a.remove();
}

/* 전체창 상태 변경 시 버튼 라벨 / 아이콘 토글 */
document.addEventListener('fullscreenchange', function () {
    const label = document.getElementById('img-lb-fullscreen-label');
    const icon  = document.getElementById('img-lb-fullscreen-icon');
    if (!label || !icon) return;
    if (document.fullscreenElement) {
        label.textContent = MSG_STR.lbFullscreenExit;
        // 축소 아이콘 (4 화살표 안으로)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V5m0 4H5m10 0V5m0 4h4M9 15v4m0-4H5m10 0v4m0-4h4"/>';
    } else {
        label.textContent = MSG_STR.lbFullscreen;
        // 확대 아이콘 (4 화살표 밖으로)
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>';
    }
});

async function loadLbComments(msgId) {
    fetch(`${LB_BASE}/messages/${msgId}/image-comments`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        renderLbComments(data.comments);
    });
}

async function renderLbComments(comments) {
    _lbRenderedCommentIds.clear();
    const list  = document.getElementById('img-lb-comments');
    const count = document.getElementById('img-lb-comment-count');
    list.innerHTML = '';
    count.textContent = comments.length ? comments.length + MSG_STR.lbCountSuffix : '';
    if (!comments.length) {
        list.innerHTML = `<span style="color:var(--color-text-tertiary);font-size:12px;text-align:center;margin:auto;">${MSG_STR.noOpinionYet}</span>`;
        return;
    }
    comments.forEach(c => {
        _lbRenderedCommentIds.add(c.id);
        list.appendChild(makeLbComment(c));
    });
    list.scrollTop = list.scrollHeight;
}

function makeLbComment(c) {
    const div = document.createElement('div');
    div.className = 'lb-comment' + (c.is_mine ? ' mine' : '');
    div.dataset.commentId = c.id;
    const delBtn = c.is_mine
        ? `<button class="lb-del-btn" onclick="deleteLbComment(${lbMsgId},${c.id},this)" title="${MSG_STR.lbDeleteTitle}">&times;</button>`
        : '';
    div.innerHTML = `
        <div class="lb-comment-name">
            <span>${escA(c.user_name)}</span>
            <span style="display:flex;align-items:center;gap:4px;">
                <span class="lb-comment-time">${escA(c.created_at)}</span>${delBtn}
            </span>
        </div>
        <div class="lb-comment-body">${escA(c.content)}</div>`;
    return div;
}

function appendLbComment(c) {
    if (_lbRenderedCommentIds.has(c.id)) return;
    _lbRenderedCommentIds.add(c.id);
    const list = document.getElementById('img-lb-comments');
    const empty = list.querySelector('span');
    if (empty) empty.remove();
    list.appendChild(makeLbComment(c));
    list.scrollTop = list.scrollHeight;
    const count = document.getElementById('img-lb-comment-count');
    const n = list.querySelectorAll('.lb-comment').length;
    count.textContent = n + MSG_STR.lbCountSuffix;
}

async function submitLbComment() {
    if (!lbMsgId) return;
    const ta  = document.getElementById('img-lb-textarea');
    const btn = document.getElementById('img-lb-submit');
    const content = ta.value.trim();
    if (!content) return;
    btn.disabled = true;
    fetch(`${LB_BASE}/messages/${lbMsgId}/image-comments`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        },
        body: JSON.stringify({ content }),
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        ta.value = '';
        appendLbComment(data.comment);
    })
    .finally(() => { btn.disabled = false; ta.focus(); });
}

async function deleteLbComment(msgId, commentId, btn) {
    if (!await __confirm(MSG_STR.confirmDeleteOpinion)) return;
    fetch(`${LB_BASE}/messages/${msgId}/image-comments/${commentId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) return;
        const item = btn.closest('.lb-comment');
        item.remove();
        const list  = document.getElementById('img-lb-comments');
        const count = document.getElementById('img-lb-comment-count');
        const n = list.querySelectorAll('.lb-comment').length;
        count.textContent = n ? n + MSG_STR.lbCountSuffix : '';
        if (!n) list.innerHTML = `<span style="color:var(--color-text-tertiary);font-size:12px;text-align:center;margin:auto;">${MSG_STR.noOpinionYet}</span>`;
    });
}

// ── SVG 위치 동기화 (이미지 실제 렌더 크기에 맞춤) ──────────────
function _lbUpdateSvgPosition() {
    const svg  = document.getElementById('img-lb-ann-svg');
    const img  = document.getElementById('img-lightbox-img');
    const side = document.getElementById('img-lb-image-side');
    if (!svg || !img || !side) return;
    const vr = side.getBoundingClientRect();
    const ir = img.getBoundingClientRect();
    if (!ir.width || !ir.height) return;
    svg.style.left   = `${ir.left - vr.left}px`;
    svg.style.top    = `${ir.top  - vr.top}px`;
    svg.style.right  = 'auto';
    svg.style.bottom = 'auto';
    svg.style.width  = `${ir.width}px`;
    svg.style.height = `${ir.height}px`;
}

// ── 도형 주석 도구/색상 ───────────────────────────────────
function lbSetAnnTool(tool) {
    lbAnnTool = (lbAnnTool === tool) ? null : tool;
    if (lbAnnTool) lbSelectAnnotation(null);
    document.querySelectorAll('.lb-ann-tool-btn').forEach(btn => {
        const t = btn.id.replace('lb-ann-btn-', '');
        btn.classList.toggle('active', t === lbAnnTool);
    });
    const svg = document.getElementById('img-lb-ann-svg');
    if (svg) {
        svg.style.pointerEvents = (lbAnnTool || lbAnnSelected) ? 'all' : 'none';
        svg.style.cursor = lbAnnTool ? 'crosshair' : 'default';
    }
}

function lbSetAnnColor(c) {
    lbAnnColor = c;
    document.querySelectorAll('.lb-ann-color-btn').forEach(btn => {
        btn.style.outline = (btn.dataset.lbcolor === c) ? '2px solid #fff' : 'none';
        btn.style.outlineOffset = '2px';
    });
}

function _getLbSvgPct(svg, e) {
    const r = svg.getBoundingClientRect();
    return {
        x: Math.max(0, Math.min(100, (e.clientX - r.left)  / r.width  * 100)),
        y: Math.max(0, Math.min(100, (e.clientY - r.top)   / r.height * 100))
    };
}

function _makeLbTempEl(type) {
    const ns = 'http://www.w3.org/2000/svg';
    if (type === 'rect') {
        const el = document.createElementNS(ns, 'rect');
        el.setAttribute('fill', 'none'); el.setAttribute('stroke', lbAnnColor);
        el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-dasharray', '5 3');
        return el;
    }
    if (type === 'circle') {
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('fill', 'none'); el.setAttribute('stroke', lbAnnColor);
        el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-dasharray', '5 3');
        return el;
    }
    if (type === 'line') {
        const el = document.createElementNS(ns, 'line');
        el.setAttribute('stroke', lbAnnColor); el.setAttribute('stroke-width', '2.5');
        el.setAttribute('stroke-linecap', 'round');
        return el;
    }
    return null;
}

async function _updateLbTempEl(el, type, x1, y1, x2, y2) {
    if (type === 'rect') {
        const x = Math.min(x1, x2), y = Math.min(y1, y2);
        el.setAttribute('x', `${x}%`); el.setAttribute('y', `${y}%`);
        el.setAttribute('width',  `${Math.abs(x2 - x1)}%`);
        el.setAttribute('height', `${Math.abs(y2 - y1)}%`);
    } else if (type === 'circle') {
        const cx = (x1 + x2) / 2, cy = (y1 + y2) / 2;
        el.setAttribute('cx', `${cx}%`); el.setAttribute('cy', `${cy}%`);
        el.setAttribute('rx', `${Math.abs(x2 - x1) / 2}%`);
        el.setAttribute('ry', `${Math.abs(y2 - y1) / 2}%`);
    } else if (type === 'line') {
        el.setAttribute('x1', `${x1}%`); el.setAttribute('y1', `${y1}%`);
        el.setAttribute('x2', `${x2}%`); el.setAttribute('y2', `${y2}%`);
    }
}

// ── SVG 마우스 이벤트 ─────────────────────────────────────
(async function() {
    function getSvg() { return document.getElementById('img-lb-ann-svg'); }

    // SVG에 직접 mousedown 부착 (pointer-events:all 일 때만 실행됨)
    document.addEventListener('mousedown', e => {
        const svg = getSvg();
        if (!svg) return;
        // SVG 자신 또는 그 자식을 클릭했을 때만 처리
        if (e.target !== svg && !svg.contains(e.target)) return;

        const pct = _getLbSvgPct(svg, e);

        if (lbAnnTool) {
            if (e.target.id === 'lb-ann-sel-overlay' || (e.target.closest && e.target.closest('#lb-ann-sel-overlay'))) return;
            e.preventDefault();
            if (lbAnnTool === 'number') {
                lbSaveAnnotation('number', { x: pct.x, y: pct.y, n: lbAnnNextNum, color: lbAnnColor });
                lbSetAnnTool(null); return;
            }
            if (lbAnnTool === 'text') {
                _lbAnnTextPct = pct;
                const popup = document.getElementById('lb-ann-text-popup');
                popup.style.left = `${Math.min(e.clientX, window.innerWidth - 380)}px`;
                popup.style.top  = `${Math.min(e.clientY, window.innerHeight - 210)}px`;
                popup.style.display = 'block';
                setTimeout(() => document.getElementById('lb-ann-text-input').focus(), 50);
                return;
            }
            lbAnnDrawing = true;
            lbAnnStartX = pct.x; lbAnnStartY = pct.y;
            lbAnnDragEl = _makeLbTempEl(lbAnnTool);
            if (lbAnnDragEl) svg.appendChild(lbAnnDragEl);
            return;
        }

        if (lbAnnSelected) {
            if (e.target.closest && e.target.closest('#lb-ann-sel-overlay')) return;
            if (_findLbAnnGroup(e.target)) return;
            lbSelectAnnotation(null);
        }
    });

    document.addEventListener('mousemove', e => {
        const svg = getSvg();
        if (!svg) return;
        if (lbAnnDrawing && lbAnnDragEl) {
            e.preventDefault();
            const pct = _getLbSvgPct(svg, e);
            _updateLbTempEl(lbAnnDragEl, lbAnnTool, lbAnnStartX, lbAnnStartY, pct.x, pct.y);
            return;
        }
        if (lbAnnMoveActive && lbAnnSelected) {
            e.preventDefault();
            const pct = _getLbSvgPct(svg, e);
            _directMoveLbAnn(lbAnnSelected.type, lbAnnMoveStartData, pct.x - lbAnnMoveStartX, pct.y - lbAnnMoveStartY);
        }
    });

    async function lbFinishInteraction(e) {
        const svg = getSvg();
        if (!svg) return;
        if (lbAnnDrawing) {
            lbAnnDrawing = false;
            const pct = _getLbSvgPct(svg, e);
            if (lbAnnDragEl) { lbAnnDragEl.remove(); lbAnnDragEl = null; }
            const dx = pct.x - lbAnnStartX, dy = pct.y - lbAnnStartY;
            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) return;
            if (lbAnnTool === 'rect') {
                lbSaveAnnotation('rect', { x1: lbAnnStartX, y1: lbAnnStartY, x2: pct.x, y2: pct.y, color: lbAnnColor });
            } else if (lbAnnTool === 'circle') {
                const cx = (lbAnnStartX + pct.x) / 2, cy = (lbAnnStartY + pct.y) / 2;
                lbSaveAnnotation('circle', { cx, cy, rx: Math.abs(dx) / 2, ry: Math.abs(dy) / 2, color: lbAnnColor });
            } else if (lbAnnTool === 'line') {
                lbSaveAnnotation('line', { x1: lbAnnStartX, y1: lbAnnStartY, x2: pct.x, y2: pct.y, color: lbAnnColor });
            }
            lbSetAnnTool(null); return;
        }
        if (lbAnnMoveActive && lbAnnSelected) {
            lbAnnMoveActive = false;
            if (svg) svg.style.cursor = 'default';
            const pct = _getLbSvgPct(svg, e);
            const dx = pct.x - lbAnnMoveStartX, dy = pct.y - lbAnnMoveStartY;
            if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) {
                _clearLbSelectionOverlay();
                _showLbSelectionOverlay(lbAnnSelected.id); return;
            }
            const newData = lbApplyDelta(lbAnnSelected.type, lbAnnMoveStartData, dx, dy);
            const idx = lbAnnList.findIndex(a => a.id === lbAnnSelected.id);
            if (idx !== -1) { lbAnnList[idx].data = newData; lbAnnSelected = lbAnnList[idx]; }
            lbPatchAnnotation(lbAnnSelected.id, newData);
            lbRenderAnnotations();
        }
    }

    document.addEventListener('mouseup',    lbFinishInteraction);
    document.addEventListener('mouseleave', lbFinishInteraction);
})();

// ── 주석 로드/렌더 ────────────────────────────────────────
async function lbLoadAnnotations(msgId) {
    fetch(`${LB_BASE}/messages/${msgId}/annotations`, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        lbAnnList    = data.annotations || [];
        lbAnnNextNum = _lbCalcNextNum(lbAnnList);
        lbRenderAnnotations();
    })
    .catch(err => console.error('[lbLoadAnnotations]', err));
}

async function lbRenderAnnotations() {
    const svg = document.getElementById('img-lb-ann-svg');
    if (!svg) return;
    svg.querySelectorAll('.lb-ann-item, #lb-ann-sel-overlay').forEach(el => el.remove());
    lbAnnList.forEach(a => _renderLbAnnItem(a, svg));
    if (lbAnnSelected && lbAnnList.find(a => a.id === lbAnnSelected.id)) {
        _showLbSelectionOverlay(lbAnnSelected.id);
    } else if (lbAnnSelected) {
        lbAnnSelected = null;
        svg.style.pointerEvents = lbAnnTool ? 'all' : 'none';
    }
    requestAnimationFrame(_lbUpdateSvgPosition);
}

async function _renderLbAnnItem(ann, svg) {
    const ns = 'http://www.w3.org/2000/svg';
    const g = document.createElementNS(ns, 'g');
    g.classList.add('lb-ann-item');
    g.dataset.annId = ann.id;
    if (ann.can_delete) g.dataset.canDelete = '1';

    const d = ann.data, color = d.color || '#ef4444';

    if (ann.type === 'number') {
        const c = document.createElementNS(ns, 'circle');
        c.setAttribute('cx', `${d.x}%`); c.setAttribute('cy', `${d.y}%`); c.setAttribute('r', '14');
        c.setAttribute('fill', color); c.setAttribute('stroke', 'white'); c.setAttribute('stroke-width', '1.5');
        const t = document.createElementNS(ns, 'text');
        t.setAttribute('x', `${d.x}%`); t.setAttribute('y', `${d.y}%`);
        t.setAttribute('text-anchor', 'middle'); t.setAttribute('dominant-baseline', 'central');
        t.setAttribute('fill', 'white'); t.setAttribute('font-size', '11'); t.setAttribute('font-weight', '700');
        t.setAttribute('pointer-events', 'none'); t.textContent = d.n;
        g.appendChild(c); g.appendChild(t);

    } else if (ann.type === 'rect') {
        const x = Math.min(d.x1, d.x2), y = Math.min(d.y1, d.y2);
        const rect = document.createElementNS(ns, 'rect');
        rect.setAttribute('x', `${x}%`); rect.setAttribute('y', `${y}%`);
        rect.setAttribute('width',  `${Math.abs(d.x2 - d.x1)}%`);
        rect.setAttribute('height', `${Math.abs(d.y2 - d.y1)}%`);
        rect.setAttribute('fill', 'rgba(0,0,0,0)'); rect.setAttribute('stroke', color); rect.setAttribute('stroke-width', '2.5');
        g.appendChild(rect);

    } else if (ann.type === 'circle') {
        const el = document.createElementNS(ns, 'ellipse');
        el.setAttribute('cx', `${d.cx}%`); el.setAttribute('cy', `${d.cy}%`);
        el.setAttribute('rx', `${d.rx}%`); el.setAttribute('ry', `${d.ry}%`);
        el.setAttribute('fill', 'rgba(0,0,0,0)'); el.setAttribute('stroke', color); el.setAttribute('stroke-width', '2.5');
        g.appendChild(el);

    } else if (ann.type === 'line') {
        const el = document.createElementNS(ns, 'line');
        el.setAttribute('x1', `${d.x1}%`); el.setAttribute('y1', `${d.y1}%`);
        el.setAttribute('x2', `${d.x2}%`); el.setAttribute('y2', `${d.y2}%`);
        el.setAttribute('stroke', color); el.setAttribute('stroke-width', '2.5'); el.setAttribute('stroke-linecap', 'round');
        const markId = `lb-arr-${ann.id}`;
        const defs = svg.querySelector('defs') || svg.insertBefore(document.createElementNS(ns, 'defs'), svg.firstChild);
        const mk = document.createElementNS(ns, 'marker');
        mk.setAttribute('id', markId); mk.setAttribute('markerWidth', '8'); mk.setAttribute('markerHeight', '6');
        mk.setAttribute('refX', '7'); mk.setAttribute('refY', '3'); mk.setAttribute('orient', 'auto');
        const poly = document.createElementNS(ns, 'polygon');
        poly.setAttribute('points', '0 0, 8 3, 0 6'); poly.setAttribute('fill', color);
        mk.appendChild(poly); defs.appendChild(mk);
        el.setAttribute('marker-end', `url(#${markId})`);
        g.appendChild(el);

    } else if (ann.type === 'text') {
        const el = document.createElementNS(ns, 'text');
        el.setAttribute('x', `${d.x}%`); el.setAttribute('y', `${d.y}%`);
        el.setAttribute('fill', color); el.setAttribute('font-size', '14'); el.setAttribute('font-weight', '700');
        el.setAttribute('dominant-baseline', 'hanging');
        const lines = (d.text || '').split('\n');
        lines.forEach((line, i) => {
            const tspan = document.createElementNS(ns, 'tspan');
            tspan.setAttribute('x', `${d.x}%`);
            tspan.setAttribute('dy', i === 0 ? '0' : '1.4em');
            tspan.textContent = line || ' ';
            el.appendChild(tspan);
        });
        g.appendChild(el);
    }

    if (ann.can_delete) {
        g.setAttribute('pointer-events', 'all');
        g.style.cursor = 'grab';
        g.addEventListener('mousedown', e => {
            if (lbAnnTool) return;
            e.preventDefault(); e.stopPropagation();
            const svgEl = document.getElementById('img-lb-ann-svg');
            const pct   = _getLbSvgPct(svgEl, e);
            lbSelectAnnotation(ann.id);
            lbAnnMoveActive    = true;
            lbAnnMoveStartX    = pct.x;
            lbAnnMoveStartY    = pct.y;
            lbAnnMoveStartData = JSON.parse(JSON.stringify(lbAnnList.find(a => a.id === ann.id)?.data || ann.data));
            _clearLbSelectionOverlay();
            svgEl.style.cursor = 'grabbing';
        });
    } else {
        g.setAttribute('pointer-events', 'none');
        g.style.cursor = 'default';
    }

    svg.appendChild(g);
}

// ── 주석 CRUD ─────────────────────────────────────────────
async function lbSaveAnnotation(type, data) {
    if (!lbMsgId) return;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`${LB_BASE}/messages/${lbMsgId}/annotations`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ type, data })
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) return;
        lbAnnList.push(resp.annotation);
        if (type === 'number') lbAnnNextNum = _lbCalcNextNum(lbAnnList);
        lbRenderAnnotations();
    })
    .catch(err => console.error('[lbSaveAnnotation]', err));
}

async function lbDeleteAnnotation(id) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`${LB_BASE}/messages/${lbMsgId}/annotations/${id}`, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }
    })
    .then(r => r.json())
    .then(resp => {
        if (!resp.ok) return;
        lbAnnList = lbAnnList.filter(a => a.id !== id);
        lbAnnNextNum = _lbCalcNextNum(lbAnnList);
        if (lbAnnSelected?.id === id) lbSelectAnnotation(null);
        lbRenderAnnotations();
    })
    .catch(err => console.error('[lbDeleteAnnotation]', err));
}

async function lbPatchAnnotation(id, data) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    fetch(`${LB_BASE}/messages/${lbMsgId}/annotations/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        body: JSON.stringify({ data })
    })
    .catch(err => console.error('[lbPatchAnnotation]', err));
}

// ── 선택/오버레이 ─────────────────────────────────────────
async function lbSelectAnnotation(id) {
    lbAnnSelected = id ? (lbAnnList.find(a => a.id === id) || null) : null;
    const svg = document.getElementById('img-lb-ann-svg');
    if (svg) svg.style.pointerEvents = (lbAnnSelected || lbAnnTool) ? 'all' : 'none';
    _clearLbSelectionOverlay();
    if (lbAnnSelected) _showLbSelectionOverlay(lbAnnSelected.id);
}

async function _clearLbSelectionOverlay() {
    document.getElementById('lb-ann-sel-overlay')?.remove();
}

async function _showLbSelectionOverlay(annId) {
    _clearLbSelectionOverlay();
    const svg = document.getElementById('img-lb-ann-svg');
    const g   = svg?.querySelector(`.lb-ann-item[data-ann-id="${annId}"]`);
    if (!g) return;
    let bbox;
    try { bbox = g.getBBox(); } catch (_) { return; }
    const ann = lbAnnList.find(a => a.id === annId);
    const ns  = 'http://www.w3.org/2000/svg';
    const pad = 6;

    const overlay = document.createElementNS(ns, 'g');
    overlay.id = 'lb-ann-sel-overlay';
    overlay.style.pointerEvents = 'none';

    const selRect = document.createElementNS(ns, 'rect');
    selRect.setAttribute('x',      bbox.x - pad);
    selRect.setAttribute('y',      bbox.y - pad);
    selRect.setAttribute('width',  Math.max(bbox.width  + pad * 2, 4));
    selRect.setAttribute('height', Math.max(bbox.height + pad * 2, 4));
    selRect.setAttribute('fill',   'rgba(167,139,250,.08)');
    selRect.setAttribute('stroke', '#a78bfa');
    selRect.setAttribute('stroke-width',   '1.5');
    selRect.setAttribute('stroke-dasharray', '5 3');
    selRect.setAttribute('rx', '4');
    overlay.appendChild(selRect);

    if (ann && ann.type === 'text' && ann.can_delete) {
        const editG = document.createElementNS(ns, 'g');
        const editX = bbox.x + bbox.width + pad + 2;
        editG.setAttribute('transform', `translate(${editX}, ${bbox.y - pad - 2})`);
        editG.style.pointerEvents = 'all'; editG.style.cursor = 'pointer';
        editG.addEventListener('mousedown', e => e.stopPropagation());
        editG.addEventListener('click', e => {
            e.stopPropagation();
            _lbAnnEditId = ann.id;
            document.getElementById('lb-ann-text-popup-title').textContent = MSG_STR.annTextEdit;
            document.getElementById('lb-ann-text-input').value = ann.data.text || '';
            const sr = svg.getBoundingClientRect();
            const px = sr.left + bbox.x;
            const py = sr.top  + bbox.y + bbox.height + 4;
            const popup = document.getElementById('lb-ann-text-popup');
            popup.style.left = `${Math.min(px, window.innerWidth  - 380)}px`;
            popup.style.top  = `${Math.min(py, window.innerHeight - 210)}px`;
            popup.style.display = 'block';
            setTimeout(() => { const inp = document.getElementById('lb-ann-text-input'); inp.focus(); inp.select(); }, 30);
        });
        const editBg = document.createElementNS(ns, 'circle');
        editBg.setAttribute('r', '9'); editBg.setAttribute('fill', '#7c3aed');
        editBg.setAttribute('stroke', 'white'); editBg.setAttribute('stroke-width', '1.5');
        editG.appendChild(editBg);
        const editTxt = document.createElementNS(ns, 'text');
        editTxt.setAttribute('text-anchor', 'middle'); editTxt.setAttribute('dominant-baseline', 'central');
        editTxt.setAttribute('fill', 'white'); editTxt.setAttribute('font-size', '11');
        editTxt.setAttribute('pointer-events', 'none');
        editTxt.textContent = '✎';
        editG.appendChild(editTxt);
        overlay.appendChild(editG);
    }

    if (ann && ann.can_delete) {
        const delOffset = (ann.type === 'text') ? 24 : 0;
        const delG = document.createElementNS(ns, 'g');
        delG.setAttribute('transform', `translate(${bbox.x + bbox.width + pad + 2 + delOffset}, ${bbox.y - pad - 2})`);
        delG.style.pointerEvents = 'all'; delG.style.cursor = 'pointer';
        delG.addEventListener('mousedown', e => e.stopPropagation());
        delG.addEventListener('click', async e => {
            e.stopPropagation();
            if (!await __confirm(MSG_STR.confirmDeleteAnn)) return;
            lbDeleteAnnotation(ann.id);
        });
        const delBg = document.createElementNS(ns, 'circle');
        delBg.setAttribute('r', '9'); delBg.setAttribute('fill', '#ef4444');
        delBg.setAttribute('stroke', 'white'); delBg.setAttribute('stroke-width', '1.5');
        delG.appendChild(delBg);
        const delTxt = document.createElementNS(ns, 'text');
        delTxt.setAttribute('text-anchor', 'middle'); delTxt.setAttribute('dominant-baseline', 'central');
        delTxt.setAttribute('fill', 'white'); delTxt.setAttribute('font-size', '13');
        delTxt.setAttribute('font-weight', '700'); delTxt.setAttribute('pointer-events', 'none');
        delTxt.textContent = '×';
        delG.appendChild(delTxt);
        overlay.appendChild(delG);
    }

    svg.appendChild(overlay);
}

async function _findLbAnnGroup(el) {
    let curr = el;
    const svg = document.getElementById('img-lb-ann-svg');
    while (curr && curr !== svg) {
        if (curr.classList && curr.classList.contains('lb-ann-item')) return curr;
        curr = curr.parentElement;
    }
    return null;
}

async function lbApplyDelta(type, data, dx, dy) {
    const cl = v => Math.max(0, Math.min(100, v));
    const d  = Object.assign({}, data);
    if (type === 'number' || type === 'text') {
        d.x = cl(d.x + dx); d.y = cl(d.y + dy);
    } else if (type === 'rect' || type === 'line') {
        d.x1 = cl(d.x1 + dx); d.y1 = cl(d.y1 + dy);
        d.x2 = cl(d.x2 + dx); d.y2 = cl(d.y2 + dy);
    } else if (type === 'circle') {
        d.cx = cl(d.cx + dx); d.cy = cl(d.cy + dy);
    }
    return d;
}

async function _directMoveLbAnn(type, base, dx, dy) {
    const ann = lbAnnSelected;
    if (!ann) return;
    const svg = document.getElementById('img-lb-ann-svg');
    const g = svg?.querySelector(`.lb-ann-item[data-ann-id="${ann.id}"]`);
    if (!g) return;
    const cl = v => Math.max(0, Math.min(100, v));
    if (type === 'number') {
        const nx = cl(base.x + dx), ny = cl(base.y + dy);
        g.querySelector('circle')?.setAttribute('cx', `${nx}%`);
        g.querySelector('circle')?.setAttribute('cy', `${ny}%`);
        g.querySelector('text')?.setAttribute('x', `${nx}%`);
        g.querySelector('text')?.setAttribute('y', `${ny}%`);
    } else if (type === 'rect') {
        const nx1 = cl(base.x1 + dx), ny1 = cl(base.y1 + dy);
        const nx2 = cl(base.x2 + dx), ny2 = cl(base.y2 + dy);
        const r = g.querySelector('rect');
        if (r) {
            r.setAttribute('x', `${Math.min(nx1, nx2)}%`); r.setAttribute('y', `${Math.min(ny1, ny2)}%`);
            r.setAttribute('width', `${Math.abs(nx2 - nx1)}%`); r.setAttribute('height', `${Math.abs(ny2 - ny1)}%`);
        }
    } else if (type === 'circle') {
        const e = g.querySelector('ellipse');
        if (e) { e.setAttribute('cx', `${cl(base.cx + dx)}%`); e.setAttribute('cy', `${cl(base.cy + dy)}%`); }
    } else if (type === 'line') {
        const l = g.querySelector('line');
        if (l) {
            l.setAttribute('x1', `${cl(base.x1 + dx)}%`); l.setAttribute('y1', `${cl(base.y1 + dy)}%`);
            l.setAttribute('x2', `${cl(base.x2 + dx)}%`); l.setAttribute('y2', `${cl(base.y2 + dy)}%`);
        }
    } else if (type === 'text') {
        const t = g.querySelector('text');
        if (t) {
            const nx = `${cl(base.x + dx)}%`, ny = `${cl(base.y + dy)}%`;
            t.setAttribute('x', nx); t.setAttribute('y', ny);
            t.querySelectorAll('tspan').forEach(ts => ts.setAttribute('x', nx));
        }
    }
}

// ── 텍스트 주석 팝업 ─────────────────────────────────────
async function lbConfirmAnnText() {
    const val = document.getElementById('lb-ann-text-input').value.trim();
    document.getElementById('lb-ann-text-popup').style.display = 'none';
    document.getElementById('lb-ann-text-input').value = '';
    document.getElementById('lb-ann-text-popup-title').textContent = MSG_STR.annTextTitle;

    if (_lbAnnEditId) {
        if (val) {
            const ann = lbAnnList.find(a => a.id === _lbAnnEditId);
            if (ann) {
                const newData = Object.assign({}, ann.data, { text: val });
                ann.data = newData;
                lbPatchAnnotation(_lbAnnEditId, newData);
                lbRenderAnnotations();
            }
        }
        _lbAnnEditId = null; return;
    }
    if (!val || !_lbAnnTextPct) { _lbAnnTextPct = null; return; }
    lbSaveAnnotation('text', { x: _lbAnnTextPct.x, y: _lbAnnTextPct.y, text: val, color: lbAnnColor });
    _lbAnnTextPct = null; lbSetAnnTool(null);
}

async function lbCancelAnnText() {
    document.getElementById('lb-ann-text-popup').style.display = 'none';
    document.getElementById('lb-ann-text-input').value = '';
    document.getElementById('lb-ann-text-popup-title').textContent = MSG_STR.annTextTitle;
    _lbAnnTextPct = null; _lbAnnEditId = null;
}

document.getElementById('lb-ann-text-input').addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); lbConfirmAnnText(); }
    if (e.key === 'Escape') { e.preventDefault(); lbCancelAnnText(); }
});

// 기본 색상 초기화
lbSetAnnColor('#ef4444');

// Echo로 상대방 의견 실시간 수신
@if(isset($conversation))
(async function() {
    async function setupLbEcho() {
        window.Echo.private('conversation.' + CONV_ID)
            .listen('.ImageCommentPosted', async function(data) {
                if (lbMsgId && data.message_id == lbMsgId) {
                    // 이미 내가 등록한 의견은 fetch 응답으로 이미 추가됨 → 중복 방지
                    const exists = document.querySelector(`.lb-comment[data-comment-id="${data.comment.id}"]`);
                    if (!exists) appendLbComment(data.comment);
                }
            });
    }
    if (window.Echo) setupLbEcho();
    else window.addEventListener('echoReady', setupLbEcho, { once: true });
})();
@endif

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        if (document.getElementById('lb-ann-text-popup').style.display !== 'none') { lbCancelAnnText(); return; }
        if (lbAnnSelected) { lbSelectAnnotation(null); return; }
        if (lbAnnTool)     { lbSetAnnTool(lbAnnTool); return; }
        closeLightbox();
    }
});

// ─────────────────────────────────────────────────────────────────────────
// 워크스페이스 팝업 (상단바 컨트롤 + 인페이지 플로팅 팝업)
// ─────────────────────────────────────────────────────────────────────────
(function () {
    const PROJECTS_URL = @json(route('messages.workspace.projects'));
    const LS_KEY       = 'msgWorkspaceState.v2';   // v1(window.open 기반)과 키 분리
    const STR = {
        select_project:       @json(__('messages.ws_select_project')),
        select_menu:          @json(__('messages.ws_select_menu')),
        open_popup:           @json(__('messages.ws_open_popup')),
        close_popup:          @json(__('messages.ws_close_popup')),
        choose_project_first: @json(__('messages.ws_choose_project_first')),
        choose_menu_first:    @json(__('messages.ws_choose_menu_first')),
        no_projects:          @json(__('messages.ws_no_projects')),
        restore:              @json(__('messages.ws_restore')),
        maximize:             @json(__('messages.ws_maximize')),
    };

    const $proj   = document.getElementById('ws-project-sel');
    const $menu   = document.getElementById('ws-menu-sel');
    const $open   = document.getElementById('ws-open-btn');
    const $min    = document.getElementById('ws-min-btn');
    const $max    = document.getElementById('ws-max-btn');
    const $close  = document.getElementById('ws-close-btn');
    const $stat   = document.getElementById('ws-status');
    const $popup  = document.getElementById('ws-popup');
    const $tbar   = document.getElementById('ws-popup-titlebar');
    const $title  = document.getElementById('ws-popup-title-text');
    const $iframe = document.getElementById('ws-popup-iframe');
    const $load   = document.getElementById('ws-popup-loading');

    let projects = [];
    let savedBounds = null;   // 최대화 전 원본 위치/크기
    let isOpen = false;
    let isMaximized = false;
    let isMinimized = false;

    function loadState() {
        try { return JSON.parse(localStorage.getItem(LS_KEY) || '{}'); } catch { return {}; }
    }
    function saveState(s) {
        try { localStorage.setItem(LS_KEY, JSON.stringify(s)); } catch {}
    }
    function patchState(patch) {
        const s = loadState();
        saveState(Object.assign(s, patch));
    }

    function setStatus(text, isOpenFlag) {
        $stat.textContent = text || '';
        $stat.classList.toggle('is-open', !!isOpenFlag);
    }

    function refreshControls() {
        const hasProj = !!$proj.value;
        const hasMenu = !!$menu.value;
        $menu.disabled  = !hasProj || projects.length === 0;
        $open.disabled  = !(hasProj && hasMenu);
        $min.disabled   = !isOpen;
        $max.disabled   = !isOpen;
        $close.disabled = !isOpen;
        $max.title      = isMaximized ? STR.restore : STR.maximize;
    }

    function fillProjects() {
        $proj.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = projects.length ? STR.select_project : STR.no_projects;
        $proj.appendChild(opt0);
        for (const p of projects) {
            const o = document.createElement('option');
            o.value = String(p.id);
            o.textContent = p.name;
            $proj.appendChild(o);
        }
        $proj.disabled = projects.length === 0;
    }

    function fillMenus(projectId) {
        $menu.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = STR.select_menu;
        $menu.appendChild(opt0);
        const proj = projects.find(p => String(p.id) === String(projectId));
        if (!proj) { $menu.disabled = true; return; }
        for (const m of (proj.menus || [])) {
            const o = document.createElement('option');
            o.value = m.key;
            o.textContent = m.label;
            o.dataset.url = m.url;
            $menu.appendChild(o);
        }
        $menu.disabled = false;
    }

    function getSelectedUrl() {
        const projId = $proj.value;
        const menuKey = $menu.value;
        if (!projId || !menuKey) return null;
        const proj = projects.find(p => String(p.id) === String(projId));
        if (!proj) return null;
        const menu = (proj.menus || []).find(m => m.key === menuKey);
        if (!menu?.url) return null;
        // 팝업 안의 페이지에서는 전역 사이드바·헤더·공지를 숨기기 위해 ?embed=1
        return menu.url + (menu.url.includes('?') ? '&' : '?') + 'embed=1';
    }

    function getStatusText() {
        if (!$proj.value || !$menu.value) return '';
        return $proj.options[$proj.selectedIndex].text + ' · ' + $menu.options[$menu.selectedIndex].text;
    }

    function defaultBounds() {
        const vw = window.innerWidth, vh = window.innerHeight;
        // 화면 우측 절반 영역
        const w = Math.max(420, Math.floor(vw * 0.5));
        const h = Math.max(360, Math.floor(vh * 0.85));
        const left = Math.max(8, vw - w - 16);
        const top  = Math.max(8, Math.floor((vh - h) / 2));
        return { w, h, left, top };
    }

    function clampBounds(b) {
        const vw = window.innerWidth, vh = window.innerHeight;
        const w = Math.min(b.w, vw - 16);
        const h = Math.min(b.h, vh - 16);
        const left = Math.max(0, Math.min(b.left, vw - w));
        const top  = Math.max(0, Math.min(b.top,  vh - h));
        return { w, h, left, top };
    }

    function applyBounds(b) {
        const cb = clampBounds(b);
        $popup.style.left   = cb.left + 'px';
        $popup.style.top    = cb.top  + 'px';
        $popup.style.width  = cb.w + 'px';
        $popup.style.height = cb.h + 'px';
    }

    function loadUrlInIframe(url) {
        if (!url) return;
        $load.style.display = 'flex';
        $iframe.onload = () => { $load.style.display = 'none'; };
        $iframe.src = url;
    }

    function showPopup() {
        const s = loadState();
        const b = s.bounds || defaultBounds();
        applyBounds(b);
        $popup.classList.add('is-open');
        isOpen = true;
        isMinimized = false;
        $popup.classList.remove('is-minimized');
    }

    function hidePopup() {
        $popup.classList.remove('is-open', 'is-minimized');
        isOpen = false;
        isMaximized = false;
        isMinimized = false;
        savedBounds = null;
        $iframe.src = 'about:blank';
        $load.style.display = 'none';
    }

    // ── 공개 함수 (HTML onclick 에서 호출) ──
    window.wsOpenPopup = function () {
        const url = getSelectedUrl();
        if (!url) {
            if (!$proj.value)      alert(STR.choose_project_first);
            else if (!$menu.value) alert(STR.choose_menu_first);
            return;
        }
        if (!isOpen) showPopup();
        $title.textContent = getStatusText();
        loadUrlInIframe(url);
        setStatus(getStatusText(), true);
        refreshControls();
    };

    window.wsClosePopup = function () {
        if (!isOpen) return;
        hidePopup();
        setStatus('', false);
        refreshControls();
    };

    window.wsMinimize = function () {
        if (!isOpen) return;
        isMinimized = !isMinimized;
        $popup.classList.toggle('is-minimized', isMinimized);
        refreshControls();
    };

    window.wsMaximize = function () {
        if (!isOpen) return;
        if (isMinimized) {
            isMinimized = false;
            $popup.classList.remove('is-minimized');
        }
        if (!isMaximized) {
            savedBounds = {
                w: $popup.offsetWidth,  h: $popup.offsetHeight,
                left: $popup.offsetLeft, top: $popup.offsetTop,
            };
            applyBounds({ w: window.innerWidth - 16, h: window.innerHeight - 16, left: 8, top: 8 });
            isMaximized = true;
        } else {
            const b = savedBounds || defaultBounds();
            applyBounds(b);
            isMaximized = false;
        }
        refreshControls();
    };

    // ── 드래그 (타이틀바) ──
    let dragState = null;
    $tbar.addEventListener('mousedown', (e) => {
        if (e.target.closest('.ws-tt-btn')) return;
        if (isMaximized) return;
        e.preventDefault();
        dragState = {
            startX: e.clientX, startY: e.clientY,
            origLeft: $popup.offsetLeft, origTop: $popup.offsetTop,
        };
        $popup.classList.add('is-dragging');
        document.addEventListener('mousemove', onDragMove);
        document.addEventListener('mouseup', onDragEnd, { once: true });
    });
    function onDragMove(e) {
        if (!dragState) return;
        const dx = e.clientX - dragState.startX;
        const dy = e.clientY - dragState.startY;
        const vw = window.innerWidth, vh = window.innerHeight;
        let nl = dragState.origLeft + dx;
        let nt = dragState.origTop  + dy;
        nl = Math.max(0, Math.min(nl, vw - $popup.offsetWidth));
        nt = Math.max(0, Math.min(nt, vh - 36));
        $popup.style.left = nl + 'px';
        $popup.style.top  = nt + 'px';
    }
    function onDragEnd() {
        $popup.classList.remove('is-dragging');
        document.removeEventListener('mousemove', onDragMove);
        if (!isMaximized) {
            patchState({ bounds: {
                w: $popup.offsetWidth, h: $popup.offsetHeight,
                left: $popup.offsetLeft, top: $popup.offsetTop,
            }});
        }
        dragState = null;
    }

    // ── 리사이즈 (가장자리/모서리 핸들) ──
    let resizeState = null;
    $popup.querySelectorAll('.ws-rs').forEach(h => {
        h.addEventListener('mousedown', (e) => {
            if (isMaximized) return;
            e.preventDefault();
            e.stopPropagation();
            resizeState = {
                dir: h.dataset.rs,
                startX: e.clientX, startY: e.clientY,
                origW: $popup.offsetWidth, origH: $popup.offsetHeight,
                origLeft: $popup.offsetLeft, origTop: $popup.offsetTop,
            };
            $popup.classList.add('is-resizing');
            document.addEventListener('mousemove', onResizeMove);
            document.addEventListener('mouseup', onResizeEnd, { once: true });
        });
    });
    function onResizeMove(e) {
        if (!resizeState) return;
        const dx = e.clientX - resizeState.startX;
        const dy = e.clientY - resizeState.startY;
        const minW = 320, minH = 160;
        const vw = window.innerWidth, vh = window.innerHeight;
        let nw = resizeState.origW, nh = resizeState.origH, nl = resizeState.origLeft, nt = resizeState.origTop;
        const dir = resizeState.dir;
        if (dir.includes('r')) nw = Math.max(minW, Math.min(resizeState.origW + dx, vw - nl - 8));
        if (dir.includes('b')) nh = Math.max(minH, Math.min(resizeState.origH + dy, vh - nt - 8));
        if (dir.includes('l')) {
            const candW = Math.max(minW, resizeState.origW - dx);
            nl = resizeState.origLeft + (resizeState.origW - candW);
            nl = Math.max(0, nl);
            nw = resizeState.origW + resizeState.origLeft - nl;
        }
        $popup.style.width  = nw + 'px';
        $popup.style.height = nh + 'px';
        $popup.style.left   = nl + 'px';
        $popup.style.top    = nt + 'px';
    }
    function onResizeEnd() {
        $popup.classList.remove('is-resizing');
        document.removeEventListener('mousemove', onResizeMove);
        if (!isMaximized) {
            patchState({ bounds: {
                w: $popup.offsetWidth, h: $popup.offsetHeight,
                left: $popup.offsetLeft, top: $popup.offsetTop,
            }});
        }
        resizeState = null;
    }

    // 창 리사이즈 시 팝업 경계 재조정
    window.addEventListener('resize', () => {
        if (!isOpen) return;
        if (isMaximized) {
            applyBounds({ w: window.innerWidth - 16, h: window.innerHeight - 16, left: 8, top: 8 });
        } else {
            applyBounds({
                w: $popup.offsetWidth, h: $popup.offsetHeight,
                left: $popup.offsetLeft, top: $popup.offsetTop,
            });
        }
    });

    // ── 선택 이벤트 ──
    $proj.addEventListener('change', () => {
        fillMenus($proj.value);
        patchState({ projectId: $proj.value, menuKey: '' });
        refreshControls();
    });
    $menu.addEventListener('change', () => {
        patchState({ menuKey: $menu.value });
        if (isOpen) {
            const url = getSelectedUrl();
            if (url) {
                loadUrlInIframe(url);
                $title.textContent = getStatusText();
                setStatus(getStatusText(), true);
            }
        }
        refreshControls();
    });

    // ── 초기 로드 ──
    fetch(PROJECTS_URL, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(r => r.ok ? r.json() : Promise.reject(r.status))
        .then(data => {
            projects = Array.isArray(data?.projects) ? data.projects : [];
            fillProjects();
            const s = loadState();
            if (s.projectId && projects.some(p => String(p.id) === String(s.projectId))) {
                $proj.value = String(s.projectId);
                fillMenus(s.projectId);
                if (s.menuKey) {
                    const has = Array.from($menu.options).some(o => o.value === s.menuKey);
                    if (has) $menu.value = s.menuKey;
                }
            }
            refreshControls();
        })
        .catch(() => {
            $proj.innerHTML = '<option value="">' + STR.no_projects + '</option>';
            $proj.disabled = true;
            refreshControls();
        });
})();
</script>
@endsection
