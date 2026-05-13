@extends('layouts.ai-agent')
@section('title', '프로젝트 종합 보고서 — 웍스 Agent')

@push('styles')
<style>
.rpt-header { margin-bottom:28px; }
.rpt-header h1 { font-size:24px; font-weight:900; color:#1e1b2e; margin:0 0 4px; }
.rpt-header p  { font-size:14px; color:#64748b; margin:0; }

.rpt-grid { display:grid; grid-template-columns:1fr 300px; gap:20px; align-items:start; }
@media(max-width:960px) { .rpt-grid { grid-template-columns:1fr; } }

.rpt-card { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 24px; margin-bottom:16px; }
.rpt-card-title { font-size:15px; font-weight:800; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:8px; }
.rpt-card-title span { font-size:11px; font-weight:700; background:#ede8ff; color:#7c3aed; padding:2px 8px; border-radius:99px; }

.rpt-stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(130px,1fr)); gap:12px; margin-bottom:0; }
.rpt-stat { background:#f8fafc; border-radius:10px; padding:14px 16px; }
.rpt-stat-val { font-size:22px; font-weight:900; color:#1e1b2e; display:block; margin-bottom:3px; }
.rpt-stat-key { font-size:11.5px; color:#64748b; }

.rpt-phase { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:1px solid #f1f5f9; }
.rpt-phase:last-child { border-bottom:none; }
.rpt-phase-num  { min-width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:800; flex-shrink:0; }
.rpt-phase-num.done { background:#dcfce7; color:#15803d; }
.rpt-phase-num.prog { background:#ede8ff; color:#7c3aed; }
.rpt-phase-title { font-size:13.5px; font-weight:700; color:#1e1b2e; margin-bottom:3px; }
.rpt-phase-items { font-size:12px; color:#64748b; line-height:1.6; }

.rpt-artifact { display:flex; align-items:center; justify-content:space-between; padding:8px 0; border-bottom:1px solid #f8fafc; font-size:13px; }
.rpt-artifact:last-child { border-bottom:none; }
.rpt-artifact-name  { color:#1e1b2e; font-weight:600; display:flex; align-items:center; gap:6px; }
.rpt-artifact-badge { font-size:10px; font-weight:700; background:#ede8ff; color:#7c3aed; padding:1px 7px; border-radius:99px; }

.rpt-approval-row { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid #f1f5f9; font-size:13px; }
.rpt-approval-row:last-child { border-bottom:none; }

.rpt-back-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:#f1f5f9; color:#475569; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; margin-bottom:20px; }
.rpt-back-btn:hover { background:#e2e8f0; }
</style>
@endpush

@section('ai-agent-content')

<a href="{{ route('ai-agent.projects.release.approval.index', $project) }}" class="rpt-back-btn">
    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
    </svg>
    승인 게이트로 돌아가기
</a>

<div class="rpt-header">
    <h1>📋 {{ $project->name }} — 프로젝트 종합 보고서</h1>
    <p>웍스 Agent 전체 5개 Phase 완료 요약 · 생성일: {{ now()->format('Y-m-d H:i') }}</p>
</div>

<div class="rpt-grid">

    {{-- ─── 좌: 본문 ───────────────────────────────────────────────── --}}
    <div>

        {{-- 종합 통계 --}}
        <div class="rpt-card">
            <div class="rpt-card-title">📊 종합 통계</div>
            <div class="rpt-stats-grid">
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ $projectStats['completed_phases'] }}/5</span>
                    <span class="rpt-stat-key">Phase 완료</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ $projectStats['total_artifacts'] }}</span>
                    <span class="rpt-stat-key">전체 산출물</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ $projectStats['total_screens'] }}</span>
                    <span class="rpt-stat-key">화면 수</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ $projectStats['total_requirements'] }}</span>
                    <span class="rpt-stat-key">요구사항</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ number_format($projectStats['total_ai_calls']) }}</span>
                    <span class="rpt-stat-key">웍스 호출 횟수</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ number_format($projectStats['total_tokens']) }}</span>
                    <span class="rpt-stat-key">총 토큰</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">${{ $projectStats['total_cost_usd'] }}</span>
                    <span class="rpt-stat-key">웍스 비용</span>
                </div>
                <div class="rpt-stat">
                    <span class="rpt-stat-val">{{ $projectStats['duration_days'] }}일</span>
                    <span class="rpt-stat-key">소요 기간</span>
                </div>
            </div>
        </div>

        {{-- Phase별 작업 내역 --}}
        <div class="rpt-card">
            <div class="rpt-card-title">🗂️ Phase별 산출물</div>
            @foreach([
                ['num' => '1', 'label' => 'Phase 1: 기반 인프라', 'items' => ['데이터 모델 (DB 마이그레이션)', 'Eloquent 모델', '추적성 서비스', '버전 관리 서비스', '승인 게이트 서비스', '웍스 Provider 어댑터', '웍스 사용량 로그', '프롬프트 라이브러리', '기술 스택 시드 데이터', '웍스 Agent 메뉴 라우팅', '공통 레이아웃', '승인 게이트 UI', '프로젝트 대시보드']],
                ['num' => '2', 'label' => 'Phase 2: 기획', 'items' => ['AS-IS 분석', 'TO-BE 요구사항', 'Gap 분석', '웍스 기획서', 'IA / 화면 흐름도', '화면 생성 프롬프트', '웍스 목업']],
                ['num' => '3', 'label' => 'Phase 3: 디자인', 'items' => ['Figma API 연동', 'Design Token 추출', 'Component 명세', 'Layout / Grid 정의', '화면 매핑 (Figma → SCR-XXX)', '디자인 일관성 검증', '디자인 시스템 문서', 'Figma Dev Mode URL']],
                ['num' => '4', 'label' => 'Phase 4: 개발', 'items' => ['ERD 자동 생성', 'API 명세 (OpenAPI 3.0)', 'RBAC 권한 모델', '코드 생성 프롬프트', 'Frontend 코드 생성', 'Output 검증', 'Backend 코드 생성', 'API 연계', '웍스 코드 리뷰', '웍스 추가 수정']],
                ['num' => '5', 'label' => 'Phase 5: 릴리즈', 'items' => ['통합 릴리즈 패키지 (T48)', '배포 가이드 (T49)', '사용자 매뉴얼 (T50)', '마이그레이션 가이드 (T51)', '최종 승인 게이트 (T52)']],
            ] as $phase)
            <div class="rpt-phase">
                <div class="rpt-phase-num done">{{ $phase['num'] }}</div>
                <div>
                    <div class="rpt-phase-title">{{ $phase['label'] }}</div>
                    <div class="rpt-phase-items">{{ implode(' · ', $phase['items']) }}</div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Phase 5 산출물 상세 --}}
        <div class="rpt-card">
            <div class="rpt-card-title">📦 Phase 5 산출물 현황 <span>릴리즈</span></div>
            @foreach(array_merge($diagnosis['blocking'], $diagnosis['warnings']) as $item)
            <div class="rpt-artifact">
                <div class="rpt-artifact-name">
                    {{ $item['complete'] ? '✅' : ($item['level'] ?? 'warning') === 'blocking' ? '❌' : '⚠️' }}
                    {{ $item['label'] }}
                    <span class="rpt-artifact-badge">{{ $item['source_task'] }}</span>
                </div>
                <span style="font-size:12px;color:{{ $item['complete'] ? '#15803d' : '#b45309' }};">
                    {{ $item['complete'] ? '생성됨 — ' . ($item['generated_at'] ?? '') : '미생성' }}
                </span>
            </div>
            @endforeach
        </div>

    </div>

    {{-- ─── 우: 사이드바 ───────────────────────────────────────────── --}}
    <div>

        {{-- 승인 이력 --}}
        <div class="rpt-card">
            <div class="rpt-card-title">✅ 승인 이력</div>
            @if(!empty($projectStats['approvals']))
                @foreach($projectStats['approvals'] as $approval)
                <div class="rpt-approval-row">
                    <div>
                        <div style="font-weight:700;font-size:13px;color:#1e1b2e;">{{ $approval['stage_label'] }}</div>
                        <div style="font-size:11.5px;color:#94a3b8;">{{ $approval['approver'] }}</div>
                    </div>
                    <span style="font-size:12px;color:#64748b;">{{ $approval['approved_at'] }}</span>
                </div>
                @endforeach
            @else
                <p style="font-size:13px;color:#94a3b8;margin:0;">승인 이력이 없습니다.</p>
            @endif
        </div>

        {{-- 프로젝트 정보 --}}
        <div class="rpt-card">
            <div class="rpt-card-title">📌 프로젝트 정보</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;">프로젝트명</span>
                    <span style="font-weight:700;">{{ $project->name }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;">시작일</span>
                    <span>{{ $projectStats['started_at']->format('Y.m.d') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;">완료일</span>
                    <span>{{ now()->format('Y.m.d') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;">소요 기간</span>
                    <span>{{ $projectStats['duration_days'] }}일</span>
                </div>
            </div>
        </div>

        {{-- 빠른 이동 --}}
        <div class="rpt-card" style="background:#fafaff;">
            <div class="rpt-card-title">🔗 빠른 이동</div>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <a href="{{ route('ai-agent.projects.release.package.download', $project) }}" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:#7c3aed;color:#fff;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:700;">
                    📦 최종 패키지 다운로드
                </a>
                <a href="{{ route('ai-agent.projects.release.package.index', $project) }}" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:#f1f5f9;color:#1e1b2e;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    T48 통합 패키지
                </a>
                <a href="{{ route('ai-agent.projects.release.deploy-guide.index', $project) }}" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:#f1f5f9;color:#1e1b2e;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    T49 배포 가이드
                </a>
                <a href="{{ route('ai-agent.projects.release.user-manual.index', $project) }}" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:#f1f5f9;color:#1e1b2e;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    T50 사용자 매뉴얼
                </a>
                <a href="{{ route('ai-agent.projects.release.migration-guide.index', $project) }}" style="display:flex;align-items:center;gap:7px;padding:7px 10px;background:#f1f5f9;color:#1e1b2e;border-radius:8px;text-decoration:none;font-size:12.5px;font-weight:600;">
                    T51 마이그레이션 가이드
                </a>
            </div>
        </div>

    </div>

</div>

@endsection
