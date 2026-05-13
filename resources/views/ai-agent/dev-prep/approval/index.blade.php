@extends('layouts.ai-agent')
@section('title', '개발 준비 단계 승인 — 웍스 Agent')

@php
use App\Enums\Agent\StageStatus;
$isApproved = $stage->status === StageStatus::APPROVED;
$isPending  = $stage->status === StageStatus::PENDING_APPROVAL;
@endphp

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────────────── */
.apv-header { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:20px; }
.apv-header-left h1 { font-size:22px; font-weight:800; color:#1e1b2e; margin:0 0 4px; }
.apv-header-left p  { font-size:13.5px; color:#64748b; margin:0; }

/* ── Grid layout ─────────────────────────────────────────────────── */
.apv-grid { display:grid; grid-template-columns:1fr 340px; gap:20px; align-items:start; }
@media(max-width:900px) { .apv-grid { grid-template-columns:1fr; } }

/* ── Section card ─────────────────────────────────────────────────── */
.apv-section { background:#fff; border:1.5px solid #ede8ff; border-radius:14px; padding:20px 22px; margin-bottom:16px; }
.apv-section-title { font-size:13px; font-weight:700; color:#1e1b2e; margin:0 0 16px; display:flex; align-items:center; gap:8px; }

/* ── Progress bar ─────────────────────────────────────────────────── */
.apv-progress-wrap { margin-bottom:18px; }
.apv-progress-bg { background:#ede8ff; border-radius:99px; height:10px; overflow:hidden; }
.apv-progress-bar { height:10px; border-radius:99px; transition:width .4s ease; background:linear-gradient(90deg,#7c3aed,#a78bfa); }
.apv-progress-bar.complete { background:linear-gradient(90deg,#16a34a,#4ade80); }

/* ── Checklist ────────────────────────────────────────────────────── */
.apv-list { display:flex; flex-direction:column; gap:8px; }
.apv-item { display:flex; align-items:flex-start; gap:10px; padding:11px 14px; border-radius:10px; border:1.5px solid; }
.apv-item.done     { background:#f0fdf4; border-color:#bbf7d0; }
.apv-item.missing  { background:#fef2f2; border-color:#fca5a5; }
.apv-item.warn-ok  { background:#fff; border-color:#e2e8f0; }
.apv-item.warn-no  { background:#fffbeb; border-color:#fde68a; }

.apv-item-icon  { font-size:16px; flex-shrink:0; margin-top:1px; }
.apv-item-body  { flex:1; min-width:0; }
.apv-item-label { font-size:13px; font-weight:700; color:#1e1b2e; margin-bottom:2px; }
.apv-item-note  { font-size:11.5px; color:#64748b; margin-top:2px; }
.apv-item-link  { font-size:11.5px; color:#7c3aed; text-decoration:none; font-weight:600; }
.apv-item-link:hover { text-decoration:underline; }

/* ── Coverage pill ────────────────────────────────────────────────── */
.apv-pill { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:2px 9px; border-radius:99px; margin-left:6px; }
.apv-pill.ok  { background:#dcfce7; color:#15803d; }
.apv-pill.low { background:#fef3c7; color:#92400e; }

/* ── Section divider ─────────────────────────────────────────────── */
.apv-divider { font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:.06em; margin:14px 0 8px; padding:0 2px; }

/* ── Approved banner ─────────────────────────────────────────────── */
.apv-approved-banner { display:flex; align-items:center; gap:10px; padding:14px 18px; background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; margin-bottom:18px; }
.apv-approved-banner-text { font-size:13.5px; font-weight:700; color:#15803d; }
.apv-approved-banner-sub  { font-size:12px; color:#166534; margin-top:2px; }

/* ── Refresh btn ─────────────────────────────────────────────────── */
.apv-refresh-btn { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; border:1.5px solid #e2e8f0; background:#f8fafc; color:#475569; transition:all .15s; }
.apv-refresh-btn:hover { background:#e2e8f0; }
.apv-refresh-btn.spinning svg { animation:spin .7s linear infinite; }
@keyframes spin { to { transform:rotate(360deg); } }

/* ── Score badge ─────────────────────────────────────────────────── */
.apv-score-badge { display:inline-flex; align-items:center; gap:4px; font-size:11px; font-weight:700; padding:2px 8px; border-radius:99px; margin-left:6px; }
.apv-score-badge.high   { background:#dcfce7; color:#15803d; }
.apv-score-badge.mid    { background:#fef3c7; color:#92400e; }
.apv-score-badge.low    { background:#fee2e2; color:#b91c1c; }
.apv-score-badge.none   { background:#f1f5f9; color:#64748b; }
</style>
@endpush

@section('ai-agent-content')

<div class="apv-header">
    <div class="apv-header-left">
        <h1>개발 준비 단계 승인 게이트</h1>
        <p>ERD, API 명세, 권한 모델 및 코드 산출물이 완료되면 매니저에게 승인을 요청하고 개발 단계를 활성화합니다.</p>
    </div>
    <button class="apv-refresh-btn" id="refreshBtn" onclick="refreshDiagnosis(this)">
        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
        </svg>
        진단 새로고침
    </button>
</div>

{{-- Approved banner --}}
@if($isApproved)
<div class="apv-approved-banner">
    <svg width="22" height="22" fill="none" stroke="#16a34a" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div>
        <div class="apv-approved-banner-text">개발 준비 단계 승인 완료</div>
        <div class="apv-approved-banner-sub">개발 단계가 활성화되었습니다. 다음 단계로 진행하세요.</div>
    </div>
</div>
@endif

<div class="apv-grid">

    {{-- ─── 좌: 진단 체크리스트 ─────────────────────────────────────── --}}
    <div>

        {{-- Progress --}}
        <div class="apv-section" id="diagnosisPanel">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
                <span class="apv-section-title" style="margin:0;">완성도 진단</span>
                <span style="font-size:13px;font-weight:700;color:{{ $diagnosis['overall_percent'] >= 100 ? '#16a34a' : '#7c3aed' }};">
                    {{ $diagnosis['overall_percent'] }}%
                </span>
            </div>
            <div class="apv-progress-wrap">
                <div class="apv-progress-bg">
                    <div class="apv-progress-bar {{ $diagnosis['overall_percent'] >= 100 ? 'complete' : '' }}"
                         style="width:{{ $diagnosis['overall_percent'] }}%;"></div>
                </div>
            </div>

            {{-- Blocking --}}
            <div class="apv-divider">필수 산출물 ({{ $diagnosis['blocking_complete'] }}/{{ $diagnosis['blocking_total'] }})</div>
            <div class="apv-list" id="blockingList">
                @foreach($diagnosis['blocking'] as $item)
                @php
                    $link = match($item['type']) {
                        'erd'        => route('ai-agent.projects.pre-dev.erd', $project),
                        'api_spec'   => route('ai-agent.projects.pre-dev.api-spec', $project),
                        'rbac_model' => route('ai-agent.projects.pre-dev.rbac', $project),
                        default      => null,
                    };
                @endphp
                <div class="apv-item {{ $item['complete'] ? 'done' : 'missing' }}">
                    <span class="apv-item-icon">{{ $item['complete'] ? '✅' : '❌' }}</span>
                    <div class="apv-item-body">
                        <div class="apv-item-label">{{ $item['label'] }}</div>
                        @if(!$item['complete'] && $link)
                            <a href="{{ $link }}" class="apv-item-link">→ 생성하러 가기</a>
                        @endif
                        @if($item['note'])
                            <div class="apv-item-note">{{ $item['note'] }}</div>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Warnings --}}
            <div class="apv-divider" style="margin-top:18px;">권장 산출물 ({{ $diagnosis['warning_complete'] }}/{{ $diagnosis['warning_total'] }})</div>
            <div class="apv-list" id="warningList">
                @foreach($diagnosis['warnings'] as $item)
                @php
                    $link = match($item['type']) {
                        'code_gen_prompt' => route('ai-agent.projects.pre-dev.code-prompts', $project),
                        'frontend_code'   => route('ai-agent.projects.dev.frontend-code', $project),
                        'code_validation' => route('ai-agent.projects.dev.code-validation', $project),
                        default           => null,
                    };
                    $cls = $item['complete'] ? 'warn-ok' : 'warn-no';
                @endphp
                <div class="apv-item {{ $cls }}">
                    <span class="apv-item-icon">{{ $item['complete'] ? '✅' : '⚠️' }}</span>
                    <div class="apv-item-body">
                        <div class="apv-item-label">
                            {{ $item['label'] }}
                            @if(($item['coverage'] ?? null) !== null)
                                <span class="apv-pill {{ $item['complete'] ? 'ok' : 'low' }}">
                                    {{ $item['covered'] }}/{{ $item['total'] }} ({{ $item['coverage'] }}%)
                                </span>
                            @endif
                            @if($item['type'] === 'code_validation' && ($item['avg_score'] ?? null) !== null)
                                @php
                                    $scoreClass = $item['avg_score'] >= 80 ? 'high' : ($item['avg_score'] >= 60 ? 'mid' : 'low');
                                @endphp
                                <span class="apv-score-badge {{ $scoreClass }}">평균 {{ $item['avg_score'] }}점</span>
                                @if(($item['critical_count'] ?? 0) > 0)
                                    <span class="apv-score-badge low">Critical {{ $item['critical_count'] }}건</span>
                                @endif
                            @endif
                        </div>
                        @if($item['note'])
                            <div class="apv-item-note">{{ $item['note'] }}</div>
                        @endif
                        @if(!$item['complete'] && $link)
                            <a href="{{ $link }}" class="apv-item-link">→ 작업하러 가기</a>
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

    </div>

    {{-- ─── 우: 승인 게이트 컴포넌트 ──────────────────────────────────── --}}
    <div>
        <x-ai-agent.approval-gate
            :gate="$gate"
            type="stage"
            :target-id="$stage->id"
            :project="$project"
            label="개발 준비 단계"
        />

        {{-- Stage info --}}
        <div class="apv-section" style="margin-top:16px;">
            <div class="apv-section-title">단계 정보</div>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:13px;color:#475569;">
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">상태</span>
                    <span style="font-weight:700;">
                        @if($isApproved)
                            <span style="color:#16a34a;">승인 완료</span>
                        @elseif($isPending)
                            <span style="color:#b45309;">승인 대기 중</span>
                        @else
                            <span style="color:#7c3aed;">진행 중</span>
                        @endif
                    </span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">필수 산출물</span>
                    <span style="font-weight:700;">{{ $diagnosis['blocking_complete'] }} / {{ $diagnosis['blocking_total'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">권장 산출물</span>
                    <span style="font-weight:700;">{{ $diagnosis['warning_complete'] }} / {{ $diagnosis['warning_total'] }}</span>
                </div>
                @if($diagnosis['total_screens'] > 0)
                <div style="display:flex;justify-content:space-between;">
                    <span style="color:#94a3b8;font-size:12px;">총 화면 수</span>
                    <span style="font-weight:700;">{{ $diagnosis['total_screens'] }}개</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Next step guide --}}
        @if($isApproved)
        <div class="apv-section" style="margin-top:16px;background:#f0fdf4;border-color:#bbf7d0;">
            <div class="apv-section-title" style="color:#15803d;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
                다음 단계: 개발
            </div>
            <p style="font-size:12.5px;color:#166534;margin:0 0 12px;line-height:1.6;">
                개발 단계가 활성화되었습니다. Frontend 코드 생성, Backend 개발, API 연계를 시작할 수 있습니다.
            </p>
            <a href="{{ route('ai-agent.projects.dev.index', $project) }}"
               style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;background:#16a34a;color:#fff;border-radius:8px;font-size:12.5px;font-weight:700;text-decoration:none;">
                개발 단계로 이동 →
            </a>
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
        const res  = await fetch('{{ route('ai-agent.projects.pre-dev.approval.diagnosis', $project) }}', {
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
        const linkMap = {
            'erd':        '{{ route('ai-agent.projects.pre-dev.erd', $project) }}',
            'api_spec':   '{{ route('ai-agent.projects.pre-dev.api-spec', $project) }}',
            'rbac_model': '{{ route('ai-agent.projects.pre-dev.rbac', $project) }}',
        };
        blockingList.innerHTML = data.blocking.map(item => {
            const link = linkMap[item.type];
            const cls  = item.complete ? 'done' : 'missing';
            const icon = item.complete ? '✅' : '❌';
            return `<div class="apv-item ${cls}">
                <span class="apv-item-icon">${icon}</span>
                <div class="apv-item-body">
                    <div class="apv-item-label">${item.label}</div>
                    ${!item.complete && link ? `<a href="${link}" class="apv-item-link">→ 생성하러 가기</a>` : ''}
                    ${item.note ? `<div class="apv-item-note">${item.note}</div>` : ''}
                </div>
            </div>`;
        }).join('');
    }

    const warningList = document.getElementById('warningList');
    if (warningList) {
        const linkMap = {
            'code_gen_prompt': '{{ route('ai-agent.projects.pre-dev.code-prompts', $project) }}',
            'frontend_code':   '{{ route('ai-agent.projects.dev.frontend-code', $project) }}',
            'code_validation': '{{ route('ai-agent.projects.dev.code-validation', $project) }}',
        };
        warningList.innerHTML = data.warnings.map(item => {
            const link     = linkMap[item.type];
            const cls      = item.complete ? 'warn-ok' : 'warn-no';
            const icon     = item.complete ? '✅' : '⚠️';
            const pillCls  = item.complete ? 'ok' : 'low';

            let pills = '';
            if (item.coverage !== null) {
                pills += `<span class="apv-pill ${pillCls}">${item.covered}/${item.total} (${item.coverage}%)</span>`;
            }
            if (item.type === 'code_validation' && item.avg_score !== null) {
                const sc = item.avg_score >= 80 ? 'high' : (item.avg_score >= 60 ? 'mid' : 'low');
                pills += `<span class="apv-score-badge ${sc}">평균 ${item.avg_score}점</span>`;
                if (item.critical_count > 0) {
                    pills += `<span class="apv-score-badge low">Critical ${item.critical_count}건</span>`;
                }
            }

            return `<div class="apv-item ${cls}">
                <span class="apv-item-icon">${icon}</span>
                <div class="apv-item-body">
                    <div class="apv-item-label">${item.label}${pills}</div>
                    ${item.note ? `<div class="apv-item-note">${item.note}</div>` : ''}
                    ${!item.complete && link ? `<a href="${link}" class="apv-item-link">→ 작업하러 가기</a>` : ''}
                </div>
            </div>`;
        }).join('');
    }

    const bar = document.querySelector('.apv-progress-bar');
    if (bar) {
        bar.style.width = data.overall_percent + '%';
        bar.classList.toggle('complete', data.overall_percent >= 100);
    }

    const pct = document.querySelector('.apv-progress-bar')?.parentElement?.previousElementSibling?.querySelector('span:last-child');
    // Update % text via DOM approach
    document.querySelectorAll('[data-overall-pct]').forEach(el => {
        el.textContent = data.overall_percent + '%';
    });
}
</script>
@endpush
