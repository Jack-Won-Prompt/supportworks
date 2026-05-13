{{-- 명확화 질문 카드 (Alpine.js x-if로 동적 추가) --}}
<div id="clarification-area" style="display:none;">
    <div style="background:#fff;border:1.5px solid #ddd6fe;border-radius:14px;padding:20px;margin-top:16px;">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:16px;">
            <div style="width:8px;height:8px;border-radius:50%;background:#7c3aed;flex-shrink:0;"></div>
            <span style="font-size:13.5px;font-weight:700;color:#3f3f46;">추가 정보가 필요합니다</span>
            <span id="clarif-round-label" style="font-size:11px;color:#a1a1aa;margin-left:4px;"></span>
        </div>
        <div id="questions-container" style="display:flex;flex-direction:column;gap:14px;"></div>
        <div style="margin-top:18px;">
            <button id="submit-answers-btn" onclick="promptRefiner.submitAnswers()"
                style="display:flex;align-items:center;gap:7px;padding:9px 18px;background:linear-gradient(135deg,var(--t600),var(--t700));color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:600;cursor:pointer;transition:opacity .15s;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                답변 제출하기
            </button>
        </div>
    </div>
</div>
