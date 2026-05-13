<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ $file->original_name }} — {{ $project->name }}</title>
<style>
:root {
    --primary: #7c3aed;
    --primary-dark: #4c1d95;
    --header-h: 54px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html, body { height: 100%; overflow: hidden; background: #1e1b2e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

/* ── 헤더 ── */
#viewer-header {
    position: fixed; top: 0; left: 0; right: 0; height: var(--header-h);
    background: linear-gradient(135deg, #1e1b2e, #2d2456);
    border-bottom: 1px solid rgba(196,181,253,.15);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 18px; gap: 12px; z-index: 100;
}
#viewer-header .hdr-left  { display: flex; align-items: center; gap: 10px; min-width: 0; flex: 1; }
#viewer-header .hdr-right { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }
.hdr-back {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600;
    color: #c4b5fd; background: rgba(124,58,237,.18); border: 1px solid rgba(196,181,253,.2);
    text-decoration: none; transition: background .15s; white-space: nowrap;
}
.hdr-back:hover { background: rgba(124,58,237,.32); }
.hdr-title {
    font-size: 14px; font-weight: 700; color: #e9d5ff;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.hdr-project { font-size: 11px; color: #a78bfa; white-space: nowrap; }
.hdr-sep { color: rgba(196,181,253,.3); font-size: 14px; }
.hdr-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 6px 13px; border-radius: 8px; font-size: 12px; font-weight: 600;
    border: none; cursor: pointer; transition: all .15s; font-family: inherit;
}
.hdr-btn-ghost {
    color: #c4b5fd; background: rgba(196,181,253,.1); border: 1px solid rgba(196,181,253,.2);
}
.hdr-btn-ghost:hover { background: rgba(196,181,253,.2); }
.hdr-btn-pdf {
    color: #fff; background: linear-gradient(135deg, var(--primary), #6366f1);
    box-shadow: 0 2px 8px rgba(124,58,237,.4);
}
.hdr-btn-pdf:hover { opacity: .88; }

/* ── 뷰어 영역 ── */
#viewer-body {
    position: fixed; top: var(--header-h); left: 0; right: 0; bottom: 0;
}
#viewer-frame {
    width: 100%; height: 100%; border: none;
    background: #fff;
}

/* ── 블락 메시지 (iframe 차단 시) ── */
#block-msg {
    display: none; position: absolute; inset: 0;
    flex-direction: column; align-items: center; justify-content: center;
    background: #f5f3ff; gap: 16px; padding: 40px; text-align: center;
}
#block-msg .bm-icon { font-size: 52px; }
#block-msg .bm-title { font-size: 18px; font-weight: 700; color: #4c1d95; }
#block-msg .bm-desc  { font-size: 13px; color: #7c3aed; max-width: 440px; line-height: 1.7; }
#block-msg .bm-url   {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 10px 22px; background: var(--primary); color: #fff;
    border-radius: 10px; font-size: 13px; font-weight: 600;
    text-decoration: none; transition: opacity .15s;
}
#block-msg .bm-url:hover { opacity: .85; }

/* ── 전체화면 ── */
body.fullscreen #viewer-header { display: none; }
body.fullscreen #viewer-body   { top: 0; }

/* ── 인쇄(PDF) ── */
@media print {
    #viewer-header { display: none !important; }
    #viewer-body {
        position: static !important;
        height: 100vh !important;
        width: 100vw !important;
    }
    #viewer-frame {
        position: fixed !important;
        inset: 0 !important;
        height: 100vh !important;
        width: 100vw !important;
    }
    #block-msg { display: none !important; }
}
</style>
</head>
<body>

{{-- 헤더 --}}
<div id="viewer-header">
    <div class="hdr-left">
        <a href="{{ route('projects.files.index', $project) }}" class="hdr-back">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            {{ __('viewer.back_to_list') }}
        </a>
        <span class="hdr-sep">|</span>
        <div style="min-width:0;">
            <div class="hdr-title">{{ $file->original_name }}</div>
            <div class="hdr-project">{{ $project->name }}</div>
        </div>
    </div>
    <div class="hdr-right">
        <button class="hdr-btn hdr-btn-ghost" onclick="openInNewTab()" title="{{ __('viewer.open_new_tab_title') }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            {{ __('viewer.open_new_tab') }}
        </button>
        <button class="hdr-btn hdr-btn-pdf" onclick="downloadPdf()" title="{{ __('viewer.save_pdf_title') }}">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            {{ __('viewer.save_pdf') }}
        </button>
        <button class="hdr-btn hdr-btn-ghost" onclick="toggleFullscreen()" id="fs-btn" title="{{ __('viewer.fullscreen_title') }}">
            <svg id="fs-icon" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
            {{ __('viewer.fullscreen') }}
        </button>
    </div>
</div>

{{-- 뷰어 바디 --}}
<div id="viewer-body">
    <iframe id="viewer-frame"
            src="{{ $file->getEmbedUrl() }}"
            allowfullscreen
            allow="fullscreen"
            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation"
            onload="onFrameLoad()"
            onerror="showBlockMsg()">
    </iframe>
    <div id="block-msg">
        <div class="bm-icon">🔒</div>
        <div class="bm-title">{{ __('viewer.embed_blocked_title') }}</div>
        <div class="bm-desc">
            {{ __('viewer.embed_blocked_line1') }}<br>
            {{ __('viewer.embed_blocked_line2') }}<br>
            <strong>{{ __('viewer.embed_blocked_pdf_hint') }}</strong>{{ __('viewer.embed_blocked_line3') }}
        </div>
        <a href="{{ $file->source_url }}" target="_blank" class="bm-url">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            {{ __('viewer.open_new_tab_btn') }}
        </a>
    </div>
</div>

<script>
const SOURCE_URL = @json($file->source_url);
const EMBED_URL  = @json($file->getEmbedUrl());
const VIEWER_STR = {
    fullscreen_title:      '{{ __("viewer.fullscreen_title") }}',
    fullscreen_exit_title: '{{ __("viewer.fullscreen_exit_title") }}',
    fullscreen:            '{{ __("viewer.fullscreen") }}',
    fullscreen_exit:       '{{ __("viewer.fullscreen_exit") }}',
    popup_blocked_msg:     {{ json_encode(__('viewer.popup_blocked_msg')) }},
};

function onFrameLoad() {
    setTimeout(() => {
        const f = document.getElementById('viewer-frame');
        try {
            if (f.contentDocument && f.contentDocument.body.innerHTML === '') showBlockMsg();
        } catch(e) {
            // cross-origin → 정상 로드로 간주
        }
    }, 2500);
}

function showBlockMsg() {
    document.getElementById('viewer-frame').style.display = 'none';
    const bm = document.getElementById('block-msg');
    bm.style.display = 'flex';
}

function openInNewTab() {
    window.open(SOURCE_URL, '_blank');
}

function downloadPdf() {
    // 새 창에서 URL을 열고 인쇄 다이얼로그 자동 실행
    const win = window.open(SOURCE_URL, '_blank', 'width=1280,height=900');
    if (!win) {
        alert(VIEWER_STR.popup_blocked_msg);
        return;
    }
    win.addEventListener('load', () => {
        setTimeout(() => {
            try { win.print(); } catch(e) {}
        }, 800);
    });
}

function toggleFullscreen() {
    const isFs = document.body.classList.toggle('fullscreen');
    const btn  = document.getElementById('fs-btn');
    const icon = document.getElementById('fs-icon');
    if (isFs) {
        btn.title = VIEWER_STR.fullscreen_exit_title;
        btn.childNodes[1].textContent = ' ' + VIEWER_STR.fullscreen_exit;
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M9 9L4 4m0 0v4m0-4h4m7-1l5 5m0 0v-4m0 4h-4M9 15l-5 5m0 0h4m-4 0v-4m16 0l-5-5m0 5h4m0 0v-4"/>';
    } else {
        btn.title = VIEWER_STR.fullscreen_title;
        btn.childNodes[1].textContent = ' ' + VIEWER_STR.fullscreen;
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>';
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'F' || e.key === 'f') { e.preventDefault(); toggleFullscreen(); }
    if (e.key === 'Escape' && document.body.classList.contains('fullscreen')) toggleFullscreen();
});
</script>
</body>
</html>
