/**
 * Works Prompt — Claude 스타일 챗 인터페이스
 *
 * 상태 모델: messages 배열을 client-side로 유지.
 * 각 메시지는 { id, role:'user'|'ai'|'thinking', content, meta? } 형식.
 *
 * 백엔드는 stateless하며, 프로젝트가 선택된 경우 서버 측 PlanContextLoader가
 * 최근 PromptHistory 5건을 자동 첨부하므로 이전 대화 연속성은 자연스럽게 유지된다.
 */
(function () {
    'use strict';

    const ROUTES = window.worksPromptRoutes || {};

    const state = {
        projectId:        null,
        messages:         [],
        sending:          false,
        currentHistoryId: null,
        historyCursor:    null,
        historyLoading:   false,
        planLoading:      false,
        // 명확화 라운드 (프로젝트 모드만, 최대 1라운드)
        clarifyingSessionId: null,
        clarifyingUserInput: null,
    };

    /* ── DOM helpers ── */
    function el(id) { return document.getElementById(id); }

    /* ── 빈 화면 토글 ── */
    function toggleWelcome() {
        el('welcome-area').style.display = state.messages.length === 0 ? 'block' : 'none';
    }

    /* ── 글자 수·자동 리사이즈 ── */
    function onInputChange() {
        const ta = el('user-input');
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight, 200) + 'px';
        el('send-btn').disabled = state.sending || !ta.value.trim();
    }

    function onInputKeydown(e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            submit();
        }
    }

    /* ── 프로젝트 변경 ── */
    async function onProjectChange() {
        const projectId = el('project-select').value;
        state.projectId = projectId || null;

        const badge = el('plan-badge');
        badge.classList.remove('no-plan', 'error');

        if (!projectId) {
            badge.style.display = 'none';
            return;
        }

        badge.style.display = 'inline';
        badge.textContent = '기획서 확인 중...';
        badge.classList.add('no-plan');

        if (state.planLoading) return;
        state.planLoading = true;

        try {
            const res = await axios.get(`${ROUTES.projectPlanBase}/${projectId}/plan`);
            const d = res.data;
            badge.classList.remove('no-plan', 'error');
            if (d.has_plan) {
                badge.textContent = `기획서 v${d.version} · ${d.status_label || d.status}`;
            } else {
                badge.textContent = '기획서 없음 (프로젝트 메타만)';
                badge.classList.add('no-plan');
            }
        } catch (_) {
            badge.textContent = '기획서 로드 실패';
            badge.classList.add('error');
        } finally {
            state.planLoading = false;
        }
    }

    /* ── 새 대화 ── */
    function newChat() {
        if (state.sending) return;
        state.messages = [];
        state.clarifyingSessionId = null;
        state.clarifyingUserInput = null;
        el('messages-area').innerHTML = '';
        el('user-input').value = '';
        onInputChange();
        clearError();
        toggleWelcome();
        el('user-input').focus();
    }

    /* ── 질문 전송 ── */
    async function submit() {
        if (state.sending) return;
        clearError();
        const input = (el('user-input').value || '').trim();
        if (!input) return;
        if (input.length > 5000) { showError('질문은 5000자를 초과할 수 없습니다.'); return; }

        state.sending = true;
        const userMsg = { id: 'm_' + Date.now(), role: 'user', content: input };
        state.messages.push(userMsg);
        appendMessage(userMsg);

        const thinkingMsg = { id: 't_' + Date.now(), role: 'thinking', content: '' };
        state.messages.push(thinkingMsg);
        appendMessage(thinkingMsg);

        el('user-input').value = '';
        onInputChange();
        toggleWelcome();
        scrollToBottom();

        try {
            const payload = {
                user_input: input,
                project_id: state.projectId ? parseInt(state.projectId, 10) : null,
            };
            const res = await axios.post(ROUTES.refine, payload, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ROUTES.csrfToken,
                    'Content-Type': 'application/json',
                },
            });

            removeMessageNode(thinkingMsg.id);
            state.messages = state.messages.filter(m => m.id !== thinkingMsg.id);
            handleResponse(res.data, input);
        } catch (err) {
            removeMessageNode(thinkingMsg.id);
            state.messages = state.messages.filter(m => m.id !== thinkingMsg.id);
            handleError(err);
        } finally {
            state.sending = false;
            onInputChange();
            el('user-input').focus();
        }
    }

    /* ── 명확화 답변 제출 ── */
    async function submitClarification(cardId) {
        if (state.sending) return;
        if (!state.clarifyingSessionId) { showError('명확화 세션이 만료되었습니다.'); return; }

        const card = document.querySelector(`[data-clarify-card="${cardId}"]`);
        if (!card) return;
        const inputs = card.querySelectorAll('[data-answer-input]');

        const answers = [];
        let allFilled = true;
        inputs.forEach(inp => {
            const val = (inp.value || '').trim();
            if (!val) {
                inp.style.borderColor = '#f87171';
                allFilled = false;
            } else {
                inp.style.borderColor = '#e7e4dc';
                answers.push({ question_id: inp.dataset.questionId, answer: val });
            }
        });
        if (!allFilled) { showError('모든 질문에 답변해주세요.'); return; }
        clearError();

        // 답변을 유저 메시지로 추가
        const answerSummary = answers.map((a, i) => `${i+1}. ${a.answer}`).join('\n');
        const userMsg = { id: 'm_' + Date.now(), role: 'user', content: answerSummary };
        state.messages.push(userMsg);
        appendMessage(userMsg);

        // 카드 비활성화 + 표시 변경
        card.style.opacity = '.55';
        card.querySelectorAll('button, textarea').forEach(b => b.disabled = true);

        const thinkingMsg = { id: 't_' + Date.now(), role: 'thinking', content: '' };
        state.messages.push(thinkingMsg);
        appendMessage(thinkingMsg);
        scrollToBottom();

        state.sending = true;
        try {
            const res = await axios.post(ROUTES.refine, {
                user_input: state.clarifyingUserInput,
                project_id: state.projectId ? parseInt(state.projectId, 10) : null,
                session_id: state.clarifyingSessionId,
                clarification_answers: answers,
            }, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ROUTES.csrfToken,
                    'Content-Type': 'application/json',
                },
            });
            removeMessageNode(thinkingMsg.id);
            state.messages = state.messages.filter(m => m.id !== thinkingMsg.id);
            handleResponse(res.data, state.clarifyingUserInput);
        } catch (err) {
            removeMessageNode(thinkingMsg.id);
            state.messages = state.messages.filter(m => m.id !== thinkingMsg.id);
            handleError(err);
        } finally {
            state.sending = false;
            onInputChange();
        }
    }

    /* ── 응답 분기 ── */
    function handleResponse(data, originalInput) {
        if (data.status === 'needs_clarification') {
            // 명확화 세션 추적
            state.clarifyingSessionId = data.session_id;
            state.clarifyingUserInput = originalInput;
            const card = {
                id:        'c_' + Date.now(),
                role:      'clarify',
                content:   '',
                meta:      {
                    questions: data.questions || [],
                    task_type: data.task_type || 'other',
                },
            };
            state.messages.push(card);
            appendMessage(card);
            scrollToBottom();
        } else {
            // 답변: 명확화 세션 정리 후 답변 메시지로 렌더
            state.clarifyingSessionId = null;
            state.clarifyingUserInput = null;
            const aiMsg = {
                id:      'a_' + Date.now(),
                role:    'ai',
                content: data.answer || '',
                meta: {
                    mode:      data.mode || 'general',
                    task_type: data.task_type || 'other',
                    metadata:  data.metadata || {},
                    history_id:data.history_id,
                },
            };
            state.messages.push(aiMsg);
            appendMessage(aiMsg);
            scrollToBottom();
            loadHistory(true);
        }
    }

    /* ── 메시지 렌더링 ── */
    function appendMessage(msg) {
        const area = el('messages-area');
        const div = document.createElement('div');
        div.className = 'wp-msg';
        div.id = 'msg-' + msg.id;

        if (msg.role === 'user') {
            div.classList.add('wp-msg-user');
            const bubble = document.createElement('div');
            bubble.className = 'wp-msg-user-bubble';
            bubble.textContent = msg.content;
            div.appendChild(bubble);
        } else if (msg.role === 'thinking') {
            div.classList.add('wp-msg-ai');
            div.innerHTML = `
                <div class="wp-avatar">웍</div>
                <div class="wp-msg-ai-body">
                    <div class="wp-thinking">
                        <span class="wp-thinking-dot"></span>
                        <span class="wp-thinking-dot"></span>
                        <span class="wp-thinking-dot"></span>
                        <span style="margin-left:6px;">웍스가 생각하고 있어요...</span>
                    </div>
                </div>`;
        } else if (msg.role === 'clarify') {
            div.classList.add('wp-msg-ai');
            const questions = msg.meta?.questions || [];
            const qHtml = questions.map(q => `
                <div style="background:#faf8ff;border:1px solid #ede9fe;border-radius:10px;padding:12px 14px;margin-bottom:10px;">
                    <p style="font-size:13px;font-weight:600;color:#1e1b2e;margin:0 0 4px;">${escapeHtml(q.question)}</p>
                    ${q.why_asking ? `<p style="font-size:11.5px;color:#a1a1aa;margin:0 0 9px;">${escapeHtml(q.why_asking)}</p>` : ''}
                    ${(q.suggested_answers || []).length ? `
                        <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;">
                            ${(q.suggested_answers || []).map(s =>
                                `<button type="button" class="wp-suggestion" style="padding:4px 10px;font-size:11.5px;margin:0;" data-fill-target="${escapeAttr(q.id)}" data-fill-value="${escapeAttr(s)}">${escapeHtml(s)}</button>`
                            ).join('')}
                        </div>` : ''}
                    <textarea
                        data-answer-input
                        data-question-id="${escapeAttr(q.id)}"
                        rows="2"
                        placeholder="직접 답변을 입력하거나 위 버튼을 클릭하세요."
                        style="width:100%;border:1.5px solid #e7e4dc;border-radius:8px;padding:7px 10px;font-size:13px;color:#1e1b2e;background:#fff;resize:vertical;outline:none;font-family:inherit;box-sizing:border-box;"
                        onfocus="this.style.borderColor='#a78bfa'" onblur="this.style.borderColor='#e7e4dc'"></textarea>
                </div>`).join('');
            div.innerHTML = `
                <div class="wp-avatar">웍</div>
                <div class="wp-msg-ai-body">
                    <p style="margin:0 0 10px;font-size:13.5px;color:#52525b;">기획서를 봤습니다. 더 정확히 답하기 위해 몇 가지만 확인할게요:</p>
                    <div data-clarify-card="${msg.id}">
                        ${qHtml}
                        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:4px;">
                            <button type="button" class="wp-h-btn primary" data-clarify-submit="${msg.id}">답변 전송</button>
                        </div>
                    </div>
                </div>`;

            // 이벤트 바인딩
            div.querySelectorAll('[data-fill-target]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const target = btn.dataset.fillTarget;
                    const value  = btn.dataset.fillValue;
                    const ta = div.querySelector(`[data-question-id="${CSS.escape(target)}"]`);
                    if (ta) ta.value = value;
                });
            });
            const submitBtn = div.querySelector(`[data-clarify-submit="${msg.id}"]`);
            if (submitBtn) submitBtn.addEventListener('click', () => submitClarification(msg.id));
        } else {
            // ai
            div.classList.add('wp-msg-ai');
            const meta = msg.meta || {};
            const modeBadge = meta.mode === 'project'
                ? '<span class="wp-mode-tag project">프로젝트 컨텍스트</span>'
                : '<span class="wp-mode-tag general">일반 Q&amp;A</span>';
            const taskLabel = taskTypeLabel(meta.task_type);

            const assumptions = meta.metadata?.assumptions_made || [];
            const planRefs    = meta.metadata?.plan_references  || [];
            const hasDetails  = assumptions.length || planRefs.length;

            div.innerHTML = `
                <div class="wp-avatar">웍</div>
                <div class="wp-msg-ai-body">
                    <div class="wp-msg-ai-content">${renderMarkdown(msg.content)}</div>
                    ${hasDetails ? renderDetails(assumptions, planRefs) : ''}
                    <div class="wp-msg-meta">
                        ${modeBadge}
                        <span style="color:#a1a1aa;">${escapeHtml(taskLabel)}</span>
                        <button class="wp-meta-btn" data-copy="${msg.id}">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg>
                            복사
                        </button>
                    </div>
                </div>`;

            // 복사 버튼 이벤트
            const copyBtn = div.querySelector('[data-copy]');
            if (copyBtn) {
                copyBtn.addEventListener('click', () => copyAnswer(msg.id, copyBtn));
            }
        }

        area.appendChild(div);
    }

    function renderDetails(assumptions, planRefs) {
        let body = '';
        if (planRefs.length) {
            body += `<div style="margin-top:6px;"><strong style="font-size:11.5px;color:#7c3aed;">기획서 참조</strong><ul>${planRefs.map(a => `<li>${escapeHtml(a)}</li>`).join('')}</ul></div>`;
        }
        if (assumptions.length) {
            body += `<div style="margin-top:6px;"><strong style="font-size:11.5px;color:#7c3aed;">가정 사항</strong><ul>${assumptions.map(a => `<li>${escapeHtml(a)}</li>`).join('')}</ul></div>`;
        }
        return `<details class="wp-details"><summary>컨텍스트 / 가정 보기 (${planRefs.length + assumptions.length})</summary>${body}</details>`;
    }

    function removeMessageNode(id) {
        const node = el('msg-' + id);
        if (node) node.remove();
    }

    function scrollToBottom() {
        requestAnimationFrame(() => {
            const t = el('thread');
            t.scrollTop = t.scrollHeight;
        });
    }

    /* ── 메시지 복사 ── */
    function copyAnswer(msgId, btn) {
        const msg = state.messages.find(m => m.id === msgId);
        if (!msg) return;
        navigator.clipboard.writeText(msg.content).then(() => {
            const prev = btn.innerHTML;
            btn.innerHTML = '<span style="color:#16a34a;">✓ 복사됨</span>';
            setTimeout(() => btn.innerHTML = prev, 1400);
        });
    }

    /* ── 에러 ── */
    function handleError(err) {
        const status = err.response?.status;
        if (status === 401) {
            showSessionExpired('로그인 세션이 만료되었습니다.');
            return;
        }
        if (status === 419) {
            showSessionExpired('보안 토큰이 만료되었습니다.');
            return;
        }
        const msg = err.response?.data?.message
            || err.response?.data?.error
            || 'AI 호출 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
        showError(msg);
    }
    function showError(text) {
        const box = el('error-msg');
        box.innerHTML = '';
        box.textContent = text;
        box.style.display = 'block';
        scrollToBottom();
    }
    function showSessionExpired(prefix) {
        const box = el('error-msg');
        box.innerHTML = '';
        const span = document.createElement('span');
        span.textContent = prefix + ' 페이지를 새로고침해주세요.';
        const btn = document.createElement('button');
        btn.textContent = '새로고침';
        btn.style.cssText = 'margin-left:10px;padding:3px 12px;border:1px solid #dc2626;background:#fff;color:#dc2626;border-radius:6px;cursor:pointer;font-size:12px;font-weight:600;';
        btn.addEventListener('click', () => location.reload());
        box.appendChild(span);
        box.appendChild(btn);
        box.style.display = 'block';
        scrollToBottom();
    }
    function clearError() { el('error-msg').style.display = 'none'; }

    /* ── task type ── */
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
            chitchat:        '잡담',
            other:           '기타',
        };
        return map[type] || type || '';
    }

    /* ── 드로어 ── */
    function toggleDrawer() {
        const open = el('history-drawer').classList.toggle('open');
        el('drawer-backdrop').classList.toggle('open', open);
        if (open) loadHistory(true);
    }

    /* ── 이력 ── */
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
            // 조용히 처리
        } finally {
            state.historyLoading = false;
            el('history-loading').style.display = 'none';
        }
    }

    function renderHistoryItems(items) {
        const list = el('history-list');
        items.forEach(item => {
            const div = document.createElement('div');
            div.style.cssText = 'padding:10px 12px;border:1px solid #ece9e3;border-radius:8px;cursor:pointer;background:#fff;transition:background .12s;';
            div.onmouseover = () => div.style.background = '#f5f3ee';
            div.onmouseout  = () => div.style.background = '#fff';
            div.onclick     = () => openHistoryModal(item.history_id);

            const modeTag = item.mode === 'project'
                ? '<span class="wp-mode-tag project">프로젝트</span>'
                : '<span class="wp-mode-tag general">일반</span>';
            const date = item.created_at ? new Date(item.created_at).toLocaleDateString('ko-KR', {month:'short',day:'numeric',hour:'2-digit',minute:'2-digit'}) : '';

            div.innerHTML = `
                <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                    ${modeTag}
                    <span style="font-size:10.5px;color:#a1a1aa;">${escapeHtml(taskTypeLabel(item.task_type))}</span>
                </div>
                <p style="font-size:12.5px;color:#3f3f46;margin:0;overflow:hidden;text-overflow:ellipsis;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-height:1.45;">${escapeHtml(item.user_input_preview)}</p>
                <p style="font-size:10.5px;color:#a1a1aa;margin:4px 0 0;">${date}</p>`;
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
            const modeLabel = h.mode === 'project' ? '프로젝트' : '일반';
            el('modal-meta').textContent = `${taskTypeLabel(h.task_type)} · ${modeLabel} · ${date}`;
            el('modal-input').textContent = h.original_input || '';
            el('modal-prompt').innerHTML = renderMarkdown(h.answer ?? h.refined_prompt ?? '');
            el('modal-prompt').dataset.rawText = h.answer ?? h.refined_prompt ?? '';
        } catch (err) {
            const status = err.response?.status;
            el('modal-meta').textContent = '';
            if (status === 401 || status === 419) {
                el('modal-prompt').innerHTML = '세션이 만료되었습니다. <a href="javascript:location.reload()" style="color:#7c3aed;text-decoration:underline;">새로고침</a>해주세요.';
            } else {
                el('modal-prompt').textContent = '로드 실패';
            }
        }
    }

    function closeHistoryModal() {
        el('history-modal').style.display = 'none';
        state.currentHistoryId = null;
    }

    function copyModalPrompt() {
        const text = el('modal-prompt').dataset.rawText || el('modal-prompt').textContent;
        navigator.clipboard.writeText(text).then(() => {
            const btn = el('modal-copy-btn');
            const prev = btn.textContent;
            btn.textContent = '복사됨!';
            setTimeout(() => btn.textContent = prev, 1400);
        });
    }

    async function deleteHistory() {
        if (!state.currentHistoryId) return;
        if (!confirm('이 이력을 삭제하시겠습니까?')) return;
        try {
            await axios.delete(`${ROUTES.historyShow}/${state.currentHistoryId}`, {
                headers: {
                    // 세션 회전으로 페이지 로드 시 토큰이 오래되었을 가능성 → meta tag 우선
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ROUTES.csrfToken,
                },
            });
            closeHistoryModal();
            loadHistory(true);
        } catch (err) {
            const status = err.response?.status;
            if (status === 401 || status === 419) {
                alert('세션이 만료되었습니다. 새로고침 후 다시 시도해주세요.');
                location.reload();
            } else {
                alert('삭제 중 오류가 발생했습니다.');
            }
        }
    }

    /* ── 마크다운 (최소 구현) ──
     * 지원: 코드 블록 (```lang ... ```), 인라인 코드 (`x`), **굵게**,
     *      목록 (- *), GFM 표, 단락(\n\n), 줄바꿈(\n)
     * 보안: 코드 블록 추출 → HTML 이스케이프 → 표·인라인 마크업 → 코드 블록 복원
     */
    function renderMarkdown(text) {
        if (!text) return '';

        const codeBlocks = [];
        let out = text.replace(/```([a-zA-Z0-9_+\-.]*)\n?([\s\S]*?)```/g, (_, lang, code) => {
            codeBlocks.push(code);
            return `\x00CODE${codeBlocks.length - 1}\x00`;
        });

        out = escapeHtml(out);

        // GFM 표: |H1|H2|\n|---|---|\n|c1|c2|\n
        out = parseMarkdownTables(out);

        out = out.replace(/`([^`\n]+)`/g, (_, c) => `<code class="wp-inline-code">${c}</code>`);
        out = out.replace(/\*\*([^*\n]+)\*\*/g, '<strong>$1</strong>');

        // 목록 — 연속된 라인을 <ul>로 묶음
        out = out.replace(/(?:^|\n)((?:[-*] .+\n?)+)/g, (_, block) => {
            const items = block.trim().split(/\n/).map(line => {
                return '<li>' + line.replace(/^[-*] /, '') + '</li>';
            }).join('');
            return '\n<ul>' + items + '</ul>\n';
        });

        // 단락
        out = out.split(/\n{2,}/).map(p => {
            if (/^<(ul|ol|pre|h\d|table)/i.test(p.trim())) return p;
            return '<p>' + p.replace(/\n/g, '<br>') + '</p>';
        }).join('');

        // 코드 블록 복원
        out = out.replace(/\x00CODE(\d+)\x00/g, (_, idx) => {
            const code = codeBlocks[parseInt(idx, 10)] || '';
            return `<pre class="wp-code-block"><code>${escapeHtml(code).replace(/\n+$/, '')}</code></pre>`;
        });

        return out;
    }

    /* GFM 표 파서 (라인 기반 — escape된 텍스트 기준) */
    function parseMarkdownTables(text) {
        const lines = text.split('\n');
        const out = [];
        let i = 0;

        const isTableRow = (line) => /^\s*\|.*\|\s*$/.test(line);
        const isSeparator = (line) => /^\s*\|?\s*:?-+:?(\s*\|\s*:?-+:?)+\s*\|?\s*$/.test(line);

        const splitRow = (line) => {
            let s = line.trim();
            if (s.startsWith('|')) s = s.slice(1);
            if (s.endsWith('|')) s = s.slice(0, -1);
            return s.split('|').map(c => c.trim());
        };

        while (i < lines.length) {
            // 표 시작 후보: 헤더 + 구분자
            if (i + 1 < lines.length && isTableRow(lines[i]) && isSeparator(lines[i + 1])) {
                const headers = splitRow(lines[i]);
                i += 2;
                const rows = [];
                while (i < lines.length && isTableRow(lines[i])) {
                    rows.push(splitRow(lines[i]));
                    i++;
                }
                const thead = '<thead><tr>' + headers.map(h => `<th>${h}</th>`).join('') + '</tr></thead>';
                const tbody = '<tbody>' + rows.map(r =>
                    '<tr>' + r.map(c => `<td>${c}</td>`).join('') + '</tr>'
                ).join('') + '</tbody>';
                out.push(`<table class="wp-md-table">${thead}${tbody}</table>`);
                continue;
            }
            out.push(lines[i]);
            i++;
        }
        return out.join('\n');
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
        return String(str).replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    /* ── 초기화 ── */
    document.addEventListener('DOMContentLoaded', function () {
        onInputChange();
        toggleWelcome();

        // 모달 외부 클릭
        const modal = el('history-modal');
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeHistoryModal();
            });
        }

        // Esc로 드로어/모달 닫기
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                if (el('history-modal').style.display === 'flex') {
                    closeHistoryModal();
                } else if (el('history-drawer').classList.contains('open')) {
                    toggleDrawer();
                }
            }
        });

        el('user-input').focus();
    });

    /* ── 공개 API ── */
    window.worksPrompt = {
        onProjectChange,
        onInputChange,
        onInputKeydown,
        newChat,
        submit,
        toggleDrawer,
        loadHistory,
        openHistoryModal,
        closeHistoryModal,
        copyModalPrompt,
        deleteHistory,
    };
})();
