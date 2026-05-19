{{-- 회의록 웍스 정제 — 미리보기 모달 + JS (작성/수정 페이지·index 빠른 등록 모달 공용) --}}
<style>
.mm-refine-btn {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 9px; background:linear-gradient(135deg,#7c3aed,#9b8afb);
    color:#fff; border:none; border-radius:6px;
    font-size:11px; font-weight:700; cursor:pointer; flex-shrink:0;
    transition:opacity .15s;
}
.mm-refine-btn:hover    { opacity:.88; }
.mm-refine-btn:disabled { opacity:.55; cursor:default; }
.mm-field-head { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-bottom:5px; }
</style>

<div id="mm-refine-modal" style="display:none;position:fixed;inset:0;z-index:11000;background:rgba(15,12,30,.55);backdrop-filter:blur(3px);align-items:center;justify-content:center;padding:24px;" onclick="if(event.target===this)mmCloseRefine()">
    <div style="background:#fff;width:560px;max-width:calc(100vw - 48px);max-height:85vh;border-radius:14px;box-shadow:0 20px 60px rgba(0,0,0,.3);overflow:hidden;display:flex;flex-direction:column;">
        <div style="padding:15px 20px;border-bottom:1px solid #f3f4f6;display:flex;align-items:center;justify-content:space-between;gap:10px;">
            <span style="font-size:14px;font-weight:700;color:#1e1b2e;display:inline-flex;align-items:center;gap:6px;">
                <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                웍스 정제 미리보기
            </span>
            <button onclick="mmCloseRefine()" type="button" style="background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:2px 4px;">&times;</button>
        </div>
        <div style="padding:16px 20px;overflow-y:auto;">
            <p style="margin:0 0 6px;font-size:11px;font-weight:700;color:#6b7280;letter-spacing:.04em;">원문</p>
            <div id="mm-refine-orig" style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:9px;padding:11px 13px;font-size:13px;color:#374151;line-height:1.65;white-space:pre-wrap;word-break:break-word;max-height:180px;overflow-y:auto;"></div>
            <p style="margin:14px 0 6px;font-size:11px;font-weight:700;color:#7c3aed;letter-spacing:.04em;">정제 결과</p>
            <div id="mm-refine-result" style="background:#faf5ff;border:1.5px solid #ddd6fe;border-radius:9px;padding:11px 13px;font-size:13px;color:#1f2937;line-height:1.65;white-space:pre-wrap;word-break:break-word;max-height:280px;overflow-y:auto;"></div>
        </div>
        <div style="padding:12px 20px;border-top:1px solid #f3f4f6;display:flex;justify-content:flex-end;gap:8px;">
            <button onclick="mmCloseRefine()" type="button" style="padding:8px 16px;background:#f3f4f6;color:#374151;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">취소</button>
            <button id="mm-refine-apply" type="button" style="padding:8px 18px;background:linear-gradient(135deg,#7c3aed,#9b8afb);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;">적용</button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window._mmRefineInit) return;   // 중복 include 가드
    window._mmRefineInit = true;

    const MM_REFINE_URL = @json(route('meeting-minutes.refine'));
    const MM_CSRF       = @json(csrf_token());

    window.mmCloseRefine = function () {
        const m = document.getElementById('mm-refine-modal');
        if (m) m.style.display = 'none';
    };

    /* 공통 정제 실행 — 원문/적용 콜백만 다르게 */
    function mmRunRefine(original, field, btn, applyFn) {
        original = (original || '').trim();
        if (!original) { alert('정제할 내용을 입력하세요.'); return; }

        const origHtml = btn.innerHTML;
        btn.disabled = true;
        btn.textContent = '정제 중…';

        fetch(MM_REFINE_URL, {
            method:  'POST',
            headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': MM_CSRF },
            body:    JSON.stringify({ content: original, field: field }),
        })
        .then(r => r.json())
        .then(d => {
            if (!d.ok) throw new Error(d.message || '정제 실패');
            document.getElementById('mm-refine-orig').textContent   = original;
            document.getElementById('mm-refine-result').textContent = d.refined;
            document.getElementById('mm-refine-apply').onclick = function () {
                applyFn(d.refined);
                mmCloseRefine();
            };
            document.getElementById('mm-refine-modal').style.display = 'flex';
        })
        .catch(e => alert('웍스 정제 실패: ' + e.message))
        .finally(() => { btn.disabled = false; btn.innerHTML = origHtml; });
    }

    /* textarea 정제 */
    window.mmRefine = function (textareaId, field, btn) {
        const ta = document.getElementById(textareaId);
        if (!ta) return;
        mmRunRefine(ta.value, field, btn, function (txt) {
            ta.value = txt;
            ta.dispatchEvent(new Event('input'));
        });
    };

    /* Quill 에디터 정제 */
    window.mmRefineQuill = function (quill, field, btn) {
        if (!quill) { alert('정제할 내용을 입력하세요.'); return; }
        mmRunRefine(quill.getText(), field, btn, function (txt) {
            quill.setText(txt);
        });
    };
})();
</script>
