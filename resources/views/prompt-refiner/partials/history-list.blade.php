{{-- 이력 목록 (JS로 동적 렌더링) --}}
<div style="margin-top:32px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
        <h3 style="font-size:14px;font-weight:700;color:#3f3f46;margin:0;">내 정제 이력</h3>
        <div style="display:flex;gap:8px;align-items:center;">
            <select id="history-mode-filter" onchange="promptRefiner.loadHistory(true)"
                style="font-size:12px;border:1px solid #ddd6fe;border-radius:7px;padding:4px 10px;color:#3f3f46;background:#faf8ff;cursor:pointer;outline:none;">
                <option value="all">전체</option>
                <option value="general">일반</option>
                <option value="project">프로젝트</option>
            </select>
        </div>
    </div>

    <div id="history-list" style="display:flex;flex-direction:column;gap:8px;"></div>

    <div id="history-empty" style="display:none;padding:24px;text-align:center;color:#a1a1aa;font-size:13px;">
        아직 정제 이력이 없습니다.
    </div>

    <div id="history-loading" style="display:none;padding:16px;text-align:center;">
        <div style="display:inline-block;width:20px;height:20px;border:2px solid #ddd6fe;border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite;"></div>
    </div>

    <div id="history-more-btn" style="display:none;margin-top:12px;text-align:center;">
        <button onclick="promptRefiner.loadHistory(false)"
            style="padding:7px 20px;border:1.5px solid #ddd6fe;border-radius:8px;font-size:12.5px;color:#7c3aed;background:#fff;cursor:pointer;font-weight:600;transition:background .13s;"
            onmouseover="this.style.background='#f5f3ff'" onmouseout="this.style.background='#fff'">
            더 보기
        </button>
    </div>
</div>

{{-- 이력 상세 모달 --}}
<div id="history-modal" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.35);align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;padding:24px;max-width:680px;width:92vw;max-height:85vh;display:flex;flex-direction:column;box-shadow:0 16px 48px rgba(109,92,231,.18);">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
            <h4 style="font-size:15px;font-weight:700;color:#1e1b2e;margin:0;">이력 상세</h4>
            <button onclick="promptRefiner.closeHistoryModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:20px;line-height:1;padding:4px;">×</button>
        </div>
        <div style="overflow-y:auto;flex:1;">
            <div id="modal-meta" style="font-size:12px;color:#a1a1aa;margin-bottom:12px;"></div>
            <p style="font-size:12px;font-weight:600;color:#6d5ce7;margin:0 0 6px;">원본 입력</p>
            <p id="modal-input" style="font-size:13px;color:#3f3f46;background:#faf8ff;border-radius:8px;padding:10px 12px;margin:0 0 14px;white-space:pre-wrap;"></p>
            <p style="font-size:12px;font-weight:600;color:#6d5ce7;margin:0 0 6px;">정제된 프롬프트</p>
            <pre id="modal-prompt" style="font-size:12.5px;color:#1e1b2e;background:#faf8ff;border:1px solid #ede9fe;border-radius:8px;padding:12px;white-space:pre-wrap;word-break:break-word;margin:0;max-height:320px;overflow-y:auto;font-family:'Figtree',monospace;"></pre>
        </div>
        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid #f0eeff;">
            <button id="modal-delete-btn" onclick="promptRefiner.deleteHistory()"
                style="padding:7px 16px;border:1.5px solid #fecaca;border-radius:8px;font-size:12.5px;color:#dc2626;background:#fff5f5;cursor:pointer;font-weight:600;transition:background .13s;"
                onmouseover="this.style.background='#fee2e2'" onmouseout="this.style.background='#fff5f5'">
                삭제
            </button>
            <button id="modal-copy-btn" onclick="promptRefiner.copyModalPrompt()"
                style="padding:7px 16px;border:1.5px solid #ddd6fe;border-radius:8px;font-size:12.5px;color:#7c3aed;background:#f5f3ff;cursor:pointer;font-weight:600;transition:background .13s;"
                onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                복사
            </button>
        </div>
    </div>
</div>
