@php $isEdit = $isEdit ?? false; @endphp

<div style="display:flex;flex-direction:column;gap:14px;">

    <div>
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('admin.version') }} <span style="color:#ef4444;">*</span></label>
        <input type="text" name="version" id="{{ $isEdit ? 'edit-version' : 'add-version' }}"
            placeholder="{{ __('admin.version') }}: 1.2.0" required maxlength="20"
            style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;outline:none;"
            onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
    </div>

    <div>
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">
            {{ __('admin.appv_installer') }} @if(!$isEdit)<span style="color:#ef4444;">*</span>@endif
        </label>
        @if($isEdit)
        <div style="margin-bottom:6px;font-size:12px;color:#64748b;">
            {{ __('admin.appv_current_file') }}
            <a id="edit-current-url" href="#" target="_blank" style="color:#6366f1;">—</a>
        </div>
        <p style="font-size:11px;color:#94a3b8;margin:0 0 6px;">{{ __('admin.appv_replace_note') }}</p>
        @endif
        <label style="display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px dashed #cbd5e1;border-radius:8px;cursor:pointer;transition:border-color .15s;"
            onmouseover="this.style.borderColor='#6366f1'" onmouseout="this.style.borderColor='#cbd5e1'">
            <svg width="18" height="18" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <span id="{{ $isEdit ? 'edit-file-label' : 'add-file-label' }}" style="font-size:13px;color:#94a3b8;">{{ __('admin.appv_file_label') }}</span>
            <input type="file" name="installer" accept=".exe,.msi,.zip" {{ $isEdit ? '' : 'required' }} style="display:none;"
                onchange="document.getElementById('{{ $isEdit ? 'edit-file-label' : 'add-file-label' }}').textContent = this.files[0]?.name ?? '{{ __('admin.appv_file_label') }}';
                          document.getElementById('{{ $isEdit ? 'edit-file-label' : 'add-file-label' }}').style.color = this.files[0] ? '#1e293b' : '#94a3b8';">
        </label>
    </div>

    <div>
        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('admin.appv_changes') }} <span style="font-weight:400;color:#94a3b8;">{{ __('admin.appv_optional') }}</span></label>
        <textarea name="release_notes" id="{{ $isEdit ? 'edit-notes' : 'add-notes' }}" rows="4"
            placeholder="{{ __('admin.appv_notes_placeholder') }}"
            style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;outline:none;"
            onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
    </div>

</div>
