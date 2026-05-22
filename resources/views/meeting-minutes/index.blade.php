@extends('layouts.app')

@section('title', __('maintenance.meeting_minutes'))

@section('header-actions')@endsection

@section('content')
<div style="padding:24px 0;">

    {{-- session('success') 는 전역 토스트(window.appToast)로 표시됨 --}}

    {{-- 액션 바 --}}
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-bottom:16px;">
        <button onclick="openScheduleModal()"
            style="display:inline-flex;align-items:center;gap:8px;padding:8px 16px;background:#fff;color:var(--t600);border:1.5px solid var(--t300);border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;"
            onmouseover="this.style.background='var(--t50)';this.style.borderColor='var(--t500)'" onmouseout="this.style.background='#fff';this.style.borderColor='var(--t300)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            {{ __('maintenance.meeting_schedule') }}
        </button>
        <button onclick="openMeetingModal()"
            style="display:inline-flex;align-items:center;gap:8px;padding:8px 18px;background:var(--t600);color:#fff;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:background .15s;"
            onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('maintenance.meeting_new') }}
        </button>
    </div>

    {{-- 통계 카드 --}}
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;margin-bottom:24px;">
        @foreach([
            ['label'=>__('maintenance.stat_total'),      'value'=>$stats['total'],     'color'=>'var(--t600)'],
            ['label'=>__('maintenance.stat_this_month'), 'value'=>$stats['month'],     'color'=>'#3b82f6'],
            ['label'=>__('maintenance.scheduled_meeting'),  'value'=>$stats['scheduled'] ?? 0, 'color'=>'#f97316'],
            ['label'=>__('maintenance.stat_general'),    'value'=>$stats['general'],   'color'=>'#10b981'],
            ['label'=>__('maintenance.stat_project'),    'value'=>$stats['project'],   'color'=>'#f59e0b'],
        ] as $s)
        <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:12px;padding:16px 20px;box-shadow:0 1px 6px rgba(109,92,231,.06);">
            <div style="font-size:22px;font-weight:800;color:{{ $s['color'] }};">{{ $s['value'] }}</div>
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-top:2px;">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- 필터 --}}
    <form method="GET" style="background:#fff;border:1px solid var(--color-border-default);border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
        <select name="status" onchange="this.form.submit()" style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
            <option value="">{{ __('maintenance.filter_all_statuses') }}</option>
            <option value="scheduled" {{ request('status')==='scheduled'?'selected':'' }}>{{ __('maintenance.status_scheduled') }}</option>
            <option value="completed" {{ request('status')==='completed'?'selected':'' }}>{{ __('maintenance.status_completed_meeting') }}</option>
        </select>
        <select name="type" onchange="this.form.submit()" style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
            <option value="">{{ __('maintenance.filter_all_types') }}</option>
            <option value="general" {{ request('type')==='general'?'selected':'' }}>{{ __('maintenance.filter_general') }}</option>
            <option value="project" {{ request('type')==='project'?'selected':'' }}>{{ __('maintenance.filter_project') }}</option>
        </select>
        <select name="project_id" onchange="this.form.submit()" style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
            <option value="">{{ __('maintenance.filter_all_projects') }}</option>
            @foreach($projects as $proj)
            <option value="{{ $proj->id }}" {{ request('project_id')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
        <span style="font-size:12px;color:var(--color-text-tertiary);">~</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('maintenance.search_title') }}"
               style="flex:1;min-width:150px;padding:7px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:var(--color-text-primary);">
        <button type="submit" style="padding:7px 16px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.search') }}</button>
        @if(request()->anyFilled(['status','type','project_id','date_from','date_to','search']))
        <a href="{{ route('meeting-minutes.index') }}" style="padding:7px 12px;font-size:13px;color:var(--color-text-tertiary);text-decoration:none;">{{ __('common.reset') }}</a>
        @endif
    </form>

    {{-- 목록 --}}
    @forelse($minutes as $minute)
    <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:12px;padding:18px 20px;margin-bottom:10px;box-shadow:0 1px 6px rgba(109,92,231,.05);transition:box-shadow .15s;"
         onmouseover="this.style.boxShadow='0 4px 16px rgba(109,92,231,.1)'" onmouseout="this.style.boxShadow='0 1px 6px rgba(109,92,231,.05)'">
        <div style="display:flex;align-items:flex-start;gap:12px;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    @if($minute->isScheduled())
                    <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;background:#ffedd5;color:#c2410c;display:inline-flex;align-items:center;gap:4px;">
                        <svg width="10" height="10" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3M3 12a9 9 0 1018 0 9 9 0 00-18 0z"/></svg>
                        {{ __('maintenance.status_scheduled') }}
                    </span>
                    @endif
                    <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;
                        {{ $minute->type==='project' ? 'background:var(--t100);color:var(--t600);' : 'background:#dcfce7;color:var(--color-alert-success-500);' }}">
                        {{ $minute->type_label }}
                    </span>
                    @if($minute->project)
                    <span style="font-size:11px;color:var(--t600);background:var(--t50);padding:2px 8px;border-radius:6px;">{{ $minute->project->name }}</span>
                    @endif
                    @if($minute->weekly_department)
                    <span style="font-size:11px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $minute->weekly_department }}</span>
                    @endif
                </div>
                <a href="#" onclick="event.preventDefault(); openMinutePopup({{ $minute->id }})"
                   style="font-size:15px;font-weight:700;color:var(--color-text-primary);text-decoration:none;display:block;margin-bottom:6px;"
                   onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#1e1b2e'">
                    {{ $minute->title }}
                </a>
                <div style="display:flex;align-items:center;gap:16px;font-size:12px;color:var(--color-text-tertiary);flex-wrap:wrap;">
                    <span>📅 {{ $minute->meeting_date->format('Y.m.d H:i') }}</span>
                    @if($minute->location)
                    <span>📍 {{ $minute->display_location }}</span>
                    @endif
                    <span>✍️ {{ $minute->author->name }}</span>
                    <span>👥 {{ __('maintenance.attendees_count', ['count' => $minute->attendees->count()]) }}</span>
                    @if($minute->actionItems->count())
                    <span style="color:var(--t600);font-weight:600;">⚡ {{ __('maintenance.action_count', ['count' => $minute->actionItems->count()]) }}</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                @if($minute->isScheduled() && (auth()->id() === $minute->author_id || auth()->user()->isAdmin()))
                <button onclick="openEditModal({{ $minute->id }})"
                        style="display:inline-flex;align-items:center;gap:4px;padding:6px 10px;background:var(--t600);color:#fff;border:none;border-radius:7px;font-size:11.5px;font-weight:600;cursor:pointer;"
                        title="{{ __('maintenance.minute_write_tooltip') }}">
                    <svg width="11" height="11" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    {{ __('maintenance.minute_write') }}
                </button>
                @endif
                @if(auth()->id() === $minute->author_id || auth()->user()->isAdmin())
                <button onclick="openEditModal({{ $minute->id }})"
                        style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1.5px solid #e8e3ff;background:#fff;color:var(--color-text-tertiary);cursor:pointer;transition:all .12s;"
                        title="{{ __('common.edit') }}"
                        onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'" onmouseout="this.style.borderColor='#e8e3ff';this.style.color='#94a3b8'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                @endif
                <a href="#" onclick="event.preventDefault(); openMinutePopup({{ $minute->id }})"
                   style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;color:var(--color-text-tertiary);text-decoration:none;transition:background .12s,color .12s;"
                   onmouseover="this.style.background='var(--t50)';this.style.color='var(--t600)'" onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div style="background:#fff;border:1px solid var(--color-border-default);border-radius:16px;padding:60px 20px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <div style="font-size:15px;font-weight:600;color:var(--color-text-primary);margin-bottom:6px;">{{ __('maintenance.meeting_empty') }}</div>
        <div style="font-size:13px;color:var(--color-text-tertiary);margin-bottom:20px;">{{ __('maintenance.meeting_empty_hint') }}</div>
        <button onclick="openMeetingModal()"
                style="display:inline-flex;align-items:center;gap:8px;padding:9px 20px;background:var(--t600);color:#fff;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;">
            {{ __('maintenance.meeting_new_write') }}
        </button>
    </div>
    @endforelse

    {{-- 페이지네이션 --}}
    @if($minutes->hasPages())
    <div style="margin-top:20px;">{{ $minutes->links() }}</div>
    @endif
</div>

{{-- ============================================================
     회의록 팝업 모달
     ============================================================ --}}
<div id="meeting-modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(15,10,40,.55);z-index:1000;padding:32px 16px;align-items:flex-start;justify-content:center;overflow-y:auto;"
     onclick="if(event.target===this)closeMeetingModal()">
    <div style="background:#fff;border-radius:16px;max-width:1080px;width:100%;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.2);display:flex;flex-direction:column;max-height:calc(100vh - 64px);">

        {{-- 헤더 --}}
        <div style="padding:20px 24px;border-bottom:1px solid var(--color-border-default);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <h2 id="modal-title" style="font-size:16px;font-weight:700;color:var(--color-text-primary);margin:0;"></h2>
            <button onclick="closeMeetingModal()"
                    style="width:30px;height:30px;border-radius:8px;border:none;background:#f8f5ff;color:var(--color-text-tertiary);cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;line-height:1;"
                    onmouseover="this.style.background='#ede9fe';this.style.color='#7c3aed'" onmouseout="this.style.background='#f8f5ff';this.style.color='#94a3b8'">×</button>
        </div>

        {{-- 폼 --}}
        <form id="meeting-modal-form" method="POST" onsubmit="submitMeetingForm(event)"
              style="display:flex;flex-direction:column;flex:1;overflow:hidden;min-height:0;">
            @csrf
            <input type="hidden" name="_method" id="modal-method" value="">

            <div style="overflow-y:auto;flex:1;padding:20px 24px;">

                {{-- 유효성 오류 --}}
                <div id="modal-errors" style="display:none;margin-bottom:16px;background:var(--color-bg-danger-subtle);border:1px solid #fecaca;border-radius:10px;padding:12px 16px;">
                    <ul id="modal-errors-list" style="margin:0;padding:0 0 0 16px;font-size:13px;color:var(--color-alert-warning-500);"></ul>
                </div>

                {{-- 2열 레이아웃: 좌(기본 정보) / 우(참석자 + 회의 내용) --}}
                <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-start;">

                {{-- 좌측 컬럼 --}}
                <div style="flex:1;min-width:300px;">
                {{-- 기본 정보 --}}
                <div style="background:#faf8ff;border:1px solid var(--color-border-default);border-radius:12px;padding:18px;">
                    <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--color-border-default);">{{ __('maintenance.form_basic_info') }}</div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_meeting_title') }} <span style="color:var(--color-alert-warning-500);">*</span></label>
                        <input type="text" name="title" id="modal-title-input" required
                               style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                               placeholder="{{ __('maintenance.form_meeting_title_ph') }}"
                               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_project') }}</label>
                        <select name="project_id" id="modal-project-id"
                                style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                            <option value="">{{ __('maintenance.form_project_none') }}</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_meeting_date') }} <span style="color:var(--color-alert-warning-500);">*</span></label>
                        <input type="hidden" name="meeting_date" id="modal-meeting-date">
                        <div style="display:flex;gap:8px;">
                            <input type="date" id="modal-meeting-date-d" required
                                   style="flex:1;min-width:0;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"
                                   oninput="syncMeetingDate()">
                            <input type="time" id="modal-meeting-date-t" required step="60"
                                   style="flex:1;min-width:0;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"
                                   oninput="syncMeetingDate()">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_project_code') }}</label>
                            <input type="text" name="project_code" id="modal-project-code"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                                   placeholder="{{ __('maintenance.form_project_code_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_weekly_dept') }}</label>
                            <input type="text" name="weekly_department" id="modal-weekly-dept"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                                   placeholder="{{ __('maintenance.form_weekly_dept_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_location') }}</label>
                        <input type="text" name="location" id="modal-location"
                               style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;box-sizing:border-box;"
                               placeholder="{{ __('maintenance.form_location_ph') }}"
                               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    </div>
                </div>

                </div>{{-- /좌측 컬럼 --}}

                {{-- 우측 컬럼 --}}
                <div style="flex:1;min-width:300px;display:flex;flex-direction:column;gap:12px;">
                {{-- 참석자 (자동완성 멀티선택) --}}
                <div style="background:#faf8ff;border:1px solid var(--color-border-default);border-radius:12px;padding:18px;">
                    <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--color-border-default);">{{ __('maintenance.form_attendees') }}</div>

                    <div id="modal-attendee-wrap" style="position:relative;">
                        <div id="modal-attendee-control"
                             style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;padding:7px 9px;border:1.5px solid #e8e3ff;border-radius:9px;background:#fff;min-height:42px;cursor:text;"
                             onclick="document.getElementById('modal-attendee-input').focus()">
                            <div id="modal-attendee-chips" style="display:contents;"></div>
                            <input id="modal-attendee-input" type="text" autocomplete="off"
                                   placeholder="{{ __('maintenance.attendee_search_ph') }}"
                                   style="flex:1;min-width:140px;border:none;outline:none;background:transparent;font-size:13px;color:var(--color-text-primary);padding:4px;"
                                   oninput="onAttendeeSearch()" onfocus="onAttendeeSearch()" onkeydown="onAttendeeKeydown(event)">
                        </div>
                        <div id="modal-attendee-dropdown"
                             style="display:none;position:absolute;left:0;right:0;top:calc(100% + 4px);background:#fff;border:1px solid #e8e3ff;border-radius:9px;box-shadow:0 8px 24px rgba(15,23,42,.08);max-height:220px;overflow-y:auto;z-index:50;"></div>
                    </div>
                    <div style="font-size:11px;color:var(--color-text-tertiary);margin-top:6px;">{{ __('maintenance.attendee_external_hint') }}</div>
                </div>

                {{-- 회의 내용 --}}
                <div id="modal-content-section" style="background:#faf8ff;border:1px solid var(--color-border-default);border-radius:12px;padding:18px;">
                    <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--color-border-default);">{{ __('maintenance.form_meeting_content') }}</div>
                    <div style="margin-bottom:14px;">
                        <div class="mm-field-head">
                            <label style="font-size:12px;font-weight:600;color:#64748b;">{{ __('maintenance.agenda') }} (Agenda)</label>
                            <button type="button" class="mm-refine-btn" onclick="mmRefine('modal-agenda','agenda',this)">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                웍스 정제
                            </button>
                        </div>
                        <textarea name="agenda" id="modal-agenda" rows="3"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_agenda_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                    <div class="modal-discussion-block" style="margin-bottom:14px;">
                        <div class="mm-field-head">
                            <label style="font-size:12px;font-weight:600;color:#64748b;">{{ __('maintenance.discussion') }} (Discussion)</label>
                            <button type="button" class="mm-refine-btn" onclick="mmRefine('modal-discussion','discussion',this)">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                웍스 정제
                            </button>
                        </div>
                        <textarea name="discussion" id="modal-discussion" rows="5"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_discussion_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                    <div class="modal-discussion-block">
                        <div class="mm-field-head">
                            <label style="font-size:12px;font-weight:600;color:#64748b;">{{ __('maintenance.decisions') }} (Decisions)</label>
                            <button type="button" class="mm-refine-btn" onclick="mmRefine('modal-decisions','decisions',this)">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                                웍스 정제
                            </button>
                        </div>
                        <textarea name="decisions" id="modal-decisions" rows="3"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:var(--color-text-primary);outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_decisions_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                </div>

                {{-- Action Items --}}
                <div id="modal-actionitem-section" style="background:#faf8ff;border:1px solid var(--color-border-default);border-radius:12px;padding:18px;">
                    <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--color-border-default);">Action Items</div>
                    <div id="modal-actionitem-list"></div>
                    <button type="button" onclick="modalAddActionItem()"
                            style="display:inline-flex;align-items:center;gap:4px;padding:6px 12px;border:1.5px dashed var(--t300);border-radius:8px;background:transparent;color:var(--t600);font-size:12px;font-weight:600;cursor:pointer;margin-top:4px;">
                        {{ __('maintenance.action_item_add_btn') }}
                    </button>
                </div>
                </div>{{-- /우측 컬럼 --}}

                </div>{{-- /2열 레이아웃 --}}

            </div>{{-- /스크롤 영역 --}}

            {{-- 푸터 --}}
            <div style="padding:16px 24px;border-top:1px solid var(--color-border-default);display:flex;gap:12px;justify-content:flex-end;background:#faf8ff;border-radius:0 0 16px 16px;flex-shrink:0;">
                <button type="button" onclick="closeMeetingModal()"
                        style="padding:9px 20px;border:1.5px solid #e8e3ff;border-radius:9px;font-size:13px;font-weight:600;color:#64748b;background:#fff;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit" id="modal-submit-btn"
                        style="padding:9px 24px;background:var(--t600);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:700;cursor:pointer;transition:background .15s;"
                        onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">{{ __('common.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
const STORE_URL    = '{{ route('meeting-minutes.store') }}';
const SCHEDULE_URL = '{{ route('meeting-minutes.schedule.store') }}';
const JSON_URL     = '{{ url('meeting-minutes') }}';
const INP_STYLE  = 'width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;';
const STR_DIRECT = '{{ __('maintenance.form_attendee_direct') }}';
const STR_NAME   = '{{ __('maintenance.form_attendee_name_ph') }}';

// 사용자 노출 텍스트 번역
const T = {
    actionItemTaskPh: @json(__('maintenance.action_item_task_ph')),
    ownerUnassigned:  @json(__('maintenance.owner_unassigned')),
    priorityHigh:     @json(__('maintenance.action_priority_high')),
    priorityMedium:   @json(__('maintenance.action_priority_medium')),
    priorityLow:      @json(__('maintenance.action_priority_low')),
    attendeeManualLabel: @json(__('maintenance.attendee_manual_label')),
    attendeeRemove:   @json(__('maintenance.attendee_remove')),
    attendeeManualAdd: @json(__('maintenance.attendee_manual_add')),
    meetingCreate:    @json(__('maintenance.meeting_create')),
    meetingSchedule:  @json(__('maintenance.meeting_schedule')),
    meetingEdit:      @json(__('maintenance.meeting_edit')),
};

const TM_OPTIONS = `<option value="">${STR_DIRECT}</option>` +
    `@foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}@if($tm->email) ({{ $tm->email }})@endif</option>@endforeach`;

const TEAMMATE_DATA = @json($teammates->map(fn($t) => ['id' => $t->id, 'name' => $t->name, 'email' => $t->email]));

const OWNER_OPTIONS = `<option value="">${T.ownerUnassigned}</option>` +
    `@foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}</option>@endforeach`;

// Action Item 행 상태
let actionItemIdx = 0;

function modalAddActionItem(data) {
    data = data || {};
    const i = actionItemIdx++;
    const row = document.createElement('div');
    row.className = 'modal-actionitem-row';
    row.style.cssText = 'border:1px solid #e8e3ff;border-radius:9px;padding:10px;margin-bottom:8px;background:#fff;';
    const inp = 'padding:7px 9px;border:1.5px solid #e8e3ff;border-radius:7px;font-size:12px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;';
    row.innerHTML = `
        <input type="hidden" name="action_items[${i}][id]" value="${data.id || ''}">
        <div style="display:flex;gap:8px;margin-bottom:6px;">
            <input type="text" name="action_items[${i}][title]" placeholder="${T.actionItemTaskPh}"
                   value="${escAttHtml(data.title || '')}" style="flex:1;min-width:0;${inp}">
            <button type="button" onclick="this.closest('.modal-actionitem-row').remove()"
                    style="width:28px;flex-shrink:0;border:1.5px solid #fecaca;background:#fff;border-radius:7px;color:var(--color-alert-warning-500);cursor:pointer;font-size:15px;">&times;</button>
        </div>
        <div style="display:flex;gap:8px;">
            <select name="action_items[${i}][owner_id]" class="ai-owner" style="flex:1;min-width:0;${inp}">${OWNER_OPTIONS}</select>
            <input type="date" name="action_items[${i}][due_date]" value="${data.due_date || ''}" style="flex:1;min-width:0;${inp}">
            <select name="action_items[${i}][priority]" class="ai-priority" style="width:74px;flex-shrink:0;${inp}">
                <option value="high">${T.priorityHigh}</option>
                <option value="medium">${T.priorityMedium}</option>
                <option value="low">${T.priorityLow}</option>
            </select>
        </div>
    `;
    document.getElementById('modal-actionitem-list').appendChild(row);
    if (data.owner_id) row.querySelector('.ai-owner').value = data.owner_id;
    row.querySelector('.ai-priority').value = data.priority || 'medium';
}

function clearActionItems() {
    document.getElementById('modal-actionitem-list').innerHTML = '';
    actionItemIdx = 0;
}

// 참석자 자동완성 멀티선택 상태
let selectedAttendees = [];  // [{ user_id, name, email }, ...]
let attendeeHighlightIdx = -1;

function escAttHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function renderAttendeeChips() {
    const box = document.getElementById('modal-attendee-chips');
    box.innerHTML = '';
    selectedAttendees.forEach((a, idx) => {
        const chip = document.createElement('span');
        chip.style.cssText = 'display:inline-flex;align-items:center;gap:6px;padding:3px 4px 3px 9px;background:var(--t50);border:1px solid var(--t200);border-radius:8px;font-size:12px;color:var(--t700);max-width:100%;';
        const isManual = !a.user_id;
        const meta = a.email ? ` (${a.email})` : (isManual ? ` · ${T.attendeeManualLabel}` : '');
        chip.innerHTML = `
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600;">${escAttHtml(a.name)}</span>
            <span style="color:var(--color-text-tertiary);font-weight:500;">${escAttHtml(meta)}</span>
            <button type="button" data-att-idx="${idx}" aria-label="${T.attendeeRemove}"
                style="border:none;background:transparent;color:var(--t600);cursor:pointer;font-size:14px;line-height:1;padding:0 4px;">×</button>
        `;
        chip.querySelector('button').addEventListener('click', (e) => {
            e.stopPropagation();
            removeAttendee(idx);
        });
        box.appendChild(chip);
    });
}

function removeAttendee(idx) {
    selectedAttendees.splice(idx, 1);
    renderAttendeeChips();
    onAttendeeSearch();
}

function clearAttendees() {
    selectedAttendees = [];
    renderAttendeeChips();
    const inp = document.getElementById('modal-attendee-input');
    if (inp) inp.value = '';
    const dd = document.getElementById('modal-attendee-dropdown');
    if (dd) dd.style.display = 'none';
}

function attendeeMatches(query) {
    const q = query.trim().toLowerCase();
    return TEAMMATE_DATA
        .filter(t => !selectedAttendees.some(a => a.user_id === t.id))
        .filter(t => !q
            || t.name.toLowerCase().includes(q)
            || (t.email || '').toLowerCase().includes(q))
        .slice(0, 12);
}

function renderAttendeeDropdown() {
    const dd = document.getElementById('modal-attendee-dropdown');
    const input = document.getElementById('modal-attendee-input');
    const q = input.value;
    const matches = attendeeMatches(q);

    dd.innerHTML = '';
    attendeeHighlightIdx = matches.length ? 0 : -1;

    if (matches.length === 0 && q.trim()) {
        const item = document.createElement('div');
        item.style.cssText = 'padding:8px 12px;font-size:13px;color:var(--t700);cursor:pointer;';
        item.textContent = T.attendeeManualAdd.replace(':name', q.trim());
        item.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); addAttendeeManual(q.trim()); });
        dd.appendChild(item);
        dd.style.display = 'block';
        return;
    }

    if (matches.length === 0) {
        dd.style.display = 'none';
        return;
    }

    matches.forEach((t, i) => {
        const item = document.createElement('div');
        item.dataset.userId = t.id;
        item.style.cssText = `padding:8px 12px;font-size:13px;color:#1e1b2e;cursor:pointer;background:${i===0?'var(--t50)':'#fff'};`;
        item.innerHTML = `<span style="font-weight:600;">${escAttHtml(t.name)}</span>${t.email ? ` <span style="color:var(--color-text-tertiary);font-size:11.5px;">${escAttHtml(t.email)}</span>` : ''}`;
        item.addEventListener('mousedown', (e) => { e.preventDefault(); e.stopPropagation(); addAttendeeFromTeam(t.id); });
        item.addEventListener('mouseenter', () => setHighlight(i));
        dd.appendChild(item);
    });
    dd.style.display = 'block';
}

function setHighlight(idx) {
    const dd = document.getElementById('modal-attendee-dropdown');
    [...dd.children].forEach((el, i) => el.style.background = i === idx ? 'var(--t50)' : '#fff');
    attendeeHighlightIdx = idx;
}

function onAttendeeSearch() {
    renderAttendeeDropdown();
}

function onAttendeeKeydown(e) {
    const dd = document.getElementById('modal-attendee-dropdown');
    const input = e.target;
    const items = dd ? [...dd.children] : [];

    if (e.key === 'Backspace' && !input.value && selectedAttendees.length) {
        e.preventDefault();
        removeAttendee(selectedAttendees.length - 1);
        return;
    }
    if (e.key === 'ArrowDown' && items.length) {
        e.preventDefault();
        setHighlight(Math.min(attendeeHighlightIdx + 1, items.length - 1));
        items[attendeeHighlightIdx]?.scrollIntoView({ block: 'nearest' });
        return;
    }
    if (e.key === 'ArrowUp' && items.length) {
        e.preventDefault();
        setHighlight(Math.max(attendeeHighlightIdx - 1, 0));
        items[attendeeHighlightIdx]?.scrollIntoView({ block: 'nearest' });
        return;
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        const q = input.value.trim();
        if (items.length && attendeeHighlightIdx >= 0 && items[attendeeHighlightIdx]?.dataset.userId) {
            addAttendeeFromTeam(parseInt(items[attendeeHighlightIdx].dataset.userId, 10));
        } else if (q) {
            addAttendeeManual(q);
        }
        return;
    }
    if (e.key === 'Escape') {
        dd.style.display = 'none';
    }
}

function addAttendeeFromTeam(userId) {
    const t = TEAMMATE_DATA.find(t => t.id === userId);
    if (!t) return;
    if (selectedAttendees.some(a => a.user_id === t.id)) return;
    selectedAttendees.push({ user_id: t.id, name: t.name, email: t.email });
    finishAttendeeAdd();
}

function addAttendeeManual(name) {
    if (!name) return;
    if (selectedAttendees.some(a => !a.user_id && a.name === name)) return;
    selectedAttendees.push({ user_id: null, name, email: null });
    finishAttendeeAdd();
}

function finishAttendeeAdd() {
    document.getElementById('modal-attendee-input').value = '';
    renderAttendeeChips();
    renderAttendeeDropdown();
    document.getElementById('modal-attendee-input').focus();
}

// 외부 클릭 시 드롭다운 닫기
document.addEventListener('mousedown', (e) => {
    const wrap = document.getElementById('modal-attendee-wrap');
    if (!wrap) return;
    if (!wrap.contains(e.target)) {
        const dd = document.getElementById('modal-attendee-dropdown');
        if (dd) dd.style.display = 'none';
    }
});

function buildAttendeeHiddenInputs(form) {
    form.querySelectorAll('.att-hidden-input').forEach(el => el.remove());
    selectedAttendees.forEach((a, i) => {
        const hidU = document.createElement('input');
        hidU.type = 'hidden';
        hidU.className = 'att-hidden-input';
        hidU.name = `attendees[${i}][user_id]`;
        hidU.value = a.user_id ?? '';
        const hidN = document.createElement('input');
        hidN.type = 'hidden';
        hidN.className = 'att-hidden-input';
        hidN.name = `attendees[${i}][name]`;
        hidN.value = a.name ?? '';
        form.appendChild(hidU);
        form.appendChild(hidN);
    });
}

function syncMeetingDate() {
    const d = document.getElementById('modal-meeting-date-d').value;
    const t = document.getElementById('modal-meeting-date-t').value;
    document.getElementById('modal-meeting-date').value = (d && t) ? `${d}T${t}` : '';
}

function setMeetingDateFromIso(iso) {
    if (!iso) {
        document.getElementById('modal-meeting-date-d').value = '';
        document.getElementById('modal-meeting-date-t').value = '';
        document.getElementById('modal-meeting-date').value  = '';
        return;
    }
    const [d, t] = String(iso).split('T');
    const timePart = (t || '').slice(0, 5); // HH:MM
    document.getElementById('modal-meeting-date-d').value = d || '';
    document.getElementById('modal-meeting-date-t').value = timePart;
    document.getElementById('modal-meeting-date').value  = (d && timePart) ? `${d}T${timePart}` : '';
}

function resetMeetingModal() {
    document.getElementById('modal-title-input').value = '';
    setMeetingDateFromIso('{{ now()->format('Y-m-d\TH:i') }}');
    document.getElementById('modal-project-id').value = '';
    document.getElementById('modal-project-code').value = '';
    document.getElementById('modal-weekly-dept').value = '';
    document.getElementById('modal-location').value = '';
    document.getElementById('modal-agenda').value = '';
    document.getElementById('modal-discussion').value = '';
    document.getElementById('modal-decisions').value = '';
    document.getElementById('modal-errors').style.display = 'none';
    clearAttendees();
    clearActionItems();
}

function setDiscussionBlocksVisible(visible) {
    document.querySelectorAll('.modal-discussion-block').forEach(el => el.style.display = visible ? '' : 'none');
    const aiSection = document.getElementById('modal-actionitem-section');
    if (aiSection) aiSection.style.display = visible ? '' : 'none';
}

function openMeetingModal() {
    document.getElementById('modal-title').textContent = T.meetingCreate;
    document.getElementById('modal-method').value = '';
    document.getElementById('meeting-modal-form').action = STORE_URL;

    setDiscussionBlocksVisible(true);
    resetMeetingModal();

    document.getElementById('meeting-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function openScheduleModal() {
    document.getElementById('modal-title').textContent = T.meetingSchedule;
    document.getElementById('modal-method').value = '';
    document.getElementById('meeting-modal-form').action = SCHEDULE_URL;

    setDiscussionBlocksVisible(false);
    resetMeetingModal();

    // 예정 회의 기본값: 1시간 뒤로 설정
    const now = new Date(Date.now() + 60 * 60 * 1000);
    const pad = n => String(n).padStart(2, '0');
    setMeetingDateFromIso(
        `${now.getFullYear()}-${pad(now.getMonth()+1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`
    );

    document.getElementById('meeting-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

async function openEditModal(minuteId) {
    document.getElementById('modal-errors').style.display = 'none';

    const res = await fetch(`${JSON_URL}/${minuteId}/json`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!res.ok) return;
    const d = await res.json();

    document.getElementById('modal-title').textContent = T.meetingEdit;
    document.getElementById('modal-method').value = 'PATCH';
    document.getElementById('meeting-modal-form').action = `${JSON_URL}/${minuteId}`;

    // edit 모드에서는 항상 회의 내용 영역 표시 (예정 회의를 회의록으로 전환)
    setDiscussionBlocksVisible(true);

    document.getElementById('modal-title-input').value = d.title || '';
    setMeetingDateFromIso(d.meeting_date || '');
    document.getElementById('modal-project-id').value = d.project_id || '';
    document.getElementById('modal-project-code').value = d.project_code || '';
    document.getElementById('modal-weekly-dept').value = d.weekly_department || '';
    // Outlook/Teams 자동 입력 "Name <email>" 형식이면 이름만 추출
    (function() {
        let loc = d.location || '';
        const m = loc.match(/^(.+?)\s*<\s*[^<>\s]+@[^<>\s]+\s*>\s*$/);
        if (m) loc = m[1].trim();
        document.getElementById('modal-location').value = loc;
    })();
    document.getElementById('modal-agenda').value = d.agenda || '';
    document.getElementById('modal-discussion').value = d.discussion || '';
    document.getElementById('modal-decisions').value = d.decisions || '';

    // 참석자 칩 복원
    selectedAttendees = [];
    if (d.attendees && d.attendees.length) {
        d.attendees.forEach(a => {
            const t = a.user_id ? TEAMMATE_DATA.find(t => t.id === a.user_id) : null;
            selectedAttendees.push({
                user_id: a.user_id || null,
                name:    a.name || t?.name || '',
                email:   t?.email || null,
            });
        });
    }
    renderAttendeeChips();

    // Action Item 복원
    clearActionItems();
    if (d.action_items && d.action_items.length) {
        d.action_items.forEach(ai => modalAddActionItem(ai));
    }

    document.getElementById('meeting-modal-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMeetingModal() {
    document.getElementById('meeting-modal-overlay').style.display = 'none';
    document.body.style.overflow = '';
}


async function submitMeetingForm(e) {
    e.preventDefault();
    const form = document.getElementById('meeting-modal-form');
    const btn  = document.getElementById('modal-submit-btn');
    const origText = btn.textContent;

    buildAttendeeHiddenInputs(form);

    btn.disabled = true;
    btn.textContent = '...';

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        });

        if (res.ok) {
            closeMeetingModal();
            window.location.reload();
            return;
        }

        if (res.status === 422) {
            const json = await res.json();
            const msgs = Object.values(json.errors || {}).flat();
            const list = document.getElementById('modal-errors-list');
            list.innerHTML = msgs.map(m => `<li>${m}</li>`).join('');
            document.getElementById('modal-errors').style.display = 'block';
            document.getElementById('meeting-modal-overlay').scrollTop = 0;
        }
    } finally {
        btn.disabled = false;
        btn.textContent = origText;
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeMeetingModal();
        closeMinutePopup(false);
    }
});
</script>

{{-- ============================================================
     회의록 상세 팝업 (iframe)
     ============================================================ --}}
<div id="minute-popup-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(15,10,40,.6);z-index:2000;align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this)closeMinutePopup(false)">
    <div style="background:#fff;border-radius:16px;width:min(1060px,100%);height:calc(100vh - 40px);display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden;">
        <iframe id="minute-popup-frame"
                src=""
                style="width:100%;flex:1;border:none;border-radius:16px;"
                allowfullscreen>
        </iframe>
    </div>
</div>

<script>
const MINUTE_POPUP_BASE = '{{ url('meeting-minutes') }}';

function openMinutePopup(id) {
    const overlay = document.getElementById('minute-popup-overlay');
    const frame   = document.getElementById('minute-popup-frame');
    frame.src = MINUTE_POPUP_BASE + '/' + id + '/popup';
    overlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeMinutePopup(reload) {
    const overlay = document.getElementById('minute-popup-overlay');
    if (overlay.style.display === 'none') return;
    overlay.style.display = 'none';
    document.getElementById('minute-popup-frame').src = '';
    document.body.style.overflow = '';
    if (reload) window.location.reload();
}

// 상세 팝업(iframe) 내부의 '수정' 버튼 → 상세 팝업을 닫고 동일 페이지의 수정 모달을 연다
function editMinuteFromPopup(id) {
    closeMinutePopup(false);
    openEditModal(id);
}

// 진입 시 쿼리 파라미터로 회의록 모달 자동 오픈 (?new=1 작성 / ?edit={id} 수정)
(function () {
    const p = new URLSearchParams(location.search);
    if (p.get('new') === '1') {
        openMeetingModal();
    } else if (p.get('edit')) {
        const id = parseInt(p.get('edit'), 10);
        if (id) openEditModal(id);
    }
})();
</script>

@include('meeting-minutes._refine')
@endsection
