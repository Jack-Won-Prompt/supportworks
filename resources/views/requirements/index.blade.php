@extends('layouts.app')

@section('title', $project->name . ' - 요구사항')

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">프로젝트</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">요구사항</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    <a href="{{ route('projects.requirements.export', $project) }}"
       style="padding:6px 13px;font-size:12px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;text-decoration:none;background:#fff;"
       onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">CSV 내보내기</a>
    <button onclick="openAiModal()"
            style="padding:6px 13px;font-size:12px;font-weight:500;color:#7c3aed;border:1.5px solid #ddd6fe;border-radius:8px;background:#faf5ff;cursor:pointer;"
            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">요구사항 웍스 분석</button>
    <button onclick="openReqModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">+ 새 요구사항</button>
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'requirements'])

{{-- 필터 바 --}}
<form method="GET" id="filter-form" style="background:#fff;border:1px solid #f3f4f6;border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="제목 검색..."
           style="flex:1;min-width:160px;padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;"
           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">

    <select name="status" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">전체 상태</option>
        @foreach(\App\Models\Requirement::STATUS_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="priority" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">전체 우선순위</option>
        @foreach(\App\Models\Requirement::PRIORITY_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('priority') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="category" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">전체 카테고리</option>
        @foreach(\App\Models\Requirement::CATEGORY_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="assignee" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">전체 담당자</option>
        @foreach($members as $m)
            <option value="{{ $m->id }}" {{ request('assignee') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
    </select>

    @if($project->si_mode_enabled)
    <select name="approval_status" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">전체 승인상태</option>
        @foreach(\App\Models\Requirement::APPROVAL_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('approval_status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>
    @endif

    <button type="submit"
            style="padding:6px 14px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;background:#fff;cursor:pointer;color:#374151;"
            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">검색</button>
    @if(request()->anyFilled(['search','status','priority','category','assignee','approval_status']))
    <a href="{{ route('projects.requirements.index', $project) }}"
       style="padding:6px 10px;font-size:12px;color:#6b7280;text-decoration:none;">✕ 초기화</a>
    @endif
</form>

{{-- 일괄 액션 바 (체크 시 등장) --}}
<div id="bulk-bar" style="display:none;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:10px 16px;margin-bottom:10px;align-items:center;gap:10px;flex-wrap:wrap;">
    <span id="bulk-count" style="font-size:13px;font-weight:600;color:#1d4ed8;">0개 선택됨</span>
    <button id="bulk-apply-btn" onclick="openApplyModal()"
            style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:12px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:7px;cursor:pointer;transition:opacity .15s;"
            onmouseover="if(!this.disabled)this.style.background='#6d28d9'" onmouseout="if(!this.disabled)this.style.background='#7c3aed'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span id="bulk-apply-text">기획서 추가</span>
    </button>
    <button id="bulk-gantt-btn" onclick="openGanttModal()"
            style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:12px;font-weight:600;color:#0369a1;border:1.5px solid #bae6fd;border-radius:7px;background:#f0f9ff;cursor:pointer;transition:opacity .15s;"
            onmouseover="if(!this.disabled)this.style.background='#e0f2fe'" onmouseout="if(!this.disabled)this.style.background='#f0f9ff'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span id="bulk-gantt-text">Task 추가</span>
    </button>
    <button id="bulk-delete-btn" onclick="openDeleteConfirm()"
            style="display:inline-flex;align-items:center;gap:5px;padding:6px 14px;font-size:12px;font-weight:600;color:#dc2626;border:1.5px solid #fecaca;border-radius:7px;background:#fff5f5;cursor:pointer;transition:opacity .15s;"
            onmouseover="if(!this.disabled)this.style.background='#fee2e2'" onmouseout="if(!this.disabled)this.style.background='#fff5f5'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        <span id="bulk-delete-text">삭제</span>
    </button>
    <button onclick="clearSelection()"
            style="padding:6px 12px;font-size:12px;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:7px;cursor:pointer;"
            onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">선택 해제</button>
</div>

{{-- 테이블 --}}
<div style="background:#fff;border:1px solid #f3f4f6;border-radius:10px;overflow:hidden;">
    {{-- 헤더 행 --}}
    <div style="display:grid;grid-template-columns:36px 90px 1fr 80px 90px 100px 90px 32px;align-items:center;padding:8px 16px;border-bottom:1.5px solid #f3f4f6;gap:8px;background:#f9fafb;">
        <div style="text-align:center;">
            <input type="checkbox" id="select-all-chk" title="전체 선택" style="cursor:pointer;"
                   onchange="toggleSelectAll(this)">
        </div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;">우선순위</div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;">제목</div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;text-align:center;">카테고리</div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;text-align:center;">상태</div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;text-align:center;">담당자</div>
        <div style="font-size:11px;font-weight:600;color:#6b7280;text-align:center;">
            @if($project->si_mode_enabled)
                승인상태
            @else
                <span style="display:flex;align-items:center;justify-content:center;gap:3px;white-space:nowrap;">
                    <span style="color:#7c3aed;">기획서</span>
                    <span style="color:#d1d5db;">›</span>
                    <span style="color:#0369a1;">Task</span>
                    <span style="color:#d1d5db;">›</span>
                    <span style="color:#16a34a;">완료</span>
                </span>
            @endif
        </div>
        <div></div>
    </div>

    @forelse($requirements as $req)
    <div class="req-row" data-id="{{ $req->id }}"
         data-title="{{ $req->title }}"
         data-description="{{ $req->description ?? '' }}"
         data-assignee-id="{{ $req->assignee_id ?? '' }}"
         data-applied="{{ $req->applied_to_plan ? '1' : '0' }}"
         data-in-gantt="{{ in_array($req->id, $ganttReqIds) ? '1' : '0' }}"
         data-gantt-blocked="{{ in_array($req->id, $ganttBlockedReqIds) ? '1' : '0' }}"
         data-reporter-id="{{ $req->reporter_id }}"
         style="display:grid;grid-template-columns:36px 90px 1fr 80px 90px 100px 90px 32px;align-items:center;padding:12px 16px;border-bottom:1px solid #f9fafb;gap:8px;transition:background .1s;"
         onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">

        {{-- 체크박스 --}}
        <div style="text-align:center;">
            <input type="checkbox" class="req-chk" value="{{ $req->id }}" style="cursor:pointer;"
                   onchange="updateBulkBar()">
        </div>

        {{-- 우선순위 --}}
        <div>
            <span style="display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;background:{{ $req->priority_color['bg'] }};color:{{ $req->priority_color['text'] }};">
                {{ $req->priority_label }}
            </span>
        </div>

        {{-- 제목 --}}
        <div style="min-width:0;">
            <a href="#" onclick="openReqDetail({{ $req->id }}, '{{ route('projects.requirements.show', [$project, $req]) }}'); return false;"
               style="font-size:13px;font-weight:600;color:#18181b;text-decoration:none;display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='var(--t500)'" onmouseout="this.style.color='#18181b'">{{ $req->title }}</a>
            <div style="font-size:11px;color:#a1a1aa;margin-top:2px;">
                @if($req->source_type === 'ai_analyzed')
                    <span style="display:inline-block;padding:0 5px;background:#ede9fe;color:#6d28d9;border-radius:3px;font-size:10px;font-weight:600;margin-right:4px;">웍스</span>
                @elseif($req->source_type === 'attachment_ai')
                    @php
                        preg_match('/#(\d+)/', $req->source_ref ?? '', $_srcM);
                        $_srcId = $_srcM[1] ?? null;
                    @endphp
                    <span title="{{ $req->source_ref }}" style="display:inline-block;padding:0 5px;background:#ecfdf5;color:#059669;border-radius:3px;font-size:10px;font-weight:600;margin-right:2px;">파일웍스</span>
                    @if($_srcId)
                        <button onclick="event.stopPropagation(); openReqDetail({{ $_srcId }}, '{{ route('projects.requirements.show', [$project, $_srcId]) }}')"
                                title="{{ $req->source_ref }}"
                                style="display:inline-flex;align-items:center;gap:2px;padding:0 5px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:3px;font-size:10px;font-weight:600;color:#16a34a;cursor:pointer;margin-right:4px;"
                                onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">
                            <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            출처 #{{ $_srcId }}
                        </button>
                    @elseif($req->source_ref)
                        <span style="display:inline-block;padding:0 5px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:3px;font-size:10px;color:#16a34a;margin-right:4px;">{{ $req->source_ref }}</span>
                    @endif
                @else
                    <span style="display:inline-block;padding:0 5px;background:#f3f4f6;color:#6b7280;border-radius:3px;font-size:10px;font-weight:500;margin-right:4px;">직접</span>
                @endif
                {{ $req->reporter?->name }} · {{ $req->created_at->format('Y-m-d') }}
                @if($req->tags)
                    @foreach($req->tags as $tag)
                        <span style="margin-left:4px;padding:0 5px;background:#f3f4f6;border-radius:4px;font-size:10px;color:#6b7280;">{{ $tag }}</span>
                    @endforeach
                @endif
                @if($req->attachments_count > 0)
                    <button onclick="event.stopPropagation(); openSavedAttachmentReviewModal({{ $req->id }}, '{{ route('projects.requirements.show', [$project, $req]) }}')"
                            style="margin-left:6px;display:inline-flex;align-items:center;gap:3px;padding:2px 7px;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:5px;color:#7c3aed;font-size:11px;font-weight:600;cursor:pointer;"
                            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ $req->attachments_count }}
                    </button>
                @endif
            </div>
        </div>

        {{-- 카테고리 --}}
        <div style="font-size:12px;color:#6b7280;text-align:center;">{{ $req->category_label }}</div>

        {{-- 상태 --}}
        <div style="text-align:center;">
            <span style="display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:500;background:{{ $req->status_color['bg'] }};color:{{ $req->status_color['text'] }};">
                {{ $req->status_label }}
            </span>
        </div>

        {{-- 담당자 --}}
        <div style="font-size:12px;color:#6b7280;text-align:center;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $req->assignee?->name ?? '-' }}
        </div>

        {{-- SI: 승인 상태 / 액션 버튼 --}}
        @if($project->si_mode_enabled)
        <div style="text-align:center;">
            <span style="display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;background:{{ $req->approval_color['bg'] }};color:{{ $req->approval_color['text'] }};">
                {{ $req->approval_label }}
            </span>
        </div>
        @else
        <div style="text-align:center;">
            @if($req->applied_to_plan && in_array($req->id, $ganttReqIds))
                {{-- 3단계: Task 추가 완료 --}}
                <span title="일정 Task 추가 완료"
                      style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;font-size:10px;font-weight:700;color:#16a34a;border:1px solid #bbf7d0;border-radius:5px;background:#f0fdf4;white-space:nowrap;">
                    <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>Task 완료
                </span>
            @elseif($req->applied_to_plan)
                {{-- 2단계: 일정 Task 추가 --}}
                <button onclick="openGanttModalForRow(this)"
                        title="일정 Task 추가"
                        style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;font-size:10px;font-weight:600;color:#0369a1;border:1px solid #bae6fd;border-radius:5px;background:#f0f9ff;cursor:pointer;white-space:nowrap;"
                        onmouseover="this.style.background='#e0f2fe'" onmouseout="this.style.background='#f0f9ff'">
                    <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>Task 추가
                </button>
            @else
                {{-- 1단계: 기획서 추가 --}}
                <button onclick="openApplyModal(['{{ $req->id }}'])"
                        title="기획서에 추가"
                        style="display:inline-flex;align-items:center;gap:3px;padding:2px 7px;font-size:10px;font-weight:600;color:#7c3aed;border:1px solid #ddd6fe;border-radius:5px;background:#faf5ff;cursor:pointer;white-space:nowrap;"
                        onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#faf5ff'">
                    <svg width="9" height="9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>기획서 추가
                </button>
            @endif
        </div>
        @endif

        {{-- 삭제 버튼 (등록자 또는 관리자만 표시) --}}
        <div style="text-align:center;">
            @if($req->reporter_id === auth()->id() || auth()->user()->isAdmin())
            <button onclick="event.stopPropagation(); deleteOneReq({{ $req->id }}, '{{ route('projects.requirements.destroy', [$project, $req]) }}')"
                    title="삭제"
                    style="display:inline-flex;align-items:center;justify-content:center;width:26px;height:26px;background:none;border:1px solid transparent;border-radius:6px;color:#d1d5db;cursor:pointer;transition:all .15s;padding:0;"
                    onmouseover="this.style.color='#dc2626';this.style.background='#fef2f2';this.style.borderColor='#fecaca'"
                    onmouseout="this.style.color='#d1d5db';this.style.background='none';this.style.borderColor='transparent'">
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
            @endif
        </div>
    </div>
    @empty
    <div style="padding:60px 20px;text-align:center;">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5" style="margin:0 auto 12px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <p style="color:#9ca3af;font-size:13px;margin-bottom:12px;">아직 등록된 요구사항이 없습니다.</p>
        <button onclick="openReqModal()"
                style="padding:8px 18px;background:var(--t500);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"
                onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">+ 새 요구사항 만들기</button>
    </div>
    @endforelse
</div>

@if($requirements->hasPages())
<div style="margin-top:16px;">{{ $requirements->links() }}</div>
@endif

{{-- 기획서 적용 모달 --}}
<div id="apply-overlay" onclick="closeApplyModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="apply-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:600px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">기획서에 요구사항 적용</h3>
        <button onclick="closeApplyModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    <div style="padding:20px 22px;display:flex;flex-direction:column;gap:16px;">
        {{-- 선택된 요구사항 목록 --}}
        <div>
            <p style="font-size:12px;font-weight:600;color:#374151;margin:0 0 6px;">선택된 요구사항 <span id="apply-req-count" style="color:var(--t500);">0</span>개</p>
            <ul id="apply-req-list" style="margin:0;padding:0;list-style:none;max-height:120px;overflow-y:auto;border:1px solid #f3f4f6;border-radius:8px;padding:8px 12px;font-size:12px;color:#52525b;display:flex;flex-direction:column;gap:4px;"></ul>
        </div>

        {{-- 대상 기획서 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">대상 기획서 <span style="color:#ef4444;">*</span></label>
            <select id="apply-plan-sel"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;"
                    onchange="onPlanChange(this)">
                <option value="">기획서 선택...</option>
            </select>
        </div>

        {{-- 삽입 위치 --}}
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:8px;">삽입 위치</label>
            <div style="display:flex;flex-direction:column;gap:6px;">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                    <input type="radio" name="apply-position" value="end" checked onchange="onPositionChange()"> 기획서 끝에 추가
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                    <input type="radio" name="apply-position" value="beginning" onchange="onPositionChange()"> 기획서 처음에 추가
                </label>
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;color:#374151;">
                    <input type="radio" name="apply-position" value="after_section" onchange="onPositionChange()"> 특정 섹션 다음에
                </label>
            </div>
        </div>

        {{-- 섹션 선택 (after_section일 때만) --}}
        <div id="section-anchor-wrap" style="display:none;">
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">삽입할 섹션 선택</label>
            <select id="apply-anchor-sel"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;">
                <option value="">섹션 선택...</option>
            </select>
            <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">선택한 섹션의 다음 위치에 삽입됩니다. 섹션을 찾지 못하면 끝에 추가됩니다.</p>
        </div>

        {{-- 이미 적용된 항목 경고 --}}
        <div id="apply-skip-warn" style="display:none;background:#fefce8;border:1px solid #fef08a;border-radius:8px;padding:8px 12px;font-size:12px;color:#713f12;"></div>

        {{-- 미리보기 --}}
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                <label style="font-size:12px;font-weight:600;color:#374151;">미리보기</label>
                <button onclick="loadPreview()"
                        style="padding:3px 10px;font-size:11px;border:1.5px solid #e4e4e7;border-radius:6px;background:#fff;cursor:pointer;color:#374151;"
                        onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">새로고침</button>
            </div>
            <pre id="apply-preview" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;font-size:11px;color:#475569;overflow-x:auto;max-height:160px;white-space:pre-wrap;word-break:break-word;margin:0;">선택 후 미리보기가 여기에 표시됩니다.</pre>
        </div>

        {{-- 에러 --}}
        <div id="apply-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:4px;border-top:1px solid #f3f4f6;">
            <button onclick="doApply()" id="apply-btn"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:9px;cursor:pointer;"
                    onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">적용</button>
            <button onclick="closeApplyModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
        </div>
    </div>
</div>

{{-- 등록 모달 --}}
<div id="req-overlay" onclick="closeReqModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="req-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:540px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $project->name }}</p>
            <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">새 요구사항 등록</h3>
        </div>
        <button onclick="closeReqModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    <form id="req-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:13px;">
        @csrf
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">제목 <span style="color:#ef4444;">*</span></label>
            <input type="text" name="title" required placeholder="요구사항 제목을 입력하세요"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">설명</label>
            <textarea name="description" rows="4" placeholder="요구사항에 대한 상세 설명을 입력하세요"
                      style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                      onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">우선순위 <span style="color:#ef4444;">*</span></label>
                <select name="priority" style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    @foreach(\App\Models\Requirement::PRIORITY_LABELS as $val => $label)
                        <option value="{{ $val }}" {{ $val === 'medium' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">카테고리 <span style="color:#ef4444;">*</span></label>
                <select name="category" style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                    @foreach(\App\Models\Requirement::CATEGORY_LABELS as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">담당자</label>
            <select name="assignee_id" style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                <option value="">담당자 없음</option>
                @foreach($members as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">태그 <span style="font-weight:400;color:#9ca3af;">(쉼표로 구분)</span></label>
            <input type="text" name="tags" placeholder="예: 로그인, 인증, API"
                   style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
        </div>

        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">첨부 파일 <span style="font-weight:400;color:#9ca3af;">(최대 10MB · 여러 파일 선택 가능)</span></label>
            <label id="req-file-label" style="display:flex;align-items:center;gap:8px;padding:8px 11px;border:1.5px dashed #e4e4e7;border-radius:8px;cursor:pointer;transition:border-color .15s;background:#fafafa;"
                   onmouseover="this.style.borderColor='var(--t500)'" onmouseout="this.style.borderColor='#e4e4e7'">
                <svg width="16" height="16" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span id="req-file-hint" style="font-size:12.5px;color:#94a3b8;">파일을 선택하세요</span>
                <input type="file" name="attachments[]" id="req-file-input" multiple
                       style="display:none;" onchange="reqFileChanged(this)">
            </label>
            <div id="req-file-list" style="margin-top:6px;display:flex;flex-direction:column;gap:4px;"></div>
        </div>

        @if($project->si_mode_enabled)
        <div style="border-top:1px solid #f3f4f6;padding-top:13px;">
            <p style="font-size:11px;font-weight:600;color:#8b5cf6;margin:0 0 10px;">SI 계약 모드</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">요구사항 유형</label>
                    <select name="requirement_type" style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                        @foreach(\App\Models\Requirement::TYPE_LABELS as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">출처 참조 <span style="font-weight:400;color:#9ca3af;">(예: RFP 3.2.1)</span></label>
                    <input type="text" name="source_ref" placeholder="RFP / 계약서 참조"
                           style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;font-family:inherit;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
        </div>
        @endif

        <div id="req-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>

        <div style="display:flex;gap:8px;padding-top:4px;">
            <button type="submit" id="req-submit"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">등록</button>
            <button type="button" onclick="closeReqModal()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
        </div>
    </form>
</div>
{{-- 웍스 분석 모달 (멀티스텝) --}}
<div id="ai-overlay" onclick="closeAiModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="ai-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:560px;max-width:calc(100vw - 32px);max-height:92vh;overflow-y:auto;transition:width .2s;">

    {{-- 공통 헤더 --}}
    <div style="position:sticky;top:0;z-index:1;background:#fff;display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <div>
            <p style="font-size:11px;color:#94a3b8;margin:0 0 2px;">{{ $project->name }}</p>
            <h3 id="ai-modal-title" style="font-size:15px;font-weight:700;color:#18181b;margin:0;">웍스 요구사항 분석</h3>
        </div>
        <button onclick="closeAiModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>

    {{-- Step 1: 업로드 --}}
    <div id="ai-step-upload">
        <form id="ai-form" style="padding:20px 22px 22px;display:flex;flex-direction:column;gap:16px;">
            @csrf
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:6px;">문서 파일 <span style="font-weight:400;color:#9ca3af;">(선택)</span></label>
                <div id="ai-drop-zone" onclick="document.getElementById('ai-file-input').click()"
                     style="border:2px dashed #d1d5db;border-radius:10px;padding:24px;text-align:center;cursor:pointer;transition:border-color .15s;"
                     onmouseover="this.style.borderColor='#7c3aed'" onmouseout="this.style.borderColor='#d1d5db'">
                    <svg style="width:32px;height:32px;margin:0 auto 6px;display:block;color:#9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p style="font-size:13px;color:#6b7280;margin:0;">클릭하거나 파일을 드래그하세요</p>
                    <p style="font-size:11px;color:#9ca3af;margin:4px 0 0;">docx · xlsx · pptx · pdf · txt · md (최대 20MB, 10개)</p>
                </div>
                <input id="ai-file-input" type="file" name="files[]" multiple
                       accept=".docx,.xlsx,.xls,.pptx,.ppt,.pdf,.txt,.md,.csv,.log"
                       style="display:none;" onchange="aiUpdateFileList(this)">
                <ul id="ai-file-list" style="margin:6px 0 0;padding:0;list-style:none;display:flex;flex-direction:column;gap:3px;"></ul>
            </div>
            <div>
                <label for="ai-context" style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">
                    분석 컨텍스트 메모 <span style="font-weight:400;color:#9ca3af;">(선택)</span>
                </label>
                <textarea id="ai-context" name="context_note" rows="4" maxlength="2000"
                          placeholder="프로젝트 배경, 분석 목적, 제외할 내용 등을 입력하세요."
                          style="width:100%;padding:8px 11px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;"
                          onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
            </div>
            <div id="ai-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12.5px;color:#dc2626;"></div>
            <div style="display:flex;gap:8px;padding-top:4px;">
                <button type="submit" id="ai-submit"
                        style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:9px;cursor:pointer;font-family:inherit;"
                        onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">분석 시작</button>
                <button type="button" onclick="closeAiModal()"
                        style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;font-family:inherit;"
                        onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
            </div>
        </form>
    </div>

    {{-- Step 2: 분석 중 --}}
    <div id="ai-step-loading" style="display:none;padding:60px 22px 50px;text-align:center;">
        <svg class="animate-spin" style="width:48px;height:48px;margin:0 auto 16px;display:block;color:#7c3aed;" fill="none" viewBox="0 0 24 24">
            <circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
            <path style="opacity:.75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
        </svg>
        <p style="font-size:15px;font-weight:600;color:#374151;margin:0 0 8px;">웍스가 문서를 분석하고 있습니다</p>
        <p style="font-size:12px;color:#9ca3af;margin:0;">문서 분량에 따라 30초~1분 정도 소요될 수 있습니다.</p>
    </div>

    {{-- Step 3: 결과 검토 --}}
    <div id="ai-step-review" style="display:none;">
        <div style="padding:16px 22px 0;">
            <div id="ai-review-summary"></div>
            <div id="ai-review-warnings"></div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <p id="ai-review-count" style="font-size:13px;font-weight:600;color:#374151;margin:0;"></p>
                <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:#6b7280;cursor:pointer;">
                    <input type="checkbox" id="ai-select-all" onchange="aiToggleAll(this)"> 전체 선택
                </label>
            </div>
            <div id="ai-review-candidates" style="display:flex;flex-direction:column;gap:8px;padding-bottom:80px;"></div>
        </div>
        <div id="ai-review-error" style="display:none;margin:0 22px 8px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:12px;color:#dc2626;"></div>
        {{-- 하단 버튼 (sticky) --}}
        <div style="position:sticky;bottom:0;background:#fff;border-top:1px solid #f0f0f0;padding:12px 22px;display:flex;justify-content:space-between;align-items:center;">
            <button onclick="aiReject()" id="ai-reject-btn"
                    style="padding:8px 16px;font-size:13px;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;color:#6b7280;cursor:pointer;"
                    onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='#fff'">거부</button>
            <button onclick="aiApprove()" id="ai-approve-btn"
                    style="padding:8px 22px;font-size:13px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:8px;cursor:pointer;"
                    onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">선택한 요구사항 등록</button>
        </div>
    </div>

    {{-- Step 4: 등록 완료 --}}
    <div id="ai-step-success" style="display:none;padding:36px 22px 28px;text-align:center;">
        <div style="width:52px;height:52px;background:#d1fae5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg style="width:28px;height:28px;color:#059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p id="ai-success-msg" style="font-size:15px;font-weight:700;color:#18181b;margin:0 0 4px;"></p>
        <p style="font-size:12px;color:#6b7280;margin:0 0 22px;">요구사항 목록에서 확인할 수 있습니다.</p>
        <div style="background:#faf5ff;border:1.5px solid #ede9fe;border-radius:10px;padding:14px 16px;text-align:left;margin-bottom:14px;">
            <p style="font-size:12px;font-weight:600;color:#7c3aed;margin:0 0 8px;">기획서에 바로 적용</p>
            <div style="display:flex;gap:8px;">
                <select id="ai-success-plan-sel"
                        style="flex:1;padding:7px 10px;border:1.5px solid #ddd6fe;border-radius:7px;font-size:12px;outline:none;background:#fff;"></select>
                <button onclick="aiApplyToPlan()" id="ai-success-apply-btn"
                        style="padding:7px 16px;font-size:12px;font-weight:600;color:#fff;background:#7c3aed;border:none;border-radius:7px;cursor:pointer;"
                        onmouseover="this.style.background='#6d28d9'" onmouseout="this.style.background='#7c3aed'">적용</button>
            </div>
            <div id="ai-success-plan-error" style="display:none;margin-top:6px;font-size:11px;color:#dc2626;"></div>
        </div>
        <button onclick="closeAiModal();location.reload();"
                style="padding:8px 28px;font-size:13px;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">닫기</button>
    </div>

    {{-- Step 5: 분석 실패 --}}
    <div id="ai-step-failed" style="display:none;padding:40px 22px;text-align:center;">
        <div style="width:52px;height:52px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
            <svg style="width:28px;height:28px;color:#dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <p style="font-size:14px;font-weight:700;color:#18181b;margin:0 0 6px;">분석 실패</p>
        <p id="ai-failed-msg" style="font-size:12px;color:#dc2626;margin:0 0 22px;word-break:break-all;"></p>
        <div style="display:flex;gap:8px;justify-content:center;">
            <button onclick="showAiStep('upload')"
                    style="padding:8px 22px;font-size:13px;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:8px;cursor:pointer;"
                    onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">다시 시도</button>
            <button onclick="closeAiModal()"
                    style="padding:8px 20px;font-size:13px;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">닫기</button>
        </div>
    </div>
</div>

{{-- 요구사항 상세 팝업 --}}
<div id="rd-overlay" onclick="closeReqDetail()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10200;"></div>
<div id="rd-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10201;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:920px;max-width:calc(100vw - 32px);max-height:92vh;overflow:hidden;flex-direction:column;">

    {{-- 헤더 --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;flex-shrink:0;gap:12px;">
        <div style="flex:1;min-width:0;">
            <div id="rd-badges" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:7px;"></div>
            <h2 id="rd-title" style="font-size:17px;font-weight:700;color:#18181b;margin:0;line-height:1.4;"></h2>
            <p id="rd-meta" style="font-size:11px;color:#9ca3af;margin:4px 0 0;"></p>
            <div id="rd-source-banner" style="display:none;margin-top:6px;padding:5px 10px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:7px;font-size:11px;color:#15803d;align-items:center;gap:6px;flex-wrap:wrap;"></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
            <button id="rd-delete-btn" onclick="rdDelete()" style="display:none;padding:5px 10px;font-size:11px;color:#dc2626;border:1.5px solid #fecaca;border-radius:7px;background:#fff;cursor:pointer;"
                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='#fff'">삭제</button>
            <button onclick="closeReqDetail()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:2px;line-height:1;">&times;</button>
        </div>
    </div>

    {{-- 바디 (2컬럼) --}}
    <div style="display:grid;grid-template-columns:1fr 260px;flex:1;overflow:hidden;">

        {{-- 좌측 --}}
        <div style="overflow-y:auto;padding:18px 20px;border-right:1px solid #f3f4f6;display:flex;flex-direction:column;gap:16px;">

            {{-- 설명 --}}
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                    <p style="font-size:12px;font-weight:700;color:#374151;margin:0;">설명</p>
                    <button onclick="rdToggleDesc()" style="font-size:12px;color:var(--t500);background:none;border:none;cursor:pointer;padding:0;">편집</button>
                </div>
                <div id="rd-desc-view" style="font-size:13px;color:#374151;line-height:1.7;white-space:pre-wrap;min-height:40px;"></div>
                <div id="rd-desc-edit" style="display:none;">
                    <textarea id="rd-desc-input" rows="6" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;" onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
                    <div style="display:flex;gap:8px;margin-top:6px;">
                        <button onclick="rdSaveDesc()" style="padding:5px 14px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:7px;cursor:pointer;">저장</button>
                        <button onclick="rdToggleDesc()" style="padding:5px 12px;font-size:12px;color:#6b7280;border:1.5px solid #e4e4e7;background:#fff;border-radius:7px;cursor:pointer;">취소</button>
                    </div>
                </div>
            </div>

            {{-- 댓글 --}}
            <div>
                <p id="rd-comment-title" style="font-size:12px;font-weight:700;color:#374151;margin:0 0 10px;"></p>
                <div id="rd-comment-list" style="display:flex;flex-direction:column;gap:8px;margin-bottom:12px;"></div>
                <div style="display:flex;gap:8px;align-items:flex-end;">
                    <textarea id="rd-comment-input" rows="2" placeholder="댓글을 입력하세요..."
                              style="flex:1;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:none;font-family:inherit;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
                    <button onclick="rdPostComment()" style="padding:8px 12px;font-size:12px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;white-space:nowrap;" onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">등록</button>
                </div>
            </div>

            {{-- 변경 이력 --}}
            <div id="rd-history-wrap" style="display:none;">
                <p style="font-size:12px;font-weight:700;color:#374151;margin:0 0 8px;">변경 이력</p>
                <div id="rd-history-list" style="display:flex;flex-direction:column;gap:4px;"></div>
            </div>
        </div>

        {{-- 우측 사이드바 --}}
        <div style="overflow-y:auto;padding:16px 16px;display:flex;flex-direction:column;gap:14px;">

            {{-- 세부 정보 --}}
            <div>
                <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin:0 0 10px;">세부 정보</p>

                <div style="display:flex;flex-direction:column;gap:9px;">
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">상태</label>
                        <select id="rd-status" onchange="rdPatch('status', this.value)" style="width:100%;padding:6px 8px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;background:#fff;">
                            @foreach(\App\Models\Requirement::STATUS_LABELS as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">우선순위</label>
                        <select id="rd-priority" onchange="rdPatch('priority', this.value)" style="width:100%;padding:6px 8px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;background:#fff;">
                            @foreach(\App\Models\Requirement::PRIORITY_LABELS as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">카테고리</label>
                        <select id="rd-category" onchange="rdPatch('category', this.value)" style="width:100%;padding:6px 8px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;background:#fff;">
                            @foreach(\App\Models\Requirement::CATEGORY_LABELS as $v => $l)
                                <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">담당자</label>
                        <select id="rd-assignee" onchange="rdPatch('assignee_id', this.value)" style="width:100%;padding:6px 8px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:12px;outline:none;background:#fff;">
                            <option value="">담당자 없음</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">등록자</label>
                        <p id="rd-reporter" style="font-size:12px;color:#374151;margin:0;"></p>
                    </div>
                    <div>
                        <label style="display:block;font-size:11px;color:#9ca3af;margin-bottom:3px;">태그</label>
                        <div id="rd-tags" style="display:flex;flex-wrap:wrap;gap:4px;"></div>
                    </div>
                    <button id="rd-watch-btn" onclick="rdToggleWatch()" style="width:100%;padding:6px;font-size:12px;font-weight:600;border-radius:8px;border:1.5px solid #e4e4e7;background:#fff;color:#6b7280;cursor:pointer;margin-top:2px;"></button>
                </div>
            </div>

            {{-- 첨부 파일 --}}
            <div>
                <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin:0 0 8px;">첨부 파일</p>
                <div id="rd-attachments" style="display:flex;flex-direction:column;gap:5px;"></div>
            </div>

            {{-- 기획서 적용 이력 --}}
            <div>
                <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.05em;margin:0 0 8px;">기획서 적용 이력</p>
                <div id="rd-plan-apps" style="display:flex;flex-direction:column;gap:6px;"></div>
            </div>
        </div>
    </div>
</div>

{{-- 요구사항 삭제 확인 모달 --}}
<div id="del-overlay" onclick="closeDeleteConfirm()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10100;"></div>
<div id="del-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10101;background:#fff;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,.18);width:440px;max-width:calc(100vw - 32px);">
    <div style="display:flex;align-items:center;gap:10px;padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;">
        <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;background:#fee2e2;flex-shrink:0;">
            <svg width="16" height="16" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </span>
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">요구사항 삭제</h3>
        <button onclick="closeDeleteConfirm()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:0;line-height:1;">&times;</button>
    </div>
    <div style="padding:18px 22px;display:flex;flex-direction:column;gap:12px;">
        <div id="del-info" style="font-size:13px;color:#374151;"></div>
        <div id="del-skip-warn" style="display:none;background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:9px 12px;font-size:12px;color:#92400e;"></div>
        <p style="font-size:12px;color:#6b7280;margin:0;">삭제된 요구사항은 연결된 기획서에서도 제거됩니다. 이 작업은 되돌릴 수 없습니다.</p>
        <div id="del-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:9px 12px;font-size:12px;color:#dc2626;"></div>
        <div style="display:flex;gap:8px;padding-top:4px;">
            <button id="del-confirm-btn" onclick="doDeleteRequirements()"
                    style="flex:1;padding:9px;font-size:13px;font-weight:600;color:#fff;background:#dc2626;border:none;border-radius:9px;cursor:pointer;"
                    onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">삭제 확인</button>
            <button onclick="closeDeleteConfirm()"
                    style="padding:9px 20px;font-size:13px;font-weight:600;color:#52525b;background:#fff;border:1.5px solid #e4e4e7;border-radius:9px;cursor:pointer;"
                    onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
        </div>
    </div>
</div>

{{-- ── 파일 리뷰 + 웍스 분석 팝업 ──────────────────────────────── --}}
<div id="rfm-overlay" style="display:none;position:fixed;inset:0;z-index:10500;background:rgba(10,8,20,.78);backdrop-filter:blur(4px);align-items:center;justify-content:center;padding:28px;">
<div style="width:100%;max-width:1160px;height:calc(100vh - 56px);background:#1a1730;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.6);border:1px solid rgba(196,181,253,.12);">

    {{-- 상단바 --}}
    <div style="height:52px;background:rgba(20,17,35,.98);border-bottom:1px solid rgba(196,181,253,.12);display:flex;align-items:center;gap:10px;padding:0 16px;flex-shrink:0;border-radius:16px 16px 0 0;">
        <span style="font-size:12px;font-weight:700;color:#c4b5fd;letter-spacing:.03em;">파일 리뷰 · 웍스 요구사항 분석</span>
        <div id="rfm-tabs" style="display:flex;gap:4px;margin-left:8px;overflow-x:auto;flex:1;min-width:0;"></div>
        <button onclick="closeFileReviewModal()" style="display:inline-flex;align-items:center;gap:5px;color:#9ca3af;font-size:13px;font-weight:600;background:none;border:none;cursor:pointer;padding:6px 10px;border-radius:8px;flex-shrink:0;transition:background .15s;" onmouseover="this.style.background='rgba(255,255,255,.08)'" onmouseout="this.style.background='none'">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>닫기
        </button>
    </div>

    {{-- 본문 --}}
    <div style="display:flex;flex:1;min-height:0;overflow:hidden;">

        {{-- 뷰어 --}}
        <div style="flex:1;min-width:0;position:relative;background:#1f2937;overflow:hidden;display:flex;flex-direction:column;">
            <div id="rfm-loading" style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:14px;gap:12px;z-index:2;background:#1f2937;">
                <div style="width:32px;height:32px;border:3px solid rgba(196,181,253,.2);border-top-color:#9b8afb;border-radius:50%;animation:spin .8s linear infinite;"></div>
            </div>
            <img id="rfm-img" src="" alt="" style="display:none;position:absolute;inset:0;max-width:100%;max-height:100%;margin:auto;object-fit:contain;z-index:1;">
            <iframe id="rfm-frame" src="" style="display:none;position:absolute;inset:0;width:100%;height:100%;border:none;z-index:1;" onload="document.getElementById('rfm-loading').style.display='none';this.style.display='block';"></iframe>
            <pre id="rfm-text" style="display:none;position:absolute;inset:0;overflow:auto;padding:20px;color:#d1d5db;font-size:12px;line-height:1.6;font-family:monospace;background:#1f2937;margin:0;white-space:pre-wrap;word-break:break-all;z-index:1;"></pre>
            {{-- Excel / Word 렌더링 영역 --}}
            <div id="rfm-excel" style="display:none;position:absolute;top:0;left:0;right:0;bottom:0;overflow:auto;background:#fff;z-index:1;cursor:grab;user-select:none;"></div>
            {{-- Office 문서 하단 툴바 (페이징 + 확대/축소) --}}
            <div id="rfm-office-nav" style="display:none;position:absolute;bottom:0;left:0;right:0;height:44px;align-items:center;padding:0 16px;background:#111827;border-top:1px solid rgba(255,255,255,.07);z-index:3;">
                {{-- 좌측: 여백 (레이아웃 균형용) --}}
                <div style="flex:1;"></div>
                {{-- 중앙: 시트 페이징 --}}
                <div id="rfm-paging-controls" style="display:flex;align-items:center;gap:8px;">
                    <button onclick="rfmOfficeNav(-1)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>이전
                    </button>
                    <span id="rfm-office-page-info" style="font-size:13px;font-weight:600;color:#e5e7eb;min-width:140px;text-align:center;"></span>
                    <button onclick="rfmOfficeNav(1)"
                            style="display:inline-flex;align-items:center;gap:4px;padding:5px 12px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:12px;cursor:pointer;transition:background .15s;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'">
                        다음<svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </button>
                </div>
                {{-- 우측: 확대/축소 --}}
                <div style="flex:1;display:flex;align-items:center;justify-content:flex-end;gap:6px;">
                    <div style="width:1px;height:18px;background:rgba(255,255,255,.1);margin-right:2px;"></div>
                    <button onclick="rfmZoom(-0.2)"
                            style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:16px;line-height:1;cursor:pointer;transition:background .15s;flex-shrink:0;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'" title="축소">−</button>
                    <span id="rfm-zoom-label" style="font-size:12px;color:#9ca3af;min-width:36px;text-align:center;font-weight:600;">100%</span>
                    <button onclick="rfmZoom(0.2)"
                            style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#d1d5db;border-radius:6px;font-size:16px;line-height:1;cursor:pointer;transition:background .15s;flex-shrink:0;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'" title="확대">+</button>
                    <button onclick="rfmZoomReset()"
                            style="padding:4px 9px;display:inline-flex;align-items:center;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#9ca3af;border-radius:6px;font-size:11px;cursor:pointer;transition:background .15s;flex-shrink:0;"
                            onmouseover="this.style.background='rgba(255,255,255,.13)'" onmouseout="this.style.background='rgba(255,255,255,.07)'" title="원래 크기로">원래 크기</button>
                </div>
            </div>
            <div id="rfm-unsupported" style="display:none;position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#6b7280;font-size:13px;gap:8px;z-index:1;">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>미리보기를 지원하지 않는 형식입니다</span>
                <a id="rfm-unsupported-dl" href="#" style="display:none;margin-top:6px;padding:7px 18px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:8px;color:#9ca3af;font-size:12px;text-decoration:none;"
                   onmouseover="this.style.background='rgba(255,255,255,.15)'" onmouseout="this.style.background='rgba(255,255,255,.08)'">다운로드</a>
            </div>
        </div>

        {{-- 웍스 분석 패널 --}}
        <div style="width:300px;flex-shrink:0;background:#fff;border-left:1px solid #e5e7eb;display:flex;flex-direction:column;">

            {{-- 패널 헤더 --}}
            <div style="padding:14px 16px 12px;border-bottom:1px solid #f3f4f6;flex-shrink:0;">
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:10px;">
                    <svg width="15" height="15" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    <span style="font-size:13px;font-weight:700;color:#1f2937;">웍스 추가 요구기능 추천</span>
                    <span id="rfm-result-cnt" style="display:none;font-size:11px;background:#d1fae5;color:#065f46;padding:1px 7px;border-radius:10px;font-weight:700;"></span>
                </div>
                <button id="rfm-analyze-btn" onclick="rfmAnalyze()"
                        style="width:100%;padding:8px;font-size:13px;font-weight:600;color:#fff;background:#059669;border:none;border-radius:8px;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='#047857'" onmouseout="this.style.background='#059669'">
                    추가 요구기능 추천받기
                </button>
                <div id="rfm-ai-loading" style="display:none;margin-top:8px;display:flex;align-items:center;gap:8px;font-size:12px;color:#6b7280;padding:6px 0;">
                    <div style="width:14px;height:14px;border:2px solid #d1fae5;border-top-color:#059669;border-radius:50%;animation:spin .8s linear infinite;flex-shrink:0;"></div>
                    기획서·요구사항·파일 종합 분석 중...
                </div>
                <div id="rfm-ai-error" style="display:none;margin-top:8px;font-size:12px;color:#dc2626;background:#fef2f2;border:1px solid #fecaca;border-radius:7px;padding:7px 10px;"></div>
            </div>

            {{-- 결과 목록 --}}
            <div id="rfm-results" style="flex:1;overflow-y:auto;padding:10px 14px;display:flex;flex-direction:column;gap:6px;">
                <div id="rfm-empty" style="color:#9ca3af;font-size:12px;text-align:center;padding:24px 0;line-height:1.6;">분석 버튼을 눌러<br>요구사항을 추출하세요</div>
            </div>

            {{-- 하단 액션 --}}
            <div style="padding:12px 14px;border-top:1px solid #f3f4f6;flex-shrink:0;background:#fafaf9;display:flex;flex-direction:column;gap:8px;">
                <div id="rfm-select-bar" style="display:none;display:flex;align-items:center;justify-content:space-between;">
                    <label style="display:flex;align-items:center;gap:5px;font-size:12px;color:#6b7280;cursor:pointer;">
                        <input type="checkbox" id="rfm-select-all" onchange="rfmToggleAll(this.checked)"> 전체 선택
                    </label>
                    <span id="rfm-sel-cnt" style="font-size:12px;color:#059669;font-weight:600;"></span>
                </div>
                <button id="rfm-apply-btn" onclick="rfmApplyAndSubmit()"
                        style="width:100%;padding:9px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">
                    등록 완료
                </button>
                <button onclick="closeFileReviewModal()"
                        style="width:100%;padding:8px;font-size:12px;font-weight:600;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                        onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
            </div>
        </div>
    </div>
</div>
</div>

@endsection

@section('scripts')
<script>
const CSRF            = document.querySelector('meta[name="csrf-token"]').content;
const STORE_URL       = '{{ route('projects.requirements.store', $project) }}';
const AI_STORE_URL    = '{{ route('projects.requirements.analysis.store', $project) }}';
const PLANS_URL       = '{{ route('projects.plan-applications.plans', $project) }}';
const PREVIEW_URL     = '{{ route('projects.plan-applications.preview', $project) }}';
const APPLY_BASE      = '{{ url("projects/{$project->id}/planning") }}';
const BULK_DESTROY_URL = '{{ route('projects.requirements.bulk-destroy', $project) }}';

// ── 다중 선택 ────────────────────────────────────────────────
function getChecked() {
    return [...document.querySelectorAll('.req-chk:checked')].map(c => c.value);
}

async function updateBulkBar() {
    const checked = getChecked();
    const bar = document.getElementById('bulk-bar');
    bar.style.display = checked.length > 0 ? 'flex' : 'none';
    document.getElementById('bulk-count').textContent = checked.length + '개 선택됨';

    // 선택된 행의 상태별 카운트
    let canApply = 0, canGantt = 0;
    checked.forEach(id => {
        const row = document.querySelector(`.req-row[data-id="${id}"]`);
        if (!row) return;
        const applied = row.dataset.applied === '1';
        const inGantt = row.dataset.inGantt === '1';
        if (!applied) canApply++;
        else if (!inGantt) canGantt++;
    });

    const applyBtn = document.getElementById('bulk-apply-btn');
    applyBtn.disabled = canApply === 0;
    applyBtn.style.opacity    = canApply === 0 ? '0.35' : '1';
    applyBtn.style.cursor     = canApply === 0 ? 'not-allowed' : 'pointer';
    applyBtn.style.background = canApply === 0 ? '#a78bfa' : '#7c3aed';
    document.getElementById('bulk-apply-text').textContent = canApply > 0 ? `기획서 추가 (${canApply}개)` : '기획서 추가';

    const ganttBtn = document.getElementById('bulk-gantt-btn');
    ganttBtn.disabled = canGantt === 0;
    ganttBtn.style.opacity = canGantt === 0 ? '0.35' : '1';
    ganttBtn.style.cursor  = canGantt === 0 ? 'not-allowed' : 'pointer';
    document.getElementById('bulk-gantt-text').textContent = canGantt > 0 ? `Task 추가 (${canGantt}개)` : 'Task 추가';

    let canDelete = 0;
    checked.forEach(id => {
        const row = document.querySelector(`.req-row[data-id="${id}"]`);
        if (!row) return;
        if (row.dataset.ganttBlocked !== '1') canDelete++;
    });
    const deleteBtn = document.getElementById('bulk-delete-btn');
    deleteBtn.disabled = canDelete === 0;
    deleteBtn.style.opacity = canDelete === 0 ? '0.35' : '1';
    deleteBtn.style.cursor  = canDelete === 0 ? 'not-allowed' : 'pointer';
    document.getElementById('bulk-delete-text').textContent = canDelete > 0 ? `삭제 (${canDelete}개)` : '삭제';

    // 전체선택 체크 동기화
    const all = document.querySelectorAll('.req-chk');
    document.getElementById('select-all-chk').checked = all.length > 0 && all.length === checked.length;
}

async function toggleSelectAll(chk) {
    document.querySelectorAll('.req-chk').forEach(c => c.checked = chk.checked);
    updateBulkBar();
}

async function clearSelection() {
    document.querySelectorAll('.req-chk').forEach(c => c.checked = false);
    document.getElementById('select-all-chk').checked = false;
    updateBulkBar();
}

// ── 요구사항 단건 삭제 ────────────────────────────────────────
async function deleteOneReq(id, destroyUrl) {
    _deleteableIds = [String(id)];

    document.getElementById('del-info').innerHTML =
        '<strong style="color:#dc2626;">1개</strong>의 요구사항을 삭제합니다.';
    document.getElementById('del-skip-warn').style.display = 'none';
    document.getElementById('del-error').style.display     = 'none';
    document.getElementById('del-modal').style.display     = 'block';
    document.getElementById('del-overlay').style.display   = 'block';
}

// ── 요구사항 삭제 ─────────────────────────────────────────────
let _deleteableIds = [];

async function openDeleteConfirm() {
    const checked = getChecked();
    _deleteableIds = checked.filter(id => {
        const row = document.querySelector(`.req-row[data-id="${id}"]`);
        return row && row.dataset.ganttBlocked !== '1';
    });

    if (_deleteableIds.length === 0) return;

    const blockedIds = checked.filter(id => !_deleteableIds.includes(id));

    const infoEl = document.getElementById('del-info');
    infoEl.innerHTML = `<strong style="color:#dc2626;">${_deleteableIds.length}개</strong>의 요구사항을 삭제합니다.`;

    const warnEl = document.getElementById('del-skip-warn');
    if (blockedIds.length > 0) {
        warnEl.style.display = 'block';
        warnEl.textContent = `${blockedIds.length}개는 진행중·완료 일정 Task가 연결되어 있어 건너뜁니다.`;
    } else {
        warnEl.style.display = 'none';
    }

    document.getElementById('del-error').style.display = 'none';
    document.getElementById('del-modal').style.display = 'block';
    document.getElementById('del-overlay').style.display = 'block';
}

async function closeDeleteConfirm() {
    document.getElementById('del-modal').style.display = 'none';
    document.getElementById('del-overlay').style.display = 'none';
}

async function doDeleteRequirements() {
    if (_deleteableIds.length === 0) return;

    const btn = document.getElementById('del-confirm-btn');
    btn.disabled = true;
    btn.textContent = '삭제 중...';

    try {
        const res = await fetch(BULK_DESTROY_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify({ ids: _deleteableIds }),
        });
        const data = await res.json();

        if (!res.ok || !data.ok) {
            document.getElementById('del-error').style.display = 'block';
            document.getElementById('del-error').textContent = data.error || '삭제 중 오류가 발생했습니다.';
            btn.disabled = false;
            btn.textContent = '삭제 확인';
            return;
        }

        closeDeleteConfirm();

        if (data.skipped && data.skipped.length > 0) {
            alert(`${data.deleted}개 삭제 완료. ${data.skipped.length}개는 삭제되지 않았습니다.`);
        }

        // 삭제된 행 제거
        _deleteableIds.forEach(id => {
            const row = document.querySelector(`.req-row[data-id="${id}"]`);
            if (row) row.remove();
        });
        clearSelection();

    } catch {
        document.getElementById('del-error').style.display = 'block';
        document.getElementById('del-error').textContent = '네트워크 오류가 발생했습니다.';
        btn.disabled = false;
        btn.textContent = '삭제 확인';
    }
}

// ── 기획서 적용 모달 ──────────────────────────────────────────
let _planData = []; // { id, title, version, headings }
let _selectedReqIds = [];
let _reqTitles = {};

document.querySelectorAll('.req-row').forEach(row => {
    const id = row.dataset.id;
    const title = row.querySelector('a')?.textContent?.trim() || '#' + id;
    _reqTitles[id] = title;
});

async function openApplyModal(overrideIds = null) {
    if (overrideIds !== null) {
        _selectedReqIds = overrideIds;
    } else {
        // 일괄 적용: 아직 기획서에 반영 안 된 항목만
        _selectedReqIds = getChecked().filter(id => {
            const row = document.querySelector(`.req-row[data-id="${id}"]`);
            return row && row.dataset.applied !== '1';
        });
        if (_selectedReqIds.length === 0) {
            alert('선택한 요구사항은 이미 모두 기획서에 적용되어 있습니다.');
            return;
        }
    }
    if (_selectedReqIds.length === 0) return;

    // populate req list
    const ul = document.getElementById('apply-req-list');
    ul.innerHTML = _selectedReqIds.map(id =>
        `<li style="display:flex;align-items:center;gap:4px;"><span style="font-size:10px;color:#9ca3af;">#${id}</span> ${_reqTitles[id] || ''}</li>`
    ).join('');
    document.getElementById('apply-req-count').textContent = _selectedReqIds.length;

    document.getElementById('apply-error').style.display = 'none';
    document.getElementById('apply-skip-warn').style.display = 'none';
    document.getElementById('apply-preview').textContent = '선택 후 미리보기가 여기에 표시됩니다.';

    // load plans
    const planSel = document.getElementById('apply-plan-sel');
    planSel.innerHTML = '<option value="">불러오는 중...</option>';

    try {
        const res = await fetch(PLANS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        _planData = data.plans || [];
        planSel.innerHTML = '<option value="">기획서 선택...</option>' +
            _planData.map(p => `<option value="${p.id}">${p.title} (v${p.version})</option>`).join('');
    } catch {
        planSel.innerHTML = '<option value="">기획서를 불러올 수 없습니다</option>';
    }

    document.getElementById('apply-modal').style.display = 'block';
    document.getElementById('apply-overlay').style.display = 'block';
    loadPreview();
}

async function closeApplyModal() {
    document.getElementById('apply-modal').style.display = 'none';
    document.getElementById('apply-overlay').style.display = 'none';
}

async function onPlanChange(sel) {
    const planId = sel.value;
    const plan = _planData.find(p => String(p.id) === planId);
    const anchorSel = document.getElementById('apply-anchor-sel');
    anchorSel.innerHTML = '<option value="">섹션 선택...</option>' +
        (plan?.headings || []).map(h => `<option value="${h}">${h}</option>`).join('');
    loadPreview();
}

async function onPositionChange() {
    const pos = document.querySelector('input[name="apply-position"]:checked').value;
    document.getElementById('section-anchor-wrap').style.display = pos === 'after_section' ? 'block' : 'none';
    loadPreview();
}

async function loadPreview() {
    const preview = document.getElementById('apply-preview');
    if (_selectedReqIds.length === 0) return;
    preview.textContent = '불러오는 중...';
    try {
        const res = await fetch(PREVIEW_URL, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ requirement_ids: _selectedReqIds.map(Number) }),
        });
        const data = await res.json();
        preview.textContent = data.markdown || '';
    } catch {
        preview.textContent = '미리보기를 불러오지 못했습니다.';
    }
}

async function doApply() {
    const planId = document.getElementById('apply-plan-sel').value;
    if (!planId) { alert('기획서를 선택해주세요.'); return; }

    const position = document.querySelector('input[name="apply-position"]:checked').value;
    const anchor   = position === 'after_section'
        ? document.getElementById('apply-anchor-sel').value
        : null;

    const btn = document.getElementById('apply-btn');
    btn.disabled = true; btn.textContent = '적용 중...';
    document.getElementById('apply-error').style.display = 'none';

    try {
        const res = await fetch(`${APPLY_BASE}/${planId}/apply-requirements`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                requirement_ids: _selectedReqIds.map(Number),
                position,
                section_anchor: anchor,
            }),
        });
        const data = await res.json();

        if (!res.ok) {
            throw new Error(data.message || '적용에 실패했습니다.');
        }

        const skipped = data.skipped?.length || 0;
        const applied = data.applied?.length || 0;
        const failed  = data.failed?.length || 0;

        if (skipped > 0) {
            const warn = document.getElementById('apply-skip-warn');
            warn.textContent = `이미 적용된 요구사항 ${skipped}개는 건너뛰었습니다.`;
            warn.style.display = 'block';
        }

        if (failed > 0) {
            throw new Error(`${failed}개 항목 적용에 실패했습니다.`);
        }

        if (applied > 0) {
            closeApplyModal();
            clearSelection();
            window.location.href = `${APPLY_BASE}/${planId}?from_apply=1`;
        }
    } catch (e) {
        const errEl = document.getElementById('apply-error');
        errEl.textContent = e.message;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '적용';
    }
}

async function openReqModal() {
    document.getElementById('req-form').reset();
    document.getElementById('req-error').style.display = 'none';
    document.getElementById('req-file-list').innerHTML = '';
    document.getElementById('req-file-hint').textContent = '파일을 선택하세요';
    document.getElementById('req-modal').style.display = 'block';
    document.getElementById('req-overlay').style.display = 'block';
}

async function closeReqModal() {
    document.getElementById('req-modal').style.display = 'none';
    document.getElementById('req-overlay').style.display = 'none';
}

async function reqFileChanged(input) {
    const files = Array.from(input.files);
    const listEl = document.getElementById('req-file-list');
    const hintEl = document.getElementById('req-file-hint');
    listEl.innerHTML = '';
    if (!files.length) { hintEl.textContent = '파일을 선택하세요'; return; }
    hintEl.textContent = files.length + '개 파일 선택됨';
    files.forEach(f => {
        const size = f.size >= 1048576 ? (f.size/1048576).toFixed(1)+' MB' : f.size >= 1024 ? (f.size/1024).toFixed(1)+' KB' : f.size+' B';
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:6px;padding:4px 8px;background:#f8fafc;border:1px solid #e4e4e7;border-radius:6px;font-size:12px;';
        row.innerHTML = `<svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span style="flex:1;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${escHtmlReq(f.name)}</span><span style="color:#9ca3af;flex-shrink:0;">${size}</span>`;
        listEl.appendChild(row);
    });
}

function escHtmlReq(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── 파일 리뷰 + 웍스 분석 팝업 ──────────────────────────────────
const ANALYZE_URL     = '{{ route('projects.requirements.attachments.analyze', $project) }}';
const AI_CONTEXT_URL  = '{{ route('projects.requirements.ai-context', $project) }}';
let _rfmFormEl      = null;
let _rfmFiles       = [];   // File[] (local)
let _rfmBlobs       = [];
let _rfmIdx         = 0;
let _rfmAiReqs      = [];
let _rfmMode        = 'new';   // 'new' | 'review'
let _rfmServerFiles = [];      // [{filename, mime_type, download_url, ...}] for review mode
let _rfmReqId       = null;    // existing requirement id for review mode
let _rfmWorkbook    = null;    // SheetJS workbook for Excel
let _rfmSheetIdx    = 0;       // current sheet index
let _rfmZoom        = 1.0;     // Office 뷰어 확대 배율

async function _rfmResetPanel() {
    document.getElementById('rfm-results').innerHTML = '<div id="rfm-empty" style="color:#9ca3af;font-size:12px;text-align:center;padding:24px 0;line-height:1.6;">분석 버튼을 눌러<br>요구사항을 추출하세요</div>';
    document.getElementById('rfm-result-cnt').style.display  = 'none';
    document.getElementById('rfm-ai-loading').style.display  = 'none';
    document.getElementById('rfm-ai-error').style.display    = 'none';
    document.getElementById('rfm-select-bar').style.display  = 'none';
    document.getElementById('rfm-analyze-btn').disabled      = false;
    document.getElementById('rfm-analyze-btn').textContent   = '추가 요구기능 추천받기';
}

async function _rfmBuildTabs(names) {
    const tabsEl = document.getElementById('rfm-tabs');
    tabsEl.innerHTML = '';
    names.forEach((name, i) => {
        const btn = document.createElement('button');
        btn.textContent = name.length > 20 ? name.slice(0, 18) + '…' : name;
        btn.title = name;
        btn.style.cssText = 'padding:4px 10px;font-size:11px;font-weight:600;border:none;border-radius:5px;cursor:pointer;white-space:nowrap;transition:background .15s;';
        btn.onclick = () => rfmSwitchFile(i);
        tabsEl.appendChild(btn);
    });
}

async function _rfmSetTabActive(idx) {
    const tabs = document.getElementById('rfm-tabs').children;
    for (let i = 0; i < tabs.length; i++) {
        tabs[i].style.background = i === idx ? 'rgba(196,181,253,.25)' : 'rgba(255,255,255,.07)';
        tabs[i].style.color      = i === idx ? '#c4b5fd' : '#9ca3af';
    }
}

// ── new mode: 신규 등록 팝업 (미사용 - 폼에서 직접 호출 가능)
async function openFileReviewModal(formEl, files) {
    _rfmMode   = 'new';
    _rfmFormEl = formEl;
    _rfmFiles  = files;
    _rfmBlobs  = files.map(f => URL.createObjectURL(f));
    _rfmIdx    = 0;
    _rfmAiReqs = [];
    _rfmReqId  = null;
    _rfmServerFiles = [];

    _rfmResetPanel();
    _rfmBuildTabs(files.map(f => f.name));

    document.getElementById('rfm-apply-btn').textContent = '등록 완료';
    rfmSwitchFile(0);
    document.getElementById('rfm-overlay').style.display = 'flex';
}

// ── review mode: 기존 저장된 첨부파일 리뷰
async function openSavedAttachmentReviewModal(reqId, showUrl) {
    // 먼저 팝업 열고 로딩
    _rfmMode   = 'review';
    _rfmReqId  = reqId;
    _rfmFiles  = [];
    _rfmBlobs  = [];
    _rfmIdx    = 0;
    _rfmAiReqs = [];
    _rfmFormEl = null;
    _rfmServerFiles = [];

    _rfmResetPanel();
    document.getElementById('rfm-tabs').innerHTML = '';
    document.getElementById('rfm-apply-btn').textContent = '웍스 추천 요구사항 등록';
    document.getElementById('rfm-overlay').style.display = 'flex';

    // 로딩 상태
    document.getElementById('rfm-loading').style.display = 'flex';
    ['rfm-img','rfm-frame','rfm-text','rfm-unsupported'].forEach(id => {
        const el = document.getElementById(id); if (el) el.style.display = 'none';
    });

    try {
        const res  = await fetch(showUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        const atts = data.attachments || [];

        if (!atts.length) {
            document.getElementById('rfm-loading').style.display = 'none';
            document.getElementById('rfm-unsupported').style.display = 'flex';
            document.getElementById('rfm-unsupported').querySelector('span').textContent = '첨부파일이 없습니다.';
            return;
        }

        _rfmServerFiles = atts;
        _rfmFiles  = new Array(atts.length).fill(null);
        _rfmBlobs  = new Array(atts.length).fill(null);

        _rfmBuildTabs(atts.map(a => a.filename));
        await rfmSwitchFile(0);
    } catch(e) {
        document.getElementById('rfm-loading').style.display = 'none';
        document.getElementById('rfm-unsupported').style.display = 'flex';
    }
}

async function rfmSwitchFile(idx) {
    _rfmIdx = idx;
    _rfmSetTabActive(idx);

    if (_rfmMode === 'review') {
        // 서버 파일: 아직 blob이 없으면 다운로드
        if (!_rfmBlobs[idx]) {
            document.getElementById('rfm-loading').style.display = 'flex';
            ['rfm-img','rfm-frame','rfm-text','rfm-unsupported'].forEach(id => {
                const el = document.getElementById(id); if (el) el.style.display = 'none';
            });
            try {
                const att  = _rfmServerFiles[idx];
                const resp = await fetch(att.download_url, { headers: { 'X-CSRF-TOKEN': CSRF } });
                const blob = await resp.blob();
                const blobUrl = URL.createObjectURL(blob);
                _rfmBlobs[idx] = blobUrl;
                _rfmFiles[idx] = new File([blob], att.filename, { type: blob.type || att.mime_type || '' });
            } catch {
                document.getElementById('rfm-loading').style.display = 'none';
                document.getElementById('rfm-unsupported').style.display = 'flex';
                return;
            }
        }
        rfmRenderFile(_rfmFiles[idx], _rfmBlobs[idx]);
    } else {
        rfmRenderFile(_rfmFiles[idx], _rfmBlobs[idx]);
    }
}

async function rfmRenderFile(file, blobUrl) {
    const loading  = document.getElementById('rfm-loading');
    const img      = document.getElementById('rfm-img');
    const frame    = document.getElementById('rfm-frame');
    const text     = document.getElementById('rfm-text');
    const excelEl  = document.getElementById('rfm-excel');
    const offNav   = document.getElementById('rfm-office-nav');
    const unsupp   = document.getElementById('rfm-unsupported');

    [img, frame, text, excelEl, offNav, unsupp].forEach(el => { if (el) el.style.display = 'none'; });
    loading.style.display = 'flex';
    _rfmWorkbook = null; _rfmSheetIdx = 0; _rfmZoom = 1.0;
    const zoomLbl = document.getElementById('rfm-zoom-label');
    if (zoomLbl) zoomLbl.textContent = '100%';

    const mime = (file.type || '').toLowerCase();
    const name = (file.name || '').toLowerCase();
    const ext  = name.includes('.') ? name.split('.').pop() : '';

    const isImage = mime.startsWith('image/') || ['jpg','jpeg','png','gif','webp','bmp','svg'].includes(ext);
    const isPdf   = mime === 'application/pdf' || ext === 'pdf';
    const isText  = mime.startsWith('text/') || ['txt','md','csv','json','xml','html','htm','js','ts','php','py','java','css','log'].includes(ext);
    const isExcel = ['xlsx','xls'].includes(ext);
    const isDocx  = ext === 'docx';
    const isPpt   = ['pptx','ppt'].includes(ext);

    if (isImage) {
        img.src = blobUrl;
        img.onload  = () => { loading.style.display = 'none'; img.style.display = 'block'; };
        img.onerror = () => { loading.style.display = 'none'; unsupp.style.display = 'flex'; };
    } else if (isPdf) {
        frame.src = blobUrl;
        frame.style.display = 'block';
        frame.onload = () => { loading.style.display = 'none'; };
    } else if (isText) {
        const reader = new FileReader();
        reader.onload = e => {
            text.textContent = e.target.result;
            loading.style.display = 'none';
            text.style.display = 'block';
        };
        reader.readAsText(file);
    } else if (isExcel) {
        if (typeof XLSX === 'undefined') {
            loading.style.display = 'none';
            unsupp.style.display  = 'flex';
            unsupp.querySelector('span').textContent = 'SheetJS 라이브러리 로딩 중 오류가 발생했습니다.';
            return;
        }
        const reader = new FileReader();
        reader.onload = e => {
            try {
                if (!e.target.result || e.target.result.byteLength === 0) throw new Error('Empty file');
                const data = new Uint8Array(e.target.result);
                _rfmWorkbook  = XLSX.read(data, { type: 'array' });
                _rfmSheetIdx  = 0;
                rfmRenderExcelSheet();
            } catch(err) {
                loading.style.display = 'none';
                unsupp.style.display  = 'flex';
                unsupp.querySelector('span').textContent = '엑셀 파일을 읽는 중 오류가 발생했습니다.';
            }
        };
        reader.readAsArrayBuffer(file);
    } else if (isDocx) {
        if (typeof mammoth === 'undefined') {
            loading.style.display = 'none';
            unsupp.style.display  = 'flex';
            unsupp.querySelector('span').textContent = 'Mammoth 라이브러리 로딩 중 오류가 발생했습니다.';
            return;
        }
        const reader = new FileReader();
        reader.onload = async e => {
            try {
                const result = await mammoth.convertToHtml({ arrayBuffer: e.target.result });
                excelEl.innerHTML = `<style>
                    #rfm-excel h1,#rfm-excel h2,#rfm-excel h3{font-weight:700;margin:.8em 0 .4em;}
                    #rfm-excel h1{font-size:20px;}#rfm-excel h2{font-size:17px;}#rfm-excel h3{font-size:15px;}
                    #rfm-excel p{margin:0 0 .7em;line-height:1.7;}
                    #rfm-excel table{border-collapse:collapse;width:100%;margin:.8em 0;}
                    #rfm-excel td,#rfm-excel th{border:1px solid #d1d5db;padding:5px 10px;font-size:13px;}
                    #rfm-excel th{background:#f3f4f6;font-weight:600;}
                    #rfm-excel ul,#rfm-excel ol{padding-left:1.5em;margin:0 0 .7em;}
                    #rfm-excel li{margin:.2em 0;}
                    </style>
                    <div style="padding:28px 36px;max-width:860px;margin:0 auto;font-family:'Segoe UI',sans-serif;font-size:14px;color:#1f2937;line-height:1.7;">
                        ${result.value || '<p style="color:#6b7280;">문서 내용이 없습니다.</p>'}
                    </div>`;
                excelEl.style.bottom = '44px';
                excelEl.style.display = 'block';
                excelEl.style.zoom   = _rfmZoom;
                // 페이징 없이 확대/축소만 표시
                const pagingEl2 = document.getElementById('rfm-paging-controls');
                if (pagingEl2) pagingEl2.style.visibility = 'hidden';
                document.getElementById('rfm-office-page-info').textContent = '';
                document.getElementById('rfm-office-nav').style.display = 'flex';
                loading.style.display = 'none';
            } catch(err) {
                loading.style.display = 'none';
                unsupp.style.display  = 'flex';
                unsupp.querySelector('span').textContent = 'Word 파일을 읽는 중 오류가 발생했습니다.';
            }
        };
        reader.readAsArrayBuffer(file);
    } else if (isPpt) {
        loading.style.display = 'none';
        unsupp.style.display  = 'flex';
        unsupp.querySelector('span').textContent = 'PowerPoint 미리보기는 지원하지 않습니다. 다운로드 후 확인해주세요.';
        const dlLink = document.getElementById('rfm-unsupported-dl');
        if (dlLink && blobUrl) { dlLink.href = blobUrl; dlLink.download = file.name; dlLink.style.display = 'inline-block'; }
    } else {
        loading.style.display = 'none';
        unsupp.style.display  = 'flex';
        unsupp.querySelector('span').textContent = `미리보기 미지원 형식 (.${ext||'?'}) — 다운로드 후 확인해주세요`;
        const dlLink = document.getElementById('rfm-unsupported-dl');
        if (dlLink && blobUrl) { dlLink.href = blobUrl; dlLink.download = file.name; dlLink.style.display = 'inline-block'; }
    }
}

async function rfmRenderExcelSheet() {
    if (!_rfmWorkbook) return;
    const loading  = document.getElementById('rfm-loading');
    const excelEl  = document.getElementById('rfm-excel');
    const offNav   = document.getElementById('rfm-office-nav');
    const pageInfo = document.getElementById('rfm-office-page-info');

    const sheetNames = _rfmWorkbook.SheetNames;
    const total      = sheetNames.length;
    const sheetName  = sheetNames[_rfmSheetIdx] || '';
    const sheet      = _rfmWorkbook.Sheets[sheetName];
    let htmlTable = '<p style="color:#6b7280;padding:16px;">빈 시트입니다.</p>';
    if (sheet && sheet['!ref']) {
        try { htmlTable = XLSX.utils.sheet_to_html(sheet); }
        catch(e) { htmlTable = '<p style="color:#dc2626;padding:16px;">시트를 표시하는 중 오류가 발생했습니다.</p>'; }
    }

    excelEl.innerHTML = `<style>
        #rfm-excel table{border-collapse:collapse;font-size:12px;font-family:'Segoe UI',sans-serif;white-space:nowrap;}
        #rfm-excel td,#rfm-excel th{border:1px solid #e5e7eb;padding:4px 10px;color:#1f2937;}
        #rfm-excel tr:nth-child(even) td{background:#f9fafb;}
        #rfm-excel tr:first-child td,#rfm-excel tr:first-child th{background:#e8edf5;font-weight:700;}
        </style>
        <div id="rfm-excel-inner" style="zoom:1;display:inline-block;min-width:100%;">
            <div style="padding:10px 14px 6px;display:flex;align-items:center;gap:8px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/></svg>
                <span style="font-size:12px;font-weight:700;color:#374151;">${escHtmlReq(sheetName)}</span>
                ${total > 1 ? `<span style="font-size:11px;color:#9ca3af;">(${_rfmSheetIdx+1} / ${total})</span>` : ''}
            </div>
            <div style="padding:8px 12px 16px;">${htmlTable}</div>
        </div>`;

    excelEl.style.bottom  = '44px';
    excelEl.style.zoom    = '';       // outer 스크롤 컨테이너는 zoom 없음
    excelEl.style.display = 'block';

    // 페이징 영역: 시트 여러 개일 때만 표시
    const pagingEl = document.getElementById('rfm-paging-controls');
    if (pagingEl) pagingEl.style.visibility = total > 1 ? 'visible' : 'hidden';
    if (total > 1) pageInfo.textContent = `${escHtmlReq(sheetName)}  (${_rfmSheetIdx + 1} / ${total})`;
    else           pageInfo.textContent  = '';
    offNav.style.display = 'flex';

    // 시트 전환 시마다 뷰어 너비에 맞게 자동 맞춤
    requestAnimationFrame(() => {
        const inner  = document.getElementById('rfm-excel-inner');
        const table  = inner ? inner.querySelector('table') : null;
        if (inner && table) {
            inner.style.zoom = 1;  // 자연 크기로 측정
            const availW = excelEl.clientWidth - 2;
            const tableW = table.offsetWidth + 26;  // padding 포함
            _rfmZoom = tableW > availW
                ? Math.max(0.3, Math.round((availW / tableW) * 100) / 100)
                : 1.0;
            inner.style.zoom = _rfmZoom;
        } else {
            _rfmZoom = 1.0;
        }
        const lbl = document.getElementById('rfm-zoom-label');
        if (lbl) lbl.textContent = Math.round(_rfmZoom * 100) + '%';
        loading.style.display = 'none';
    });
}

async function rfmOfficeNav(dir) {
    if (!_rfmWorkbook) return;
    const total   = _rfmWorkbook.SheetNames.length;
    _rfmSheetIdx  = Math.max(0, Math.min(total - 1, _rfmSheetIdx + dir));
    try { rfmRenderExcelSheet(); } catch(e) { console.error('Sheet render error:', e); }
}

async function rfmZoom(delta) {
    _rfmZoom = Math.round(Math.max(0.3, Math.min(3.0, _rfmZoom + delta)) * 100) / 100;
    const inner = document.getElementById('rfm-excel-inner');
    if (inner) inner.style.zoom = _rfmZoom;
    else {
        const excelEl = document.getElementById('rfm-excel');
        if (excelEl && excelEl.style.display !== 'none') excelEl.style.zoom = _rfmZoom;
    }
    document.getElementById('rfm-zoom-label').textContent = Math.round(_rfmZoom * 100) + '%';
}

async function rfmZoomReset() {
    _rfmZoom = 1.0;
    const inner = document.getElementById('rfm-excel-inner');
    if (inner) inner.style.zoom = 1;
    else {
        const excelEl = document.getElementById('rfm-excel');
        if (excelEl && excelEl.style.display !== 'none') excelEl.style.zoom = 1;
    }
    document.getElementById('rfm-zoom-label').textContent = '100%';
}

// ── Excel / Word 뷰어 드래그 팬 ──────────────────────────────
(async function () {
    let dragging = false, startX, startY, scrollLeft, scrollTop;
    const wrap = () => document.getElementById('rfm-excel');

    document.addEventListener('mousedown', e => {
        const w = wrap();
        if (!w || w.style.display === 'none' || !w.contains(e.target)) return;
        if (e.button !== 0) return;
        dragging   = true;
        startX     = e.pageX - w.offsetLeft;
        startY     = e.pageY - w.offsetTop;
        scrollLeft = w.scrollLeft;
        scrollTop  = w.scrollTop;
        w.style.cursor = 'grabbing';
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        if (!dragging) return;
        const w = wrap();
        if (!w) return;
        w.scrollLeft = scrollLeft - (e.pageX - w.offsetLeft - startX);
        w.scrollTop  = scrollTop  - (e.pageY - w.offsetTop  - startY);
    });

    document.addEventListener('mouseup', () => {
        if (!dragging) return;
        dragging = false;
        const w = wrap();
        if (w) w.style.cursor = 'grab';
    });

    document.addEventListener('mouseleave', () => {
        if (!dragging) return;
        dragging = false;
        const w = wrap();
        if (w) w.style.cursor = 'grab';
    });
}());

async function rfmAnalyze() {
    const file = _rfmFiles[_rfmIdx];
    if (!file) { document.getElementById('rfm-ai-error').textContent = '먼저 파일 탭을 선택하세요.'; document.getElementById('rfm-ai-error').style.display = 'block'; return; }

    const btn       = document.getElementById('rfm-analyze-btn');
    const loadEl    = document.getElementById('rfm-ai-loading');
    const errEl     = document.getElementById('rfm-ai-error');
    const resultsEl = document.getElementById('rfm-results');

    btn.disabled = true; btn.textContent = '분석 중...';
    loadEl.style.display = 'flex';
    errEl.style.display  = 'none';
    resultsEl.innerHTML  = '';

    // 바이너리 파일은 웍스가 읽을 수 있는 텍스트로 미리 변환
    let analysisFile = file;
    const ext = (file.name || '').split('.').pop().toLowerCase();

    try {
        if (['xlsx', 'xls'].includes(ext) && typeof XLSX !== 'undefined') {
            // 워크북이 아직 파싱되지 않은 경우 재파싱
            let wb = _rfmWorkbook;
            if (!wb) {
                const ab = await file.arrayBuffer();
                if (!ab || ab.byteLength === 0) throw new Error('Empty file');
                wb = XLSX.read(new Uint8Array(ab), { type: 'array' });
            }
            // 전체 시트를 CSV로 변환 (시트명 헤더 포함)
            const parts = wb.SheetNames.map(sn => {
                const csv = XLSX.utils.sheet_to_csv(wb.Sheets[sn]);
                return `=== 시트: ${sn} ===\n${csv}`;
            });
            const txtName = file.name.replace(/\.[^.]+$/, '.txt');
            analysisFile  = new File([parts.join('\n\n')], txtName, { type: 'text/plain' });

        } else if (ext === 'docx' && typeof mammoth !== 'undefined') {
            const ab     = await file.arrayBuffer();
            const result = await mammoth.extractRawText({ arrayBuffer: ab });
            const txtName = file.name.replace(/\.[^.]+$/, '.txt');
            analysisFile  = new File([result.value || ''], txtName, { type: 'text/plain' });
        }
    } catch (convErr) {
        // 변환 실패 시 원본 파일 그대로 전송
        analysisFile = file;
    }

    // 기획서 + 기존 요구사항 컨텍스트 병렬 로드
    let contextJson = '{}';
    try {
        const ctxRes = await fetch(AI_CONTEXT_URL, { headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' } });
        if (ctxRes.ok) {
            const ctxData = await ctxRes.json();
            contextJson = JSON.stringify({
                requirements: ctxData.requirements || [],
                plans: (ctxData.plans || []).map(p => ({
                    title:   p.title,
                    content: (p.content || '').substring(0, 4000),
                })),
            });
        }
    } catch (_) { /* 컨텍스트 fetch 실패 시 빈 컨텍스트로 진행 */ }

    const fd = new FormData();
    fd.append('file',    analysisFile);
    fd.append('context', contextJson);

    try {
        const res  = await fetch(ANALYZE_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || '분석 실패');
        _rfmAiReqs = data.requirements || [];
        rfmRenderResults(_rfmAiReqs);
    } catch(e) {
        errEl.textContent = e.message || '오류가 발생했습니다.';
        errEl.style.display = 'block';
        resultsEl.innerHTML = '<div style="color:#9ca3af;font-size:12px;text-align:center;padding:16px 0;">분석 결과가 없습니다.</div>';
    } finally {
        loadEl.style.display = 'none';
        btn.disabled = false; btn.textContent = '추가 요구기능 추천받기';
    }
}

async function rfmRenderResults(reqs) {
    const el    = document.getElementById('rfm-results');
    const cntEl = document.getElementById('rfm-result-cnt');
    const bar   = document.getElementById('rfm-select-bar');

    if (!reqs.length) {
        el.innerHTML = '<div style="color:#9ca3af;font-size:12px;text-align:center;padding:16px 0;">추출된 요구사항이 없습니다.</div>';
        cntEl.style.display = 'none'; bar.style.display = 'none'; return;
    }

    cntEl.textContent = reqs.length + '개';
    cntEl.style.display = 'inline-block';
    bar.style.display = 'flex';

    const PRIORITY_LABELS = { high:'높음', medium:'보통', low:'낮음' };
    const PRIORITY_COLORS = { high:'#fee2e2;color:#991b1b', medium:'#fef9c3;color:#713f12', low:'#dbeafe;color:#1e40af' };
    const CATEGORY_LABELS = { functional:'기능', non_functional:'비기능', ui:'UI/UX', data:'데이터', security:'보안' };

    el.innerHTML = reqs.map((r, i) => `
        <label style="display:flex;align-items:flex-start;gap:8px;padding:9px 10px;background:#f9fafb;border:1px solid #e4e4e7;border-radius:8px;cursor:pointer;transition:background .1s;" onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='#f9fafb'">
            <input type="checkbox" class="rfm-chk" data-idx="${i}" checked style="margin-top:2px;flex-shrink:0;" onchange="rfmUpdateSelCount()">
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:600;color:#1f2937;line-height:1.4;margin-bottom:4px;">${escHtmlReq(r.title||'')}</div>
                <div style="font-size:11px;color:#6b7280;line-height:1.5;margin-bottom:5px;">${escHtmlReq((r.description||'').slice(0,80))}${(r.description||'').length>80?'…':''}</div>
                <div style="display:flex;gap:4px;flex-wrap:wrap;">
                    <span style="font-size:10px;padding:1px 6px;border-radius:3px;font-weight:600;background:${PRIORITY_COLORS[r.priority]||'#f3f4f6;color:#6b7280'};">${PRIORITY_LABELS[r.priority]||r.priority}</span>
                    <span style="font-size:10px;padding:1px 6px;border-radius:3px;background:#ede9fe;color:#6d28d9;font-weight:600;">${CATEGORY_LABELS[r.category]||r.category}</span>
                </div>
            </div>
        </label>`).join('');

    rfmUpdateSelCount();
}

async function rfmToggleAll(checked) {
    document.querySelectorAll('.rfm-chk').forEach(c => { c.checked = checked; });
    rfmUpdateSelCount();
}

async function rfmUpdateSelCount() {
    const total    = document.querySelectorAll('.rfm-chk').length;
    const selected = document.querySelectorAll('.rfm-chk:checked').length;
    document.getElementById('rfm-sel-cnt').textContent = `${selected}/${total} 선택`;
    document.getElementById('rfm-select-all').checked  = selected === total && total > 0;
}

async function rfmApplyAndSubmit() {
    const applyBtn  = document.getElementById('rfm-apply-btn');
    const errEl     = document.getElementById('rfm-ai-error');
    const checked   = [...document.querySelectorAll('.rfm-chk:checked')];

    applyBtn.disabled = true;
    applyBtn.textContent = '등록 중...';

    let sourceReqId = _rfmReqId;

    if (_rfmMode === 'new') {
        // 신규 요구사항 폼 먼저 제출
        try {
            const res  = await fetch(STORE_URL, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: new FormData(_rfmFormEl) });
            const data = await res.json();
            if (!res.ok) {
                const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message||'저장 실패');
                errEl.textContent = msgs; errEl.style.display = 'block';
                applyBtn.disabled = false; applyBtn.textContent = '등록 완료'; return;
            }
            sourceReqId = data.id;
        } catch { applyBtn.disabled = false; applyBtn.textContent = '등록 완료'; return; }
    }

    // 웍스 추출 요구사항 등록
    if (checked.length) {
        const sourceRef = sourceReqId ? `요구사항 #${sourceReqId}의 첨부파일 웍스 분석` : '첨부파일 웍스 분석';
        for (const chk of checked) {
            const req = _rfmAiReqs[parseInt(chk.dataset.idx)];
            if (!req) continue;
            const fd = new FormData();
            fd.append('title',       req.title       || '');
            fd.append('description', req.description || '');
            fd.append('priority',    req.priority    || 'medium');
            fd.append('category',    req.category    || 'functional');
            fd.append('source_type', 'attachment_ai');
            fd.append('source_ref',  sourceRef);
            await fetch(STORE_URL, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: fd });
        }
    }

    closeFileReviewModal();
    location.reload();
}

async function closeFileReviewModal() {
    document.getElementById('rfm-overlay').style.display = 'none';
    document.getElementById('rfm-frame').src = '';
    _rfmBlobs.filter(Boolean).forEach(u => URL.revokeObjectURL(u));
    _rfmBlobs = []; _rfmFiles = []; _rfmFormEl = null; _rfmAiReqs = [];
    _rfmMode = 'new'; _rfmReqId = null; _rfmServerFiles = [];
    _rfmWorkbook = null; _rfmSheetIdx = 0; _rfmZoom = 1.0;
    const excelEl = document.getElementById('rfm-excel');
    if (excelEl) { excelEl.innerHTML = ''; excelEl.style.display = 'none'; excelEl.style.zoom = ''; }
    const offNav = document.getElementById('rfm-office-nav');
    if (offNav) offNav.style.display = 'none';
    const zoomLbl2 = document.getElementById('rfm-zoom-label');
    if (zoomLbl2) zoomLbl2.textContent = '100%';
    const dlLink = document.getElementById('rfm-unsupported-dl');
    if (dlLink) { dlLink.style.display = 'none'; dlLink.href = '#'; }
}

document.getElementById('req-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn   = document.getElementById('req-submit');
    const errEl = document.getElementById('req-error');
    btn.disabled = true; btn.textContent = '저장 중...';
    errEl.style.display = 'none';
    try {
        const res = await fetch(STORE_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    new FormData(this),
        });
        if (res.ok) { closeReqModal(); location.reload(); }
        else {
            const data = await res.json().catch(() => ({}));
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || '저장에 실패했습니다.');
            errEl.textContent = msgs; errEl.style.display = 'block';
        }
    } catch {
        errEl.textContent = '네트워크 오류가 발생했습니다.'; errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '등록';
    }
});

// ── 웍스 분석 모달 (멀티스텝) ───────────────────────────────────
let _aiApproveUrl  = null;
let _aiRejectUrl   = null;
let _aiSuccessReqIds = [];
let _aiPlanData2   = [];

const AI_STEP_TITLES = {
    upload:  '웍스 요구사항 분석',
    loading: '웍스 분석 중...',
    review:  '분석 결과 검토',
    success: '요구사항 등록 완료',
    failed:  '분석 실패',
};

async function showAiStep(name) {
    ['upload','loading','review','success','failed'].forEach(s => {
        document.getElementById('ai-step-' + s).style.display = (s === name) ? '' : 'none';
    });
    document.getElementById('ai-modal-title').textContent = AI_STEP_TITLES[name] || '웍스 분석';
    document.getElementById('ai-modal').style.width = (name === 'review') ? '740px' : '560px';
}

async function openAiModal() {
    document.getElementById('ai-form').reset();
    document.getElementById('ai-file-list').innerHTML = '';
    document.getElementById('ai-error').style.display = 'none';
    showAiStep('upload');
    document.getElementById('ai-modal').style.display = 'block';
    document.getElementById('ai-overlay').style.display = 'block';
}

async function closeAiModal() {
    document.getElementById('ai-modal').style.display = 'none';
    document.getElementById('ai-overlay').style.display = 'none';
}

async function aiUpdateFileList(input) {
    const ul = document.getElementById('ai-file-list');
    ul.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const li = document.createElement('li');
        li.style.cssText = 'display:flex;align-items:center;gap:5px;font-size:12px;color:#374151;padding:2px 0;';
        li.innerHTML = `<svg style="width:14px;height:14px;color:#9ca3af;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>${f.name}</span><span style="color:#9ca3af;">(${(f.size/1024).toFixed(0)} KB)</span>`;
        ul.appendChild(li);
    });
}

// 드래그 앤 드롭
const aiDrop = document.getElementById('ai-drop-zone');
if (aiDrop) {
    aiDrop.addEventListener('dragover', e => { e.preventDefault(); aiDrop.style.borderColor='#7c3aed'; aiDrop.style.background='#faf5ff'; });
    aiDrop.addEventListener('dragleave', () => { aiDrop.style.borderColor='#d1d5db'; aiDrop.style.background=''; });
    aiDrop.addEventListener('drop', e => {
        e.preventDefault();
        aiDrop.style.borderColor='#d1d5db'; aiDrop.style.background='';
        const inp = document.getElementById('ai-file-input');
        const dt  = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        inp.files = dt.files;
        aiUpdateFileList(inp);
    });
}

// Step 1 → 분석 요청
document.getElementById('ai-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    document.getElementById('ai-error').style.display = 'none';
    const btn = document.getElementById('ai-submit');
    btn.disabled = true; btn.textContent = '분석 중...';
    showAiStep('loading');

    try {
        const res  = await fetch(AI_STORE_URL, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    new FormData(this),
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            showAiStep('upload');
            const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || '오류가 발생했습니다.');
            document.getElementById('ai-error').textContent = msg;
            document.getElementById('ai-error').style.display = 'block';
            return;
        }

        _aiApproveUrl = data.approve_url;
        _aiRejectUrl  = data.reject_url;

        if (data.status === 'review') {
            aiRenderReview(data);
            showAiStep('review');
        } else if (data.status === 'failed') {
            document.getElementById('ai-failed-msg').textContent = data.error_message || '알 수 없는 오류';
            showAiStep('failed');
        } else {
            document.getElementById('ai-failed-msg').textContent = `예상치 못한 상태: ${data.status}`;
            showAiStep('failed');
        }
    } catch {
        showAiStep('upload');
        document.getElementById('ai-error').textContent = '네트워크 오류가 발생했습니다.';
        document.getElementById('ai-error').style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '분석 시작';
    }
});

// Step 3: 후보 렌더링
async function aiEsc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function aiRenderReview(data) {
    const catLabels = {functional:'기능',non_functional:'비기능',constraint:'제약',ui_ux:'UI/UX',
                       integration:'연동',performance:'성능',security:'보안',other:'기타'};
    const priStyle  = {
        critical: 'background:#fef2f2;color:#dc2626;',
        high:     'background:#fff7ed;color:#ea580c;',
        medium:   'background:#fefce8;color:#ca8a04;',
        low:      'background:#f0fdf4;color:#16a34a;',
    };

    const summaryEl = document.getElementById('ai-review-summary');
    summaryEl.innerHTML = data.summary
        ? `<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
               <p style="font-size:11px;font-weight:600;color:#1d4ed8;margin:0 0 3px;">웍스 요약</p>
               <p style="font-size:12px;color:#374151;margin:0;">${aiEsc(data.summary)}</p>
           </div>` : '';

    const warningsEl = document.getElementById('ai-review-warnings');
    warningsEl.innerHTML = (data.warnings && data.warnings.length)
        ? `<div style="background:#fefce8;border:1px solid #fef08a;border-radius:8px;padding:10px 12px;margin-bottom:12px;">
               <p style="font-size:11px;font-weight:600;color:#713f12;margin:0 0 4px;">경고</p>
               <ul style="margin:0;padding:0 0 0 16px;font-size:12px;color:#374151;">${data.warnings.map(w=>`<li>${aiEsc(w)}</li>`).join('')}</ul>
           </div>` : '';

    const candidates = (data.candidates || []).slice(0, 30);
    document.getElementById('ai-review-count').textContent = `추출된 요구사항 후보 ${candidates.length}개`;
    document.getElementById('ai-select-all').checked = true;

    document.getElementById('ai-review-candidates').innerHTML = candidates.map((c, idx) => {
        const ps   = priStyle[c.priority] || 'background:#f3f4f6;color:#374151;';
        const conf = Math.round((c.confidence || 0.8) * 100);
        const tags = (c.tags||[]).map(t=>`<span style="padding:1px 6px;background:#f3f4f6;border-radius:4px;font-size:11px;color:#6b7280;">#${aiEsc(t)}</span>`).join('');
        return `<label style="display:block;border:1.5px solid #e5e7eb;border-radius:10px;padding:12px 14px;cursor:pointer;transition:border-color .12s;"
                      onmouseover="this.style.borderColor='#a78bfa'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='#e5e7eb'">
            <div style="display:flex;align-items:flex-start;gap:10px;">
                <input type="checkbox" class="ai-cand-chk" data-idx="${idx}" checked style="margin-top:3px;cursor:pointer;flex-shrink:0;" onchange="aiSyncSelectAll()">
                <div style="flex:1;min-width:0;">
                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:5px;">
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;font-weight:600;${ps}">${c.priority||'medium'}</span>
                        <span style="padding:2px 8px;border-radius:4px;font-size:11px;background:#f3f4f6;color:#6b7280;">${catLabels[c.category]||'기타'}</span>
                        <span style="font-size:11px;color:#9ca3af;">신뢰도 ${conf}%</span>
                        ${c.source_ref ? `<span style="font-size:11px;color:#9ca3af;">출처: ${aiEsc(c.source_ref)}</span>` : ''}
                    </div>
                    <p style="font-size:13px;font-weight:600;color:#18181b;margin:0 0 3px;">${aiEsc(c.title)}</p>
                    ${c.description ? `<p style="font-size:12px;color:#6b7280;margin:0 0 4px;line-height:1.5;">${aiEsc(c.description)}</p>` : ''}
                    ${tags ? `<div style="display:flex;flex-wrap:wrap;gap:4px;margin-top:4px;">${tags}</div>` : ''}
                </div>
            </div>
        </label>`;
    }).join('');
}

async function aiToggleAll(chk) {
    document.querySelectorAll('.ai-cand-chk').forEach(c => c.checked = chk.checked);
}

async function aiSyncSelectAll() {
    const all     = document.querySelectorAll('.ai-cand-chk');
    const checked = document.querySelectorAll('.ai-cand-chk:checked');
    document.getElementById('ai-select-all').checked = (all.length === checked.length);
}

// Step 3 → 등록
async function aiApprove() {
    const selected = [...document.querySelectorAll('.ai-cand-chk:checked')].map(c => Number(c.dataset.idx));
    if (!selected.length) { alert('등록할 요구사항을 선택해주세요.'); return; }

    const btn   = document.getElementById('ai-approve-btn');
    const errEl = document.getElementById('ai-review-error');
    btn.disabled = true; btn.textContent = '등록 중...';
    errEl.style.display = 'none';

    try {
        const res  = await fetch(_aiApproveUrl, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ selected }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || '등록 실패');

        _aiSuccessReqIds = data.requirement_ids || [];
        document.getElementById('ai-success-msg').textContent = `${data.created}개 요구사항이 등록되었습니다.`;
        await aiLoadSuccessPlans();
        showAiStep('success');
    } catch(e) {
        errEl.textContent = e.message;
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = '선택한 요구사항 등록';
    }
}

// Step 3 → 거부
async function aiReject() {
    if (!await __confirm('이 분석 결과를 거부하시겠습니까?')) return;
    const btn = document.getElementById('ai-reject-btn');
    btn.disabled = true;
    try {
        await fetch(_aiRejectUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: '{}',
        });
        closeAiModal();
    } finally {
        btn.disabled = false;
    }
}

// Step 4: 기획서 목록 로드
async function aiLoadSuccessPlans() {
    const sel = document.getElementById('ai-success-plan-sel');
    sel.innerHTML = '<option value="">불러오는 중...</option>';
    try {
        const res  = await fetch(PLANS_URL, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        _aiPlanData2 = data.plans || [];
        sel.innerHTML = '<option value="">기획서 선택...</option>' +
            _aiPlanData2.map(p => `<option value="${p.id}">${p.title} (v${p.version})</option>`).join('');
    } catch {
        sel.innerHTML = '<option value="">불러올 수 없습니다</option>';
    }
}

// Step 4 → 기획서 적용
async function aiApplyToPlan() {
    const planId = document.getElementById('ai-success-plan-sel').value;
    if (!planId) { alert('기획서를 선택해주세요.'); return; }

    const btn   = document.getElementById('ai-success-apply-btn');
    const errEl = document.getElementById('ai-success-plan-error');
    btn.disabled = true; btn.textContent = '적용 중...';
    errEl.style.display = 'none';

    try {
        const res  = await fetch(`${APPLY_BASE}/${planId}/apply-requirements`, {
            method:  'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ requirement_ids: _aiSuccessReqIds.map(Number), position: 'end' }),
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(data.message || '적용 실패');

        closeAiModal();
        window.location.href = `${APPLY_BASE}/${planId}`;
    } catch(e) {
        errEl.textContent = e.message;
        errEl.style.display = 'block';
        btn.disabled = false; btn.textContent = '적용';
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeReqDetail();
        closeReqModal();
        closeAiModal();
    }
});

// ── 요구사항 상세 팝업 ─────────────────────────────────────────
let _rd = null; // current requirement data

async function rdEsc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

async function openReqDetail(reqId, showUrl) {
    document.getElementById('rd-modal').style.display = 'flex';
    document.getElementById('rd-overlay').style.display = 'block';

    // 로딩 표시
    document.getElementById('rd-title').textContent = '불러오는 중...';
    document.getElementById('rd-badges').innerHTML = '';
    document.getElementById('rd-desc-view').textContent = '';

    try {
        const res  = await fetch(showUrl, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const data = await res.json();
        _rd = data;
        rdRender(data);
    } catch {
        document.getElementById('rd-title').textContent = '불러오기 실패';
    }
}

async function closeReqDetail() {
    document.getElementById('rd-modal').style.display = 'none';
    document.getElementById('rd-overlay').style.display = 'none';
    _rd = null;
}

async function rdRender(data) {
    const req = data.requirement;

    // 헤더
    document.getElementById('rd-title').textContent = req.title;
    document.getElementById('rd-badges').innerHTML =
        `<span style="padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;background:${req.priority_color.bg};color:${req.priority_color.text};">${rdEsc(req.priority_label)}</span>
         <span style="padding:2px 8px;border-radius:5px;font-size:11px;background:${req.status_color.bg};color:${req.status_color.text};">${rdEsc(req.status_label)}</span>
         <span style="font-size:11px;color:#9ca3af;">${rdEsc(req.category_label)}</span>
         ${req.source_type === 'ai_analyzed'    ? `<span style="padding:1px 7px;border-radius:4px;background:#ede9fe;color:#7c3aed;font-size:11px;font-weight:600;">웍스 분석${req.ai_confidence ? ' ' + Math.round(req.ai_confidence*100)+'%' : ''}</span>` : ''}
         ${req.source_type === 'attachment_ai' ? `<span style="padding:1px 7px;border-radius:4px;background:#ecfdf5;color:#059669;font-size:11px;font-weight:600;">파일웍스</span>` : ''}`;

    let metaStr = `${rdEsc(req.reporter_name ?? '')} 등록 · ${rdEsc(req.created_at)}`;
    if (req.created_at !== req.updated_at) metaStr += ` · 수정 ${rdEsc(req.updated_at)}`;
    document.getElementById('rd-meta').textContent = metaStr;

    // 파일웍스 출처 표시
    const srcBannerEl = document.getElementById('rd-source-banner');
    if (srcBannerEl) {
        if (req.source_type === 'attachment_ai' && req.source_ref) {
            const srcMatch = req.source_ref.match(/#(\d+)/);
            const srcId    = srcMatch ? srcMatch[1] : null;
            srcBannerEl.innerHTML = srcId
                ? `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                   ${rdEsc(req.source_ref)} &nbsp;
                   <button onclick="closeReqDetail(); openReqDetail(${srcId}, '{{ url('/') }}/projects/{{ $project->id }}/requirements/${srcId}')"
                           style="padding:1px 7px;background:#dcfce7;border:1px solid #86efac;border-radius:4px;font-size:10px;font-weight:600;color:#16a34a;cursor:pointer;">출처 열기</button>`
                : `<svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                   ${rdEsc(req.source_ref)}`;
            srcBannerEl.style.display = 'flex';
        } else {
            srcBannerEl.style.display = 'none';
        }
    }

    // 삭제 버튼
    document.getElementById('rd-delete-btn').style.display = data.can_delete ? '' : 'none';

    // 설명
    document.getElementById('rd-desc-view').textContent = req.description || '설명이 없습니다.';
    document.getElementById('rd-desc-input').value = req.description ?? '';
    document.getElementById('rd-desc-edit').style.display = 'none';
    document.getElementById('rd-desc-view').style.display = 'block';

    // 댓글
    document.getElementById('rd-comment-title').textContent = `댓글 (${data.comments.length})`;
    const cl = document.getElementById('rd-comment-list');
    cl.innerHTML = data.comments.map(c =>
        `<div style="padding:10px 12px;background:#f9fafb;border-radius:8px;">
            <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                <span style="font-size:12px;font-weight:600;color:#374151;">${rdEsc(c.author_name)}</span>
                <span style="font-size:11px;color:#9ca3af;">${rdEsc(c.created_at)}</span>
            </div>
            <p style="font-size:13px;color:#374151;margin:0;white-space:pre-wrap;">${rdEsc(c.content)}</p>
        </div>`
    ).join('');
    document.getElementById('rd-comment-input').value = '';

    // 변경 이력
    const hw = document.getElementById('rd-history-wrap');
    const hl = document.getElementById('rd-history-list');
    if (data.histories.length) {
        hl.innerHTML = data.histories.map(h =>
            `<div style="display:flex;align-items:center;gap:6px;font-size:11px;color:#6b7280;padding:5px 0;border-bottom:1px solid #f3f4f6;flex-wrap:wrap;">
                <span style="font-weight:600;color:#374151;">${rdEsc(h.changed_by)}</span>
                <span><b>${rdEsc(h.field_name)}</b></span>
                <span style="background:#fee2e2;color:#991b1b;padding:1px 5px;border-radius:3px;">${rdEsc(h.old_value||'없음')}</span>
                <span>→</span>
                <span style="background:#d1fae5;color:#065f46;padding:1px 5px;border-radius:3px;">${rdEsc(h.new_value||'없음')}</span>
                <span style="margin-left:auto;white-space:nowrap;">${rdEsc(h.changed_at)}</span>
            </div>`
        ).join('');
        hw.style.display = 'block';
    } else {
        hw.style.display = 'none';
    }

    // 우측: 세부 정보
    const setVal = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
    setVal('rd-status',   req.status);
    setVal('rd-priority', req.priority);
    setVal('rd-category', req.category);

    const assignSel = document.getElementById('rd-assignee');
    assignSel.innerHTML = '<option value="">담당자 없음</option>' +
        data.members.map(m => `<option value="${m.id}" ${m.id == req.assignee_id ? 'selected' : ''}>${rdEsc(m.name)}</option>`).join('');

    document.getElementById('rd-reporter').textContent = req.reporter_name ?? '-';

    const tagsEl = document.getElementById('rd-tags');
    tagsEl.innerHTML = (req.tags && req.tags.length)
        ? req.tags.map(t => `<span style="padding:2px 8px;background:#f3f4f6;border-radius:5px;font-size:11px;color:#6b7280;">${rdEsc(t)}</span>`).join('')
        : '<span style="font-size:12px;color:#9ca3af;">없음</span>';

    // 구독 버튼
    rdSetWatchBtn(data.is_watching);

    // 첨부 파일
    const attEl = document.getElementById('rd-attachments');
    attEl.innerHTML = (data.attachments && data.attachments.length)
        ? data.attachments.map(a => {
            const isImg = a.mime_type && a.mime_type.startsWith('image/');
            const icon = isImg
                ? `<svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" stroke-width="2"/><circle cx="8.5" cy="8.5" r="1.5" stroke-width="2"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 15l-5-5L5 21"/></svg>`
                : `<svg width="13" height="13" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`;
            return `<a href="${rdEsc(a.download_url)}" target="_blank" style="display:flex;align-items:center;gap:6px;padding:6px 8px;background:#f8fafc;border:1px solid #e4e4e7;border-radius:7px;text-decoration:none;transition:background .1s;"
                        onmouseover="this.style.background='#f0f9ff'" onmouseout="this.style.background='#f8fafc'">
                    ${icon}
                    <span style="flex:1;font-size:12px;color:#374151;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${rdEsc(a.filename)}</span>
                    <span style="font-size:11px;color:#9ca3af;flex-shrink:0;">${rdEsc(a.size_human)}</span>
                </a>`;
          }).join('')
        : '<p style="font-size:12px;color:#9ca3af;margin:0;">첨부 파일 없음</p>';

    // 기획서 적용 이력
    const pa = document.getElementById('rd-plan-apps');
    pa.innerHTML = data.plan_applications.length
        ? data.plan_applications.map(a =>
            `<div style="background:#f9fafb;border-radius:7px;padding:8px 10px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                    <a href="${a.plan_url}" style="font-size:12px;font-weight:600;color:var(--t500);text-decoration:none;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">📄 ${rdEsc(a.plan_title)}</a>
                </div>
                <p style="font-size:11px;color:#9ca3af;margin:3px 0 0;">${rdEsc(a.applied_by)} · ${rdEsc(a.applied_at)}</p>
            </div>`
          ).join('')
        : '<p style="font-size:12px;color:#9ca3af;margin:0;">아직 적용되지 않았습니다.</p>';
}

async function rdSetWatchBtn(watching) {
    const btn = document.getElementById('rd-watch-btn');
    btn.textContent       = watching ? '🔔 알림 구독 중' : '🔕 알림 받기';
    btn.style.borderColor = watching ? '#c7d2fe' : '#e4e4e7';
    btn.style.background  = watching ? '#eef2ff' : '#fff';
    btn.style.color       = watching ? 'var(--t600)' : '#6b7280';
}

async function rdPatch(field, value) {
    if (!_rd) return;
    const body = new FormData();
    body.append('_method', 'PATCH');
    body.append(field, value);
    await fetch(_rd.urls.update, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body });
    // 목록 행 배지 업데이트
    const row = document.querySelector(`.req-row[data-id="${_rd.requirement.id}"]`);
    if (row && (field === 'status' || field === 'priority')) location.reload();
}

async function rdToggleDesc() {
    const view = document.getElementById('rd-desc-view');
    const edit = document.getElementById('rd-desc-edit');
    const isEdit = edit.style.display !== 'none';
    view.style.display = isEdit ? 'block' : 'none';
    edit.style.display = isEdit ? 'none' : 'block';
    if (!isEdit) document.getElementById('rd-desc-input').focus();
}

async function rdSaveDesc() {
    if (!_rd) return;
    const val  = document.getElementById('rd-desc-input').value;
    const body = new FormData();
    body.append('_method', 'PATCH');
    body.append('description', val);
    const res = await fetch(_rd.urls.update, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body });
    if (res.ok) {
        document.getElementById('rd-desc-view').textContent = val || '설명이 없습니다.';
        _rd.requirement.description = val;
        rdToggleDesc();
    }
}

async function rdPostComment() {
    if (!_rd) return;
    const input   = document.getElementById('rd-comment-input');
    const content = input.value.trim();
    if (!content) return;
    const body = new FormData();
    body.append('content', content);
    const res = await fetch(_rd.urls.comment, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body });
    if (!res.ok) return;
    const d = await res.json();
    const c = d.comment;
    const div = document.createElement('div');
    div.style.cssText = 'padding:10px 12px;background:#f9fafb;border-radius:8px;';
    div.innerHTML = `<div style="display:flex;justify-content:space-between;margin-bottom:4px;">
        <span style="font-size:12px;font-weight:600;color:#374151;">${rdEsc(c.author_name)}</span>
        <span style="font-size:11px;color:#9ca3af;">${rdEsc(c.created_at)}</span>
    </div><p style="font-size:13px;color:#374151;margin:0;white-space:pre-wrap;">${rdEsc(c.content)}</p>`;
    document.getElementById('rd-comment-list').appendChild(div);
    const cnt = document.getElementById('rd-comment-title');
    cnt.textContent = cnt.textContent.replace(/\d+/, m => parseInt(m)+1);
    input.value = '';
}

async function rdToggleWatch() {
    if (!_rd) return;
    const res = await fetch(_rd.urls.watch, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'} });
    const d   = await res.json();
    rdSetWatchBtn(d.watching);
    _rd.is_watching = d.watching;
}

async function rdDelete() {
    if (!_rd || !await __confirm('이 요구사항을 삭제하시겠습니까?')) return;
    const body = new FormData();
    body.append('_method', 'DELETE');
    const res = await fetch(_rd.urls.destroy, { method:'POST', headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body });
    if (res.ok) { closeReqDetail(); location.reload(); }
}

// ── 간트 일정 등록 ──────────────────────────────────────────────
const GANTT_STORE   = '{{ route('projects.sub-tasks.store', $project) }}';
const GANTT_TREE    = '{{ route('projects.schedule-tree', $project) }}';
const GANTT_MEMBERS = @json($members->map(fn($m) => ['id' => $m->id, 'name' => $m->name]));

let _ganttReqs = [];

async function openGanttModalForRow(btn) {
    const row = btn.closest('.req-row');
    openGanttModal([{
        requirementId: row.dataset.id,
        title:         row.dataset.title,
        description:   row.dataset.description,
        assigneeId:    row.dataset.assigneeId,
    }]);
}

async function openGanttModal(overrideReqs = null) {
    if (overrideReqs !== null) {
        _ganttReqs = overrideReqs;
    } else {
        // 일괄 간트 추가: 기획서에 반영됐고 아직 간트에 없는 항목만
        _ganttReqs = [...document.querySelectorAll('.req-chk:checked')]
            .filter(el => {
                const row = el.closest('.req-row');
                return row.dataset.applied === '1' && row.dataset.inGantt !== '1';
            })
            .map(el => {
                const row = el.closest('.req-row');
                return {
                    requirementId: row.dataset.id,
                    title:         row.dataset.title,
                    description:   row.dataset.description,
                    assigneeId:    row.dataset.assigneeId,
                };
            });
        if (_ganttReqs.length === 0) {
            alert('간트에 추가할 수 있는 요구사항이 없습니다.\n기획서에 반영된 요구사항만 간트에 추가할 수 있습니다.');
            return;
        }
    }

    document.getElementById('gantt-start').value = '';
    document.getElementById('gantt-end').value   = '';
    document.getElementById('gantt-error').style.display = 'none';
    document.getElementById('gantt-info').textContent = _ganttReqs.length + '개 요구사항을 일정으로 등록합니다.';

    const firstAssigneeId = _ganttReqs[0].assigneeId || '';
    const assigneeSel = document.getElementById('gantt-assignee');
    assigneeSel.innerHTML = '<option value="">없음</option>' +
        GANTT_MEMBERS.map(m =>
            `<option value="${m.id}" ${m.id == firstAssigneeId ? 'selected' : ''}>${m.name}</option>`
        ).join('');

    const groupSel = document.getElementById('gantt-group');
    groupSel.innerHTML = '<option value="">불러오는 중...</option>';
    try {
        const res  = await fetch(GANTT_TREE, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
        const tree = await res.json();
        const opts = [];
        (tree.milestones ?? []).forEach(ms => {
            (ms.task_groups ?? ms.taskGroups ?? []).forEach(g => {
                opts.push(`<option value="${g.id}">[${ms.title}] ${g.title}</option>`);
            });
        });
        (tree.ungrouped ?? []).forEach(g => {
            opts.push(`<option value="${g.id}">${g.title}</option>`);
        });
        groupSel.innerHTML = opts.length
            ? '<option value="">그룹 선택...</option>' + opts.join('')
            : '<option value="">등록된 작업 그룹이 없습니다</option>';
    } catch {
        groupSel.innerHTML = '<option value="">그룹 로드 실패</option>';
    }

    document.getElementById('gantt-overlay').style.display = 'block';
    document.getElementById('gantt-modal').style.display   = 'block';
}

async function closeGanttModal() {
    document.getElementById('gantt-overlay').style.display = 'none';
    document.getElementById('gantt-modal').style.display   = 'none';
}

async function submitGanttModal() {
    const groupId = document.getElementById('gantt-group').value;
    const start   = document.getElementById('gantt-start').value;
    const end     = document.getElementById('gantt-end').value;
    const errEl   = document.getElementById('gantt-error');
    errEl.style.display = 'none';

    if (!groupId) { errEl.textContent = '작업 그룹을 선택해주세요.'; errEl.style.display = 'block'; return; }
    if (!start)   { errEl.textContent = '시작일을 선택해주세요.';     errEl.style.display = 'block'; return; }
    if (!end)     { errEl.textContent = '종료일을 선택해주세요.';     errEl.style.display = 'block'; return; }

    const btn = document.getElementById('gantt-submit-btn');
    btn.disabled = true; btn.textContent = '등록 중...';

    const assigneeId = document.getElementById('gantt-assignee').value;
    let success = 0, fail = 0;

    for (const req of _ganttReqs) {
        try {
            const body = new FormData();
            body.append('task_group_id',  groupId);
            body.append('title',          req.title);
            body.append('start_date',     start);
            body.append('end_date',       end);
            body.append('assignee_id',    assigneeId || req.assigneeId || '');
            body.append('description',    req.description || '');
            body.append('status',         'not_started');
            if (req.requirementId) body.append('requirement_id', req.requirementId);
            const res = await fetch(GANTT_STORE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body });
            if (res.ok) { success++; }
            else {
                const data = await res.json().catch(() => ({}));
                if (data.message && data.message.includes('이미 간트에')) { /* skip duplicates silently */ }
                else fail++;
            }
        } catch { fail++; }
    }

    btn.disabled = false; btn.textContent = '일정 등록';
    closeGanttModal();
    alert(success + '개 일정이 간트에 등록되었습니다.' + (fail ? ` (${fail}개 실패)` : ''));
    clearSelection();
}
</script>
{{-- Office 문서 파싱 라이브러리 --}}
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mammoth@1.8.0/mammoth.browser.min.js"></script>

{{-- 간트 일정 등록 모달 --}}
<div id="gantt-overlay" onclick="closeGanttModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:10200;"></div>
<div id="gantt-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:10201;background:#fff;border-radius:16px;box-shadow:0 20px 60px rgba(0,0,0,.2);width:460px;max-width:calc(100vw - 32px);max-height:90vh;overflow-y:auto;">
    <div style="padding:18px 22px 14px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;">
        <h3 style="font-size:15px;font-weight:700;color:#18181b;margin:0;">간트 일정에 추가</h3>
        <button onclick="closeGanttModal()" style="background:none;border:none;cursor:pointer;color:#a1a1aa;font-size:22px;padding:2px;line-height:1;">&times;</button>
    </div>
    <div style="padding:18px 22px;display:flex;flex-direction:column;gap:14px;">
        <div id="gantt-info" style="padding:10px 14px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;font-size:13px;color:#0369a1;font-weight:600;"></div>
        <div id="gantt-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;font-size:13px;color:#dc2626;"></div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">작업 그룹 <span style="color:#ef4444;">*</span></label>
            <select id="gantt-group" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">불러오는 중...</option>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">시작일 <span style="color:#ef4444;">*</span></label>
                <input id="gantt-start" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">종료일 <span style="color:#ef4444;">*</span></label>
                <input id="gantt-end" type="date" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                       onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            </div>
        </div>
        <div>
            <label style="display:block;font-size:12px;font-weight:600;color:#374151;margin-bottom:5px;">담당자</label>
            <select id="gantt-assignee" style="width:100%;padding:8px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                <option value="">없음</option>
            </select>
        </div>
        <p style="font-size:12px;color:#9ca3af;margin:0;">시작일·종료일·담당자는 선택한 모든 요구사항에 동일하게 적용됩니다.</p>
    </div>
    <div style="padding:0 22px 20px;display:flex;gap:10px;justify-content:flex-end;">
        <button onclick="closeGanttModal()" style="padding:8px 18px;font-size:13px;color:#6b7280;background:#fff;border:1.5px solid #e4e4e7;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">취소</button>
        <button onclick="submitGanttModal()" id="gantt-submit-btn" style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:#0284c7;border:none;border-radius:8px;cursor:pointer;"
                onmouseover="this.style.background='#0369a1'" onmouseout="this.style.background='#0284c7'">일정 등록</button>
    </div>
</div>
@endsection
