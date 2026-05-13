@php
    $isEdit = !is_null($minute);
    $inp = 'width:100%;padding:8px 12px;border:1.5px solid #e8e3ff;border-radius:8px;font-size:13px;color:#1e1b2e;outline:none;background:#fff;font-family:inherit;transition:border-color .15s;';
    $lbl = 'display:block;font-size:12px;font-weight:600;color:#64748b;margin-bottom:5px;';
    $card = 'background:#fff;border:1px solid #f0eeff;border-radius:12px;padding:20px;margin-bottom:16px;';
@endphp

{{-- 기본 정보 --}}
<div style="{{ $card }}">
    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_basic_info') }}</div>

    <div style="margin-bottom:14px;">
        <label style="{{ $lbl }}">{{ __('maintenance.form_meeting_title') }} <span style="color:#ef4444;">*</span></label>
        <input type="text" name="title" value="{{ old('title', $minute?->title) }}" required
               style="{{ $inp }}" placeholder="{{ __('maintenance.form_meeting_title_ph') }}"
               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="{{ $lbl }}">{{ __('maintenance.form_meeting_type') }} <span style="color:#ef4444;">*</span></label>
            <div style="display:flex;gap:16px;padding:8px 0;">
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="radio" name="type" value="general" {{ old('type', $minute?->type ?? 'general') === 'general' ? 'checked' : '' }}
                           onchange="toggleProjectFields(this.value)"> {{ __('maintenance.filter_general') }}
                </label>
                <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
                    <input type="radio" name="type" value="project" {{ old('type', $minute?->type) === 'project' ? 'checked' : '' }}
                           onchange="toggleProjectFields(this.value)"> {{ __('maintenance.filter_project') }}
                </label>
            </div>
        </div>
        <div>
            <label style="{{ $lbl }}">{{ __('maintenance.form_meeting_date') }} <span style="color:#ef4444;">*</span></label>
            <input type="datetime-local" name="meeting_date"
                   value="{{ old('meeting_date', $minute?->meeting_date?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i')) }}"
                   required style="{{ $inp }}"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
        </div>
    </div>

    {{-- 프로젝트 선택 (type=project일 때) --}}
    <div id="project-fields" style="{{ old('type', $minute?->type) === 'project' ? '' : 'display:none;' }}margin-bottom:14px;">
        <label style="{{ $lbl }}">{{ __('maintenance.form_project') }}</label>
        <select name="project_id" style="{{ $inp }}"
                onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
            <option value="">{{ __('maintenance.form_project_select') }}</option>
            @foreach($projects as $proj)
            <option value="{{ $proj->id }}" {{ old('project_id', $minute?->project_id) == $proj->id ? 'selected' : '' }}>{{ $proj->name }}</option>
            @endforeach
        </select>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px;">
        <div>
            <label style="{{ $lbl }}">{{ __('maintenance.form_project_code') }}</label>
            <input type="text" name="project_code" value="{{ old('project_code', $minute?->project_code) }}"
                   style="{{ $inp }}" placeholder="{{ __('maintenance.form_project_code_ph') }}"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
        </div>
        <div>
            <label style="{{ $lbl }}">{{ __('maintenance.form_weekly_dept') }}</label>
            <input type="text" name="weekly_department" value="{{ old('weekly_department', $minute?->weekly_department) }}"
                   style="{{ $inp }}" placeholder="{{ __('maintenance.form_weekly_dept_ph') }}"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
        </div>
    </div>

    <div>
        <label style="{{ $lbl }}">{{ __('maintenance.form_location') }}</label>
        <input type="text" name="location" value="{{ old('location', $minute?->location) }}"
               style="{{ $inp }}" placeholder="{{ __('maintenance.form_location_ph') }}"
               onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
    </div>
</div>

{{-- 참석자 --}}
<div style="{{ $card }}">
    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_attendees') }}</div>
    <div id="attendee-list">
        @if($isEdit && $minute->attendees->count())
            @foreach($minute->attendees as $i => $att)
            <div class="attendee-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                <select name="attendees[{{ $i }}][user_id]" style="flex:1;{{ $inp }}"
                        onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                    <option value="">{{ __('maintenance.form_attendee_direct') }}</option>
                    @foreach($teammates as $tm)
                    <option value="{{ $tm->id }}" {{ $att->user_id == $tm->id ? 'selected' : '' }}>{{ $tm->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="attendees[{{ $i }}][name]" value="{{ $att->name }}" placeholder="{{ __('maintenance.form_attendee_name_ph') }}"
                       style="flex:1;{{ $inp }}"
                       onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                <button type="button" onclick="this.closest('.attendee-row').remove()"
                        style="width:30px;height:30px;border:1.5px solid #fecaca;background:#fff;border-radius:7px;color:#ef4444;cursor:pointer;font-size:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">×</button>
            </div>
            @endforeach
        @else
        <div class="attendee-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
            <select name="attendees[0][user_id]" style="flex:1;{{ $inp }}"
                    onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
                <option value="">{{ __('maintenance.form_attendee_direct') }}</option>
                @foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}</option>@endforeach
            </select>
            <input type="text" name="attendees[0][name]" placeholder="{{ __('maintenance.form_attendee_name_ph') }}"
                   style="flex:1;{{ $inp }}"
                   onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
            <button type="button" onclick="this.closest('.attendee-row').remove()"
                    style="width:30px;height:30px;border:1.5px solid #fecaca;background:#fff;border-radius:7px;color:#ef4444;cursor:pointer;font-size:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">×</button>
        </div>
        @endif
    </div>
    <button type="button" onclick="addAttendee()"
            style="display:inline-flex;align-items:center;gap:5px;padding:6px 12px;border:1.5px dashed #c4b5fd;border-radius:8px;background:transparent;color:var(--t600);font-size:12px;font-weight:600;cursor:pointer;margin-top:4px;">
        {{ __('maintenance.form_add_attendee') }}
    </button>
</div>

{{-- 회의 내용 --}}
<div style="{{ $card }}">
    <div style="font-size:13px;font-weight:700;color:#1e1b2e;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #f0eeff;">{{ __('maintenance.form_meeting_content') }}</div>
    <div style="margin-bottom:14px;">
        <label style="{{ $lbl }}">{{ __('maintenance.agenda') }} (Agenda)</label>
        <textarea name="agenda" rows="3" style="{{ $inp }}resize:vertical;"
                  placeholder="{{ __('maintenance.form_agenda_ph') }}"
                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">{{ old('agenda', $minute?->agenda) }}</textarea>
    </div>
    <div style="margin-bottom:14px;">
        <label style="{{ $lbl }}">{{ __('maintenance.discussion') }} (Discussion)</label>
        <textarea name="discussion" rows="5" style="{{ $inp }}resize:vertical;"
                  placeholder="{{ __('maintenance.form_discussion_ph') }}"
                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">{{ old('discussion', $minute?->discussion) }}</textarea>
    </div>
    <div>
        <label style="{{ $lbl }}">{{ __('maintenance.decisions') }} (Decisions)</label>
        <textarea name="decisions" rows="3" style="{{ $inp }}resize:vertical;"
                  placeholder="{{ __('maintenance.form_decisions_ph') }}"
                  onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">{{ old('decisions', $minute?->decisions) }}</textarea>
    </div>
</div>

<script>
let attendeeIdx = {{ $isEdit ? ($minute->attendees->count() ?: 1) : 1 }};
const tmOptions = `@foreach($teammates as $tm)<option value="{{ $tm->id }}">{{ $tm->name }}</option>@endforeach`;
const inpStyle = '{{ addslashes($inp) }}';
const STR_DIRECT_INPUT = '{{ __('maintenance.form_attendee_direct') }}';
const STR_NAME_PH      = '{{ __('maintenance.form_attendee_name_ph') }}';

function addAttendee() {
    const i = attendeeIdx++;
    const row = document.createElement('div');
    row.className = 'attendee-row';
    row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';
    row.innerHTML = `
        <select name="attendees[${i}][user_id]" style="flex:1;${inpStyle}" onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
            <option value="">${STR_DIRECT_INPUT}</option>${tmOptions}
        </select>
        <input type="text" name="attendees[${i}][name]" placeholder="${STR_NAME_PH}"
               style="flex:1;${inpStyle}" onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e8e3ff'">
        <button type="button" onclick="this.closest('.attendee-row').remove()"
                style="width:30px;height:30px;border:1.5px solid #fecaca;background:#fff;border-radius:7px;color:#ef4444;cursor:pointer;font-size:16px;flex-shrink:0;display:flex;align-items:center;justify-content:center;">×</button>
    `;
    document.getElementById('attendee-list').appendChild(row);
}

function toggleProjectFields(type) {
    document.getElementById('project-fields').style.display = type === 'project' ? '' : 'none';
}
</script>
