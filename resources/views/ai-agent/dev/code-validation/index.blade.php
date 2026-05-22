@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────── */
.cv-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.cv-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cv-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Buttons ─────────────────────────────────────────── */
.cv-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.cv-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.cv-btn.primary:hover { background:var(--t700,#6d28d9); }
.cv-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.cv-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cv-btn.secondary:hover { background:#e2e8f0; }
.cv-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.cv-btn.ghost:hover { background:#f5f3ff; }
.cv-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Stats ────────────────────────────────────────────── */
.cv-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-bottom:18px; }
.cv-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; }
.cv-stat-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.cv-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Score ring ──────────────────────────────────────── */
.score-ring { position:relative; width:68px; height:68px; flex-shrink:0; }
.score-ring svg { transform:rotate(-90deg); }
.score-ring-num { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:800; color:#1e1b2e; }

/* ── Section ──────────────────────────────────────────── */
.cv-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:16px; }
.cv-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* ── Pre-condition banner ────────────────────────────── */
.cv-precond { background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 18px; margin-bottom:16px; display:flex; align-items:flex-start; gap:12px; }
.cv-precond.warn { background:#fffbeb; border-color:#fde68a; }
.cv-precond.error { background:#fff1f2; border-color:#fecaca; }
.cv-precond-icon { font-size:18px; flex-shrink:0; }
.cv-precond-text { font-size:13px; color:#334155; }
.cv-precond-text strong { font-weight:700; display:block; margin-bottom:2px; }

/* ── Start banner ────────────────────────────────────── */
.cv-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; margin-bottom:16px; }
.cv-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.cv-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.cv-banner-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.cv-start-btn { background:#fff; color:#6d28d9; border:none; border-radius:9px; padding:9px 20px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.cv-start-btn:hover { background:#f5f3ff; }
.cv-start-btn:disabled { opacity:.5; cursor:not-allowed; }

/* ── Cost confirm modal ─────────────────────────────── */
.confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.confirm-box { background:#fff; border-radius:16px; padding:28px; max-width:420px; width:100%; }
.confirm-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 8px; }
.confirm-box .cost-highlight { font-size:28px; font-weight:800; color:#7c3aed; margin:12px 0; }
.confirm-box p { font-size:13px; color:#64748b; margin:0 0 12px; }
.confirm-warning { background:#fffbeb; border:1.5px solid #fde68a; border-radius:8px; padding:10px 14px; font-size:12.5px; color:#92400e; margin-bottom:16px; }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; }

/* ── Progress ────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:10px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.progress-log { background:#0f0f1a; border-radius:10px; padding:12px 14px; max-height:180px; overflow-y:auto; font-family:monospace; font-size:12px; color:#94a3b8; }
.progress-log-line.ok     { color:#4ade80; }
.progress-log-line.fail   { color:#f87171; }
.progress-log-line.active { color:#c4b5fd; }

/* ── Screen grid ──────────────────────────────────────── */
.cv-screen-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px; }
.cv-screen-card { border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; background:#fff; display:flex; flex-direction:column; gap:6px; transition:border-color .15s; }
.cv-screen-card:hover { border-color:#c4b5fd; }
.cv-screen-card.validated { border-color:#bbf7d0; background:#f0fdf4; }
.cv-screen-card.pending   { border-color:#fde68a; }
.cv-screen-card.no-code   { border-color:#e2e8f0; background:#fafafa; opacity:.7; }
.cv-screen-card-id    { font-size:11px; font-weight:700; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:1px 7px; display:inline-block; }
.cv-screen-card-title { font-size:13px; font-weight:700; color:#1e1b2e; }
.cv-score-badge { font-size:13px; font-weight:800; padding:2px 10px; border-radius:99px; display:inline-block; }
.cv-score-badge.high   { background:#dcfce7; color:#15803d; }
.cv-score-badge.mid    { background:#fef9c3; color:#854d0e; }
.cv-score-badge.low    { background:#fee2e2; color:#b91c1c; }
.cv-screen-card-meta  { font-size:11.5px; color:#64748b; }
.cv-violation-row { display:flex; gap:5px; align-items:center; flex-wrap:wrap; }
.cv-vbadge { font-size:10.5px; font-weight:700; padding:1px 7px; border-radius:99px; }
.cv-vbadge.critical { background:#fee2e2; color:#b91c1c; }
.cv-vbadge.warning  { background:#fef3c7; color:#92400e; }
.cv-vbadge.info     { background:#eff6ff; color:#1d4ed8; }
.cv-screen-card-actions { display:flex; gap:5px; flex-wrap:wrap; margin-top:2px; }

@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="cv-index-data">
{
    "batchStartUrl": "{{ $batchStartUrl }}",
    "batchSseUrlTpl": "{{ $batchSseUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="cvIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="cv-header">
        <div class="cv-header-left">
            <h1>Output 검증</h1>
            <p>T40에서 생성된 Frontend 코드를 정적 분석 + 웍스로 자동 검수합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            @if($validatedCount > 0)
            <a href="{{ $exportUrl }}" class="cv-btn ghost sm">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Markdown 내보내기
            </a>
            @endif
        </div>
    </div>

    {{-- 사전 조건 --}}
    <div class="cv-precond {{ $codeCount < $totalCount ? 'warn' : '' }}">
        <div class="cv-precond-icon">{{ $codeCount >= $totalCount ? '✅' : '⚠️' }}</div>
        <div class="cv-precond-text">
            <strong>Frontend 코드 (T40)</strong>
            {{ $codeCount }}개 / {{ $totalCount }}개 생성됨
            @if($codeCount < $totalCount)
            — {{ $totalCount - $codeCount }}개 화면에 코드가 없습니다. T40을 먼저 실행하세요.
            @endif
        </div>
    </div>
    <div class="cv-precond {{ !$staticAvailable ? 'warn' : '' }}" style="margin-bottom:0;">
        <div class="cv-precond-icon">{{ $staticAvailable ? '✅' : '⚠️' }}</div>
        <div class="cv-precond-text">
            <strong>정적 분석 (Node.js)</strong>
            @if($staticAvailable)
            Node.js 감지됨 — 정적 분석 활성화
            @else
            Node.js 미감지 — 웍스 검수만 진행됩니다
            @endif
        </div>
    </div>

    {{-- 통계 --}}
    <div class="cv-stats" style="margin-top:16px;">
        <div class="cv-stat">
            <div class="cv-stat-label">전체 화면</div>
            <div class="cv-stat-value">{{ $totalCount }}</div>
        </div>
        <div class="cv-stat">
            <div class="cv-stat-label">코드 준비</div>
            <div class="cv-stat-value" style="color:#7c3aed;">{{ $codeCount }}</div>
        </div>
        <div class="cv-stat">
            <div class="cv-stat-label">검증 완료</div>
            <div class="cv-stat-value" style="color:#16a34a;">{{ $validatedCount }}</div>
        </div>
        <div class="cv-stat">
            <div class="cv-stat-label">평균 점수</div>
            <div class="cv-stat-value" style="color:{{ $avgScore !== null ? ($avgScore >= 80 ? '#16a34a' : ($avgScore >= 60 ? '#d97706' : '#b91c1c')) : '#94a3b8' }};">
                {{ $avgScore !== null ? $avgScore : '—' }}
            </div>
        </div>
    </div>

    {{-- 검증 시작 배너 --}}
    @if($codeCount > 0)
    <div class="cv-banner">
        <div class="cv-banner-text">
            <h3>🔍 웍스 Output 일괄 검증</html>
            <p>화면당 평균 ~$0.30 · {{ $codeCount }}개 코드 = ~${{ $estimatedCost }}
                @if(!$staticAvailable) · ⚠️ 웍스 검수만 진행@endif
            </p>
        </div>
        <div class="cv-banner-actions">
            <button class="cv-start-btn" :disabled="isValidating" @click="requestBatch()">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="isValidating ? 'animation:spin 1s linear infinite':''"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                <span x-text="isValidating ? '검증 중...' : '전체 검증 시작'"></span>
            </button>
        </div>
    </div>
    @endif

    {{-- SSE 진행 --}}
    <template x-if="isValidating || batchResult">
        <div class="cv-section">
            <div class="cv-section-title">
                <span x-text="isValidating ? '검증 진행 중...' : '검증 완료'"></span>
                <span x-show="isValidating" style="font-size:11px;font-weight:400;color:#64748b;" x-text="`${progressDone}/${progressTotal}`"></span>
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
                <div style="margin-top:12px;display:flex;gap:12px;flex-wrap:wrap;font-size:13px;align-items:center;">
                    <span>✅ 완료 <strong x-text="batchResult.done - batchResult.failed"></strong>개</span>
                    <span x-show="batchResult.failed > 0" style="color:#b91c1c;">❌ 실패 <strong x-text="batchResult.failed"></strong>개</span>
                    <span style="color:#64748b;">⏱ <span x-text="batchResult.elapsed"></span>초</span>
                    <button class="cv-btn secondary sm" @click="batchResult=null;progressLog=[];progress=0;" style="margin-left:auto;">닫기</button>
                    <button class="cv-btn primary sm" @click="window.location.reload()">새로고침</button>
                </div>
            </template>
        </div>
    </template>

    {{-- 화면별 결과 --}}
    <div class="cv-section">
        <div class="cv-section-title">
            화면별 검증 결과
            <span style="font-size:12px;font-weight:400;color:#94a3b8;">{{ $totalCount }}개</span>
        </div>

        @if($totalCount === 0)
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
            <p style="font-size:15px;font-weight:600;margin-bottom:8px;">화면이 없습니다</p>
        </div>
        @else
        <div class="cv-screen-grid">
            @foreach($screenData as $item)
            @php $screen = $item['screen']; @endphp
            <div class="cv-screen-card {{ $item['has_validation'] ? 'validated' : ($item['has_code'] ? 'pending' : 'no-code') }}">
                <div>
                    <span class="cv-screen-card-id">{{ $screen->screen_id }}</span>
                    @if(!$item['has_code'])
                    <span style="font-size:10px;background:#fee2e2;color:#b91c1c;border-radius:99px;padding:1px 6px;margin-left:4px;">코드 없음</span>
                    @endif
                </div>
                <div class="cv-screen-card-title">{{ $screen->title }}</div>

                @if($item['has_validation'])
                @php
                    $score = $item['compliance_score'];
                    $scoreClass = $score >= 80 ? 'high' : ($score >= 60 ? 'mid' : 'low');
                @endphp
                <div style="display:flex;align-items:center;gap:8px;">
                    <span class="cv-score-badge {{ $scoreClass }}">{{ $score }}점</span>
                    <div class="cv-violation-row">
                        @if($item['critical_count'] > 0)
                        <span class="cv-vbadge critical">🔴 {{ $item['critical_count'] }}건</span>
                        @endif
                        @php $warningCount = $item['violations_count'] - $item['critical_count']; @endphp
                        @if($warningCount > 0)
                        <span class="cv-vbadge warning">🟡 {{ $warningCount }}건</span>
                        @endif
                        @if($item['violations_count'] === 0)
                        <span style="font-size:11.5px;color:#16a34a;font-weight:600;">위반 없음 ✅</span>
                        @endif
                    </div>
                </div>
                @if($item['validated_at'])
                <div class="cv-screen-card-meta">{{ \Carbon\Carbon::parse($item['validated_at'])->format('m/d H:i') }}</div>
                @endif
                @elseif($item['has_code'])
                <div style="font-size:12px;color:#d97706;font-weight:600;">⏳ 미검증</div>
                @else
                <div style="font-size:12px;color:#94a3b8;">T40 먼저 실행 필요</div>
                @endif

                <div class="cv-screen-card-actions">
                    @if($item['has_code'])
                    <a href="{{ $item['show_url'] }}" class="cv-btn {{ $item['has_validation'] ? 'ghost' : 'secondary' }} sm">
                        {{ $item['has_validation'] ? '상세' : '검증' }}
                    </a>
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
                <h3>⚠️ 검증 비용 확인</h3>
                <div class="cost-highlight">~$<span x-text="confirmData.estimatedCost"></span></div>
                <p><span x-text="confirmData.screenCount"></span>개 화면 × $0.30 기준 (실제 비용은 다를 수 있습니다)</p>
                <template x-if="confirmData.warning === 'COST_HIGH'">
                    <div class="confirm-warning">⚠️ 비용이 $5를 초과합니다.</div>
                </template>
                <div class="confirm-actions">
                    <button class="cv-btn secondary" @click="showCostConfirm=false">취소</button>
                    <button class="cv-btn primary" @click="confirmAndStart()">확인하고 시작</button>
                </div>
            </div>
        </div>
    </template>

</div>
@endsection

@push('scripts')
<script>
function cvIndex() {
    return {
        cfg: {},
        isValidating: false,
        progress: 0,
        progressDone: 0,
        progressTotal: 0,
        progressLog: [],
        batchResult: null,
        eventSource: null,
        showCostConfirm: false,
        confirmData: {},

        init() {
            const raw = document.getElementById('cv-index-data')?.textContent;
            if (raw) this.cfg = JSON.parse(raw);
        },

        async requestBatch() {
            if (this.isValidating) return;
            try {
                const res  = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ confirmed_cost: false }),
                });
                const data = await res.json();
                if (data.requiresConfirmation) {
                    this.confirmData     = data;
                    this.showCostConfirm = true;
                } else if (data.sessionId) {
                    this.startSse(data.sessionId);
                }
            } catch (e) {
                this.addLog('오류: ' + e.message, 'fail');
            }
        },

        async confirmAndStart() {
            this.showCostConfirm = false;
            this.isValidating    = true;
            this.progress        = 0;
            this.progressLog     = [];
            this.batchResult     = null;

            try {
                const res  = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ confirmed_cost: true }),
                });
                const data = await res.json();
                if (data.sessionId) this.startSse(data.sessionId);
            } catch (e) {
                this.addLog('오류: ' + e.message, 'fail');
                this.isValidating = false;
            }
        },

        startSse(sessionId) {
            this.isValidating = true;
            const url = this.cfg.batchSseUrlTpl.replace('SESSION_ID', sessionId);
            this.eventSource  = new EventSource(url);

            this.eventSource.addEventListener('start', e => {
                const d = JSON.parse(e.data);
                this.progressTotal = d.total || 0;
                this.addLog(d.message || '검증 시작', 'active');
            });

            this.eventSource.addEventListener('screen_start', e => {
                const d = JSON.parse(e.data);
                this.progress     = d.progress || 0;
                this.progressDone = d.done || 0;
                this.addLog(`🔍 [${d.screen_id}] ${d.title}`, 'active');
            });

            this.eventSource.addEventListener('screen_done', e => {
                const d = JSON.parse(e.data);
                this.progress     = d.progress || 0;
                this.progressDone = d.done || 0;
                const icon = d.status === 'done' ? '✅' : '❌';
                const info = d.status === 'done' ? `${d.compliance_score}점 · 위반 ${d.violations_count}건` : '';
                this.addLog(`${icon} [${d.screen_id}] ${d.title} ${info}`, d.status === 'done' ? 'ok' : 'fail');
            });

            this.eventSource.addEventListener('screen_error', e => {
                const d = JSON.parse(e.data);
                this.addLog(`❌ [${d.screen_id}] 오류: ${d.error}`, 'fail');
            });

            this.eventSource.addEventListener('complete', e => {
                const d = JSON.parse(e.data);
                this.progress     = 100;
                this.batchResult  = d;
                this.isValidating = false;
                this.addLog(`완료 — ${d.done}개 처리, ${d.elapsed}초`, 'ok');
                this.eventSource?.close();
            });

            this.eventSource.addEventListener('error', e => {
                try { const d = JSON.parse(e.data); this.addLog('오류: ' + d.message, 'fail'); } catch {}
                this.isValidating = false;
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
