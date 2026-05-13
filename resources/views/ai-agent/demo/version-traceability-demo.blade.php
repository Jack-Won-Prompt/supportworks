@extends('layouts.ai-agent')
@section('title', '버전 이력 · 추적성 데모 — 웍스 Agent')

@push('styles')
<style>
.vt-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(460px, 1fr)); gap:20px; margin-top:24px; }
.vt-card { background:#fff; border:1.5px solid #e2e8f0; border-radius:16px; padding:22px; }
.vt-card-tag { font-size:10px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.07em; margin-bottom:6px; }
.vt-card-title { font-size:14px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.vt-card-desc { font-size:12.5px; color:#64748b; line-height:1.6; margin:0 0 16px; }
.vt-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.vt-artifact-mock { display:inline-flex; align-items:center; gap:8px; background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:10px 14px; font-size:13px; color:#374151; font-weight:500; }
.vt-artifact-mock svg { flex-shrink:0; }
.vt-note { background:#f8fafc; border:1.5px solid #e2e8f0; border-radius:10px; padding:14px 16px; font-size:12px; color:#64748b; line-height:1.7; }
.vt-note code { background:#f1f5f9; padding:1px 5px; border-radius:4px; font-size:11.5px; color:#374151; }

.vt-usage { background:#f0fdf4; border:1.5px solid #86efac; border-radius:12px; padding:18px 22px; margin-top:32px; }
.vt-usage h3 { font-size:13px; font-weight:700; color:#166534; margin:0 0 12px; }
.vt-usage pre { font-size:11.5px; color:#374151; line-height:1.7; overflow-x:auto; margin:0; white-space:pre-wrap; }
</style>
@endpush

@section('ai-agent-content')

<div style="max-width:1020px;">
    <h1 style="font-size:22px;font-weight:800;color:#1e1b2e;margin:0 0 6px;">버전 이력 · 추적성 뷰어 데모</h1>
    <p style="font-size:13.5px;color:#64748b;margin:0 0 4px;line-height:1.7;">
        <code>&lt;x-ai-agent.version-history&gt;</code>와 <code>&lt;x-ai-agent.traceability-viewer&gt;</code> 컴포넌트를 테스트합니다.
    </p>
    <p style="font-size:12px;color:#94a3b8;margin:0;">
        실제 DB 없이 목업 데이터로 동작합니다. 우측에서 패널이 슬라이드됩니다.
    </p>

    <div class="vt-grid">

        {{-- ── 버전 이력 뷰어 데모 ── --}}
        <div class="vt-card" style="border-color:#ede9fe;">
            <div class="vt-card-tag" style="color:var(--t500,#8b5cf6);">Part A — Version History</div>
            <h3 class="vt-card-title">버전 이력 뷰어</h3>
            <p class="vt-card-desc">
                산출물의 버전 타임라인을 확인하고 임의 두 버전을 줄 단위 diff로 비교합니다.
                현재 v5 기준으로 이전 버전과 변경 내역을 비교해보세요.
            </p>

            {{-- 가상 산출물 카드 --}}
            <div class="vt-row">
                <div class="vt-artifact-mock">
                    <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    AS-IS 업무 분석 보고서
                </div>

                {{-- Version History Trigger --}}
                <x-ai-agent.version-history
                    :artifact-id="1"
                    artifact-title="AS-IS 업무 분석 보고서"
                    :current-version="5"
                    :history-url="route('ai-agent.demo.artifact.versions', 1)"
                    :version-url-tpl="str_replace('VERSION', '{version}', route('ai-agent.demo.artifact.version', [1, 'VERSION']))"
                    allow-restore
                />
            </div>

            <div class="vt-note">
                <strong>테스트 방법:</strong><br>
                1. <strong>v5</strong> 시계 버튼 클릭 → 우측 패널이 열립니다<br>
                2. v4~v1 항목의 <strong>[비교]</strong> 버튼 → 줄 단위 diff 확인<br>
                3. <strong>[이 버전으로 복구]</strong> → 복구 POST 호출 (데모라 실제 저장 안 됨)
            </div>
        </div>

        {{-- ── 추적성 뷰어 데모 ── --}}
        <div class="vt-card" style="border-color:#bae6fd;">
            <div class="vt-card-tag" style="color:#0369a1;">Part B — Traceability</div>
            <h3 class="vt-card-title">추적성 뷰어</h3>
            <p class="vt-card-desc">
                요구사항 REQ-001이 어떤 화면·API·코드에 연결되어 있는지 확인하고,
                변경 시 영향받는 모든 항목을 BFS로 분석합니다.
            </p>

            <div class="vt-row">
                <div class="vt-artifact-mock" style="border-color:#bae6fd;background:#f0f9ff;">
                    <svg width="15" height="15" fill="none" stroke="#0369a1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    REQ-001 · 작업 요청 관리
                </div>

                {{-- Traceability Viewer Trigger --}}
                <x-ai-agent.traceability-viewer
                    source-type="requirement"
                    :source-id="1"
                    source-ref="REQ-001"
                    :links-url="route('ai-agent.demo.traceability.links', ['requirement', 1])"
                    :impact-url="route('ai-agent.demo.traceability.impact', ['requirement', 1])"
                />
            </div>

            <div class="vt-note">
                <strong>테스트 방법:</strong><br>
                1. <strong>[추적성]</strong> 버튼 클릭 → 우측 패널이 열립니다<br>
                2. <strong>링크 탭:</strong> 참조하는 화면·API, 역참조 산출물·코드<br>
                3. <strong>영향 분석 탭:</strong> BFS depth별 영향 항목 트리 확인
            </div>
        </div>

        {{-- ── 다른 타입 추적성 ── --}}
        <div class="vt-card" style="border-color:#bae6fd;">
            <div class="vt-card-tag" style="color:#0369a1;">Part B-2 — Artifact Traceability</div>
            <h3 class="vt-card-title">산출물 추적성</h3>
            <p class="vt-card-desc">
                산출물(Artifact) 타입의 추적성을 테스트합니다.
                artifact 타입으로 동일한 뷰어가 동작합니다.
            </p>

            <div class="vt-row">
                <div class="vt-artifact-mock" style="border-color:#fce7f3;background:#fdf2f8;">
                    <svg width="15" height="15" fill="none" stroke="#9d174d" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    ART-001 · 기능 명세서
                </div>

                <x-ai-agent.traceability-viewer
                    source-type="artifact"
                    :source-id="501"
                    source-ref="ART-001"
                    :links-url="route('ai-agent.demo.traceability.links', ['artifact', 501])"
                    :impact-url="route('ai-agent.demo.traceability.impact', ['artifact', 501])"
                />
            </div>

            <div class="vt-note">
                같은 컴포넌트를 <code>source-type="artifact"</code>로 사용한 예시입니다.
                링크·영향 목업 데이터는 타입/ID와 무관하게 동일하게 반환됩니다.
            </div>
        </div>

        {{-- ── 빈 상태 ── --}}
        <div class="vt-card">
            <div class="vt-card-tag">빈 상태 테스트</div>
            <h3 class="vt-card-title">링크 없는 버전 이력</h3>
            <p class="vt-card-desc">
                버전 1인 신규 산출물 — 비교 버튼 없이 "현재" 뱃지만 표시됩니다.
                히스토리 API가 버전 1만 반환하는 케이스입니다.
            </p>

            <div class="vt-row">
                <div class="vt-artifact-mock">
                    <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    TO-BE 목표 정의서 (신규)
                </div>

                <x-ai-agent.version-history
                    :artifact-id="2"
                    artifact-title="TO-BE 목표 정의서"
                    :current-version="1"
                    :history-url="route('ai-agent.demo.artifact.versions', 2)"
                    :version-url-tpl="str_replace('VERSION', '{version}', route('ai-agent.demo.artifact.version', [2, 'VERSION']))"
                />
            </div>

            <div class="vt-note">
                버전 이력이 1개뿐이면 비교 버튼이 표시되지 않고 "현재" 뱃지만 나옵니다.
                <code>allow-restore</code> 미전달 시 복구 버튼도 숨겨집니다.
            </div>
        </div>

    </div>

    {{-- 사용 예시 --}}
    <div class="vt-usage">
        <h3>T17+ 작업에서 활용하는 방법</h3>
        <pre>{{-- version-history 컴포넌트 --}}
&lt;x-ai-agent.version-history
    :artifact-id="$artifact->id"
    :artifact-title="$artifact->title"
    :current-version="$artifact->version"
    :history-url="route('ai-agent.projects.artifact.versions', [$project, $artifact])"
    :version-url-tpl="str_replace('VERSION', '{version}', route('ai-agent.projects.artifact.version', [$project, $artifact, 'VERSION']))"
    :restore-url-tpl="str_replace('VERSION', '{version}', route('ai-agent.projects.artifact.restore', [$project, $artifact, 'VERSION']))"
    allow-restore
/&gt;

{{-- traceability-viewer 컴포넌트 --}}
&lt;x-ai-agent.traceability-viewer
    source-type="requirement"
    :source-id="$requirement->id"
    :source-ref="$requirement->req_id"
    :links-url="route('ai-agent.projects.traceability.links', [$project, 'requirement', $requirement->id])"
    :impact-url="route('ai-agent.projects.traceability.impact', [$project, 'requirement', $requirement->id])"
/&gt;

{{-- 복구 이벤트 수신 --}}
&lt;script&gt;
    window.addEventListener('version-restored', (e) => {
        console.log('복구 완료:', e.detail.new_version);
        // 페이지 새로고침 또는 내용 갱신
    });
&lt;/script&gt;</pre>
    </div>

</div>

@endsection
