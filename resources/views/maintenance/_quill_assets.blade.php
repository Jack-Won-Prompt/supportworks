{{-- Quill CDN (한 페이지에서 한 번만 include) --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<style>
.sr-editor-wrap .ql-container { font-size:14px; font-family:inherit; border-radius:0 0 9px 9px; border-color:#e4e4e7; min-height:140px; }
.sr-editor-wrap .ql-toolbar { border-radius:9px 9px 0 0; border-color:#e4e4e7; background:#fafafa; }
.sr-editor-wrap.focused .ql-container,
.sr-editor-wrap.focused .ql-toolbar { border-color:#7c3aed; }
.sr-editor-wrap .ql-editor { min-height:130px; line-height:1.75; color:#374151; }
.sr-editor-wrap .ql-editor.ql-blank::before { color:#9ca3af; font-style:normal; }
.sr-editor-wrap .ql-editor img { max-width:100%; border-radius:6px; margin:4px 0; }
/* 답글용 작은 에디터 */
.sr-reply-editor-wrap .ql-container { font-size:13px; font-family:inherit; border-radius:0 0 9px 9px; border-color:#e4e4e7; }
.sr-reply-editor-wrap .ql-toolbar { border-radius:9px 9px 0 0; border-color:#e4e4e7; background:#fafafa; }
.sr-reply-editor-wrap.focused .ql-container,
.sr-reply-editor-wrap.focused .ql-toolbar { border-color:#7c3aed; }
.sr-reply-editor-wrap .ql-editor { min-height:80px; line-height:1.65; color:#374151; }
.sr-reply-editor-wrap .ql-editor.ql-blank::before { color:#9ca3af; font-style:normal; }
.sr-reply-editor-wrap .ql-editor img { max-width:100%; border-radius:6px; }
/* 콘텐츠 표시 영역 */
.sr-content-view { font-size:14px; color:#374151; line-height:1.8; word-break:break-word; }
.sr-content-view img { max-width:100%; border-radius:8px; margin:6px 0; }
.sr-content-view p { margin:0 0 6px; }
.sr-content-view ul, .sr-content-view ol { padding-left:20px; margin:4px 0; }
.sr-reply-content { font-size:13px; color:#374151; line-height:1.65; word-break:break-word; }
.sr-reply-content img { max-width:100%; border-radius:6px; margin:4px 0; }
.sr-reply-content p { margin:0 0 4px; }
</style>
<script>
const Delta = Quill.import('delta');

/* 공통 이미지 업로드 함수 */
async function srUploadImage(file, csrf) {
    const fd = new FormData();
    fd.append('image', file);
    const res = await fetch('{{ route('upload.sr-image') }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf },
        body: fd,
    });
    if (!res.ok) return null;
    const data = await res.json();
    return data.url || null;
}

/* Quill 인스턴스 생성 헬퍼 */
function createSrEditor(containerId, hiddenInputId, placeholder, isSmall, csrf) {
    const wrap = document.getElementById(containerId).parentElement;
    const toolbarOptions = isSmall
        ? [['bold','italic','underline'], [{'list':'bullet'}], ['image']]
        : [['bold','italic','underline','strike'], [{'header':[1,2,3,false]}],
           [{'list':'ordered'},{'list':'bullet'}], ['blockquote','code-block'],
           ['link','image'], ['clean']];

    const quill = new Quill('#' + containerId, {
        theme: 'snow',
        placeholder: placeholder,
        modules: {
            toolbar: {
                container: toolbarOptions,
                handlers: {
                    image: function() {
                        const input = document.createElement('input');
                        input.type = 'file'; input.accept = 'image/*';
                        input.onchange = async () => {
                            const url = await srUploadImage(input.files[0], csrf);
                            if (url) {
                                const range = quill.getSelection(true);
                                quill.insertEmbed(range.index, 'image', url, 'user');
                                quill.setSelection(range.index + 1);
                            }
                        };
                        input.click();
                    }
                }
            },
            clipboard: {
                /* base64 이미지 매처 — Quill이 붙여넣기 시 자동 삽입하는 base64 차단 */
                matchers: [
                    ['img', function(node, delta) {
                        if (node.src && node.src.startsWith('data:')) {
                            return new Delta(); // base64 이미지 제거
                        }
                        return delta;
                    }]
                ]
            }
        }
    });

    /* 클래스 on focus */
    quill.on('selection-change', function(range) {
        const w = quill.root.closest('.sr-editor-wrap, .sr-reply-editor-wrap');
        if (w) w.classList.toggle('focused', !!range);
    });

    /* 붙여넣기 이미지 업로드 — capture phase로 Quill보다 먼저 실행 */
    quill.root.addEventListener('paste', function(e) {
        const clipData = e.clipboardData || e.originalEvent?.clipboardData;
        if (!clipData) return;
        const imageItem = Array.from(clipData.items).find(
            item => item.kind === 'file' && item.type.startsWith('image/')
        );
        if (!imageItem) return;

        e.preventDefault();
        e.stopImmediatePropagation();

        const file = imageItem.getAsFile();
        srUploadImage(file, csrf).then(url => {
            if (url) {
                const range = quill.getSelection(true);
                const idx = range ? range.index : quill.getLength();
                quill.insertEmbed(idx, 'image', url, 'user');
                quill.setSelection(idx + 1);
            }
        });
    }, true); /* true = capture phase */

    /* 제출 전 hidden input 동기화 + base64 이미지 후처리 차단 */
    const hidden = document.getElementById(hiddenInputId);
    if (hidden) {
        quill.on('text-change', function(delta, oldDelta, source) {
            if (source !== 'silent') {
                const contents = quill.getContents();
                const hasBase64 = contents.ops.some(op =>
                    op.insert && op.insert.image && String(op.insert.image).startsWith('data:')
                );
                if (hasBase64) {
                    const cleaned = contents.ops.filter(op =>
                        !(op.insert && op.insert.image && String(op.insert.image).startsWith('data:'))
                    );
                    quill.setContents({ ops: cleaned }, 'silent');
                }
            }
            hidden.value = quill.root.innerHTML;
        });
    }

    return quill;
}
</script>
