<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $file->original_name }} — {{ __('team.file_list') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; background: #1e1b2e; }

        .topbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 100;
            height: 52px;
            background: rgba(20,17,35,.97);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(196,181,253,.12);
            display: flex; align-items: center; gap: 12px;
            padding: 0 16px;
        }
        .topbar-back {
            display: inline-flex; align-items: center; gap: 6px;
            color: #c4b5fd; font-size: 13px; font-weight: 600;
            text-decoration: none; padding: 6px 10px;
            border-radius: 8px; transition: background .15s;
        }
        .topbar-back:hover { background: rgba(196,181,253,.1); }

        .topbar-title {
            flex: 1; overflow: hidden;
            font-size: 13px; font-weight: 600; color: #e5e7eb;
            white-space: nowrap; text-overflow: ellipsis;
        }
        .topbar-badge {
            font-size: 11px; font-weight: 700;
            padding: 3px 9px; border-radius: 5px;
            flex-shrink: 0;
        }
        .badge-office  { background: #1e3a5f; color: #60a5fa; }
        .badge-pdf     { background: #3f1515; color: #f87171; }
        .badge-image   { background: #1a3321; color: #4ade80; }

        /* ── 다운로드 프로그래스 바 ── */
        .sw-dlp{position:fixed;z-index:999999;pointer-events:none;min-width:60px;}
        .sw-dlp-track{height:4px;background:rgba(196,181,253,.35);border-radius:2px;overflow:hidden;}
        .sw-dlp-fill{height:100%;width:0%;background:linear-gradient(90deg,#7c3aed,#a78bfa);border-radius:2px;transition:width .25s ease;}
        .sw-dlp-fill.sw-dlp-indet{width:38%;animation:sw-dlp-slide 1.1s infinite ease-in-out;transition:none;}
        @keyframes sw-dlp-slide{0%{transform:translateX(-110%)}100%{transform:translateX(370%)}}
        .sw-dlp-pct{display:block;font-size:10px;color:#a78bfa;font-weight:700;text-align:right;margin-top:2px;line-height:1;white-space:nowrap;}

        .topbar-download {
            display: inline-flex; align-items: center; gap: 5px;
            color: #a5b4fc; font-size: 12px; font-weight: 600;
            text-decoration: none; padding: 5px 10px;
            border: 1px solid rgba(165,180,252,.25); border-radius: 7px;
            transition: background .15s;
            flex-shrink: 0;
        }
        .topbar-download:hover { background: rgba(165,180,252,.1); }

        .viewer-wrap {
            position: fixed; top: 52px; left: 0; right: 0; bottom: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .viewer-wrap iframe {
            width: 100%; height: 100%; border: none;
        }
        .viewer-wrap img {
            max-width: 100%; max-height: 100%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 8px 40px rgba(0,0,0,.5);
            padding: 20px;
        }
        .loading-msg {
            color: #a1a1aa; font-size: 14px; text-align: center;
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%);
        }
        .loading-msg .spinner {
            width: 32px; height: 32px;
            border: 3px solid rgba(196,181,253,.2);
            border-top-color: #9b8afb;
            border-radius: 50%;
            animation: spin .8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="topbar">
    <a href="{{ route('projects.files.index', $project) }}" class="topbar-back">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('team.file_list') }}
    </a>

    <span class="topbar-title">{{ $file->original_name }}</span>

    @php
        $ext = strtolower(pathinfo($file->original_name, PATHINFO_EXTENSION));
        $badgeClass = $previewType === 'office' ? 'badge-office' : ($previewType === 'pdf' ? 'badge-pdf' : 'badge-image');
        $badgeLabel = match($ext) {
            'docx', 'doc' => 'Word',
            'xlsx', 'xls' => 'Excel',
            'pptx', 'ppt' => 'PowerPoint',
            'pdf'         => 'PDF',
            default       => strtoupper($ext),
        };
    @endphp
    <span class="topbar-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>

    <a href="{{ route('projects.files.download', [$project, $file]) }}" class="topbar-download">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        {{ __('team.download_btn') }}
    </a>
</div>

<div class="viewer-wrap" id="viewer">

    @if($previewType === 'image')
        <img src="{{ $serveUrl }}" alt="{{ $file->original_name }}" id="img-viewer">

    @elseif($previewType === 'pdf')
        <div class="loading-msg" id="loading">
            <div class="spinner"></div>
            {{ __('team.pdf_loading') }}
        </div>
        <iframe src="{{ $serveUrl }}" id="pdf-frame"
                onload="document.getElementById('loading').style.display='none'">
        </iframe>

    @elseif($previewType === 'office')
        @php
            $officeViewerUrl = 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode($serveUrl);
        @endphp
        <div class="loading-msg" id="loading">
            <div class="spinner"></div>
            {{ __('team.doc_loading') }}
            <div style="font-size:12px;color:#71717a;margin-top:6px;">{{ __('team.office_viewer_note') }}</div>
        </div>
        <iframe src="{{ $officeViewerUrl }}" id="office-frame"
                onload="document.getElementById('loading').style.display='none'">
        </iframe>
    @endif

</div>

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
        removeBar(el);
        const r=el.getBoundingClientRect();
        const d=document.createElement('div');
        d.className='sw-dlp';
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

</body>
</html>
