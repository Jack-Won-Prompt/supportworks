@extends('layouts.ai-agent')
@section('title', $pageTitle . ' — 웍스 Agent')

@push('styles')
<style>
.crs-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.crs-header-left h1 { font-size:20px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.crs-header-left p  { font-size:13px; color:#64748b; margin:0; }

.crs-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; transition:all .15s; text-decoration:none; white-space:nowrap; }
.crs-btn.primary   { background:#7c3aed; color:#fff; }
.crs-btn.primary:hover { background:#6d28d9; }
.crs-btn.primary[disabled] { opacity:.4; cursor:not-allowed; pointer-events:none; }
.crs-btn.secondary { background:#f1f5f9; color:#475569; border:1.5px solid #e2e8f0; }
.crs-btn.secondary:hover { background:#e2e8f0; }
.crs-btn.ghost { background:transparent; color:#7c3aed; border:1.5px solid #c4b5fd; }
.crs-btn.ghost:hover { background:#f5f3ff; }
.crs-btn.danger { background:#fee2e2; color:#b91c1c; border:1.5px solid #fecaca; }
.crs-btn.danger:hover { background:#fecaca; }

.crs-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:18px 20px; margin-bottom:14px; }
.crs-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 14px; display:flex; align-items:center; gap:8px; }

.score-big { font-size:42px; font-weight:800; margin-right:4px; }
.score-big.good { color:#15803d; }
.score-big.warn { color:#a16207; }
.score-big.bad  { color:#b91c1c; }

.cat-bar-wrap { margin-bottom:8px; }
.cat-bar-label { display:flex; justify-content:space-between; font-size:12px; color:#475569; margin-bottom:3px; }
.new-badge { font-size:10px; background:#ede8ff; color:#7c3aed; border-radius:4px; padding:0 5px; font-weight:700; }
.cat-bar-track { background:#f1f5f9; border-radius:99px; height:7px; overflow:hidden; }
.cat-bar-fill { height:100%; border-radius:99px; transition:width .4s; }

.finding-card { border-radius:10px; padding:14px 16px; margin-bottom:10px; border-left:4px solid; }
.finding-card.t41     { border-color:#cbd5e1; background:#f8fafc; }
.finding-card.critical{ border-color:#f87171; background:#fff1f2; }
.finding-card.warning { border-color:#fbbf24; background:#fffbeb; }
.finding-card.info    { border-color:#60a5fa; background:#eff6ff; }
.finding-card.fixed   { border-color:#4ade80; background:#f0fdf4; opacity:.75; }
.finding-card.ignored { opacity:.45; }
.finding-tag { font-size:10.5px; font-weight:700; text-transform:uppercase; border-radius:4px; padding:2px 7px; display:inline-block; margin-right:4px; }
.finding-tag.critical { background:#fee2e2; color:#b91c1c; }
.finding-tag.warning  { background:#fef9c3; color:#a16207; }
.finding-tag.info     { background:#dbeafe; color:#1d4ed8; }
.finding-tag.t41      { background:#f1f5f9; color:#64748b; }
.finding-tag.cat      { background:#ede8ff; color:#7c3aed; }
.finding-title  { font-size:13.5px; font-weight:700; color:#1e1b2e; margin:6px 0 4px; }
.finding-desc   { font-size:13px; color:#475569; margin-bottom:6px; }
.finding-file   { font-size:11.5px; font-family:monospace; color:#7c3aed; background:#f5f3ff; border-radius:5px; padding:2px 8px; display:inline-block; margin-right:4px; }
.finding-suggestion { font-size:12.5px; color:#334155; background:#f8fafc; border-radius:7px; padding:8px 12px; margin-top:6px; border-left:2px solid #c4b5fd; }
.finding-actions { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
.finding-action-btn { font-size:11.5px; padding:3px 10px; border-radius:7px; border:1.5px solid; cursor:pointer; font-weight:600; display:inline-flex; align-items:center; gap:4px; transition:all .15s; }
.finding-action-btn.fix    { border-color:#4ade80; color:#15803d; background:#f0fdf4; }
.finding-action-btn.fix:hover { background:#dcfce7; }
.finding-action-btn.ignore { border-color:#cbd5e1; color:#64748b; background:#f8fafc; }
.finding-action-btn.ignore:hover { background:#e2e8f0; }

.section-divider { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin:16px 0 10px; padding-bottom:6px; border-bottom:1.5px dashed #e2e8f0; }
</style>
@endpush

@section('ai-agent-content')
<div x-data="screenReview()" x-init="init()">

    {{-- Header --}}
    <div class="crs-header">
        <div class="crs-header-left">
            <h1>
                <span style="font-family:monospace;font-size:15px;color:#7c3aed;background:#f5f3ff;border-radius:6px;padding:2px 10px;margin-right:8px;">{{ $screen->screen_id }}</span>
                {{ $screen->title }}
            </h1>
            <p>웍스 코드 리뷰 상세 — T41 발견 + T45 추가 발견</p>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="{{ $indexUrl }}" class="crs-btn secondary">← 목록</a>
            @if($hasReview)
            <button class="crs-btn ghost" @click="regenerate()" :disabled="regenerating">
                <span x-text="regenerating ? '리뷰 중...' : '재리뷰'"></span>
            </button>
            @endif
        </div>
    </div>

    @if(!$hasReview)
    <div class="crs-section" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:32px;margin-bottom:8px;">🔍</div>
        <div style="font-weight:700;color:#475569;margin-bottom:12px;">아직 리뷰가 없습니다</div>
        <button class="crs-btn primary" @click="regenerate()" :disabled="regenerating">
            <span x-text="regenerating ? '리뷰 중...' : '리뷰 시작'"></span>
        </button>
    </div>
    @else

    @php
        $score     = $decoded['compliance_score'] ?? 0;
        $catScores = $decoded['category_scores'] ?? [];
        $t41       = $decoded['from_t41'] ?? [];
        $additional = $decoded['additional_findings'] ?? [];
        $activeAdditional = array_filter($additional, fn($f) => empty($f['ignored']) && empty($f['fixed']));
        $fixedAdditional  = array_filter($additional, fn($f) => !empty($f['fixed']));
        $ignoredAdditional = array_filter($additional, fn($f) => !empty($f['ignored']) && empty($f['fixed']));
        $scoreCls = $score >= 80 ? 'good' : ($score >= 60 ? 'warn' : 'bad');
    @endphp

    {{-- Score + Category --}}
    <div class="crs-section">
        <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
            <div>
                <div style="font-size:12px;font-weight:700;color:#7c6fa0;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px;">종합 점수</div>
                <div>
                    <span class="score-big {{ $scoreCls }}">{{ $score }}</span>
                    <span style="font-size:18px;color:#94a3b8;">/100</span>
                </div>
            </div>
            <div style="flex:1;min-width:260px;">
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
                @php $cs = $catScores[$key] ?? 0; @endphp
                <div class="cat-bar-wrap">
                    <div class="cat-bar-label">
                        <span>{{ $label }} @if($isNew)<span class="new-badge">신규</span>@endif</span>
                        <span style="font-weight:700;color:{{ $cs >= 80 ? '#15803d' : ($cs >= 60 ? '#a16207' : '#b91c1c') }}">{{ $cs }}</span>
                    </div>
                    <div class="cat-bar-track">
                        <div class="cat-bar-fill" style="width:{{ $cs }}%;background:{{ $cs >= 80 ? '#4ade80' : ($cs >= 60 ? '#fbbf24' : '#f87171') }}"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- T41 findings (reference) --}}
    @if(!empty($t41))
    <div class="crs-section">
        <div class="crs-section-title">
            <span style="background:#f1f5f9;color:#64748b;border-radius:5px;padding:1px 8px;font-size:11px;">T41</span>
            이미 발견된 위반 ({{ count($t41) }}건)
            <span style="font-size:11px;font-weight:400;color:#94a3b8;">— 참고용, T45에서 중복 처리 안 함</span>
        </div>
        @foreach($t41 as $v)
        <div class="finding-card t41 {{ !empty($v['ignored']) ? 'ignored' : (!empty($v['fixed']) ? 'fixed' : '') }}">
            <div>
                <span class="finding-tag t41">T41</span>
                <span class="finding-tag {{ $v['severity'] ?? 'info' }}">{{ strtoupper($v['severity'] ?? 'info') }}</span>
                <span class="finding-tag cat">{{ $v['category'] ?? '' }}</span>
                @if(!empty($v['fixed']))<span class="finding-tag" style="background:#dcfce7;color:#15803d;">수정됨</span>@endif
                @if(!empty($v['ignored']))<span class="finding-tag" style="background:#f1f5f9;color:#94a3b8;">무시됨</span>@endif
            </div>
            <div class="finding-title">{{ $v['title'] ?? '' }}</div>
            <div class="finding-desc">{{ $v['description'] ?? '' }}</div>
            @if(!empty($v['file']))
            <div><span class="finding-file">{{ $v['file'] }}{{ !empty($v['line']) ? ':' . $v['line'] : '' }}</span></div>
            @endif
        </div>
        @endforeach
    </div>
    @endif

    {{-- T45 additional findings --}}
    <div class="crs-section">
        <div class="crs-section-title">
            <span style="background:#ede8ff;color:#7c3aed;border-radius:5px;padding:1px 8px;font-size:11px;">T45</span>
            추가 발견 ({{ count($activeAdditional) }}건 활성)
        </div>

        @if(empty($additional))
        <div style="text-align:center;padding:20px;color:#94a3b8;font-size:13px;">추가 발견 없음 — T41이 충분히 처리했습니다.</div>
        @else

        @foreach($activeAdditional as $f)
        <div class="finding-card {{ $f['severity'] ?? 'info' }}" id="finding-{{ $f['id'] ?? '' }}">
            <div>
                <span class="finding-tag {{ $f['severity'] ?? 'info' }}">{{ strtoupper($f['severity'] ?? 'info') }}</span>
                <span class="finding-tag cat">{{ $f['category'] ?? '' }}</span>
                @if(!empty($f['auto_fixable']))<span class="finding-tag" style="background:#dcfce7;color:#15803d;">자동수정 가능</span>@endif
            </div>
            <div class="finding-title">{{ $f['title'] ?? '' }}</div>
            <div class="finding-desc">{{ $f['description'] ?? '' }}</div>
            <div>
                @if(!empty($f['frontend_file']))<span class="finding-file">FE: {{ $f['frontend_file'] }}</span>@endif
                @if(!empty($f['backend_file']))<span class="finding-file">BE: {{ $f['backend_file'] }}</span>@endif
            </div>
            @if(!empty($f['suggestion']))
            <div class="finding-suggestion">💡 {{ $f['suggestion'] }}</div>
            @endif
            <div class="finding-actions">
                @if(!empty($f['auto_fixable']))
                <button class="finding-action-btn fix" @click="autoFix('{{ $f['id'] ?? '' }}')">
                    ✨ 자동 수정
                </button>
                @endif
                <button class="finding-action-btn ignore" @click="ignore('{{ $f['id'] ?? '' }}')">
                    무시
                </button>
            </div>
        </div>
        @endforeach

        @if(!empty($fixedAdditional))
        <div class="section-divider">수정된 항목 ({{ count($fixedAdditional) }}건)</div>
        @foreach($fixedAdditional as $f)
        <div class="finding-card fixed">
            <span class="finding-tag" style="background:#dcfce7;color:#15803d;">수정됨</span>
            <span class="finding-tag cat">{{ $f['category'] ?? '' }}</span>
            <div class="finding-title" style="opacity:.7;">{{ $f['title'] ?? '' }}</div>
        </div>
        @endforeach
        @endif

        @if(!empty($ignoredAdditional))
        <div class="section-divider">무시된 항목 ({{ count($ignoredAdditional) }}건)</div>
        @foreach($ignoredAdditional as $f)
        <div class="finding-card ignored">
            <span class="finding-tag t41">무시됨</span>
            <div class="finding-title">{{ $f['title'] ?? '' }}</div>
        </div>
        @endforeach
        @endif

        @endif
    </div>

    {{-- Error message --}}
    <div x-show="errorMsg" x-cloak style="background:#fff1f2;border:1.5px solid #fecaca;border-radius:10px;padding:12px 16px;margin-bottom:14px;font-size:13px;color:#b91c1c;" x-text="errorMsg"></div>

    @endif

</div>
@endsection

@push('scripts')
<script>
const _REGENERATE_URL = @json($regenerateUrl);
const _AUTO_FIX_URL   = @json($autoFixUrlTpl);
const _IGNORE_URL_TPL = @json($ignoreUrlTpl);

async function screenReview() {
    return {
        regenerating: false,
        errorMsg: '',

        init() {},

        regenerate() {
            if (!await __confirm('이 화면의 코드 리뷰를 다시 실행하시겠습니까?')) return;
            this.regenerating = true;
            this.errorMsg = '';

            fetch(_REGENERATE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({}),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.errorMsg = data.message || '오류가 발생했습니다.';
                    this.regenerating = false;
                }
            })
            .catch(e => { this.errorMsg = e.message; this.regenerating = false; });
        },

        autoFix(findingId) {
            if (!await __confirm('이 항목을 자동 수정하시겠습니까?')) return;

            fetch(_AUTO_FIX_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: JSON.stringify({ finding_id: findingId }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.errorMsg = data.message || '자동 수정 실패';
                }
            })
            .catch(e => { this.errorMsg = e.message; });
        },

        ignore(findingId) {
            if (!await __confirm('이 항목을 무시하시겠습니까?')) return;

            const url = _IGNORE_URL_TPL.replace('FINDING_ID', findingId);
            fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    this.errorMsg = data.message || '무시 처리 실패';
                }
            })
            .catch(e => { this.errorMsg = e.message; });
        },
    };
}
</script>
@endpush
