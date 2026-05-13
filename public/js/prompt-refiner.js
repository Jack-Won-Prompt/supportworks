/**
 * Prompt Refiner — Vanilla JS (Axios + no framework dependency)
 * 전역 객체 window.promptRefiner 으로 노출
 */
(function () {
    'use strict';

    const ROUTES = window.promptRefinerRoutes || {};

    const state = {
        mode:             'general',
        sessionId:        null,
        scheduleId:       null,
        currentHistoryId: null,
        historyCursor:    null,
        historyLoading:   false,
        taskLoading:      false,
    };

    /* ── helpers ── */
    function el(id) { return document.getElementById(id); }

    function setLoading(on) {
        el('loading-area').style.display     = on ? 'block' : 'none';
        el('input-card').style.opacity       = on ? '.5' : '1';
        el('refine-btn').disabled            = on;
        el('refine-btn-text').textContent    = on ? '분석 중...' : '정제하기';
    }

    function showError(msg) {
        const box = el('error-msg');
        box.textContent = msg;
        box.style.display = 'block';
    }

    function clearError() {
        el('error-msg').style.display = 'none';
    }

    function taskTypeLabel(type) {
        const map = {
            code_generation: '코드 생성',
            code_review:     '코드 리뷰',
            debugging:       '디버깅',
            architecture:    '아키텍처',
            testing:         '테스팅',
            documentation:   '문서화',
            explanation:     '설명',
            refactoring:     '리팩터링',
        };
        return map[type] || type;
    }

    /* ── 모드 전환 ── */
    function setMode(mode) {
        state.mode = mode;
        el('mode-btn-general').classList.toggle('active', mode === 'general');
        el('mode-btn-project').classList.toggle('active', mode === 'project');
        el('project-select-area').style.display = mode === 'project' ? 'block' : 'none';

        if (mode === 'general') {
            el('task-select-area').style.display = 'none';
            state.scheduleId = null;
            _setContextStrengthIndicator('none');
        }
    }

    /* ── 글자 수 ── */
    function updateCharCount() {
        const len = (el('user-input').value || '').length;
        el('char-count').textContent = `${len.toLocaleString()} / 5,000`;
    }

    /* ── 프로젝트 변경 시 Task 목록 로드 ── */
    function onProjectChange() {
        const projectId = el('project-select').value;
        state.scheduleId = null;

        const taskArea = el('task-select-area');
        const taskSelect = el('task-select');

        // 프로젝트 미선택 시 Task 영역 숨김
        if (!projectId) {
            taskArea.style.display = 'none';
            _setContextStrengthIndicator('none');
            return;
        }

        // Task 영역 표시 + 기본 옵션으로 리셋
        taskArea.style.display = 'block';
        taskSelect.innerHTML = '<option value="">전체 (Task 미선택)</option>';
        _setContextStrengthIndicator('project');

        loadTasksForProject(projectId);
    }

    /* ── Task 선택 변경 ── */
    function onTaskChange() {
        const taskVal = el('task-select').value;
        state.scheduleId = taskVal ? parseInt(taskVal, 10) : null;

        _setContextStrengthIndicator(state.scheduleId ? 'task' : 'project');
    }

    /* ── Task 목록 로드 ── */
    async function loadTasksForProject(projectId) {
        if (state.taskLoading) return;
        state.taskLoading = true;

        const indicator = el('task-loading-indicator');
        if (indicator) indicator.style.display = 'block';

        try {
            const res = await axios.get(`${ROUTES.projectTasksBase}/${projectId}/tasks`);
            const tasks = res.data.items || [];
            const taskSelect = el('task-select');

            // 기존 옵션 초기화 후 기본 항목 추가
            taskSelect.innerHTML = '<option value="">전체 (Task 미선택)</option>';
            tasks.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.schedule_id;
                opt.textContent = t.refinement_count > 0
                    ? `${t.title} (${t.refinement_count}회 정제됨)`
                    : t.title;
                taskSelect.appendChild(opt);
            });
        } catch (err) {
            console.error('[PromptRefiner] Task 목록 로드 실패:', err?.response?.status, err?.message);
        } finally {
            state.taskLoading = false;
            const indicator = el('task-loading-indicator');
            if (indicator) indicator.style.display = 'none';
        }
    }

    /* ── 컨텍스트 강도 표시 ── */
    function _setContextStrengthIndicator(strength) {
        const area  = el('context-strength-area');
        const dot   = el('context-dot');
        const label = el('context-strength-label');
        if (!area || !dot || !label) return;

        if (strength === 'none') {
            area.style.display = 'none';
            return;
        }

        const config = {
            task:    { color: '#16a34a', text: 'Task 컨텍스트 활성화 (강한 참고)' },
            project: { color: '#ca8a04', text: '프로젝트 컨텍스트 활성화 (중간 참고)' },
        };

        const c = config[strength];
        dot.style.background = c.color;
        label.textContent = c.text;
        area.style.display = 'flex';
    }

    /* ── 정제 요청 ── */
    async function submit() {
        clearError();
        const input = (el('user-input').value || '').trim();
        if (!input) { showError('요청 내용을 입력해주세요.'); return; }
        if (input.length > 5000) { showError('입력 내용은 5000자를 초과할 수 없습니다.'); return; }
        if (state.mode === 'project' && !el('project-select').value) {
            showError('프로젝트를 선택해주세요.');
            return;
        }

        setLoading(true);
        hideClarification();
        hideResult();

        const payload = {
            session_id: state.sessionId,
            user_input: input,
            mode:       state.mode,
            project_id: state.mode === 'project' ? (el('project-select').value || null) : null,
            schedule_id: state.mode === 'project' ? (state.scheduleId || null) : null,
            clarification_answers: [],
        };

        try {
            const res = await axios.post(ROUTES.refine, payload, {
                headers: {
                    'X-CSRF-TOKEN': ROUTES.csrfToken,
                    'Content-Type': 'application/json',
                },
            });
            handleResponse(res.data);
        } catch (err) {
            handleError(err);
        } finally {
            setLoading(false);
        }
    }

    /* ── 명확화 답변 제출 ── */
    async function submitAnswers() {
        clearError();
        const answers = collectAnswers();
        if (!answers) return;

        setLoading(true);
        hideClarification();
        hideResult();

        const payload = {
            session_id:            state.sessionId,
            user_input:            el('user-input').value.trim(),
            mode:                  state.mode,
            project_id:            state.mode === 'project' ? (el('project-select').value || null) : null,
            schedule_id:           state.mode === 'project' ? (state.scheduleId || null) : null,
            clarification_answers: answers,
        };

        try {
            const res = await axios.post(ROUTES.refine, payload, {
                headers: {
                    'X-CSRF-TOKEN': ROUTES.csrfToken,
                    'Content-Type': 'application/json',
                },
            });
            handleResponse(res.data);
        } catch (err) {
            handleError(err);
        } finally {
            setLoading(false);
        }
    }

    function collectAnswers() {
        const container = el('questions-container');
        const inputs = container.querySelectorAll('[data-answer-input]');
        const answers = [];
        let valid = true;
        inputs.forEach(inp => {
            const val = inp.value.trim();
            if (!val) {
                inp.style.borderColor = '#f87171';
                valid = false;
            } else {
                inp.style.borderColor = '#ddd6fe';
            }
            answers.push({ question_id: inp.dataset.questionId, answer: val });
        });
        if (!valid) {
            showError('모든 질문에 답변해주세요.');
            return null;
        }
        return answers;
    }

    /* ── 응답 분기 ── */
    function handleResponse(data) {
        state.sessionId = data.session_id;

        if (data.status === 'needs_clarification') {
            showClarification(data);
        } else if (data.status === 'refined') {
            showResult(data);
            loadHistory(true);
        }
    }

    function handleError(err) {
        const msg = err.response?.data?.message
            || err.response?.data?.error
            || 'AI 호출 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
        showError(msg);
    }

    /* ── 명확화 질문 UI ── */
    function showClarification(data) {
        const area = el('clarification-area');
        const container = el('questions-container');
        container.innerHTML = '';

        el('clarif-round-label').textContent = `라운드 ${data.round}`;

        data.questions.forEach(q => {
            const div = document.createElement('div');
            div.className = 'pr-fade';
            div.style.cssText = 'background:#faf8ff;border:1px solid #ede9fe;border-radius:10px;padding:14px;';

            div.innerHTML = `
                <p style="font-size:13.5px;font-weight:600;color:#1e1b2e;margin:0 0 4px;">${escapeHtml(q.question)}</p>
                <p style="font-size:12px;color:#a1a1aa;margin:0 0 10px;">${escapeHtml(q.why_asking)}</p>
                <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:10px;">
                    ${(q.suggested_answers || []).map(s =>
                        `<button class="q-suggestion-btn" onclick="promptRefiner._fillAnswer('${escapeAttr(q.id)}', '${escapeAttr(s)}')">${escapeHtml(s)}</button>`
                    ).join('')}
                </div>
                <textarea
                    data-answer-input
                    data-question-id="${escapeAttr(q.id)}"
                    rows="2"
                    placeholder="직접 답변을 입력하거나 위 버튼을 클릭하세요."
                    style="width:100%;border:1.5px solid #ddd6fe;border-radius:8px;padding:8px 10px;font-size:13px;color:#1e1b2e;background:#fff;resize:vertical;outline:none;transition:border-color .13s;font-family:inherit;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#ddd6fe'"></textarea>
            `;
            container.appendChild(div);
        });

        area.style.display = 'block';
        area.classList.add('pr-fade');
    }

    function hideClarification() {
        el('clarification-area').style.display = 'none';
    }

    function _fillAnswer(questionId, value) {
        const inp = document.querySelector(`[data-question-id="${questionId}"]`);
        if (inp) inp.value = value;
    }

    /* ── 결과 UI ── */
    function showResult(data) {
        el('result-task-type').textContent = taskTypeLabel(data.task_type);
        el('result-prompt').textContent    = data.refined_prompt || '';

        const tokens = data.metadata?.estimated_tokens;
        el('result-tokens').textContent = tokens ? `약 ${tokens.toLocaleString()} 토큰` : '';

        const assumptions = data.metadata?.assumptions_made || [];
        if (assumptions.length) {
            const list = el('assumptions-list');
            list.innerHTML = assumptions.map(a => `<li>${escapeHtml(a)}</li>`).join('');
            el('assumptions-area').style.display = 'block';
        } else {
            el('assumptions-area').style.display = 'none';
        }

        // 컨텍스트 강도 배지
        const csEl = el('result-context-strength');
        if (csEl) {
            const strength = data.context_strength;
            const csConfig = {
                task:    { bg: '#dcfce7', color: '#15803d', text: 'Task 컨텍스트' },
                project: { bg: '#fef9c3', color: '#a16207', text: '프로젝트 컨텍스트' },
                none:    null,
            };
            const cfg = csConfig[strength];
            if (cfg) {
                csEl.textContent = cfg.text;
                csEl.style.background = cfg.bg;
                csEl.style.color = cfg.color;
                csEl.style.display = 'inline';
            } else {
                csEl.style.display = 'none';
            }
        }

        // 폴백 배지
        const fallbackBadge = el('result-fallback-badge');
        if (fallbackBadge) {
            fallbackBadge.style.display = data.metadata?.fallback_occurred ? 'inline' : 'none';
        }

        el('result-area').style.display = 'block';
        el('result-area').classList.add('pr-fade');
    }

    function hideResult() {
        el('result-area').style.display = 'none';
    }

    /* ── 복사 ── */
    function copyResult() {
        const text = el('result-prompt').textContent;
        copyToClipboard(text, 'copy-btn-text');
    }

    function copyModalPrompt() {
        const text = el('modal-prompt').textContent;
        copyToClipboard(text, 'modal-copy-btn');
    }

    function copyToClipboard(text, labelId) {
        navigator.clipboard.writeText(text).then(() => {
            const btn = el(labelId);
            const prev = btn.textContent;
            btn.textContent = '복사됨!';
            setTimeout(() => btn.textContent = prev, 1500);
        });
    }

    /* ── 초기화 ── */
    function reset() {
        state.sessionId = null;
        state.scheduleId    = null;
        el('user-input').value = '';
        updateCharCount();
        clearError();
        hideClarification();
        hideResult();
        el('user-input').focus();
    }

    /* ── 이력 로드 ── */
    async function loadHistory(refresh) {
        if (state.historyLoading) return;
        if (refresh) {
            state.historyCursor = null;
            el('history-list').innerHTML = '';
        }

        state.historyLoading = true;
        el('history-loading').style.display = 'block';
        el('history-more-btn').style.display = 'none';

        const mode = el('history-mode-filter').value;
        const params = new URLSearchParams({ limit: 20, mode });
        if (state.historyCursor) params.append('cursor', state.historyCursor);

        try {
            const res = await axios.get(`${ROUTES.history}?${params.toString()}`);
            const { items, next_cursor, has_more } = res.data;

            if (refresh && items.length === 0) {
                el('history-empty').style.display = 'block';
            } else {
                el('history-empty').style.display = 'none';
                renderHistoryItems(items);
            }

            state.historyCursor = next_cursor;
            el('history-more-btn').style.display = has_more ? 'block' : 'none';
        } catch (_) {
            // 이력 로드 실패 시 조용히 처리
        } finally {
            state.historyLoading = false;
            el('history-loading').style.display = 'none';
        }
    }

    function renderHistoryItems(items) {
        const list = el('history-list');
        items.forEach(item => {
            const div = document.createElement('div');
            div.className = 'hist-item pr-fade';
            div.onclick = () => openHistoryModal(item.history_id);

            const modeTag = item.mode === 'project'
                ? '<span style="font-size:10px;padding:1px 6px;border-radius:4px;background:#ede9fe;color:#6d5ce7;font-weight:600;flex-shrink:0;">프로젝트</span>'
                : '<span style="font-size:10px;padding:1px 6px;border-radius:4px;background:#f4f4f5;color:#71717a;font-weight:600;flex-shrink:0;">일반</span>';

            const elapsed = item.elapsed_ms ? `${(item.elapsed_ms / 1000).toFixed(1)}s` : '';
            const date = item.created_at ? new Date(item.created_at).toLocaleDateString('ko-KR', {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : '';

            div.innerHTML = `
                <div style="width:32px;height:32px;border-radius:8px;background:linear-gradient(135deg,#ddd6fe,#c4b5fd);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#6d5ce7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px;flex-wrap:wrap;">
                        ${modeTag}
                        <span style="font-size:11px;padding:1px 6px;border-radius:4px;background:#f5f3ff;color:#7c3aed;font-weight:600;">${escapeHtml(taskTypeLabel(item.task_type))}</span>
                    </div>
                    <p style="font-size:13px;color:#3f3f46;margin:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escapeHtml(item.user_input_preview)}</p>
                    <p style="font-size:11px;color:#a1a1aa;margin:3px 0 0;">${date}${elapsed ? ' · ' + elapsed : ''}</p>
                </div>
                <svg width="14" height="14" fill="none" stroke="#c4b5fd" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            `;
            list.appendChild(div);
        });
    }

    /* ── 이력 모달 ── */
    async function openHistoryModal(historyId) {
        state.currentHistoryId = historyId;
        const modal = el('history-modal');
        modal.style.display = 'flex';

        try {
            const res = await axios.get(`${ROUTES.historyShow}/${historyId}`);
            const h = res.data;
            const date = h.created_at ? new Date(h.created_at).toLocaleString('ko-KR') : '';
            el('modal-meta').textContent = `${taskTypeLabel(h.task_type)} · ${h.mode === 'project' ? '프로젝트' : '일반'} · ${date}`;
            el('modal-input').textContent  = h.original_input;
            el('modal-prompt').textContent = h.refined_prompt;
        } catch (_) {
            el('modal-meta').textContent   = '';
            el('modal-prompt').textContent = '로드 실패';
        }
    }

    function closeHistoryModal() {
        el('history-modal').style.display = 'none';
        state.currentHistoryId = null;
    }

    async function deleteHistory() {
        if (!state.currentHistoryId) return;
        if (!confirm('이 이력을 삭제하시겠습니까?')) return;

        try {
            await axios.delete(`${ROUTES.historyShow}/${state.currentHistoryId}`, {
                headers: { 'X-CSRF-TOKEN': ROUTES.csrfToken },
            });
            closeHistoryModal();
            loadHistory(true);
        } catch (_) {
            alert('삭제 중 오류가 발생했습니다.');
        }
    }

    /* ── XSS 방어 ── */
    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeAttr(str) {
        return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }

    /* ── 초기화 ── */
    document.addEventListener('DOMContentLoaded', function () {
        updateCharCount();
        loadHistory(true);

        // 모달 외부 클릭 시 닫기
        el('history-modal').addEventListener('click', function (e) {
            if (e.target === this) closeHistoryModal();
        });
    });

    /* ── 공개 API ── */
    window.promptRefiner = {
        setMode,
        updateCharCount,
        submit,
        submitAnswers,
        copyResult,
        copyModalPrompt,
        reset,
        loadHistory,
        openHistoryModal,
        closeHistoryModal,
        deleteHistory,
        onProjectChange,
        onTaskChange,
        _fillAnswer,
    };
})();
