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
    .stdq-img-overlay { position:fixed; pointer-events:none; z-index:50; display:none; }
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
        document.body.appendChild(overlay);

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
            if (img) { img.classList.add('stdq-img-selected'); positionOverlay(); }
            else overlay.classList.remove('is-active');
        }

        // 이미지 클릭 → 선택, 그 외 클릭 → 해제
        quill.root.addEventListener('click', e => {
            if (e.target.tagName === 'IMG') { e.preventDefault(); selectImage(e.target); }
            else if (!overlay.contains(e.target)) selectImage(null);
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
            h.addEventListener('mousedown', e => {
                if (!selectedImg) return;
                e.preventDefault();
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
        }

        return { destroy: () => { overlay.remove(); }, deselect: () => selectImage(null) };
    };
})();
</script>
@endonce
