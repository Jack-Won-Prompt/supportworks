@extends('layouts.app')

@section('title', __('maintenance.meeting_minutes'))

@section('header-actions')@endsection

@section('content')
<div style="padding:24px 0;">

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #bbf7d0;border-radius:10px;padding:10px 16px;margin-bottom:16px;font-size:13px;color:#16a34a;">
        {{ session('success') }}
    </div>
    @endif

    {{-- 액션 바 --}}
    <div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
        <button onclick="openMeetingModal()"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 18px;background:var(--t600);color:#fff;border-radius:8px;font-size:13px;font-weight:600;border:none;cursor:pointer;transition:background .15s;"
            onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('maintenance.meeting_new') }}
        </button>
    </div>

    {{-- 통계 카드 --}}
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px;">
        @foreach([
            ['label'=>__('maintenance.stat_total'),      'value'=>$stats['total'],   'color'=>'var(--t600)'],
            ['label'=>__('maintenance.stat_this_month'), 'value'=>$stats['month'],   'color'=>'#3b82f6'],
            ['label'=>__('maintenance.stat_general'),    'value'=>$stats['general'], 'color'=>'#10b981'],
            ['label'=>__('maintenance.stat_project'),    'value'=>$stats['project'], 'color'=>'#f59e0b'],
        ] as $s)
        <div style="background:#fff;border:1px solid #f0eeff;border-radius:12px;padding:16px 20px;box-shadow:0 1px 6px rgba(109,92,231,.06);">
            <div style="font-size:22px;font-weight:800;color:{{ $s['color'] }};">{{ $s['value'] }}</div>
            <div style="font-size:12px;color:#94a3b8;margin-top:2px;">{{ $s['label'] }}</div>
        </div>
        @endforeach
    </div>

    {{-- 필터 --}}
    <form method="GET" style="background:#fff;border:1px solid #f0eeff;border-radius:12px;padding:14px 16px;margin-bottom:20px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <select name="type" onchange="this.form.submit()" style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#1e1b2e;">
            <option value="">{{ __('maintenance.filter_all_types') }}</option>
            <option value="general" {{ request('type')==='general'?'selected':'' }}>{{ __('maintenance.filter_general') }}</option>
            <option value="project" {{ request('type')==='project'?'selected':'' }}>{{ __('maintenance.filter_project') }}</option>
        </select>
        <select name="project_id" onchange="this.form.submit()" style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#1e1b2e;">
            <option value="">{{ __('maintenance.filter_all_projects') }}</option>
            @foreach($projects as $proj)
            <option value="{{ $proj->id }}" {{ request('project_id')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}"
               style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#1e1b2e;">
        <span style="font-size:12px;color:#94a3b8;">~</span>
        <input type="date" name="date_to" value="{{ request('date_to') }}"
               style="padding:7px 10px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#1e1b2e;">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('maintenance.search_title') }}"
               style="flex:1;min-width:150px;padding:7px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;outline:none;background:#fff;color:#1e1b2e;">
        <button type="submit" style="padding:7px 16px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">{{ __('common.search') }}</button>
        @if(request()->anyFilled(['type','project_id','date_from','date_to','search']))
        <a href="{{ route('meeting-minutes.index') }}" style="padding:7px 12px;font-size:13px;color:#94a3b8;text-decoration:none;">{{ __('common.reset') }}</a>
        @endif
    </form>

    {{-- 목록 --}}
    @forelse($minutes as $minute)
    <div style="background:#fff;border:1px solid #f0eeff;border-radius:12px;padding:18px 20px;margin-bottom:10px;box-shadow:0 1px 6px rgba(109,92,231,.05);transition:box-shadow .15s;"
         onmouseover="this.style.boxShadow='0 4px 16px rgba(109,92,231,.1)'" onmouseout="this.style.boxShadow='0 1px 6px rgba(109,92,231,.05)'">
        <div style="display:flex;align-items:flex-start;gap:14px;">
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:6px;
                        {{ $minute->type==='project' ? 'background:#ede9fe;color:#7c3aed;' : 'background:#dcfce7;color:#16a34a;' }}">
                        {{ $minute->type_label }}
                    </span>
                    @if($minute->project)
                    <span style="font-size:11px;color:#7c3aed;background:#f5f3ff;padding:2px 8px;border-radius:6px;">{{ $minute->project->name }}</span>
                    @endif
                    @if($minute->weekly_department)
                    <span style="font-size:11px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ $minute->weekly_department }}</span>
                    @endif
                </div>
                <a href="#" onclick="event.preventDefault(); openMinutePopup({{ $minute->id }})"
                   style="font-size:15px;font-weight:700;color:#1e1b2e;text-decoration:none;display:block;margin-bottom:6px;"
                   onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#1e1b2e'">
                    {{ $minute->title }}
                </a>
                <div style="display:flex;align-items:center;gap:16px;font-size:12px;color:#94a3b8;flex-wrap:wrap;">
                    <span>📅 {{ $minute->meeting_date->format('Y.m.d H:i') }}</span>
                    @if($minute->location)
                    <span>📍 {{ $minute->location }}</span>
                    @endif
                    <span>✍️ {{ $minute->author->name }}</span>
                    <span>👥 {{ __('maintenance.attendees_count', ['count' => $minute->attendees->count()]) }}</span>
                    @if($minute->actionItems->count())
                    <span style="color:var(--t600);font-weight:600;">⚡ Action {{ $minute->actionItems->count() }}건</span>
                    @endif
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                @if(auth()->id() === $minute->author_id || auth()->user()->isAdmin())
                <button onclick="openEditModal({{ $minute->id }})"
                        style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1.5px solid #e8e3ff;background:#fff;color:#94a3b8;cursor:pointer;transition:all .12s;"
                        title="{{ __('common.edit') }}"
                        onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--t600)'" onmouseout="this.style.borderColor='#e8e3ff';this.style.color='#94a3b8'">
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                @endif
                <a href="#" onclick="event.preventDefault(); openMinutePopup({{ $minute->id }})"
                   style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;color:#94a3b8;text-decoration:none;transition:background .12s,color .12s;"
                   onmouseover="this.style.background='var(--t50)';this.style.color='var(--t600)'" onmouseout="this.style.background='transparent';this.style.color='#94a3b8'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    </div>
    @empty
    <div style="background:#fff;border:1px solid #f0eeff;border-radius:16px;padding:60px 20px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">📋</div>
        <div style="font-size:15px;font-weight:600;color:#1e1b2e;margin-bottom:6px;">{{ __('maintenance.meeting_empty') }}</div>
        <div style="font-size:13px;color:#94a3b8;margin-bottom:20px;">{{ __('maintenance.meeting_empty_hint') }}</div>
        <button onclick="openMeetingModal()"
                style="display:inline-flex;align-items:center;gap:6px;padding:9px 20px;background:var(--t600);color:#fff;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;">
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
    <div style="background:#fff;border-radius:16px;max-width:860px;width:100%;margin:0 auto;box-shadow:0 20px 60px rgba(0,0,0,.2);display:flex;flex-direction:column;max-height:calc(100vh - 64px);">

        {{-- 헤더 --}}
        <div style="padding:20px 24px;border-bottom:1px solid #f0eeff;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
            <h2 id="modal-title" style="font-size:16px;font-weight:700;color:#1e1b2e;margin:0;"></h2>
            <button onclick="closeMeetingModal()"
                    style="width:30px;height:30px;border-radius:8px;border:none;background:#f8f5ff;color:#94a3b8;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;line-height:1;"
                    onmouseover="this.style.background='#ede9fe';this.style.color='#7c3aed'" onmouseout="this.style.background='#f8f5ff';this.style.color='#94a3b8'">×</button>
        </div>

        {{-- 폼 --}}
        <form id="meeting-modal-form" method="POST" onsubmit="submitMeetingForm(event)"
              style="display:flex;flex-direction:column;flex:1;overflow:hidden;min-height:0;">
            @csrf
            <input type="hidden" name="_method" id="modal-method" value="">

            <div style="overflow-y:auto;flex:1;padding:20px 24px;">

                {{-- 유효성 오류 --}}
                <div id="modal-errors" style="display:none;margin-bottom:16px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:12px 16px;">
                    <ul id="modal-errors-list" style="margin:0;padding:0 0 0 16px;font-size:13px;color:#dc2626;"></ul>
                </div>

                {{-- 기본 정보 --}}
                <div style="background:#faf8ff;border:1px solid #f0eeff;border-radius:12px;padding:18px;margin-bottom:14px;">
                    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_basic_info') }}</div>

                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_meeting_title') }} <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="title" id="modal-title-input" required
                               style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;"
                               placeholder="{{ __('maintenance.form_meeting_title_ph') }}"
                               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_meeting_type') }} <span style="color:#ef4444;">*</span></label>
                            <div style="display:flex;gap:16px;padding:8px 0;">
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                                    <input type="radio" name="type" value="general" checked onchange="modalToggleProject(this.value)"> {{ __('maintenance.filter_general') }}
                                </label>
                                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                                    <input type="radio" name="type" value="project" onchange="modalToggleProject(this.value)"> {{ __('maintenance.filter_project') }}
                                </label>
                            </div>
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_meeting_date') }} <span style="color:#ef4444;">*</span></label>
                            <input type="datetime-local" name="meeting_date" id="modal-meeting-date" required
                                   style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    <div id="modal-project-fields" style="display:none;margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_project') }}</label>
                        <select name="project_id" id="modal-project-id"
                                style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;"
                                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                            <option value="">{{ __('maintenance.form_project_select') }}</option>
                            @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_project_code') }}</label>
                            <input type="text" name="project_code" id="modal-project-code"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;"
                                   placeholder="{{ __('maintenance.form_project_code_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                        <div>
                            <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_weekly_dept') }}</label>
                            <input type="text" name="weekly_department" id="modal-weekly-dept"
                                   style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;"
                                   placeholder="{{ __('maintenance.form_weekly_dept_ph') }}"
                                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.form_location') }}</label>
                        <input type="text" name="location" id="modal-location"
                               style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;"
                               placeholder="{{ __('maintenance.form_location_ph') }}"
                               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    </div>
                </div>

                {{-- 참석자 --}}
                <div style="background:#faf8ff;border:1px solid #f0eeff;border-radius:12px;padding:18px;margin-bottom:14px;">
                    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_attendees') }}</div>
                    <div id="modal-attendee-list"></div>
                    <button type="button" onclick="modalAddAttendee()"
                            style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px dashed #c4b5fd;border-radius:8px;background:transparent;color:var(--t600);font-size:12px;font-weight:600;cursor:pointer;margin-top:4px;">
                        {{ __('maintenance.form_add_attendee') }}
                    </button>
                </div>

                {{-- 회의 내용 --}}
                <div style="background:#faf8ff;border:1px solid #f0eeff;border-radius:12px;padding:18px;">
                    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_meeting_content') }}</div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.agenda') }} (Agenda)</label>
                        <textarea name="agenda" id="modal-agenda" rows="3"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_agenda_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                    <div style="margin-bottom:14px;">
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.discussion') }} (Discussion)</label>
                        <textarea name="discussion" id="modal-discussion" rows="5"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_discussion_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                    <div>
                        <label style="display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;">{{ __('maintenance.decisions') }} (Decisions)</label>
                        <textarea name="decisions" id="modal-decisions" rows="3"
                                  style="width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;resize:vertical;font-family:inherit;box-sizing:border-box;"
                                  placeholder="{{ __('maintenance.form_decisions_ph') }}"
                                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'"></textarea>
                    </div>
                </div>

            </div>{{-- /스크롤 영역 --}}

            {{-- 푸터 --}}
            <div style="padding:16px 24px;border-top:1px solid #f0eeff;display:flex;gap:10px;justify-content:flex-end;background:#faf8ff;border-radius:0 0 16px 16px;flex-shrink:0;">
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
const STORE_URL  = '{{ route('meeting-minutes.store') }}';
const JSON_URL   = '{{ url('meeting-minutes') }}';
const INP_STYLE  = 'width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;box-sizing:border-box;';
const STR_DIRECT = '{{ __('maintenance.form_attendee_direct') }}';
const STR_NAME   = '{{ __('maintenance.form_attendee_name_ph') }}';

const TM_OPTIONS = `<option value="">${STR_DIRECT}</option>` +
    `@foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}</option>@endforeach`;

let modalAttendeeIdx = 0;

function openMeetingModal() {
    document.getElementById('modal-title').textContent = '{{ __('maintenance.meeting_create') }}';
    document.getElementById('modal-method').value = '';
    document.getElementById('meeting-modal-form').action = STORE_URL;

    document.getElementById('modal-title-input').value = '';
    document.querySelector('[name="type"][value="general"]').checked = true;
    document.getElementById('modal-meeting-date').value = '{{ now()->format('Y-m-d\TH:i') }}';
    document.getElementById('modal-project-fields').style.display = 'none';
    document.getElementById('modal-project-id').value = '';
    document.getElementById('modal-project-code').value = '';
    document.getElementById('modal-weekly-dept').value = '';
    document.getElementById('modal-location').value = '';
    document.getElementById('modal-agenda').value = '';
    document.getElementById('modal-discussion').value = '';
    document.getElementById('modal-decisions').value = '';
    document.getElementById('modal-attendee-list').innerHTML = '';
    document.getElementById('modal-errors').style.display = 'none';
    modalAttendeeIdx = 0;
    modalAddAttendee();

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

    document.getElementById('modal-title').textContent = '{{ __('maintenance.meeting_edit') }}';
    document.getElementById('modal-method').value = 'PATCH';
    document.getElementById('meeting-modal-form').action = `${JSON_URL}/${minuteId}`;

    document.getElementById('modal-title-input').value = d.title || '';
    document.querySelector(`[name="type"][value="${d.type || 'general'}"]`).checked = true;
    modalToggleProject(d.type || 'general');
    document.getElementById('modal-meeting-date').value = d.meeting_date || '';
    document.getElementById('modal-project-id').value = d.project_id || '';
    document.getElementById('modal-project-code').value = d.project_code || '';
    document.getElementById('modal-weekly-dept').value = d.weekly_department || '';
    document.getElementById('modal-location').value = d.location || '';
    document.getElementById('modal-agenda').value = d.agenda || '';
    document.getElementById('modal-discussion').value = d.discussion || '';
    document.getElementById('modal-decisions').value = d.decisions || '';

    document.getElementById('modal-attendee-list').innerHTML = '';
    modalAttendeeIdx = 0;
    if (d.attendees && d.attendees.length) {
        d.attendees.forEach(a => modalAddAttendee(a.user_id, a.name));
    } else {
        modalAddAttendee();
    }

    document.getElementById('meeting-modal-overlay').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeMeetingModal() {
    document.getElementById('meeting-modal-overlay').style.display = 'none';
    document.body.style.overflow = '';
}

function modalToggleProject(type) {
    document.getElementById('modal-project-fields').style.display = type === 'project' ? '' : 'none';
}

function modalAddAttendee(userId, name) {
    const i = modalAttendeeIdx++;
    const row = document.createElement('div');
    row.className = 'modal-attendee-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';

    const selEl = document.createElement('select');
    selEl.name = `attendees[${i}][user_id]`;
    selEl.style.cssText = `flex:1;${INP_STYLE}`;
    selEl.innerHTML = TM_OPTIONS;
    selEl.addEventListener('focus', () => selEl.style.borderColor = 'var(--t500)');
    selEl.addEventListener('blur',  () => selEl.style.borderColor = '#e8e3ff');
    if (userId) selEl.value = userId;

    const inpEl = document.createElement('input');
    inpEl.type = 'text';
    inpEl.name = `attendees[${i}][name]`;
    inpEl.placeholder = STR_NAME;
    inpEl.style.cssText = `flex:1;${INP_STYLE}`;
    inpEl.addEventListener('focus', () => inpEl.style.borderColor = 'var(--t500)');
    inpEl.addEventListener('blur',  () => inpEl.style.borderColor = '#e8e3ff');
    if (name) inpEl.value = name;

    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.innerHTML = '×';
    delBtn.style.cssText = 'width:30px;height:30px;border:1.5px solid #fecaca;background:#fff;border-radius:7px;color:#ef4444;cursor:pointer;font-size:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;';
    delBtn.onclick = () => row.remove();

    row.append(selEl, inpEl, delBtn);
    document.getElementById('modal-attendee-list').appendChild(row);
}

async function submitMeetingForm(e) {
    e.preventDefault();
    const form = document.getElementById('meeting-modal-form');
    const btn  = document.getElementById('modal-submit-btn');
    const origText = btn.textContent;

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
</script>
@endsection
