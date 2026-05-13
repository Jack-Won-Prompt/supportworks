@extends('layouts.ai-agent')
@section('title', '웍스 진행 표시 데모 — 웍스 Agent')

@push('styles')
<style>
.demo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(460px, 1fr)); gap: 20px; margin-top: 28px; }
.demo-card { background: #fff; border: 1.5px solid #ede8ff; border-radius: 16px; padding: 20px; }
.demo-card-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .07em; margin-bottom: 6px; }
.demo-card-title { font-size: 14px; font-weight: 800; color: #1e1b2e; margin: 0 0 4px; }
.demo-card-desc { font-size: 12.5px; color: #64748b; line-height: 1.6; margin: 0 0 14px; }
.demo-btn-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
.demo-trigger-btn { display: inline-flex; align-items: center; gap: 5px; padding: 7px 14px; border-radius: 8px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: none; background: var(--t600); color: #fff; transition: all .15s; }
.demo-trigger-btn:hover { background: var(--t700); }
.demo-trigger-btn.secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.demo-trigger-btn.secondary:hover { background: #e2e8f0; }
.demo-trigger-btn.danger { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
.demo-trigger-btn.danger:hover { background: #fecaca; }
.demo-usage-note { background: #faf5ff; border: 1.5px solid var(--t100); border-radius: 12px; padding: 16px 20px; margin-top: 32px; }
.demo-usage-note h3 { font-size: 13px; font-weight: 700; color: var(--t700); margin: 0 0 12px; }
.demo-usage-note pre { font-size: 11.5px; color: #374151; line-height: 1.7; overflow-x: auto; margin: 0; white-space: pre-wrap; }
</style>
@endpush

@section('ai-agent-content')

<div style="max-width:980px;">
    <h1 style="font-size:22px;font-weight:800;color:#1e1b2e;margin:0 0 6px;">웍스 진행 표시 컴포넌트 데모</h1>
    <p style="font-size:13.5px;color:#64748b;margin:0 0 4px;line-height:1.7;">
        <code>&lt;x-ai-agent.ai-progress&gt;</code> 컴포넌트의 4가지 시나리오를 테스트합니다.
    </p>
    <p style="font-size:12px;color:#94a3b8;margin:0;">
        실제 웍스 API를 호출하지 않고 서버 측에서 스트리밍을 시뮬레이션합니다.
    </p>

    <div class="demo-grid">

        {{-- ── 시나리오 1: 짧은 응답 ── --}}
        <div class="demo-card" x-data="{ comp: null }">
            <div class="demo-card-label">시나리오 1</div>
            <h3 class="demo-card-title">짧은 응답 스트리밍</h3>
            <p class="demo-card-desc">
                약 2초간 텍스트를 청크 단위로 수신합니다.
                STARTING → STREAMING → COMPLETED 상태 전환을 확인합니다.
            </p>
            <div class="demo-btn-row">
                <button class="demo-trigger-btn" @click="$refs.prog1.startDemo('short')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    시작
                </button>
                <button class="demo-trigger-btn secondary" @click="$refs.prog1.reset()">초기화</button>
            </div>
            <x-ai-agent.ai-progress
                mode="demo"
                :demo-sse-url-tpl="$demoSseBaseUrl"
                :cancel-url-tpl="$cancelUrlTpl"
                label="짧은 응답 생성"
                x-ref="prog1"
            />
        </div>

        {{-- ── 시나리오 2: 긴 응답 + 취소 ── --}}
        <div class="demo-card">
            <div class="demo-card-label">시나리오 2</div>
            <h3 class="demo-card-title">긴 응답 + 취소 테스트</h3>
            <p class="demo-card-desc">
                약 9초간 AS-IS 분석 보고서가 생성됩니다.
                중간에 <strong>[취소]</strong> 버튼을 눌러 CANCELLED 상태를 확인합니다.
            </p>
            <div class="demo-btn-row">
                <button class="demo-trigger-btn" @click="$refs.prog2.startDemo('long')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    시작 (긴 응답 ~9초)
                </button>
                <button class="demo-trigger-btn secondary" @click="$refs.prog2.reset()">초기화</button>
            </div>
            <x-ai-agent.ai-progress
                mode="demo"
                :demo-sse-url-tpl="$demoSseBaseUrl"
                :cancel-url-tpl="$cancelUrlTpl"
                label="AS-IS 분석 보고서 작성"
                allow-cancel
                x-ref="prog2"
            />
        </div>

        {{-- ── 시나리오 3: 에러 처리 ── --}}
        <div class="demo-card">
            <div class="demo-card-label">시나리오 3</div>
            <h3 class="demo-card-title">에러 처리</h3>
            <p class="demo-card-desc">
                약 2.5초 후 API 오류가 발생합니다.
                ERROR 상태와 재시도 버튼을 확인합니다.
            </p>
            <div class="demo-btn-row">
                <button class="demo-trigger-btn danger" @click="$refs.prog3.startDemo('error')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    에러 발생 시뮬레이션
                </button>
                <button class="demo-trigger-btn secondary" @click="$refs.prog3.reset()">초기화</button>
            </div>
            <x-ai-agent.ai-progress
                mode="demo"
                :demo-sse-url-tpl="$demoSseBaseUrl"
                :cancel-url-tpl="$cancelUrlTpl"
                label="에러 시나리오"
                x-ref="prog3"
            />
        </div>

        {{-- ── 시나리오 4: Queue 작업 (진행률 바) ── --}}
        <div class="demo-card">
            <div class="demo-card-label">시나리오 4</div>
            <h3 class="demo-card-title">Queue 작업 (진행률 바)</h3>
            <p class="demo-card-desc">
                멀티 파일 분석처럼 단계별 진행률이 표시되는 긴 작업입니다.
                약 5초간 5단계 진행률 바가 갱신됩니다.
            </p>
            <div class="demo-btn-row">
                <button class="demo-trigger-btn" @click="$refs.prog4.startDemo('job')">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/></svg>
                    Queue 작업 시작 (~5초)
                </button>
                <button class="demo-trigger-btn secondary" @click="$refs.prog4.reset()">초기화</button>
            </div>
            <x-ai-agent.ai-progress
                mode="demo"
                :demo-sse-url-tpl="$demoSseBaseUrl"
                :cancel-url-tpl="$cancelUrlTpl"
                label="요구사항 분석 (Job 모드)"
                allow-cancel
                x-ref="prog4"
            />
        </div>

    </div>{{-- /.demo-grid --}}

    {{-- 사용 예시 --}}
    <div class="demo-usage-note">
        <h3>T17+ 작업에서 활용하는 방법</h3>
        <pre>{{-- 1. 스트리밍 모드 (실시간 청크 수신) --}}
&lt;x-ai-agent.ai-progress
    mode="streaming"
    :start-url="route('ai-agent.projects.stream.start', $project)"
    :sse-url-tpl="str_replace('SESSION_ID', 'SESSION_ID', route('ai-agent.projects.stream.sse', [$project, 'SESSION_ID']))"
    :cancel-url-tpl="route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID'])"
    label="AS-IS 분석"
    on-complete="onAnalysisComplete"
    x-ref="progressBar"
/&gt;

{{-- 2. JS에서 시작 --}}
&lt;script&gt;
    // Alpine 컴포넌트 바깥에서 시작:
    document.querySelector('[x-ref=progressBar]').__x.$data.start(
        '다음 파일을 분석해 주세요: ...',
        { stage: 'planning', task_type: 'as_is_analysis' }
    );

    // 완료 콜백:
    function onAnalysisComplete(data, component) {
        console.log('완료! 토큰:', data.tokensIn + data.tokensOut, '비용:', data.costUsd);
    }
&lt;/script&gt;

{{-- 3. Cache 키 규칙 (취소 메커니즘용) --}}
// 캐시 키: "ai-agent:stream:{sessionId}"
// Cache::get('ai-agent:stream:' . $sessionId)['cancel'] === true 이면 취소</pre>
    </div>

</div>

@endsection
