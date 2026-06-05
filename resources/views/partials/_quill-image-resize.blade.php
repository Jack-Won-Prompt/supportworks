{{--
    Quill 이미지 리사이즈 + 클립보드 paste — SR 요청 상세(_form) 의 표준 동작을 그대로 옮겨둔 공유 파셜.
    사용:
      1) 본 파셜을 한 번만 include (보통 layouts.app)
      2) 페이지에서 Quill 인스턴스 만든 뒤:
         installQuillImageResize(quill, { uploadUrl: '...', csrfToken: '...' })

    paste/툴바 업로드는 caller 가 직접 처리하는 게 자유롭지만, 본 함수는 paste 핸들러도 함께 설치한다.
--}}
@once
<style>
    .stdq-img-overlay { position:fixed; pointer-events:none; z-index:11050; display:none; }
    .stdq-img-overlay.is-active { display:block; }
    .stdq-img-handle { position:absolute; width:10px; height:10px; background:var(--t500, #7c3aed); border:1.5px solid #fff; border-radius:2px; pointer-events:auto; box-shadow:0 0 0 1px rgba(0,0,0,.15); }
    .stdq-img-handle.h-tl { top:-5px; left:-5px; cursor:nwse-resize; }
    .stdq-img-handle.h-tm { top:-5px; left:50%; margin-left:-5px; cursor:ns-resize; }
    .stdq-img-handle.h-tr { top:-5px; right:-5px; cursor:nesw-resize; }
    .stdq-img-handle.h-ml { top:50%; margin-top:-5px; left:-5px; cursor:ew-resize; }
    .stdq-img-handle.h-mr { top:50%; margin-top:-5px; right:-5px; cursor:ew-resize; }
    .stdq-img-handle.h-bl { bottom:-5px; left:-5px; cursor:nesw-resize; }
    .stdq-img-handle.h-bm { bottom:-5px; left:50%; margin-left:-5px; cursor:ns-resize; }
    .stdq-img-handle.h-br { bottom:-5px; right:-5px; cursor:nwse-resize; }
    .ql-editor img.stdq-img-selected { outline:2px solid var(--t500, #7c3aed); outline-offset:1px; }
    .ql-editor img { cursor:pointer; }
    /* 이미지 주석 버튼 (선택 시 표시) */
    .stdq-img-action-bar { position:absolute; right:-1px; top:-30px; pointer-events:auto; display:none; align-items:center; gap:5px; }
    .stdq-img-overlay.is-active .stdq-img-action-bar { display:inline-flex; }
    .stdq-img-action-btn { display:inline-flex; align-items:center; gap:5px; padding:4px 9px; background:#fff; border:1.5px solid var(--t300,#c4b5fd); color:var(--t700,#6d28d9); font-size:11.5px; font-weight:700; border-radius:7px; cursor:pointer; box-shadow:0 4px 12px rgba(0,0,0,.15); white-space:nowrap; }
    .stdq-img-action-btn:hover { background:var(--t50,#f5f3ff); }
    .stdq-img-action-btn.is-reset { color:#b45309; border-color:#fed7aa; }
    .stdq-img-action-btn.is-reset:hover { background:#fff7ed; }
    .stdq-img-action-btn.is-viewer { background:var(--t600,#7c3aed); color:#fff; border-color:rgba(255,255,255,.85); }
    .stdq-img-action-btn.is-viewer:hover { background:var(--t700,#6d28d9); }
</style>
<script>
(function() {
    if (window.installQuillImageResize) return;

    // Quill StyledImage blot — style/width/height/alt 보존 (SR 표준)
    function ensureStyledImage() {
        if (window.__stdqStyledImageRegistered || !window.Quill) return;
        window.__stdqStyledImageRegistered = true;
        const ImageBlot = Quill.import('formats/image');
        const PRESERVED = ['alt','height','width','style'];
        class StyledImage extends ImageBlot {
            static formats(d) {
                return PRESERVED.reduce((a,n) => { if (d.hasAttribute(n)) a[n] = d.getAttribute(n); return a; }, {});
            }
            format(name, value) {
                if (PRESERVED.indexOf(name) > -1) {
                    if (value) this.domNode.setAttribute(name, value);
                    else this.domNode.removeAttribute(name);
                } else { super.format(name, value); }
            }
        }
        Quill.register(StyledImage, true);
    }

    /**
     * Quill 인스턴스에 SR 표준 이미지 동작 설치:
     *   - 클립보드 paste → 업로드
     *   - 툴바 이미지 버튼 → 파일 선택 → 업로드
     *   - 이미지 클릭 → 8 방향 핸들로 리사이즈
     *
     * @param {Quill} quill
     * @param {Object} opts
     *   - uploadUrl (string) : 이미지 업로드 엔드포인트
     *   - csrfToken (string) : CSRF 토큰
     */
    window.installQuillImageResize = function(quill, opts = {}) {
        ensureStyledImage();
        const uploadUrl = opts.uploadUrl || '';
        const csrf      = opts.csrfToken || (document.querySelector('meta[name=csrf-token]')?.content || '');

        function uploadImage(file) {
            if (!file || !uploadUrl) return;
            const fd = new FormData(); fd.append('image', file);
            fetch(uploadUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                credentials: 'same-origin', body: fd,
            }).then(r => r.ok ? r.json() : Promise.reject(r.status))
              .then(d => {
                  if (!d.url) return;
                  const range = quill.getSelection(true) || { index: quill.getLength() };
                  quill.insertEmbed(range.index, 'image', d.url);
                  quill.setSelection(range.index + 1);
              }).catch(err => alert('이미지 업로드에 실패했습니다. (' + err + ')'));
        }

        // 툴바 이미지 버튼 — 파일 picker
        try {
            quill.getModule('toolbar').addHandler('image', () => {
                const inp = document.createElement('input');
                inp.type = 'file'; inp.accept = 'image/*';
                inp.onchange = () => { if (inp.files[0]) uploadImage(inp.files[0]); };
                inp.click();
            });
        } catch (e) { /* toolbar 없음 */ }

        // 클립보드 paste
        quill.root.addEventListener('paste', e => {
            const it = [...(e.clipboardData?.items || [])].find(x => x.type.startsWith('image/'));
            if (!it) return;
            e.preventDefault();
            uploadImage(it.getAsFile());
        });

        // ── 리사이즈 오버레이 (인스턴스마다 생성) ──
        const overlay = document.createElement('div');
        overlay.className = 'stdq-img-overlay';
        ['tl','tm','tr','ml','mr','bl','bm','br'].forEach(dir => {
            const h = document.createElement('span');
            h.className = 'stdq-img-handle h-' + dir;
            h.dataset.dir = dir;
            overlay.appendChild(h);
        });
        // 이미지 주석/초기화/뷰어 액션바 (사용처에서 옵션으로 활성화)
        let actionBar = null, resetBtn = null;
        const wantAnnotate = opts.enableAnnotate && typeof window.openMailImageAnnotator === 'function';
        const wantViewer   = typeof opts.onViewerOpen === 'function';
        if (wantAnnotate || wantViewer) {
            actionBar = document.createElement('div');
            actionBar.className = 'stdq-img-action-bar';

            if (wantViewer) {
                const viewerBtn = document.createElement('button');
                viewerBtn.type = 'button';
                viewerBtn.className = 'stdq-img-action-btn is-viewer';
                viewerBtn.innerHTML = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 3h6m0 0v6m0-6L14 10M9 21H3m0 0v-6m0 6l7-7"/></svg>이미지 뷰어';
                viewerBtn.addEventListener('click', e => {
                    e.preventDefault(); e.stopPropagation();
                    if (!selectedImg) return;
                    try { opts.onViewerOpen(selectedImg); } catch (_) {}
                });
                viewerBtn.addEventListener('mousedown', e => e.stopPropagation());
                actionBar.appendChild(viewerBtn);
            }
        }
        if (wantAnnotate) {
            const annotateBtn = document.createElement('button');
            annotateBtn.type = 'button';
            annotateBtn.className = 'stdq-img-action-btn';
            annotateBtn.innerHTML = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>이미지 주석';
            annotateBtn.addEventListener('click', e => {
                e.preventDefault(); e.stopPropagation();
                if (!selectedImg) return;
                // 원본 보관 — 첫 이미지 주석 시점에 현재 src 를 data-original-src 로 보관 (이미 있으면 보존)
                if (!selectedImg.dataset.originalSrc) selectedImg.dataset.originalSrc = selectedImg.src;
                const target = selectedImg;
                // 주석 모달 위로 리사이즈 핸들 오버레이가 비치지 않도록 선택 해제
                selectImage(null);
                window.openMailImageAnnotator(quill, target, { uploadUrl, csrfToken: csrf });
            });
            annotateBtn.addEventListener('mousedown', e => e.stopPropagation());

            resetBtn = document.createElement('button');
            resetBtn.type = 'button';
            resetBtn.className = 'stdq-img-action-btn is-reset';
            resetBtn.innerHTML = '<svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>원본 복원';
            resetBtn.addEventListener('click', async (e) => {
                e.preventDefault(); e.stopPropagation();
                if (!selectedImg) return;
                const orig = selectedImg.dataset.originalSrc;
                if (!orig || orig === selectedImg.src) return;
                const ask = window.__confirm || ((m) => Promise.resolve(confirm(m)));
                if (!(await ask('이미지 주석을 모두 지우고 원본 이미지로 돌릴까요?'))) return;
                const img = selectedImg;
                // 1) 크기 강제 초기화: attribute + inline style 둘 다 (StyledImage blot 이 style 보존하므로 직접 클리어 필요)
                img.removeAttribute('width');
                img.removeAttribute('height');
                img.style.width = '';
                img.style.height = '';
                // 2) src 교체 — 동일 캐시 회피용으로 한번 비우고 재설정
                img.src = '';
                img.src = orig;
                // 3) Quill 내부 상태 갱신 + 이미지 로드 후 overlay 재배치
                try { quill.update('user'); } catch (_) {}
                const onload = () => {
                    img.removeEventListener('load', onload);
                    // 동일 이미지 재선택 → positionOverlay + refreshActionBar 자동 호출
                    selectImage(img);
                };
                img.addEventListener('load', onload);
                // 캐시 히트로 load 이벤트가 즉시 발생 안 할 수도 있으니 fallback
                setTimeout(() => selectImage(img), 60);
            });
            resetBtn.addEventListener('mousedown', e => e.stopPropagation());

            actionBar.appendChild(annotateBtn);
            actionBar.appendChild(resetBtn);
        }
        // actionBar 가 한 가지라도 활성화되면 overlay 에 추가
        if (actionBar) overlay.appendChild(actionBar);
        document.body.appendChild(overlay);

        // 현재 선택된 이미지 상태에 맞춰 "원본 복원" 버튼 가시성 결정
        function refreshActionBar() {
            if (!resetBtn) return;
            const orig = selectedImg?.dataset?.originalSrc;
            const changed = orig && orig !== selectedImg.src;
            resetBtn.style.display = changed ? 'inline-flex' : 'none';
        }

        let selectedImg = null;
        function positionOverlay() {
            if (!selectedImg) { overlay.classList.remove('is-active'); return; }
            const r = selectedImg.getBoundingClientRect();
            overlay.style.left   = r.left + 'px';
            overlay.style.top    = r.top + 'px';
            overlay.style.width  = r.width + 'px';
            overlay.style.height = r.height + 'px';
            overlay.classList.add('is-active');
        }
        function selectImage(img) {
            if (selectedImg) selectedImg.classList.remove('stdq-img-selected');
            selectedImg = img;
            if (img) { img.classList.add('stdq-img-selected'); positionOverlay(); refreshActionBar(); }
            else overlay.classList.remove('is-active');
        }

        // 이미지 클릭 → 선택, 그 외 클릭 → 해제
        quill.root.addEventListener('click', e => {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
                e.stopPropagation();   // 모달/팝오버의 outside-click 핸들러로 새지 않도록
                selectImage(e.target);
            } else if (!overlay.contains(e.target)) {
                selectImage(null);
            }
        });
        document.addEventListener('click', e => {
            if (!selectedImg) return;
            if (e.target === selectedImg || overlay.contains(e.target)) return;
            if (quill.root.contains(e.target)) selectImage(null);
        }, true);
        window.addEventListener('resize', positionOverlay);
        quill.root.addEventListener('scroll', positionOverlay);
        window.addEventListener('scroll', positionOverlay, true);
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && selectedImg) selectImage(null);
        });

        // ── 8 방향 핸들 드래그 ──
        let resizeState = null;
        overlay.querySelectorAll('.stdq-img-handle').forEach(h => {
            // 핸들 위에서 발생하는 mousedown/click 이 모달/팝오버 outside-click 으로 새지 않도록
            h.addEventListener('click',     e => { e.stopPropagation(); });
            h.addEventListener('mousedown', e => {
                if (!selectedImg) return;
                e.preventDefault();
                e.stopPropagation();
                const rect = selectedImg.getBoundingClientRect();
                resizeState = {
                    dir: h.dataset.dir,
                    startX: e.clientX, startY: e.clientY,
                    origW: rect.width, origH: rect.height,
                    aspect: rect.width / rect.height,
                };
                document.body.style.userSelect = 'none';
                document.addEventListener('mousemove', onResizeMove);
                document.addEventListener('mouseup',   onResizeEnd, { once: true });
            });
        });
        function onResizeMove(e) {
            if (!resizeState || !selectedImg) return;
            const dx = e.clientX - resizeState.startX;
            const dy = e.clientY - resizeState.startY;
            const dir = resizeState.dir;
            let nw = resizeState.origW, nh = resizeState.origH;
            if (dir.includes('r')) nw = Math.max(40, resizeState.origW + dx);
            if (dir.includes('l')) nw = Math.max(40, resizeState.origW - dx);
            if (dir.includes('b')) nh = Math.max(40, resizeState.origH + dy);
            if (dir.includes('t')) nh = Math.max(40, resizeState.origH - dy);
            const isCorner = dir.length === 2;
            if (isCorner) {
                const wRatio = nw / resizeState.origW, hRatio = nh / resizeState.origH;
                const ratio = Math.abs(wRatio - 1) > Math.abs(hRatio - 1) ? wRatio : hRatio;
                nw = Math.max(40, resizeState.origW * ratio);
                nh = nw / resizeState.aspect;
            }
            selectedImg.style.width  = Math.round(nw) + 'px';
            selectedImg.style.height = isCorner ? '' : Math.round(nh) + 'px';
            positionOverlay();
        }
        function onResizeEnd() {
            document.body.style.userSelect = '';
            document.removeEventListener('mousemove', onResizeMove);
            resizeState = null;
            // 드래그 직후 mouseup 위치에서 발생할 수 있는 click 이벤트가
            // 모달/팝오버의 outside-click 핸들러를 발동시키지 않도록 capture 단계에서 한 번 차단.
            const swallow = (ev) => { ev.stopPropagation(); ev.stopImmediatePropagation(); };
            document.addEventListener('click', swallow, { capture: true, once: true });
            // click 이벤트가 발생하지 않으면 다음 macrotask 에서 핸들러 정리
            setTimeout(() => document.removeEventListener('click', swallow, { capture: true }), 0);
        }

        return { destroy: () => { overlay.remove(); }, deselect: () => selectImage(null) };
    };
})();
</script>
@endonce
