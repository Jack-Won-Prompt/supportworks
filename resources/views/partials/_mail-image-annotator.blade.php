{{--
    메일 작성 이미지 주석 모달 — Quill 본문 이미지에 도형(네모/동그라미/화살표/텍스트)을 그려
    canvas 에 burn-in 합성한 뒤 서버에 업로드, 기존 이미지를 새 이미지로 교체한다.

    사용:
      1) 본 파셜을 한 번 include (보통 layouts.app 의 partial 영역)
      2) 메일 페이지의 Quill 본문에서 이미지 선택 시 진입 버튼을 통해
         window.openMailImageAnnotator(quill, imgEl, { uploadUrl, csrfToken }) 호출
--}}
@once
<style>
    .mia-overlay { display:none; position:fixed; inset:0; z-index:10700; background:rgba(15,15,25,.7); align-items:center; justify-content:center; }
    .mia-overlay.is-open { display:flex; }
    .mia-modal { background:#fff; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.35); width:min(1200px, 96vw); max-height:96vh; display:flex; flex-direction:column; overflow:hidden; }
    .mia-head { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; border-bottom:1px solid #f0f0f0; background:#fafafa; flex-shrink:0; }
    .mia-head h3 { margin:0; font-size:14px; font-weight:700; color:#18181b; }
    .mia-head-x { background:none; border:none; cursor:pointer; color:#6b7280; padding:4px 8px; font-size:18px; line-height:1; border-radius:6px; }
    .mia-head-x:hover { background:#fee2e2; color:#dc2626; }

    .mia-toolbar { display:flex; align-items:center; gap:10px; padding:8px 14px; border-bottom:1px solid #f0f0f0; flex-wrap:wrap; flex-shrink:0; background:#fff; }
    .mia-tool-group { display:flex; gap:4px; align-items:center; padding-right:10px; border-right:1px solid #f0f0f0; }
    .mia-tool-group:last-child { border-right:none; padding-right:0; }
    .mia-tool { display:inline-flex; align-items:center; justify-content:center; gap:4px; min-width:34px; height:30px; padding:0 8px; background:#fff; border:1.5px solid #e5e7eb; border-radius:7px; cursor:pointer; color:#374151; font-size:12px; font-weight:600; transition:all .12s; }
    .mia-tool:hover { background:#f5f3ff; border-color:var(--t300,#c4b5fd); color:var(--t700,#6d28d9); }
    .mia-tool.is-active { background:var(--t100,#ede9fe); border-color:var(--t500,#8b5cf6); color:var(--t700,#6d28d9); }
    .mia-color { width:22px; height:22px; border-radius:50%; border:2px solid #fff; box-shadow:0 0 0 1.5px #e5e7eb; cursor:pointer; padding:0; }
    .mia-color.is-active { box-shadow:0 0 0 2px var(--t600,#7c3aed); }
    .mia-thickness { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:#6b7280; }
    .mia-thickness input { width:80px; }
    .mia-label-small { font-size:10px; color:#9ca3af; text-transform:uppercase; letter-spacing:.04em; }

    .mia-stage { flex:1; min-height:300px; background:#1e1b2e; display:flex; align-items:center; justify-content:center; overflow:auto; padding:20px; }
    .mia-canvas-wrap { position:relative; display:inline-block; }
    .mia-canvas-wrap canvas { display:block; max-width:100%; cursor:crosshair; user-select:none; }
    .mia-canvas-wrap.tool-select canvas { cursor:default; }
    .mia-text-input { position:absolute; background:rgba(255,255,255,.97); border:2px solid var(--t500,#8b5cf6); border-radius:6px; padding:4px 8px; font-size:14px; outline:none; min-width:80px; font-family:inherit; }

    .mia-foot { display:flex; align-items:center; justify-content:space-between; padding:10px 16px; border-top:1px solid #f0f0f0; background:#fafafa; flex-shrink:0; }
    .mia-status { font-size:11.5px; color:#94a3b8; }
    .mia-buttons { display:flex; gap:8px; }
    .mia-btn-cancel { padding:7px 16px; background:#fff; border:1.5px solid #e4e4e7; color:#52525b; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; }
    .mia-btn-cancel:hover { background:#f4f4f5; }
    .mia-btn-apply { padding:7px 18px; background:linear-gradient(135deg,var(--t500,#8b5cf6),var(--t700,#6d28d9)); color:#fff; border:none; border-radius:8px; font-size:12.5px; font-weight:700; cursor:pointer; box-shadow:0 2px 6px rgba(124,58,237,.3); }
    .mia-btn-apply:hover { filter:brightness(1.08); }
    .mia-btn-apply:disabled { opacity:.6; cursor:not-allowed; }
</style>

<div id="mia-overlay" class="mia-overlay" onclick="if(event.target===this) miaClose()">
    <div class="mia-modal" role="dialog" aria-modal="true" aria-labelledby="mia-title">
        <div class="mia-head">
            <h3 id="mia-title">이미지 주석</h3>
            <button type="button" class="mia-head-x" onclick="miaClose()" aria-label="닫기">&times;</button>
        </div>
        <div class="mia-toolbar">
            <div class="mia-tool-group">
                <span class="mia-label-small">도형</span>
                <button type="button" class="mia-tool" data-tool="select" onclick="miaSetTool('select')" title="선택">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l7.07 16.97 2.51-7.39 7.39-2.51L3 3z"/></svg>
                </button>
                <button type="button" class="mia-tool is-active" data-tool="rect" onclick="miaSetTool('rect')" title="네모">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="4" y="6" width="16" height="12" rx="1"/></svg>
                </button>
                <button type="button" class="mia-tool" data-tool="circle" onclick="miaSetTool('circle')" title="동그라미">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="12" rx="9" ry="7"/></svg>
                </button>
                <button type="button" class="mia-tool" data-tool="arrow" onclick="miaSetTool('arrow')" title="화살표">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 19L19 5m0 0H9m10 0v10"/></svg>
                </button>
                <button type="button" class="mia-tool" data-tool="text" onclick="miaSetTool('text')" title="텍스트" style="font-weight:800;font-size:14px;">T</button>
            </div>
            <div class="mia-tool-group">
                <span class="mia-label-small">색상</span>
                <button type="button" class="mia-color is-active" data-color="#ef4444" style="background:#ef4444;" onclick="miaSetColor('#ef4444')" title="빨강"></button>
                <button type="button" class="mia-color"             data-color="#3b82f6" style="background:#3b82f6;" onclick="miaSetColor('#3b82f6')" title="파랑"></button>
                <button type="button" class="mia-color"             data-color="#10b981" style="background:#10b981;" onclick="miaSetColor('#10b981')" title="초록"></button>
                <button type="button" class="mia-color"             data-color="#f59e0b" style="background:#f59e0b;" onclick="miaSetColor('#f59e0b')" title="주황"></button>
                <button type="button" class="mia-color"             data-color="#000000" style="background:#000000;" onclick="miaSetColor('#000000')" title="검정"></button>
            </div>
            <div class="mia-tool-group">
                <span class="mia-thickness">
                    <span class="mia-label-small">굵기</span>
                    <input type="range" min="2" max="12" value="4" oninput="miaSetThickness(this.value)">
                    <span id="mia-thickness-val" style="font-size:11px;color:#374151;min-width:14px;text-align:right;">4</span>
                </span>
            </div>
            <div class="mia-tool-group">
                <button type="button" class="mia-tool" onclick="miaUndo()" title="되돌리기 (Ctrl+Z)">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 14l-4-4 4-4M5 10h11a4 4 0 014 4v0a4 4 0 01-4 4h-4"/></svg>
                </button>
                <button type="button" class="mia-tool" onclick="miaClear()" title="모두 지우기">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M4 7h16"/></svg>
                </button>
                <button type="button" class="mia-tool" onclick="miaDeleteSelected()" title="선택 도형 삭제 (Delete)">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
        <div class="mia-stage">
            <div id="mia-canvas-wrap" class="mia-canvas-wrap">
                <canvas id="mia-canvas"></canvas>
            </div>
        </div>
        <div class="mia-foot">
            <span id="mia-status" class="mia-status">도형을 그리려면 캔버스에 드래그하세요. 텍스트는 클릭 후 입력.</span>
            <div class="mia-buttons">
                <button type="button" class="mia-btn-cancel" onclick="miaClose()">취소</button>
                <button type="button" id="mia-btn-apply" class="mia-btn-apply" onclick="miaApply()">적용</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    if (window.openMailImageAnnotator) return;

    let _ctx = null;          // 2d context
    let _cvs = null;          // canvas element
    let _wrap = null;         // canvas wrapper
    let _image = null;        // HTMLImageElement (원본)
    let _shapes = [];         // {kind, color, lineWidth, x1,y1,x2,y2, text?}
    let _tool = 'rect';
    let _color = '#ef4444';
    let _lineWidth = 4;
    let _drawing = null;      // {kind, x1, y1, x2, y2, color, lineWidth}
    let _selectedIdx = -1;
    let _resolveCtx = null;   // { quill, imgEl, uploadUrl, csrfToken }
    let _scale = 1;           // 화면 표시 배율 (캔버스 픽셀 vs CSS 픽셀). burn-in 은 원본 픽셀로 합성.

    function el(id) { return document.getElementById(id); }

    window.openMailImageAnnotator = function(quill, imgEl, opts) {
        _resolveCtx = { quill, imgEl, uploadUrl: opts?.uploadUrl || '', csrfToken: opts?.csrfToken || '' };
        _shapes = [];
        _selectedIdx = -1;
        _drawing = null;

        // 원본 이미지 로드 (CORS 안전: same-origin 만 가정)
        _image = new Image();
        _image.crossOrigin = 'anonymous';
        _image.onload = () => {
            _cvs = el('mia-canvas');
            _wrap = el('mia-canvas-wrap');
            _cvs.width  = _image.naturalWidth;
            _cvs.height = _image.naturalHeight;
            // 화면에 너무 크면 CSS 로 축소 표시 (burn-in 은 원본 해상도 유지)
            const maxW = Math.min(window.innerWidth - 120, 1100);
            const maxH = Math.min(window.innerHeight - 220, 700);
            _scale = Math.min(maxW / _image.naturalWidth, maxH / _image.naturalHeight, 1);
            _cvs.style.width  = (_image.naturalWidth  * _scale) + 'px';
            _cvs.style.height = (_image.naturalHeight * _scale) + 'px';
            _ctx = _cvs.getContext('2d');
            miaRedraw();
            el('mia-overlay').classList.add('is-open');
        };
        _image.onerror = () => { alert('이미지를 불러올 수 없습니다. (서로 다른 도메인의 외부 이미지는 이미지 주석 불가)'); };
        _image.src = imgEl.src;
    };

    window.miaClose = function() {
        el('mia-overlay').classList.remove('is-open');
        _shapes = []; _drawing = null; _selectedIdx = -1; _image = null; _resolveCtx = null;
        if (_ctx && _cvs) _ctx.clearRect(0, 0, _cvs.width, _cvs.height);
        const ti = document.querySelector('.mia-text-input');
        if (ti) ti.remove();
    };

    window.miaSetTool = function(t) {
        _tool = t;
        document.querySelectorAll('.mia-tool[data-tool]').forEach(b => b.classList.toggle('is-active', b.dataset.tool === t));
        _wrap?.classList.toggle('tool-select', t === 'select');
        if (t !== 'select') _selectedIdx = -1;
        miaRedraw();
    };
    window.miaSetColor = function(c) {
        _color = c;
        document.querySelectorAll('.mia-color').forEach(b => b.classList.toggle('is-active', b.dataset.color === c));
    };
    window.miaSetThickness = function(v) {
        _lineWidth = parseInt(v, 10) || 4;
        el('mia-thickness-val').textContent = _lineWidth;
    };
    window.miaUndo = function() {
        if (_shapes.length === 0) return;
        _shapes.pop();
        _selectedIdx = -1;
        miaRedraw();
    };
    window.miaClear = async function() {
        if (_shapes.length === 0) return;
        const ask = window.__confirm || ((m) => Promise.resolve(confirm(m)));
        if (!(await ask('모든 도형을 지울까요?'))) return;
        _shapes = []; _selectedIdx = -1; miaRedraw();
    };
    window.miaDeleteSelected = function() {
        if (_selectedIdx < 0) return;
        _shapes.splice(_selectedIdx, 1);
        _selectedIdx = -1;
        miaRedraw();
    };

    // ── 그리기 핵심 ──
    function miaRedraw() {
        if (!_ctx || !_image) return;
        _ctx.clearRect(0, 0, _cvs.width, _cvs.height);
        _ctx.drawImage(_image, 0, 0);
        _shapes.forEach((s, i) => drawShape(s, i === _selectedIdx));
        if (_drawing) drawShape(_drawing, false);
    }

    function drawShape(s, selected) {
        _ctx.save();
        _ctx.strokeStyle = s.color;
        _ctx.fillStyle = s.color;
        _ctx.lineWidth = s.lineWidth;
        _ctx.lineCap = 'round'; _ctx.lineJoin = 'round';
        if (s.kind === 'rect') {
            const x = Math.min(s.x1, s.x2), y = Math.min(s.y1, s.y2);
            const w = Math.abs(s.x2 - s.x1), h = Math.abs(s.y2 - s.y1);
            _ctx.strokeRect(x, y, w, h);
        } else if (s.kind === 'circle') {
            const cx = (s.x1 + s.x2) / 2, cy = (s.y1 + s.y2) / 2;
            const rx = Math.abs(s.x2 - s.x1) / 2, ry = Math.abs(s.y2 - s.y1) / 2;
            _ctx.beginPath(); _ctx.ellipse(cx, cy, rx, ry, 0, 0, Math.PI * 2); _ctx.stroke();
        } else if (s.kind === 'arrow') {
            _ctx.beginPath(); _ctx.moveTo(s.x1, s.y1); _ctx.lineTo(s.x2, s.y2); _ctx.stroke();
            // 화살촉
            const ang = Math.atan2(s.y2 - s.y1, s.x2 - s.x1);
            const head = 8 + s.lineWidth * 1.6;
            _ctx.beginPath();
            _ctx.moveTo(s.x2, s.y2);
            _ctx.lineTo(s.x2 - head * Math.cos(ang - Math.PI / 7), s.y2 - head * Math.sin(ang - Math.PI / 7));
            _ctx.lineTo(s.x2 - head * Math.cos(ang + Math.PI / 7), s.y2 - head * Math.sin(ang + Math.PI / 7));
            _ctx.closePath(); _ctx.fill();
        } else if (s.kind === 'text') {
            const fontSize = 14 + s.lineWidth * 2.5;
            _ctx.font = `${fontSize}px "Pretendard","Noto Sans KR",sans-serif`;
            _ctx.textBaseline = 'top';
            // 가독성을 위해 텍스트 뒤에 살짝 흰색 outline
            _ctx.lineWidth = Math.max(2, Math.round(s.lineWidth / 2));
            _ctx.strokeStyle = 'rgba(255,255,255,0.85)';
            _ctx.strokeText(s.text || '', s.x1, s.y1);
            _ctx.fillStyle = s.color;
            _ctx.fillText(s.text || '', s.x1, s.y1);
        }
        if (selected) {
            const b = shapeBounds(s);
            _ctx.strokeStyle = '#7c3aed';
            _ctx.lineWidth = 1;
            _ctx.setLineDash([5, 4]);
            _ctx.strokeRect(b.x - 3, b.y - 3, b.w + 6, b.h + 6);
            _ctx.setLineDash([]);
        }
        _ctx.restore();
    }

    function shapeBounds(s) {
        if (s.kind === 'text') {
            const fontSize = 14 + s.lineWidth * 2.5;
            _ctx.font = `${fontSize}px "Pretendard","Noto Sans KR",sans-serif`;
            const w = _ctx.measureText(s.text || '').width;
            return { x: s.x1, y: s.y1, w, h: fontSize * 1.2 };
        }
        const x = Math.min(s.x1, s.x2), y = Math.min(s.y1, s.y2);
        const w = Math.abs(s.x2 - s.x1), h = Math.abs(s.y2 - s.y1);
        return { x, y, w, h };
    }

    function hitTest(x, y) {
        for (let i = _shapes.length - 1; i >= 0; i--) {
            const b = shapeBounds(_shapes[i]);
            if (x >= b.x - 6 && x <= b.x + b.w + 6 && y >= b.y - 6 && y <= b.y + b.h + 6) return i;
        }
        return -1;
    }

    function evtPos(e) {
        const r = _cvs.getBoundingClientRect();
        return {
            x: (e.clientX - r.left) / _scale,
            y: (e.clientY - r.top)  / _scale,
        };
    }

    // ── 마우스 이벤트 ──
    document.addEventListener('mousedown', e => {
        if (!_cvs || e.target !== _cvs) return;
        e.preventDefault();
        const p = evtPos(e);
        if (_tool === 'select') {
            _selectedIdx = hitTest(p.x, p.y);
            miaRedraw();
            return;
        }
        if (_tool === 'text') {
            // 텍스트 입력 박스 표시
            const ti = document.createElement('input');
            ti.type = 'text';
            ti.className = 'mia-text-input';
            ti.style.left = (p.x * _scale) + 'px';
            ti.style.top  = (p.y * _scale) + 'px';
            ti.placeholder = '텍스트 입력 후 Enter';
            _wrap.appendChild(ti);
            ti.focus();
            const commit = () => {
                const v = ti.value.trim();
                ti.remove();
                if (v) {
                    _shapes.push({ kind:'text', color:_color, lineWidth:_lineWidth, x1:p.x, y1:p.y, text:v });
                    _selectedIdx = _shapes.length - 1;
                    miaSetTool('select');
                    miaRedraw();
                }
            };
            ti.addEventListener('keydown', ev => {
                if (ev.key === 'Enter') { ev.preventDefault(); commit(); }
                else if (ev.key === 'Escape') ti.remove();
            });
            ti.addEventListener('blur', commit);
            return;
        }
        _drawing = { kind:_tool, color:_color, lineWidth:_lineWidth, x1:p.x, y1:p.y, x2:p.x, y2:p.y };
    });
    document.addEventListener('mousemove', e => {
        if (!_drawing || !_cvs) return;
        const p = evtPos(e);
        _drawing.x2 = p.x; _drawing.y2 = p.y;
        miaRedraw();
    });
    document.addEventListener('mouseup', e => {
        if (!_drawing) return;
        const dx = Math.abs(_drawing.x2 - _drawing.x1);
        const dy = Math.abs(_drawing.y2 - _drawing.y1);
        if (dx + dy > 4) {
            _shapes.push(_drawing);              // 너무 작은 드래그는 무시
            _selectedIdx = _shapes.length - 1;   // 방금 그린 도형 자동 선택
            miaSetTool('select');                // 그리기 도구 → 선택 도구로 자동 복귀
        }
        _drawing = null;
        miaRedraw();
    });
    document.addEventListener('keydown', e => {
        if (!el('mia-overlay').classList.contains('is-open')) return;
        if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA')) return;
        if (e.key === 'Delete' || e.key === 'Backspace') { miaDeleteSelected(); e.preventDefault(); }
        else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { miaUndo(); e.preventDefault(); }
        else if (e.key === 'Escape') { miaClose(); }
    });

    // ── Burn-in + 업로드 + Quill 교체 ──
    window.miaApply = async function() {
        if (!_resolveCtx || !_cvs) return;
        const btn = el('mia-btn-apply');
        const status = el('mia-status');
        btn.disabled = true;
        const prevLabel = btn.textContent;
        btn.textContent = '업로드 중...';
        status.textContent = '이미지 합성 + 업로드 중...';

        // 합성에는 선택 박스(점선)가 들어가지 않도록 임시로 selection 해제 후 redraw
        const prevSelected = _selectedIdx;
        _selectedIdx = -1;
        miaRedraw();

        try {
            const blob = await new Promise((res, rej) => _cvs.toBlob(b => b ? res(b) : rej('blob 생성 실패'), 'image/png', 0.92));
            const fd = new FormData();
            fd.append('image', blob, 'annotated.png');
            const r = await fetch(_resolveCtx.uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': _resolveCtx.csrfToken, 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: fd,
            });
            if (!r.ok) throw new Error('업로드 실패 (' + r.status + ')');
            const d = await r.json();
            if (!d.url) throw new Error('서버가 이미지 URL을 반환하지 않았습니다.');

            // Quill 이미지 src 교체 + 형식 보존
            const img = _resolveCtx.imgEl;
            img.removeAttribute('width');
            img.removeAttribute('height');
            img.style.width = '';   // StyledImage 가 보존하는 inline style 도 클리어
            img.style.height = '';
            img.src = d.url;
            // Quill 내부 상태 갱신
            try { _resolveCtx.quill.update('user'); } catch (_) {}

            status.textContent = '완료';
            miaClose();
        } catch (err) {
            alert('이미지 주석 적용 실패: ' + (err.message || err));
            status.textContent = '실패';
            // 실패 시 selection 복구 (모달 유지)
            _selectedIdx = prevSelected;
            miaRedraw();
        } finally {
            btn.disabled = false;
            btn.textContent = prevLabel;
        }
    };
})();
</script>
@endonce
