@php
$_serveUrl       = $customServeUrl      ?? route('files.public-serve', $token);
$_commentsUrl    = $customCommentsUrl   ?? route('files.public-comments.index', $token);
$_commentPostUrl = $customCommentPost   ?? route('files.public-comments.store', $token);
$_annGetUrl      = $customAnnGet        ?? route('files.public-annotations.index', $token);
$_annPostUrl     = $customAnnPost       ?? route('files.public-annotations.store', $token);
$_annUpdateBase  = $customAnnBase       ?? (url("share/file/{$token}/annotations") . '/');
// 버전 비교 — 유지보수 파일 공유 등 다른 컨트롤러에서 렌더될 때를 위한 기본값
$versions       = $versions       ?? [];
$activeVersion  = $activeVersion  ?? null;
$currentVersion = $currentVersion ?? null;
$isEmbed        = $isEmbed        ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('favicon.png') }}" type="image/png">
    <title>{{ $file->original_name }} — {{ __('team.shared_file') }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; background: #1e1b2e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; overflow: hidden; }

        .topbar { position:fixed;top:0;left:0;right:0;z-index:100;height:54px;background:rgba(20,17,35,.97);backdrop-filter:blur(12px);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:12px;padding:0 18px; }
        .topbar-logo   { font-size:13px;font-weight:800;color:#a78bfa;letter-spacing:-.3px;flex-shrink:0;padding:4px 10px;background:rgba(167,139,250,.12);border:1px solid rgba(167,139,250,.2);border-radius:7px; }
        .topbar-sep    { color:rgba(196,181,253,.25);flex-shrink:0; }
        .topbar-title  { flex:1;overflow:hidden;font-size:13px;font-weight:600;color:#e5e7eb;white-space:nowrap;text-overflow:ellipsis; }
        .topbar-badge  { font-size:11px;font-weight:700;padding:3px 10px;border-radius:5px;flex-shrink:0; }
        .badge-office  { background:#1e3a5f;color:#60a5fa; }
        .badge-pdf     { background:#3f1515;color:#f87171; }
        .badge-image   { background:#1a3321;color:#4ade80; }
        .badge-url     { background:#2d1a5f;color:#c4b5fd; }
        .topbar-shared { display:inline-flex;align-items:center;gap:5px;font-size:11px;font-weight:600;color:#a78bfa;padding:4px 10px;border:1px solid rgba(167,139,250,.25);border-radius:20px;flex-shrink:0; }

        #ann-toolbar { position:fixed;top:54px;left:0;right:0;z-index:99;height:42px;background:rgba(12,9,26,.98);border-bottom:1px solid rgba(196,181,253,.08);display:none;align-items:center;gap:4px;padding:0 14px; }
        .ann-tool-btn { display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:6px;color:#9ca3af;cursor:pointer;transition:background .15s,color .15s;padding:0;flex-shrink:0; }
        .ann-tool-btn:hover  { background:rgba(196,181,253,.15);color:#c4b5fd; }
        .ann-tool-btn.active { background:rgba(196,181,253,.28);color:#c4b5fd;border-color:rgba(196,181,253,.45); }
        .ann-color-btn { width:16px;height:16px;border-radius:50%;border:none;cursor:pointer;padding:0;flex-shrink:0; }

        #main-wrap { position:fixed;top:54px;left:0;right:0;bottom:0;display:flex;overflow:hidden; }
        #main-wrap.has-toolbar { top:96px; }

        #viewer-area { flex:1;min-width:0;position:relative;background:#1f2937;overflow:hidden;display:flex;flex-direction:column; }

        #viewer-loading { position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:14px;gap:12px;z-index:10;background:#1f2937; }
        .spinner { width:36px;height:36px;border:3px solid rgba(196,181,253,.2);border-top-color:#9b8afb;border-radius:50%;animation:spin .8s linear infinite; }
        @keyframes spin { to { transform:rotate(360deg); } }

        /* iframe (office/url) */
        #viewer-frame { width:100%;height:100%;position:absolute;inset:0;border:none;display:none;z-index:1; }

        /* ── Image viewer ── */
        #viewer-img-wrap { display:none;position:absolute;inset:0;z-index:1; }
        #img-scroll-wrap {
            position:absolute; top:0; left:0; right:0; bottom:44px;
            overflow:auto; background:#1f2937;
            cursor:grab; user-select:none;
        }
        #viewer-img-wrap .zoom-bar { position:absolute; bottom:0; left:0; right:0; height:44px; padding:0 16px; }
        #img-scroll-wrap.grabbing { cursor:grabbing !important; }
        #img-scroll-wrap.crosshair { cursor:crosshair !important; }
        /* ← FIX: flex + margin:auto → centers when small, scrollable-both-sides when large */
        #img-inner {
            display:flex; align-items:flex-start; justify-content:flex-start;
            min-width:100%; min-height:100%;
            padding:20px; box-sizing:border-box;
        }
        #img-rel-wrap {
            position:relative; display:block; line-height:0;
            margin:auto; flex-shrink:0; /* margin:auto centers, flex-shrink:0 prevents collapse */
        }
        #viewer-img { display:block; }

        /* ── PDF viewer ── */
        #viewer-pdf { display:none;position:absolute;inset:0;overflow:hidden;z-index:1; }
        #pdf-canvas-wrap {
            position:absolute; top:0; left:0; right:0; bottom:44px;
            overflow:auto; background:#374151;
            cursor:grab; user-select:none;
        }
        #pdf-canvas-wrap.grabbing  { cursor:grabbing !important; }
        #pdf-canvas-wrap.crosshair { cursor:crosshair !important; }
        #pdf-rel-wrap { position:relative; display:block; margin:20px auto; line-height:0; width:fit-content; }
        #pdf-canvas   { display:block; box-shadow:0 4px 24px rgba(0,0,0,.5); }
        #viewer-pdf .zoom-bar { position:absolute; bottom:0; left:0; right:0; height:44px; padding:0 16px; }

        /* SVG overlay — always pointer-events:none; events captured on scroll container */
        .ann-svg-overlay { position:absolute; inset:0; width:100%; height:100%; pointer-events:none; z-index:5; overflow:visible; }

        /* URL block */
        #url-block { display:none;position:absolute;inset:0;flex-direction:column;align-items:center;justify-content:center;background:#1e1b2e;gap:14px;padding:40px;text-align:center;z-index:3; }
        #url-block .bm-icon  { font-size:48px; }
        #url-block .bm-title { font-size:17px;font-weight:700;color:#e9d5ff; }
        #url-block .bm-desc  { font-size:13px;color:#a78bfa;max-width:400px;line-height:1.7; }
        #url-block .bm-btn   { display:inline-flex;align-items:center;gap:6px;padding:10px 22px;background:#7c3aed;color:#fff;border-radius:10px;font-size:13px;font-weight:600;text-decoration:none; }

        .zoom-bar { display:flex;align-items:center;justify-content:center;gap:10px;padding:8px 16px;background:#111827;flex-shrink:0;border-top:1px solid rgba(255,255,255,.07); }
        .zoom-btn { padding:5px 10px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:14px;cursor:pointer;line-height:1;transition:background .15s; }
        .zoom-btn:hover { background:rgba(255,255,255,.13); }
        .zoom-label { font-size:12px;color:#9ca3af;min-width:40px;text-align:center; }

        /* Comment panel */
        #comment-panel { width:260px;flex-shrink:0;background:#fff;border-left:1px solid #e5e7eb;display:flex;flex-direction:column;min-width:200px;max-width:720px; }

        /* Comment panel resizer (가로 드래그) — 항상 보이는 6점 그립 아이콘 */
        #cp-resizer {
            width:6px; flex-shrink:0; background:#e5e7eb; cursor:col-resize;
            transition:background .12s; position:relative;
        }
        #cp-resizer::before {
            content:''; position:absolute; top:50%; left:50%;
            transform:translate(-50%, -50%);
            width:2px; height:2px; background:transparent; border-radius:50%;
            box-shadow:
                -3px -9px 0 1px #6b7280,  3px -9px 0 1px #6b7280,
                -3px  0   0 1px #6b7280,  3px  0   0 1px #6b7280,
                -3px  9px 0 1px #6b7280,  3px  9px 0 1px #6b7280;
            opacity:.7;
            transition:opacity .15s;
            pointer-events:none;
        }
        #cp-resizer:hover, #cp-resizer.dragging { background:#a78bfa; }
        #cp-resizer:hover::before, #cp-resizer.dragging::before {
            opacity:1;
            box-shadow:
                -3px -9px 0 1px #fff,  3px -9px 0 1px #fff,
                -3px  0   0 1px #fff,  3px  0   0 1px #fff,
                -3px  9px 0 1px #fff,  3px  9px 0 1px #fff;
        }
        body.cp-resizing { cursor:col-resize !important; user-select:none !important; }
        body.cp-resizing iframe { pointer-events:none !important; }   /* 드래그 중 iframe 이벤트 가로채기 방지 */

        /* 의견 페이지 입력 — 기본 브라우저 number 스피너 화살표 숨김 (자체 +/- 버튼 사용) */
        #comment-page::-webkit-outer-spin-button,
        #comment-page::-webkit-inner-spin-button {
            -webkit-appearance: none; appearance: none; margin: 0;
        }
        #comment-page { -moz-appearance: textfield; appearance: textfield; }
        .cp-header  { padding:12px 16px 10px;border-bottom:1px solid #f3f4f6;flex-shrink:0; }
        .cp-title   { font-size:14px;font-weight:700;color:#1f2937;display:flex;align-items:center;gap:6px; }
        #comment-count { font-size:11px;background:#ede9fe;color:#6d28d9;padding:1px 7px;border-radius:10px;font-weight:700; }
        #comment-list  { flex:1;overflow-y:auto;padding:12px 14px;display:flex;flex-direction:column;gap:10px; }
        #comment-empty { color:#9ca3af;font-size:13px;text-align:center;padding:24px 0; }
        .cp-form    { padding:12px 14px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9; }
        .cp-input   { width:100%;padding:8px 10px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:inherit; }
        .cp-input:focus { border-color:#a78bfa; }
        .cp-textarea{ width:100%;padding:9px 11px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;color:#1f2937;resize:none;outline:none;transition:border-color .15s;box-sizing:border-box;font-family:inherit; }
        .cp-textarea:focus { border-color:#a78bfa; }
        .cp-submit  { padding:7px 16px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer; }
        .comment-card { background:#f9fafb;border:1px solid #f3f4f6;border-radius:10px;padding:10px 12px; }
        .page-badge   { display:inline-block;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px; }

        /* ── 다운로드 프로그래스 바 ── */
        .sw-dlp{position:fixed;z-index:999999;pointer-events:none;min-width:60px;}
        .sw-dlp-track{height:4px;background:rgba(196,181,253,.35);border-radius:2px;overflow:hidden;}
        .sw-dlp-fill{height:100%;width:0%;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:2px;transition:width .25s ease;}
        .sw-dlp-fill.sw-dlp-indet{width:38%;animation:sw-dlp-slide 1.1s infinite ease-in-out;transition:none;}
        @keyframes sw-dlp-slide{0%{transform:translateX(-110%)}100%{transform:translateX(370%)}}
        .sw-dlp-pct{display:block;font-size:10px;color:#a78bfa;font-weight:700;text-align:right;margin-top:2px;line-height:1;white-space:nowrap;}

        /* ── 버전 비교 ── */
        #compare-modal{display:none;position:fixed;inset:0;z-index:10006;background:#0d0a1c;flex-direction:column;}
        .cmp-topbar{height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:14px;padding:0 16px;flex-shrink:0;}
        .cmp-body{display:flex;flex:1;min-height:0;}
        .cmp-pane{flex:1;min-width:0;display:flex;flex-direction:column;}
        .cmp-pane.cmp-left{border-right:2px solid #2d2748;}
        .cmp-pane-label{flex-shrink:0;height:32px;display:flex;align-items:center;gap:8px;padding:0 14px;background:rgba(124,58,237,.13);border-bottom:1px solid rgba(196,181,253,.14);font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;}
        .cmp-frame{flex:1;width:100%;border:none;background:#1a1730;}
        .vcmp-row{display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:7px;cursor:pointer;transition:background .12s;}
        .vcmp-row:hover{background:rgba(196,181,253,.1);}
        .vcmp-row.is-checked{background:rgba(125,211,252,.14);}
        .vcmp-row.is-disabled{opacity:.4;cursor:not-allowed;}
        .vcmp-row input{width:15px;height:15px;accent-color:#0ea5e9;cursor:pointer;flex-shrink:0;}
        .vcmp-vtag{font-size:11px;font-weight:700;color:#c4b5fd;background:rgba(196,181,253,.15);padding:2px 7px;border-radius:4px;flex-shrink:0;}
        .vcmp-meta{display:flex;flex-direction:column;min-width:0;}
        .vcmp-name{font-size:12px;color:#e5e7eb;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .vcmp-sub{font-size:10px;color:#8b85a8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

    </style>
</head>
<body>

<div class="topbar">
    <span class="topbar-logo">SupportWorks</span>
    <span class="topbar-sep">›</span>
    <span class="topbar-title">{{ $file->original_name }}</span>
    @php
        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        if ($file->isUrlType()) {
            $badgeClass = 'badge-url'; $badgeLabel = 'URL';
        } else {
            $pt = $file->previewType();
            $badgeClass = match($pt) { 'office'=>'badge-office','pdf'=>'badge-pdf',default=>'badge-image' };
            $badgeLabel = match($ext) {
                'docx','doc'=>'Word','xlsx','xls'=>'Excel','pptx','ppt'=>'PowerPoint','pdf'=>'PDF',
                default=>strtoupper($ext)?:__('viewer.file_type_fallback'),
            };
        }
        $previewType   = $customPreviewType ?? ($file->isUrlType() ? 'url' : $file->previewType());
        $hasAnnotation = in_array($previewType, ['image','pdf']);
    @endphp
    <span class="topbar-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
    @if(!$isEmbed && count($versions) >= 2)
    <button id="cmp-open-btn" type="button" onclick="openVersionCompareMenu(event)"
            title="{{ __('files.compare_versions_title') }}"
            style="display:inline-flex;align-items:center;gap:4px;color:#7dd3fc;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(125,211,252,.3);border-radius:7px;background:none;cursor:pointer;flex-shrink:0;"
            onmouseover="this.style.background='rgba(125,211,252,.1)'" onmouseout="this.style.background='none'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/></svg>
        {{ __('files.compare_versions') }}
    </button>
    @endif
    @if($hasAnnotation)
    <button id="ann-dl-btn" onclick="downloadAnnotatedPdf()" style="display:inline-flex;align-items:center;gap:4px;color:#c4b5fd;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(196,181,253,.25);border-radius:7px;flex-shrink:0;background:none;cursor:pointer;">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        {{ __('team.download_with_review') }}
    </button>
    @endif
    <span class="topbar-shared">
        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
        {{ __('team.shared_file') }}
    </span>

    @if(!$isEmbed)
    {{-- 공유 링크 복사 --}}
    <button id="share-copy-btn" onclick="copyShareLink()" type="button"
            style="display:inline-flex;align-items:center;gap:4px;color:#c4b5fd;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(196,181,253,.25);border-radius:7px;background:none;cursor:pointer;flex-shrink:0;"
            onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
        <span id="share-copy-label">{{ __('files.copy_share_link') }}</span>
    </button>

    {{-- 전체창 토글 --}}
    <button id="fs-toggle-btn" type="button" onclick="toggleFullscreen()" title="{{ __('files.fullscreen_title') }}"
            style="display:inline-flex;align-items:center;gap:4px;color:#c4b5fd;font-size:12px;font-weight:600;padding:5px 10px;border:1px solid rgba(196,181,253,.25);border-radius:7px;background:none;cursor:pointer;flex-shrink:0;"
            onmouseover="this.style.background='rgba(196,181,253,.1)'" onmouseout="this.style.background='none'">
        <svg id="fs-icon-on"  width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V5H5m14 4V5h-4M9 15v4H5m14-4v4h-4"/></svg>
        <svg id="fs-icon-off" width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4m12-4v4h-4"/></svg>
        <span id="fs-toggle-label">{{ __('files.fullscreen') }}</span>
    </button>

    {{-- SupportWorks 가입하기 (비로그인 시) --}}
    @guest
    <a href="{{ route('files.public-share.signup', $token) }}"
       style="display:inline-flex;align-items:center;gap:4px;color:#fff;font-size:12px;font-weight:700;padding:6px 12px;background:linear-gradient(135deg,#7c3aed,#6366f1);border-radius:7px;text-decoration:none;flex-shrink:0;transition:opacity .15s;"
       onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        {{ __('files.signup') }}
    </a>
    @endguest
    @endif
</div>

@if($hasAnnotation)
<div id="ann-toolbar">
    <span style="font-size:10px;font-weight:600;color:#6b7280;letter-spacing:.4px;margin-right:4px;">{{ __('team.ann_toolbar_shapes') }}</span>
    <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 4px;"></div>
    <button id="ann-btn-number" onclick="setAnnTool('number')" title="{{ __('team.ann_label_number') }}" class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.5" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
    <button id="ann-btn-rect"   onclick="setAnnTool('rect')"   title="{{ __('team.ann_label_rect') }}"    class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
    <button id="ann-btn-circle" onclick="setAnnTool('circle')" title="{{ __('team.ann_label_circle') }}" class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
    <button id="ann-btn-line"   onclick="setAnnTool('line')"   title="{{ __('team.ann_label_line') }}"   class="ann-tool-btn"><svg width="14" height="14" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
    <button id="ann-btn-text"   onclick="setAnnTool('text')"   title="{{ __('team.ann_label_text') }}"   class="ann-tool-btn" style="font-size:13px;font-weight:700;line-height:1;">T</button>
    <div style="width:1px;height:16px;background:rgba(255,255,255,.08);margin:0 6px;"></div>
    <span style="font-size:10px;color:#6b7280;margin-right:4px;">{{ __('team.ann_color_label') }}</span>
    <button onclick="setAnnColor('#ef4444')" data-color="#ef4444" class="ann-color-btn" style="background:#ef4444;outline:2px solid #fff;outline-offset:2px;"></button>
    <button onclick="setAnnColor('#f97316')" data-color="#f97316" class="ann-color-btn" style="background:#f97316;"></button>
    <button onclick="setAnnColor('#eab308')" data-color="#eab308" class="ann-color-btn" style="background:#eab308;"></button>
    <button onclick="setAnnColor('#22c55e')" data-color="#22c55e" class="ann-color-btn" style="background:#22c55e;"></button>
    <button onclick="setAnnColor('#3b82f6')" data-color="#3b82f6" class="ann-color-btn" style="background:#3b82f6;"></button>
    <button onclick="setAnnColor('#a855f7')" data-color="#a855f7" class="ann-color-btn" style="background:#a855f7;"></button>
    <div style="flex:1;"></div>
    <span id="ann-mode-hint" style="font-size:10px;color:#4b5563;">{{ __('team.ann_hint_idle') }}</span>
</div>
@endif

<div id="main-wrap" class="{{ $hasAnnotation ? 'has-toolbar' : '' }}">
    <div id="viewer-area">
        <div id="viewer-loading">
            <div class="spinner"></div>
            <span id="loading-label">{{ __('team.loading_label') }}</span>
        </div>

        @if($previewType === 'url')
            <iframe id="viewer-frame"
                    src="{{ $file->getEmbedUrl() }}"
                    allowfullscreen allow="fullscreen"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-top-navigation"
                    onload="onFrameLoad()" onerror="showUrlBlock()"></iframe>
            <div id="url-block">
                <div class="bm-icon">🔒</div>
                <div class="bm-title">{{ __('team.embed_blocked_title') }}</div>
                <div class="bm-desc">{!! __('team.embed_blocked_desc') !!}</div>
                <a href="{{ $file->source_url }}" target="_blank" class="bm-btn">{{ __('team.open_new_tab') }}</a>
            </div>

        @elseif($previewType === 'image')
            <div id="viewer-img-wrap">
                <div id="img-scroll-wrap">
                    <div id="img-inner">
                        {{-- margin:auto on img-rel-wrap → centered when small, fully scrollable when large --}}
                        <div id="img-rel-wrap">
                            <img id="viewer-img"
                                 src="{{ $_serveUrl }}"
                                 alt="{{ $file->original_name }}"
                                 onload="onImgLoad()" onerror="onImgLoad()">
                            <svg id="ann-svg" class="ann-svg-overlay" xmlns="http://www.w3.org/2000/svg"></svg>
                        </div>
                    </div>
                </div>
                <div class="zoom-bar">
                    <button class="zoom-btn" onclick="imgZoom(-0.25)">−</button>
                    <span id="img-zoom-label" class="zoom-label">{{ __('team.zoom_fit') }}</span>
                    <button class="zoom-btn" onclick="imgZoom(0.25)">+</button>
                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                    <button class="zoom-btn" style="font-size:12px;" onclick="imgZoomFit()">{{ __('team.zoom_fit') }}</button>
                    <button class="zoom-btn" style="font-size:12px;" onclick="imgZoomOriginal()">{{ __('team.zoom_original') }}</button>
                </div>
            </div>

        @elseif($previewType === 'pdf')
            <div id="viewer-pdf">
                <div id="pdf-canvas-wrap">
                    <div id="pdf-rel-wrap">
                        <canvas id="pdf-canvas"></canvas>
                        <svg id="ann-svg" class="ann-svg-overlay" xmlns="http://www.w3.org/2000/svg"></svg>
                    </div>
                </div>
                <div class="zoom-bar">
                    <button id="pdf-prev-btn" onclick="pdfPrevPage()" style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>{{ __('common.prev') }}
                    </button>
                    <span id="pdf-page-info" style="font-size:13px;font-weight:600;color:#e5e7eb;min-width:100px;text-align:center;">— / —</span>
                    <button id="pdf-next-btn" onclick="pdfNextPage()" style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;">
                        {{ __('common.next') }}<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                    <button class="zoom-btn" onclick="pdfZoom(-0.2)">−</button>
                    <span id="pdf-zoom-label" class="zoom-label">100%</span>
                    <button class="zoom-btn" onclick="pdfZoom(0.2)">+</button>
                </div>
            </div>

        @elseif($previewType === 'office')
            @php $officeUrl='https://view.officeapps.live.com/op/embed.aspx?src='.urlencode($_serveUrl); @endphp
            <iframe id="viewer-frame" src="{{ $officeUrl }}" onload="onFrameLoad()"></iframe>

        @elseif($previewType === 'video')
            {{-- 프로젝트 파일 뷰어와 동일한 커스텀 동영상 컨트롤 바 --}}
            <div id="viewer-video" style="position:absolute;inset:0;display:flex;flex-direction:column;background:#000;">
                <div id="vid-wrap" style="flex:1;min-height:0;position:relative;display:flex;align-items:center;justify-content:center;background:#000;">
                    <video id="vid-el" src="{{ $_serveUrl }}" preload="metadata" playsinline
                           style="max-width:100%;max-height:100%;display:block;"></video>
                    {{-- 재생 중 해당 시점 의견 토스트 --}}
                    <div id="vid-comment-toast" style="display:none;position:absolute;top:18px;left:18px;right:18px;max-width:560px;margin:0 auto;background:rgba(15,12,30,.94);border:1px solid rgba(196,181,253,.35);color:#fff;border-radius:10px;padding:10px 14px;font-size:13px;line-height:1.5;box-shadow:0 8px 28px rgba(0,0,0,.4);z-index:5;"></div>
                </div>
                {{-- 컨트롤 바 --}}
                <div style="background:#111827;border-top:1px solid rgba(255,255,255,.07);padding:8px 14px 10px;flex-shrink:0;">
                    {{-- 타임라인 + 의견 시점 마커 --}}
                    <div id="vid-track-wrap" style="position:relative;height:18px;margin-bottom:8px;cursor:pointer;user-select:none;">
                        <div id="vid-track" style="position:absolute;top:7px;left:0;right:0;height:5px;background:rgba(255,255,255,.14);border-radius:3px;transition:height .15s,top .15s;pointer-events:none;"></div>
                        <div id="vid-progress" style="position:absolute;top:7px;left:0;width:0;height:5px;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:3px;pointer-events:none;transition:height .15s,top .15s;"></div>
                        <div id="vid-thumb" style="display:none;position:absolute;top:50%;transform:translate(-50%,-50%);width:13px;height:13px;background:#fff;border:2px solid #7c3aed;border-radius:50%;box-shadow:0 2px 6px rgba(0,0,0,.4);pointer-events:none;"></div>
                        <div id="vid-hover-time" style="display:none;position:absolute;bottom:22px;transform:translateX(-50%);background:rgba(15,12,30,.95);color:#fff;font-size:11px;font-weight:600;padding:3px 8px;border-radius:5px;pointer-events:none;font-variant-numeric:tabular-nums;white-space:nowrap;"></div>
                        <div id="vid-markers" style="position:absolute;inset:0;pointer-events:none;"></div>
                    </div>
                    {{-- 컨트롤 버튼 --}}
                    <div style="display:flex;align-items:center;justify-content:center;gap:12px;">
                        <button type="button" onclick="vidSeekRelative(-10)" title="10초 뒤로"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5 5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M18 17l-5-5 5-5"/></svg>
                            10초 뒤로
                        </button>
                        <button type="button" id="vid-play-btn" onclick="vidTogglePlay()" title="재생/일시정지"
                                style="display:inline-flex;align-items:center;justify-content:center;width:42px;height:42px;background:linear-gradient(135deg,#7c3aed,#9b8afb);border:none;color:#fff;border-radius:50%;cursor:pointer;flex-shrink:0;box-shadow:0 4px 14px rgba(124,58,237,.45);"
                                onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
                            <svg id="vid-play-icon" width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        </button>
                        <button type="button" onclick="vidSeekRelative(10)" title="10초 앞으로"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                            10초 앞으로
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 17l5-5-5-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 17l5-5-5-5"/></svg>
                        </button>
                        <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                        <span id="vid-time-label" style="font-size:12px;color:#9ca3af;font-variant-numeric:tabular-nums;min-width:90px;text-align:center;">0:00 / 0:00</span>
                        <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin:0 4px;"></div>
                        <button type="button" onclick="vidPauseAndAddComment()" title="현재 시점에 의견 추가"
                                style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;background:rgba(196,181,253,.15);border:1px solid rgba(196,181,253,.3);color:#c4b5fd;border-radius:7px;font-size:12px;cursor:pointer;font-weight:600;"
                                onmouseover="this.style.background='rgba(196,181,253,.25)'" onmouseout="this.style.background='rgba(196,181,253,.15)'">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            이 시점 의견
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- 패널 접힘 시 보이는 좌측 핸들 --}}
    <button id="comment-panel-handle" onclick="toggleCommentPanel()" title="{{ __('files.open_comment_panel') }}"
            style="display:none;width:28px;flex-shrink:0;background:#ede9fe;border:none;border-left:1px solid #c4b5fd;cursor:pointer;align-items:center;justify-content:center;color:#6d28d9;padding:0;writing-mode:vertical-rl;font-size:11px;font-weight:700;letter-spacing:.05em;">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="transform:rotate(-90deg);margin-bottom:8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        {{ __('files.show_comments') }}
    </button>

    {{-- 가로 리사이저 (의견 영역 폭 조절) --}}
    <div id="cp-resizer" title="{{ __('files.resize_comment_panel') }}"></div>

    <div id="comment-panel">
        <div class="cp-header">
            <div class="cp-title">
                <svg width="15" height="15" fill="none" stroke="#6d28d9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                {{ __('team.opinion_count') }} <span id="comment-count"></span>
                <button onclick="toggleCommentPanel()" title="{{ __('files.collapse_comment_panel') }}"
                        style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;padding:2px 4px;border-radius:5px;display:inline-flex;align-items:center;justify-content:center;"
                        onmouseover="this.style.color='#6d28d9';this.style.background='#f5f3ff'" onmouseout="this.style.color='#9ca3af';this.style.background='none'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
            @if($previewType === 'pdf')
            <div id="cmt-filter-bar" style="margin-top:8px;padding-top:8px;border-top:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:8px;">
                <span id="cmt-filter-label" style="font-size:11px;color:#6b7280;"></span>
                <button onclick="togglePageFilter()" id="cmt-filter-btn" style="font-size:11px;color:#7c3aed;background:none;border:1px solid #ede9fe;border-radius:5px;cursor:pointer;padding:2px 8px;font-weight:600;"></button>
            </div>
            @endif
        </div>
        <div id="comment-list">
            <div id="comment-empty" style="color:#9ca3af;font-size:13px;text-align:center;padding:24px 0;">{{ __('team.comment_empty') }}</div>
        </div>
        <div class="cp-form">
            <div style="display:flex;gap:8px;margin-bottom:6px;">
                <input id="guest-name" type="text" placeholder="{{ __('team.name_placeholder') }}" maxlength="100" class="cp-input" style="flex:1;">
                <button onclick="submitComment()" id="submit-btn-name" class="cp-submit" style="flex-shrink:0;white-space:nowrap;">{{ __('team.submit_btn') }}</button>
            </div>
            @if(in_array($previewType, ['pdf','image']))
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <label style="font-size:11px;font-weight:600;color:#6b7280;white-space:nowrap;">{{ __('team.page_label') }}</label>
                <div style="display:flex;align-items:center;border:1.5px solid #e5e7eb;border-radius:7px;overflow:hidden;background:#fff;">
                    <button type="button" onclick="adjustPage(-1)" style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">−</button>
                    <input type="number" id="comment-page" min="1" max="9999" placeholder="—" style="width:48px;text-align:center;border:none;outline:none;font-size:13px;font-weight:600;color:#1f2937;padding:4px 0;">
                    <button type="button" onclick="adjustPage(1)"  style="padding:4px 8px;background:none;border:none;cursor:pointer;color:#6b7280;font-size:14px;line-height:1;">+</button>
                </div>
                <span style="font-size:10px;color:#9ca3af;">{{ __('team.page_all') }}</span>
            </div>
            @endif
            <textarea id="comment-input" rows="3" placeholder="{{ __('team.opinion_placeholder') }}" class="cp-textarea"
                      onkeydown="if((event.ctrlKey||event.metaKey)&&event.key==='Enter')submitComment()"></textarea>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:7px;">
                <span style="font-size:11px;color:#9ca3af;">{{ __('team.ctrl_enter_hint') }}</span>
                <button onclick="submitComment()" id="submit-btn" class="cp-submit">{{ __('team.submit_btn') }}</button>
            </div>
        </div>
    </div>
</div>

@if($hasAnnotation)
<div id="ann-text-popup" style="display:none;position:fixed;z-index:10010;background:#fff;border:2px solid #a78bfa;border-radius:10px;padding:12px 14px;box-shadow:0 8px 30px rgba(0,0,0,.25);min-width:280px;max-width:360px;">
    <div style="font-size:11px;font-weight:700;color:#6d28d9;margin-bottom:8px;">{{ __('team.ann_text_popup_title') }}</div>
    <textarea id="ann-text-input" rows="4" placeholder="{{ __('team.ann_text_placeholder') }}"
              style="width:100%;border:1.5px solid #e5e7eb;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;font-family:inherit;"
              onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button onclick="confirmAnnText()" style="flex:1;padding:6px 0;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">{{ __('team.ann_confirm') }}</button>
        <button onclick="cancelAnnText()"  style="flex:1;padding:6px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">{{ __('team.ann_cancel') }}</button>
    </div>
</div>

<div id="ann-info-popup" style="display:none;position:fixed;z-index:10005;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:10px 14px;box-shadow:0 4px 20px rgba(0,0,0,.18);min-width:160px;max-width:220px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
        <span id="ann-info-type" style="font-size:10px;font-weight:700;color:#7c3aed;background:#ede9fe;padding:2px 7px;border-radius:4px;"></span>
        <button onclick="hideAnnInfoPopup()" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 2px;">&times;</button>
    </div>
    <div style="display:flex;align-items:center;gap:4px;margin-bottom:2px;">
        <svg width="11" height="11" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        <span id="ann-info-name" style="font-size:12px;font-weight:700;color:#374151;"></span>
    </div>
    <div id="ann-info-time" style="font-size:11px;color:#9ca3af;padding-left:16px;"></div>
    <button id="ann-info-del" onclick="deleteAnnFromInfo()" style="display:none;margin-top:8px;width:100%;padding:5px 0;background:#fee2e2;color:#dc2626;border:none;border-radius:6px;font-size:12px;font-weight:600;cursor:pointer;">{{ __('team.ann_delete') }}</button>
</div>
@endif

@if($previewType === 'pdf')
<script src="https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
@endif
@if($hasAnnotation)
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
@endif
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

<script>
const PREVIEW_TYPE    = '{{ $previewType }}';
const SERVE_URL       = '{{ $_serveUrl }}';
const COMMENTS_URL    = '{{ $_commentsUrl }}';
const COMMENT_POST    = '{{ $_commentPostUrl }}';
const ANN_GET_URL     = '{{ $_annGetUrl }}';
const ANN_POST_URL    = '{{ $_annPostUrl }}';
const ANN_UPDATE_BASE = '{{ $_annUpdateBase }}';
const HAS_ANN         = {{ $hasAnnotation ? 'true' : 'false' }};
const CSRF            = '{{ csrf_token() }}';
const FILE_ID         = {{ $file->id }};
const PUSHER_KEY      = '{{ config("broadcasting.connections.pusher.key") }}';
const PUSHER_CLUSTER  = '{{ config("broadcasting.connections.pusher.options.cluster") }}';

const STR = {
    loading_label:      '{{ __("team.loading_label") }}',
    pdf_load_fail:      '{{ __("team.pdf_load_fail") }}',
    current_page:       '{{ __("team.current_page") }}',
    current_page_only:  '{{ __("team.current_page_only") }}',
    view_all_pages:     '{{ __("team.view_all_pages") }}',
    comment_all_pages:  '{{ __("team.comment_all_pages") }}',
    enter_name:         '{{ __("team.enter_name") }}',
    enter_name_first:   '{{ __("team.enter_name_first") }}',
    comment_fail:       '{{ __("team.comment_fail") }}',
    ann_save_fail:      '{{ __("team.ann_save_fail") }}',
    ann_hint_idle:      '{{ __("team.ann_hint_idle") }}',
    ann_hint_active:    '{{ __("team.ann_hint_active") }}',
    ann_label_number:   '{{ __("team.ann_label_number") }}',
    ann_label_rect:     '{{ __("team.ann_label_rect") }}',
    ann_label_circle:   '{{ __("team.ann_label_circle") }}',
    ann_label_line:     '{{ __("team.ann_label_line") }}',
    ann_label_text:     '{{ __("team.ann_label_text") }}',
    pdf_js_missing:     '{{ __("team.pdf_js_missing") }}',
    jspdf_missing:      '{{ __("team.jspdf_missing") }}',
    pdf_gen_fail:       '{{ __("team.pdf_gen_fail") }}',
    pdf_gen_label:      '{{ __("team.generating") }}',
    review_suffix:      '{{ __("team.review_suffix") }}',
    zoom_fit:           '{{ __("team.zoom_fit") }}',
    external_reviewer:  '{{ __("team.unknown_inviter") }}',
    copy_share_link:        @json(__('files.copy_share_link')),
    copy_share_link_done:   @json(__('files.copy_share_link_done')),
    copy_share_link_prompt: @json(__('files.copy_share_link_prompt')),
    fullscreen:             @json(__('files.fullscreen')),
    fullscreen_exit:        @json(__('files.fullscreen_exit')),
    fullscreen_title_f11:   @json(__('files.fullscreen_title_f11')),
    fullscreen_exit_title:  @json(__('files.fullscreen_exit_title')),
    cmp_current:            @json(__('files.cmp_current')),
    cmp_current_version:    @json(__('files.cmp_current_version')),
    cmp_sync_pages:         @json(__('files.cmp_sync_pages')),
    cmp_sync_video:         @json(__('files.cmp_sync_video')),
    cmp_sync_note:          @json(__('files.cmp_sync_note')),
    cmp_pick_versions:      @json(__('files.cmp_pick_versions')),
    cmp_pick_hint:          @json(__('files.cmp_pick_hint')),
    cmp_select_prompt:      @json(__('files.cmp_select_prompt')),
};

// ── state ────────────────────────────────────────────────────
let _comments = [], _showAllPages = false, _pdfPage = 1;
let pdfDoc = null, pdfPage = 1, pdfTotal = 0, pdfScale = 1.0;
let pdfRendering = false, pdfPending = null;
let imgNatW = 0, imgNatH = 0;
let annTool = null, annColor = '#ef4444', annNextNum = 1;
let annList = [], annDragEl = null;
let annDrawing = false, annStartX = 0, annStartY = 0;
let _annTextPct = null;
// IDs of annotations created in this browser session (persisted in localStorage)
const _ANN_MINE_KEY = 'ann_mine_' + FILE_ID;
const myAnnIds = new Set(JSON.parse(localStorage.getItem(_ANN_MINE_KEY) || '[]'));
function _saveMyAnnIds() { localStorage.setItem(_ANN_MINE_KEY, JSON.stringify([...myAnnIds])); }
function _markMine(list) { list.forEach(a => { if (myAnnIds.has(a.id)) a.can_delete = true; }); }

// ── init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (PREVIEW_TYPE === 'pdf')   initPdf();
    if (PREVIEW_TYPE === 'url') {
        setTimeout(() => {
            const f = document.getElementById('viewer-frame');
            try { if (f && f.contentDocument && f.contentDocument.body.innerHTML === '') showUrlBlock(); }
            catch(e) {}
        }, 3000);
    }
    if (HAS_ANN) {
        document.getElementById('ann-toolbar').style.display = 'flex';
        initViewerEvents(); // unified drag + annotation handler
    } else if (PREVIEW_TYPE === 'image' || PREVIEW_TYPE === 'pdf') {
        initViewerEvents();
    }
    loadComments();
    _initPusher();
});

// ── unified viewer events ────────────────────────────────────
// All mouse interaction (drag-scroll, annotation drawing, annotation move) is
// handled on the scroll container. No pointer-events toggling on the SVG needed.
function initViewerEvents() {
    const scrollEl = document.getElementById(
        PREVIEW_TYPE === 'image' ? 'img-scroll-wrap' : 'pdf-canvas-wrap'
    );
    if (!scrollEl) return;

    let dragActive = false, dragX = 0, dragY = 0, dragSL = 0, dragST = 0;
    let dragMoved = false;
    let annMoving = false, annMoveAnn = null, annMoveStartData = null;
    let annMoveStartMx = 0, annMoveStartMy = 0, annMoveMoved = false;

    scrollEl.addEventListener('mousedown', e => {
        if (e.button !== 0) return;
        e.preventDefault();
        hideAnnInfoPopup();

        if (annTool) {
            // ── ANNOTATION DRAW MODE ─────────────────────
            const svg  = document.getElementById('ann-svg');
            if (!svg) return;
            const size = annSvgSize();
            if (!size) return;
            const sr = svg.getBoundingClientRect();
            const mx = e.clientX - sr.left;
            const my = e.clientY - sr.top;

            if (annTool === 'text') {
                _annTextPct = { x: mx/size.w*100, y: my/size.h*100 };
                showAnnTextPopup(e.clientX, e.clientY);
                return;
            }

            annDrawing = true;
            annStartX  = mx;
            annStartY  = my;
            annDragEl  = _svgEl('g');
            annDragEl.style.pointerEvents = 'none';
            svg.appendChild(annDragEl);

        } else {
            // ── MOVE or DRAG-SCROLL MODE ─────────────────
            const svg  = document.getElementById('ann-svg');
            const size = annSvgSize();
            let hit = null;
            if (HAS_ANN && svg && size) {
                const sr = svg.getBoundingClientRect();
                hit = findAnnAtPoint(e.clientX - sr.left, e.clientY - sr.top, size);
            }
            if (hit) {
                annMoving        = true;
                annMoveMoved     = false;
                annMoveAnn       = hit;
                annMoveStartData = JSON.parse(JSON.stringify(hit.data));
                annMoveStartMx   = e.clientX;
                annMoveStartMy   = e.clientY;
                scrollEl.classList.add('grabbing');
            } else {
                dragActive = true;
                dragMoved  = false;
                dragX = e.clientX; dragY = e.clientY;
                dragSL = scrollEl.scrollLeft; dragST = scrollEl.scrollTop;
                scrollEl.classList.add('grabbing');
            }
        }
    });

    document.addEventListener('mousemove', e => {
        if (annDrawing) {
            const svg = document.getElementById('ann-svg');
            if (!svg) return;
            const sr = svg.getBoundingClientRect();
            updateAnnPreview(e.clientX - sr.left, e.clientY - sr.top);
        } else if (annMoving) {
            const size = annSvgSize();
            if (!size || !annMoveAnn) return;
            const dx = e.clientX - annMoveStartMx;
            const dy = e.clientY - annMoveStartMy;
            if (Math.hypot(dx, dy) > 4) annMoveMoved = true;
            if (annMoveMoved) {
                annMoveAnn.data = applyAnnDelta(annMoveStartData, dx/size.w*100, dy/size.h*100, annMoveAnn.type);
                renderAnnotations();
            }
        } else if (dragActive) {
            if (Math.hypot(e.clientX - dragX, e.clientY - dragY) > 4) dragMoved = true;
            scrollEl.scrollLeft = dragSL - (e.clientX - dragX);
            scrollEl.scrollTop  = dragST - (e.clientY - dragY);
        }
    });

    document.addEventListener('mouseup', e => {
        if (annDrawing) {
            annDrawing = false;
            const svg = document.getElementById('ann-svg');
            if (!svg) return;
            const sr = svg.getBoundingClientRect();
            const ex = e.clientX - sr.left, ey = e.clientY - sr.top;
            if (annDragEl) { svg.removeChild(annDragEl); annDragEl = null; }
            commitAnnotation(annStartX, annStartY, ex, ey);
        } else if (annMoving) {
            annMoving = false;
            scrollEl.classList.remove('grabbing');
            if (annMoveMoved) {
                patchAnnotation(annMoveAnn.id, annMoveAnn.data);
                renderAnnotations();
            } else {
                // Short click on annotation → restore data and show info popup
                annMoveAnn.data = annMoveStartData;
                renderAnnotations();
                showAnnInfoPopup(annMoveAnn, e.clientX, e.clientY);
            }
            annMoveAnn = null; annMoveStartData = null;
        } else if (dragActive) {
            dragActive = false;
            scrollEl.classList.remove('grabbing');
            // Short tap (no drag) → check annotation hit
            if (!dragMoved) {
                const svg  = document.getElementById('ann-svg');
                const size = annSvgSize();
                if (svg && size) {
                    const sr  = svg.getBoundingClientRect();
                    const hit = findAnnAtPoint(e.clientX - sr.left, e.clientY - sr.top, size);
                    if (hit) showAnnInfoPopup(hit, e.clientX, e.clientY);
                }
            }
        }
    });

    // Hover cursor: pointer when over an existing annotation (not in draw/move mode)
    if (HAS_ANN) {
        scrollEl.addEventListener('mousemove', e => {
            if (annTool || dragActive || annDrawing || annMoving) { scrollEl.style.cursor = ''; return; }
            const svg  = document.getElementById('ann-svg');
            const size = annSvgSize();
            if (!svg || !size) return;
            const sr  = svg.getBoundingClientRect();
            scrollEl.style.cursor = findAnnAtPoint(e.clientX - sr.left, e.clientY - sr.top, size) ? 'pointer' : '';
        });
    }
}

// ── frame helpers ────────────────────────────────────────────
function onFrameLoad() {
    document.getElementById('viewer-loading').style.display = 'none';
    const f = document.getElementById('viewer-frame');
    if (f) f.style.display = 'block';
}
function showUrlBlock() {
    document.getElementById('viewer-loading').style.display = 'none';
    const f = document.getElementById('viewer-frame');
    if (f) f.style.display = 'none';
    document.getElementById('url-block').style.display = 'flex';
}

// ── image viewer ─────────────────────────────────────────────
function onImgLoad() {
    document.getElementById('viewer-loading').style.display = 'none';
    document.getElementById('viewer-img-wrap').style.display = 'block';
    const img = document.getElementById('viewer-img');
    imgNatW = img.naturalWidth  || img.offsetWidth  || 800;
    imgNatH = img.naturalHeight || img.offsetHeight || 600;
    imgZoomFit();
    if (HAS_ANN) loadAnnotations();
}
function imgZoomFit() {
    const img  = document.getElementById('viewer-img');
    const wrap = document.getElementById('img-scroll-wrap');
    const avW  = wrap.clientWidth  - 40;
    const avH  = wrap.clientHeight - 40;
    const s    = Math.min(avW / imgNatW, avH / imgNatH, 1);
    _setImgPx(Math.round(imgNatW * s), Math.round(imgNatH * s));
    document.getElementById('img-zoom-label').textContent = STR.zoom_fit;
}
function imgZoomOriginal() {
    _setImgPx(imgNatW, imgNatH);
    document.getElementById('img-zoom-label').textContent = '100%';
}
function imgZoom(delta) {
    const img  = document.getElementById('viewer-img');
    const wrap = document.getElementById('img-scroll-wrap');
    const cur  = img.offsetWidth / imgNatW || Math.min((wrap.clientWidth-40)/imgNatW, (wrap.clientHeight-40)/imgNatH, 1);
    const next = Math.max(0.05, Math.min(8, cur + delta));
    _setImgPx(Math.round(imgNatW * next), Math.round(imgNatH * next));
    document.getElementById('img-zoom-label').textContent = Math.round(next * 100) + '%';
}
function _setImgPx(w, h) {
    const img = document.getElementById('viewer-img');
    img.style.width  = w + 'px';
    img.style.height = h + 'px';
    if (HAS_ANN) requestAnimationFrame(renderAnnotations);
}

// ── PDF.js ───────────────────────────────────────────────────
function initPdf() {
    if (typeof pdfjsLib === 'undefined') return;
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    pdfjsLib.getDocument(SERVE_URL).promise.then(doc => {
        pdfDoc = doc; pdfTotal = doc.numPages;
        pdfScale = 1.0;
        document.getElementById('viewer-loading').style.display = 'none';
        document.getElementById('viewer-pdf').style.display     = 'block';
        renderPdfPage(1);
    }).catch(e => { document.getElementById('loading-label').textContent = STR.pdf_load_fail + e.message; });
}
function renderPdfPage(num) {
    if (!pdfDoc) return;
    if (pdfRendering) { pdfPending = num; return; }
    pdfRendering = true;
    pdfDoc.getPage(num).then(page => {
        const canvas = document.getElementById('pdf-canvas');
        const vp     = page.getViewport({ scale: pdfScale });
        canvas.width = vp.width; canvas.height = vp.height;
        // Keep SVG in sync with canvas size
        const svg = document.getElementById('ann-svg');
        if (svg) { svg.style.width = vp.width+'px'; svg.style.height = vp.height+'px'; }
        page.render({ canvasContext: canvas.getContext('2d'), viewport: vp }).promise.then(() => {
            pdfRendering = false;
            if (pdfPending) { const p = pdfPending; pdfPending = null; renderPdfPage(p); return; }
            const pageChanged = (num !== pdfPage);
            pdfPage = num; _pdfPage = num;
            if (CMP_IS_EMBED) _cmpPostPage(num);
            document.getElementById('pdf-page-info').textContent = `${num} / ${pdfTotal}`;
            document.getElementById('pdf-prev-btn').disabled = num <= 1;
            document.getElementById('pdf-next-btn').disabled = num >= pdfTotal;
            updatePageFilter();
            if (HAS_ANN) {
                if (pageChanged || !annList.length) loadAnnotations();
                else renderAnnotations();
            }
        });
    });
}
function pdfPrevPage() { if (pdfPage > 1) renderPdfPage(pdfPage - 1); }
function pdfNextPage() { if (pdfPage < pdfTotal) renderPdfPage(pdfPage + 1); }
function pdfZoom(delta) {
    pdfScale = Math.max(0.4, Math.min(4, pdfScale + delta));
    document.getElementById('pdf-zoom-label').textContent = Math.round(pdfScale * 100) + '%';
    renderPdfPage(pdfPage);
}

// ── page helpers ─────────────────────────────────────────────
function adjustPage(d) {
    const inp = document.getElementById('comment-page');
    if (inp) inp.value = Math.max(1, (parseInt(inp.value)||0) + d) || '';
}
function updatePageFilter() {
    const bar = document.getElementById('cmt-filter-bar');
    if (!bar) return;
    document.getElementById('cmt-filter-label').textContent = `${STR.current_page} ${_pdfPage}`;
    document.getElementById('cmt-filter-btn').textContent   = _showAllPages ? STR.current_page_only : STR.view_all_pages;
    renderCommentList();
}
function togglePageFilter() { _showAllPages = !_showAllPages; updatePageFilter(); }

// ── comments ─────────────────────────────────────────────────
function loadComments() {
    fetch(COMMENTS_URL, { headers:{ 'Accept':'application/json' } })
        .then(r => r.json()).then(d => { _comments = (d.comments||[]).reverse(); renderCommentList(); });
}
function renderCommentList() {
    const list  = document.getElementById('comment-list');
    const empty = document.getElementById('comment-empty');
    const count = document.getElementById('comment-count');
    let visible = _comments;
    if (PREVIEW_TYPE === 'pdf' && !_showAllPages)
        visible = _comments.filter(c => c.page == null || c.page == _pdfPage);
    count.textContent = _comments.length || '';
    list.querySelectorAll('.comment-card').forEach(c => c.remove());
    if (PREVIEW_TYPE === 'video') renderVidMarkers();
    if (!visible.length) { if (empty) empty.style.display = 'block'; return; }
    if (empty) empty.style.display = 'none';
    visible.forEach(c => {
        let badge;
        if (PREVIEW_TYPE === 'video') {
            badge = (c.video_time != null)
                ? `<button onclick="vidSeekTo(${c.video_time})" class="page-badge" style="background:#ddd6fe;color:#5b21b6;border:none;cursor:pointer;" title="이 시점으로 이동">▶ ${vidFmtTime(c.video_time)}</button>`
                : '';
        } else {
            const bg = c.page ? '#ede9fe' : '#dcfce7', fg = c.page ? '#6d28d9' : '#166534';
            badge = `<span class="page-badge" style="background:${bg};color:${fg};">${c.page?'p.'+c.page:STR.comment_all_pages}</span>`;
        }
        list.insertAdjacentHTML('beforeend',
            `<div class="comment-card">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="font-size:12px;font-weight:700;color:#374151;">${esc(c.user_name)}</span>
                    ${badge}
                    <span style="font-size:10px;color:#9ca3af;margin-left:auto;">${esc(c.created_at)}</span>
                </div>
                <div style="font-size:13px;color:#374151;line-height:1.6;white-space:pre-wrap;">${esc(c.content)}</div>
            </div>`);
    });
}
function submitComment() {
    const nameEl = document.getElementById('guest-name');
    const pageEl = document.getElementById('comment-page');
    const txtEl  = document.getElementById('comment-input');
    const btn     = document.getElementById('submit-btn');
    const btnName = document.getElementById('submit-btn-name');
    const name   = (nameEl?.value||'').trim(), txt = txtEl.value.trim();
    if (!name) { nameEl?.focus(); alert(STR.enter_name); return; }
    if (!txt)  { txtEl.focus(); return; }
    btn.disabled = true; if (btnName) btnName.disabled = true;
    const body = { guest_name:name, content:txt };
    if (pageEl) body.page = parseInt(pageEl.value)||null;
    if (PREVIEW_TYPE === 'video' && _vidPendingTime != null) body.video_time = _vidPendingTime;
    fetch(COMMENT_POST,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify(body)})
        .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(d=>{ if(!_comments.some(c=>c.id===d.id))_comments.unshift(d); txtEl.value=''; if(pageEl)pageEl.value=''; vidClearTime(); renderCommentList(); })
        .catch(()=>alert(STR.comment_fail))
        .finally(()=>{ btn.disabled=false; if (btnName) btnName.disabled=false; });
}

// ── annotation tool controls ─────────────────────────────────
function setAnnTool(tool) {
    // Toggle: clicking active tool deactivates it
    annTool = (annTool === tool) ? null : tool;
    document.querySelectorAll('.ann-tool-btn').forEach(b => b.classList.remove('active'));
    if (annTool) document.getElementById('ann-btn-'+annTool)?.classList.add('active');
    // Cursor on scroll container
    const sc = document.getElementById(PREVIEW_TYPE === 'image' ? 'img-scroll-wrap' : 'pdf-canvas-wrap');
    if (sc) { sc.classList.toggle('crosshair', !!annTool); sc.classList.remove('grabbing'); }
    // Hint text
    const hint = document.getElementById('ann-mode-hint');
    if (hint) hint.textContent = annTool ? STR.ann_hint_active : STR.ann_hint_idle;
}
function setAnnColor(col) {
    annColor = col;
    document.querySelectorAll('.ann-color-btn').forEach(b => {
        b.style.outline       = b.dataset.color === col ? '2px solid #fff' : 'none';
        b.style.outlineOffset = '2px';
    });
}

// ── annotation size helper ───────────────────────────────────
// Returns the pixel dimensions of the SVG overlay (= image or canvas size)
function annSvgSize() {
    if (PREVIEW_TYPE === 'image') {
        const img = document.getElementById('viewer-img');
        if (!img || !img.offsetWidth) return null;
        return { w: img.offsetWidth, h: img.offsetHeight };
    }
    if (PREVIEW_TYPE === 'pdf') {
        const c = document.getElementById('pdf-canvas');
        if (!c || !c.width) return null;
        return { w: c.width, h: c.height };
    }
    return null;
}

// ── annotation hit-test ──────────────────────────────────────
function findAnnAtPoint(cx, cy, size) {
    const { w, h } = size;
    const px = v => v/100 * w, py = v => v/100 * h;
    const HIT = 10;
    return [...annList].reverse().find(a => {
        const d = a.data || {};
        switch (a.type) {
            case 'number': return Math.hypot(cx - px(d.x??0), cy - py(d.y??0)) <= 16;
            case 'rect': {
                const x1=Math.min(px(d.x1??0),px(d.x2??0))-HIT, x2=Math.max(px(d.x1??0),px(d.x2??0))+HIT;
                const y1=Math.min(py(d.y1??0),py(d.y2??0))-HIT, y2=Math.max(py(d.y1??0),py(d.y2??0))+HIT;
                return cx>=x1 && cx<=x2 && cy>=y1 && cy<=y2;
            }
            case 'circle': {
                const rx=px(d.rx??0), ry=py(d.ry??0);
                if (!rx||!ry) return false;
                return Math.pow((cx-px(d.cx??0))/rx,2)+Math.pow((cy-py(d.cy??0))/ry,2) <= 1.44;
            }
            case 'line': return _distToSeg(cx,cy,px(d.x1??0),py(d.y1??0),px(d.x2??0),py(d.y2??0)) <= HIT+2;
            case 'text':  return Math.abs(cx-px(d.x??0))<80 && Math.abs(cy-py(d.y??0))<24;
            default: return false;
        }
    }) || null;
}
function _distToSeg(px,py,x1,y1,x2,y2) {
    const dx=x2-x1, dy=y2-y1, l2=dx*dx+dy*dy;
    if (!l2) return Math.hypot(px-x1,py-y1);
    const t=Math.max(0,Math.min(1,((px-x1)*dx+(py-y1)*dy)/l2));
    return Math.hypot(px-(x1+t*dx), py-(y1+t*dy));
}

// ── annotation info popup ────────────────────────────────────
let _infoAnn = null;
const ANN_LABELS = { number: STR.ann_label_number, rect: STR.ann_label_rect, circle: STR.ann_label_circle, line: STR.ann_label_line, text: STR.ann_label_text };
function showAnnInfoPopup(ann, cx, cy) {
    _infoAnn = ann;
    const p = document.getElementById('ann-info-popup');
    if (!p) return;
    document.getElementById('ann-info-type').textContent = ANN_LABELS[ann.type] || ann.type;
    document.getElementById('ann-info-name').textContent = ann.user_name || STR.external_reviewer;
    document.getElementById('ann-info-time').textContent = ann.created_at || '';
    const del = document.getElementById('ann-info-del');
    if (del) del.style.display = ann.can_delete ? 'block' : 'none';
    p.style.display = 'block';
    p.style.left = Math.max(4, Math.min(cx+10, window.innerWidth-228)) + 'px';
    p.style.top  = Math.max(4, Math.min(cy-10, window.innerHeight-140)) + 'px';
}
function hideAnnInfoPopup() {
    const p = document.getElementById('ann-info-popup');
    if (p) p.style.display = 'none';
    _infoAnn = null;
}
function deleteAnnFromInfo() {
    if (!_infoAnn?.can_delete) return;
    const ann = _infoAnn;
    hideAnnInfoPopup();
    fetch(ANN_UPDATE_BASE + ann.id, {
        method: 'DELETE',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            annList = annList.filter(a => a.id !== ann.id);
            myAnnIds.delete(ann.id);
            _saveMyAnnIds();
            renderAnnotations();
        }
    });
}
document.addEventListener('click', e => {
    const p = document.getElementById('ann-info-popup');
    if (p && p.style.display !== 'none' && !p.contains(e.target)) hideAnnInfoPopup();
});

// ── annotation data load / render ────────────────────────────
function loadAnnotations() {
    let url = ANN_GET_URL;
    if (PREVIEW_TYPE === 'pdf') url += '?page=' + _pdfPage;
    fetch(url, { headers:{ 'Accept':'application/json' } })
        .then(r=>r.json()).then(d=>{ annList=d.annotations||[]; _markMine(annList); annNextNum=calcNextNum(annList); renderAnnotations(); });
}
function calcNextNum(list) {
    const ns = list.filter(a=>a.type==='number').map(a=>a.data?.n??0);
    return ns.length ? Math.max(...ns)+1 : 1;
}
function renderAnnotations() {
    const svg  = document.getElementById('ann-svg');
    const size = annSvgSize();
    if (!svg || !size) return;
    svg.innerHTML = '';
    annList.forEach(a => svg.appendChild(buildAnnEl(a, size)));
}
function buildAnnEl(a, size) {
    const { w, h } = size;
    const d   = a.data || {};
    const col = d.color || '#ef4444';
    const g   = _svgEl('g');
    g.dataset.id = a.id;
    const px = v => v/100 * w, py = v => v/100 * h;

    switch (a.type) {
        case 'number': {
            const cx=px(d.x??0), cy=py(d.y??0);
            const c=_svgEl('circle'); c.setAttribute('cx',cx); c.setAttribute('cy',cy); c.setAttribute('r',12); c.setAttribute('fill',col); c.setAttribute('opacity','0.85');
            const t=_svgEl('text');   t.setAttribute('x',cx);  t.setAttribute('y',cy);  t.setAttribute('text-anchor','middle'); t.setAttribute('dominant-baseline','central'); t.setAttribute('font-size','11'); t.setAttribute('font-weight','700'); t.setAttribute('fill','#fff'); t.textContent=d.n??'?';
            g.append(c,t); break;
        }
        case 'rect': {
            const x1=px(d.x1??0),y1=py(d.y1??0),x2=px(d.x2??0),y2=py(d.y2??0);
            const r=_svgEl('rect'); r.setAttribute('x',Math.min(x1,x2)); r.setAttribute('y',Math.min(y1,y2)); r.setAttribute('width',Math.abs(x2-x1)); r.setAttribute('height',Math.abs(y2-y1)); r.setAttribute('stroke',col); r.setAttribute('stroke-width','2'); r.setAttribute('fill',col); r.setAttribute('fill-opacity','0.12'); r.setAttribute('rx','2');
            g.append(r); break;
        }
        case 'circle': {
            const el=_svgEl('ellipse'); el.setAttribute('cx',px(d.cx??0)); el.setAttribute('cy',py(d.cy??0)); el.setAttribute('rx',px(d.rx??0)); el.setAttribute('ry',py(d.ry??0)); el.setAttribute('stroke',col); el.setAttribute('stroke-width','2'); el.setAttribute('fill',col); el.setAttribute('fill-opacity','0.12');
            g.append(el); break;
        }
        case 'line': {
            const defs=_svgEl('defs'),mk=_svgEl('marker'),id='arr'+a.id;
            mk.setAttribute('id',id); mk.setAttribute('markerWidth','8'); mk.setAttribute('markerHeight','8'); mk.setAttribute('refX','6'); mk.setAttribute('refY','3'); mk.setAttribute('orient','auto');
            const poly=_svgEl('polygon'); poly.setAttribute('points','0 0, 8 3, 0 6'); poly.setAttribute('fill',col);
            mk.append(poly); defs.append(mk);
            const ln=_svgEl('line'); ln.setAttribute('x1',px(d.x1??0)); ln.setAttribute('y1',py(d.y1??0)); ln.setAttribute('x2',px(d.x2??0)); ln.setAttribute('y2',py(d.y2??0)); ln.setAttribute('stroke',col); ln.setAttribute('stroke-width','2'); ln.setAttribute('marker-end','url(#'+id+')');
            g.append(defs,ln); break;
        }
        case 'text': {
            const t=_svgEl('text'); t.setAttribute('x',px(d.x??0)); t.setAttribute('y',py(d.y??0)); t.setAttribute('font-size','13'); t.setAttribute('fill',col); t.setAttribute('font-weight','600');
            (d.text||'').split('\n').forEach((line,i)=>{ const ts=_svgEl('tspan'); ts.setAttribute('x',px(d.x??0)); ts.setAttribute('dy',i?'1.4em':'0'); ts.textContent=line; t.append(ts); });
            g.append(t); break;
        }
    }
    return g;
}
function _svgEl(tag) { return document.createElementNS('http://www.w3.org/2000/svg', tag); }

// ── annotation preview (during drag) ─────────────────────────
function updateAnnPreview(ex, ey) {
    if (!annDragEl) return;
    annDragEl.innerHTML = '';
    const x1=annStartX, y1=annStartY, col=annColor;
    switch (annTool) {
        case 'rect': {
            const r=_svgEl('rect'); r.setAttribute('x',Math.min(x1,ex)); r.setAttribute('y',Math.min(y1,ey)); r.setAttribute('width',Math.abs(ex-x1)); r.setAttribute('height',Math.abs(ey-y1)); r.setAttribute('stroke',col); r.setAttribute('stroke-width','2'); r.setAttribute('fill',col); r.setAttribute('fill-opacity','0.12'); r.setAttribute('rx','2');
            annDragEl.append(r); break;
        }
        case 'circle': {
            const el=_svgEl('ellipse'); el.setAttribute('cx',(x1+ex)/2); el.setAttribute('cy',(y1+ey)/2); el.setAttribute('rx',Math.abs(ex-x1)/2); el.setAttribute('ry',Math.abs(ey-y1)/2); el.setAttribute('stroke',col); el.setAttribute('stroke-width','2'); el.setAttribute('fill',col); el.setAttribute('fill-opacity','0.12');
            annDragEl.append(el); break;
        }
        case 'line': {
            const ln=_svgEl('line'); ln.setAttribute('x1',x1); ln.setAttribute('y1',y1); ln.setAttribute('x2',ex); ln.setAttribute('y2',ey); ln.setAttribute('stroke',col); ln.setAttribute('stroke-width','2'); ln.setAttribute('stroke-linecap','round');
            annDragEl.append(ln); break;
        }
        case 'number': {
            const c=_svgEl('circle'); c.setAttribute('cx',ex); c.setAttribute('cy',ey); c.setAttribute('r',12); c.setAttribute('fill',col); c.setAttribute('opacity','0.85');
            const t=_svgEl('text');   t.setAttribute('x',ex); t.setAttribute('y',ey); t.setAttribute('text-anchor','middle'); t.setAttribute('dominant-baseline','central'); t.setAttribute('font-size','11'); t.setAttribute('font-weight','700'); t.setAttribute('fill','#fff'); t.textContent=annNextNum;
            annDragEl.append(c,t); break;
        }
    }
}

// ── commit annotation ─────────────────────────────────────────
function commitAnnotation(sx, sy, ex, ey) {
    if (!annTool) return;
    const size = annSvgSize();
    if (!size) return;
    const { w, h } = size;
    const guestName = (document.getElementById('guest-name')?.value||'').trim();
    if (!guestName) { alert(STR.enter_name_first); setAnnTool(null); return; }
    const data = { color: annColor };
    const page = PREVIEW_TYPE === 'pdf' ? _pdfPage : null;
    const tool = annTool; // capture before setAnnTool clears it
    switch (tool) {
        case 'number': data.n=annNextNum; data.x=ex/w*100; data.y=ey/h*100; break;
        case 'rect':   data.x1=sx/w*100; data.y1=sy/h*100; data.x2=ex/w*100; data.y2=ey/h*100; break;
        case 'circle': data.cx=(sx+ex)/2/w*100; data.cy=(sy+ey)/2/h*100; data.rx=Math.abs(ex-sx)/2/w*100; data.ry=Math.abs(ey-sy)/2/h*100; break;
        case 'line':   data.x1=sx/w*100; data.y1=sy/h*100; data.x2=ex/w*100; data.y2=ey/h*100; break;
        default: return;
    }
    // ← Auto-deactivate tool after drawing (one-shot)
    setAnnTool(null);
    fetch(ANN_POST_URL,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({guest_name:guestName,type:tool,data,page})})
        .then(r=>{ if(!r.ok) throw new Error('HTTP '+r.status); return r.json(); })
        .then(res=>{ if(res.ok){ if(tool==='number')annNextNum++; res.annotation.can_delete=true; myAnnIds.add(res.annotation.id); _saveMyAnnIds(); annList.push(res.annotation); renderAnnotations(); } })
        .catch(()=>alert(STR.ann_save_fail));
}

// ── annotation move helpers ──────────────────────────────────
function applyAnnDelta(startData, dxPct, dyPct, type) {
    const d = Object.assign({}, startData);
    switch (type) {
        case 'number': case 'text':
            d.x = (d.x||0) + dxPct; d.y = (d.y||0) + dyPct; break;
        case 'rect': case 'line':
            d.x1=(d.x1||0)+dxPct; d.y1=(d.y1||0)+dyPct;
            d.x2=(d.x2||0)+dxPct; d.y2=(d.y2||0)+dyPct; break;
        case 'circle':
            d.cx=(d.cx||0)+dxPct; d.cy=(d.cy||0)+dyPct; break;
    }
    return d;
}
function patchAnnotation(annId, data) {
    fetch(ANN_UPDATE_BASE + annId, {
        method: 'PATCH',
        headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body: JSON.stringify({ data })
    });
}

// ── text annotation popup ────────────────────────────────────
function showAnnTextPopup(cx, cy) {
    const p = document.getElementById('ann-text-popup');
    if (!p) return;
    p.style.display = 'block';
    p.style.left = Math.min(cx, window.innerWidth-380)+'px';
    p.style.top  = Math.min(cy, window.innerHeight-200)+'px';
    document.getElementById('ann-text-input').value = '';
    document.getElementById('ann-text-input').focus();
}
function cancelAnnText() {
    const p = document.getElementById('ann-text-popup');
    if (p) p.style.display = 'none';
    _annTextPct = null;
    setAnnTool(null);  // also deactivate on cancel
}
function confirmAnnText() {
    const text = document.getElementById('ann-text-input').value.trim();
    const p    = document.getElementById('ann-text-popup');
    if (p) p.style.display = 'none';
    if (!text || !_annTextPct) { setAnnTool(null); return; }
    const guestName = (document.getElementById('guest-name')?.value||'').trim();
    if (!guestName) { alert(STR.enter_name_first); _annTextPct=null; setAnnTool(null); return; }
    const page = PREVIEW_TYPE === 'pdf' ? _pdfPage : null;
    const data = { color:annColor, x:_annTextPct.x, y:_annTextPct.y, text };
    _annTextPct = null;
    setAnnTool(null); // deactivate
    fetch(ANN_POST_URL,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},body:JSON.stringify({guest_name:guestName,type:'text',data,page})})
        .then(r=>r.json()).then(res=>{ if(res.ok){ res.annotation.can_delete=true; myAnnIds.add(res.annotation.id); _saveMyAnnIds(); annList.push(res.annotation); renderAnnotations(); } });
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cancelAnnText(); hideAnnInfoPopup(); }
    if ((e.ctrlKey||e.metaKey) && e.key==='Enter') {
        const p = document.getElementById('ann-text-popup');
        if (p && p.style.display!=='none' && document.activeElement===document.getElementById('ann-text-input'))
            confirmAnnText();
    }
});

// ── 실시간 의견 동기화 (Pusher) ───────────────────────────────
function _initPusher() {
    if (typeof Pusher === 'undefined' || !PUSHER_KEY) return;
    const pusher  = new Pusher(PUSHER_KEY, { cluster: PUSHER_CLUSTER });
    const channel = pusher.subscribe('file.' + FILE_ID);
    channel.bind('FileCommentPosted', data => {
        if (_comments.some(c => c.id === data.id)) return;
        _comments.unshift(data);
        renderCommentList();
    });
    channel.bind('FileCommentDeleted', data => {
        _comments = _comments.filter(c => c.id !== data.id);
        renderCommentList();
    });
}

// ── utility ──────────────────────────────────────────────────
function esc(s) {
    return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── 리뷰 포함 PDF 다운로드 ──────────────────────────
function _drawAnnsOnCanvas(ctx, anns, canvasW, canvasH) {
    // data는 0-100 퍼센트 값으로 저장됨
    const px = v => v / 100 * canvasW;
    const py = v => v / 100 * canvasH;
    anns.forEach(a => {
        const d   = a.data || {};
        const col = d.color || '#ef4444';
        ctx.strokeStyle = col;
        ctx.fillStyle   = col;
        ctx.lineWidth   = Math.max(2, canvasW * 0.0025);
        if (a.type === 'rect') {
            const x1 = px(d.x1 ?? 0), y1 = py(d.y1 ?? 0);
            const x2 = px(d.x2 ?? 0), y2 = py(d.y2 ?? 0);
            ctx.strokeRect(Math.min(x1,x2), Math.min(y1,y2), Math.abs(x2-x1), Math.abs(y2-y1));
        } else if (a.type === 'circle') {
            // circle: d.cx, d.cy (center %) + d.rx, d.ry (radius %)
            const cx = px(d.cx ?? 50), cy = py(d.cy ?? 50);
            const rx = px(d.rx ?? 10), ry = py(d.ry ?? 10);
            ctx.beginPath();
            ctx.ellipse(cx, cy, Math.max(1, rx), Math.max(1, ry), 0, 0, Math.PI * 2);
            ctx.stroke();
        } else if (a.type === 'line') {
            const x1 = px(d.x1 ?? 0), y1 = py(d.y1 ?? 0);
            const x2 = px(d.x2 ?? 0), y2 = py(d.y2 ?? 0);
            ctx.beginPath(); ctx.moveTo(x1, y1); ctx.lineTo(x2, y2); ctx.stroke();
            const angle = Math.atan2(y2 - y1, x2 - x1);
            const hs = Math.max(10, canvasW * 0.014);
            ctx.beginPath();
            ctx.moveTo(x2, y2);
            ctx.lineTo(x2 - hs*Math.cos(angle-Math.PI/6), y2 - hs*Math.sin(angle-Math.PI/6));
            ctx.lineTo(x2 - hs*Math.cos(angle+Math.PI/6), y2 - hs*Math.sin(angle+Math.PI/6));
            ctx.closePath(); ctx.fill();
        } else if (a.type === 'number') {
            // number: d.x, d.y (center %)
            const cx = px(d.x ?? 50), cy = py(d.y ?? 50);
            const r  = Math.max(12, canvasW * 0.018);
            ctx.beginPath(); ctx.arc(cx, cy, r, 0, Math.PI * 2);
            ctx.fillStyle = col; ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.font = `bold ${Math.round(r * 1.2)}px sans-serif`;
            ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
            ctx.fillText(String(d.n ?? ''), cx, cy);
        } else if (a.type === 'text') {
            const x = px(d.x ?? 0), y = py(d.y ?? 0);
            const fs = Math.max(13, canvasW * 0.018);
            ctx.font = `bold ${fs}px sans-serif`;
            ctx.fillStyle = col;
            const lines = (d.text || '').split('\n');
            lines.forEach((ln, i) => ctx.fillText(ln, x, y + i * (fs + 4)));
        }
    });
}

async function _buildAnnotatedPdf(serveUrl, allAnns, fileName) {
    const { jsPDF } = window.jspdf;
    if (!window.pdfjsLib) { alert(STR.pdf_js_missing); return; }

    const SCALE = 1.5;
    const PDF_WORKER_URL = 'https://unpkg.com/pdfjs-dist@3.11.174/build/pdf.worker.min.js';
    if (!pdfjsLib.GlobalWorkerOptions.workerSrc) {
        try {
            const blob = new Blob([`importScripts('${PDF_WORKER_URL}');`], { type: 'application/javascript' });
            pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(blob);
        } catch (e) {
            pdfjsLib.GlobalWorkerOptions.workerSrc = PDF_WORKER_URL;
        }
    }

    const loadingTask = pdfjsLib.getDocument({ url: serveUrl, withCredentials: false });
    const pdf = await loadingTask.promise;
    const total = pdf.numPages;

    let doc = null;
    for (let p = 1; p <= total; p++) {
        const page = await pdf.getPage(p);
        const vp   = page.getViewport({ scale: SCALE });
        const pw   = Math.round(vp.width);
        const ph   = Math.round(vp.height);

        const orient = pw >= ph ? 'l' : 'p';
        if (!doc) {
            doc = new jsPDF({ unit: 'px', format: [pw, ph], compress: true, orientation: orient });
        } else {
            doc.addPage([pw, ph], orient);
        }

        const canvas = document.createElement('canvas');
        canvas.width  = pw;
        canvas.height = ph;
        const ctx = canvas.getContext('2d');
        await page.render({ canvasContext: ctx, viewport: vp }).promise;

        const pageAnns = allAnns.filter(a => (a.page ?? 1) === p);
        _drawAnnsOnCanvas(ctx, pageAnns, pw, ph);

        const imgData = canvas.toDataURL('image/jpeg', 0.92);
        doc.addImage(imgData, 'JPEG', 0, 0, pw, ph);
    }

    const base = fileName.replace(/\.[^.]+$/, '');
    doc.save(base + STR.review_suffix + '.pdf');
}

function _trimContentBounds(canvas) {
    const w = canvas.width, h = canvas.height;
    const d = canvas.getContext('2d').getImageData(0, 0, w, h).data;
    const blank = (x, y) => { const i = (y * w + x) * 4; return d[i+3] < 8 || (d[i] > 242 && d[i+1] > 242 && d[i+2] > 242); };
    let bottom = h;
    for (let y = h - 1; y > 0; y--) {
        let hit = false;
        for (let x = 0; x < w; x += 3) { if (!blank(x, y)) { hit = true; break; } }
        if (hit) { bottom = y + 1; break; }
    }
    let right = w;
    for (let x = w - 1; x > 0; x--) {
        let hit = false;
        for (let y = 0; y < bottom; y += 3) { if (!blank(x, y)) { hit = true; break; } }
        if (hit) { right = x + 1; break; }
    }
    return { w: Math.min(right + 20, w), h: Math.min(bottom + 20, h) };
}

async function _buildAnnotatedImagePdf(serveUrl, anns, fileName) {
    const { jsPDF } = window.jspdf;

    const img = await new Promise((res, rej) => {
        const i = new Image(); i.crossOrigin = 'anonymous';
        i.onload = () => res(i); i.onerror = rej;
        i.src = serveUrl;
    });

    const canvas = document.createElement('canvas');
    canvas.width  = img.naturalWidth;
    canvas.height = img.naturalHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(img, 0, 0);
    _drawAnnsOnCanvas(ctx, anns, canvas.width, canvas.height);

    const b = _trimContentBounds(canvas);
    const trimmed = document.createElement('canvas');
    trimmed.width = b.w; trimmed.height = b.h;
    trimmed.getContext('2d').drawImage(canvas, 0, 0, b.w, b.h, 0, 0, b.w, b.h);

    const imgData = trimmed.toDataURL('image/jpeg', 0.92);
    const doc = new jsPDF({ unit: 'px', format: [b.w, b.h], compress: true, orientation: b.w >= b.h ? 'l' : 'p' });
    doc.addImage(imgData, 'JPEG', 0, 0, b.w, b.h);

    const base = fileName.replace(/\.[^.]+$/, '');
    doc.save(base + STR.review_suffix + '.pdf');
}

function _swDlpShowBtn(el) {
    if (!el) return;
    const r = el.getBoundingClientRect();
    const d = document.createElement('div');
    d.className = 'sw-dlp'; d.id = 'sw-dlp-ann';
    d.style.top = (r.bottom + 3) + 'px'; d.style.left = r.left + 'px'; d.style.width = Math.max(r.width, 72) + 'px';
    d.innerHTML = '<div class="sw-dlp-track"><div class="sw-dlp-fill sw-dlp-indet"></div></div><span class="sw-dlp-pct">···</span>';
    document.body.appendChild(d);
}
function _swDlpDoneBtn() {
    const d = document.getElementById('sw-dlp-ann'); if (!d) return;
    const f = d.querySelector('.sw-dlp-fill'), p = d.querySelector('.sw-dlp-pct');
    if (f) { f.classList.remove('sw-dlp-indet'); f.style.width = '100%'; }
    if (p) p.textContent = '✓';
    setTimeout(function() { d.remove(); }, 1200);
}

async function downloadAnnotatedPdf() {
    if (!window.jspdf) { alert(STR.jspdf_missing); return; }

    const btn = document.getElementById('ann-dl-btn');
    const origText = btn ? btn.innerHTML : '';
    if (btn) { btn.disabled = true; btn.innerHTML = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v4m0 12v4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83M2 12h4m12 0h4M4.93 19.07l2.83-2.83m8.48-8.48l2.83-2.83"/></svg> ' + STR.pdf_gen_label; }
    _swDlpShowBtn(btn);

    try {
        const fileName = '{{ $file->original_name }}';
        if (PREVIEW_TYPE === 'pdf') {
            // fetch all annotations (no page filter) for multi-page PDF
            const resp = await fetch(ANN_GET_URL, { headers: { 'Accept': 'application/json' } });
            const data = await resp.json();
            const allAnns = data.annotations || [];
            await _buildAnnotatedPdf(SERVE_URL, allAnns, fileName);
        } else {
            await _buildAnnotatedImagePdf(SERVE_URL, annList, fileName);
        }
        _swDlpDoneBtn();
    } catch (e) {
        const d = document.getElementById('sw-dlp-ann'); if (d) d.remove();
        alert(STR.pdf_gen_fail + e.message);
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = origText; }
    }
}
</script>
<script>
(function(){
    function isSameOrigin(url){try{return new URL(url,location.href).origin===location.origin;}catch(e){return false;}}
    function isDownloadAnchor(a){
        if(!a||a.tagName!=='A')return false;
        const href=a.getAttribute('href')||'';
        if(!href||/^(javascript:|#|data:|blob:|mailto:)/i.test(href))return false;
        if(!isSameOrigin(href))return false;
        return a.hasAttribute('download')||/\/download(\/|\?|$)/i.test(href);
    }
    function createBar(el){
        if(el._swDlp){el._swDlp.remove();el._swDlp=null;}
        const r=el.getBoundingClientRect();
        const d=document.createElement('div');d.className='sw-dlp';
        d.style.top=(r.bottom+3)+'px';d.style.left=r.left+'px';d.style.width=Math.max(r.width,72)+'px';
        d.innerHTML='<div class="sw-dlp-track"><div class="sw-dlp-fill"></div></div><span class="sw-dlp-pct">0%</span>';
        document.body.appendChild(d);el._swDlp=d;
    }
    function removeBar(el){if(el._swDlp){el._swDlp.remove();el._swDlp=null;}if(el._swDlXhr){try{el._swDlXhr.abort();}catch(e){}el._swDlXhr=null;}}
    function setPct(el,pct){if(!el._swDlp)return;const f=el._swDlp.querySelector('.sw-dlp-fill'),p=el._swDlp.querySelector('.sw-dlp-pct');if(f){f.classList.remove('sw-dlp-indet');f.style.width=pct+'%';}if(p)p.textContent=Math.round(pct)+'%';}
    function setIndet(el){if(!el._swDlp)return;const f=el._swDlp.querySelector('.sw-dlp-fill');if(f)f.classList.add('sw-dlp-indet');const p=el._swDlp.querySelector('.sw-dlp-pct');if(p)p.textContent='···';}
    function doDownload(el,url,fname){
        createBar(el);
        const xhr=new XMLHttpRequest();el._swDlXhr=xhr;
        xhr.open('GET',url,true);xhr.responseType='blob';
        let hasLen=false;
        xhr.onprogress=function(e){if(e.lengthComputable&&e.total>0){hasLen=true;setPct(el,(e.loaded/e.total)*97);}else if(!hasLen)setIndet(el);};
        xhr.onload=function(){
            if(xhr.status>=200&&xhr.status<300){
                setPct(el,100);
                let filename=fname;
                if(!filename){const cd=xhr.getResponseHeader('Content-Disposition')||'';const m=cd.match(/filename\*?=(?:UTF-8''|")?([^"';\r\n]+)/i);if(m&&m[1]){try{filename=decodeURIComponent(m[1].trim().replace(/^"|"$/g,''));}catch(e2){filename=m[1].trim();}}}
                if(!filename)filename=url.split('/').pop().split('?')[0]||'download';
                const blobUrl=URL.createObjectURL(xhr.response);
                const a=document.createElement('a');a.href=blobUrl;a.download=filename;
                document.body.appendChild(a);a.click();document.body.removeChild(a);
                setTimeout(function(){URL.revokeObjectURL(blobUrl);},1500);
                const pctEl=el._swDlp&&el._swDlp.querySelector('.sw-dlp-pct');if(pctEl)pctEl.textContent='✓';
                setTimeout(function(){removeBar(el);},1200);
            }else{removeBar(el);}
        };
        xhr.onerror=xhr.onabort=function(){removeBar(el);};
        xhr.send();
    }
    document.addEventListener('click',function(e){
        const a=e.target.closest('a');
        if(!isDownloadAnchor(a))return;
        const href=a.getAttribute('href');if(!href)return;
        e.preventDefault();
        doDownload(a,href,a.getAttribute('download')||'');
    },false);
})();
</script>

<script>
/* 의견 패널 펼침/접힘 */
function toggleCommentPanel() {
    const panel  = document.getElementById('comment-panel');
    const handle = document.getElementById('comment-panel-handle');
    if (!panel || !handle) return;
    const collapsed = panel.style.display === 'none';
    if (collapsed) {
        panel.style.display = 'flex';
        handle.style.display = 'none';
        try { localStorage.setItem('sw-share-cp-collapsed', '0'); } catch (_) {}
    } else {
        panel.style.display = 'none';
        handle.style.display = 'flex';
        try { localStorage.setItem('sw-share-cp-collapsed', '1'); } catch (_) {}
    }
}
(function applyInitialPanelState() {
    let collapsed = '0';
    try { collapsed = localStorage.getItem('sw-share-cp-collapsed') || '0'; } catch (_) {}
    const panel    = document.getElementById('comment-panel');
    const handle   = document.getElementById('comment-panel-handle');
    const resizer  = document.getElementById('cp-resizer');

    if (collapsed === '1' && panel && handle) {
        panel.style.display = 'none';
        handle.style.display = 'flex';
        if (resizer) resizer.style.display = 'none';
    } else if (panel) {
        // 저장된 폭 복원
        try {
            const w = parseInt(localStorage.getItem('sw-share-cp-width') || '', 10);
            if (w >= 200 && w <= 720) panel.style.width = w + 'px';
        } catch (_) {}
    }
})();

/* ── 의견 영역 가로 리사이저 ─────────────────────────── */
(function setupCpResizer() {
    const resizer = document.getElementById('cp-resizer');
    const panel   = document.getElementById('comment-panel');
    if (!resizer || !panel) return;

    let dragging = false;
    let startX = 0, startWidth = 0;

    resizer.addEventListener('mousedown', function(e) {
        dragging = true;
        startX = e.clientX;
        startWidth = panel.getBoundingClientRect().width;
        resizer.classList.add('dragging');
        document.body.classList.add('cp-resizing');
        e.preventDefault();
    });

    window.addEventListener('mousemove', function(e) {
        if (!dragging) return;
        // 오른쪽으로 드래그 → 패널 좁아짐, 왼쪽 → 넓어짐
        const delta  = e.clientX - startX;
        let newWidth = startWidth - delta;
        if (newWidth < 200) newWidth = 200;
        if (newWidth > 720) newWidth = 720;
        panel.style.width = newWidth + 'px';
    });

    window.addEventListener('mouseup', function() {
        if (!dragging) return;
        dragging = false;
        resizer.classList.remove('dragging');
        document.body.classList.remove('cp-resizing');
        try {
            localStorage.setItem('sw-share-cp-width', String(parseInt(panel.style.width, 10) || 260));
        } catch (_) {}
    });
})();

/* 패널 토글에 리사이저 표시도 함께 갱신 */
const _origToggleCommentPanel = window.toggleCommentPanel;
window.toggleCommentPanel = function() {
    if (typeof _origToggleCommentPanel === 'function') _origToggleCommentPanel();
    const panel   = document.getElementById('comment-panel');
    const resizer = document.getElementById('cp-resizer');
    if (panel && resizer) {
        resizer.style.display = (panel.style.display === 'none') ? 'none' : '';
    }
};

/* ── 전체창 토글 (브라우저 Fullscreen API) ──────────── */
function toggleFullscreen() {
    const inFs = !!(document.fullscreenElement || document.webkitFullscreenElement);
    if (inFs) {
        const exit = document.exitFullscreen || document.webkitExitFullscreen;
        if (exit) exit.call(document);
    } else {
        const root = document.documentElement;
        const req  = root.requestFullscreen || root.webkitRequestFullscreen;
        if (req) req.call(root);
    }
}
window.toggleFullscreen = toggleFullscreen;

/* fullscreenchange → 아이콘/라벨 토글 */
function _updateFsBtnState() {
    const inFs    = !!(document.fullscreenElement || document.webkitFullscreenElement);
    const iconOn  = document.getElementById('fs-icon-on');
    const iconOff = document.getElementById('fs-icon-off');
    const label   = document.getElementById('fs-toggle-label');
    const btn     = document.getElementById('fs-toggle-btn');
    if (iconOn)  iconOn.style.display  = inFs ? 'inline' : 'none';
    if (iconOff) iconOff.style.display = inFs ? 'none'   : 'inline';
    if (label)   label.textContent     = inFs ? STR.fullscreen_exit : STR.fullscreen;
    if (btn)     btn.title             = inFs ? STR.fullscreen_exit_title : STR.fullscreen_title_f11;
}
document.addEventListener('fullscreenchange',       _updateFsBtnState);
document.addEventListener('webkitfullscreenchange', _updateFsBtnState);

/* F11 단축키 → 우리 핸들러로 위임 (브라우저 기본 fullscreen 도 가능하나 일관성 위해) */
document.addEventListener('keydown', function(e) {
    if (e.key === 'F11') {
        e.preventDefault();
        toggleFullscreen();
    }
});

/* 공유 링크 복사 */
function copyShareLink() {
    const url = location.href;
    const label = document.getElementById('share-copy-label');
    const done = () => {
        if (!label) return;
        const orig = STR.copy_share_link;
        label.textContent = STR.copy_share_link_done;
        setTimeout(() => { label.textContent = orig; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done).catch(() => fallback());
    } else {
        fallback();
    }
    function fallback() {
        const ta = document.createElement('textarea');
        ta.value = url; ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch (_) { prompt(STR.copy_share_link_prompt, url); }
        document.body.removeChild(ta);
    }
}
</script>

@if(!$isEmbed && count($versions) >= 2)
{{-- ════════ 버전 비교 모달 ════════ --}}
<div id="compare-modal">
    <div class="cmp-topbar">
        <span style="font-size:13px;font-weight:700;color:#e5e7eb;display:inline-flex;align-items:center;gap:8px;flex-shrink:0;">
            <svg width="15" height="15" fill="none" stroke="#7dd3fc" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="5" width="7" height="14" rx="1"/><rect x="14" y="5" width="7" height="14" rx="1"/></svg>
            {{ __('files.compare_versions') }}
        </span>
        <span id="cmp-title" style="flex:1;overflow:hidden;font-size:12px;color:#9ca3af;white-space:nowrap;text-overflow:ellipsis;">{{ $file->original_name }}</span>
        <label id="cmp-sync-wrap" style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:600;color:#c4b5fd;cursor:pointer;user-select:none;flex-shrink:0;opacity:.5;">
            <input type="checkbox" id="cmp-sync-pages" onchange="onCmpSyncToggle()" disabled style="width:15px;height:15px;accent-color:#7c3aed;cursor:pointer;">
            <span id="cmp-sync-label">{{ __('files.cmp_sync_pages') }}</span>
        </label>
        <span id="cmp-sync-note" style="display:none;font-size:11px;color:#6b7280;flex-shrink:0;"></span>
        <button onclick="closeCompare()" type="button"
                style="display:inline-flex;align-items:center;gap:8px;color:#c4b5fd;font-size:13px;font-weight:600;background:none;border:1px solid rgba(196,181,253,.25);cursor:pointer;padding:6px 12px;border-radius:8px;flex-shrink:0;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ __('files.cmp_close') }}
        </button>
    </div>
    <div class="cmp-body">
        <div class="cmp-pane cmp-left">
            <div id="cmp-label-a" class="cmp-pane-label"></div>
            <iframe id="cmp-frame-a" class="cmp-frame" title="{{ __('files.cmp_frame_left') }}"></iframe>
        </div>
        <div class="cmp-pane">
            <div id="cmp-label-b" class="cmp-pane-label"></div>
            <iframe id="cmp-frame-b" class="cmp-frame" title="{{ __('files.cmp_frame_right') }}"></iframe>
        </div>
    </div>
</div>
@endif

<script>
/* ══════════ 버전 비교 (Version Comparison) ══════════ */
const SHARE_BASE_URL = @json(route('files.public-share', $token));
const CMP_VERSIONS   = @json($versions);
const CMP_IS_EMBED   = {{ $isEmbed ? 'true' : 'false' }};
const CMP_ACTIVE_VER = {{ $activeVersion !== null ? (int) $activeVersion : 'null' }};

function _cmpFresh(){ return { readyA:false, readyB:false, ptypeA:null, ptypeB:null, pageA:1, pageB:1, vidA:null, vidB:null }; }
let _cmpState       = _cmpFresh();
let _cmpSyncMode    = null;   // 'page' | 'video' | null
let _cmpVidApplying = false;

function _cmpEsc(s){ return String(s==null?'':s).replace(/[&<>"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

/* ── 임베드 → 부모 메시지 ── */
function _cmpPostToParent(msg){
    if (!CMP_IS_EMBED || window.parent === window) return;
    try { window.parent.postMessage(msg, location.origin); } catch(_){}
}
function _cmpPostPage(num){ _cmpPostToParent({ type:'sw-cmp-page', page:num, total:pdfTotal }); }
function _cmpPostReady(){ _cmpPostToParent({ type:'sw-cmp-ready', ptype:PREVIEW_TYPE, page:1 }); }
function _cmpGotoPage(n){
    if (!pdfDoc) return;
    n = Math.max(1, Math.min(pdfTotal||1, parseInt(n,10)||1));
    if (n !== pdfPage) renderPdfPage(n);
}

/* ══════════ 동영상 뷰어 (커스텀 컨트롤 + 시점 의견) ══════════ */
/* 프로젝트 파일 뷰어와 동일한 컨트롤 바·타임라인·마커·토스트 */
let _vidPendingTime     = null;   // "이 시점 의견"으로 캡처된 재생 시점
let _vidLastToastId     = null;
let _vidScrubbing       = false;
let _vidOrigPlaceholder = '';

function initVideo(){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    const loading = document.getElementById('viewer-loading');
    if (loading) loading.style.display = 'none';

    const ta = document.getElementById('comment-input');
    if (ta) _vidOrigPlaceholder = ta.placeholder;

    vid.addEventListener('loadedmetadata', () => { vidUpdateTime(); renderVidMarkers(); });
    vid.addEventListener('timeupdate',     () => { vidUpdateTime(); vidShowCommentToast(); });
    vid.addEventListener('play',  () => { const i=document.getElementById('vid-play-icon'); if(i) i.innerHTML='<path d="M6 4h4v16H6V4zm8 0h4v16h-4V4z"/>'; });
    vid.addEventListener('pause', () => { const i=document.getElementById('vid-play-icon'); if(i) i.innerHTML='<path d="M8 5v14l11-7z"/>'; });
    vid.addEventListener('error', () => {
        const wrapInner = document.getElementById('vid-wrap');
        if (wrapInner && !document.getElementById('vid-err-msg')) {
            const e = document.createElement('div');
            e.id = 'vid-err-msg';
            e.style.cssText = 'position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(20,17,35,.92);color:#f9a8a8;font-size:13px;gap:8px;padding:24px;text-align:center;';
            e.innerHTML = '<div style="font-size:32px;">🎬</div><div style="font-weight:700;">동영상을 재생할 수 없습니다</div><div style="color:#fca5a5;font-size:12px;">형식이 지원되지 않거나 파일을 찾을 수 없습니다.</div>';
            wrapInner.appendChild(e);
        }
    });

    vidSetupTrack();
    if (vid.readyState >= 1) { vidUpdateTime(); renderVidMarkers(); }

    // 버전 비교 임베드 — 재생/탐색 동기화
    if (CMP_IS_EMBED){
        const post = (action) => {
            if (_cmpVidApplying) return;
            _cmpPostToParent({ type:'sw-cmp-vid', action:action, time:vid.currentTime });
        };
        vid.addEventListener('play',   () => post('play'));
        vid.addEventListener('pause',  () => post('pause'));
        vid.addEventListener('seeked', () => post('seek'));
    }
}

function vidFmtTime(t){
    if (!isFinite(t) || t < 0) t = 0;
    const m = Math.floor(t/60), s = Math.floor(t%60);
    return m + ':' + String(s).padStart(2,'0');
}

function vidSetupTrack(){
    const wrap  = document.getElementById('vid-track-wrap');
    const thumb = document.getElementById('vid-thumb');
    const hover = document.getElementById('vid-hover-time');
    const track = document.getElementById('vid-track');
    const prog  = document.getElementById('vid-progress');
    if (!wrap || wrap._vidBound) return;
    wrap._vidBound = true;

    const seekFromEvent = (e) => {
        const vid = document.getElementById('vid-el');
        if (!vid) return;
        const dur = vid.duration;
        if (!isFinite(dur) || dur <= 0) return;
        const r = wrap.getBoundingClientRect();
        const x = (e.touches ? e.touches[0].clientX : e.clientX) - r.left;
        vid.currentTime = Math.max(0, Math.min(1, x / r.width)) * dur;
    };
    const updateHover = (e) => {
        const vid = document.getElementById('vid-el');
        const dur = vid?.duration;
        if (!isFinite(dur) || dur <= 0) return;
        const r = wrap.getBoundingClientRect();
        const pct = Math.max(0, Math.min(1, ((e.touches?e.touches[0].clientX:e.clientX) - r.left) / r.width));
        hover.style.left = (pct*100) + '%';
        hover.textContent = vidFmtTime(pct*dur);
        hover.style.display = 'block';
    };
    wrap.addEventListener('click', seekFromEvent);
    wrap.addEventListener('mousedown', (e) => {
        if (e.button !== 0) return;
        _vidScrubbing = true;
        track.style.height='7px'; track.style.top='6px';
        prog.style.height='7px';  prog.style.top='6px';
        seekFromEvent(e);
    });
    window.addEventListener('mousemove', (e) => { if (_vidScrubbing){ seekFromEvent(e); updateHover(e); } });
    window.addEventListener('mouseup', () => {
        if (!_vidScrubbing) return;
        _vidScrubbing = false;
        track.style.height='5px'; track.style.top='7px';
        prog.style.height='5px';  prog.style.top='7px';
        hover.style.display='none';
    });
    wrap.addEventListener('mousemove', updateHover);
    wrap.addEventListener('mouseleave', () => { if (!_vidScrubbing){ hover.style.display='none'; thumb.style.display='none'; } });
    wrap.addEventListener('mouseenter', () => {
        const vid = document.getElementById('vid-el');
        if (vid && isFinite(vid.duration) && vid.duration>0) thumb.style.display='block';
    });
    wrap.addEventListener('touchstart', (e)=>{ seekFromEvent(e); updateHover(e); }, {passive:true});
    wrap.addEventListener('touchmove',  (e)=>{ seekFromEvent(e); updateHover(e); }, {passive:true});
    wrap.addEventListener('touchend',   ()=>{ hover.style.display='none'; });
}

function vidTogglePlay(){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    if (vid.paused){ const p = vid.play(); if (p && p.catch) p.catch(()=>{}); }
    else vid.pause();
}
function vidSeekRelative(delta){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    const dur = vid.duration;
    if (!isFinite(dur) || dur <= 0) return;
    vid.currentTime = Math.max(0, Math.min(dur, (vid.currentTime||0) + delta));
}
function vidSeekTo(time){
    const vid = document.getElementById('vid-el');
    if (!vid || !vid.duration) return;
    vid.currentTime = Math.max(0, Math.min(vid.duration, time));
    if (vid.paused) vid.play().catch(()=>{});
}
function vidUpdateTime(){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    const dur = vid.duration || 0, cur = vid.currentTime || 0;
    const lbl = document.getElementById('vid-time-label');
    if (lbl) lbl.textContent = vidFmtTime(cur) + ' / ' + vidFmtTime(dur);
    const prog = document.getElementById('vid-progress');
    if (prog) prog.style.width = (dur ? (cur/dur*100) : 0) + '%';
}
function renderVidMarkers(){
    const vid = document.getElementById('vid-el');
    const markersEl = document.getElementById('vid-markers');
    if (!markersEl || !vid) return;
    const dur = vid.duration || 0;
    markersEl.innerHTML = '';
    if (!dur) return;
    _comments.filter(c => c.video_time != null).forEach(c => {
        const pct = Math.max(0, Math.min(100, (c.video_time / dur) * 100));
        const m = document.createElement('div');
        m.title = vidFmtTime(c.video_time) + ' — ' + (c.user_name || '') + ': ' + (c.content || '');
        m.style.cssText = `position:absolute;left:${pct}%;top:-2px;width:22px;height:22px;margin-left:-11px;cursor:pointer;pointer-events:auto;display:flex;align-items:center;justify-content:center;`;
        m.innerHTML = '<span style="display:block;width:14px;height:14px;background:#a78bfa;border:2px solid #fff;border-radius:50%;box-shadow:0 1px 4px rgba(0,0,0,.4);transition:transform .12s,background .12s;pointer-events:none;"></span>';
        m.addEventListener('mouseenter', () => {
            m.firstElementChild.style.background = '#7c3aed';
            m.firstElementChild.style.transform  = 'scale(1.25)';
            const hover = document.getElementById('vid-hover-time');
            if (hover){ hover.style.left=pct+'%'; hover.textContent=vidFmtTime(c.video_time)+' — '+(c.user_name||''); hover.style.display='block'; }
        });
        m.addEventListener('mouseleave', () => {
            m.firstElementChild.style.background = '#a78bfa';
            m.firstElementChild.style.transform  = 'scale(1)';
        });
        const stop = (e) => e.stopPropagation();
        m.addEventListener('mousedown', stop);
        m.addEventListener('mouseup',   stop);
        m.addEventListener('mousemove', stop);
        m.addEventListener('touchstart', stop, {passive:true});
        m.addEventListener('click', (e) => { e.stopPropagation(); e.preventDefault(); vidSeekTo(c.video_time); });
        markersEl.appendChild(m);
    });
}
function vidShowCommentToast(){
    const vid   = document.getElementById('vid-el');
    const toast = document.getElementById('vid-comment-toast');
    if (!vid || !toast) return;
    const cur = vid.currentTime || 0;
    const hit = _comments.find(c => c.video_time != null
        && Math.abs(c.video_time - cur) <= 0.6 && c.id !== _vidLastToastId);
    if (!hit) return;
    _vidLastToastId = hit.id;
    toast.innerHTML = `<div style="display:flex;gap:8px;align-items:flex-start;">
        <span style="flex-shrink:0;font-size:11px;font-weight:700;color:#c4b5fd;background:rgba(196,181,253,.15);padding:2px 8px;border-radius:10px;">${vidFmtTime(hit.video_time)}</span>
        <div style="flex:1;min-width:0;">
            <div style="font-size:11px;font-weight:700;color:#a78bfa;margin-bottom:2px;">${esc(hit.user_name)}</div>
            <div style="color:#e5e7eb;white-space:pre-wrap;word-break:break-word;">${esc(hit.content)}</div>
        </div>
        <button onclick="document.getElementById('vid-comment-toast').style.display='none'" style="flex-shrink:0;background:none;border:none;color:#9ca3af;font-size:16px;line-height:1;cursor:pointer;">×</button>
    </div>`;
    toast.style.display = 'block';
    clearTimeout(toast._t);
    toast._t = setTimeout(() => { toast.style.display = 'none'; _vidLastToastId = null; }, 4000);
}
function vidPauseAndAddComment(){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    vid.pause();
    _vidPendingTime = +(vid.currentTime || 0).toFixed(2);
    const panel = document.getElementById('comment-panel');
    if (panel && panel.style.display === 'none') toggleCommentPanel();
    const ta = document.getElementById('comment-input');
    if (ta){ ta.placeholder = vidFmtTime(_vidPendingTime) + ' 시점에 의견을 작성하세요...'; ta.focus(); }
}
function vidClearTime(){
    _vidPendingTime = null;
    const ta = document.getElementById('comment-input');
    if (ta && _vidOrigPlaceholder) ta.placeholder = _vidOrigPlaceholder;
}
function _cmpApplyVideo(action, time){
    const vid = document.getElementById('vid-el');
    if (!vid) return;
    _cmpVidApplying = true;
    if (typeof time === 'number' && isFinite(time) && Math.abs(vid.currentTime - time) > 0.3){
        vid.currentTime = time;
    }
    if (action === 'play')  vid.play().catch(()=>{});
    if (action === 'pause') vid.pause();
    clearTimeout(_cmpApplyVideo._t);
    _cmpApplyVideo._t = setTimeout(() => { _cmpVidApplying = false; }, 500);
}

/* ── 버전 선택 팝오버 ── */
function openVersionCompareMenu(ev){
    if (ev) ev.stopPropagation();
    closeVersionCompareMenu();
    const btn = document.getElementById('cmp-open-btn');
    if (!btn || !CMP_VERSIONS.length) return;
    const rows = [...CMP_VERSIONS].reverse().map(v => {
        const sub = (v.uploader || '—') + (v.created_at ? ' · ' + v.created_at : '') + (v.is_current ? ' · ' + STR.cmp_current : '');
        const pre = (v.version === CMP_ACTIVE_VER) ? ' checked' : '';
        return `<label class="vcmp-row${pre?' is-checked':''}" data-version="${v.version}">
            <input type="checkbox" value="${v.version}" onchange="onVersionCompareCheck()"${pre}>
            <span class="vcmp-vtag">v${v.version}</span>
            <span class="vcmp-meta"><span class="vcmp-name">${_cmpEsc(v.name)}</span><span class="vcmp-sub">${_cmpEsc(sub)}</span></span>
        </label>`;
    }).join('');
    const pop = document.createElement('div');
    pop.id = 'version-compare-pop';
    pop.style.cssText = 'position:fixed;z-index:10011;background:#241f3d;border:1px solid rgba(196,181,253,.25);border-radius:10px;box-shadow:0 16px 48px rgba(0,0,0,.55);padding:10px;width:288px;';
    pop.innerHTML = `<div style="font-size:12px;font-weight:700;color:#e9d5ff;margin-bottom:6px;">${_cmpEsc(STR.cmp_pick_versions)} <span style="font-weight:500;color:#8b85a8;">${_cmpEsc(STR.cmp_pick_hint)}</span></div>
        <div style="display:flex;flex-direction:column;gap:4px;max-height:280px;overflow-y:auto;">${rows}</div>
        <button id="vcmp-go" type="button" onclick="confirmVersionCompare()" style="margin-top:9px;width:100%;padding:8px;border:none;border-radius:7px;font-size:12px;font-weight:700;"></button>`;
    document.body.appendChild(pop);
    const r = btn.getBoundingClientRect();
    pop.style.top  = (r.bottom + 6) + 'px';
    pop.style.left = Math.max(8, r.right - 288) + 'px';
    onVersionCompareCheck();
    setTimeout(() => document.addEventListener('mousedown', _vcmpOutside), 0);
}
function _vcmpOutside(e){
    const pop = document.getElementById('version-compare-pop');
    if (pop && !pop.contains(e.target) && !e.target.closest('#cmp-open-btn')) closeVersionCompareMenu();
}
function closeVersionCompareMenu(){
    const pop = document.getElementById('version-compare-pop');
    if (pop) pop.remove();
    document.removeEventListener('mousedown', _vcmpOutside);
}
function onVersionCompareCheck(){
    const pop = document.getElementById('version-compare-pop');
    if (!pop) return;
    const boxes = [...pop.querySelectorAll('input[type=checkbox]')];
    const checked = boxes.filter(b => b.checked);
    boxes.forEach(b => { b.disabled = !b.checked && checked.length >= 2; });
    pop.querySelectorAll('.vcmp-row').forEach(row => {
        const cb = row.querySelector('input');
        row.classList.toggle('is-checked', cb.checked);
        row.classList.toggle('is-disabled', cb.disabled);
    });
    const go = document.getElementById('vcmp-go');
    if (checked.length === 0){
        go.disabled = true; go.textContent = STR.cmp_select_prompt;
        go.style.background = '#3a3357'; go.style.color = '#8b85a8'; go.style.cursor = 'not-allowed';
    } else if (checked.length === 1){
        go.disabled = false; go.textContent = @json(__('files.cmp_view_one')).replace(':version', checked[0].value);
        go.style.background = 'linear-gradient(135deg,#7c3aed,#a78bfa)'; go.style.color = '#fff'; go.style.cursor = 'pointer';
    } else {
        const s = checked.map(b=>parseInt(b.value,10)).sort((a,b)=>a-b);
        go.disabled = false; go.textContent = @json(__('files.cmp_compare_two')).replace(':a', s[0]).replace(':b', s[1]);
        go.style.background = 'linear-gradient(135deg,#0ea5e9,#7dd3fc)'; go.style.color = '#08233a'; go.style.cursor = 'pointer';
    }
}
function confirmVersionCompare(){
    const pop = document.getElementById('version-compare-pop');
    if (!pop) return;
    const checked = [...pop.querySelectorAll('input[type=checkbox]:checked')].map(b=>parseInt(b.value,10)).sort((a,b)=>a-b);
    closeVersionCompareMenu();
    if (checked.length === 1){
        // 1개 선택 → 해당 버전 단일 보기
        window.location = SHARE_BASE_URL + '?version=' + checked[0];
    } else if (checked.length === 2){
        openCompare(checked[0], checked[1]);
    }
}

/* ── 비교 모달 ── */
function _cmpLabelHtml(v){
    if (!v) return '';
    const sub = (v.uploader || '—') + (v.created_at ? ' · ' + v.created_at : '') + (v.is_current ? ' · ' + STR.cmp_current_version : '');
    return `<span style="background:linear-gradient(135deg,#7c3aed,#a78bfa);color:#fff;padding:2px 9px;border-radius:5px;font-size:11px;flex-shrink:0;">v${v.version}</span>
        <span style="color:#e5e7eb;overflow:hidden;text-overflow:ellipsis;">${_cmpEsc(v.name)}</span>
        <span style="color:#8b85a8;font-weight:500;flex-shrink:0;">${_cmpEsc(sub)}</span>`;
}
function openCompare(vA, vB){
    const va = CMP_VERSIONS.find(v=>v.version===vA);
    const vb = CMP_VERSIONS.find(v=>v.version===vB);
    _cmpState = _cmpFresh(); _cmpSyncMode = null;
    document.getElementById('cmp-label-a').innerHTML = _cmpLabelHtml(va);
    document.getElementById('cmp-label-b').innerHTML = _cmpLabelHtml(vb);
    const sync = document.getElementById('cmp-sync-pages');
    sync.checked = false; sync.disabled = true;
    document.getElementById('cmp-sync-wrap').style.opacity = '.5';
    document.getElementById('cmp-sync-note').style.display = 'none';
    document.getElementById('cmp-frame-a').src = SHARE_BASE_URL + '?embed=1&version=' + vA;
    document.getElementById('cmp-frame-b').src = SHARE_BASE_URL + '?embed=1&version=' + vB;
    document.getElementById('compare-modal').style.display = 'flex';
}
function closeCompare(){
    const m = document.getElementById('compare-modal');
    if (m) m.style.display = 'none';
    const fa = document.getElementById('cmp-frame-a'), fb = document.getElementById('cmp-frame-b');
    if (fa) fa.src = 'about:blank';
    if (fb) fb.src = 'about:blank';
    _cmpState = _cmpFresh(); _cmpSyncMode = null;
}
function _cmpSendGoto(which, page){
    const fr = document.getElementById(which === 'a' ? 'cmp-frame-a' : 'cmp-frame-b');
    if (fr && fr.contentWindow) fr.contentWindow.postMessage({ type:'sw-cmp-goto', page:page }, location.origin);
}
function _cmpSendVid(which, action, time){
    const fr = document.getElementById(which === 'a' ? 'cmp-frame-a' : 'cmp-frame-b');
    if (fr && fr.contentWindow) fr.contentWindow.postMessage({ type:'sw-cmp-vid-apply', action:action, time:time }, location.origin);
}
function _cmpUpdateSyncAvailability(){
    if (!_cmpState.readyA || !_cmpState.readyB) return;
    const sync  = document.getElementById('cmp-sync-pages');
    const wrap  = document.getElementById('cmp-sync-wrap');
    const note  = document.getElementById('cmp-sync-note');
    const label = document.getElementById('cmp-sync-label');
    const bothPdf   = _cmpState.ptypeA === 'pdf'   && _cmpState.ptypeB === 'pdf';
    const bothVideo = _cmpState.ptypeA === 'video' && _cmpState.ptypeB === 'video';
    _cmpSyncMode = bothPdf ? 'page' : (bothVideo ? 'video' : null);
    if (label) label.textContent = bothVideo ? STR.cmp_sync_video : STR.cmp_sync_pages;
    const enabled = !!_cmpSyncMode;
    sync.disabled = !enabled;
    wrap.style.opacity = enabled ? '1' : '.5';
    if (!enabled){
        sync.checked = false;
        note.textContent = STR.cmp_sync_note;
        note.style.display = 'inline';
    } else {
        note.style.display = 'none';
    }
}
function onCmpSyncToggle(){
    const sync = document.getElementById('cmp-sync-pages');
    if (!sync.checked || sync.disabled) return;
    if (_cmpSyncMode === 'page'){
        _cmpSendGoto('b', _cmpState.pageA);
        _cmpState.pageB = _cmpState.pageA;
    } else if (_cmpSyncMode === 'video' && _cmpState.vidA){
        _cmpSendVid('b', _cmpState.vidA.action, _cmpState.vidA.time);
    }
}

/* ── 부모 ↔ 임베드 메시지 통신 ── */
window.addEventListener('message', function(e){
    if (e.origin !== location.origin) return;
    const d = e.data;
    if (!d || typeof d.type !== 'string') return;
    if (CMP_IS_EMBED){
        if (d.type === 'sw-cmp-goto' && typeof d.page === 'number') _cmpGotoPage(d.page);
        else if (d.type === 'sw-cmp-vid-apply') _cmpApplyVideo(d.action, d.time);
        return;
    }
    const fA = document.getElementById('cmp-frame-a');
    const fB = document.getElementById('cmp-frame-b');
    let which = null;
    if (fA && e.source === fA.contentWindow)      which = 'a';
    else if (fB && e.source === fB.contentWindow) which = 'b';
    if (!which) return;
    if (d.type === 'sw-cmp-ready'){
        if (which === 'a'){ _cmpState.readyA = true; _cmpState.ptypeA = d.ptype; _cmpState.pageA = d.page||1; }
        else              { _cmpState.readyB = true; _cmpState.ptypeB = d.ptype; _cmpState.pageB = d.page||1; }
        _cmpUpdateSyncAvailability();
    } else if (d.type === 'sw-cmp-page' && typeof d.page === 'number'){
        if (which === 'a') _cmpState.pageA = d.page; else _cmpState.pageB = d.page;
        const sync = document.getElementById('cmp-sync-pages');
        if (sync && sync.checked && !sync.disabled && _cmpSyncMode === 'page') _cmpSendGoto(which === 'a' ? 'b' : 'a', d.page);
    } else if (d.type === 'sw-cmp-vid'){
        const st = { action:d.action, time:d.time };
        if (which === 'a') _cmpState.vidA = st; else _cmpState.vidB = st;
        const sync = document.getElementById('cmp-sync-pages');
        if (sync && sync.checked && !sync.disabled && _cmpSyncMode === 'video') _cmpSendVid(which === 'a' ? 'b' : 'a', d.action, d.time);
    }
});

/* ── 임베드 모드 준비 통보 + 동영상 초기화 + ESC 처리 ── */
document.addEventListener('DOMContentLoaded', function(){
    if (PREVIEW_TYPE === 'video') initVideo();
    if (CMP_IS_EMBED) _cmpPostReady();
});
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape'){
        const m = document.getElementById('compare-modal');
        if (m && m.style.display === 'flex') closeCompare();
    }
});
</script>
</body>
</html>
