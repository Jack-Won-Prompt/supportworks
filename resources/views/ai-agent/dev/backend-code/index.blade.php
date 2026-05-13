@extends('layouts.ai-agent')
@section('title', 'Backend 코드 생성 — 웍스 Agent')

@push('styles')
<style>
/* ── Header ──────────────────────────────────────────────────────── */
.bk-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.bk-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.bk-header p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Stats bar ───────────────────────────────────────────────────── */
.bk-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.bk-stat  { flex:1; min-width:120px; background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; }
.bk-stat-val { font-size:24px; font-weight:800; color:#7c3aed; }
.bk-stat-lbl { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

/* ── Pre-condition ───────────────────────────────────────────────── */
.bk-precond { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 18px; margin-bottom:16px; }
.bk-precond-title { font-size:12px; font-weight:700; color:#7c3aed; margin:0 0 10px; text-transform:uppercase; letter-spacing:.05em; }
.bk-precond-items { display:flex; flex-wrap:wrap; gap:8px; }
.bk-cond { display:flex; align-items:center; gap:6px; padding:6px 12px; border-radius:8px; font-size:12.5px; font-weight:600; }
.bk-cond.ok  { background:#f0fdf4; color:#16a34a; }
.bk-cond.err { background:#fef2f2; color:#b91c1c; }

/* ── Batch panel ─────────────────────────────────────────────────── */
.bk-batch { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:16px 20px; margin-bottom:20px; }
.bk-batch-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 12px; }
.bk-batch-cost  { font-size:13px; color:#475569; margin-bottom:12px; }
.bk-batch-btns  { display:flex; gap:8px; flex-wrap:wrap; }

/* ── SSE panel ───────────────────────────────────────────────────── */
.bk-sse { display:none; background:#0f172a; border-radius:12px; padding:16px; margin-bottom:20px; max-height:320px; overflow-y:auto; font-family:monospace; font-size:12px; }
.bk-sse-line { margin:1px 0; }
.bk-sse-line.ok   { color:#4ade80; }
.bk-sse-line.err  { color:#f87171; }
.bk-sse-line.info { color:#94a3b8; }

/* ── Grid ────────────────────────────────────────────────────────── */
.bk-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }

/* ── Resource card ───────────────────────────────────────────────── */
.bk-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:14px; padding:16px 18px; position:relative; transition:box-shadow .15s; }
.bk-card:hover { box-shadow:0 4px 18px rgba(124,58,237,.09); }
.bk-card.has-code { border-color:#c4b5fd; }
.bk-card-icon  { font-size:26px; margin-bottom:8px; }
.bk-card-name  { font-size:15px; font-weight:800; color:#1e1b2e; margin-bottom:2px; }
.bk-card-table { font-size:11.5px; color:#94a3b8; font-family:monospace; margin-bottom:6px; }
.bk-card-desc  { font-size:12px; color:#64748b; margin-bottom:10px; line-height:1.5; }

.bk-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:2px 8px; border-radius:99px; margin-right:4px; }
.bk-badge.done    { background:#ede9fe; color:#7c3aed; }
.bk-badge.missing { background:#f1f5f9; color:#94a3b8; }

.bk-card-meta  { font-size:11px; color:#94a3b8; margin-top:8px; }
.bk-card-btns  { display:flex; gap:6px; margin-top:10px; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.btn-primary  { display:inline-flex;align-items:center;gap:5px;padding:7px 15px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
.btn-primary:hover  { opacity:.9; }
.btn-secondary{ display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fff;color:#7c3aed;border:1.5px solid #c4b5fd;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-secondary:hover{ background:#f5f3ff; }
.btn-sm { padding:5px 11px; font-size:12px; }
.btn-danger   { display:inline-flex;align-items:center;gap:5px;padding:5px 11px;background:#fff;color:#b91c1c;border:1.5px solid #fca5a5;border-radius:8px;font-size:12px;font-weight:600;cursor:pointer;transition:all .15s; }
.btn-danger:hover { background:#fef2f2; }

/* ── Modal ───────────────────────────────────────────────────────── */
.bk-modal-bg { position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9998;display:none;align-items:center;justify-content:center; }
.bk-modal-bg.open { display:flex; }
.bk-modal { background:#fff;border-radius:16px;padding:28px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.18); }
.bk-modal h3 { font-size:18px;font-weight:800;margin:0 0 8px; }
.bk-modal p  { font-size:14px;color:#475569;margin:0 0 18px;line-height:1.6; }
.bk-modal-btns { display:flex;gap:8px;justify-content:flex-end; }

.btn-loading { pointer-events:none; opacity:.65; }
.spinner { display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

<div class="bk-header">
    <div>
        <h1>Backend 코드 생성 (T43)</h1>
        <p>ERD의 테이블 단위로 Laravel Model, Migration, Controller, Policy를 자동 생성합니다.</p>
    </div>
    <a href="{{ $downloadAllUrl }}" class="btn-secondary btn-sm">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        전체 다운로드
    </a>
</div>

{{-- Pre-condition --}}
<div class="bk-precond">
    <div class="bk-precond-title">사전 조건</div>
    <div class="bk-precond-items">
        <div class="bk-cond {{ $hasErd ? 'ok' : 'err' }}">
            {{ $hasErd ? '✅' : '❌' }}
            <span>ERD (T36)</span>
            @if(!$hasErd)
                <a href="{{ route('ai-agent.projects.pre-dev.erd', $project) }}" style="color:inherit;font-size:11px;margin-left:4px;">→ 생성</a>
            @else
                <span style="font-size:11px;color:inherit;opacity:.7;">{{ $totalCount }}개 테이블</span>
            @endif
        </div>
        <div class="bk-cond {{ $hasApi ? 'ok' : 'err' }}">
            {{ $hasApi ? '✅' : '❌' }} API 명세 (T37)
        </div>
        <div class="bk-cond {{ $hasRbac ? 'ok' : 'err' }}">
            {{ $hasRbac ? '✅' : '❌' }} RBAC (T38)
        </div>
    </div>
</div>

{{-- Stats --}}
<div class="bk-stats">
    <div class="bk-stat">
        <div class="bk-stat-val">{{ $totalCount }}</div>
        <div class="bk-stat-lbl">총 리소스</div>
    </div>
    <div class="bk-stat">
        <div class="bk-stat-val" style="color:#16a34a;">{{ $doneCount }}</div>
        <div class="bk-stat-lbl">코드 생성됨</div>
    </div>
    <div class="bk-stat">
        <div class="bk-stat-val" style="color:#d97706;">{{ $missingCount }}</div>
        <div class="bk-stat-lbl">미생성</div>
    </div>
</div>

{{-- Batch panel --}}
@if($hasErd && $totalCount > 0)
<div class="bk-batch" x-data="{
    sseOpen: false,
    sseLines: [],
    batchRunning: false,
    confirming: false,
    confirmData: null,
    cost: 0,
    resourceCount: 0,

    async startBatch(onlyMissing) {
        const res = await fetch('{{ $batchStartUrl }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ only_missing: onlyMissing }),
        });
        const data = await res.json();
        if (data.requiresConfirmation) {
            this.confirmData = { onlyMissing };
            this.cost = data.estimatedCost;
            this.resourceCount = data.resourceCount;
            this.confirming = true;
        } else if (data.success) {
            this.runSse(data.sessionId);
        }
    },

    async confirmBatch() {
        this.confirming = false;
        const res = await fetch('{{ $batchStartUrl }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ only_missing: this.confirmData.onlyMissing, confirmed_cost: true }),
        });
        const data = await res.json();
        if (data.success) this.runSse(data.sessionId);
    },

    runSse(sessionId) {
        this.sseOpen = true;
        this.batchRunning = true;
        this.sseLines = [];
        const url = '{{ $batchSseUrlTpl }}'.replace('SESSION_ID', sessionId);
        const es  = new EventSource(url);
        es.addEventListener('status',   e => { const d = JSON.parse(e.data); this.addLine(d.message, 'info'); });
        es.addEventListener('progress', e => {
            const d = JSON.parse(e.data);
            const icon = d.status === 'done' ? '✅' : d.status === 'failed' ? '❌' : '⏳';
            this.addLine(`${icon} [${d.resource}] ${d.table} — ${d.status}`, d.status === 'failed' ? 'err' : 'ok');
        });
        es.addEventListener('complete', e => {
            const d = JSON.parse(e.data);
            this.addLine(`완료: ${d.done}/${d.total} 성공 · $${d.cost_usd} · ${d.elapsed}s`, 'info');
            es.close(); this.batchRunning = false;
            setTimeout(() => window.location.reload(), 1500);
        });
        es.addEventListener('error', e => {
            try { const d = JSON.parse(e.data); this.addLine('오류: ' + d.message, 'err'); } catch(x) {}
            es.close(); this.batchRunning = false;
        });
        es.onerror = () => { if (this.batchRunning) { this.addLine('연결 오류', 'err'); es.close(); this.batchRunning = false; } };
    },

    addLine(text, cls) {
        this.sseLines.push({ text, cls });
        this.$nextTick(() => { const el = this.$refs.sseLog; if (el) el.scrollTop = el.scrollHeight; });
    },
}">

    <div class="bk-batch-title">일괄 생성</div>
    <div class="bk-batch-cost">
        리소스당 예상 비용: <strong>~$0.65</strong>
        · 미생성 {{ $missingCount }}개 기준 ~<strong>${{ number_format($estimatedCost, 2) }}</strong>
    </div>
    <div class="bk-batch-btns">
        <button class="btn-primary" :class="{ 'btn-loading': batchRunning }" @click="startBatch(false)">
            <span x-show="!batchRunning">전체 생성</span>
            <span x-show="batchRunning" class="spinner"></span>
            <span x-show="batchRunning">생성 중...</span>
        </button>
        <button class="btn-secondary" :class="{ 'btn-loading': batchRunning }" @click="startBatch(true)">
            미생성만 ({{ $missingCount }}개)
        </button>
    </div>

    {{-- SSE log --}}
    <div x-show="sseOpen" x-cloak class="bk-sse" style="margin-top:14px;" x-ref="sseLog">
        <template x-for="(l, i) in sseLines" :key="i">
            <div class="bk-sse-line" :class="l.cls" x-text="l.text"></div>
        </template>
    </div>

    {{-- Cost confirm modal --}}
    <div class="bk-modal-bg" :class="{ open: confirming }">
        <div class="bk-modal">
            <h3>비용 확인</h3>
            <p>
                <strong x-text="resourceCount"></strong>개 리소스 코드 생성 예상 비용:<br>
                <span style="font-size:22px;font-weight:800;color:#7c3aed;" x-text="'$' + cost.toFixed(2)"></span>
            </p>
            <div class="bk-modal-btns">
                <button class="btn-secondary btn-sm" @click="confirming = false">취소</button>
                <button class="btn-primary btn-sm" @click="confirmBatch()">확인 후 생성</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Resource grid --}}
@if($resourceData->isEmpty())
<div style="text-align:center;padding:48px;color:#94a3b8;font-size:14px;">
    @if(!$hasErd)
        ERD가 없습니다. T36에서 ERD를 먼저 생성하세요.
    @else
        ERD에 테이블이 없습니다.
    @endif
</div>
@else
<div class="bk-grid">
    @foreach($resourceData as $res)
    <div class="bk-card {{ $res['has_code'] ? 'has-code' : '' }}">
        <div class="bk-card-icon">{{ match(true) {
            str_contains(strtolower($res['table']), 'user') => '👤',
            str_contains(strtolower($res['table']), 'order') => '📦',
            str_contains(strtolower($res['table']), 'product') => '🛒',
            str_contains(strtolower($res['table']), 'log') => '📋',
            str_contains(strtolower($res['table']), 'file') => '📄',
            str_contains(strtolower($res['table']), 'message') => '💬',
            str_contains(strtolower($res['table']), 'report') => '📊',
            default => '🗄️'
        } }}</div>
        <div class="bk-card-name">{{ $res['resource'] }}</div>
        <div class="bk-card-table"><code>{{ $res['table'] }}</code></div>
        @if($res['description'])
            <div class="bk-card-desc">{{ Str::limit($res['description'], 60) }}</div>
        @endif

        @if($res['has_code'])
            <span class="bk-badge done">{{ $res['files_count'] }} 파일</span>
            <span class="bk-badge done">v{{ $res['version'] }}</span>
            <div class="bk-card-meta">
                @if($res['generated_at'])
                    {{ \Carbon\Carbon::parse($res['generated_at'])->diffForHumans() }}
                @endif
                @if($res['cost_usd'])
                    · ${{ number_format($res['cost_usd'], 3) }}
                @endif
            </div>
        @else
            <span class="bk-badge missing">미생성</span>
        @endif

        <div class="bk-card-btns">
            @if($res['has_code'])
                <a href="{{ $res['show_url'] }}" class="btn-secondary btn-sm">상세 보기</a>
            @endif
            <button class="btn-primary btn-sm"
                    onclick="generateSingle(this, '{{ $res['table'] }}', '{{ $res['generate_url'] }}', '{{ $res['show_url'] }}')">
                {{ $res['has_code'] ? '재생성' : '생성' }}
            </button>
        </div>
    </div>
    @endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
async function generateSingle(btn, tableName, generateUrl, showUrl) {
    if (!await __confirm(`[${tableName}] 코드를 생성하시겠습니까?\n예상 비용: ~$0.65`)) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span> 생성 중...';

    try {
        const res = await fetch(generateUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        });
        const data = await res.json();
        if (data.success) {
            window.location.href = showUrl;
        } else {
            alert('생성 실패: ' + (data.message || '알 수 없는 오류'));
            btn.disabled = false;
            btn.innerHTML = '재생성';
        }
    } catch (e) {
        alert('오류 발생: ' + e.message);
        btn.disabled = false;
        btn.innerHTML = '생성';
    }
}
</script>
@endpush
