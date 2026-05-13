@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────── */
.fc-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.fc-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.fc-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Buttons ─────────────────────────────────────────── */
.fc-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.fc-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.fc-btn.primary:hover { background:var(--t700,#6d28d9); }
.fc-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.fc-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.fc-btn.secondary:hover { background:#e2e8f0; }
.fc-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.fc-btn.ghost:hover { background:#f5f3ff; }
.fc-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Stats ────────────────────────────────────────────── */
.fc-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-bottom:18px; }
.fc-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; }
.fc-stat-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.fc-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Section ──────────────────────────────────────────── */
.fc-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.fc-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* ── Cost banner ──────────────────────────────────────── */
.fc-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; margin-bottom:18px; }
.fc-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.fc-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.fc-banner-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.fc-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 20px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.fc-start-btn:hover { background:#f5f3ff; }
.fc-start-btn:disabled { opacity:.5; cursor:not-allowed; }
.fc-start-btn.outline { background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.3); }
.fc-start-btn.outline:hover { background:rgba(255,255,255,.25); }

/* ── Cost confirm modal ─────────────────────────────── */
.confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.confirm-box { background:#fff; border-radius:16px; padding:28px; max-width:420px; width:100%; }
.confirm-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 8px; }
.confirm-box .cost-highlight { font-size:28px; font-weight:800; color:#7c3aed; margin:12px 0; }
.confirm-box p { font-size:13px; color:#64748b; margin:0 0 16px; }
.confirm-warning { background:#fffbeb; border:1.5px solid #fde68a; border-radius:8px; padding:10px 14px; font-size:12.5px; color:#92400e; margin-bottom:16px; }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; }

/* ── Progress ─────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.progress-log { background:#0f0f1a; border-radius:10px; padding:12px 14px; max-height:200px; overflow-y:auto; font-family:monospace; font-size:12px; color:#94a3b8; }
.progress-log-line.ok     { color:#4ade80; }
.progress-log-line.fail   { color:#f87171; }
.progress-log-line.active { color:#c4b5fd; }

/* ── Screen grid ──────────────────────────────────────── */
.screen-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(220px, 1fr)); gap:14px; }
.screen-card { border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; background:#fff; display:flex; flex-direction:column; gap:8px; transition:border-color .15s; }
.screen-card:hover { border-color:#c4b5fd; }
.screen-card.done   { border-color:#bbf7d0; background:#f0fdf4; }
.screen-card.missing { border-color:#fde68a; }
.screen-card-id   { font-size:11px; font-weight:700; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:1px 7px; display:inline-block; }
.screen-card-title { font-size:13px; font-weight:700; color:#1e1b2e; }
.screen-card-desc  { font-size:12px; color:#94a3b8; flex:1; }
.screen-card-meta  { font-size:11.5px; color:#64748b; }
.screen-card-status { font-size:11.5px; font-weight:700; }
.screen-card-status.done    { color:#16a34a; }
.screen-card-status.missing { color:#d97706; }
.screen-card-actions { display:flex; gap:5px; flex-wrap:wrap; margin-top:4px; }

/* ── Stack badge ──────────────────────────────────────── */
.stack-badge { font-size:12px; font-weight:700; padding:3px 10px; border-radius:99px; }
.stack-badge.html  { background:#fef3c7; color:#92400e; }
.stack-badge.react { background:#dbeafe; color:#1e40af; }
.stack-badge.vue   { background:#d1fae5; color:#065f46; }

@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')
<script type="application/json" id="fc-index-data">
{
    "totalCount": {{ $totalCount }},
    "doneCount": {{ $doneCount }},
    "missingCount": {{ $missingCount }},
    "estimatedCost": {{ $estimatedCost }},
    "batchStartUrl": "{{ $batchStartUrl }}",
    "batchSseUrlTpl": "{{ $batchSseUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="frontendCodeIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="fc-header">
        <div class="fc-header-left">
            <h1>
                Frontend Code 생성
                <span class="stack-badge {{ $stack->value }}" style="font-size:13px;vertical-align:middle;margin-left:6px;">{{ $stack->label() }}</span>
            </h1>
            <p>ERD·API·RBAC·디자인 시스템을 통합하여 화면별 프로덕션 수준의 Frontend 코드를 웍스로 생성합니다.</p>
        </div>
        @if($doneCount > 0)
        <div style="display:flex;gap:8px;align-items:center;">
            <a href="{{ $downloadAllUrl }}" class="fc-btn ghost sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                전체 Zip
            </a>
        </div>
        @endif
    </div>

    {{-- 통계 --}}
    <div class="fc-stats">
        <div class="fc-stat">
            <div class="fc-stat-label">전체 화면</div>
            <div class="fc-stat-value">{{ $totalCount }}</div>
        </div>
        <div class="fc-stat">
            <div class="fc-stat-label">코드 생성</div>
            <div class="fc-stat-value" style="color:#16a34a;">{{ $doneCount }}</div>
        </div>
        <div class="fc-stat">
            <div class="fc-stat-label">미생성</div>
            <div class="fc-stat-value" style="color:#d97706;">{{ $missingCount }}</div>
        </div>
        <div class="fc-stat">
            <div class="fc-stat-label">프롬프트 준비</div>
            <div class="fc-stat-value" style="color:#7c3aed;">{{ $promptCount }}</div>
        </div>
    </div>

    {{-- 생성 배너 --}}
    @if($totalCount > 0)
    <div class="fc-banner">
        <div class="fc-banner-text">
            <h3>⭐ 웍스 Frontend 코드 일괄 생성</h3>
            <p>
                화면당 평균 ~${{ number_format(0.80, 2) }} · 전체 {{ $totalCount }}개 = ~${{ $estimatedCost }}
                @if($promptCount < $totalCount)
                · ⚠️ {{ $totalCount - $promptCount }}개 화면에 코드 프롬프트 없음 (T39 먼저 실행 권장)
                @endif
            </p>
        </div>
        <div class="fc-banner-actions">
            <button class="fc-start-btn outline" :disabled="isGenerating" @click="requestBatch(true)">미생성만</button>
            <button class="fc-start-btn" :disabled="isGenerating" @click="requestBatch(false)">전체 생성</button>
        </div>
    </div>
    @endif

    {{-- SSE 진행 상황 --}}
    <template x-if="isGenerating || batchResult">
        <div class="fc-section">
            <div class="fc-section-title">
                <span x-text="isGenerating ? '코드 생성 진행 중...' : '생성 완료'"></span>
                <span x-show="isGenerating" style="font-size:11px;font-weight:400;color:#64748b;" x-text="`${progressDone}/${progressTotal}`"></span>
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progress}%`"></div>
            </div>
            <div class="progress-log" x-ref="logEl">
                <template x-for="(line, i) in progressLog" :key="i">
                    <div class="progress-log-line" :class="line.cls" x-text="line.text"></div>
                </template>
            </div>
            <template x-if="batchResult">
                <div style="margin-top:14px;display:flex;gap:16px;flex-wrap:wrap;font-size:13px;align-items:center;">
                    <span>✅ 완료 <strong x-text="batchResult.done - batchResult.failed_count"></strong>개</span>
                    <span x-show="batchResult.failed_count > 0" style="color:#b91c1c;">❌ 실패 <strong x-text="batchResult.failed_count"></strong>개</span>
                    <span style="color:#64748b;">⏱ <span x-text="batchResult.elapsed"></span>초</span>
                    <span style="color:#64748b;">💰 $<span x-text="batchResult.cost_usd"></span></span>
                    <button class="fc-btn secondary sm" @click="batchResult=null;progressLog=[];progress=0;" style="margin-left:auto;">닫기</button>
                    <button class="fc-btn primary sm" @click="window.location.reload()">목록 새로고침</button>
                </div>
            </template>
        </div>
    </template>

    {{-- 화면 그리드 --}}
    <div class="fc-section">
        <div class="fc-section-title">
            화면별 코드
            <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:4px;">{{ $totalCount }}개</span>
        </div>

        @if($totalCount === 0)
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
            <p style="font-size:15px;font-weight:600;margin-bottom:8px;">화면이 없습니다</p>
            <p style="font-size:13px;">기획 단계에서 화면을 먼저 등록해주세요.</p>
        </div>
        @else
        <div class="screen-grid">
            @foreach($screenData as $item)
            @php $screen = $item['screen']; @endphp
            <div class="screen-card {{ $item['has_code'] ? 'done' : 'missing' }}">
                <div>
                    <span class="screen-card-id">{{ $screen->screen_id }}</span>
                    @if(!$item['has_prompt'])
                    <span style="font-size:10px;background:#fef3c7;color:#92400e;border-radius:99px;padding:1px 6px;margin-left:4px;">프롬프트 없음</span>
                    @endif
                </div>
                <div class="screen-card-title">{{ $screen->title }}</div>
                @if($screen->description)
                <div class="screen-card-desc">{{ Str::limit($screen->description, 60) }}</div>
                @endif
                <div class="screen-card-status {{ $item['has_code'] ? 'done' : 'missing' }}">
                    @if($item['has_code'])
                    ✅ {{ $item['files_count'] }}개 파일 생성됨
                    @else
                    ⏳ 미생성
                    @endif
                </div>
                @if($item['generated_at'])
                <div class="screen-card-meta">
                    {{ \Carbon\Carbon::parse($item['generated_at'])->format('m/d H:i') }}
                </div>
                @endif
                <div class="screen-card-actions">
                    @if($item['has_code'])
                    <a href="{{ $item['show_url'] }}" class="fc-btn ghost sm">보기</a>
                    @else
                    <a href="{{ $item['show_url'] }}" class="fc-btn secondary sm">생성</a>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- 비용 확인 모달 --}}
    <template x-if="showCostConfirm">
        <div class="confirm-overlay" @click.self="showCostConfirm=false">
            <div class="confirm-box">
                <h3>⚠️ 비용 확인</h3>
                <div class="cost-highlight">~$<span x-text="confirmData.estimatedCost"></span></div>
                <p><span x-text="confirmData.screenCount"></span>개 화면 × $0.80 기준 (실제 비용은 다를 수 있습니다)</p>
                <template x-if="confirmData.warning === 'COST_HIGH'">
                    <div class="confirm-warning">⚠️ 비용이 $5를 초과합니다. 신중하게 진행해주세요.</div>
                </template>
                <p style="font-size:12px;color:#94a3b8;">이 금액은 Anthropic API 비용으로 청구됩니다.</p>
                <div class="confirm-actions">
                    <button class="fc-btn secondary" @click="showCostConfirm=false">취소</button>
                    <button class="fc-btn primary" @click="confirmAndStart()">확인하고 진행</button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function frontendCodeIndex() {
    return {
        cfg: {},
        isGenerating: false,
        onlyMissing: false,
        progress: 0,
        progressDone: 0,
        progressTotal: 0,
        progressLog: [],
        batchResult: null,
        eventSource: null,
        showCostConfirm: false,
        confirmData: {},
        pendingOnlyMissing: false,

        init() {
            const raw = document.getElementById('fc-index-data')?.textContent;
            if (raw) this.cfg = JSON.parse(raw);
        },

        async requestBatch(onlyMissing) {
            if (this.isGenerating) return;
            this.pendingOnlyMissing = onlyMissing;

            try {
                const res = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ only_missing: onlyMissing, confirmed_cost: false }),
                });
                const data = await res.json();

                if (data.requiresConfirmation) {
                    this.confirmData     = data;
                    this.showCostConfirm = true;
                } else if (data.sessionId) {
                    this.startSse(data.sessionId, onlyMissing);
                }
            } catch (e) {
                this.addLog('오류: ' + e.message, 'fail');
            }
        },

        async confirmAndStart() {
            this.showCostConfirm = false;
            const onlyMissing    = this.pendingOnlyMissing;
            this.isGenerating    = true;
            this.progress        = 0;
            this.progressLog     = [];
            this.batchResult     = null;

            try {
                const res = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ only_missing: onlyMissing, confirmed_cost: true }),
                });
                const data = await res.json();
                if (data.sessionId) this.startSse(data.sessionId);
            } catch (e) {
                this.addLog('오류: ' + e.message, 'fail');
                this.isGenerating = false;
            }
        },

        startSse(sessionId) {
            this.isGenerating = true;
            this.eventSource  = new EventSource(this.cfg.batchSseUrlTpl.replace('SESSION_ID', sessionId));

            this.eventSource.addEventListener('status', e => {
                const d = JSON.parse(e.data);
                this.addLog(d.message, 'active');
            });

            this.eventSource.addEventListener('progress', e => {
                const d = JSON.parse(e.data);
                this.progress      = d.progress ?? 0;
                this.progressDone  = d.done  ?? 0;
                this.progressTotal = d.total ?? 0;
                const cls  = d.status === 'done' ? 'ok' : d.status === 'failed' ? 'fail' : 'active';
                const icon = d.status === 'done' ? '✅' : d.status === 'failed' ? '❌' : '🔄';
                this.addLog(`${icon} [${d.screen_id}] ${d.title}`, cls);
            });

            this.eventSource.addEventListener('complete', e => {
                const d = JSON.parse(e.data);
                this.progress     = 100;
                this.batchResult  = d;
                this.isGenerating = false;
                this.addLog(`완료 — ${d.done}개 처리, ${d.elapsed}초`, 'ok');
                this.eventSource?.close();
            });

            this.eventSource.addEventListener('error', e => {
                try { const d = JSON.parse(e.data); this.addLog('오류: ' + d.message, 'fail'); } catch {}
                this.isGenerating = false;
                this.eventSource?.close();
            });
        },

        addLog(text, cls = '') {
            this.progressLog.push({ text, cls });
            this.$nextTick(() => {
                if (this.$refs.logEl) this.$refs.logEl.scrollTop = this.$refs.logEl.scrollHeight;
            });
        },
    };
}
</script>
@endpush
