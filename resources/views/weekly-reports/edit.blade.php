@php $isPopup = request()->boolean('popup'); @endphp
@extends($isPopup ? 'layouts.popup' : 'layouts.app')

@section('title', ($report ? $report->week_label : __('weekly.page_title')) . ' - ' . $project->name)

@if($isPopup)
@section('popup-title')
<svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
{{ __('weekly.edit_popup_title', ['label' => $report?->week_label ?? __('weekly.edit_popup_new')]) }}
@endsection
@endif

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('weekly.weekly_report') }}</span>
@endsection

@push('styles')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<style>
    .ql-editor { min-height: 120px; font-family: inherit; font-size: 13.5px; }
    .ql-toolbar { border-radius: 8px 8px 0 0; border-color: #e5e7eb !important; }
    .ql-container { border-radius: 0 0 8px 8px; border-color: #e5e7eb !important; }

    .task-grid-table th { font-size: 11.5px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; }
    .task-grid-table td { font-size: 13px; padding: 6px 8px; vertical-align: middle; }
    .task-grid-table tr:hover td { background: #f5f3ff; }

    .badge-modified { display:inline-flex; align-items:center; gap:3px; background:#fef3c7; color:#92400e; border:1px solid #fde68a; border-radius:4px; font-size:10px; font-weight:600; padding:1px 5px; cursor:help; }
    .badge-prev     { display:inline-flex; align-items:center; gap:3px; background:#ede9fe; color:#4f46e5; border:1px solid #c4b5fd; border-radius:4px; font-size:10px; font-weight:600; padding:1px 5px; }

    .status-completed  { background:#d1fae5; color:#065f46; }
    .status-in_progress{ background:#dbeafe; color:#1e40af; }
    .status-pending    { background:#f3f4f6; color:#6b7280; }

    .section-preview-row { border-left: 3px solid #e5e7eb; padding: 4px 10px; margin: 2px 0; font-size: 13px; color: #374151; display: flex; gap: 12px; align-items: center; }
    .section-preview-row.completed  { border-left-color: #10b981; }
    .section-preview-row.in_progress{ border-left-color: #3b82f6; }

    .section-card { background:#fff; border:1px solid #e9e7fb; border-radius:12px; padding:20px 24px; margin-bottom:16px; }
    .section-number { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background: linear-gradient(135deg,#7c3aed,#6d28d9); color:#fff; border-radius:50%; font-size:11px; font-weight:700; margin-right:8px; }
    .section-title { font-size:14px; font-weight:700; color:#1f2937; }

    input.wr-date { border:1px solid #e5e7eb; border-radius:7px; padding:5px 10px; font-size:13px; color:#374151; outline:none; }
    input.wr-date:focus { border-color:#7c3aed; box-shadow:0 0 0 2px #ede9fe; }
    input.wr-text { border:1px solid #e5e7eb; border-radius:7px; padding:5px 10px; font-size:13px; color:#374151; outline:none; width:100%; }
    input.wr-text:focus { border-color:#7c3aed; box-shadow:0 0 0 2px #ede9fe; }
    select.wr-select { border:1px solid #e5e7eb; border-radius:7px; padding:5px 10px; font-size:12.5px; color:#374151; outline:none; cursor:pointer; }
    select.wr-select:focus { border-color:#7c3aed; }
    textarea.wr-textarea { border:1px solid #e5e7eb; border-radius:8px; padding:10px 12px; font-size:13px; color:#374151; outline:none; width:100%; resize:vertical; min-height:90px; }
    textarea.wr-textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 2px #ede9fe; }

    .btn-add-row { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border:1.5px dashed #c4b5fd; border-radius:7px; color:#7c3aed; font-size:12.5px; font-weight:600; cursor:pointer; background:transparent; transition:all .13s; }
    .btn-add-row:hover { background:#f5f3ff; border-color:#7c3aed; }

    #concurrent-modal { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,.45); align-items:center; justify-content:center; }
    #concurrent-modal.show { display:flex; }
</style>
@endpush

@section('content')
<div class="space-y-4 pt-4" style="max-width:900px;margin:0 auto;">

{{-- 동시성 경고 모달 --}}
<div id="concurrent-modal">
    <div style="background:#fff;border-radius:14px;padding:28px 32px;max-width:420px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
            <div style="background:#fef3c7;border-radius:8px;padding:7px;">
                <svg width="20" height="20" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
            </div>
            <span style="font-size:15px;font-weight:700;color:#1f2937;">{{ __('weekly.concurrent_detected') }}</span>
        </div>
        <p id="concurrent-msg" style="font-size:13.5px;color:#374151;line-height:1.6;margin-bottom:20px;"></p>
        <div style="display:flex;gap:12px;justify-content:flex-end;">
            <button onclick="closeConcurrentModal()" style="padding:8px 18px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;font-weight:600;color:#374151;background:#fff;cursor:pointer;">{{ __('weekly.continue_writing') }}</button>
            <a href="{{ route('projects.show', $project) }}" style="padding:8px 18px;background:#6d28d9;color:#fff;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;">{{ __('weekly.back_to_project') }}</a>
        </div>
    </div>
</div>

{{-- 폼 --}}
<form id="report-form" method="POST"
    action="{{ $report ? route('projects.weekly-reports.update', [$project, $report]) : route('projects.weekly-reports.store', $project) }}">
    @csrf
    @if($report) @method('PATCH') @endif

    <input type="hidden" name="action"          id="action-field"     value="draft">
    <input type="hidden" name="current_tasks"   id="current-tasks-json">
    <input type="hidden" name="next_week_tasks" id="next-week-tasks-json">
    <input type="hidden" name="summary"         id="summary-hidden">
    <input type="hidden" name="week_start_date" id="week-start-date"  value="{{ $weekStartDate }}">
    @if($isPopup)<input type="hidden" name="popup" value="1">@endif

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:10px 16px;font-size:13px;color:#065f46;margin-bottom:12px;">
        {{ session('success') }}
    </div>
    @endif

    @if($errors->has('week_start_date'))
    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:10px 16px;font-size:13px;color:#991b1b;margin-bottom:12px;">
        {{ $errors->first('week_start_date') }}
    </div>
    @endif

    {{-- ─── 헤더 카드 ─── --}}
    <div class="section-card">
        {{-- 제목 --}}
        <div style="margin-bottom:16px;">
            <p style="font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px;">{{ __('weekly.report_title_label') }}</p>
            <h2 id="report-title" style="font-size:18px;font-weight:700;color:#1f2937;"></h2>
        </div>

        {{-- 주차 선택 --}}
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;padding:10px 14px;background:#f5f3ff;border-radius:9px;">
            <svg width="16" height="16" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <span style="font-size:12.5px;font-weight:600;color:#6d28d9;">{{ __('weekly.base_date_select') }}</span>
            <input type="date" id="base-date" class="wr-date" value="{{ $weekStartDate }}">
            <span id="week-label-badge" style="background:#ede9fe;color:#5b21b6;border-radius:6px;padding:3px 10px;font-size:12.5px;font-weight:700;"></span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:12px;">
            {{-- 팀명 --}}
            <div>
                <label style="font-size:11.5px;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">{{ __('weekly.team_name') }}</label>
                <input type="text" name="team_name" id="team-name" list="team-names-list" class="wr-text"
                    value="{{ $report?->team_name ?? '' }}" placeholder="{{ __('weekly.team_name_placeholder') }}">
                <datalist id="team-names-list">
                    @foreach($teamNames as $tn)
                    <option value="{{ $tn }}">
                    @endforeach
                </datalist>
            </div>
            {{-- 담당 매니저 --}}
            <div>
                <label style="font-size:11.5px;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">{{ __('weekly.manager_name') }}</label>
                <input type="text" name="manager_name" id="manager-name" list="manager-names-list" class="wr-text"
                    value="{{ $report?->manager_name ?? '' }}" placeholder="{{ __('weekly.manager_name_placeholder') }}">
                <datalist id="manager-names-list">
                    @foreach($projectMembers as $member)
                    <option value="{{ $member->name }}">
                    @endforeach
                </datalist>
            </div>
            {{-- 작성자 --}}
            <div>
                <label style="font-size:11.5px;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">{{ __('weekly.author_name') }}</label>
                <input type="text" name="author_name" id="author-name" class="wr-text"
                    value="{{ $report?->author_name ?? auth()->user()->name }}" readonly style="background:#f9fafb;color:#6b7280;">
            </div>
            {{-- 작성일 --}}
            <div>
                <label style="font-size:11.5px;font-weight:600;color:#6b7280;display:block;margin-bottom:5px;">{{ __('weekly.report_date') }}</label>
                <input type="date" name="report_date" id="report-date" class="wr-date" style="width:100%;"
                    value="{{ $report?->report_date?->format('Y-m-d') ?? today()->format('Y-m-d') }}">
            </div>
        </div>
    </div>

    {{-- ─── 섹션 1: 주요 성과 요약 ─── --}}
    <div class="section-card">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <span class="section-number">1</span>
            <span class="section-title">{{ __('weekly.section1_title') }}</span>
            <span style="margin-left:6px;font-size:11px;background:#ede9fe;color:#7c3aed;border-radius:4px;padding:1px 7px;font-weight:600;">Rich Text</span>
        </div>
        <div id="quill-editor"></div>
    </div>

    {{-- ─── 섹션 2+3: 업무 현황 관리 그리드 ─── --}}
    <div class="section-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="display:flex;gap:4px;">
                    <span class="section-number">2</span>
                    <span class="section-number" style="background:linear-gradient(135deg,#0891b2,#0e7490);">3</span>
                </div>
                <span class="section-title">{{ __('weekly.section23_title') }}</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span id="prev-load-badge" style="display:none;font-size:11px;background:#ede9fe;color:#7c3aed;border-radius:5px;padding:2px 8px;font-weight:600;"></span>
                <button type="button" class="btn-add-row" onclick="addCurrentTask()">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    {{ __('weekly.add_task') }}
                </button>
            </div>
        </div>
        <p style="font-size:11.5px;color:#9ca3af;margin-bottom:12px;">{{ __('weekly.section23_hint') }}</p>

        <div style="overflow-x:auto;">
            <table class="task-grid-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1.5px solid #e9e7fb;">
                        <th style="width:40%;padding:8px 8px;text-align:left;">{{ __('weekly.col_task_name') }}</th>
                        <th style="width:13%;padding:8px 8px;text-align:center;">{{ __('weekly.col_start_date') }}</th>
                        <th style="width:13%;padding:8px 8px;text-align:center;">{{ __('weekly.col_end_date') }}</th>
                        <th style="width:18%;padding:8px 8px;text-align:center;">{{ __('weekly.col_status') }}</th>
                        <th style="width:8%;padding:8px 8px;text-align:center;">{{ __('weekly.col_original') }}</th>
                        <th style="width:8%;padding:8px 8px;text-align:center;"></th>
                    </tr>
                </thead>
                <tbody id="current-tasks-body">
                    <tr id="current-empty-row">
                        <td colspan="6" style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;">
                            {{ __('weekly.current_empty') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- 섹션 2+3 미리보기 --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:14px;">
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span class="section-number" style="width:18px;height:18px;font-size:10px;">2</span>
                    <span style="font-size:12.5px;font-weight:700;color:#065f46;">{{ __('weekly.section2_preview_title') }}</span>
                    <span id="completed-count" style="background:#10b981;color:#fff;border-radius:999px;font-size:10px;padding:1px 7px;font-weight:700;margin-left:auto;">0</span>
                </div>
                <div id="section2-preview" style="display:flex;flex-direction:column;gap:4px;"></div>
            </div>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 14px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;">
                    <span class="section-number" style="width:18px;height:18px;font-size:10px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);">3</span>
                    <span style="font-size:12.5px;font-weight:700;color:#1e40af;">{{ __('weekly.section3_preview_title') }}</span>
                    <span id="inprogress-count" style="background:#3b82f6;color:#fff;border-radius:999px;font-size:10px;padding:1px 7px;font-weight:700;margin-left:auto;">0</span>
                </div>
                <div id="section3-preview" style="display:flex;flex-direction:column;gap:4px;"></div>
            </div>
        </div>
    </div>

    {{-- ─── 섹션 4: 차주 업무 계획 ─── --}}
    <div class="section-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="section-number" style="background:linear-gradient(135deg,#f59e0b,#d97706);">4</span>
                <span class="section-title">{{ __('weekly.section4_title') }}</span>
            </div>
            <button type="button" class="btn-add-row" onclick="addNextWeekTask()">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                {{ __('weekly.add_plan') }}
            </button>
        </div>

        <div style="overflow-x:auto;">
            <table class="task-grid-table" style="width:100%;border-collapse:collapse;">
                <thead>
                    <tr style="border-bottom:1.5px solid #e9e7fb;">
                        <th style="width:55%;padding:8px 8px;text-align:left;">{{ __('weekly.col_task_name') }}</th>
                        <th style="width:17%;padding:8px 8px;text-align:center;">{{ __('weekly.col_start_date') }}</th>
                        <th style="width:17%;padding:8px 8px;text-align:center;">{{ __('weekly.col_end_date') }}</th>
                        <th style="width:11%;padding:8px 8px;text-align:center;"></th>
                    </tr>
                </thead>
                <tbody id="next-week-tasks-body">
                    <tr id="next-empty-row">
                        <td colspan="4" style="text-align:center;padding:20px;color:#9ca3af;font-size:13px;">
                            {{ __('weekly.next_empty') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ─── 섹션 5: 특이 사항 ─── --}}
    <div class="section-card">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:14px;">
            <span class="section-number" style="background:linear-gradient(135deg,#ef4444,#dc2626);">5</span>
            <span class="section-title">{{ __('weekly.section5_title') }}</span>
        </div>
        <textarea name="special_notes" id="special-notes" class="wr-textarea"
            placeholder="{{ __('weekly.special_notes_placeholder') }}"
        >{{ $report?->special_notes ?? '' }}</textarea>
    </div>

    {{-- ─── 하단 버튼 (고정) ─── --}}
    <div style="position:sticky;bottom:0;z-index:50;display:flex;align-items:center;justify-content:space-between;padding:14px 24px;background:#fff;border-top:1px solid #e9e7fb;margin:0 -24px;box-shadow:0 -4px 16px rgba(0,0,0,.06);">
        @if($isPopup)
        <button type="button" onclick="window.parent&&window.parent.closeWeeklyReportPopup(false)"
            style="display:inline-flex;align-items:center;gap:8px;color:#6b7280;font-size:13px;background:none;border:none;cursor:pointer;padding:0;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            {{ __('common.close') }}
        </button>
        @else
        <a href="{{ route('projects.show', $project) }}"
            style="display:inline-flex;align-items:center;gap:8px;color:#6b7280;font-size:13px;text-decoration:none;">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            {{ __('weekly.back_to_project') }}
        </a>
        @endif
        <div style="display:flex;gap:12px;">
            <button type="button" onclick="submitForm('draft')"
                style="display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border:1.5px solid #d1d5db;border-radius:8px;background:#fff;color:#374151;font-size:13.5px;font-weight:600;cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                {{ __('weekly.save_draft') }}
            </button>
            <button type="button" onclick="submitForm('submit')"
                style="display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border:none;border-radius:8px;background:#059669;color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ __('weekly.submit_report') }}
            </button>
            <button type="button" onclick="submitForm('download')"
                style="display:inline-flex;align-items:center;gap:8px;padding:9px 20px;border:none;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#7c3aed);color:#fff;font-size:13.5px;font-weight:600;cursor:pointer;">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                {{ __('weekly.download_word') }}
            </button>
        </div>
    </div>

</form>
</div>
@endsection

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.js"></script>
<script>
// ── 초기 데이터 ───────────────────────────────────────────────────────
const INITIAL = {
    report_id:       {{ $report?->id ?? 'null' }},
    is_new:          {{ $report ? 'false' : 'true' }},
    week_start_date: @json($weekStartDate),
    summary:         @json($report?->summary ?? ''),
    current_tasks:   @json($initialCurrentTasks),
    next_week_tasks: @json($initialNextWeekTasks),
};
const PROJECT_ID = {{ $project->id }};
const URLS = {
    prevTasks:       '{{ route('projects.weekly-reports.previous-tasks', $project) }}',
    checkConcurrent: '{{ route('projects.weekly-reports.check-concurrent', $project) }}',
};

// ── 번역 문자열 ────────────────────────────────────────────────────
const WR_I18N = {
    weekLabel:        @json(__('weekly.week_label_js')),
    titleTemplate:    @json(__('weekly.report_title_template', ['project' => $project->name, 'label' => ':label'])),
    prevLinkedBadge:  @json(__('weekly.prev_linked_badge', ['date' => ':date', 'count' => ':count'])),
    concurrentMsg:    @json(__('weekly.concurrent_message', ['name' => ':name', 'date' => ':date'])),
    taskNamePlaceholder: @json(__('weekly.task_name_placeholder')),
    statusPending:    @json(__('weekly.status_pending')),
    statusInProgress: @json(__('weekly.status_in_progress')),
    statusCompleted:  @json(__('weekly.status_completed')),
    viewOriginal:     @json(__('weekly.view_original')),
    badgeModified:    @json(__('weekly.badge_modified')),
    badgeLinked:      @json(__('weekly.badge_linked')),
    taskNameEmpty:    @json(__('weekly.task_name_empty')),
    noCompletedTask:  @json(__('weekly.no_completed_task')),
    noInProgressTask: @json(__('weekly.no_in_progress_task')),
    {{-- @json directive 가 PHP 8.3 의 token parser 에서 3-key array placeholder 파싱 실패. Blade 우회 후 직접 json_encode. --}}
    originalDataAlert:<?php echo json_encode(__('weekly.original_data_alert', ['name' => ':name', 'start' => ':start', 'end' => ':end'])); ?>,
    downloadingWord:  @json(__('weekly.downloading_word')),
};

// ── 상태 ────────────────────────────────────────────────────────────
let currentTasks   = [];
let nextWeekTasks  = [];
let taskIdCounter  = 0;
let quill;

function newId() { return 'ct_' + (++taskIdCounter); }

// ── 주차 계산 ────────────────────────────────────────────────────────
function getWeekInfo(dateStr) {
    const d = new Date(dateStr + 'T00:00:00');
    const dow = d.getDay(); // 0=Sun
    const diffToMon = dow === 0 ? -6 : 1 - dow;
    const mon = new Date(d);
    mon.setDate(d.getDate() + diffToMon);

    const y = mon.getFullYear();
    const m = mon.getMonth() + 1;
    const day = mon.getDate();
    const w = Math.ceil(day / 7);

    const pad2 = n => String(n).padStart(2, '0');
    return {
        label:          WR_I18N.weekLabel
                            .replace(':year', y).replace(':month', m).replace(':week', w),
        weekStartDate:  `${y}-${pad2(m)}-${pad2(day)}`,
        isoWeek:        getISOWeek(mon),
        year:           y,
    };
}

function getISOWeek(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    d.setDate(d.getDate() + 3 - (d.getDay() + 6) % 7);
    const w1 = new Date(d.getFullYear(), 0, 4);
    return 1 + Math.round(((d - w1) / 864e5 - 3 + (w1.getDay() + 6) % 7) / 7);
}

// ── 제목 / 라벨 업데이트 ─────────────────────────────────────────────
function updateWeekUI(weekStartDate) {
    const info = getWeekInfo(weekStartDate);
    const title = WR_I18N.titleTemplate.replace(':label', info.label);
    document.getElementById('report-title').textContent = title;
    document.getElementById('week-label-badge').textContent = info.label;
    document.getElementById('week-start-date').value = info.weekStartDate;
}

// ── 이전 주차 데이터 로드 ────────────────────────────────────────────
async function loadPreviousTasks(weekStartDate) {
    try {
        const res = await fetch(`${URLS.prevTasks}?week_start_date=${weekStartDate}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.found && data.tasks.length > 0) {
            currentTasks = data.tasks.map(t => ({ ...t, _id: newId() }));
            renderCurrentTasks();
            const badge = document.getElementById('prev-load-badge');
            badge.textContent = WR_I18N.prevLinkedBadge
                .replace(':date', data.from_week_date)
                .replace(':count', data.tasks.length);
            badge.style.display = 'inline-flex';
        }
    } catch (e) { /* 무시 */ }
}

// ── 동시성 확인 ──────────────────────────────────────────────────────
async function checkConcurrent(weekStartDate) {
    try {
        const res = await fetch(`${URLS.checkConcurrent}?week_start_date=${weekStartDate}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.concurrent) {
            document.getElementById('concurrent-msg').textContent = WR_I18N.concurrentMsg
                .replace(':name', data.user_name)
                .replace(':date', weekStartDate);
            document.getElementById('concurrent-modal').classList.add('show');
        }
    } catch (e) { /* 무시 */ }
}

function closeConcurrentModal() {
    document.getElementById('concurrent-modal').classList.remove('show');
}

// ── 현재 업무 그리드 ─────────────────────────────────────────────────
function renderCurrentTasks() {
    const tbody = document.getElementById('current-tasks-body');
    const emptyRow = document.getElementById('current-empty-row');
    emptyRow.style.display = currentTasks.length === 0 ? '' : 'none';

    // 기존 동적 행 제거
    tbody.querySelectorAll('tr.ct-row').forEach(r => r.remove());

    currentTasks.forEach((task, idx) => {
        const isModified = isTaskModified(task);
        const isFromPrev = task.original_data != null;
        const tr = document.createElement('tr');
        tr.className = 'ct-row';
        tr.dataset.id = task._id;

        const badgeHtml = isModified
            ? `<span class="badge-modified" title="${escHtml(WR_I18N.viewOriginal)}: ${escHtml(task.original_data?.task_name ?? '')} / ${task.original_data?.start_date ?? '-'} ~ ${task.original_data?.end_date ?? '-'}">${escHtml(WR_I18N.badgeModified)}</span>`
            : (isFromPrev ? `<span class="badge-prev">${escHtml(WR_I18N.badgeLinked)}</span>` : '');

        tr.innerHTML = `
            <td style="padding:6px 8px;">
                <div style="display:flex;align-items:center;gap:8px;">
                    <input type="text" value="${escHtml(task.task_name)}" placeholder="${escHtml(WR_I18N.taskNamePlaceholder)}"
                        style="border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;font-size:13px;width:100%;outline:none;"
                        oninput="updateCurrentTask('${task._id}','task_name',this.value)"
                        onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
                    ${badgeHtml}
                </div>
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <input type="date" value="${task.start_date}" class="wr-date" style="width:100%;"
                    onchange="updateCurrentTask('${task._id}','start_date',this.value)">
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <input type="date" value="${task.end_date}" class="wr-date" style="width:100%;"
                    onchange="updateCurrentTask('${task._id}','end_date',this.value)">
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <select class="wr-select" style="width:100%;"
                    onchange="updateCurrentTask('${task._id}','status',this.value)">
                    <option value="pending"     ${task.status==='pending'    ?'selected':''}>${escHtml(WR_I18N.statusPending)}</option>
                    <option value="in_progress" ${task.status==='in_progress'?'selected':''}>${escHtml(WR_I18N.statusInProgress)}</option>
                    <option value="completed"   ${task.status==='completed'  ?'selected':''}>${escHtml(WR_I18N.statusCompleted)}</option>
                </select>
            </td>
            <td style="text-align:center;padding:6px 8px;">
                ${isFromPrev
                    ? `<button type="button" title="${escHtml(WR_I18N.viewOriginal)}" onclick="showOriginal('${task._id}')"
                        style="background:none;border:none;cursor:pointer;color:#7c3aed;padding:2px;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      </button>`
                    : ''}
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <button type="button" onclick="removeCurrentTask('${task._id}')"
                    style="background:none;border:none;cursor:pointer;color:#ef4444;padding:2px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0V5a1 1 0 011-1h4a1 1 0 011 1v2H5z"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });

    renderSectionPreviews();
    if (typeof window.initDatePickers === 'function') window.initDatePickers();
}

function updateCurrentTask(id, field, value) {
    const task = currentTasks.find(t => t._id === id);
    if (!task) return;
    task[field] = value;
    // 배지만 업데이트 (재렌더보다 가볍게)
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if (row) {
        const badgeCell = row.querySelector('td:first-child');
        const badgeArea = badgeCell.querySelector('.badge-modified, .badge-prev');
        if (badgeArea) badgeArea.remove();
        const input = badgeCell.querySelector('input');
        const div = badgeCell.querySelector('div');
        if (isTaskModified(task)) {
            const badge = document.createElement('span');
            badge.className = 'badge-modified';
            badge.title = `${WR_I18N.viewOriginal}: ${escHtml(task.original_data?.task_name ?? '')} / ${task.original_data?.start_date ?? '-'} ~ ${task.original_data?.end_date ?? '-'}`;
            badge.textContent = WR_I18N.badgeModified;
            div.appendChild(badge);
        } else if (task.original_data) {
            const badge = document.createElement('span');
            badge.className = 'badge-prev';
            badge.textContent = WR_I18N.badgeLinked;
            div.appendChild(badge);
        }
    }
    if (field === 'status') renderSectionPreviews();
}

function addCurrentTask() {
    currentTasks.push({ _id: newId(), task_name: '', start_date: '', end_date: '', status: 'pending', original_data: null });
    renderCurrentTasks();
}

function removeCurrentTask(id) {
    currentTasks = currentTasks.filter(t => t._id !== id);
    renderCurrentTasks();
}

function isTaskModified(task) {
    if (!task.original_data) return false;
    const o = task.original_data;
    return task.task_name !== o.task_name
        || task.start_date !== (o.start_date ?? '')
        || task.end_date   !== (o.end_date ?? '');
}

function showOriginal(id) {
    const task = currentTasks.find(t => t._id === id);
    if (!task?.original_data) return;
    const o = task.original_data;
    alert(WR_I18N.originalDataAlert
        .replace(':name', o.task_name)
        .replace(':start', o.start_date || '-')
        .replace(':end', o.end_date || '-'));
}

// ── 섹션 2+3 미리보기 ────────────────────────────────────────────────
function renderSectionPreviews() {
    const s2 = document.getElementById('section2-preview');
    const s3 = document.getElementById('section3-preview');
    const completed   = currentTasks.filter(t => t.status === 'completed');
    const inProgress  = currentTasks.filter(t => t.status === 'in_progress');

    document.getElementById('completed-count').textContent  = completed.length;
    document.getElementById('inprogress-count').textContent = inProgress.length;

    s2.innerHTML = completed.length
        ? completed.map(t => `<div class="section-preview-row completed">✅ <span>${escHtml(t.task_name) || escHtml(WR_I18N.taskNameEmpty)}</span></div>`).join('')
        : `<div style="font-size:12px;color:#9ca3af;padding:4px 10px;">${escHtml(WR_I18N.noCompletedTask)}</div>`;

    s3.innerHTML = inProgress.length
        ? inProgress.map(t => `<div class="section-preview-row in_progress">🔵 <span>${escHtml(t.task_name) || escHtml(WR_I18N.taskNameEmpty)}</span></div>`).join('')
        : `<div style="font-size:12px;color:#9ca3af;padding:4px 10px;">${escHtml(WR_I18N.noInProgressTask)}</div>`;
}

// ── 차주 업무 그리드 ─────────────────────────────────────────────────
function renderNextWeekTasks() {
    const tbody = document.getElementById('next-week-tasks-body');
    const emptyRow = document.getElementById('next-empty-row');
    emptyRow.style.display = nextWeekTasks.length === 0 ? '' : 'none';

    tbody.querySelectorAll('tr.nw-row').forEach(r => r.remove());

    nextWeekTasks.forEach(task => {
        const tr = document.createElement('tr');
        tr.className = 'nw-row';
        tr.dataset.id = task._id;
        tr.innerHTML = `
            <td style="padding:6px 8px;">
                <input type="text" value="${escHtml(task.task_name)}" placeholder="${escHtml(WR_I18N.taskNamePlaceholder)}"
                    style="border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;font-size:13px;width:100%;outline:none;"
                    oninput="updateNextWeekTask('${task._id}','task_name',this.value)"
                    onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <input type="date" value="${task.start_date}" class="wr-date" style="width:100%;"
                    onchange="updateNextWeekTask('${task._id}','start_date',this.value)">
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <input type="date" value="${task.end_date}" class="wr-date" style="width:100%;"
                    onchange="updateNextWeekTask('${task._id}','end_date',this.value)">
            </td>
            <td style="text-align:center;padding:6px 8px;">
                <button type="button" onclick="removeNextWeekTask('${task._id}')"
                    style="background:none;border:none;cursor:pointer;color:#ef4444;padding:2px;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0V5a1 1 0 011-1h4a1 1 0 011 1v2H5z"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
    });
    if (typeof window.initDatePickers === 'function') window.initDatePickers();
}

function updateNextWeekTask(id, field, value) {
    const task = nextWeekTasks.find(t => t._id === id);
    if (task) task[field] = value;
}

function addNextWeekTask() {
    nextWeekTasks.push({ _id: newId(), task_name: '', start_date: '', end_date: '' });
    renderNextWeekTasks();
}

function removeNextWeekTask(id) {
    nextWeekTasks = nextWeekTasks.filter(t => t._id !== id);
    renderNextWeekTasks();
}

// ── 폼 제출 ─────────────────────────────────────────────────────────
function submitForm(action) {
    document.getElementById('summary-hidden').value      = quill.root.innerHTML;
    document.getElementById('current-tasks-json').value = JSON.stringify(
        currentTasks.map(({ _id, ...rest }) => rest)
    );
    document.getElementById('next-week-tasks-json').value = JSON.stringify(
        nextWeekTasks.map(({ _id, ...rest }) => rest)
    );
    document.getElementById('action-field').value = action;

    if (action === 'download') {
        const btn = event.currentTarget;
        btn.disabled = true;
        btn.textContent = WR_I18N.downloadingWord;
    }

    document.getElementById('report-form').submit();
}

// ── 유틸 ────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

// ── 초기화 ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    // Quill 초기화
    quill = new Quill('#quill-editor', {
        theme: 'snow',
        placeholder: @json(__('weekly.summary_placeholder')),
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ list: 'ordered' }, { list: 'bullet' }],
                ['clean']
            ]
        }
    });
    if (INITIAL.summary) {
        quill.root.innerHTML = INITIAL.summary;
    }

    // 기존 tasks 로드
    if (INITIAL.current_tasks.length > 0) {
        currentTasks = INITIAL.current_tasks.map(t => ({ ...t, _id: newId() }));
    }
    if (INITIAL.next_week_tasks.length > 0) {
        nextWeekTasks = INITIAL.next_week_tasks.map(t => ({ ...t, _id: newId() }));
    }

    renderCurrentTasks();
    renderNextWeekTasks();
    if (typeof window.initDatePickers === 'function') window.initDatePickers();

    // 주차 UI 업데이트
    updateWeekUI(INITIAL.week_start_date);

    // 신규 작성 시: 이전 데이터 로드 + 동시성 확인
    if (INITIAL.is_new) {
        loadPreviousTasks(INITIAL.week_start_date);
        checkConcurrent(INITIAL.week_start_date);
    }

    // 날짜 변경 리스너
    document.getElementById('base-date').addEventListener('change', function () {
        const info = getWeekInfo(this.value);
        updateWeekUI(info.weekStartDate);
        // 이전 데이터 재로드 (기존 tasks가 없을 때만)
        if (currentTasks.length === 0) {
            loadPreviousTasks(info.weekStartDate);
        }
        checkConcurrent(info.weekStartDate);
    });
});
</script>
@endpush

@if($isPopup)
@push('scripts')
<script>
// 팝업 모드 — 저장 성공 시 자동 닫기
@if(session('success'))
(function() {
    if (window.parent && window.parent.closeWeeklyReportPopup) {
        window.parent.closeWeeklyReportPopup(true);
    }
})();
@endif
// 팝업 모드 — submitForm 재정의: 다운로드는 부모에서 열기
function submitForm(action) {
    document.getElementById('summary-hidden').value       = quill.root.innerHTML;
    document.getElementById('current-tasks-json').value  = JSON.stringify(currentTasks.map(({ _id, ...r }) => r));
    document.getElementById('next-week-tasks-json').value = JSON.stringify(nextWeekTasks.map(({ _id, ...r }) => r));
    document.getElementById('action-field').value = action;
    document.getElementById('report-form').submit();
}
</script>
@endpush
@endif
