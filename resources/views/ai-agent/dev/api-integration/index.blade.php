@extends('layouts.ai-agent')
@section('title', 'API 연계 — 웍스 Agent')

@push('styles')
<style>
/* ── Header ──────────────────────────────────────────────────────── */
.api-header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:20px; }
.api-header h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.api-header p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Pre-condition ───────────────────────────────────────────────── */
.api-pre { display:flex; flex-wrap:wrap; gap:8px; padding:12px 18px; background:#fff; border:1.5px solid #ede8ff; border-radius:12px; margin-bottom:16px; }
.api-cond { display:flex; align-items:center; gap:6px; padding:5px 12px; border-radius:8px; font-size:12.5px; font-weight:600; }
.api-cond.ok  { background:#f0fdf4; color:#16a34a; }
.api-cond.err { background:#fef2f2; color:#b91c1c; }

/* ── Stats ───────────────────────────────────────────────────────── */
.api-stats { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
.api-stat  { flex:1; min-width:110px; background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:14px 16px; text-align:center; }
.api-stat-val { font-size:24px; font-weight:800; }
.api-stat-lbl { font-size:11px; color:#94a3b8; margin-top:2px; font-weight:600; }

/* ── Progress bar ────────────────────────────────────────────────── */
.api-rate-wrap { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; padding:16px 20px; margin-bottom:16px; }
.api-rate-label { display:flex; justify-content:space-between; font-size:13px; font-weight:700; margin-bottom:8px; }
.api-rate-bg { background:#ede8ff; border-radius:99px; height:12px; overflow:hidden; }
.api-rate-bar { height:12px; border-radius:99px; transition:width .4s ease; }
.api-rate-bar.high { background:linear-gradient(90deg,#16a34a,#4ade80); }
.api-rate-bar.mid  { background:linear-gradient(90deg,#d97706,#fbbf24); }
.api-rate-bar.low  { background:linear-gradient(90deg,#b91c1c,#f87171); }

/* ── Section ─────────────────────────────────────────────────────── */
.api-section { background:#fff; border:1.5px solid #ede8ff; border-radius:12px; overflow:hidden; margin-bottom:16px; }
.api-section-header { display:flex; align-items:center; justify-content:space-between; padding:12px 16px; border-bottom:1.5px solid #f1f5f9; }
.api-section-title  { font-size:13px; font-weight:700; color:#1e1b2e; }
.api-section-badge  { font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:99px; }
.badge-green  { background:#dcfce7; color:#15803d; }
.badge-yellow { background:#fef3c7; color:#92400e; }
.badge-blue   { background:#dbeafe; color:#1d4ed8; }

/* ── Match item ──────────────────────────────────────────────────── */
.api-match { padding:11px 16px; border-bottom:1px solid #f8fafc; font-size:12.5px; }
.api-match:last-child { border-bottom:none; }
.api-match-top { display:flex; align-items:center; gap:8px; margin-bottom:4px; }
.api-method { display:inline-block; padding:1px 7px; border-radius:4px; font-size:10.5px; font-weight:700; min-width:48px; text-align:center; }
.api-method.GET    { background:#dbeafe; color:#1d4ed8; }
.api-method.POST   { background:#dcfce7; color:#15803d; }
.api-method.PUT,.api-method.PATCH  { background:#fef3c7; color:#92400e; }
.api-method.DELETE { background:#fee2e2; color:#b91c1c; }
.api-match-url  { font-family:monospace; color:#1e1b2e; font-size:12px; font-weight:600; }
.api-match-meta { font-size:11.5px; color:#94a3b8; margin-top:2px; }
.api-match-ctrl { font-size:11px; color:#7c3aed; font-family:monospace; }

/* ── Unmatched ───────────────────────────────────────────────────── */
.api-unmatched { padding:11px 16px; border-bottom:1px solid #f8fafc; font-size:12.5px; }
.api-unmatched:last-child { border-bottom:none; }
.api-unmatched.fe  { border-left:3px solid #f59e0b; }
.api-unmatched.be  { border-left:3px solid #60a5fa; }
.api-unmatched-issue { font-size:11.5px; color:#92400e; margin-top:3px; }
.api-unmatched-suggest { font-size:11px; color:#94a3b8; margin-top:2px; }

/* ── Integration files ───────────────────────────────────────────── */
.api-files-list { padding:12px 16px; display:flex; flex-direction:column; gap:8px; }
.api-file-item  { display:flex; align-items:center; gap:10px; }
.api-file-name  { font-family:monospace; font-size:12.5px; color:#475569; flex:1; }
.api-file-badge { font-size:10.5px; padding:2px 8px; border-radius:99px; background:#ede9fe; color:#6d28d9; font-weight:600; }

/* ── Buttons ─────────────────────────────────────────────────────── */
.btn-primary   { display:inline-flex;align-items:center;gap:5px;padding:8px 18px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .15s; }
.btn-primary:hover   { opacity:.9; }
.btn-secondary { display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#fff;color:#7c3aed;border:1.5px solid #c4b5fd;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-secondary:hover { background:#f5f3ff; }
.btn-sm { padding:5px 11px; font-size:12px; }
.spinner { display:inline-block;width:12px;height:12px;border:2px solid rgba(255,255,255,.4);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }
</style>
@endpush

@section('ai-agent-content')

<div x-data="apiIntegration()" x-init="init()">

<div class="api-header">
    <div>
        <h1>API 연계 (T44)</h1>
        <p>Frontend API 호출과 Backend 엔드포인트의 매칭을 분석하고 통합 설정 파일을 생성합니다.</p>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;">
        @if($analysis)
        <a href="{{ $exportUrl }}" class="btn-secondary btn-sm">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            ZIP 다운로드
        </a>
        @endif
        <button class="btn-primary" :class="{ 'opacity-60 pointer-events-none': analyzing }" @click="runAnalyze()">
            <span x-show="!analyzing">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:-1px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                {{ $analysis ? '재분석' : '매칭 분석' }}
            </span>
            <span x-show="analyzing" class="spinner"></span>
            <span x-show="analyzing">분석 중...</span>
        </button>
    </div>
</div>

{{-- Pre-condition --}}
<div class="api-pre">
    <div class="api-cond {{ $feCount > 0 ? 'ok' : 'err' }}">
        {{ $feCount > 0 ? '✅' : '❌' }}
        Frontend 코드 {{ $feCount }}개 (T40)
        @if($feCount === 0)
            <a href="{{ route('ai-agent.projects.dev.frontend-code', $project) }}" style="color:inherit;font-size:11px;margin-left:4px;">→ 생성</a>
        @endif
    </div>
    <div class="api-cond {{ $beCount > 0 ? 'ok' : 'err' }}">
        {{ $beCount > 0 ? '✅' : '❌' }}
        Backend 코드 {{ $beCount }}개 (T43)
        @if($beCount === 0)
            <a href="{{ route('ai-agent.projects.dev.backend', $project) }}" style="color:inherit;font-size:11px;margin-left:4px;">→ 생성</a>
        @endif
    </div>
</div>

{{-- Error message --}}
<div x-show="errorMsg" x-cloak
     style="padding:12px 16px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;color:#b91c1c;margin-bottom:16px;"
     x-text="errorMsg"></div>

@if($analysis)
@php
    $meta = $analysis['$metadata'] ?? [];
    $rate = $meta['compliance_rate'] ?? 0;
    $rateClass = $rate >= 90 ? 'high' : ($rate >= 70 ? 'mid' : 'low');
@endphp

{{-- Stats --}}
<div class="api-stats">
    <div class="api-stat">
        <div class="api-stat-val" style="color:#7c3aed;">{{ $meta['frontend_calls'] ?? 0 }}</div>
        <div class="api-stat-lbl">FE 호출</div>
    </div>
    <div class="api-stat">
        <div class="api-stat-val" style="color:#0369a1;">{{ $meta['backend_endpoints'] ?? 0 }}</div>
        <div class="api-stat-lbl">BE 엔드포인트</div>
    </div>
    <div class="api-stat">
        <div class="api-stat-val" style="color:#16a34a;">{{ $meta['matched'] ?? 0 }}</div>
        <div class="api-stat-lbl">매칭됨</div>
    </div>
    <div class="api-stat">
        <div class="api-stat-val" style="color:#d97706;">{{ $meta['unmatched_frontend'] ?? 0 }}</div>
        <div class="api-stat-lbl">FE 미매칭</div>
    </div>
    <div class="api-stat">
        <div class="api-stat-val" style="color:#64748b;">{{ $meta['unmatched_backend'] ?? 0 }}</div>
        <div class="api-stat-lbl">BE 미사용</div>
    </div>
</div>

{{-- Match rate --}}
<div class="api-rate-wrap">
    <div class="api-rate-label">
        <span>매칭률</span>
        <span style="color:{{ $rateClass === 'high' ? '#16a34a' : ($rateClass === 'mid' ? '#d97706' : '#b91c1c') }};">{{ $rate }}%</span>
    </div>
    <div class="api-rate-bg">
        <div class="api-rate-bar {{ $rateClass }}" style="width:{{ min(100, $rate) }}%;"></div>
    </div>
    @if(isset($meta['analyzed_at']))
    <div style="font-size:11px;color:#94a3b8;margin-top:6px;">분석: {{ \Carbon\Carbon::parse($meta['analyzed_at'])->format('Y-m-d H:i') }}</div>
    @endif
</div>

{{-- Matched --}}
@if(!empty($analysis['matches']))
<div class="api-section">
    <div class="api-section-header">
        <span class="api-section-title">✅ 매칭됨</span>
        <span class="api-section-badge badge-green">{{ count($analysis['matches']) }}쌍</span>
    </div>
    @foreach($analysis['matches'] as $m)
    @php $fe = $m['frontend_call']; $be = $m['backend_endpoint']; @endphp
    <div class="api-match">
        <div class="api-match-top">
            <span class="api-method {{ $fe['method'] }}">{{ $fe['method'] }}</span>
            <span class="api-match-url">{{ $fe['url'] }}</span>
        </div>
        <div class="api-match-meta">
            @if($fe['screen_id'])FE: [{{ $fe['screen_id'] }}] @endif
            <code style="font-size:11px;">{{ $fe['file'] }}:{{ $fe['line'] }}</code>
        </div>
        <div class="api-match-ctrl">→ {{ $be['controller'] }}
            @if($be['resource'])<span style="color:#94a3b8;"> ({{ $be['resource'] }})</span>@endif
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Unmatched Frontend --}}
@if(!empty($analysis['unmatched_frontend']))
<div class="api-section">
    <div class="api-section-header">
        <span class="api-section-title">⚠️ 매칭 안 된 Frontend 호출</span>
        <span class="api-section-badge badge-yellow">{{ count($analysis['unmatched_frontend']) }}건</span>
    </div>
    @foreach($analysis['unmatched_frontend'] as $u)
    @php $fe = $u['frontend_call']; @endphp
    <div class="api-unmatched fe">
        <div class="api-match-top">
            <span class="api-method {{ $fe['method'] }}">{{ $fe['method'] }}</span>
            <span class="api-match-url">{{ $fe['url'] }}</span>
        </div>
        @if($fe['screen_id'])
        <div class="api-match-meta">FE: [{{ $fe['screen_id'] }}] <code style="font-size:11px;">{{ $fe['file'] }}:{{ $fe['line'] }}</code></div>
        @endif
        <div class="api-unmatched-issue">⚠️ {{ $u['issue'] }}</div>
        <div class="api-unmatched-suggest">💡 {{ $u['suggestion'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- Unmatched Backend --}}
@if(!empty($analysis['unmatched_backend']))
<div class="api-section">
    <div class="api-section-header">
        <span class="api-section-title">ℹ️ 매칭 안 된 Backend 엔드포인트</span>
        <span class="api-section-badge badge-blue">{{ count($analysis['unmatched_backend']) }}건</span>
    </div>
    @foreach($analysis['unmatched_backend'] as $u)
    @php $be = $u['backend_endpoint']; @endphp
    <div class="api-unmatched be">
        <div class="api-match-top">
            <span class="api-method {{ $be['method'] }}">{{ $be['method'] }}</span>
            <span class="api-match-url">{{ $be['uri'] }}</span>
            @if($be['resource'])
                <span style="font-size:11px;color:#94a3b8;">({{ $be['resource'] }})</span>
            @endif
        </div>
        <div class="api-match-ctrl">{{ $be['controller'] }}</div>
        <div class="api-unmatched-issue">ℹ️ {{ $u['issue'] }}</div>
        <div class="api-unmatched-suggest">💡 {{ $u['suggestion'] }}</div>
    </div>
    @endforeach
</div>
@endif

{{-- Integration files --}}
@if($files)
<div class="api-section">
    <div class="api-section-header">
        <span class="api-section-title">통합 설정 파일</span>
        <button class="btn-secondary btn-sm"
                :class="{ 'opacity-60 pointer-events-none': regenning }"
                @click="regenFiles()">
            <span x-show="!regenning">재생성</span>
            <span x-show="regenning" class="spinner" style="border-top-color:#7c3aed;border-color:#c4b5fd;"></span>
        </button>
    </div>
    <div class="api-files-list">
        @foreach($files as $path => $content)
        <div class="api-file-item">
            <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" style="flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="api-file-name">{{ $path }}</span>
            @php
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $label = match($ext) { 'ts' => 'TypeScript', 'js' => 'JavaScript', 'php' => 'PHP', default => strtoupper($ext) };
            @endphp
            <span class="api-file-badge">{{ $label }}</span>
            <span style="font-size:11px;color:#94a3b8;">{{ number_format(strlen($content)) }}자</span>
        </div>
        @endforeach
    </div>
    <div style="padding:0 16px 12px;">
        <a href="{{ $exportUrl }}" class="btn-secondary btn-sm">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            모든 파일 다운로드 (ZIP)
        </a>
    </div>
</div>
@endif

@else
{{-- No analysis yet --}}
<div style="text-align:center;padding:56px 24px;background:#fff;border:1.5px solid #ede8ff;border-radius:14px;">
    <div style="font-size:40px;margin-bottom:14px;">🔗</div>
    <div style="font-size:15px;font-weight:700;color:#1e1b2e;margin-bottom:6px;">API 연계 분석 미실행</div>
    <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">
        Frontend 코드와 Backend 코드가 준비되면 매칭 분석을 실행하세요.
    </div>
    <button class="btn-primary" :class="{ 'opacity-60 pointer-events-none': analyzing }" @click="runAnalyze()">
        <span x-show="!analyzing">🔍 매칭 분석 시작</span>
        <span x-show="analyzing" class="spinner"></span>
        <span x-show="analyzing">분석 중...</span>
    </button>
</div>
@endif

</div>{{-- /x-data --}}

@endsection

@push('scripts')
<script>
function apiIntegration() {
    return {
        analyzing: false,
        regenning: false,
        errorMsg: '',

        init() {},

        async runAnalyze() {
            this.analyzing = true;
            this.errorMsg  = '';
            try {
                const res  = await fetch('{{ $analyzeUrl }}', {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    this.errorMsg  = data.message || '분석 실패';
                    this.analyzing = false;
                }
            } catch (e) {
                this.errorMsg  = '오류: ' + e.message;
                this.analyzing = false;
            }
        },

        async regenFiles() {
            this.regenning = true;
            try {
                const res  = await fetch('{{ $regenUrl }}', {
                    method:  'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || '재생성 실패');
                }
            } catch (e) {
                alert('오류: ' + e.message);
            }
            this.regenning = false;
        },
    };
}
</script>
@endpush
