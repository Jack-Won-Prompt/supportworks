@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.cr-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.cr-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.cr-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

.cr-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.cr-btn.primary   { background:#7c3aed; color:#fff; }
.cr-btn.primary:hover { background:#6d28d9; }
.cr-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.cr-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.cr-btn.secondary:hover { background:#e2e8f0; }
.cr-btn.ghost { background:transparent; color:#7c3aed; border:1.5px solid #c4b5fd; }
.cr-btn.ghost:hover { background:#f5f3ff; }

.cr-stats { display:grid; grid-template-columns:repeat(auto-fit, minmax(120px, 1fr)); gap:10px; margin-bottom:18px; }
.cr-stat { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; }
.cr-stat-label { font-size:11px; font-weight:700; color:#7c6fa0; text-transform:uppercase; letter-spacing:.04em; }
.cr-stat-value { font-size:22px; font-weight:800; color:#1e1b2e; margin-top:2px; }

.cr-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:16px; }
.cr-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

.cr-precond { background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:14px 18px; margin-bottom:16px; }
.cr-precond.warn  { background:#fffbeb; border-color:#fde68a; }
.cr-precond.error { background:#fff1f2; border-color:#fecaca; }
.cr-precond-row { display:flex; align-items:center; gap:10px; font-size:13px; color:#334155; margin-bottom:4px; }
.cr-precond-row:last-child { margin-bottom:0; }

.cr-banner { background:linear-gradient(135deg,#7c3aed,#6d28d9); border-radius:14px; padding:20px 24px; display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap; color:#fff; margin-bottom:16px; }
.cr-banner-text h3 { font-size:15px; font-weight:800; margin:0 0 4px; }
.cr-banner-text p  { font-size:13px; opacity:.85; margin:0; }
.cr-start-btn { background:#fff; color:#6d28d9; border:none; border-radius:9px; padding:9px 20px; font-size:13.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; transition:all .15s; }
.cr-start-btn:hover { background:#f5f3ff; }
.cr-start-btn:disabled { opacity:.5; cursor:not-allowed; }

.confirm-overlay { position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:50; display:flex; align-items:center; justify-content:center; padding:20px; }
.confirm-box { background:#fff; border-radius:16px; padding:28px; max-width:420px; width:100%; }
.confirm-box h3 { font-size:16px; font-weight:800; color:#1e1b2e; margin:0 0 8px; }
.confirm-box .cost-highlight { font-size:28px; font-weight:800; color:#7c3aed; margin:12px 0; }
.confirm-box p { font-size:13px; color:#64748b; margin:0 0 12px; }
.confirm-actions { display:flex; gap:8px; justify-content:flex-end; }

.progress-bar-wrap { background:#f1f5f9; border-radius:99px; height:8px; overflow:hidden; margin-bottom:10px; }
.progress-bar-fill { height:100%; background:linear-gradient(90deg,#7c3aed,#6d28d9); border-radius:99px; transition:width .3s; }
.progress-log { background:#0f0f1a; border-radius:10px; padding:12px 14px; max-height:180px; overflow-y:auto; font-family:monospace; font-size:12px; color:#94a3b8; }
.progress-log-line.ok     { color:#4ade80; }
.progress-log-line.fail   { color:#f87171; }
.progress-log-line.system { color:#60a5fa; }

.cr-screen-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:12px; }
.cr-screen-card { border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; background:#fff; display:flex; flex-direction:column; gap:6px; transition:border-color .15s; }
.cr-screen-card:hover { border-color:#c4b5fd; }
.cr-screen-card.reviewed { border-color:#bbf7d0; background:#f0fdf4; }
.cr-screen-card.issues   { border-color:#fde68a; background:#fffbeb; }
.cr-screen-card.critical { border-color:#fecaca; background:#fff1f2; }
.cr-screen-card-id    { font-size:11px; font-weight:700; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:1px 7px; display:inline-block; }
.cr-screen-card-title { font-size:13px; font-weight:700; color:#1e1b2e; }
.cr-score-badge { font-size:13px; font-weight:800; padding:2px 10px; border-radius:99px; display:inline-block; }
.cr-score-badge.good { background:#dcfce7; color:#15803d; }
.cr-score-badge.warn { background:#fef9c3; color:#a16207; }
.cr-score-badge.bad  { background:#fee2e2; color:#b91c1c; }

.cat-bar-wrap { margin-bottom:8px; }
.cat-bar-label { display:flex; justify-content:space-between; font-size:12px; color:#475569; margin-bottom:3px; }
.cat-bar-label .new-badge { font-size:10px; background:#ede8ff; color:#7c3aed; border-radius:4px; padding:0 5px; font-weight:700; }
.cat-bar-track { background:#f1f5f9; border-radius:99px; height:7px; overflow:hidden; }
.cat-bar-fill { height:100%; border-radius:99px; transition:width .4s; }

.sys-card { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px; margin-bottom:16px; }
.sys-score { font-size:36px; font-weight:800; color:#7c3aed; }
.sys-summary { font-size:13.5px; color:#334155; line-height:1.7; margin:12px 0; padding:14px 16px; background:#f5f3ff; border-radius:10px; border-left:3px solid #7c3aed; }
.sys-issue { border-left:3px solid #fbbf24; background:#fffbeb; border-radius:8px; padding:12px 14px; margin-bottom:8px; font-size:13px; }
.sys-issue.critical { border-color:#f87171; background:#fff1f2; }
.sys-issue-title { font-weight:700; color:#1e1b2e; }
.sys-issue-desc  { color:#475569; margin-top:3px; }
.sys-issue-affected { font-size:11.5px; color:#7c3aed; margin-top:4px; }
.sys-strength { display:flex; align-items:flex-start; gap:8px; font-size:13px; color:#334155; margin-bottom:6px; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="codeReview()" x-init="init()">

    {{-- Header --}}
    <div class="cr-header">
        <div class="cr-header-left">
            <h1>웍스 코드 리뷰 (T45)</h1>
            <p>Frontend + Backend 통합 관점으로 코드를 검토합니다. T41 발견 항목은 참조하여 중복을 피합니다.</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            @if($doneScreens > 0)
            <a href="{{ $exportUrl }}" class="cr-btn secondary">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Markdown 내보내기
            </a>
            @if($systemData)
            <a href="{{ $systemUrl }}" class="cr-btn ghost">
                시스템 종합 리뷰
            </a>
            @endif
            @endif
        </div>
    </div>

    {{-- Pre-conditions --}}
    <div class="cr-precond {{ ($feCount === 0 || $beCount === 0) ? 'warn' : '' }}">
        <div class="cr-precond-row">
            <span style="font-size:16px;">{{ $feCount > 0 ? '✅' : '⬜' }}</span>
            <span><strong style="font-weight:700;">Frontend 코드 (T40)</strong>
                — {{ $feCount }}개 화면</span>
        </div>
        <div class="cr-precond-row">
            <span style="font-size:16px;">{{ $beCount > 0 ? '✅' : '⬜' }}</span>
            <span><strong style="font-weight:700;">Backend 코드 (T43)</strong>
                — {{ $beCount }}개 리소스</span>
        </div>
        <div class="cr-precond-row">
            <span style="font-size:16px;">{{ $t41Count > 0 ? '✅' : 'ℹ️' }}</span>
            <span><strong style="font-weight:700;">T41 검증 결과 (참고용)</strong>
                — {{ $t41Count }}개 화면 (없어도 진행 가능)</span>
        </div>
        @if($t44Meta)
        <div class="cr-precond-row">
            <span style="font-size:16px;">✅</span>
            <span><strong style="font-weight:700;">API 연계 (T44)</strong>
                — 매칭률 {{ $t44Meta['compliance_rate'] ?? 0 }}%</span>
        </div>
        @endif
    </div>

    {{-- Stats --}}
    @if($doneScreens > 0)
    <div class="cr-stats">
        <div class="cr-stat">
            <div class="cr-stat-label">리뷰 완료</div>
            <div class="cr-stat-value">{{ $doneScreens }}<span style="font-size:14px;color:#94a3b8;">/{{ $totalScreens }}</span></div>
        </div>
        <div class="cr-stat">
            <div class="cr-stat-label">평균 점수</div>
            <div class="cr-stat-value" style="color:{{ $avgScore >= 80 ? '#15803d' : ($avgScore >= 60 ? '#a16207' : '#b91c1c') }}">{{ $avgScore }}</div>
        </div>
        @if($systemData)
        <div class="cr-stat">
            <div class="cr-stat-label">시스템 종합</div>
            <div class="cr-stat-value" style="color:#7c3aed;">{{ $systemData['overall_score'] ?? '-' }}</div>
        </div>
        @endif
        <div class="cr-stat">
            <div class="cr-stat-label">총 추가 발견</div>
            <div class="cr-stat-value">{{ $screenData->sum('findings_count') }}</div>
        </div>
        <div class="cr-stat">
            <div class="cr-stat-label">Critical</div>
            <div class="cr-stat-value" style="color:{{ $screenData->sum('critical_count') > 0 ? '#b91c1c' : '#15803d' }}">{{ $screenData->sum('critical_count') }}</div>
        </div>
    </div>

    {{-- Category scores --}}
    @if(!empty($categoryAvgs) && array_sum($categoryAvgs) > 0)
    <div class="cr-section" style="margin-bottom:16px;">
        <div class="cr-section-title">카테고리별 평균 점수</div>
        @php
        $catLabels = [
            'spec_compliance' => ['명세 부합도', false],
            'code_quality'    => ['코드 품질', false],
            'security'        => ['보안', false],
            'best_practices'  => ['베스트 프랙티스', false],
            'performance'     => ['성능', false],
            'data_flow'       => ['데이터 흐름', true],
            'integration'     => ['통합', true],
        ];
        @endphp
        @foreach($catLabels as $key => [$label, $isNew])
        @php $score = $categoryAvgs[$key] ?? 0; @endphp
        <div class="cat-bar-wrap">
            <div class="cat-bar-label">
                <span>{{ $label }} @if($isNew)<span class="new-badge">신규</span>@endif</span>
                <span style="font-weight:700;color:{{ $score >= 80 ? '#15803d' : ($score >= 60 ? '#a16207' : '#b91c1c') }}">{{ $score }}</span>
            </div>
            <div class="cat-bar-track">
                <div class="cat-bar-fill" style="width:{{ $score }}%;background:{{ $score >= 80 ? '#4ade80' : ($score >= 60 ? '#fbbf24' : '#f87171') }}"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
    @endif

    {{-- System summary (if exists) --}}
    @if($systemData)
    <div class="sys-card">
        <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;font-weight:700;color:#7c6fa0;text-transform:uppercase;letter-spacing:.04em;">시스템 종합 점수</div>
                <div class="sys-score">{{ $systemData['overall_score'] ?? '-' }}<span style="font-size:18px;color:#94a3b8;">/100</span></div>
            </div>
            <a href="{{ $systemUrl }}" class="cr-btn ghost" style="margin-left:auto;">상세 보기</a>
        </div>

        @if(!empty($systemData['executive_summary']))
        <div class="sys-summary">{{ $systemData['executive_summary'] }}</div>
        @endif

        @if(!empty($systemData['data_flow_issues']))
        <div style="margin-top:12px;">
            <div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:8px;">데이터 흐름 이슈</div>
            @foreach(array_slice($systemData['data_flow_issues'], 0, 3) as $issue)
            <div class="sys-issue {{ ($issue['severity'] ?? '') === 'critical' ? 'critical' : '' }}">
                <div class="sys-issue-title">⚠️ {{ $issue['title'] }}</div>
                <div class="sys-issue-desc">{{ $issue['description'] }}</div>
                @if(!empty($issue['affected_screens']))
                <div class="sys-issue-affected">영향: {{ implode(', ', $issue['affected_screens']) }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($systemData['strengths']))
        <div style="margin-top:12px;">
            <div style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;margin-bottom:8px;">잘된 점 ✅</div>
            @foreach($systemData['strengths'] as $s)
            <div class="sys-strength"><span style="color:#4ade80;flex-shrink:0;">✓</span> {{ $s }}</div>
            @endforeach
        </div>
        @endif
    </div>
    @endif

    {{-- Launch banner --}}
    @if($feCount > 0)
    <div class="cr-banner">
        <div class="cr-banner-text">
            <h3>🤖 통합 리뷰 시작</h3>
            <p>{{ $totalScreens }}개 화면 + 시스템 종합 1회 · 예상 비용: ~${{ number_format($estimatedCost, 2) }}</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;">
            <button class="cr-start-btn" @click="startReview()">
                <svg width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span x-text="reviewing ? '리뷰 중...' : ({{ $doneScreens > 0 ? 'true' : 'false' }} ? '재리뷰' : '리뷰 시작')"></span>
            </button>
        </div>
    </div>

    {{-- Progress --}}
    <div x-show="reviewing || progressLog.length > 0" x-cloak style="margin-bottom:16px;">
        <div class="cr-section">
            <div class="cr-section-title">
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><polyline stroke-width="2" points="12 6 12 12 16 14"/></svg>
                진행 상황
            </div>
            <div class="progress-bar-wrap">
                <div class="progress-bar-fill" :style="`width:${progress}%`"></div>
            </div>
            <div style="font-size:12px;color:#64748b;margin-bottom:10px;" x-text="progressMsg"></div>
            <div class="progress-log" x-ref="logBox">
                <template x-for="(line, i) in progressLog" :key="i">
                    <div class="progress-log-line" :class="line.cls" x-text="line.text"></div>
                </template>
            </div>
            <div style="margin-top:10px;display:flex;gap:8px;">
                <button x-show="reviewing" @click="cancelReview()" class="cr-btn secondary" style="font-size:12px;padding:4px 12px;">취소</button>
            </div>
        </div>
    </div>
    @else
    <div class="cr-section" style="text-align:center;padding:32px;color:#94a3b8;">
        <div style="font-size:32px;margin-bottom:8px;">⬜</div>
        <div style="font-weight:700;color:#475569;">Frontend 코드를 먼저 생성하세요 (T40)</div>
    </div>
    @endif

    {{-- Screen grid --}}
    @if($totalScreens > 0)
    <div class="cr-section">
        <div class="cr-section-title">
            화면별 리뷰 결과
            <span style="font-size:11px;font-weight:500;color:#94a3b8;">{{ $doneScreens }}/{{ $totalScreens }} 완료</span>
        </div>
        <div class="cr-screen-grid">
            @foreach($screenData as $item)
            @php
                $hasReview = $item['has_review'];
                $score     = $item['compliance_score'];
                $critical  = $item['critical_count'];
                $findings  = $item['findings_count'];
                $cardClass = !$hasReview ? '' : ($critical > 0 ? 'critical' : ($findings > 0 ? 'issues' : 'reviewed'));
                $badgeCls  = !$score ? '' : ($score >= 80 ? 'good' : ($score >= 60 ? 'warn' : 'bad'));
            @endphp
            <div class="cr-screen-card {{ $cardClass }}">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;">
                    <span class="cr-screen-card-id">{{ $item['screen']->screen_id }}</span>
                    @if($hasReview)
                    <span class="cr-score-badge {{ $badgeCls }}">{{ $score }}</span>
                    @endif
                </div>
                <div class="cr-screen-card-title">{{ $item['screen']->title }}</div>
                @if($hasReview)
                <div style="font-size:11.5px;color:#64748b;">
                    추가 발견 {{ $findings }}건
                    @if($critical > 0)<span style="color:#b91c1c;font-weight:700;"> · Critical {{ $critical }}건</span>@endif
                </div>
                @else
                <div style="font-size:11.5px;color:#94a3b8;">미리뷰</div>
                @endif
                @if($hasReview)
                <a href="{{ $item['show_url'] }}" class="cr-btn ghost" style="font-size:11.5px;padding:3px 10px;margin-top:2px;">상세 보기</a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Cost confirm modal --}}
    <div x-show="showConfirm" x-cloak class="confirm-overlay" @click.self="showConfirm = false">
        <div class="confirm-box">
            <h3>리뷰 비용 확인</h3>
            <div class="cost-highlight">~$<span x-text="confirmCost"></span></div>
            <p><span x-text="confirmScreens"></span>개 화면 + 시스템 종합 1회</p>
            <p style="font-size:12px;color:#94a3b8;">화면별 ~${{ number_format(0.40, 2) }} + 시스템 ~${{ number_format(0.20, 2) }}</p>
            <div x-show="confirmCost > 5" class="cr-precond warn" style="margin:8px 0 12px;padding:8px 12px;font-size:12.5px;">
                ⚠️ 비용이 $5를 초과합니다. 계속 진행하시겠습니까?
            </div>
            <div class="confirm-actions">
                <button @click="showConfirm = false" class="cr-btn secondary">취소</button>
                <button @click="confirmAndStart()" class="cr-btn primary">확인 후 시작</button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const _BATCH_START_URL  = @json($batchStartUrl);
const _BATCH_SSE_TPLURL = @json($batchSseUrlTpl);
const _CANCEL_URL_TPL   = @json($cancelUrlTpl);

function codeReview() {
    return {
        reviewing: false,
        progress: 0,
        progressMsg: '',
        progressLog: [],
        showConfirm: false,
        confirmCost: 0,
        confirmScreens: 0,
        sessionId: null,
        sse: null,

        init() {},

        startReview() {
            fetch(_BATCH_START_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ confirmed_cost: false }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.requiresConfirmation) {
                    this.confirmCost = parseFloat(data.estimatedCost).toFixed(2);
                    this.confirmScreens = data.screenCount;
                    this.showConfirm = true;
                }
            })
            .catch(e => alert('오류: ' + e.message));
        },

        confirmAndStart() {
            this.showConfirm = false;
            this.reviewing = true;
            this.progress = 0;
            this.progressLog = [];
            this.progressMsg = '리뷰 준비 중...';

            fetch(_BATCH_START_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ confirmed_cost: true }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) throw new Error(data.message || '세션 생성 실패');
                this.sessionId = data.sessionId;
                this.connectSse(data.sessionId);
            })
            .catch(e => { this.reviewing = false; alert('오류: ' + e.message); });
        },

        connectSse(sessionId) {
            const url = _BATCH_SSE_TPLURL.replace('SESSION_ID', sessionId);
            this.sse = new EventSource(url);

            const handle = (ev, data) => {
                if (ev === 'screen_start') {
                    this.progress = data.progress || 0;
                    this.progressMsg = `[${data.screen_id}] ${data.title} 리뷰 중...`;
                    this.addLog(`▶ [${data.screen_id}] ${data.title}`, 'active');
                } else if (ev === 'screen_done') {
                    this.progress = data.progress || 0;
                    this.addLog(`✓ [${data.screen_id}] ${data.title} — ${data.compliance_score}점, 추가발견 ${data.findings_count}건`, 'ok');
                } else if (ev === 'screen_error') {
                    this.addLog(`✗ [${data.screen_id}] ${data.error}`, 'fail');
                } else if (ev === 'system_start') {
                    this.progress = data.progress || 92;
                    this.progressMsg = '시스템 종합 리뷰 중...';
                    this.addLog('▶ 시스템 종합 리뷰 중...', 'system');
                } else if (ev === 'system_done') {
                    this.addLog(`✓ 시스템 종합 — ${data.overall_score}점`, 'ok');
                } else if (ev === 'complete') {
                    this.progress = 100;
                    this.progressMsg = `완료 — ${data.done}개 처리, ${data.failed}개 실패`;
                    this.addLog(`완료: ${data.done}개 (실패 ${data.failed}개)`, 'ok');
                    this.reviewing = false;
                    this.sse?.close();
                    setTimeout(() => location.reload(), 1500);
                } else if (ev === 'error') {
                    this.progressMsg = '오류: ' + data.message;
                    this.addLog('오류: ' + data.message, 'fail');
                    this.reviewing = false;
                    this.sse?.close();
                }
            };

            ['status','start','screen_start','screen_done','screen_error','system_start','system_done','complete','error'].forEach(ev => {
                this.sse.addEventListener(ev, e => handle(ev, JSON.parse(e.data)));
            });
            this.sse.onerror = () => { this.reviewing = false; this.sse?.close(); };
        },

        addLog(text, cls) {
            this.progressLog.push({ text, cls });
            this.$nextTick(() => {
                const box = this.$refs.logBox;
                if (box) box.scrollTop = box.scrollHeight;
            });
        },

        cancelReview() {
            if (this.sessionId) {
                fetch(_CANCEL_URL_TPL.replace('SESSION_ID', this.sessionId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                });
            }
            this.sse?.close();
            this.reviewing = false;
            this.progressMsg = '취소됨';
        },
    };
}
</script>
@endpush
