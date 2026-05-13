@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ────────────────────────────────────────────────── */
.cp-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.cp-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cp-header-left p  { font-size:13.5px; color:#64748b; margin:0; }
.cp-header-right   { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* ── Buttons ───────────────────────────────────────────────── */
.cp-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.cp-btn.primary   { background:var(--t600,#7c3aed); color:#fff; }
.cp-btn.primary:hover { background:var(--t700,#6d28d9); }
.cp-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.cp-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cp-btn.secondary:hover { background:#e2e8f0; }
.cp-btn.ghost     { background:transparent; color:var(--t600,#7c3aed); border:1.5px solid var(--t300,#c4b5fd); }
.cp-btn.ghost:hover { background:#f5f3ff; }
.cp-btn.sm { padding:4px 10px; font-size:12px; }

/* ── Stats ──────────────────────────────────────────────────── */
.cp-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-bottom:18px; }
.cp-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; }
.cp-stat-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.cp-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; margin-top:2px; }
.cp-stat-sub   { font-size:11.5px; color:#94a3b8; margin-top:2px; }

/* ── Section ────────────────────────────────────────────────── */
.cp-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:18px; }
.cp-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

/* ── Progress ───────────────────────────────────────────────── */
.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:12px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.progress-log { background:#0f0f1a; border-radius:10px; padding:12px 14px; max-height:200px; overflow-y:auto; font-family:monospace; font-size:12px; color:#94a3b8; }
.progress-log-line { padding:2px 0; }
.progress-log-line.ok     { color:#4ade80; }
.progress-log-line.fail   { color:#f87171; }
.progress-log-line.active { color:#c4b5fd; }

/* ── Screen list ─────────────────────────────────────────────── */
.screen-list-header { display:grid; grid-template-columns:80px 1fr 100px 140px 80px; gap:12px; padding:8px 14px; font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; border-bottom:1.5px solid #ede8ff; margin-bottom:6px; }
.screen-row { display:grid; grid-template-columns:80px 1fr 100px 140px 80px; gap:12px; padding:10px 14px; border-radius:10px; align-items:center; border:1.5px solid transparent; transition:border-color .15s; }
.screen-row:hover { background:#fafafe; border-color:#ede8ff; }
.screen-row-id   { font-size:12px; font-weight:700; font-family:monospace; color:#7c3aed; }
.screen-row-title { font-size:13px; font-weight:600; color:#1e1b2e; }
.screen-row-desc  { font-size:12px; color:#94a3b8; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.screen-row-status { font-size:11.5px; font-weight:700; }
.screen-row-status.done { color:#16a34a; }
.screen-row-status.missing { color:#d97706; }
.screen-row-date  { font-size:11.5px; color:#94a3b8; }

/* ── Prereq ──────────────────────────────────────────────────── */
.prereq-item { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; border:1.5px solid #e2e8f0; margin-bottom:8px; font-size:13px; }
.prereq-item.ok   { background:#f0fdf4; border-color:#bbf7d0; }
.prereq-item.miss { background:#fffbeb; border-color:#fde68a; }

/* ── Start banner ────────────────────────────────────────────── */
.cp-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:22px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; margin-bottom:18px; }
.cp-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.cp-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.cp-banner-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.cp-start-btn { background:#fff; color:var(--t700,#6d28d9); border:none; border-radius:9px; padding:9px 20px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.cp-start-btn:hover { background:#f5f3ff; }
.cp-start-btn:disabled { opacity:.5; cursor:not-allowed; }
.cp-start-btn.secondary { background:rgba(255,255,255,.15); color:#fff; border:1.5px solid rgba(255,255,255,.3); }
.cp-start-btn.secondary:hover { background:rgba(255,255,255,.25); }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')
<script type="application/json" id="cp-data">
{
    "totalCount": {{ $totalCount }},
    "doneCount": {{ $doneCount }},
    "missingCount": {{ $missingCount }},
    "batchStartUrl": "{{ $batchStartUrl }}",
    "batchSseUrlTpl": "{{ $batchSseUrlTpl }}",
    "cancelUrlTpl": "{{ $cancelUrlTpl }}",
    "csrfToken": "{{ csrf_token() }}"
}
</script>

<div x-data="codePromptsIndex()" x-init="init()">

    {{-- 헤더 --}}
    <div class="cp-header">
        <div class="cp-header-left">
            <h1>코드 생성 프롬프트</h1>
            <p>각 화면에 대한 프로덕션 수준의 코드 생성 프롬프트를 웍스로 자동 생성합니다. ERD·API·RBAC·디자인 시스템을 모두 반영합니다.</p>
        </div>
    </div>

    {{-- 통계 --}}
    <div class="cp-stats">
        <div class="cp-stat">
            <div class="cp-stat-label">전체 화면</div>
            <div class="cp-stat-value">{{ $totalCount }}</div>
        </div>
        <div class="cp-stat">
            <div class="cp-stat-label">생성 완료</div>
            <div class="cp-stat-value" style="color:#16a34a;">{{ $doneCount }}</div>
        </div>
        <div class="cp-stat">
            <div class="cp-stat-label">미생성</div>
            <div class="cp-stat-value" style="color:#d97706;">{{ $missingCount }}</div>
        </div>
        @if($totalCount > 0)
        <div class="cp-stat">
            <div class="cp-stat-label">진행률</div>
            <div class="cp-stat-value">{{ $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0 }}%</div>
            <div class="cp-stat-sub">
                <div class="progress-bar-wrap" style="margin:4px 0 0;">
                    <div class="progress-bar-fill" style="width:{{ $totalCount > 0 ? round(($doneCount / $totalCount) * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- 생성 배너 --}}
    @if($totalCount > 0)
    <div class="cp-banner">
        <div class="cp-banner-text">
            <h3>코드 생성 프롬프트 일괄 생성</h3>
            <p>모든 화면의 프롬프트를 자동 생성합니다. ERD, API 명세, RBAC, 디자인 시스템을 통합하여 완성도 높은 프롬프트를 만듭니다.</p>
        </div>
        <div class="cp-banner-actions">
            <button class="cp-start-btn secondary" :disabled="isGenerating" @click="startBatch(true)">
                <template x-if="isGenerating && onlyMissing"><svg style="width:14px;height:14px;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></template>
                미생성만
            </button>
            <button class="cp-start-btn" :disabled="isGenerating" @click="startBatch(false)">
                <template x-if="isGenerating && !onlyMissing"><svg style="width:14px;height:14px;animation:spin 1s linear infinite" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></template>
                전체 생성
            </button>
        </div>
    </div>
    @endif

    {{-- SSE 진행 상황 --}}
    <template x-if="isGenerating || batchResult">
        <div class="cp-section" style="margin-bottom:18px;">
            <div class="cp-section-title">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span x-text="isGenerating ? '생성 진행 중...' : '생성 완료'"></span>
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
                <div style="margin-top:14px;display:flex;gap:20px;flex-wrap:wrap;font-size:13px;">
                    <span>✅ 완료 <strong x-text="batchResult.done - batchResult.failed_count"></strong>개</span>
                    <span x-show="batchResult.failed_count > 0" style="color:#b91c1c;">❌ 실패 <strong x-text="batchResult.failed_count"></strong>개</span>
                    <span x-show="batchResult.cost_usd > 0" style="color:#64748b;">💰 비용 $<span x-text="batchResult.cost_usd"></span></span>
                    <button class="cp-btn secondary sm" @click="batchResult=null;progressLog=[];progress=0;" style="margin-left:auto;">닫기</button>
                    <button class="cp-btn primary sm" @click="window.location.reload()">목록 새로고침</button>
                </div>
            </template>
        </div>
    </template>

    {{-- 화면 목록 --}}
    <div class="cp-section">
        <div class="cp-section-title">
            화면 목록
            <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:4px;">{{ $totalCount }}개</span>
        </div>

        @if($totalCount === 0)
        <div style="text-align:center;padding:40px 20px;color:#94a3b8;">
            <p style="font-size:15px;font-weight:600;margin-bottom:8px;">화면이 없습니다</p>
            <p style="font-size:13px;">기획 단계에서 화면을 먼저 등록해주세요.</p>
        </div>
        @else
        <div class="screen-list-header">
            <div>화면 ID</div>
            <div>화면명</div>
            <div>상태</div>
            <div>생성 시각</div>
            <div></div>
        </div>
        @foreach($screenData as $item)
        <div class="screen-row">
            <div class="screen-row-id">{{ $item['screen']->screen_id }}</div>
            <div>
                <div class="screen-row-title">{{ $item['screen']->title }}</div>
                @if($item['screen']->description)
                <div class="screen-row-desc">{{ $item['screen']->description }}</div>
                @endif
            </div>
            <div class="screen-row-status {{ $item['has_prompt'] ? 'done' : 'missing' }}">
                {{ $item['has_prompt'] ? '✅ 생성됨' : '⏳ 미생성' }}
            </div>
            <div class="screen-row-date">
                {{ $item['generated_at'] ? \Carbon\Carbon::parse($item['generated_at'])->format('m/d H:i') : '—' }}
            </div>
            <div>
                <a href="{{ $item['show_url'] }}" class="cp-btn ghost sm">보기</a>
            </div>
        </div>
        @endforeach
        @endif
    </div>

</div>
@endsection

@push('scripts')
<script>
function codePromptsIndex() {
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

        init() {
            const raw = document.getElementById('cp-data')?.textContent;
            if (raw) this.cfg = JSON.parse(raw);
        },

        async startBatch(onlyMissing) {
            if (this.isGenerating) return;
            this.isGenerating  = true;
            this.onlyMissing   = onlyMissing;
            this.progress      = 0;
            this.progressDone  = 0;
            this.progressTotal = 0;
            this.progressLog   = [];
            this.batchResult   = null;

            try {
                const res = await fetch(this.cfg.batchStartUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.cfg.csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ only_missing: onlyMissing }),
                });
                const { sessionId } = await res.json();
                const sseUrl = this.cfg.batchSseUrlTpl.replace('SESSION_ID', sessionId);
                this.listenSse(sseUrl);
            } catch (e) {
                this.addLog('오류: ' + e.message, 'fail');
                this.isGenerating = false;
            }
        },

        listenSse(url) {
            this.eventSource = new EventSource(url);

            this.eventSource.addEventListener('status', e => {
                const d = JSON.parse(e.data);
                this.addLog(d.message, 'active');
            });

            this.eventSource.addEventListener('progress', e => {
                const d = JSON.parse(e.data);
                this.progress      = d.progress ?? 0;
                this.progressDone  = d.done  ?? 0;
                this.progressTotal = d.total ?? 0;
                const status = d.status === 'done' ? 'ok' : d.status === 'failed' ? 'fail' : 'active';
                const icon   = d.status === 'done' ? '✅' : d.status === 'failed' ? '❌' : '🔄';
                this.addLog(`${icon} [${d.screen_id}] ${d.title} — ${d.status}`, status);
            });

            this.eventSource.addEventListener('complete', e => {
                const d = JSON.parse(e.data);
                this.progress     = 100;
                this.batchResult  = d;
                this.isGenerating = false;
                this.addLog(`완료: ${d.done}개 처리, ${d.elapsed}초 소요`, 'ok');
                this.eventSource?.close();
            });

            this.eventSource.addEventListener('error', e => {
                try {
                    const d = JSON.parse(e.data);
                    this.addLog('오류: ' + d.message, 'fail');
                } catch {}
                this.isGenerating = false;
                this.eventSource?.close();
            });
        },

        addLog(text, cls = '') {
            this.progressLog.push({ text, cls });
            this.$nextTick(() => {
                if (this.$refs.logEl) {
                    this.$refs.logEl.scrollTop = this.$refs.logEl.scrollHeight;
                }
            });
        },
    };
}
</script>
@endpush
