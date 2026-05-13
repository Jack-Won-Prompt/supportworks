@extends('layouts.ai-agent')
@section('title', '디자인 일관성 검수 — 웍스 Agent')

@push('styles')
<style>
/* ── Layout ── */
.dr-wrap        { max-width: 960px; }
.dr-card        { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:22px 24px; margin-bottom:16px; }
.dr-card-title  { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:14px; display:flex; align-items:center; gap:7px; }
.dr-card-title::after { content:''; flex:1; height:1px; background:#f1f5f9; }

/* ── Prerequisites ── */
.dr-prereq-item { display:flex; align-items:center; gap:9px; padding:6px 0; font-size:13px; color:#374151; }
.dr-prereq-ok   { color:#22c55e; font-weight:700; }
.dr-prereq-warn { color:#f59e0b; font-weight:700; }
.dr-prereq-err  { color:#ef4444; font-weight:700; }

/* ── Score card ── */
.dr-score-big   { font-size:56px; font-weight:900; line-height:1; font-family:monospace; }
.dr-score-label { font-size:12px; color:#94a3b8; margin-top:4px; }
.dr-gauge-bar   { height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; margin-top:4px; }
.dr-gauge-fill  { height:100%; border-radius:4px; transition:width .5s ease; }
.dr-cat-row     { display:flex; align-items:center; gap:10px; margin-bottom:8px; }
.dr-cat-label   { font-size:12px; color:#64748b; width:72px; flex-shrink:0; }
.dr-cat-bar     { flex:1; height:5px; border-radius:3px; background:#f1f5f9; overflow:hidden; }
.dr-cat-fill    { height:100%; border-radius:3px; }
.dr-cat-score   { font-size:11.5px; font-weight:700; color:#374151; width:32px; text-align:right; flex-shrink:0; }

/* ── Screen list ── */
.dr-screen-row  { display:flex; align-items:center; gap:10px; padding:8px 12px; border-radius:8px; margin-bottom:5px; border:1.5px solid #f1f5f9; text-decoration:none; transition:all .12s; }
.dr-screen-row:hover { border-color:#a78bfa; background:#faf8ff; }
.dr-scr-id      { font-family:monospace; font-size:12px; font-weight:700; color:#7c3aed; width:66px; flex-shrink:0; }
.dr-scr-name    { font-size:13px; color:#374151; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.dr-scr-score   { font-size:12px; font-weight:700; width:36px; text-align:right; }
.dr-scr-vcount  { font-size:11px; color:#94a3b8; width:40px; text-align:right; }

/* ── Violation badge ── */
.dr-badge { display:inline-flex; align-items:center; gap:3px; font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:5px; }
.dr-badge-critical { background:#fef2f2; color:#dc2626; }
.dr-badge-warning  { background:#fffbeb; color:#d97706; }
.dr-badge-info     { background:#eff6ff; color:#2563eb; }
.dr-badge-pass     { background:#f0fdf4; color:#16a534; }

/* ── Progress overlay ── */
.dr-progress-wrap  { position:fixed; inset:0; background:rgba(15,10,30,.5); z-index:500; display:flex; align-items:center; justify-content:center; }
.dr-progress-box   { background:#fff; border-radius:16px; width:min(560px,95vw); padding:28px 32px; box-shadow:0 24px 64px rgba(0,0,0,.2); }
.dr-progress-bar   { height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; margin:14px 0 8px; }
.dr-progress-fill  { height:100%; border-radius:4px; background:linear-gradient(90deg,#7c3aed,#a78bfa); transition:width .4s ease; }
.dr-screen-log     { max-height:200px; overflow-y:auto; margin-top:12px; }
.dr-log-item       { display:flex; align-items:center; gap:8px; padding:4px 0; border-bottom:1px solid #f8fafc; font-size:12px; }
.dr-log-item:last-child { border-bottom:none; }

/* ── Buttons ── */
.dr-btn  { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.dr-btn-primary { background:#7c3aed; color:#fff; }
.dr-btn-primary:hover:not(:disabled) { background:#6d28d9; }
.dr-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.dr-btn-outline:hover { border-color:#a78bfa; color:#7c3aed; }
.dr-btn:disabled { opacity:.5; cursor:not-allowed; }

/* ── Toast ── */
.dr-toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:500; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.dr-toast.show { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('page-actions')
<a href="{{ route('ai-agent.projects.design.index', $project) }}" class="dr-btn dr-btn-outline">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    디자인 단계
</a>
@if($artifact)
<a href="{{ route('ai-agent.projects.design.review.export', $project) }}" class="dr-btn dr-btn-outline">내보내기</a>
@endif
@endsection

@section('ai-agent-content')
<div x-data="drPage(@json([
    'startUrl'   => route('ai-agent.projects.design.review.start',  $project),
    'sseBase'    => route('ai-agent.projects.design.review.sse',    [$project, '__SID__']),
    'screenBase' => route('ai-agent.projects.design.review.screen', [$project, 0]),
    'mappingUrl' => route('ai-agent.projects.design.screens',       $project),
    'csrfToken'  => csrf_token(),
]))" class="dr-wrap">

    <div class="dr-toast" :class="{ show: toast.show }" x-text="toast.msg"></div>

    {{-- ── 사전 조건 카드 ── --}}
    <div class="dr-card">
        <div class="dr-card-title">검수 준비 상태</div>

        <div class="dr-prereq-item">
            <span class="{{ $context['has_tokens']     ? 'dr-prereq-ok' : 'dr-prereq-warn' }}">{{ $context['has_tokens']     ? '✅' : '⚠️' }}</span>
            Design Tokens (T28)
            @if(!$context['has_tokens']) <span style="font-size:11.5px;color:#f59e0b;">&nbsp;— 추출 필요</span> @endif
        </div>
        <div class="dr-prereq-item">
            <span class="{{ $context['has_components'] ? 'dr-prereq-ok' : 'dr-prereq-warn' }}">{{ $context['has_components'] ? '✅' : '⚠️' }}</span>
            Component 명세 (T29)
            @if(!$context['has_components']) <span style="font-size:11.5px;color:#f59e0b;">&nbsp;— 추출 필요</span> @endif
        </div>
        <div class="dr-prereq-item">
            <span class="{{ $context['has_layouts']    ? 'dr-prereq-ok' : 'dr-prereq-warn' }}">{{ $context['has_layouts']    ? '✅' : '⚠️' }}</span>
            표준 Layout (T30)
            @if(!$context['has_layouts']) <span style="font-size:11.5px;color:#f59e0b;">&nbsp;— 분석 필요</span> @endif
        </div>
        <div class="dr-prereq-item">
            @php $mappedOk = $context['mapped_screens'] >= $context['total_screens'] && $context['total_screens'] > 0; @endphp
            <span class="{{ $mappedOk ? 'dr-prereq-ok' : ($context['mapped_screens'] > 0 ? 'dr-prereq-warn' : 'dr-prereq-err') }}">
                {{ $mappedOk ? '✅' : ($context['mapped_screens'] > 0 ? '⚠️' : '❌') }}
            </span>
            화면 매핑 (T31) — {{ $context['mapped_screens'] }}/{{ $context['total_screens'] }}개 매핑됨
            @if(!$mappedOk)
            <a href="{{ route('ai-agent.projects.design.screens', $project) }}" style="font-size:11.5px;color:#7c3aed;margin-left:6px;">매핑하러 가기</a>
            @endif
        </div>

        @if($context['mapped_screens'] > 0)
        <div style="margin-top:16px;padding-top:14px;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <div style="font-size:13px;color:#374151;">
                검수 대상: <strong>{{ $context['mapped_screens'] }}개</strong> 화면
                @if(!$mappedOk)
                <span style="font-size:11.5px;color:#f59e0b;">(미매핑 {{ $context['total_screens'] - $context['mapped_screens'] }}개 제외)</span>
                @endif
            </div>
            <div style="margin-left:auto;display:flex;gap:8px;">
                @if($artifact)
                <button class="dr-btn dr-btn-outline" @click="startReview()" :disabled="reviewing">
                    재검수
                </button>
                @endif
                <button class="dr-btn dr-btn-primary" @click="startReview()" :disabled="reviewing">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                    <span x-text="reviewing ? '검수 중...' : '🤖 일관성 검수 시작'"></span>
                </button>
            </div>
        </div>
        @else
        <div style="margin-top:14px;padding:12px 16px;background:#fef9c3;border:1.5px solid #fde68a;border-radius:9px;font-size:13px;color:#92400e;">
            화면 매핑이 없습니다. 검수를 시작하려면 먼저 T31 화면 매핑을 완료하세요.
            <a href="{{ route('ai-agent.projects.design.screens', $project) }}" class="dr-btn dr-btn-outline" style="margin-left:10px;padding:4px 10px;font-size:12px;">매핑하러 가기</a>
        </div>
        @endif
    </div>

    {{-- ── 진행 오버레이 ── --}}
    <div class="dr-progress-wrap" x-show="reviewing" x-cloak>
        <div class="dr-progress-box">
            <div style="font-size:15px;font-weight:700;color:#1e1b2e;margin-bottom:6px;">디자인 일관성 검수 중</div>
            <div style="font-size:12.5px;color:#64748b;" x-text="progressMsg"></div>
            <div class="dr-progress-bar">
                <div class="dr-progress-fill" :style="{ width: progress + '%' }"></div>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:11.5px;color:#94a3b8;">
                <span x-text="progress + '%'"></span>
                <span x-text="'토큰: ' + tokensIn.toLocaleString() + ' / ' + tokensOut.toLocaleString()"></span>
            </div>
            <div class="dr-screen-log">
                <template x-for="(log, idx) in screenLogs.slice(-10)" :key="idx">
                    <div class="dr-log-item">
                        <span x-text="log.icon" style="flex-shrink:0;"></span>
                        <span style="font-family:monospace;font-size:11px;color:#7c3aed;flex-shrink:0;" x-text="log.screenId"></span>
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="log.name"></span>
                        <template x-if="log.score !== null">
                            <span style="font-weight:700;" :style="{ color: log.score >= 80 ? '#16a34a' : log.score >= 60 ? '#d97706' : '#dc2626' }" x-text="log.score + '점'"></span>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @if($report)
    {{-- ── 검수 결과 ── --}}
    @php
        $meta    = $report['$metadata'] ?? [];
        $stats   = $meta['stats'] ?? [];
        $summary = $report['summary'] ?? [];
        $bd      = $summary['compliance_breakdown'] ?? [];
        $scoreColor = fn($s) => $s >= 80 ? '#16a34a' : ($s >= 60 ? '#d97706' : '#dc2626');
        $scoreGrad  = fn($s) => $s >= 80 ? 'linear-gradient(90deg,#22c55e,#86efac)' : ($s >= 60 ? 'linear-gradient(90deg,#f59e0b,#fcd34d)' : 'linear-gradient(90deg,#ef4444,#fca5a5)');
    @endphp

    {{-- 종합 점수 --}}
    <div class="dr-card">
        <div class="dr-card-title">종합 검수 결과</div>
        <div style="display:grid;grid-template-columns:140px 1fr;gap:24px;align-items:start;">
            <div style="text-align:center;">
                <div class="dr-score-big" style="color:{{ $scoreColor($stats['compliance_score'] ?? 0) }};">{{ $stats['compliance_score'] ?? 0 }}</div>
                <div class="dr-score-label">/ 100점</div>
                <div class="dr-gauge-bar" style="margin-top:8px;">
                    <div class="dr-gauge-fill" style="width:{{ $stats['compliance_score'] ?? 0 }}%;background:{{ $scoreGrad($stats['compliance_score'] ?? 0) }};"></div>
                </div>
                <div style="font-size:11.5px;color:#94a3b8;margin-top:6px;">{{ $meta['reviewed_at'] ? \Carbon\Carbon::parse($meta['reviewed_at'])->format('Y.m.d H:i') : '' }}</div>
            </div>
            <div>
                <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:14px;">
                    <div style="display:flex;flex-direction:column;align-items:center;padding:8px 14px;background:#fef2f2;border-radius:9px;">
                        <span style="font-size:18px;font-weight:900;color:#dc2626;">{{ $stats['critical'] ?? 0 }}</span>
                        <span style="font-size:10.5px;color:#64748b;">Critical</span>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;padding:8px 14px;background:#fffbeb;border-radius:9px;">
                        <span style="font-size:18px;font-weight:900;color:#d97706;">{{ $stats['warning'] ?? 0 }}</span>
                        <span style="font-size:10.5px;color:#64748b;">Warning</span>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;padding:8px 14px;background:#eff6ff;border-radius:9px;">
                        <span style="font-size:18px;font-weight:900;color:#2563eb;">{{ $stats['info'] ?? 0 }}</span>
                        <span style="font-size:10.5px;color:#64748b;">Info</span>
                    </div>
                    <div style="display:flex;flex-direction:column;align-items:center;padding:8px 14px;background:#f0fdf4;border-radius:9px;">
                        <span style="font-size:18px;font-weight:900;color:#16a34a;">{{ $stats['passed_screens'] ?? 0 }}</span>
                        <span style="font-size:10.5px;color:#64748b;">통과 화면</span>
                    </div>
                </div>
                {{-- Category breakdown --}}
                @foreach(['color' => '색상', 'typography' => '타이포', 'component' => '컴포넌트', 'layout' => '레이아웃'] as $key => $label)
                @php $cs = $bd[$key] ?? 0; @endphp
                <div class="dr-cat-row">
                    <span class="dr-cat-label">{{ $label }}</span>
                    <div class="dr-cat-bar">
                        <div class="dr-cat-fill" style="width:{{ $cs }}%;background:{{ $scoreGrad($cs) }};"></div>
                    </div>
                    <span class="dr-cat-score" style="color:{{ $scoreColor($cs) }};">{{ $cs }}</span>
                </div>
                @endforeach
            </div>
        </div>

        @if($summary['executive'] ?? null)
        <div style="margin-top:14px;padding-top:14px;border-top:1px solid #f1f5f9;font-size:13px;color:#374151;line-height:1.7;">
            {{ $summary['executive'] }}
        </div>
        @endif
    </div>

    {{-- 화면별 결과 --}}
    <div class="dr-card">
        <div class="dr-card-title">화면별 결과</div>
        @php
            $screens = $report['violations_by_screen'] ?? [];
            usort_hack: // just sort by score ascending
            $sorted = collect($screens)->sortBy('compliance_score')->all();
        @endphp
        @foreach($sorted as $scrId => $scrResult)
        @php
            $sc   = $scrResult['compliance_score'] ?? 0;
            $vcs  = $scrResult['violations'] ?? [];
            $crit = collect($vcs)->where('severity', 'critical')->count();
            $warn = collect($vcs)->where('severity', 'warning')->count();
        @endphp
        <a href="{{ isset($scrResult['screen_db_id']) ? route('ai-agent.projects.design.review.screen', [$project, $scrResult['screen_db_id']]) : '#' }}"
           class="dr-screen-row">
            @if($sc >= 80)
                <svg width="14" height="14" fill="none" stroke="#22c55e" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @elseif($sc >= 60)
                <svg width="14" height="14" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            @else
                <svg width="14" height="14" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            @endif
            <span class="dr-scr-id">{{ $scrId }}</span>
            <span class="dr-scr-name">{{ $scrResult['screen_name'] ?? '—' }}</span>
            @if($crit > 0)
            <span class="dr-badge dr-badge-critical">C {{ $crit }}</span>
            @endif
            @if($warn > 0)
            <span class="dr-badge dr-badge-warning">W {{ $warn }}</span>
            @endif
            <span class="dr-scr-score" style="color:{{ $scoreColor($sc) }};">{{ $sc }}</span>
            <svg width="12" height="12" fill="none" stroke="#c4c9d4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        @endforeach
    </div>

    {{-- 권장 사항 --}}
    @if(!empty($report['recommendations']))
    <div class="dr-card">
        <div class="dr-card-title">권장 사항</div>
        <ol style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:8px;">
            @foreach($report['recommendations'] as $rec)
            <li style="font-size:13px;color:#374151;line-height:1.6;">{{ $rec }}</li>
            @endforeach
        </ol>
    </div>
    @endif

    @else
    {{-- No results yet --}}
    <div class="dr-card" style="text-align:center;padding:40px 24px;color:#94a3b8;">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-opacity=".3" viewBox="0 0 24 24" style="margin:0 auto 12px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <div style="font-size:14px;font-weight:600;">아직 검수 결과가 없습니다</div>
        <div style="font-size:12.5px;margin-top:4px;">상단의 "일관성 검수 시작" 버튼을 눌러 검수를 진행하세요.</div>
    </div>
    @endif

</div>

@push('scripts')
<script>
function drPage(cfg) {
    return {
        cfg,
        reviewing:   false,
        progress:    0,
        progressMsg: '',
        screenLogs:  [],
        tokensIn:    0,
        tokensOut:   0,
        toast:       { show: false, msg: '' },

        showToast(msg) {
            this.toast = { show: true, msg };
            setTimeout(() => { this.toast.show = false; }, 3500);
        },

        async startReview() {
            this.reviewing  = true;
            this.progress   = 2;
            this.progressMsg = '검수 세션 생성 중...';
            this.screenLogs = [];
            this.tokensIn   = 0;
            this.tokensOut  = 0;

            try {
                const res = await axios.post(this.cfg.startUrl, { _token: this.cfg.csrfToken });
                if (!res.data.success) {
                    this.showToast(res.data.message);
                    this.reviewing = false;
                    return;
                }

                const sseUrl = this.cfg.sseBase.replace('__SID__', res.data.sessionId);
                this.listenSse(sseUrl);
            } catch (e) {
                this.showToast(e.response?.data?.message ?? '오류 발생');
                this.reviewing = false;
            }
        },

        listenSse(url) {
            const es = new EventSource(url);

            const handle = (event, data) => {
                if (data.progress !== undefined) this.progress = data.progress;
                if (data.tokens_in !== undefined)  this.tokensIn  = data.tokens_in;
                if (data.tokens_out !== undefined) this.tokensOut = data.tokens_out;

                switch (event) {
                    case 'start':
                        this.progressMsg = `${data.total}개 화면 검수 시작...`;
                        break;
                    case 'images_loaded':
                        this.progressMsg = `Figma 이미지 ${data.count}개 로드 완료`;
                        break;
                    case 'screen_start':
                        this.progressMsg = `${data.index}/${data.total} — ${data.screen_id} 검수 중...`;
                        this.screenLogs.push({ icon: '🔄', screenId: data.screen_id, name: data.screen_name, score: null });
                        break;
                    case 'screen_done':
                        const last = this.screenLogs.findLast(l => l.screenId === data.screen_id);
                        if (last) { last.icon = data.score >= 80 ? '✅' : data.score >= 60 ? '⚠️' : '❌'; last.score = data.score; }
                        this.progressMsg = `${data.index}/${data.total} 완료 (${data.score}점)`;
                        break;
                    case 'screen_error':
                        const errLog = this.screenLogs.findLast(l => l.screenId === data.screen_id);
                        if (errLog) { errLog.icon = '❌'; }
                        break;
                    case 'aggregating':
                        this.progressMsg = data.message;
                        break;
                    case 'complete':
                        es.close();
                        this.reviewing = false;
                        this.showToast(`검수 완료 — 종합 ${data.score}점, 위반 ${data.violations}건`);
                        setTimeout(() => window.location.reload(), 1500);
                        break;
                    case 'error':
                        es.close();
                        this.reviewing = false;
                        this.showToast(data.message);
                        break;
                }
            };

            ['start','images_loaded','screen_start','screen_done','screen_error','aggregating','complete','error'].forEach(ev => {
                es.addEventListener(ev, e => handle(ev, JSON.parse(e.data)));
            });

            es.onerror = () => {
                es.close();
                this.reviewing = false;
                this.showToast('연결이 끊어졌습니다. 페이지를 새로고침하세요.');
            };
        },
    };
}
</script>
@endpush
@endsection
