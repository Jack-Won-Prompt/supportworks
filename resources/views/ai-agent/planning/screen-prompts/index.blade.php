@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.sp-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.sp-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.sp-header p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.sp-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.sp-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.sp-btn.primary:hover { background:var(--t700,#6d28d9); }
.sp-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.sp-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.sp-btn.secondary:hover { background:#e2e8f0; }
.sp-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.sp-btn.ghost:hover { background:#f5f3ff; }
.sp-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Section card ────────────────────────────────────────────────── */
.sp-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.sp-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:7px; }

/* ── Summary cards ───────────────────────────────────────────────── */
.sp-summary-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:16px; }
.sp-summary-card { background:#f8f5ff; border:1.5px solid #ede8ff; border-radius:10px; padding:12px 14px; }
.sp-summary-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.sp-summary-value { font-size:20px; font-weight:800; color:#1e1b2e; margin-top:2px; }

/* ── Screen table ────────────────────────────────────────────────── */
.sp-table { width:100%; border-collapse:collapse; font-size:13px; }
.sp-table th { padding:9px 12px; text-align:left; font-size:11.5px; font-weight:700; color:#64748b; text-transform:uppercase; letter-spacing:.04em; border-bottom:1.5px solid #ede8ff; }
.sp-table td { padding:10px 12px; border-bottom:1px solid #f1f5f9; vertical-align:middle; }
.sp-table tr:last-child td { border-bottom:none; }
.sp-table tr:hover td { background:#fdfcff; }

/* ── Status badges ───────────────────────────────────────────────── */
.sp-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; border-radius:99px; font-size:11.5px; font-weight:600; }
.sp-badge.done    { background:#dcfce7; color:#15803d; }
.sp-badge.user    { background:#dbeafe; color:#1d4ed8; }
.sp-badge.missing { background:#f1f5f9; color:#64748b; }
.sp-badge.busy    { background:#fef3c7; color:#92400e; }

/* ── Progress ────────────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:10px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.sp-progress-list { list-style:none; margin:0; padding:0; max-height:300px; overflow-y:auto; }
.sp-progress-list li { display:flex; align-items:center; gap:8px; padding:6px 8px; border-radius:8px; font-size:12.5px; }
.sp-progress-list li:hover { background:#fdfcff; }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="sp-data">
{
    "batchStartUrl": "{{ $batchStartUrl }}",
    "batchSseUrlTpl": "{{ $batchSseUrlTpl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "csrfToken": "{{ $csrfToken }}",
    "totalCount": {{ $totalCount }},
    "missingIds": {{ json_encode($missingIds) }}
}
</script>

<div x-data="spIndex()" x-init="init()">

    {{-- ── 헤더 ──────────────────────────────────────────────────────────── --}}
    <div class="sp-header">
        <div>
            <h1>화면 생성 프롬프트</h1>
            <p>각 화면(SCR-XXX)의 웍스 목업 생성용 프롬프트를 자동 작성합니다.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <button class="sp-btn secondary" @click="startBatch(false)"
                    :disabled="isBatching || {{ $totalCount }} === 0">
                전체 일괄 생성
            </button>
            <button class="sp-btn primary" @click="startBatch(true)"
                    :disabled="isBatching || missingIds.length === 0"
                    x-text="`미생성만 (${missingIds.length}건)`">
            </button>
        </div>
    </div>

    {{-- ── 요약 카드 ─────────────────────────────────────────────────────── --}}
    <div class="sp-section">
        <div class="sp-summary-grid">
            <div class="sp-summary-card">
                <div class="sp-summary-label">전체 화면</div>
                <div class="sp-summary-value">{{ $totalCount }}</div>
            </div>
            <div class="sp-summary-card">
                <div class="sp-summary-label">프롬프트 완료</div>
                <div class="sp-summary-value">{{ $promptCount }}</div>
            </div>
            <div class="sp-summary-card">
                <div class="sp-summary-label">미생성</div>
                <div class="sp-summary-value">{{ $totalCount - $promptCount }}</div>
            </div>
            <div class="sp-summary-card">
                <div class="sp-summary-label">프론트엔드 스택</div>
                <div class="sp-summary-value" style="font-size:14px;">{{ $stackLabel }}</div>
            </div>
        </div>

        @if($totalCount > 0)
        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" style="width:{{ $totalCount > 0 ? round(($promptCount / $totalCount) * 100) : 0 }}%"></div>
        </div>
        <div style="font-size:12px;color:#64748b;">
            완료율 {{ $totalCount > 0 ? round(($promptCount / $totalCount) * 100) : 0 }}%
        </div>
        @endif
    </div>

    {{-- ── 일괄 생성 진행 패널 ──────────────────────────────────────────── --}}
    <template x-if="isBatching || batchDone">
        <div class="sp-section">
            <div class="sp-section-title">
                <template x-if="isBatching">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="animation:spin 1s linear infinite"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </template>
                <template x-if="!isBatching && batchDone">
                    <span style="color:#22c55e;">✓</span>
                </template>
                <span x-text="isBatching ? '일괄 생성 중...' : '일괄 생성 완료'"></span>
            </div>

            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${batchPct}%`"></div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-bottom:12px;" x-text="`${batchProgress.done || 0} / ${batchProgress.total || 0} 완료`"></div>

            <ul class="sp-progress-list">
                <template x-for="s in batchItems" :key="s.screen_id">
                    <li>
                        <span style="width:18px;text-align:center;flex-shrink:0;">
                            <template x-if="s.status === 'pending'">  <span style="color:#cbd5e1;">○</span></template>
                            <template x-if="s.status === 'processing'"><span style="color:#a78bfa;animation:pulse 1s infinite;">◉</span></template>
                            <template x-if="s.status === 'done'">     <span style="color:#22c55e;">✓</span></template>
                            <template x-if="s.status === 'failed'">   <span style="color:#ef4444;">✗</span></template>
                        </span>
                        <span style="color:#94a3b8;font-family:monospace;font-size:11.5px;" x-text="s.screen_id"></span>
                        <span style="flex:1;color:#374151;" x-text="s.title"></span>
                    </li>
                </template>
            </ul>

            <template x-if="batchDone">
                <div style="margin-top:12px;padding:10px 14px;background:#f0fdf4;border:1.5px solid #86efac;border-radius:8px;font-size:12.5px;color:#15803d;">
                    완료: <strong x-text="batchStats.total"></strong>건 처리 |
                    실패: <strong x-text="batchStats.failed_count"></strong>건 |
                    비용: $<strong x-text="(batchStats.cost_usd || 0).toFixed(4)"></strong>
                    <button class="sp-btn ghost sm" style="margin-left:10px;" onclick="window.location.reload()">새로고침</button>
                </div>
            </template>

            <template x-if="isBatching">
                <div style="margin-top:10px;text-align:right;">
                    <button class="sp-btn secondary sm" @click="cancelBatch()">취소</button>
                </div>
            </template>
        </div>
    </template>

    {{-- ── 화면 목록 ─────────────────────────────────────────────────────── --}}
    <div class="sp-section">
        <div class="sp-section-title">화면별 프롬프트 현황</div>

        @if($screens->isEmpty())
            <div style="text-align:center;padding:40px;color:#94a3b8;font-size:13.5px;">
                등록된 화면이 없습니다. 먼저 화면을 등록해주세요.
            </div>
        @else
        <table class="sp-table">
            <thead>
                <tr>
                    <th>화면 ID</th>
                    <th>화면명</th>
                    <th>프롬프트 상태</th>
                    <th style="text-align:right;">액션</th>
                </tr>
            </thead>
            <tbody>
                @foreach($screens as $screen)
                    @php
                        $artifact = $artifacts->get($screen->id);
                        $hasPrompt = $artifact !== null;
                        $changeType = $artifact?->meta['change_type'] ?? null;
                    @endphp
                    <tr>
                        <td>
                            <span style="font-family:monospace;font-size:12px;color:#7c3aed;font-weight:700;">{{ $screen->screen_id }}</span>
                        </td>
                        <td style="color:#1e1b2e;font-weight:600;">{{ $screen->title }}</td>
                        <td>
                            @if($hasPrompt)
                                @if($changeType === 'user_edited')
                                    <span class="sp-badge user">✏️ v{{ $artifact->version }} 사용자 편집</span>
                                @else
                                    <span class="sp-badge done">✅ v{{ $artifact->version }} 웍스 생성</span>
                                @endif
                            @else
                                <span class="sp-badge missing">⬜ 미생성</span>
                            @endif
                        </td>
                        <td style="text-align:right;">
                            <div style="display:inline-flex;gap:6px;align-items:center;">
                                @if($hasPrompt)
                                    <a href="{{ route('ai-agent.projects.planning.prompts.show', [$project, $screen]) }}"
                                       class="sp-btn ghost sm">편집</a>
                                    <button class="sp-btn secondary sm"
                                            data-screen-id="{{ $screen->id }}"
                                            data-generate-url="{{ route('ai-agent.projects.planning.prompts.generate', [$project, $screen]) }}"
                                            onclick="generateOne(this)">재생성</button>
                                @else
                                    <button class="sp-btn primary sm"
                                            data-screen-id="{{ $screen->id }}"
                                            data-generate-url="{{ route('ai-agent.projects.planning.prompts.generate', [$project, $screen]) }}"
                                            onclick="generateOne(this)">생성</button>
                                @endif
                                <a href="{{ route('ai-agent.projects.planning.mockups', $project) }}"
                                   class="sp-btn ghost sm"
                                   style="{{ !$hasPrompt ? 'opacity:.4;pointer-events:none;' : '' }}"
                                   title="목업 생성 (T25)">목업 →</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</div>

@push('scripts')
<style>
@keyframes spin { to { transform:rotate(360deg); } }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.4;} }
</style>
<script>
const SP_CSRF = "{{ csrf_token() }}";

async function generateOne(btn) {
    const url = btn.dataset.generateUrl;
    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '생성 중...';
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': SP_CSRF },
        });
        const json = await res.json();
        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || '생성 실패');
            btn.disabled = false;
            btn.textContent = origText;
        }
    } catch(e) {
        alert('오류: ' + e.message);
        btn.disabled = false;
        btn.textContent = origText;
    }
}

function spIndex() {
    const cfg = JSON.parse(document.getElementById('sp-data').textContent);
    return {
        cfg,
        isBatching: false,
        batchDone: false,
        batchItems: [],
        batchProgress: {},
        batchStats: {},
        missingIds: cfg.missingIds,
        _es: null,

        get batchPct() {
            const t = this.batchProgress.total || 0;
            const d = this.batchProgress.done  || 0;
            return t > 0 ? Math.min(100, Math.round((d / t) * 100)) : 0;
        },

        init() {},

        async startBatch(onlyMissing) {
            if (this.isBatching) return;
            this.isBatching = true;
            this.batchDone  = false;
            this.batchStats = {};

            let res, json;
            try {
                res  = await fetch(this.cfg.batchStartUrl, {
                    method:  'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body:    JSON.stringify({ only_missing: onlyMissing }),
                });
                json = await res.json();
                if (!json.success) { alert(json.message || '시작 실패'); this.isBatching = false; return; }
            } catch(e) { alert('오류: ' + e.message); this.isBatching = false; return; }

            this.batchProgress = { done: 0, total: json.totalScreens };
            this.batchItems    = [];

            const sseUrl = this.cfg.batchSseUrlTpl.replace('SESSION_ID', json.sessionId);
            const es     = new EventSource(sseUrl);
            this._es     = es;

            es.addEventListener('screen_progress', e => {
                const d = JSON.parse(e.data);
                this.batchProgress = { done: d.done, total: d.total };
                const idx = this.batchItems.findIndex(s => s.screen_id === d.screen_id);
                if (idx >= 0) {
                    this.batchItems[idx] = { ...this.batchItems[idx], status: d.status };
                } else {
                    this.batchItems.push({ screen_id: d.screen_id, title: d.title, status: d.status });
                }
            });

            es.addEventListener('complete', e => {
                es.close(); this._es = null;
                this.isBatching = false;
                this.batchDone  = true;
                this.batchStats = JSON.parse(e.data);
                this.batchProgress.done = this.batchProgress.total;
                this.batchItems.forEach(s => { if (s.status !== 'failed') s.status = 'done'; });
            });

            es.addEventListener('error', e => {
                es.close(); this._es = null;
                this.isBatching = false;
                let msg = '일괄 생성 중 오류';
                try { msg = JSON.parse(e.data).message || msg; } catch {}
                alert(msg);
            });

            es.onerror = () => {
                if (es.readyState === EventSource.CLOSED) { this.isBatching = false; }
            };
        },

        cancelBatch() {
            if (this._es) { this._es.close(); this._es = null; }
            this.isBatching = false;
        },
    };
}
</script>
@endpush

@endsection
