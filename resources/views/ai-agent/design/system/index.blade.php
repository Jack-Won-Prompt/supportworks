@extends('layouts.ai-agent')
@section('title', '디자인 시스템 문서 — 웍스 Agent')

@push('styles')
<style>
.dss-wrap     { max-width: 860px; }
.dss-card     { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:14px; }
.dss-card-ttl { font-size:12px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:12px; display:flex; align-items:center; gap:7px; }
.dss-card-ttl::after { content:''; flex:1; height:1px; background:#f1f5f9; }

.dss-check-row { display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid #f8fafc; }
.dss-check-row:last-child { border-bottom:none; }
.dss-check-icon { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:11px; font-weight:700; }
.dss-check-icon.ok       { background:#dcfce7; color:#16a34a; }
.dss-check-icon.optional { background:#fffbeb; color:#d97706; }
.dss-check-icon.missing  { background:#fef2f2; color:#dc2626; }
.dss-check-label { flex:1; font-size:13px; color:#374151; font-weight:500; }
.dss-check-meta  { font-size:11.5px; color:#94a3b8; }

.dss-btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; font-size:13px; font-weight:600; cursor:pointer; transition:all .15s; border:none; text-decoration:none; }
.dss-btn-primary { background:#7c3aed; color:#fff; }
.dss-btn-primary:hover { background:#6d28d9; color:#fff; }
.dss-btn-outline { background:#fff; color:#475569; border:1.5px solid #e2e8f0; }
.dss-btn-outline:hover { border-color:#a78bfa; color:#7c3aed; }
.dss-btn-ai { background:#f5f3ff; color:#7c3aed; border:1.5px solid #ddd6fe; }
.dss-btn-ai:hover { background:#ede9fe; }
.dss-btn:disabled { opacity:.5; cursor:not-allowed; }

.dss-artifact-box { background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:10px; padding:12px 16px; display:flex; align-items:center; gap:12px; }
.dss-artifact-box .version { font-size:20px; font-weight:800; font-family:monospace; color:#16a34a; }

.dss-export-dd { position:relative; display:inline-block; }
.dss-export-menu { display:none; position:absolute; right:0; top:calc(100% + 4px); background:#fff; border:1.5px solid #e2e8f0; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.08); z-index:50; min-width:160px; }
.dss-export-dd.open .dss-export-menu { display:block; }
.dss-export-menu a { display:flex; align-items:center; gap:8px; padding:9px 14px; font-size:13px; color:#374151; text-decoration:none; }
.dss-export-menu a:hover { background:#f8fafc; }

.dss-toast { position:fixed; bottom:24px; right:24px; z-index:9999; background:#1e1b2e; color:#fff; padding:10px 18px; border-radius:10px; font-size:13px; font-weight:500; opacity:0; transform:translateY(10px); transition:all .25s; pointer-events:none; }
.dss-toast.show { opacity:1; transform:translateY(0); }

.dss-preview-frame { width:100%; height:600px; border:none; border-radius:10px; background:#fff; }
</style>
@endpush

@section('page-actions')
<div x-data="dssActions(@json([
    'generateUrl' => route('ai-agent.projects.design.system.generate', $project),
    'enrichUrl'   => route('ai-agent.projects.design.system.enrich', $project),
    'previewUrl'  => route('ai-agent.projects.design.system.preview', $project),
    'exportMdUrl' => route('ai-agent.projects.design.system.export', $project) . '?format=md',
    'exportHtmlUrl'=> route('ai-agent.projects.design.system.export', $project) . '?format=html',
    'csrfToken'   => csrf_token(),
    'hasMissing'  => {{ count($missing) > 0 ? 'true' : 'false' }},
    'hasArtifact' => {{ $artifact ? 'true' : 'false' }},
]))" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">

    <div class="dss-toast" :class="{ show: toast.show }" x-text="toast.msg"></div>

    {{-- Generate button --}}
    <button class="dss-btn dss-btn-primary"
            @click="doGenerate()"
            :disabled="generating || hasMissing"
            x-text="generating ? '생성 중...' : (hasArtifact ? '재생성' : '📄 문서 생성')">
    </button>

    {{-- 웍스 Enrich --}}
    @if($artifact)
    <button class="dss-btn dss-btn-ai"
            @click="doEnrich()"
            :disabled="enriching">
        <span x-text="enriching ? '웍스 보강 중...' : '✨ 웍스 보강'"></span>
    </button>
    @endif

    {{-- Export dropdown --}}
    @if($artifact || count($missing) === 0)
    <div class="dss-export-dd" @click.outside="open = false" x-data="{ open: false }">
        <button class="dss-btn dss-btn-outline" @click="open = !open">
            내보내기 ▾
        </button>
        <div class="dss-export-menu">
            <a href="{{ route('ai-agent.projects.design.system.export', $project) }}?format=html" target="_blank">
                🌐 HTML 다운로드
            </a>
            <a href="{{ route('ai-agent.projects.design.system.export', $project) }}?format=md" target="_blank">
                📝 Markdown 다운로드
            </a>
        </div>
    </div>
    @endif

    {{-- Preview new tab --}}
    <a href="{{ route('ai-agent.projects.design.system.preview', $project) }}"
       target="_blank"
       class="dss-btn dss-btn-outline {{ count($missing) > 0 && !$artifact ? 'dss-btn:disabled' : '' }}">
       🔍 미리보기
    </a>

    <script>
    function dssActions(cfg) {
        return {
            cfg,
            generating: false,
            enriching:  false,
            hasMissing: cfg.hasMissing,
            hasArtifact: cfg.hasArtifact,
            toast: { show: false, msg: '' },
            showToast(msg) { this.toast = { show: true, msg }; setTimeout(() => this.toast.show = false, 3500); },
            async doGenerate() {
                this.generating = true;
                try {
                    const res = await axios.post(this.cfg.generateUrl, { _token: this.cfg.csrfToken });
                    if (res.data.success) {
                        this.showToast(res.data.message + ' v' + res.data.version);
                        this.hasArtifact = true;
                        setTimeout(() => window.location.reload(), 1500);
                    } else { this.showToast(res.data.message); }
                } catch (e) { this.showToast(e.response?.data?.message ?? '오류가 발생했습니다.'); }
                this.generating = false;
            },
            async doEnrich() {
                this.enriching = true;
                try {
                    const res = await axios.post(this.cfg.enrichUrl, { _token: this.cfg.csrfToken });
                    if (res.data.success) {
                        this.showToast(res.data.message);
                        setTimeout(() => window.location.reload(), 1500);
                    } else { this.showToast(res.data.message); }
                } catch (e) { this.showToast(e.response?.data?.message ?? '웍스 보강 실패'); }
                this.enriching = false;
            },
        };
    }
    </script>
</div>
@endsection

@section('ai-agent-content')
<div class="dss-wrap">

    {{-- Prerequisites --}}
    <div class="dss-card">
        <div class="dss-card-ttl">사전 조건</div>
        @foreach($dataStatus as $key => $status)
        @php
        $iconClass = $status['ready'] ? 'ok' : ($status['optional'] ? 'optional' : 'missing');
        $icon      = $status['ready'] ? '✓' : ($status['optional'] ? '!' : '✗');
        @endphp
        <div class="dss-check-row">
            <div class="dss-check-icon {{ $iconClass }}">{{ $icon }}</div>
            <div class="dss-check-label">{{ $status['label'] }}</div>
            <div class="dss-check-meta">
                @if($status['ready'])
                    @if(isset($status['total']))
                        {{ $status['count'] }} / {{ $status['total'] }}개 매핑
                    @elseif($key === 'review')
                        {{ $status['count'] }}점
                    @else
                        {{ $status['count'] }}개
                    @endif
                @elseif($status['optional'])
                    선택 — 없어도 생성 가능
                @else
                    필수 — 먼저 완료하세요
                @endif
            </div>
        </div>
        @endforeach

        @if(count($missing) > 0)
        <div style="margin-top:12px;padding:10px 14px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:8px;font-size:12.5px;color:#dc2626;">
            ⚠️ 필수 항목 누락: <strong>{{ implode(', ', $missing) }}</strong> — 완료 후 문서 생성 가능합니다.
        </div>
        @endif
    </div>

    {{-- Current Artifact --}}
    @if($artifact)
    @php
    $meta = $artifact->meta ?? [];
    $generatedAt = $meta['generated_at'] ?? null;
    @endphp
    <div class="dss-card">
        <div class="dss-card-ttl">현재 산출물</div>
        <div class="dss-artifact-box">
            <div class="version">v{{ $artifact->version }}</div>
            <div style="flex:1;">
                <div style="font-size:13px;font-weight:600;color:#1e1b2e;">{{ $artifact->title }}</div>
                <div style="font-size:11.5px;color:#64748b;margin-top:2px;">
                    @if($generatedAt) {{ \Carbon\Carbon::parse($generatedAt)->format('Y-m-d H:i') }} 생성 @endif
                    @if(isset($meta['token_count'])) · 토큰 {{ $meta['token_count'] }}개 @endif
                    @if(isset($meta['component_count'])) · 컴포넌트 {{ $meta['component_count'] }}개 @endif
                    @if(isset($meta['has_ai_sections']) && $meta['has_ai_sections']) · 웍스 보강 완료 @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Inline Preview --}}
    @if(count($missing) === 0)
    <div class="dss-card">
        <div class="dss-card-ttl">
            라이브 미리보기
            <a href="{{ route('ai-agent.projects.design.system.preview', $project) }}" target="_blank"
               style="font-size:11.5px;font-weight:600;color:#7c3aed;text-decoration:none;margin-left:8px;">새 탭으로 열기 ↗</a>
        </div>
        <iframe class="dss-preview-frame"
                src="{{ route('ai-agent.projects.design.system.preview', $project) }}"
                title="디자인 시스템 미리보기">
        </iframe>
    </div>
    @else
    <div class="dss-card" style="text-align:center;padding:40px;color:#94a3b8;">
        <div style="font-size:14px;font-weight:600;margin-bottom:6px;">미리보기 불가</div>
        <div style="font-size:12.5px;">필수 데이터 ({{ implode(', ', $missing) }})를 먼저 완료하세요.</div>
    </div>
    @endif

</div>
@endsection
