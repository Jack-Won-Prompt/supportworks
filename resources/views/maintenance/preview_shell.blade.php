<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>미리보기 — {{ $screen->name }}</title>
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css'])
    <style>
    /* 미리보기 배너 */
    #mn-preview-bar{
        position:sticky;top:0;z-index:9999;
        display:flex;align-items:center;gap:10px;
        background:linear-gradient(90deg,#7c3aed,#6d28d9);
        color:#fff;padding:10px 18px;
        font-size:13px;font-weight:600;
        box-shadow:0 3px 12px rgba(109,40,217,.35);
    }
    #mn-preview-bar .pv-badge{
        background:rgba(255,255,255,.22);
        border-radius:5px;padding:2px 8px;font-size:11px;font-weight:700;
        letter-spacing:.04em;
    }
    #mn-preview-bar .pv-name{
        font-size:13.5px;font-weight:700;
    }
    #mn-preview-bar .pv-note{
        font-size:12px;font-weight:400;opacity:.82;
    }
    #mn-preview-bar .pv-spacer{flex:1;}
    #mn-preview-bar button{
        background:rgba(255,255,255,.18);
        border:1.5px solid rgba(255,255,255,.35);
        color:#fff;border-radius:7px;
        padding:5px 14px;font-size:12.5px;font-weight:600;
        cursor:pointer;transition:background .12s;
        font-family:inherit;
    }
    #mn-preview-bar button:hover{background:rgba(255,255,255,.28);}

    /* 미리보기 워터마크 (배경 표시) */
    body::before{
        content:'PREVIEW';
        position:fixed;top:50%;left:50%;
        transform:translate(-50%,-50%) rotate(-30deg);
        font-size:140px;font-weight:900;
        color:rgba(124,58,237,.04);
        pointer-events:none;z-index:0;
        white-space:nowrap;letter-spacing:.1em;
    }

    /* 동적 데이터 플레이스홀더 스타일링 */
    .mn-preview-content span[style*="opacity:.4"]{
        background:rgba(124,58,237,.08);
        border-radius:3px;padding:0 4px;
        font-size:.85em;font-family:monospace;
        color:#7c3aed;
    }
    </style>
</head>
<body style="margin:0;background:#f8f7ff;min-height:100vh;">

{{-- 미리보기 배너 --}}
<div id="mn-preview-bar">
    <span class="pv-badge">PREVIEW</span>
    <span class="pv-name">{{ $screen->name }}</span>
    <span class="pv-note">— 실제 적용 전 미리보기입니다. 동적 데이터는 표시되지 않을 수 있습니다.</span>
    <div class="pv-spacer"></div>
    <button onclick="window.close()">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" style="vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        {{ __('common.close') }}
    </button>
</div>

{{-- 미리보기 콘텐츠 --}}
<div class="mn-preview-content" style="position:relative;z-index:1;">
    {!! $html !!}
</div>

</body>
</html>
