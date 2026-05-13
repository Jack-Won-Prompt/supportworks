@extends('layouts.app')

@section('title', __('maintenance.sr_register'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('common.list') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<a href="{{ route('projects.maintenances.index', $project) }}" class="hover:text-indigo-500 transition-colors">{{ __('maintenance.sr_receipt') }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('maintenance.sr_register') }}</span>
@endsection

@section('content')
<div style="max-width:700px;margin:0 auto;">

    <div style="background:#fff;border-radius:14px;border:1px solid #ede9fe;box-shadow:0 2px 12px rgba(0,0,0,.05);overflow:hidden;">

        <div style="padding:20px 24px;border-bottom:1px solid #f3f4f6;background:linear-gradient(135deg,#faf5ff,#f5f3ff);">
            <h2 style="margin:0;font-size:16px;font-weight:700;color:#1e1b2e;">{{ __('maintenance.sr_register') }}</h2>
            <p style="margin:4px 0 0;font-size:13px;color:#7c3aed;">{{ $project->name }}</p>
        </div>

        <form method="POST" action="{{ route('projects.maintenances.store', $project) }}" enctype="multipart/form-data" style="padding:24px;">
            @csrf

            {{-- 제목 --}}
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('maintenance.field_title') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       placeholder="{{ __('maintenance.field_title') }}..."
                       style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;color:#1e1b2e;outline:none;box-sizing:border-box;transition:border-color .15s;"
                       onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
                @error('title')
                    <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- 우선순위 --}}
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('maintenance.field_priority') }} <span style="color:#ef4444;">*</span></label>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @foreach(['low' => ['priority_low','#6b7280','#f3f4f6'], 'normal' => ['priority_normal','#2563eb','#dbeafe'], 'high' => ['priority_high','#d97706','#fef3c7'], 'urgent' => ['priority_urgent','#dc2626','#fee2e2']] as $val => [$lkey, $color, $bg])
                    <label style="display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid #e5e7eb;border-radius:8px;cursor:pointer;font-size:13px;font-weight:600;color:#374151;transition:all .15s;user-select:none;" id="pri-{{ $val }}">
                        <input type="radio" name="priority" value="{{ $val }}" {{ old('priority', 'normal') === $val ? 'checked' : '' }}
                               style="accent-color:{{ $color }};" onchange="updatePriorityChips()">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $color }};flex-shrink:0;"></span>
                        {{ __('maintenance.' . $lkey) }}
                    </label>
                    @endforeach
                </div>
                @error('priority')
                    <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- 내용 --}}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('maintenance.field_content') }} <span style="color:#ef4444;">*</span></label>
                <textarea name="content" rows="8" required
                          placeholder="{{ __('maintenance.field_content') }}..."
                          style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:14px;color:#1e1b2e;outline:none;resize:vertical;box-sizing:border-box;font-family:inherit;line-height:1.7;transition:border-color .15s;"
                          onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">{{ old('content') }}</textarea>
                @error('content')
                    <p style="font-size:12px;color:#ef4444;margin:5px 0 0;">{{ $message }}</p>
                @enderror
            </div>

            {{-- 파일 첨부 --}}
            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('maintenance.field_attachment') }} <span style="font-size:11px;font-weight:400;color:#9ca3af;">{{ __('maintenance.field_attachment_hint') }}</span></label>
                <div id="cr-drop-zone"
                     style="border:2px dashed #ddd6fe;border-radius:10px;padding:24px 16px;text-align:center;cursor:pointer;transition:all .15s;background:#faf5ff;"
                     onclick="document.getElementById('cr-file-input').click()"
                     ondragover="event.preventDefault();this.style.borderColor='#7c3aed';this.style.background='#ede9fe';"
                     ondragleave="this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';"
                     ondrop="event.preventDefault();this.style.borderColor='#ddd6fe';this.style.background='#faf5ff';crHandleDrop(event.dataTransfer.files);">
                    <svg width="24" height="24" fill="none" stroke="#a78bfa" viewBox="0 0 24 24" stroke-width="1.5" style="margin:0 auto 8px;display:block;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <p style="font-size:13px;color:#7c3aed;font-weight:600;margin:0 0 2px;">{{ __('maintenance.upload_click_or_drag') }}</p>
                    <p style="font-size:11px;color:#9ca3af;margin:0;">{{ __('maintenance.upload_max_size') }}</p>
                </div>
                <input type="file" id="cr-file-input" name="attachments[]" multiple style="display:none;" onchange="crHandleFiles(this.files)">
                <div id="cr-file-list" style="margin-top:8px;display:flex;flex-direction:column;gap:5px;"></div>
                @if($fileCategories->isNotEmpty())
                <div style="margin-top:10px;display:flex;align-items:center;gap:8px;">
                    <label style="font-size:12px;font-weight:600;color:#6b7280;flex-shrink:0;">{{ __('maintenance.field_file_category') }}</label>
                    <select name="attachment_category_id"
                            style="padding:5px 10px;border:1.5px solid #e5e7eb;border-radius:7px;font-size:12px;color:#374151;outline:none;background:#fff;cursor:pointer;"
                            onfocus="this.style.borderColor='#7c3aed'" onblur="this.style.borderColor='#e5e7eb'">
                        <option value="">{{ __('maintenance.cat_uncategorized') }}</option>
                        @foreach($fileCategories as $fc)
                        <option value="{{ $fc->id }}">{{ $fc->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>

            {{-- 버튼 --}}
            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('projects.maintenances.index', $project) }}"
                   style="padding:10px 20px;border:1.5px solid #e5e7eb;border-radius:9px;font-size:13px;font-weight:600;color:#6b7280;text-decoration:none;transition:background .15s;"
                   onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='transparent'">{{ __('common.cancel') }}</a>
                <button type="submit"
                        style="padding:10px 24px;background:linear-gradient(135deg,#7c3aed,#6d28d9);color:#fff;border:none;border-radius:9px;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .15s;"
                        onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
                    {{ __('maintenance.btn_register') }}
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@section('scripts')
<script>
function updatePriorityChips() {
    const colors = { low: '#6b7280', normal: '#2563eb', high: '#d97706', urgent: '#dc2626' };
    const bgs    = { low: '#f3f4f6', normal: '#dbeafe', high: '#fef3c7', urgent: '#fee2e2' };
    document.querySelectorAll('input[name=priority]').forEach(function(radio) {
        const label = document.getElementById('pri-' + radio.value);
        if (!label) return;
        if (radio.checked) {
            label.style.borderColor = colors[radio.value];
            label.style.background  = bgs[radio.value];
            label.style.color       = colors[radio.value];
        } else {
            label.style.borderColor = '#e5e7eb';
            label.style.background  = 'transparent';
            label.style.color       = '#374151';
        }
    });
}
document.addEventListener('DOMContentLoaded', updatePriorityChips);

/* ── 파일 첨부 ── */
let _crFiles = [];

function crHandleFiles(fileList) {
    Array.from(fileList).forEach(f => {
        if (_crFiles.find(x => x.name === f.name && x.size === f.size)) return;
        _crFiles.push(f);
    });
    crRenderList();
}
function crHandleDrop(fileList) { crHandleFiles(fileList); }

function crRenderList() {
    const list = document.getElementById('cr-file-list');
    list.innerHTML = '';
    _crFiles.forEach((f, i) => {
        const row = document.createElement('div');
        row.style.cssText = 'display:flex;align-items:center;gap:8px;padding:7px 10px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;font-size:12px;';
        row.innerHTML = `
            <svg width="14" height="14" fill="none" stroke="#7c3aed" viewBox="0 0 24 24" stroke-width="2" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#374151;">${f.name}</span>
            <span style="color:#9ca3af;flex-shrink:0;">${(f.size/1024/1024).toFixed(1)}MB</span>
            <button type="button" onclick="crRemoveFile(${i})" style="background:none;border:none;cursor:pointer;color:#d1d5db;font-size:16px;line-height:1;padding:0;flex-shrink:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#d1d5db'">×</button>`;
        list.appendChild(row);
    });
}
function crRemoveFile(idx) {
    _crFiles.splice(idx, 1);
    crRenderList();
}

/* 폼 제출 시 DataTransfer로 파일 목록 동기화 */
document.querySelector('form').addEventListener('submit', function() {
    const dt = new DataTransfer();
    _crFiles.forEach(f => dt.items.add(f));
    document.getElementById('cr-file-input').files = dt.files;
});
</script>
@endsection
