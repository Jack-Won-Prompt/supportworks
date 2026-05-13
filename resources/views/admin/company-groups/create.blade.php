@extends('layouts.admin')

@section('title', __('admin.company_create'))

@section('header-actions')
<a href="{{ route('admin.company-groups.index') }}" class="btn-secondary">{{ __('admin.back_to_list') }}</a>
@endsection

@section('content')
<div style="max-width:560px;">
    <div class="admin-card">
        <form method="POST" action="{{ route('admin.company-groups.store') }}" class="space-y-5">
            @csrf

            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">{{ __('admin.company_name') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required
                    placeholder="{{ __('admin.cg_name_placeholder') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                @error('name')<p style="font-size:11px;color:#ef4444;margin-top:4px;">{{ $message }}</p>@enderror
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">{{ __('admin.col_code') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="code" value="{{ old('code') }}" required
                    placeholder="{{ __('admin.cg_code_placeholder') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;color:#1e293b;outline:none;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">
                <p style="font-size:11px;color:#94a3b8;margin-top:4px;">{{ __('admin.company_group_code_hint') }}</p>
                @error('code')<p style="font-size:11px;color:#ef4444;margin-top:2px;">{{ $message }}</p>@enderror
            </div>

            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:6px;">{{ __('admin.col_description') }}</label>
                <textarea name="description" rows="3" placeholder="{{ __('admin.col_description') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;outline:none;resize:vertical;box-sizing:border-box;"
                    onfocus="this.style.borderColor='#6366f1'" onblur="this.style.borderColor='#e2e8f0'">{{ old('description') }}</textarea>
            </div>

            <div style="display:flex;align-items:center;gap:8px;">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                    style="width:16px;height:16px;accent-color:#6366f1;">
                <label for="is_active" style="font-size:13px;color:#334155;cursor:pointer;">{{ __('admin.active_on_create') }}</label>
            </div>

            <div style="display:flex;gap:10px;padding-top:8px;border-top:1px solid #f1f5f9;">
                <button type="submit" class="btn-primary">{{ __('admin.create') }}</button>
                <a href="{{ route('admin.company-groups.index') }}" class="btn-secondary">{{ __('admin.cancel') }}</a>
            </div>
        </form>
    </div>
</div>
@endsection
