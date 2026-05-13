@extends('layouts.app')

@section('title', '주간 업무 보고 - ' . $project->name)

@section('header-actions')@endsection

@section('page-actions')
<button type="button"
    onclick="openWeeklyReportPopup('{{ route('projects.weekly-reports.create', $project) }}')"
    style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--t500);color:#fff;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:opacity .15s;"
    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    새 보고서 작성
</button>
@endsection

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">주간 업무 보고</span>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'weekly-reports'])
<div class="space-y-5">

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:10px 16px;font-size:13px;color:#065f46;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 16px;font-size:13px;color:#991b1b;">
        {{ session('error') }}
    </div>
    @endif

    {{-- 매니저 툴바 --}}
    @if($isManager && !$reports->isEmpty())
    <div id="manager-toolbar" style="background:#fff;border:1px solid #e9e7fb;border-radius:10px;padding:12px 16px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <label style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#6b7280;cursor:pointer;user-select:none;">
            <input type="checkbox" id="select-all-reports" style="width:15px;height:15px;accent-color:#6d28d9;cursor:pointer;">
            전체 선택
        </label>
        <span id="selected-count" style="font-size:12px;color:#9ca3af;margin-left:2px;">0개 선택됨</span>

        <div style="flex:1;"></div>

        {{-- 주차 보고 분석 (전체 팀원) --}}
        <button type="button" onclick="openAnalysis('all')"
            style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1.5px solid #c4b5fd;border-radius:8px;font-size:12.5px;font-weight:600;color:#6d28d9;background:#faf5ff;cursor:pointer;transition:all .15s;"
            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            주차 보고 분석
        </button>

        {{-- 팀원 주간 보고 분석 (선택 팀원) --}}
        <button type="button" id="member-analysis-btn" onclick="openMemberAnalysisPicker()" disabled
            style="display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border:1.5px solid #d1d5db;border-radius:8px;font-size:12.5px;font-weight:600;color:#6b7280;background:#fff;cursor:not-allowed;opacity:.5;transition:all .15s;">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            팀원 주간 보고 분석
        </button>
    </div>
    @endif

    {{-- 목록 --}}
    @if($reports->isEmpty())
    <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;padding:60px 24px;text-align:center;">
        <div style="width:52px;height:52px;background:#f5f3ff;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg width="24" height="24" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <p style="font-size:14px;font-weight:600;color:#374151;margin-bottom:6px;">아직 작성된 보고서가 없습니다.</p>
        <p style="font-size:12.5px;color:#9ca3af;margin-bottom:18px;">첫 번째 주간 보고서를 작성해 보세요.</p>
        <button type="button"
            onclick="openWeeklyReportPopup('{{ route('projects.weekly-reports.create', $project) }}')"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:#4f46e5;color:#fff;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;">
            보고서 작성하기
        </button>
    </div>
    @else

    {{-- 주차별 그룹 --}}
    @php
        $grouped = $reports->groupBy(function($r) {
            return $r->week_start_date->format('Y-m-d');
        })->sortKeysDesc();

        // 매니저 담당자별 분석용: 보고서 작성자 목록 (user_id → author_name)
        $reportUsers = $reports->unique('user_id')->mapWithKeys(fn($r) => [$r->user_id => $r->author_name]);
    @endphp

    @foreach($grouped as $weekStart => $weekReports)
    @php
        $firstReport = $weekReports->first();
        $weekLabel   = $firstReport->week_label;
        $weekEnd     = \Carbon\Carbon::parse($weekStart)->addDays(6)->format('m/d');
        $weekStartFmt = \Carbon\Carbon::parse($weekStart)->format('m/d');
    @endphp

    <div style="background:#fff;border:1px solid #e9e7fb;border-radius:12px;overflow:hidden;">
        {{-- 주차 헤더 --}}
        <div style="display:flex;align-items:center;gap:10px;padding:12px 20px;background:linear-gradient(135deg,#f5f3ff,#ede9fe);border-bottom:1px solid #ddd6fe;">
            <span style="background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;border-radius:7px;padding:3px 11px;font-size:12.5px;font-weight:700;">{{ $weekLabel }}</span>
            <span style="font-size:12px;color:#7c3aed;font-weight:500;">{{ $weekStartFmt }} ~ {{ $weekEnd }}</span>
            <span style="margin-left:auto;font-size:11.5px;color:#9ca3af;">{{ $weekReports->count() }}명 작성</span>
        </div>

        {{-- 보고서 행 --}}
        @foreach($weekReports as $report)
        @php $isOwner = $report->user_id === $user->id; @endphp
        <div style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid #f3f4f6;transition:background .12s;"
            onmouseover="this.style.background='#fafaf9'" onmouseout="this.style.background=''">

            {{-- 체크박스 (매니저만) --}}
            @if($isManager)
            <div style="flex-shrink:0;">
                <input type="checkbox" class="report-checkbox" value="{{ $report->id }}"
                    data-user-id="{{ $report->user_id }}" data-author="{{ $report->author_name }}"
                    style="width:15px;height:15px;accent-color:#6d28d9;cursor:pointer;">
            </div>
            @endif

            {{-- 보고자 아바타 --}}
            <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#6d28d9,#4f46e5);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <span style="color:#fff;font-size:13px;font-weight:700;">{{ mb_substr($report->author_name, 0, 1) }}</span>
            </div>

            {{-- 보고자 정보 --}}
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span style="font-size:11px;color:#9ca3af;font-weight:500;">보고자</span>
                    <span style="font-size:13.5px;font-weight:600;color:#1f2937;">{{ $report->author_name }}</span>
                    @if($isOwner)
                    <span style="background:#ede9fe;color:#7c3aed;border-radius:4px;font-size:10px;font-weight:700;padding:1px 6px;">내 보고서</span>
                    @endif
                    @if($report->team_name)
                    <span style="background:#f3f4f6;color:#6b7280;border-radius:4px;font-size:11px;padding:1px 7px;">{{ $report->team_name }}</span>
                    @endif
                    @if($report->manager_name)
                    <span style="display:inline-flex;align-items:center;gap:3px;font-size:11px;color:#6b7280;">
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        수신 {{ $report->manager_name }}
                    </span>
                    @endif
                </div>
                <div style="display:flex;align-items:center;gap:10px;margin-top:3px;">
                    <span style="font-size:11.5px;color:#9ca3af;">
                        작성일 {{ $report->report_date->format('Y.m.d') }}
                    </span>
                    <span style="font-size:11.5px;color:#9ca3af;">
                        최종 수정 {{ $report->updated_at->format('Y.m.d H:i') }}
                    </span>
                </div>
            </div>

            {{-- 상태 배지 --}}
            <div style="flex-shrink:0;">
                @if($report->status === 'submitted')
                <span style="display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    제출 완료
                </span>
                @else
                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border-radius:6px;padding:4px 10px;font-size:12px;font-weight:600;">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    임시 저장
                </span>
                @endif
            </div>

            {{-- 액션 버튼 --}}
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                @if($isOwner || $user->isAdmin())
                {{-- 수정 (팝업) --}}
                <button type="button"
                    onclick="openWeeklyReportPopup('{{ route('projects.weekly-reports.edit', [$project, $report]) }}')"
                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border:1.5px solid #d1d5db;border-radius:7px;font-size:12px;font-weight:600;color:#374151;background:#fff;cursor:pointer;transition:all .12s;"
                    onmouseover="this.style.borderColor='#7c3aed';this.style.color='#7c3aed';this.style.background='#f5f3ff'"
                    onmouseout="this.style.borderColor='#d1d5db';this.style.color='#374151';this.style.background='#fff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    수정
                </button>
                @endif
                {{-- 웍스 워드 다운로드 (소유자/어드민/매니저) --}}
                @if($isOwner || $user->isAdmin() || $isManager)
                <a href="{{ route('projects.weekly-reports.download', [$project, $report]) }}"
                    style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border:1.5px solid #c4b5fd;border-radius:7px;font-size:12px;font-weight:600;color:#6d28d9;text-decoration:none;background:#faf5ff;transition:all .12s;"
                    onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Word
                </a>
                @endif
                {{-- 삭제 --}}
                @if($isOwner || $user->isAdmin())
                <form method="POST" action="{{ route('projects.weekly-reports.destroy', [$project, $report]) }}"
                    onsubmit="return confirm('보고서를 삭제하시겠습니까?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        style="display:inline-flex;align-items:center;gap:4px;padding:5px 10px;border:1.5px solid #fca5a5;border-radius:7px;font-size:12px;font-weight:600;color:#dc2626;background:#fff;cursor:pointer;transition:all .12s;"
                        onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0V5a1 1 0 011-1h4a1 1 0 011 1v2H5z"/></svg>
                        삭제
                    </button>
                </form>
                @endif
                @if(!$isOwner && !$user->isAdmin() && !$isManager)
                <span style="font-size:11.5px;color:#d1d5db;padding:0 4px;">열람 전용</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endforeach

    @endif

</div>

{{-- ── 주간보고 수정 모달 팝업 ── --}}
<style>
#wr-popup-backdrop {
    display:none; position:fixed; inset:0; z-index:1200;
    background:rgba(15,23,42,.5);
    align-items:center; justify-content:center;
    padding:20px;
}
#wr-popup-backdrop.open { display:flex; }
#wr-popup-panel {
    background:#f9fafb; border-radius:14px;
    box-shadow:0 24px 80px rgba(0,0,0,.22);
    width:100%; max-width:980px;
    height:90vh; max-height:900px;
    display:flex; flex-direction:column;
    opacity:0; transform:scale(.96) translateY(10px);
    transition:opacity .2s ease, transform .2s ease;
    overflow:hidden;
}
#wr-popup-backdrop.open #wr-popup-panel {
    opacity:1; transform:scale(1) translateY(0);
}

{{-- 웍스 분석 모달 --}}
#ai-modal-backdrop {
    display:none; position:fixed; inset:0; z-index:1300;
    background:rgba(15,23,42,.55);
    align-items:center; justify-content:center;
    padding:20px;
}
#ai-modal-backdrop.open { display:flex; }
#ai-modal-panel {
    background:#fff; border-radius:14px;
    box-shadow:0 24px 80px rgba(0,0,0,.22);
    width:100%; max-width:760px;
    max-height:88vh;
    display:flex; flex-direction:column;
    opacity:0; transform:scale(.96) translateY(10px);
    transition:opacity .2s ease, transform .2s ease;
    overflow:hidden;
}
#ai-modal-backdrop.open #ai-modal-panel {
    opacity:1; transform:scale(1) translateY(0);
}
#ai-modal-body {
    flex:1; overflow-y:auto; padding:24px;
    font-size:13.5px; line-height:1.75; color:#374151;
}
#ai-modal-body h1,#ai-modal-body h2,#ai-modal-body h3 {
    font-weight:700; color:#1f2937; margin:16px 0 6px;
}
#ai-modal-body h1{font-size:16px;}
#ai-modal-body h2{font-size:14.5px;}
#ai-modal-body h3{font-size:13.5px;}
#ai-modal-body ul,#ai-modal-body ol{padding-left:20px;margin:6px 0;}
#ai-modal-body li{margin:2px 0;}
#ai-modal-body strong{font-weight:700;color:#1f2937;}
#ai-modal-body hr{border:none;border-top:1px solid #e5e7eb;margin:14px 0;}
#ai-modal-body p{margin:6px 0;}

{{-- 담당자 선택 피커 --}}
#member-picker-backdrop {
    display:none; position:fixed; inset:0; z-index:1400;
    background:rgba(15,23,42,.45);
    align-items:center; justify-content:center;
    padding:20px;
}
#member-picker-backdrop.open { display:flex; }
#member-picker-panel {
    background:#fff; border-radius:12px;
    box-shadow:0 12px 40px rgba(0,0,0,.2);
    width:320px; max-height:440px;
    display:flex; flex-direction:column;
    overflow:hidden;
}
</style>

<div id="wr-popup-backdrop" onclick="if(event.target===this) closeWeeklyReportPopup(false)">
    <div id="wr-popup-panel">
        <iframe id="wr-popup-iframe" src="" style="flex:1;border:none;width:100%;border-radius:0 0 14px 14px;"></iframe>
    </div>
</div>

{{-- 웍스 분석 모달 --}}
<div id="ai-modal-backdrop" onclick="if(event.target===this)closeAiModal()">
    <div id="ai-modal-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #e5e7eb;flex-shrink:0;">
            <div style="display:flex;align-items:center;gap:8px;">
                <svg width="18" height="18" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                <span id="ai-modal-title" style="font-size:14px;font-weight:700;color:#1f2937;">웍스 분석</span>
            </div>
            <button onclick="closeAiModal()"
                style="display:flex;align-items:center;gap:4px;padding:5px 11px;font-size:13px;color:#6b7280;border:1px solid #e4e4e7;border-radius:7px;background:#fff;cursor:pointer;">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                닫기
            </button>
        </div>
        <div id="ai-modal-body">
            <div id="ai-loading" style="display:flex;flex-direction:column;align-items:center;justify-content:center;padding:60px 20px;gap:14px;">
                <div style="width:36px;height:36px;border:3px solid #e9e7fb;border-top-color:#7c3aed;border-radius:50%;animation:spin .8s linear infinite;"></div>
                <p style="font-size:13px;color:#6b7280;">웍스가 보고서를 분석하고 있습니다...</p>
            </div>
            <div id="ai-result" style="display:none;"></div>
            <div id="ai-error" style="display:none;padding:20px;background:#fef2f2;border-radius:8px;color:#dc2626;font-size:13px;"></div>
        </div>
    </div>
</div>

{{-- 담당자 선택 피커 --}}
<div id="member-picker-backdrop" onclick="if(event.target===this)closeMemberPicker()">
    <div id="member-picker-panel">
        <div style="padding:14px 16px;border-bottom:1px solid #e5e7eb;font-size:13.5px;font-weight:700;color:#1f2937;">
            담당자 선택
        </div>
        <div id="member-picker-list" style="flex:1;overflow-y:auto;padding:8px;">
        </div>
        <div style="padding:10px 16px;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;">
            <button onclick="closeMemberPicker()"
                style="padding:6px 14px;font-size:12.5px;color:#6b7280;border:1px solid #d1d5db;border-radius:7px;background:#fff;cursor:pointer;">
                취소
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ─── 주간보고 팝업 ───────────────────────────────
async function openWeeklyReportPopup(url) {
    const iframe = document.getElementById('wr-popup-iframe');
    const backdrop = document.getElementById('wr-popup-backdrop');
    iframe.src = url + (url.includes('?') ? '&' : '?') + 'popup=1';
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
}

async function closeWeeklyReportPopup(refresh) {
    const backdrop = document.getElementById('wr-popup-backdrop');
    const panel = document.getElementById('wr-popup-panel');
    const iframe = document.getElementById('wr-popup-iframe');
    panel.style.opacity = '0';
    panel.style.transform = 'scale(.96) translateY(10px)';
    setTimeout(async function() {
        backdrop.classList.remove('open');
        panel.style.opacity = '';
        panel.style.transform = '';
        iframe.src = '';
        document.body.style.overflow = '';
        if (refresh) location.reload();
    }, 200);
}

document.addEventListener('keydown', async function(e) {
    if (e.key === 'Escape') {
        if (document.getElementById('member-picker-backdrop').classList.contains('open')) { closeMemberPicker(); return; }
        if (document.getElementById('ai-modal-backdrop').classList.contains('open')) { closeAiModal(); return; }
        if (document.getElementById('wr-popup-backdrop').classList.contains('open')) { closeWeeklyReportPopup(false); return; }
    }
});

@if($isManager && !$reports->isEmpty())
// ─── 체크박스 선택 로직 ───────────────────────────
const selectAllCb = document.getElementById('select-all-reports');
const memberBtn   = document.getElementById('member-analysis-btn');
const countLabel  = document.getElementById('selected-count');

function getChecked() {
    return Array.from(document.querySelectorAll('.report-checkbox:checked'));
}

async function updateToolbar() {
    const checked = getChecked();
    const count   = checked.length;
    countLabel.textContent = count + '개 선택됨';

    // 팀원 주간 보고 분석: 1명 이상 선택 시 활성화
    const userIds = [...new Set(checked.map(cb => cb.dataset.userId))];
    const hasUser = userIds.length > 0;
    memberBtn.disabled = !hasUser;
    memberBtn.style.opacity     = hasUser ? '1' : '.5';
    memberBtn.style.cursor      = hasUser ? 'pointer' : 'not-allowed';
    memberBtn.style.color       = hasUser ? '#6d28d9' : '#6b7280';
    memberBtn.style.borderColor = hasUser ? '#c4b5fd' : '#d1d5db';
    memberBtn.style.background  = hasUser ? '#faf5ff' : '#fff';

    // 전체 선택 체크박스 상태
    const allCbs = document.querySelectorAll('.report-checkbox');
    selectAllCb.indeterminate = count > 0 && count < allCbs.length;
    selectAllCb.checked = count > 0 && count === allCbs.length;
}

selectAllCb.addEventListener('change', async function() {
    document.querySelectorAll('.report-checkbox').forEach(cb => cb.checked = this.checked);
    updateToolbar();
});

document.querySelectorAll('.report-checkbox').forEach(cb => {
    cb.addEventListener('change', updateToolbar);
});

// ─── 담당자 선택 피커 ─────────────────────────────
const reportUsers = @json($reportUsers ?? collect());

async function openMemberAnalysisPicker() {
    const checked = getChecked();
    const userIds = [...new Set(checked.map(cb => cb.dataset.userId))];

    if (userIds.length === 1) {
        // 선택된 담당자가 1명이면 바로 분석
        openAnalysis('member', userIds[0], checked[0].dataset.author);
        return;
    }

    // 여러 담당자 → 피커 표시
    const list = document.getElementById('member-picker-list');
    list.innerHTML = '';

    const uniqueUsers = {};
    checked.forEach(cb => { uniqueUsers[cb.dataset.userId] = cb.dataset.author; });

    Object.entries(uniqueUsers).forEach(([uid, name]) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.style.cssText = 'display:block;width:100%;text-align:left;padding:9px 12px;font-size:13px;color:#374151;border:none;background:none;border-radius:7px;cursor:pointer;transition:background .1s;';
        btn.textContent = name;
        btn.onmouseover = () => btn.style.background = '#f5f3ff';
        btn.onmouseout  = () => btn.style.background = 'none';
        btn.onclick = () => { closeMemberPicker(); openAnalysis('member', uid, name); };
        list.appendChild(btn);
    });

    document.getElementById('member-picker-backdrop').classList.add('open');
}

async function closeMemberPicker() {
    document.getElementById('member-picker-backdrop').classList.remove('open');
}

// ─── 웍스 분석 모달 ─────────────────────────────────
async function openAnalysis(type, userId, userName) {
    const modal = document.getElementById('ai-modal-backdrop');
    const title = document.getElementById('ai-modal-title');
    const loading = document.getElementById('ai-loading');
    const result  = document.getElementById('ai-result');
    const errEl   = document.getElementById('ai-error');

    title.textContent = type === 'member' ? userName + ' 팀원 주간 보고 분석' : '주차 보고 분석 (모든 팀원)';
    loading.style.display = 'flex';
    result.style.display  = 'none';
    errEl.style.display   = 'none';
    result.innerHTML      = '';

    modal.classList.add('open');
    document.body.style.overflow = 'hidden';

    const body = { type };
    if (type === 'member' && userId) body.user_id = userId;

    fetch('{{ route('projects.weekly-reports.analyze', $project) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify(body),
    })
    .then(r => r.json())
    .then(data => {
        loading.style.display = 'none';
        if (data.error) {
            errEl.textContent   = data.error;
            errEl.style.display = 'block';
        } else {
            result.innerHTML   = markdownToHtml(data.result || '');
            result.style.display = 'block';
        }
    })
    .catch(err => {
        loading.style.display = 'none';
        errEl.textContent   = '웍스 분석 요청 중 오류가 발생했습니다.';
        errEl.style.display = 'block';
    });
}

async function closeAiModal() {
    const modal = document.getElementById('ai-modal-backdrop');
    modal.classList.remove('open');
    document.body.style.overflow = '';
}

async function markdownToHtml(md) {
    return md
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>')
        .replace(/\*(.+?)\*/g,'<em>$1</em>')
        .replace(/^### (.+)$/gm,'<h3>$1</h3>')
        .replace(/^## (.+)$/gm,'<h2>$1</h2>')
        .replace(/^# (.+)$/gm,'<h1>$1</h1>')
        .replace(/^---$/gm,'<hr>')
        .replace(/^[-*] (.+)$/gm,'<li>$1</li>')
        .replace(/(<li>.*<\/li>\n?)+/g, s => '<ul>' + s + '</ul>')
        .replace(/^\d+\. (.+)$/gm,'<li>$1</li>')
        .replace(/\n{2,}/g,'</p><p>')
        .replace(/^(?!<[hul]|<li|<hr)(.+)$/gm,'<p>$1</p>')
        .replace(/<p><\/p>/g,'');
}
@endif
</script>
<style>
@keyframes spin { to { transform: rotate(360deg); } }
</style>
@endpush
@endsection
