{{--
  웍스 처리 진행 표시 컴포넌트 (T13)

  Usage — demo mode (no server-side session needed):
    <x-ai-agent.ai-progress
        :demo-sse-url-tpl="route('ai-agent.stream.demo-sse', ['scenario' => 'SCENARIO'])"
        :cancel-url-tpl="route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID'])"
        mode="demo"
        label="AS-IS 분석"
    />
    // JS: $refs.myProgress.startDemo('short')

  Usage — real streaming (project-scoped):
    <x-ai-agent.ai-progress
        :start-url="route('ai-agent.projects.stream.start', $project)"
        :sse-url-tpl="route('ai-agent.projects.stream.sse', [$project, 'SESSION_ID'])"
        :cancel-url-tpl="route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID'])"
        :status-url-tpl="route('ai-agent.projects.stream.status', [$project, 'SESSION_ID'])"
        mode="streaming"
        label="기획서 초안 작성"
        on-complete="handleAnalysisComplete"
    />
    // JS: $refs.myProgress.start(prompt, {stage: 'planning', task_type: 'analysis'})

  Props:
    mode            — 'streaming' | 'polling' | 'demo'
    startUrl        — POST URL to create session (streaming/polling modes)
    sseUrlTpl       — SSE URL template with 'SESSION_ID' placeholder (streaming mode)
    cancelUrlTpl    — Cancel URL template with 'SESSION_ID' placeholder
    statusUrlTpl    — Status URL template with 'SESSION_ID' placeholder (polling mode)
    demoSseUrlTpl   — Demo SSE URL template with 'SCENARIO' placeholder (demo mode)
    allowCancel     — bool (default true)
    showOutput      — bool — show streaming text (default true)
    label           — display title
    onComplete      — JS function name called with (data, component) on completion
    onError         — JS function name called with (data, component) on error
--}}
@props([
    'mode'           => 'streaming',
    'startUrl'       => null,
    'sseUrlTpl'      => null,
    'cancelUrlTpl'   => null,
    'statusUrlTpl'   => null,
    'demoSseUrlTpl'  => null,
    'allowCancel'    => true,
    'showOutput'     => true,
    'label'          => '웍스 처리',
    'onComplete'     => null,
    'onError'        => null,
])

@push('styles')
<style>
[x-cloak] { display: none !important; }
.aip { background: #fff; border: 1.5px solid #ede8ff; border-radius: 14px; overflow: hidden; }
.aip-header { display: flex; align-items: center; gap: 10px; padding: 14px 18px; border-bottom: 1px solid #f3eeff; }
.aip-header-icon { color: var(--t500); flex-shrink: 0; }
.aip-header-title { font-size: 13px; font-weight: 700; color: #1e1b2e; flex: 1; }
.aip-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 700; padding: 2px 9px; border-radius: 20px; }
.aip-badge.idle      { background: #f1f5f9; color: #64748b; }
.aip-badge.starting  { background: #ede9fe; color: #6d28d9; }
.aip-badge.streaming { background: #dbeafe; color: #1d4ed8; }
.aip-badge.completed { background: #dcfce7; color: #15803d; }
.aip-badge.error     { background: #fee2e2; color: #b91c1c; }
.aip-badge.cancelled { background: #fef3c7; color: #92400e; }
.aip-body { padding: 16px 18px; }
.aip-elapsed { font-size: 12px; font-weight: 700; color: #94a3b8; font-variant-numeric: tabular-nums; }
.aip-progress-bar-wrap { background: #e0e7ff; border-radius: 999px; height: 6px; margin-bottom: 10px; overflow: hidden; }
.aip-progress-bar { background: var(--t500); height: 100%; border-radius: 999px; transition: width .4s ease; }
.aip-progress-msg { font-size: 12px; color: #64748b; margin-bottom: 10px; }
.aip-output { background: #faf5ff; border: 1px solid #ede8ff; border-radius: 8px; padding: 12px 14px; font-size: 13px; color: #374151; line-height: 1.7; white-space: pre-wrap; word-break: break-word; max-height: 320px; overflow-y: auto; }
.aip-stats { display: flex; flex-wrap: wrap; gap: 14px; font-size: 12px; color: #64748b; margin-top: 12px; }
.aip-stat { display: flex; align-items: center; gap: 4px; }
.aip-stat strong { color: #1e1b2e; font-weight: 600; }
.aip-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px; }
.aip-btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: none; transition: all .15s; }
.aip-btn.primary   { background: var(--t600); color: #fff; }
.aip-btn.primary:hover   { background: var(--t700); }
.aip-btn.danger    { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.aip-btn.danger:hover    { background: #fecaca; }
.aip-btn.ghost     { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.aip-btn.ghost:hover     { background: #e2e8f0; }
.aip-btn:disabled  { opacity: .45; cursor: not-allowed; }
.aip-spinner { width: 16px; height: 16px; border: 2px solid #e0e7ff; border-top-color: var(--t500); border-radius: 50%; animation: aip-spin .7s linear infinite; flex-shrink: 0; }
@keyframes aip-spin { to { transform: rotate(360deg); } }
.aip-check { color: #16a34a; }
.aip-error-icon { color: #dc2626; }
.aip-idle-text { font-size: 13px; color: #94a3b8; text-align: center; padding: 12px 0; }
</style>
@endpush

<div class="aip"
     x-data="aiProgress(@json([
         'mode'          => $mode,
         'startUrl'      => $startUrl,
         'sseUrlTpl'     => $sseUrlTpl,
         'cancelUrlTpl'  => $cancelUrlTpl,
         'statusUrlTpl'  => $statusUrlTpl,
         'demoSseUrlTpl' => $demoSseUrlTpl,
         'allowCancel'   => $allowCancel,
         'showOutput'    => $showOutput,
         'label'         => $label,
         'onComplete'    => $onComplete,
         'onError'       => $onError,
         'csrfToken'     => csrf_token(),
     ]))">

    {{-- Header --}}
    <div class="aip-header">
        <svg class="aip-header-icon" width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0112 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5"/>
        </svg>
        <span class="aip-header-title" x-text="label"></span>
        <span class="aip-elapsed" x-show="status === 'STREAMING' || status === 'STARTING'" x-cloak x-text="formatElapsed(elapsed)"></span>

        {{-- Status badge --}}
        <span class="aip-badge" :class="badgeClass()">
            <span class="aip-spinner" x-show="status === 'STARTING' || status === 'STREAMING'" style="width:9px;height:9px;border-width:1.5px;"></span>
            <svg x-show="status === 'COMPLETED'" class="aip-check" width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <svg x-show="status === 'ERROR'" class="aip-error-icon" width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <span x-text="badgeLabel()"></span>
        </span>
    </div>

    {{-- Body --}}
    <div class="aip-body">

        {{-- IDLE --}}
        <div x-show="status === 'IDLE'">
            <p class="aip-idle-text">웍스 처리 준비 중입니다.</p>
        </div>

        {{-- STARTING --}}
        <div x-show="status === 'STARTING'" x-cloak>
            <p style="font-size:13px;color:#64748b;margin:0;display:flex;align-items:center;gap:8px;">
                <span class="aip-spinner"></span>
                웍스에 요청을 전송하고 있습니다...
            </p>
        </div>

        {{-- STREAMING --}}
        <div x-show="status === 'STREAMING'" x-cloak>
            {{-- Progress bar (job mode) --}}
            <div x-show="progress > 0">
                <div class="aip-progress-bar-wrap">
                    <div class="aip-progress-bar" :style="'width:' + progress + '%'"></div>
                </div>
                <p class="aip-progress-msg" x-text="progressMessage || '처리 중...'"></p>
            </div>

            {{-- Streaming text output --}}
            <div x-show="showOutput && receivedText" class="aip-output" x-ref="outputBox" x-text="receivedText"></div>

            {{-- Actions --}}
            <div class="aip-actions">
                <template x-if="allowCancel">
                    <button class="aip-btn danger" @click="cancel">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        취소
                    </button>
                </template>
            </div>
        </div>

        {{-- COMPLETED --}}
        <div x-show="status === 'COMPLETED'" x-cloak>
            {{-- Usage stats --}}
            <div class="aip-stats">
                <span class="aip-stat">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    입력 <strong x-text="formatTokens(tokensIn)"></strong>
                </span>
                <span class="aip-stat">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    출력 <strong x-text="formatTokens(tokensOut)"></strong>
                </span>
                <span class="aip-stat">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <strong x-text="formatElapsed(elapsed)"></strong>
                </span>
                <span class="aip-stat" x-show="costUsd > 0">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <strong x-text="formatCost(costUsd)"></strong>
                </span>
            </div>

            {{-- Output text --}}
            <div x-show="showOutput && receivedText" class="aip-output" style="margin-top:12px;" x-text="receivedText"></div>
        </div>

        {{-- ERROR --}}
        <div x-show="status === 'ERROR'" x-cloak>
            <div style="display:flex;align-items:flex-start;gap:8px;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:12.5px;color:#b91c1c;">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 20 20" style="flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span x-text="errorMessage || '처리 중 오류가 발생했습니다.'"></span>
            </div>
            <div class="aip-actions">
                <button class="aip-btn ghost" @click="reset">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    재시도
                </button>
            </div>
        </div>

        {{-- CANCELLED --}}
        <div x-show="status === 'CANCELLED'" x-cloak>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:#92400e;padding:8px 12px;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                작업이 취소되었습니다.
                <span x-show="elapsed > 0" x-text="'(' + formatElapsed(elapsed) + ' 경과)'"></span>
            </div>
            <div class="aip-actions">
                <button class="aip-btn ghost" @click="reset">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    다시 시작
                </button>
            </div>
        </div>

    </div>{{-- /.aip-body --}}

</div>

@once
@push('scripts')
<script>
function aiProgress(cfg) {
    return {
        // Config
        mode:         cfg.mode,
        startUrl:     cfg.startUrl,
        sseUrlTpl:    cfg.sseUrlTpl,
        cancelUrlTpl: cfg.cancelUrlTpl,
        statusUrlTpl: cfg.statusUrlTpl,
        demoSseUrlTpl: cfg.demoSseUrlTpl,
        allowCancel:  cfg.allowCancel,
        showOutput:   cfg.showOutput,
        label:        cfg.label,
        csrfToken:    cfg.csrfToken,
        _onCompleteCb: cfg.onComplete,
        _onErrorCb:    cfg.onError,

        // Reactive state
        status:          'IDLE',   // IDLE | STARTING | STREAMING | COMPLETED | ERROR | CANCELLED
        sessionId:       null,
        receivedText:    '',
        tokensIn:        0,
        tokensOut:       0,
        elapsed:         0,
        progress:        0,        // 0-100 for job mode
        progressMessage: '',
        costUsd:         0,
        errorMessage:    null,

        // Private handles
        _es:           null,       // EventSource
        _timer:        null,       // elapsed timer interval
        _pollTimer:    null,       // polling interval
        _startMs:      null,

        // ── Public API ─────────────────────────────────────────────────

        // Streaming / polling mode: POST to startUrl first, then open SSE/poll
        async start(prompt, extraData = {}) {
            if (this.status === 'STARTING' || this.status === 'STREAMING') return;
            this._resetState();
            this.status = 'STARTING';
            this._startMs = Date.now();
            this._startElapsedTimer();

            try {
                const res  = await fetch(this.startUrl, {
                    method: 'POST',
                    headers: this._headers(),
                    body:    JSON.stringify({ prompt, ...extraData }),
                });
                const data = await res.json();
                if (!data.success) throw new Error(data.message || '세션 시작 실패');
                this.sessionId = data.sessionId;

                if (this.mode === 'polling') {
                    this._startPolling();
                } else {
                    this._openSSE(this.sseUrlTpl.replace('SESSION_ID', this.sessionId));
                }
            } catch (e) {
                this._handleError(e.message || '처리 시작 중 오류가 발생했습니다.');
            }
        },

        // Demo mode: client-generated sessionId, connects directly to demo SSE
        startDemo(scenario) {
            if (this.status === 'STARTING' || this.status === 'STREAMING') return;
            this._resetState();
            this.status   = 'STARTING';
            this.sessionId = 'demo-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);
            this._startMs = Date.now();
            this._startElapsedTimer();

            const url = this.demoSseUrlTpl.replace('SCENARIO', scenario) + '?sessionId=' + this.sessionId;
            this._openSSE(url);
        },

        async cancel() {
            if (!this.sessionId || !this.cancelUrlTpl) return;
            const url = this.cancelUrlTpl.replace('SESSION_ID', this.sessionId);
            try {
                await fetch(url, { method: 'POST', headers: this._headers(), body: '{}' });
            } catch {}
            // The SSE will eventually send 'cancelled'; update UI immediately for responsiveness
            this._closeSSE();
            this._stopTimers();
            this.status = 'CANCELLED';
        },

        reset() {
            this._closeSSE();
            this._stopTimers();
            this._resetState();
        },

        // ── SSE ────────────────────────────────────────────────────────

        _openSSE(url) {
            this._es = new EventSource(url);

            this._es.addEventListener('status', (e) => {
                const d = JSON.parse(e.data);
                this.status = d.status || this.status;
                if (d.progress !== undefined) this.progress = d.progress;
            });

            this._es.addEventListener('token', (e) => {
                const d = JSON.parse(e.data);
                this.status = 'STREAMING';
                this.receivedText += d.text || '';
                if (d.elapsed !== undefined) this.elapsed = d.elapsed;
                this._scrollOutput();
            });

            this._es.addEventListener('progress', (e) => {
                const d = JSON.parse(e.data);
                this.status = 'STREAMING';
                if (d.progress !== undefined) this.progress = d.progress;
                if (d.message)  this.progressMessage = d.message;
                if (d.elapsed !== undefined) this.elapsed = d.elapsed;
            });

            this._es.addEventListener('complete', (e) => {
                const d = JSON.parse(e.data);
                this.status    = 'COMPLETED';
                this.tokensIn  = d.tokensIn  || 0;
                this.tokensOut = d.tokensOut || 0;
                this.elapsed   = d.elapsed   || this.elapsed;
                this.costUsd   = d.costUsd   || 0;
                this.progress  = 100;
                if (d.text) this.receivedText = d.text;
                this._closeSSE();
                this._stopTimers();
                this._trigger('complete', d);
            });

            this._es.addEventListener('cancelled', (e) => {
                const d = JSON.parse(e.data);
                this.status  = 'CANCELLED';
                if (d.elapsed !== undefined) this.elapsed = d.elapsed;
                if (d.tokensOut) this.tokensOut = d.tokensOut;
                this._closeSSE();
                this._stopTimers();
            });

            this._es.addEventListener('error', (e) => {
                let msg = '처리 중 오류가 발생했습니다.';
                try { msg = JSON.parse(e.data).message || msg; } catch {}
                this._handleError(msg);
            });

            this._es.onerror = () => {
                if (this.status === 'STREAMING' || this.status === 'STARTING') {
                    this._handleError('SSE 연결이 끊어졌습니다.');
                }
            };
        },

        // ── Polling ────────────────────────────────────────────────────

        _startPolling() {
            this.status = 'STREAMING';
            const url   = this.statusUrlTpl.replace('SESSION_ID', this.sessionId);

            this._pollTimer = setInterval(async () => {
                try {
                    const res     = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const payload = await res.json();
                    if (!payload.success) return;

                    const s = payload.session;
                    if (s.status === 'COMPLETED') {
                        this.status    = 'COMPLETED';
                        this.tokensIn  = s.tokens_in;
                        this.tokensOut = s.tokens_out;
                        this.elapsed   = s.elapsed;
                        this.costUsd   = s.cost_usd;
                        this.receivedText = s.text;
                        this._stopTimers();
                        this._trigger('complete');
                    } else if (s.status === 'ERROR') {
                        this._handleError(s.error || '오류가 발생했습니다.');
                    } else if (s.status === 'CANCELLED') {
                        this.status = 'CANCELLED';
                        this.elapsed = s.elapsed;
                        this._stopTimers();
                    }
                } catch {}
            }, 2000);
        },

        // ── Timers & cleanup ───────────────────────────────────────────

        _startElapsedTimer() {
            this._timer = setInterval(() => {
                if (this._startMs && (this.status === 'STREAMING' || this.status === 'STARTING')) {
                    this.elapsed = parseFloat(((Date.now() - this._startMs) / 1000).toFixed(1));
                }
            }, 500);
        },

        _stopTimers() {
            if (this._timer)     { clearInterval(this._timer);     this._timer = null; }
            if (this._pollTimer) { clearInterval(this._pollTimer); this._pollTimer = null; }
        },

        _closeSSE() {
            if (this._es) { this._es.close(); this._es = null; }
        },

        _scrollOutput() {
            this.$nextTick(() => {
                const box = this.$refs.outputBox;
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        // ── Shared helpers ─────────────────────────────────────────────

        _headers() {
            return { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrfToken };
        },

        _resetState() {
            this.status = 'IDLE'; this.sessionId = null; this.receivedText = '';
            this.tokensIn = 0; this.tokensOut = 0; this.elapsed = 0;
            this.progress = 0; this.progressMessage = ''; this.costUsd = 0;
            this.errorMessage = null;
        },

        _handleError(msg) {
            this.status = 'ERROR';
            this.errorMessage = msg;
            this._closeSSE();
            this._stopTimers();
            this._trigger('error', { message: msg });
        },

        _trigger(type, data = {}) {
            const cb = type === 'complete' ? this._onCompleteCb : this._onErrorCb;
            if (cb && typeof window[cb] === 'function') window[cb](data, this);
        },

        // ── Badge ──────────────────────────────────────────────────────

        badgeClass() {
            const map = { IDLE: 'idle', STARTING: 'starting', STREAMING: 'streaming', COMPLETED: 'completed', ERROR: 'error', CANCELLED: 'cancelled' };
            return 'aip-badge ' + (map[this.status] ?? 'idle');
        },

        badgeLabel() {
            const map = { IDLE: '대기 중', STARTING: '시작 중', STREAMING: '처리 중', COMPLETED: '완료', ERROR: '오류', CANCELLED: '취소됨' };
            return map[this.status] ?? '';
        },

        // ── Formatters ─────────────────────────────────────────────────

        formatTokens(n) {
            return n >= 1000 ? (n / 1000).toFixed(1) + 'K' : String(n);
        },

        formatCost(usd) {
            if (!usd || usd === 0) return '$0';
            if (usd < 0.001)  return '<$0.001';
            if (usd < 0.01)   return '$' + usd.toFixed(4);
            return '$' + usd.toFixed(3);
        },

        formatElapsed(s) {
            if (!s || s < 0.1) return '0.0초';
            const m   = Math.floor(s / 60);
            const sec = (s % 60).toFixed(m > 0 ? 0 : 1);
            return m > 0 ? `${m}분 ${sec}초` : `${sec}초`;
        },
    };
}
</script>
@endpush
@endonce
