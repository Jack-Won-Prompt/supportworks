@extends('layouts.ai-agent')
@section('title', '릴리즈 단계 승인 — 웍스 Agent')

@php
use App\Enums\Agent\StageStatus;
$isApproved = $stage->status === StageStatus::APPROVED;
$isPending  = $stage->status === StageStatus::PENDING_APPROVAL;
@endphp

@push('styles')
<style>
.apv-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.apv-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.apv-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

.apv-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
@media(max-width:900px) { .apv-grid { grid-template-columns:1fr; } }

.apv-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.apv-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:8px; }

.apv-progress-bg  { background:#ede8ff; border-radius:99px; height:10px; overflow:hidden; margin-bottom:18px; }
.apv-progress-bar { height:10px; border-radius:99px; transition:width .4s ease; background:linear-gradient(90deg,#7c3aed,#a78bfa); }
.apv-progress-bar.complete { background:linear-gradient(90deg,#16a34a,#4ade80); }

.apv-list { display:flex; flex-direction:column; gap:8px; }
.apv-item { display:flex; align-items:flex-start; gap:10px; padding:11px 14px; border-radius:10px; border:1.5px solid; }
.apv-item.done    { background:#f0fdf4; border-color:#bbf7d0; }
.apv-item.missing { background:#fef2f2; border-color:#fca5a5; }
.apv-item.warn-ok { background:#fff; border-color:#e2e8f0; }
.apv-item.warn-no { background:#fffbeb; border-color:#fde68a; }

.apv-item-icon  { font-size:16px; flex-shrink:0; margin-top:1px; }
.apv-item-body  { flex:1; min-width:0; }
.apv-item-label { font-size:13px; font-weight:700; color:#1e1b2e; margin-bottom:2px; }
.apv-item-note  { font-size:11.5px; color:#64748b; margin-top:2px; }
.apv-item-link  { font-size:11.5px; color:#7c3aed; text-decoration:none; font-weight:600; }
.apv-item-link:hover { text-decoration:underline; }

.apv-pill     { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:2px 9px; border-radius:99px; margin-left:6px; }
.apv-pill.ok  { background:#dcfce7; color:#15803d; }
.apv-pill.low { background:#fef3c7; color:#92400e; }
.apv-task-tag { display:inline-flex; align-items:center; font-size:10px; font-weight:700; padding:1px 7px; border-radius:99px; background:#ede8ff; color:#7c3aed; margin-left:6px; }

.apv-divider { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin:14px 0 8px; padding:0 2px; }

.apv-approved-banner { display:flex; align-items:center; gap:10px; padding:14px 18px; background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; margin-bottom:18px; }
.apv-approved-banner-text { font-size:13.5px; font-weight:700; color:#15803d; }
.apv-approved-banner-sub  { font-size:12px; color:#166534; margin-top:2px; }

.apv-refresh-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#f8fafc; color:#475569; transition:all .15s; }
.apv-refresh-btn:hover { background:#e2e8f0; }
.apv-refresh-btn.spinning svg { animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* 🎊 완료 축하 배너 */
.apv-celebration-banner { background:linear-gradient(135deg,#4c1d95 0%,#7c3aed 50%,#a78bfa 100%); border-radius:18px; padding:28px 28px 24px; margin-bottom:24px; color:#fff; position:relative; overflow:hidden; }
.apv-celebration-banner::before { content:'🎊'; position:absolute; font-size:80px; right:-10px; top:-10px; opacity:.15; }
.apv-celebration-banner::after  { content:'🎊'; position:absolute; font-size:60px; left:-5px; bottom:-10px; opacity:.10; }
.apv-celeb-title { font-size:22px; font-weight:900; margin:0 0 6px; }
.apv-celeb-sub   { font-size:14px; opacity:.85; margin:0 0 20px; line-height:1.6; }
.apv-celeb-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:12px; margin-top:16px; }
.apv-celeb-stat  { background:rgba(255,255,255,.15); border-radius:10px; padding:10px 14px; text-align:center; }
.apv-celeb-stat-val  { font-size:20px; font-weight:800; display:block; }
.apv-celeb-stat-key  { font-size:11px; opacity:.75; margin-top:2px; display:block; }
.apv-celeb-actions   { display:flex; gap:10px; flex-wrap:wrap; margin-top:18px; }
.apv-celeb-btn       { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:9px; font-size:13px; font-weight:700; text-decoration:none; transition:transform .1s; }
.apv-celeb-btn:hover { transform:translateY(-1px); }
.apv-celeb-btn.primary   { background:#fff; color:#7c3aed; }
.apv-celeb-btn.secondary { background:rgba(255,255,255,.2); color:#fff; border:1.5px solid rgba(255,255,255,.4); }

/* approval history table */
.apv-history-row { display:flex; justify-content:space-between; align-items:center; padding:7px 0; border-bottom:1px solid #f1f5f9; font-size:12.5px; }
.apv-history-row:last-child { border-bottom:none; }
</style>
@endpush

@section('ai-agent-content')

<div class="apv-header">
    <div class="apv-header-left">
        <h1>릴리즈 단계 승인 게이트</h1>
        <p>통합 패키지, 배포 가이드, 사용자 매뉴얼, 마이그레이션 가이드를 검증하여 Phase 5(릴리즈)를 완료합니다.</p>
    </div>
    <button class="apv-refresh-btn" id="refreshBtn" onclick="refreshDiagnosis(this)">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        진단 새로고침
    </button>
</div>

{{-- 🎊 완료 축하 배너 (승인 후) --}}
@if($isApproved)
<div class="apv-celebration-banner">
    <div class="apv-celeb-title">🎊 프로젝트 100% 완성! 축하합니다!</div>
    <div class="apv-celeb-sub">
        {{ $project->name }} 프로젝트가 웍스 Agent와 함께 5개 Phase를 모두 완료했습니다.<br>
        기획 → 디자인 → 개발 준비 → 개발 → 릴리즈까지 전 과정이 승인되었습니다.
    </div>
    <div class="apv-celeb-stats">
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">{{ $projectStats['completed_phases'] }}/5</span>
            <span class="apv-celeb-stat-key">Phase 완료</span>
        </div>
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">{{ $projectStats['total_artifacts'] }}</span>
            <span class="apv-celeb-stat-key">산출물</span>
        </div>
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">{{ $projectStats['total_screens'] }}</span>
            <span class="apv-celeb-stat-key">화면</span>
        </div>
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">{{ $projectStats['total_ai_calls'] }}</span>
            <span class="apv-celeb-stat-key">웍스 호출</span>
        </div>
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">${{ $projectStats['total_cost_usd'] }}</span>
            <span class="apv-celeb-stat-key">웍스 비용</span>
        </div>
        <div class="apv-celeb-stat">
            <span class="apv-celeb-stat-val">{{ $projectStats['duration_days'] }}일</span>
            <span class="apv-celeb-stat-key">소요 기간</span>
        </div>
    </div>
    <div class="apv-celeb-actions">
        <a href="{{ route('ai-agent.projects.release.package.download', $project) }}" class="apv-celeb-btn primary">
            📦 최종 패키지 다운로드
        </a>
        <a href="{{ route('ai-agent.projects.release.approval.summary', $project) }}" class="apv-celeb-btn secondary">
            📋 종합 보고서 보기
        </a>
        <a href="{{ route('ai-agent.dashboard') }}" class="apv-celeb-btn secondary">
            🚀 새 프로젝트 시작
        </a>
    </div>
</div>
@endif

{{-- 승인 완료 배너 (일반) --}}
@if($isApproved)
<div class="apv-approved-banner">
    <svg width="22" height="22" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <div class="apv-approved-banner-text">릴리즈 단계 승인 완료 ✅ — 프로젝트 100% 완성!</div>
        <div class="apv-approved-banner-sub">모든 Phase가 완료되었습니다. 🎊 웍스 Agent와 함께해주셔서 감사합니다!</div>
    </div>
</div>
@endif

<div class="apv-grid">

    {{-- ─── 좌: 진단 체크리스트 ─────────────────────────────────────── --}}
    <div>

        <div class="apv-section" id="diagnosisPanel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <span class="apv-section-title" style="margin:0;">완성도 진단</span>
                <span style="font-size:13px;font-weight:700;color:{{ $diagnosis['overall_percent'] >= 100 ? '#16a34a' : '#7c3aed' }};" data-overall-pct>
                    {{ $diagnosis['overall_percent'] }}%
                </span>
            </div>
            <div class="apv-progress-bg">
                <div class="apv-progress-bar {{ $diagnosis['overall_percent'] >= 100 ? 'complete' : '' }}"
                     style="width:{{ $diagnosis['overall_percent'] }}%;"></div>
            </div>

            {{-- Blocking --}}
            <div class="apv-divider">필수 산출물 ({{ $diagnosis['blocking_complete'] }}/{{ $diagnosis['blocking_total'] }})</div>
            <div class="apv-list" id="blockingList">
                @foreach($diagnosis['blocking'] as $item)
                <div class="apv-item {{ $item['complete'] ? 'done' : 'missing' }}">
                    <span class="apv-item-icon">{{ $item['complete'] ? '✅' : '❌' }}</span>
                    <div class="apv-item-body">
                        <div class="apv-item-label">
                            {{ $item['label'] }}
                            <span class="apv-task-tag">{{ $item['source_task'] }}</span>
                            @if($item['complete'])
                                <span class="apv-pill ok">생성됨</span>
                            @endif
                        </div>
                        <div class="apv-item-note">{{ $item['note'] }}</div>
                        @if(!$item['complete'])
                            <a href="{{ route($item['route_name'], $project) }}" class="apv-item-link">→ 작업하러 가기</a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Warnings --}}
            <div class="apv-divider" style="margin-top:18px;">권장 사항 ({{ $diagnosis['warning_complete'] }}/{{ $diagnosis['warning_total'] }})</div>
            <div class="apv-list" id="warningList">
                @foreach($diagnosis['warnings'] as $item)
                <div class="apv-item {{ $item['complete'] ? 'warn-ok' : 'warn-no' }}">
                    <span class="apv-item-icon">{{ $item['complete'] ? '✅' : '⚠️' }}</span>
                    <div class="apv-item-body">
                        <div class="apv-item-label">
                            {{ $item['label'] }}
                            <span class="apv-task-tag">{{ $item['source_task'] }}</span>
                            @if($item['complete'])
                                <span class="apv-pill ok">생성됨</span>
                            @endif
                        </div>
                        <div class="apv-item-note">{{ $item['note'] }}</div>
                        @if(!$item['complete'])
                            <a href="{{ route($item['route_name'], $project) }}" class="apv-item-link">→ 작업하러 가기</a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Missing required notice --}}
        @if(!$diagnosis['can_request'] && !$isPending && !$isApproved)
        <div style="display:flex;align-items:flex-start;gap:8px;padding:12px 16px;background:#fef2f2;border:1.5px solid #fca5a5;border-radius:10px;font-size:13px;color:#b91c1c;margin-bottom:16px;">
            <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:1px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            <div>
                <strong>승인 요청 불가</strong> — 필수 산출물이 미완성입니다.<br>
                <span style="font-size:12px;opacity:.85;">미완성: {{ implode(', ', $diagnosis['missing_required']) }}</span>
            </div>
        </div>
        @endif

        {{-- 프로젝트 종합 통계 --}}
        <div class="apv-section">
            <div class="apv-section-title">프로젝트 종합 통계</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                @foreach([
                    ['산출물', $projectStats['total_artifacts'] . '개'],
                    ['화면 수', $projectStats['total_screens'] . '개'],
                    ['요구사항', $projectStats['total_requirements'] . '개'],
                    ['웍스 호출', number_format($projectStats['total_ai_calls']) . '회'],
                    ['총 토큰', number_format($projectStats['total_tokens'])],
                    ['웍스 비용', '$' . $projectStats['total_cost_usd']],
                    ['소요 기간', $projectStats['duration_days'] . '일'],
                    ['Phase 완료', $projectStats['completed_phases'] . '/5'],
                ] as [$key, $val])
                <div style="display:flex;flex-direction:column;padding:10px 14px;background:#f8fafc;border-radius:9px;">
                    <span style="font-size:11px;color:#94a3b8;margin-bottom:3px;">{{ $key }}</span>
                    <span style="font-size:14px;font-weight:800;color:#1e1b2e;">{{ $val }}</span>
                </div>
                @endforeach
            </div>

            @if(!empty($projectStats['approvals']))
            <div style="margin-top:16px;">
                <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;">승인 이력</div>
                @foreach($projectStats['approvals'] as $approval)
                <div class="apv-history-row">
                    <span style="font-weight:600;color:#1e1b2e;">{{ $approval['stage_label'] }}</span>
                    <span style="color:#64748b;">{{ $approval['approver'] }}</span>
                    <span style="color:#94a3b8;">{{ $approval['approved_at'] }}</span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

    </div>

    {{-- ─── 우: 승인 게이트 + 릴리즈 구성 ──────────────────────────── --}}
    <div>
        <x-ai-agent.approval-gate
            :gate="$gate"
            type="stage"
            :target-id="$stage->id"
            :project="$project"
            label="릴리즈 단계"
        />

        {{-- Stage stats --}}
        <div class="apv-section" style="margin-top:16px;">
            <div class="apv-section-title">단계 통계</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;color:#475569;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">상태</span>
                    <span style="font-weight:700;">
                        @if($isApproved)   <span style="color:#16a34a;">승인 완료 🎊</span>
                        @elseif($isPending) <span style="color:#b45309;">승인 대기 중</span>
                        @else               <span style="color:#7c3aed;">진행 중</span>
                        @endif
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">필수 산출물</span>
                    <span style="font-weight:700;">{{ $diagnosis['blocking_complete'] }} / {{ $diagnosis['blocking_total'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">권장 사항</span>
                    <span style="font-weight:700;">{{ $diagnosis['warning_complete'] }} / {{ $diagnosis['warning_total'] }}</span>
                </div>
            </div>
        </div>

        {{-- Phase 5 tasks --}}
        <div class="apv-section" style="margin-top:0;">
            <div class="apv-section-title">Phase 5 구성 작업</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach([
                    ['T48', '통합 릴리즈 패키지', route('ai-agent.projects.release.package.index', $project)],
                    ['T49', '배포 가이드',          route('ai-agent.projects.release.deploy-guide.index', $project)],
                    ['T50', '사용자 매뉴얼',         route('ai-agent.projects.release.user-manual.index', $project)],
                    ['T51', '마이그레이션 가이드',    route('ai-agent.projects.release.migration-guide.index', $project)],
                ] as [$task, $label, $link])
                <a href="{{ $link }}" style="display:flex;align-items:center;gap:8px;padding:6px 10px;border-radius:8px;background:#f8fafc;text-decoration:none;font-size:12.5px;color:#1e1b2e;transition:background .1s;" onmouseover="this.style.background='#ede8ff'" onmouseout="this.style.background='#f8fafc'">
                    <span style="font-size:10px;font-weight:700;background:#ede8ff;color:#7c3aed;padding:1px 7px;border-radius:99px;">{{ $task }}</span>
                    {{ $label }}
                    <svg style="margin-left:auto;" width="11" height="11" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
                @endforeach
            </div>
        </div>

        {{-- 종합 보고서 링크 --}}
        <div class="apv-section" style="margin-top:0;background:#fafaff;">
            <div class="apv-section-title">종합 보고서</div>
            <p style="font-size:12.5px;color:#64748b;margin:0 0 12px;line-height:1.6;">
                전체 5개 Phase의 산출물과 웍스 활용 통계를 종합한 보고서를 확인합니다.
            </p>
            <a href="{{ route('ai-agent.projects.release.approval.summary', $project) }}"
               style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;background:#7c3aed;color:#fff;border-radius:8px;font-size:12.5px;font-weight:700;text-decoration:none;">
                📋 종합 보고서 보기 →
            </a>
        </div>

        {{-- 다음 단계 안내 (완료 후) --}}
        @if($isApproved)
        <div class="apv-section" style="background:#fdf4ff;border-color:#e9d5ff;margin-top:0;">
            <div class="apv-section-title" style="color:#7c3aed;">💡 시스템 운영 안내</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:12.5px;color:#4c1d95;">
                <div>📦 <strong>DEPLOY.md</strong>를 따라 시스템을 배포하세요</div>
                <div>📖 <strong>MANUAL.md</strong>를 사용자에게 배포하세요</div>
                <div>🔧 <strong>MIGRATION.md</strong>를 참고하여 데이터를 셋업하세요</div>
            </div>
        </div>
        @endif

    </div>

</div>

@endsection

@push('scripts')
<script>
async function refreshDiagnosis(btn) {
    btn.classList.add('spinning');
    btn.disabled = true;
    try {
        const res  = await fetch('{{ route('ai-agent.projects.release.approval.diagnosis', $project) }}', {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        });
        const data = await res.json();
        updateDiagnosis(data);
    } catch (e) {
        console.error('진단 새로고침 실패', e);
    }
    btn.classList.remove('spinning');
    btn.disabled = false;
}

function updateDiagnosis(data) {
    const blockingList = document.getElementById('blockingList');
    if (blockingList) {
        blockingList.innerHTML = data.blocking.map(item => {
            const cls  = item.complete ? 'done' : 'missing';
            const icon = item.complete ? '✅' : '❌';
            const pill = item.complete ? `<span class="apv-pill ok">생성됨</span>` : '';
            const link = !item.complete
                ? `<a href="${item.route_url || '#'}" class="apv-item-link">→ 작업하러 가기</a>`
                : '';
            return `<div class="apv-item ${cls}">
                <span class="apv-item-icon">${icon}</span>
                <div class="apv-item-body">
                    <div class="apv-item-label">${item.label}<span class="apv-task-tag">${item.source_task}</span>${pill}</div>
                    <div class="apv-item-note">${item.note || ''}</div>
                    ${link}
                </div>
            </div>`;
        }).join('');
    }

    const warningList = document.getElementById('warningList');
    if (warningList) {
        warningList.innerHTML = data.warnings.map(item => {
            const cls  = item.complete ? 'warn-ok' : 'warn-no';
            const icon = item.complete ? '✅' : '⚠️';
            const pill = item.complete ? `<span class="apv-pill ok">생성됨</span>` : '';
            const link = !item.complete
                ? `<a href="${item.route_url || '#'}" class="apv-item-link">→ 작업하러 가기</a>`
                : '';
            return `<div class="apv-item ${cls}">
                <span class="apv-item-icon">${icon}</span>
                <div class="apv-item-body">
                    <div class="apv-item-label">${item.label}<span class="apv-task-tag">${item.source_task}</span>${pill}</div>
                    <div class="apv-item-note">${item.note || ''}</div>
                    ${link}
                </div>
            </div>`;
        }).join('');
    }

    const bar = document.querySelector('.apv-progress-bar');
    if (bar) {
        bar.style.width = data.overall_percent + '%';
        bar.classList.toggle('complete', data.overall_percent >= 100);
    }

    document.querySelectorAll('[data-overall-pct]').forEach(el => {
        el.textContent = data.overall_percent + '%';
        el.style.color = data.overall_percent >= 100 ? '#16a34a' : '#7c3aed';
    });
}
</script>
@endpush
