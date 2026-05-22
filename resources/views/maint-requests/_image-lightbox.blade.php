{{-- SR 이미지 라이트박스 — 전체 창 · 다운로드 · 도형 주석 · 댓글
     호출:  window.openSrImageLightbox(src, alt, srId)
       - srId 있으면 주석·댓글 패널 활성, 없으면(신규 모달) 비활성 (저장 후 사용 안내) --}}
<style>
    /* z-index: SR 상세 모달(10901)·신규 SR 모달(11001) 보다 위. 백드롭 없이 떠있는 독립 팝업처럼 */
    #sr-img-lightbox { display:none; position:fixed; inset:0; z-index:11500; background:transparent; align-items:center; justify-content:center; }
    #sr-img-lightbox.is-open { display:flex; }
    #sr-img-lightbox:fullscreen { padding:0; }
    /* 뷰어 컨테이너 — 화면 중앙의 작은 독립 팝업, 강한 그림자로 떠있는 느낌 */
    #sr-img-lb-container { display:flex; flex-direction:column; width:min(1100px,92vw); height:min(82vh,760px); background:#0b1220; border-radius:14px; overflow:hidden; box-shadow:0 30px 80px rgba(0,0,0,.55), 0 0 0 1px rgba(0,0,0,.25); }
    #sr-img-lightbox:fullscreen #sr-img-lb-container { width:100%; height:100%; border-radius:0; box-shadow:none; }

    #sr-img-lb-toolbar { display:flex; align-items:center; gap:6px; padding:10px 14px; background:#0f172a; color:#fff; flex-shrink:0; flex-wrap:wrap; }
    #sr-img-lb-title { flex:1; font-size:13px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; min-width:140px; }
    .sr-lb-btn { background:rgba(255,255,255,.12); border:none; border-radius:7px; height:28px; padding:0 10px; color:rgba(255,255,255,.88); font-size:12.5px; font-weight:600; cursor:pointer; display:inline-flex; align-items:center; gap:5px; transition:background .12s; flex-shrink:0; }
    .sr-lb-btn:hover { background:rgba(255,255,255,.22); color:#fff; }
    .sr-lb-btn.close-btn:hover { background:#dc2626; }
    .sr-lb-divider { width:1px; height:18px; background:rgba(255,255,255,.12); margin:0 4px; flex-shrink:0; }
    .sr-lb-tool-btn { width:30px; height:28px; display:inline-flex; align-items:center; justify-content:center; background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.12); border-radius:6px; color:rgba(255,255,255,.72); cursor:pointer; transition:all .12s; flex-shrink:0; }
    .sr-lb-tool-btn:hover { background:rgba(255,255,255,.18); color:#fff; }
    .sr-lb-tool-btn.active { background:rgba(196,181,253,.28); border-color:rgba(196,181,253,.45); color:#c4b5fd; }
    .sr-lb-color { width:18px; height:18px; border-radius:50%; border:none; cursor:pointer; padding:0; outline:none; flex-shrink:0; }
    .sr-lb-color.active { outline:2px solid #fff; outline-offset:2px; }
    .sr-lb-tool-label { font-size:10.5px; font-weight:700; color:rgba(255,255,255,.55); letter-spacing:.04em; margin-right:2px; }

    #sr-img-lb-body { flex:1; display:flex; min-height:0; }
    #sr-img-lb-canvas-wrap { flex:1; background:#0b1220; display:flex; align-items:center; justify-content:center; padding:14px; overflow:auto; position:relative; }
    #sr-img-lb-stage { position:relative; display:inline-block; max-width:100%; max-height:100%; }
    #sr-img-lb-img { display:block; max-width:100%; max-height:100%; object-fit:contain; box-shadow:0 14px 40px rgba(0,0,0,.45); }
    #sr-img-lb-ann-svg { position:absolute; left:0; top:0; width:100%; height:100%; pointer-events:none; overflow:visible; }
    #sr-img-lb-ann-svg.drawing { pointer-events:auto; cursor:crosshair; }
    .sr-ann-shape { fill:none; stroke-width:0.45; vector-effect:non-scaling-stroke; }
    .sr-ann-shape line, .sr-ann-shape rect, .sr-ann-shape ellipse, .sr-ann-shape circle { vector-effect:non-scaling-stroke; }
    /* 본인/관리자 도형 — 이동/삭제 가능. 그리기 모드에서는 통과시킴 */
    .sr-ann-shape[data-can-delete="1"] { pointer-events:all; cursor:move; }
    #sr-img-lb-ann-svg.drawing .sr-ann-shape[data-can-delete="1"] { pointer-events:none; cursor:default; }
    .sr-ann-shape[data-can-delete="1"]:hover { stroke-width:0.9; }
    .sr-ann-shape.is-dragging { opacity:.75; }
    .sr-ann-text { font-size:14px; font-weight:600; }
    .sr-ann-number { font-size:13px; font-weight:800; fill:#fff; }
    .sr-ann-number-bg { stroke-width:0.3; }
    /* 삭제 X 배지 — HTML 오버레이(고정 픽셀 크기, 이미지 확대/축소와 무관) */
    #sr-img-lb-ann-html { position:absolute; left:0; top:0; width:100%; height:100%; pointer-events:none; }
    .sr-ann-del-html {
        position:absolute; width:22px; height:22px; margin-left:-11px; margin-top:-11px;
        background:#ef4444; color:#fff; border:1.5px solid #fff; border-radius:50%;
        display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:700; line-height:1;
        box-shadow:0 2px 6px rgba(0,0,0,.35);
        cursor:pointer; pointer-events:auto; opacity:0; transition:opacity .12s, background .12s;
    }
    .sr-ann-del-html.is-visible { opacity:1; }
    .sr-ann-del-html:hover { background:#dc2626; }
    #sr-img-lb-ann-svg.drawing ~ #sr-img-lb-ann-html .sr-ann-del-html { display:none; }
    /* 숫자 도형 — HTML 오버레이로 정원 유지 (SVG viewBox 비균등 스케일과 무관) */
    .sr-ann-num-html {
        position:absolute; width:26px; height:26px; margin-left:-13px; margin-top:-13px;
        color:#fff; border:1.5px solid #fff; border-radius:50%;
        display:flex; align-items:center; justify-content:center;
        font-size:12.5px; font-weight:800; line-height:1;
        box-shadow:0 2px 6px rgba(0,0,0,.35); user-select:none;
        pointer-events:none;
    }
    .sr-ann-num-html.is-editable { pointer-events:auto; cursor:move; }
    .sr-ann-num-html.is-dragging { opacity:.75; }
    #sr-img-lb-ann-svg.drawing ~ #sr-img-lb-ann-html .sr-ann-num-html { pointer-events:none; cursor:default; }

    #sr-img-lb-side { width:300px; background:#fff; display:flex; flex-direction:column; flex-shrink:0; border-left:1px solid #1f2937; }
    #sr-img-lb-side.hidden { display:none; }
    #sr-img-lb-side-head { padding:10px 14px; border-bottom:1px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; flex-shrink:0; }
    #sr-img-lb-side-title { font-size:13px; font-weight:700; color:#0f172a; }
    #sr-img-lb-cmt-count { font-size:11px; color:#9ca3af; font-weight:500; }
    #sr-img-lb-cmt-list { flex:1; overflow-y:auto; padding:12px 14px; display:flex; flex-direction:column; gap:10px; }
    .sr-cmt-item { padding:10px 12px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:8px; }
    .sr-cmt-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; }
    .sr-cmt-author { font-size:11.5px; font-weight:600; color:#0f172a; }
    .sr-cmt-time   { font-size:10.5px; color:#9ca3af; }
    .sr-cmt-body   { font-size:12.5px; color:#374151; line-height:1.55; white-space:pre-wrap; word-break:break-word; }
    .sr-cmt-del    { background:none; border:none; color:#cbd5e1; font-size:11px; cursor:pointer; padding:0 4px; }
    .sr-cmt-del:hover { color:#ef4444; }
    #sr-img-lb-cmt-form { padding:10px 12px; border-top:1px solid #e5e7eb; display:flex; gap:6px; flex-shrink:0; }
    #sr-img-lb-cmt-body { flex:1; border:1px solid #e5e7eb; border-radius:6px; padding:6px 9px; font-size:12.5px; font-family:inherit; resize:none; min-height:32px; }
    #sr-img-lb-cmt-submit { padding:0 12px; background:#0f86ef; color:#fff; border:none; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; }
    #sr-img-lb-cmt-submit:disabled { opacity:.55; cursor:not-allowed; }
    #sr-img-lb-cmt-disabled { padding:18px 14px; font-size:12px; color:#9ca3af; text-align:center; line-height:1.6; }
</style>

{{-- 텍스트 주석 입력 팝업 (라이트박스 위 z-index) --}}
<div id="sr-ann-text-popup" style="display:none;position:fixed;z-index:12000;background:#fff;border:2px solid #a78bfa;border-radius:10px;padding:12px 14px;box-shadow:0 12px 36px rgba(0,0,0,.3);min-width:280px;max-width:360px;">
    <div style="font-size:11px;font-weight:700;color:#6d28d9;margin-bottom:8px;">텍스트 주석</div>
    <textarea id="sr-ann-text-input" rows="4" placeholder="텍스트 입력 (Ctrl+Enter: 확인, Esc: 취소)"
              style="width:100%;border:1.5px solid #e5e7eb;border-radius:6px;padding:7px 10px;font-size:13px;outline:none;box-sizing:border-box;resize:vertical;min-height:80px;line-height:1.5;font-family:inherit;"
              onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
    <div style="display:flex;gap:8px;margin-top:10px;">
        <button type="button" id="sr-ann-text-confirm" style="flex:1;padding:6px 0;background:#7c3aed;color:#fff;border:none;border-radius:6px;font-size:12px;font-weight:700;cursor:pointer;">확인</button>
        <button type="button" id="sr-ann-text-cancel" style="flex:1;padding:6px 0;background:#f3f4f6;color:#374151;border:none;border-radius:6px;font-size:12px;cursor:pointer;">취소</button>
    </div>
</div>

<div id="sr-img-lightbox" onclick="if(event.target===this)window.closeSrImageLightbox()">
    <div id="sr-img-lb-container" onclick="event.stopPropagation()">
        <div id="sr-img-lb-toolbar">
            <span id="sr-img-lb-title">이미지</span>

            <span class="sr-lb-tool-label">주석</span>
            <button type="button" class="sr-lb-tool-btn" data-tool="rect"   title="사각형"><svg width="13" height="13" viewBox="0 0 14 14" fill="none"><rect x="1.5" y="3" width="11" height="8" stroke="currentColor" stroke-width="1.5" rx="1"/></svg></button>
            <button type="button" class="sr-lb-tool-btn" data-tool="circle" title="원"><svg width="13" height="13" viewBox="0 0 14 14" fill="none"><ellipse cx="7" cy="7" rx="5.5" ry="4.5" stroke="currentColor" stroke-width="1.5"/></svg></button>
            <button type="button" class="sr-lb-tool-btn" data-tool="line"   title="화살표"><svg width="13" height="13" viewBox="0 0 14 14" fill="none"><line x1="2" y1="12" x2="11" y2="3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><polygon points="11,3 7.5,4.5 9.5,7" fill="currentColor"/></svg></button>
            <button type="button" class="sr-lb-tool-btn" data-tool="number" title="번호"><svg width="13" height="13" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5"/><text x="7" y="7.6" text-anchor="middle" dominant-baseline="central" font-size="7" font-weight="700" fill="currentColor">1</text></svg></button>
            <button type="button" class="sr-lb-tool-btn" data-tool="text"   title="텍스트" style="font-size:13px;font-weight:700;line-height:1;">T</button>

            <span class="sr-lb-divider"></span>
            <span class="sr-lb-tool-label">색</span>
            <button type="button" class="sr-lb-color" data-color="#ef4444" style="background:#ef4444"></button>
            <button type="button" class="sr-lb-color" data-color="#f97316" style="background:#f97316"></button>
            <button type="button" class="sr-lb-color" data-color="#eab308" style="background:#eab308"></button>
            <button type="button" class="sr-lb-color" data-color="#22c55e" style="background:#22c55e"></button>
            <button type="button" class="sr-lb-color" data-color="#3b82f6" style="background:#3b82f6"></button>
            <button type="button" class="sr-lb-color" data-color="#8b5cf6" style="background:#8b5cf6"></button>

            <span class="sr-lb-divider"></span>
            <button type="button" class="sr-lb-btn" onclick="window.srLbToggleFullscreen()" title="전체 창">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8V4m0 0h4M4 4l5 5m11-5v4m0-4h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                전체 창
            </button>
            <button type="button" class="sr-lb-btn" onclick="window.srLbDownload()" title="다운로드">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                다운로드
            </button>
            <button type="button" class="sr-lb-btn close-btn" onclick="window.closeSrImageLightbox()" title="닫기">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                닫기
            </button>
        </div>

        <div id="sr-img-lb-body">
            <div id="sr-img-lb-canvas-wrap">
                <div id="sr-img-lb-stage">
                    <img id="sr-img-lb-img" src="" alt="">
                    <svg id="sr-img-lb-ann-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"></svg>
                    <div id="sr-img-lb-ann-html"></div>
                </div>
            </div>
            <aside id="sr-img-lb-side">
                <div id="sr-img-lb-side-head">
                    <span id="sr-img-lb-side-title">댓글</span>
                    <span id="sr-img-lb-cmt-count">0</span>
                </div>
                <div id="sr-img-lb-cmt-list"></div>
                <form id="sr-img-lb-cmt-form" onsubmit="return false;">
                    <textarea id="sr-img-lb-cmt-body" rows="2" placeholder="이미지에 댓글 작성…"></textarea>
                    <button type="submit" id="sr-img-lb-cmt-submit">등록</button>
                </form>
                <div id="sr-img-lb-cmt-disabled" style="display:none;">신규 SR은 먼저 <strong>등록</strong>해야<br>주석·댓글을 사용할 수 있습니다.</div>
            </aside>
        </div>
    </div>
</div>

<script>
(function() {
    const lb        = document.getElementById('sr-img-lightbox');
    const img       = document.getElementById('sr-img-lb-img');
    const titleEl   = document.getElementById('sr-img-lb-title');
    const svg       = document.getElementById('sr-img-lb-ann-svg');
    const annHtml   = document.getElementById('sr-img-lb-ann-html');
    const stage     = document.getElementById('sr-img-lb-stage');
    const side      = document.getElementById('sr-img-lb-side');
    const cmtList   = document.getElementById('sr-img-lb-cmt-list');
    const cmtForm   = document.getElementById('sr-img-lb-cmt-form');
    const cmtBody   = document.getElementById('sr-img-lb-cmt-body');
    const cmtSubmit = document.getElementById('sr-img-lb-cmt-submit');
    const cmtCount  = document.getElementById('sr-img-lb-cmt-count');
    const cmtDisabledMsg = document.getElementById('sr-img-lb-cmt-disabled');
    const toolButtons  = lb.querySelectorAll('.sr-lb-tool-btn');
    const colorButtons = lb.querySelectorAll('.sr-lb-color');

    const SVG_NS = 'http://www.w3.org/2000/svg';
    const CSRF = document.querySelector('meta[name=csrf-token]')?.content || '';

    let currentSrc = '';
    let currentSrId = null;
    let currentTool = null;     // rect/circle/line/number/text
    let currentColor = '#ef4444';
    let annotations = [];        // 서버에서 가져온 도형 목록
    let drawing = null;          // 그리기 진행 중 임시 상태
    let nextNumber = 1;

    // 초기 색상 active
    colorButtons[0]?.classList.add('active');

    // ── 라이트박스 열기/닫기 ──
    window.openSrImageLightbox = function(src, alt, srId) {
        if (!src) return;
        currentSrc = src;
        currentSrId = srId || null;
        img.src = src;
        img.alt = alt || '';
        titleEl.textContent = alt || (src.split('/').pop() || '이미지');
        lb.classList.add('is-open');
        setTool(null);
        clearSvg();
        annotations = [];
        nextNumber = 1;
        cmtList.innerHTML = '';
        cmtCount.textContent = '0';
        if (currentSrId) {
            cmtForm.style.display = '';
            cmtDisabledMsg.style.display = 'none';
            loadAnnotations();
            loadComments();
        } else {
            // 신규 SR — 저장 전. 주석·댓글 비활성
            cmtForm.style.display = 'none';
            cmtDisabledMsg.style.display = 'block';
            // 도구바도 disable
            toolButtons.forEach(b => b.disabled = true);
        }
    };
    window.closeSrImageLightbox = function() {
        if (document.fullscreenElement === lb) document.exitFullscreen?.();
        lb.classList.remove('is-open');
        img.src = '';
        clearSvg();
        toolButtons.forEach(b => b.disabled = false);
        if (textPopup) { textPopup.style.display = 'none'; textPopupPct = null; }
    };
    window.srLbToggleFullscreen = function() {
        if (document.fullscreenElement === lb) document.exitFullscreen?.();
        else lb.requestFullscreen?.();
    };
    window.srLbDownload = function() {
        if (!currentSrc) return;
        const a = document.createElement('a');
        a.href = currentSrc;
        a.download = (currentSrc.split('/').pop() || 'image').split('?')[0];
        a.target = '_blank';
        document.body.appendChild(a); a.click(); a.remove();
    };

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && lb.classList.contains('is-open')) {
            if (currentTool) { setTool(null); return; }
            window.closeSrImageLightbox();
        }
    });

    // ── 도구 선택 ──
    toolButtons.forEach(btn => btn.addEventListener('click', () => {
        if (!currentSrId) return;
        setTool(btn.dataset.tool === currentTool ? null : btn.dataset.tool);
    }));
    function setTool(tool) {
        currentTool = tool;
        toolButtons.forEach(b => b.classList.toggle('active', b.dataset.tool === tool));
        svg.classList.toggle('drawing', !!tool);
    }
    colorButtons.forEach(btn => btn.addEventListener('click', () => {
        currentColor = btn.dataset.color;
        colorButtons.forEach(b => b.classList.toggle('active', b === btn));
    }));

    // ── 좌표 변환 (mouse → 이미지 비율 0~1) ──
    function imgPercent(e) {
        const r = img.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        const y = (e.clientY - r.top)  / r.height;
        return { x: Math.max(0, Math.min(1, x)) * 100, y: Math.max(0, Math.min(1, y)) * 100 };
    }

    // ── SVG 캔버스 그리기 ──
    function clearSvg() {
        while (svg.firstChild) svg.removeChild(svg.firstChild);
        if (annHtml) while (annHtml.firstChild) annHtml.removeChild(annHtml.firstChild);
    }
    function renderAnnotations() {
        clearSvg();
        nextNumber = 1;
        annotations.forEach(a => {
            renderShape(a);
            if (a.shape === 'number') nextNumber = Math.max(nextNumber, (a.payload.num || 0) + 1);
        });
    }

    // 숫자 도형 — HTML 오버레이 렌더 (항상 정원, 정 사각형 텍스트)
    function renderHtmlNumber(a, p) {
        const numEl = document.createElement('div');
        numEl.className = 'sr-ann-num-html';
        numEl.dataset.annId = a.id ?? '';
        numEl.style.left = (p.x ?? 0) + '%';
        numEl.style.top  = (p.y ?? 0) + '%';
        numEl.style.background = a.color || '#ef4444';
        numEl.textContent = String(p.num ?? '?');
        annHtml.appendChild(numEl);

        if (a.can_delete && a.id != null) {
            numEl.classList.add('is-editable');
            attachHtmlNumberDragHandlers(numEl, a);
            appendHoverDeleteBadgeForHtmlNumber(numEl, a);
        }
    }

    // 숫자 도형 드래그 (HTML 기반)
    function attachHtmlNumberDragHandlers(numEl, a) {
        numEl.addEventListener('mousedown', (e) => {
            if (currentTool) return;
            e.stopPropagation();
            e.preventDefault();
            const start = imgPercent(e);
            let dx = 0, dy = 0, moved = false;
            const badge = annHtml.querySelector('.sr-ann-del-html[data-ann-id="' + a.id + '"]');

            const onMove = (ev) => {
                const pt = imgPercent(ev);
                dx = pt.x - start.x;
                dy = pt.y - start.y;
                if (!moved && (Math.abs(dx) > 0.4 || Math.abs(dy) > 0.4)) {
                    moved = true;
                    numEl.classList.add('is-dragging');
                    if (badge) { badge.classList.remove('is-visible'); badge.style.display = 'none'; }
                }
                if (moved) {
                    numEl.style.left = ((a.payload.x ?? 0) + dx) + '%';
                    numEl.style.top  = ((a.payload.y ?? 0) + dy) + '%';
                }
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);
                if (!moved) return;
                const newPayload = { ...a.payload, x: (a.payload.x ?? 0) + dx, y: (a.payload.y ?? 0) + dy };
                updateAnnotation(a.id, newPayload,
                    () => { a.payload = newPayload; renderAnnotations(); },
                    () => {
                        numEl.style.left = (a.payload.x ?? 0) + '%';
                        numEl.style.top  = (a.payload.y ?? 0) + '%';
                        numEl.classList.remove('is-dragging');
                        alert('주석 이동 저장 실패');
                    }
                );
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    // 숫자 도형 전용 호버 X 배지 (HTML number 우상단)
    function appendHoverDeleteBadgeForHtmlNumber(numEl, a) {
        const btn = document.createElement('div');
        btn.className = 'sr-ann-del-html';
        btn.dataset.annId = a.id;
        btn.textContent = '×';
        btn.title = '삭제';
        // 숫자 원 우상단에 살짝 오른쪽-위로 (반지름 13px 기준)
        btn.style.left = `calc(${a.payload.x ?? 0}% + 11px)`;
        btn.style.top  = `calc(${a.payload.y ?? 0}% - 11px)`;

        let hideTimer = null;
        const show = () => { clearTimeout(hideTimer); btn.classList.add('is-visible'); };
        const hide = () => { hideTimer = setTimeout(() => btn.classList.remove('is-visible'), 80); };
        numEl.addEventListener('mouseenter', show);
        numEl.addEventListener('mouseleave', hide);
        btn.addEventListener('mouseenter', show);
        btn.addEventListener('mouseleave', hide);

        btn.addEventListener('mousedown', (e) => { e.stopPropagation(); e.preventDefault(); });
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteAnnotation(a.id);
        });
        annHtml.appendChild(btn);
    }

    // 호버 시 도형 우상단에 표시되는 X 삭제 배지 — HTML overlay (확대/축소 무관 고정 픽셀 크기)
    function appendHoverDeleteBadge(g, a) {
        let bx, by;
        try {
            const bb = g.getBBox();
            bx = bb.x + bb.width;   // viewBox 단위 = % (0~100)
            by = bb.y;
        } catch (e) {
            const p = a.payload || {};
            bx = (p.x ?? 0) + Math.max(0, p.w ?? 0);
            by = (p.y ?? 0) + Math.min(0, p.h ?? 0);
        }
        const btn = document.createElement('div');
        btn.className = 'sr-ann-del-html';
        btn.dataset.annId = a.id;
        btn.textContent = '×';
        btn.style.left = bx + '%';
        btn.style.top  = by + '%';
        btn.title = '삭제';

        // 호버 브리지 — 도형↔배지 간 마우스 이동시 깜빡임 방지
        let hideTimer = null;
        const show = () => { clearTimeout(hideTimer); btn.classList.add('is-visible'); };
        const hide = () => { hideTimer = setTimeout(() => btn.classList.remove('is-visible'), 80); };
        g.addEventListener('mouseenter', show);
        g.addEventListener('mouseleave', hide);
        btn.addEventListener('mouseenter', show);
        btn.addEventListener('mouseleave', hide);

        // 드래그/그리기와 충돌 방지
        btn.addEventListener('mousedown', (e) => { e.stopPropagation(); e.preventDefault(); });
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            deleteAnnotation(a.id);
        });
        annHtml.appendChild(btn);
    }
    function renderShape(a) {
        const p = a.payload || {};

        // 숫자 도형은 SVG 비균등 스트레치와 무관하게 정원이 되도록 HTML 오버레이로 그림
        if (a.shape === 'number') {
            renderHtmlNumber(a, p);
            return;
        }

        const g = document.createElementNS(SVG_NS, 'g');
        g.classList.add('sr-ann-shape');
        if (a.id != null) g.setAttribute('data-ann-id', a.id);

        if (a.shape === 'rect') {
            const rect = document.createElementNS(SVG_NS, 'rect');
            rect.setAttribute('x', Math.min(p.x, p.x + p.w));
            rect.setAttribute('y', Math.min(p.y, p.y + p.h));
            rect.setAttribute('width',  Math.abs(p.w));
            rect.setAttribute('height', Math.abs(p.h));
            rect.setAttribute('stroke', a.color);
            rect.setAttribute('fill', 'none');
            g.appendChild(rect);
        } else if (a.shape === 'circle') {
            const e = document.createElementNS(SVG_NS, 'ellipse');
            e.setAttribute('cx', p.x + p.w / 2);
            e.setAttribute('cy', p.y + p.h / 2);
            e.setAttribute('rx', Math.abs(p.w) / 2);
            e.setAttribute('ry', Math.abs(p.h) / 2);
            e.setAttribute('stroke', a.color);
            e.setAttribute('fill', 'none');
            g.appendChild(e);
        } else if (a.shape === 'line') {
            const line = document.createElementNS(SVG_NS, 'line');
            line.setAttribute('x1', p.x); line.setAttribute('y1', p.y);
            line.setAttribute('x2', p.x + p.w); line.setAttribute('y2', p.y + p.h);
            line.setAttribute('stroke', a.color);
            line.setAttribute('stroke-width', '0.45');
            line.setAttribute('stroke-linecap', 'round');
            line.setAttribute('vector-effect', 'non-scaling-stroke');
            g.appendChild(line);
            const angle = Math.atan2(p.h, p.w);
            const ah = 2; // 길이 (퍼센트)
            const hx = p.x + p.w, hy = p.y + p.h;
            const head = document.createElementNS(SVG_NS, 'polygon');
            head.setAttribute('points',
                `${hx},${hy} ${hx - ah*Math.cos(angle-0.4)},${hy - ah*Math.sin(angle-0.4)} ${hx - ah*Math.cos(angle+0.4)},${hy - ah*Math.sin(angle+0.4)}`);
            head.setAttribute('fill', a.color);
            g.appendChild(head);
        } else if (a.shape === 'text') {
            const t = document.createElementNS(SVG_NS, 'text');
            t.setAttribute('x', p.x); t.setAttribute('y', p.y);
            t.setAttribute('fill', a.color);
            t.setAttribute('font-size', '3');
            t.setAttribute('font-weight', '600');
            t.textContent = p.text || '';
            g.appendChild(t);
        } else {
            return;
        }

        if (a.can_delete && a.id != null) {
            g.setAttribute('data-can-delete', '1');
            attachShapeDragHandlers(g, a);
        }
        svg.appendChild(g);
        // 도형이 svg에 붙은 다음 호버용 삭제 배지 추가 (bbox 계산 후 같은 group 내부에 추가)
        if (a.can_delete && a.id != null) {
            appendHoverDeleteBadge(g, a);
        }
    }

    // 도형 드래그 = 이동, 클릭(이동 없음) = 삭제
    function attachShapeDragHandlers(g, a) {
        g.addEventListener('mousedown', (e) => {
            if (currentTool) return;       // 그리기 도구 활성 시 통과
            e.stopPropagation();
            e.preventDefault();
            const start = imgPercent(e);
            let dx = 0, dy = 0, moved = false;
            const badge = annHtml.querySelector('.sr-ann-del-html[data-ann-id="' + a.id + '"]');

            const onMove = (ev) => {
                const p = imgPercent(ev);
                dx = p.x - start.x;
                dy = p.y - start.y;
                if (!moved && (Math.abs(dx) > 0.4 || Math.abs(dy) > 0.4)) {
                    moved = true;
                    g.classList.add('is-dragging');
                    // 드래그 시작 — 삭제 X 배지 즉시 숨김 (파일 뷰어와 동일 동작; 드롭 후 재렌더링되며 새 위치에 복원)
                    if (badge) { badge.classList.remove('is-visible'); badge.style.display = 'none'; }
                }
                if (moved) g.setAttribute('transform', `translate(${dx} ${dy})`);
            };
            const onUp = () => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);

                if (!moved) {
                    // 클릭만(이동 없음) — 호버 X 배지로 삭제 가능하므로 별도 동작 없음
                    return;
                }

                // 이동 → 새 좌표 반영 후 서버 저장
                const newPayload = { ...a.payload,
                    x: (a.payload.x ?? 0) + dx,
                    y: (a.payload.y ?? 0) + dy,
                };
                updateAnnotation(a.id, newPayload,
                    () => {
                        a.payload = newPayload;
                        g.removeAttribute('transform');
                        g.classList.remove('is-dragging');
                        renderAnnotations();
                    },
                    () => {
                        g.removeAttribute('transform');
                        g.classList.remove('is-dragging');
                        alert('주석 이동 저장 실패');
                    }
                );
            };
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }

    // ── 그리기 (mouse) ──
    svg.addEventListener('mousedown', (e) => {
        if (!currentTool || !currentSrId) return;
        const p = imgPercent(e);
        if (currentTool === 'number') {
            createAnnotation({ shape: 'number', color: currentColor, payload: { x: p.x, y: p.y, num: nextNumber } });
            setTool(null);   // 도형 삽입 후 도구 자동 해제 (파일 뷰어와 동일)
            return;
        }
        if (currentTool === 'text') {
            openTextPopup(e, p);
            return;
        }
        drawing = { tool: currentTool, color: currentColor, startX: p.x, startY: p.y, x: p.x, y: p.y };
    });
    svg.addEventListener('mousemove', (e) => {
        if (!drawing) return;
        const p = imgPercent(e);
        drawing.x = p.x; drawing.y = p.y;
        clearTempShape();
        drawTempShape();
    });
    svg.addEventListener('mouseup', (e) => {
        if (!drawing) return;
        const w = drawing.x - drawing.startX;
        const h = drawing.y - drawing.startY;
        clearTempShape();
        if (Math.abs(w) >= 1 || Math.abs(h) >= 1) {
            createAnnotation({
                shape: drawing.tool,
                color: drawing.color,
                payload: { x: drawing.startX, y: drawing.startY, w, h },
            });
            setTool(null);   // 도형 삽입 후 도구 자동 해제 (파일 뷰어와 동일)
        }
        drawing = null;
    });
    function clearTempShape() {
        const tmp = svg.querySelector('[data-tmp]');
        if (tmp) tmp.remove();
    }
    function drawTempShape() {
        const a = {
            shape: drawing.tool, color: drawing.color,
            payload: { x: drawing.startX, y: drawing.startY, w: drawing.x - drawing.startX, h: drawing.y - drawing.startY },
        };
        const prev = svg.children.length;
        renderShape(a);
        const last = svg.children[prev];
        if (last) last.setAttribute('data-tmp', '1');
    }

    // ── 텍스트 주석 입력 팝업 (파일 뷰어와 동일 방식) ──
    let textPopupPct = null;
    const textPopup    = document.getElementById('sr-ann-text-popup');
    const textPopupIn  = document.getElementById('sr-ann-text-input');
    const textPopupOk  = document.getElementById('sr-ann-text-confirm');
    const textPopupCxl = document.getElementById('sr-ann-text-cancel');

    function openTextPopup(ev, pct) {
        textPopupPct = pct;
        const popupW = 360, popupH = 210;
        const x = Math.max(8, Math.min(ev.clientX, window.innerWidth  - popupW - 8));
        const y = Math.max(8, Math.min(ev.clientY, window.innerHeight - popupH - 8));
        textPopup.style.left = x + 'px';
        textPopup.style.top  = y + 'px';
        textPopup.style.display = 'block';
        setTimeout(() => { textPopupIn.value = ''; textPopupIn.focus(); }, 30);
    }
    function confirmTextPopup() {
        const val = textPopupIn.value.trim();
        textPopup.style.display = 'none';
        textPopupIn.value = '';
        if (val && textPopupPct && currentSrId) {
            createAnnotation({ shape: 'text', color: currentColor, payload: { x: textPopupPct.x, y: textPopupPct.y, text: val } });
        }
        textPopupPct = null;
        setTool(null);
    }
    function cancelTextPopup() {
        textPopup.style.display = 'none';
        textPopupIn.value = '';
        textPopupPct = null;
        setTool(null);
    }
    textPopupOk?.addEventListener('click',  confirmTextPopup);
    textPopupCxl?.addEventListener('click', cancelTextPopup);
    textPopupIn?.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') { e.preventDefault(); confirmTextPopup(); }
        if (e.key === 'Escape') { e.preventDefault(); cancelTextPopup(); }
    });

    // ── API: annotations ──
    function urlAnn(suffix) {
        return @json(url('/maint-requests')) + '/' + currentSrId + '/image-annotations' + (suffix || '');
    }
    function loadAnnotations() {
        if (!currentSrId) return;
        fetch(urlAnn() + '?image_url=' + encodeURIComponent(currentSrc), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                annotations = d.annotations || [];
                renderAnnotations();
            }).catch(() => {});
    }
    function createAnnotation(spec) {
        fetch(urlAnn(), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify({ image_url: currentSrc, ...spec }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok && d.annotation) {
                annotations.push(d.annotation);
                renderAnnotations();
            }
        }).catch(() => alert('주석 저장 실패'));
    }
    function deleteAnnotation(id) {
        fetch(urlAnn('/' + id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                annotations = annotations.filter(a => a.id !== id);
                renderAnnotations();
            }
        });
    }
    function updateAnnotation(id, payload, onOk, onErr) {
        fetch(urlAnn('/' + id), {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify({ payload }),
        })
        .then(r => r.json())
        .then(d => { d.ok ? (onOk && onOk()) : (onErr && onErr()); })
        .catch(() => onErr && onErr());
    }

    // ── API: comments ──
    function urlCmt(suffix) {
        return @json(url('/maint-requests')) + '/' + currentSrId + '/image-comments' + (suffix || '');
    }
    function loadComments() {
        if (!currentSrId) return;
        fetch(urlCmt() + '?image_url=' + encodeURIComponent(currentSrc), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(r => r.json())
            .then(d => {
                renderComments(d.comments || []);
            });
    }
    function renderComments(list) {
        cmtList.innerHTML = '';
        cmtCount.textContent = list.length;
        list.forEach(c => cmtList.appendChild(commentNode(c)));
    }
    function commentNode(c) {
        const wrap = document.createElement('div');
        wrap.className = 'sr-cmt-item';
        const head = document.createElement('div'); head.className = 'sr-cmt-head';
        const author = document.createElement('span'); author.className = 'sr-cmt-author'; author.textContent = c.user_name || '?';
        const meta = document.createElement('div'); meta.style.display = 'flex'; meta.style.alignItems = 'center'; meta.style.gap = '6px';
        const time = document.createElement('span'); time.className = 'sr-cmt-time'; time.textContent = c.created_at || '';
        meta.appendChild(time);
        if (c.can_delete) {
            const del = document.createElement('button'); del.type = 'button'; del.className = 'sr-cmt-del'; del.textContent = '삭제';
            del.addEventListener('click', () => {
                if (!confirm('이 댓글을 삭제하시겠습니까?')) return;
                deleteComment(c.id, wrap);
            });
            meta.appendChild(del);
        }
        head.appendChild(author); head.appendChild(meta);
        const body = document.createElement('div'); body.className = 'sr-cmt-body'; body.textContent = c.body;
        wrap.appendChild(head); wrap.appendChild(body);
        return wrap;
    }
    function deleteComment(id, node) {
        fetch(urlCmt('/' + id), {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
        }).then(r => r.json()).then(d => {
            if (d.ok) {
                node.remove();
                cmtCount.textContent = String(parseInt(cmtCount.textContent) - 1);
            }
        });
    }
    cmtSubmit.addEventListener('click', () => {
        const body = cmtBody.value.trim();
        if (!body || !currentSrId) return;
        cmtSubmit.disabled = true;
        fetch(urlCmt(), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            credentials: 'same-origin',
            body: JSON.stringify({ image_url: currentSrc, body }),
        })
        .then(r => r.json())
        .then(d => {
            if (d.ok && d.comment) {
                cmtList.appendChild(commentNode(d.comment));
                cmtCount.textContent = String(parseInt(cmtCount.textContent) + 1);
                cmtBody.value = '';
            } else { alert(d.message || '댓글 등록 실패'); }
        }).catch(() => alert('댓글 등록 실패'))
        .finally(() => { cmtSubmit.disabled = false; });
    });
})();
</script>
