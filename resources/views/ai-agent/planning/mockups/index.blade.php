@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.mk-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:20px; flex-wrap:wrap; }
.mk-header-left { flex:1; min-width:0; }
.mk-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.mk-header-left p  { font-size:13px; color:#64748b; margin:0; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.mk-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; }
.mk-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.mk-btn.primary:hover { background:var(--t700,#6d28d9); }
.mk-btn.primary:disabled { opacity:.4; cursor:not-allowed; pointer-events:none; }
.mk-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.mk-btn.secondary:hover { background:#e2e8f0; }
.mk-btn.ghost { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.mk-btn.ghost:hover { background:#f5f3ff; }
.mk-btn.sm { padding:4px 10px; font-size:12px; }
.mk-btn.danger { background:#fef2f2; color:#dc2626; border:1.5px solid #fca5a5; }

/* ── Summary cards ───────────────────────────────────────────────── */
.mk-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:10px; margin-bottom:18px; }
.mk-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; text-align:center; }
.mk-stat-label { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.04em; margin-bottom:4px; }
.mk-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; }

/* ── Grid ────────────────────────────────────────────────────────── */
.mk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(240px,1fr)); gap:14px; }
.mk-card { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; overflow:hidden; display:flex; flex-direction:column; }
.mk-card-header { padding:10px 12px 8px; display:flex; align-items:center; gap:7px; border-bottom:1px solid #f1f5f9; }
.mk-card-id { font-family:monospace; font-size:11.5px; font-weight:700; color:#7c3aed; background:#f8f5ff; padding:2px 7px; border-radius:5px; }
.mk-card-title { font-size:12.5px; font-weight:700; color:#1e1b2e; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.mk-card-thumb { position:relative; height:140px; background:#f8f9fa; overflow:hidden; }
.mk-card-thumb iframe { position:absolute; top:0; left:0; width:200%; height:200%; border:none; transform:scale(.5); transform-origin:top left; pointer-events:none; }
.mk-card-empty { height:140px; display:flex; flex-direction:column; align-items:center; justify-content:center; background:#f8f5ff; gap:6px; }
.mk-card-empty-icon { font-size:28px; opacity:.4; }
.mk-card-empty-text { font-size:11.5px; color:#94a3b8; }
.mk-card-footer { padding:8px 10px; display:flex; align-items:center; gap:6px; flex-wrap:wrap; border-top:1px solid #f1f5f9; }
.mk-badge { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:700; }
.mk-badge.done { background:#dcfce7; color:#15803d; }
.mk-badge.missing { background:#f1f5f9; color:#64748b; }
.mk-badge.user { background:#dbeafe; color:#1d4ed8; }

/* ── Batch panel ─────────────────────────────────────────────────── */
.mk-batch-panel { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:18px; }
.mk-batch-title { font-size:14px; font-weight:700; color:#1e1b2e; margin:0 0 12px; }
.mk-progress-bar-bg { background:#ede8ff; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.mk-progress-bar { background:linear-gradient(90deg,#7c3aed,#a78bfa); height:8px; border-radius:99px; transition:width .3s; }
.mk-batch-list { max-height:260px; overflow-y:auto; display:flex; flex-direction:column; gap:4px; }
.mk-batch-row { display:flex; align-items:center; gap:8px; padding:5px 8px; border-radius:7px; font-size:12.5px; }
.mk-batch-row.done { background:#f0fdf4; }
.mk-batch-row.processing { background:#fef3c7; }
.mk-batch-row.failed { background:#fef2f2; }
.mk-batch-row.waiting { background:#f8f9fa; color:#94a3b8; }
</style>
@endpush

@section('ai-agent-content')

<script type="application/json" id="mk-data">
{
    "batchStartUrl":  "{{ $batchStartUrl }}",
    "batchSseUrlTpl": "{{ $batchSseUrlTpl }}",
    "csrfToken":      "{{ $csrfToken }}"
}
</script>

<div x-data="mkIndex()" x-init="init()">

    {{-- ── 헤더 ──────────────────────────────────────────────────────────── --}}
    <div class="mk-header">
        <div class="mk-header-left">
            <h1>웍스 샘플 화면 (목업)</h1>
            <p>화면 프롬프트(T24)를 기반으로 {{ $stackLabel }} 코드를 자동 생성합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button class="mk-btn ghost" @click="startBatch(true)" :disabled="isBatching">
                <span x-text="isBatching ? '생성 중...' : '미생성만 일괄 생성'"></span>
            </button>
            <button class="mk-btn primary" @click="startBatch(false)" :disabled="isBatching">
                <span x-text="isBatching ? '생성 중...' : '전체 일괄 생성'"></span>
            </button>
        </div>
    </div>

    {{-- ── 요약 카드 ──────────────────────────────────────────────────────── --}}
    <div class="mk-summary">
        <div class="mk-stat">
            <div class="mk-stat-label">전체 화면</div>
            <div class="mk-stat-value">{{ $totalCount }}</div>
        </div>
        <div class="mk-stat">
            <div class="mk-stat-label">목업 완료</div>
            <div class="mk-stat-value" style="color:#15803d;">{{ $mockupCount }}</div>
        </div>
        <div class="mk-stat">
            <div class="mk-stat-label">미생성</div>
            <div class="mk-stat-value" style="color:#dc2626;">{{ $totalCount - $mockupCount }}</div>
        </div>
        <div class="mk-stat">
            <div class="mk-stat-label">스택</div>
            <div class="mk-stat-value" style="font-size:14px;">{{ $stackLabel }}</div>
        </div>
    </div>

    {{-- ── 일괄 생성 진행 패널 ──────────────────────────────────────────── --}}
    <template x-if="isBatching || batchDone">
        <div class="mk-batch-panel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <div class="mk-batch-title" x-text="batchDone ? '목업 생성 완료' : '목업 일괄 생성 중...'"></div>
                <template x-if="isBatching">
                    <button class="mk-btn danger sm" @click="cancelBatch()">취소</button>
                </template>
            </div>
            <div class="mk-progress-bar-bg">
                <div class="mk-progress-bar" :style="`width:${progressPct}%`"></div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-bottom:8px;" x-text="`${progressPct}% 완료`"></div>
            <div class="mk-batch-list">
                <template x-for="row in batchProgress" :key="row.screen_id">
                    <div class="mk-batch-row" :class="row.status">
                        <span style="font-size:14px;" x-text="row.status === 'done' ? '✅' : row.status === 'failed' ? '❌' : row.status === 'processing' ? '🔄' : '⏳'"></span>
                        <span style="font-family:monospace;font-size:11px;font-weight:700;color:#7c3aed;" x-text="row.screen_id"></span>
                        <span x-text="row.title"></span>
                        <span style="margin-left:auto;font-size:11px;color:#94a3b8;" x-text="row.status === 'processing' ? '생성 중...' : row.status === 'failed' ? '실패' : row.status === 'done' ? '완료' : '대기'"></span>
                    </div>
                </template>
            </div>
            <template x-if="batchStats">
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f1f5f9;font-size:12px;color:#64748b;">
                    완료: <strong x-text="batchStats.total"></strong>개
                    | 실패: <strong x-text="batchStats.failed_count"></strong>개
                    | 비용: <strong x-text="'$' + (batchStats.cost_usd || 0).toFixed(4)"></strong>
                    | 소요: <strong x-text="batchStats.elapsed + '초'"></strong>
                </div>
            </template>
        </div>
    </template>

    {{-- ── 화면 그리드 ────────────────────────────────────────────────────── --}}
    @if($screens->isEmpty())
        <div style="background:#f8f5ff;border:1.5px dashed #c4b5fd;border-radius:12px;padding:40px;text-align:center;">
            <div style="font-size:14px;font-weight:700;color:#1e1b2e;margin-bottom:6px;">등록된 화면이 없습니다</div>
            <div style="font-size:13px;color:#64748b;">T16 화면 관리에서 화면을 먼저 등록하세요.</div>
        </div>
    @else
        <div class="mk-grid">
            @foreach($screens as $screen)
                @php
                    $artifact = $artifacts->get($screen->id);
                    $data     = $artifact ? json_decode($artifact->content, true) : null;
                    $meta     = $artifact?->meta ?? [];
                    $changeType = $meta['change_type'] ?? 'ai_generated';
                @endphp
                <div class="mk-card">
                    <div class="mk-card-header">
                        <span class="mk-card-id">{{ $screen->screen_id }}</span>
                        <span class="mk-card-title" title="{{ $screen->title }}">{{ $screen->title }}</span>
                    </div>

                    {{-- 썸네일 --}}
                    @if($artifact)
                        <div class="mk-card-thumb">
                            <iframe
                                src="{{ route('ai-agent.projects.planning.mockups.preview', [$project, $screen]) }}"
                                loading="lazy"
                                sandbox="allow-scripts"
                                title="{{ $screen->title }} 미리보기">
                            </iframe>
                        </div>
                    @else
                        <div class="mk-card-empty">
                            <div class="mk-card-empty-icon">🖼️</div>
                            <div class="mk-card-empty-text">목업 미생성</div>
                        </div>
                    @endif

                    <div class="mk-card-footer">
                        @if($artifact)
                            @if($changeType === 'user_edited')
                                <span class="mk-badge user">✏️ v{{ $artifact->version }}</span>
                            @else
                                <span class="mk-badge done">✓ v{{ $artifact->version }}</span>
                            @endif
                            <a href="{{ route('ai-agent.projects.planning.mockups.show', [$project, $screen]) }}" class="mk-btn secondary sm" style="margin-left:auto;">편집</a>
                        @else
                            <span class="mk-badge missing">미생성</span>
                            <button
                                class="mk-btn primary sm"
                                style="margin-left:auto;"
                                onclick="generateOneMockup(this, '{{ route('ai-agent.projects.planning.mockups.generate', [$project, $screen]) }}', '{{ csrf_token() }}')"
                            >생성</button>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
<script>
async function mkIndex() {
    const cfg = JSON.parse(document.getElementById('mk-data').textContent);

    return {
        cfg,
        isBatching: false,
        batchDone: false,
        batchProgress: [],
        progressPct: 0,
        batchStats: null,
        _es: null,

        init() {},

        async startBatch(onlyMissing) {
            if (this.isBatching) return;

            // 1차 요청: 비용 추정 확인
            const res1 = await fetch(this.cfg.batchStartUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                body: JSON.stringify({ only_missing: onlyMissing }),
            });
            const j1 = await res1.json();

            if (!res1.ok || j1.message) {
                if (j1.message) alert(j1.message);
                return;
            }

            if (j1.requires_confirmation) {
                const ok = await __confirm(
                    `${j1.screen_count}개 화면 목업 생성\n예상 비용: $${(j1.estimated_cost || 0).toFixed(2)}\n\n계속하시겠습니까?`
                );
                if (!ok) return;

                // 2차 요청: confirmed_cost: true
                const res2 = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.cfg.csrfToken },
                    body: JSON.stringify({ only_missing: onlyMissing, confirmed_cost: true }),
                });
                const j2 = await res2.json();
                if (j2.success) {
                    this.connectSse(j2.session_id);
                } else {
                    alert(j2.message || '시작 실패');
                }
                return;
            }

            if (j1.success) {
                this.connectSse(j1.session_id);
            } else {
                alert(j1.message || '시작 실패');
            }
        },

        connectSse(sessionId) {
            this.isBatching = true;
            this.batchDone = false;
            this.batchProgress = [];
            this.progressPct = 0;
            this.batchStats = null;

            const url = this.cfg.batchSseUrlTpl.replace('SESSION_ID', sessionId);
            this._es = new EventSource(url);

            this._es.addEventListener('screen_progress', e => {
                const d = JSON.parse(e.data);
                const idx = this.batchProgress.findIndex(p => p.screen_id === d.screen_id);
                if (idx >= 0) this.batchProgress[idx] = d;
                else this.batchProgress.push(d);
                if (d.total > 0) this.progressPct = Math.round((d.done / d.total) * 100);
            });

            this._es.addEventListener('complete', e => {
                this._es.close();
                this.isBatching = false;
                this.batchDone = true;
                this.batchStats = JSON.parse(e.data);
                this.progressPct = 100;
                setTimeout(() => window.location.reload(), 2000);
            });

            this._es.addEventListener('error', e => {
                this._es?.close();
                this.isBatching = false;
                try {
                    const d = JSON.parse(e.data || '{}');
                    alert('오류: ' + (d.message || '알 수 없는 오류'));
                } catch(_) {}
            });
        },

        cancelBatch() {
            this._es?.close();
            this.isBatching = false;
        },
    };
}

// 단일 화면 생성 (카드 내 버튼)
async function generateOneMockup(btn, url, token) {
    btn.disabled = true;
    btn.textContent = '생성 중...';

    try {
        const res  = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
        });
        const json = await res.json();

        if (json.success) {
            window.location.reload();
        } else {
            alert(json.message || '생성 실패');
            btn.disabled = false;
            btn.textContent = '생성';
        }
    } catch(e) {
        alert('오류: ' + e.message);
        btn.disabled = false;
        btn.textContent = '생성';
    }
}
</script>
@endpush

@endsection
