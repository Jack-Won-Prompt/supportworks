{{-- 정제 결과 카드 (JS로 동적 표시) --}}
<div id="result-area" style="display:none;">
    <div style="background:#fff;border:1.5px solid #a78bfa;border-radius:14px;padding:20px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px;flex-wrap:wrap;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:8px;height:8px;border-radius:50%;background:#16a34a;flex-shrink:0;"></div>
                <span style="font-size:13.5px;font-weight:700;color:#3f3f46;">정제 완료</span>
                <span id="result-task-type" style="font-size:11px;padding:2px 8px;border-radius:5px;background:#f5f3ff;color:#7c3aed;font-weight:600;"></span>
                <span id="result-context-strength" style="display:none;font-size:11px;padding:2px 8px;border-radius:5px;font-weight:600;"></span>
            </div>
            <div style="display:flex;gap:8px;align-items:center;">
                <span id="result-tokens" style="font-size:11px;color:#a1a1aa;"></span>
                <button id="copy-btn" onclick="promptRefiner.copyResult()"
                    style="display:flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px solid #ddd6fe;border-radius:8px;font-size:12px;font-weight:600;color:#7c3aed;background:#f5f3ff;cursor:pointer;transition:all .13s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                    <span id="copy-btn-text">복사</span>
                </button>
            </div>
        </div>

        {{-- 정제된 프롬프트 --}}
        <pre id="result-prompt"
            style="background:#faf8ff;border:1px solid #ede9fe;border-radius:10px;padding:16px;font-size:13px;line-height:1.7;color:#1e1b2e;white-space:pre-wrap;word-break:break-word;max-height:70vh;overflow-y:auto;margin:0;font-family:'Figtree',monospace;"></pre>

        {{-- 메타데이터 (가정 사항) --}}
        <div id="assumptions-area" style="display:none;margin-top:12px;">
            <details style="cursor:pointer;">
                <summary style="font-size:12px;color:#a1a1aa;font-weight:600;user-select:none;">가정 사항 보기</summary>
                <ul id="assumptions-list" style="margin:8px 0 0 0;padding-left:18px;font-size:12px;color:#71717a;line-height:1.8;"></ul>
            </details>
        </div>

        {{-- 이력 저장 안내 + 폴백 표시 --}}
        <div style="margin-top:12px;font-size:11.5px;color:#a1a1aa;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <span>
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" style="vertical-align:middle;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                이력에 자동 저장되었습니다.
            </span>
            <span id="result-fallback-badge" style="display:none;font-size:11px;color:#a16207;background:#fef9c3;border:1px solid #fde68a;padding:2px 8px;border-radius:5px;font-weight:600;"
                title="기본 웍스가 일시적 응답 지연으로 보조 웍스가 처리했습니다">
                ⚡ 보조 웍스 처리됨
            </span>
        </div>
    </div>

    {{-- 다시 시작 --}}
    <div style="margin-top:12px;text-align:center;">
        <button onclick="promptRefiner.reset()"
            style="padding:8px 20px;border:1.5px solid #ddd6fe;border-radius:8px;font-size:13px;color:#7c3aed;background:#fff;cursor:pointer;font-weight:600;transition:background .13s;"
            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
            새 프롬프트 정제하기
        </button>
    </div>
</div>
