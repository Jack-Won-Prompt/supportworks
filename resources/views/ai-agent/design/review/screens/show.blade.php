@extends('layouts.ai-agent')
@section('title', ($screen->screen_id ?? '') . ' 검수 상세 — 웍스 Agent')

@push('styles')
<style>
.drss-wrap     { max-width: 800px; }
.drss-card     { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:14px; }
.drss-card-ttl { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:12px; display:flex; align-items:center; gap:7px; }
.drss-card-ttl::after { content:''; flex:1; height:1px; background:#f1f5f9; }

.drss-score-row { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
.drss-score-num { font-size:44px; font-weight:900; font-family:monospace; line-height:1; }
.drss-score-bar { flex:1; height:8px; border-radius:4px; background:#e2e8f0; overflow:hidden; }
.drss-score-fill{ height:100%; border-radius:4px; }

.drss-cat-row  { display:flex; align-items:center; gap:8px; margin-bottom:6px; }
.drss-cat-lbl  { font-size:12px; color:#64748b; width:68px; flex-shrink:0; }
.drss-cat-bar  { flex:1; height:4px; border-radius:2px; background:#f1f5f9; overflow:hidden; }
.drss-cat-fill { height:100%; border-radius:2px; }
.drss-cat-score{ font-size:11.5px; font-weight:700; width:28px; text-align:right; flex-shrink:0; }

.drss-violation { border:1.5px solid #f1f5f9; border-radius:10px; padding:12px 14px; margin-bottom:8px; }
.drss-violation.critical { border-color:#fca5a5; background:#fef9f9; }
.drss-violation.warning  { border-color:#fcd34d; background:#fffdf0; }
.drss-violation.info     { border-color:#bfdbfe; background:#f0f7ff; }
.drss-violation.ignored  { opacity:.45; }
.drss-v-hdr    { display:flex; align-items:flex-start; gap:8px; margin-bottom:5px; }
.drss-v-title  { font-size:13px; font-weight:700; color:#374151; flex:1; }
.drss-v-body   { font-size:12.5px; color:#64748b; line-height:1.6; }
.drss-v-detail { margin-top:7px; display:flex; gap:14px; flex-wrap:wrap; }
.drss-v-chip   { font-size:11px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:5px; padding:2px 7px; color:#475569; }
.drss-v-chip strong { color:#374151; }

.drss-severity-badge { display:inline-flex; align-items:center; font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:5px; flex-shrink:0; }
.drss-severity-badge.critical { background:#fef2f2; color:#dc2626; }
.drss-severity-badge.warning  { background:#fffbeb; color:#d97706; }
.drss-severity-badge.info     { background:#eff6ff; color:#2563eb; }

.drss-strength { display:flex; align-items:flex-start; gap:7px; padding:7px 0; border-bottom:1px solid #f8fafc; font-size:13px; color:#374151; }
.drss-strength:last-child { border-bottom:none; }

.drss-btn { display:inline-flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.drss-btn-primary { background:#7c3aed; color:#fff; }
.drss-btn-primary:hover { background:#6d28d9; }
.drss-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.drss-btn-outline:hover { border-color:#a78bfa; color:#7c3aed; }
.drss-btn:disabled { opacity:.5; cursor:not-allowed; }

.drss-toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:500; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.drss-toast.show { opacity:1; transform:translateY(0); }
</style>
@endpush

@section('page-actions')
<a href="{{ route('ai-agent.projects.design.validation', $project) }}" class="drss-btn drss-btn-outline">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    검수 목록
</a>
@if($screen->hasFigmaMapping())
<a href="{{ $screen->getFigmaViewUrl() }}" target="_blank" class="drss-btn drss-btn-outline">Figma 열기</a>
@endif
@endsection

@section('ai-agent-content')
@php
    $score     = $result['compliance_score'] ?? null;
    $catScores = $result['category_scores'] ?? [];
    $violations= $result['violations'] ?? [];
    $strengths = $result['strengths'] ?? [];
    $scoreColor= fn($s) => $s >= 80 ? '#16a34a' : ($s >= 60 ? '#d97706' : '#dc2626');
    $scoreGrad = fn($s) => $s >= 80 ? 'linear-gradient(90deg,#22c55e,#86efac)' : ($s >= 60 ? 'linear-gradient(90deg,#f59e0b,#fcd34d)' : 'linear-gradient(90deg,#ef4444,#fca5a5)');
@endphp

<div x-data="drssPage(@json([
    'screenId'     => $screen->screen_id,
    'saveUrl'      => route('ai-agent.projects.design.review.save', $project),
    'regenUrl'     => route('ai-agent.projects.design.review.regenerate', [$project, $screen]),
    'csrfToken'    => csrf_token(),
]))" class="drss-wrap">

    <div class="drss-toast" :class="{ show: toast.show }" x-text="toast.msg"></div>

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:20px;flex-wrap:wrap;">
        <div style="font-size:12px;font-weight:800;color:#7c3aed;background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:8px;padding:6px 14px;font-family:monospace;white-space:nowrap;">
            {{ $screen->screen_id }}
        </div>
        <div style="flex:1;">
            <h1 style="font-size:20px;font-weight:800;color:#1e1b2e;margin:0 0 4px;">{{ $screen->title }}</h1>
            @if($screen->figma_frame_name)
            <div style="font-size:12px;color:#94a3b8;">Figma 프레임: {{ $screen->figma_frame_name }}</div>
            @endif
        </div>
        @if($artifact)
        <button class="drss-btn drss-btn-outline" @click="doRegenerate()" :disabled="regenerating">
            <span x-text="regenerating ? '재검수 중...' : '단일 재검수'"></span>
        </button>
        @endif
    </div>

    @if($result === null)
    <div class="drss-card" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:14px;font-weight:600;">이 화면의 검수 결과가 없습니다.</div>
        <div style="font-size:12.5px;margin-top:4px;">전체 검수를 실행하거나 단일 재검수를 눌러주세요.</div>
        <button class="drss-btn drss-btn-primary" style="margin-top:14px;" @click="doRegenerate()" :disabled="regenerating">
            <span x-text="regenerating ? '재검수 중...' : '🤖 이 화면 검수'"></span>
        </button>
    </div>
    @else

    {{-- Score --}}
    <div class="drss-card">
        <div class="drss-card-ttl">검수 점수</div>
        <div class="drss-score-row">
            <div class="drss-score-num" style="color:{{ $scoreColor($score) }};">{{ $score }}</div>
            <div style="flex:1;">
                <div class="drss-score-bar">
                    <div class="drss-score-fill" style="width:{{ $score }}%;background:{{ $scoreGrad($score) }};"></div>
                </div>
                <div style="font-size:11.5px;color:#94a3b8;margin-top:4px;">/ 100점</div>
            </div>
        </div>
        @foreach(['color' => '색상', 'typography' => '타이포', 'component' => '컴포넌트', 'layout' => '레이아웃'] as $key => $label)
        @php $cs = $catScores[$key] ?? 0; @endphp
        <div class="drss-cat-row">
            <span class="drss-cat-lbl">{{ $label }}</span>
            <div class="drss-cat-bar"><div class="drss-cat-fill" style="width:{{ $cs }}%;background:{{ $scoreGrad($cs) }};"></div></div>
            <span class="drss-cat-score" style="color:{{ $scoreColor($cs) }};">{{ $cs }}</span>
        </div>
        @endforeach
    </div>

    {{-- Violations --}}
    <div class="drss-card" x-data="{ ignoredIds: [] }">
        <div class="drss-card-ttl">
            위반 사항 ({{ count($violations) }}건)
            @if(count($violations) > 0)
            <button class="drss-btn drss-btn-outline" style="padding:3px 10px;font-size:11.5px;margin-left:8px;"
                    @click="saveIgnored()">무시 항목 저장</button>
            @endif
        </div>

        @if(empty($violations))
        <div style="text-align:center;padding:20px;color:#16a34a;font-size:13px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:block;margin:0 auto 8px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            위반 사항이 없습니다. 훌륭합니다!
        </div>
        @else
        @foreach($violations as $idx => $v)
        @php
            $sid = $v['id'] ?? "V-{$idx}";
            $isIgnored = $v['ignored'] ?? false;
        @endphp
        <div class="drss-violation {{ $v['severity'] ?? 'info' }}{{ $isIgnored ? ' ignored' : '' }}" x-data="{ ignored: {{ $isIgnored ? 'true' : 'false' }}, sid: '{{ $sid }}' }"
             @change="ignored ? (ignoredIds.indexOf(sid) === -1 && ignoredIds.push(sid)) : (ignoredIds = ignoredIds.filter(i => i !== sid))">
            <div class="drss-v-hdr">
                <span class="drss-severity-badge {{ $v['severity'] ?? 'info' }}">
                    {{ match($v['severity'] ?? 'info') { 'critical' => '🔴 Critical', 'warning' => '🟡 Warning', default => '🔵 Info' } }}
                </span>
                <span class="drss-v-title">{{ $v['title'] ?? '' }}</span>
                <label style="display:flex;align-items:center;gap:4px;font-size:11.5px;color:#94a3b8;cursor:pointer;flex-shrink:0;">
                    <input type="checkbox" :checked="ignored" @change="ignored = !ignored" style="accent-color:#7c3aed;">
                    무시
                </label>
            </div>
            <div class="drss-v-body">{{ $v['description'] ?? '' }}</div>
            @if(($v['current_value'] ?? null) || ($v['suggested_value'] ?? null) || ($v['location'] ?? null))
            <div class="drss-v-detail">
                @if($v['current_value'] ?? null)
                <span class="drss-v-chip"><strong>현재: </strong>{{ $v['current_value'] }}</span>
                @endif
                @if($v['suggested_value'] ?? null)
                <span class="drss-v-chip"><strong>권장: </strong>{{ $v['suggested_value'] }}</span>
                @endif
                @if($v['location'] ?? null)
                <span class="drss-v-chip">📍 {{ $v['location'] }}</span>
                @endif
            </div>
            @endif
        </div>
        @endforeach
        @endif

        <script>
        function drssPage(cfg) {
            return {
                cfg, regenerating: false,
                toast: { show: false, msg: '' },
                showToast(msg) { this.toast = { show: true, msg }; setTimeout(() => this.toast.show = false, 3000); },
                async saveIgnored() {
                    // collect from inner x-data scopes via DOM
                    const checked = [...document.querySelectorAll('.drss-violation input[type=checkbox]:checked')]
                        .map(el => el.closest('[x-data]')?.__x?.$data?.sid).filter(Boolean);
                    try {
                        await axios.post(this.cfg.saveUrl, { screen_id: this.cfg.screenId, ignored_ids: checked, _token: this.cfg.csrfToken });
                        this.showToast('저장되었습니다.');
                    } catch (e) { this.showToast('저장 실패'); }
                },
                async doRegenerate() {
                    this.regenerating = true;
                    try {
                        const res = await axios.post(this.cfg.regenUrl, { _token: this.cfg.csrfToken });
                        if (res.data.success) {
                            this.showToast(res.data.message);
                            setTimeout(() => window.location.reload(), 1500);
                        } else { this.showToast(res.data.message); }
                    } catch (e) { this.showToast(e.response?.data?.message ?? '오류'); }
                    this.regenerating = false;
                },
            };
        }
        </script>
    </div>

    {{-- Strengths --}}
    @if(!empty($strengths))
    <div class="drss-card">
        <div class="drss-card-ttl">잘된 점</div>
        @foreach($strengths as $s)
        <div class="drss-strength">
            <span style="color:#22c55e;font-size:14px;flex-shrink:0;">✓</span>
            <span>{{ $s }}</span>
        </div>
        @endforeach
    </div>
    @endif

    @endif {{-- result null --}}

</div>
@endsection
