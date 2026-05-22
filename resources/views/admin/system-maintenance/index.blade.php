@extends('layouts.admin')

@section('title', __('admin.system_maintenance'))

@section('content')
<div class="admin-card" style="max-width:680px;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:16px;font-weight:700;color:#1e293b;margin:0 0 4px;">{{ __('admin.system_maintenance') }}</h2>
        <p style="font-size:13px;color:#64748b;margin:0;">{{ __('admin.sm_desc') }}</p>
    </div>

    {{-- session 플래시는 전역 토스트(window.appToast)로 표시됨 --}}

    <form method="POST" action="{{ route('admin.system-maintenance.update') }}">
        @csrf
        @method('PATCH')

        <div style="margin-bottom:24px;padding:18px;border:1px solid {{ $setting->maintenance_mode ? '#fecaca' : '#e2e8f0' }};border-radius:12px;background:{{ $setting->maintenance_mode ? '#fef2f2' : '#f8fafc' }};">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:42px;height:42px;border-radius:10px;background:{{ $setting->maintenance_mode ? 'linear-gradient(135deg,#ef4444,#b91c1c)' : 'linear-gradient(135deg,#64748b,#475569)' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="20" height="20" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div style="flex:1;">
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ __('admin.sm_current_status') }}</div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                        @if($setting->maintenance_mode)
                            <span style="color:#dc2626;font-weight:600;">● {{ __('admin.sm_status_on') }}</span> — {{ __('admin.sm_status_on_desc') }}
                        @else
                            <span style="color:#16a34a;font-weight:600;">● {{ __('admin.sm_status_off') }}</span> — {{ __('admin.sm_status_off_desc') }}
                        @endif
                    </div>
                </div>

                {{-- 토글 스위치 --}}
                <label style="position:relative;display:inline-block;width:52px;height:28px;flex-shrink:0;cursor:pointer;">
                    <input type="hidden" name="maintenance_mode" value="0">
                    <input type="checkbox" name="maintenance_mode" value="1" id="sm-toggle"
                           {{ $setting->maintenance_mode ? 'checked' : '' }}
                           style="opacity:0;width:0;height:0;">
                    <span id="sm-slider" style="position:absolute;inset:0;background:{{ $setting->maintenance_mode ? '#ef4444' : '#cbd5e1' }};border-radius:28px;transition:background .2s;"></span>
                    <span id="sm-thumb" style="position:absolute;top:3px;left:{{ $setting->maintenance_mode ? '27px' : '3px' }};width:22px;height:22px;background:#fff;border-radius:50%;transition:left .2s;box-shadow:0 1px 3px rgba(0,0,0,.2);"></span>
                </label>
            </div>
        </div>

        <div style="margin-bottom:24px;">
            <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.sm_message_label') }}</label>
            <textarea name="maintenance_message" rows="4"
                placeholder="{{ __('admin.sm_message_placeholder') }}"
                style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;outline:none;">{{ old('maintenance_message', $setting->maintenance_message) }}</textarea>
            <p style="font-size:11px;color:#94a3b8;margin-top:6px;">{{ __('admin.sm_message_help') }}</p>
        </div>

        <div style="background:#fef9c3;border:1px solid #fde68a;border-radius:8px;padding:12px 14px;font-size:12px;color:#92400e;margin-bottom:20px;line-height:1.6;">
            <strong>{{ __('admin.sm_warning_title') }}</strong><br>
            • {{ __('admin.sm_warning_1') }}<br>
            • {{ __('admin.sm_warning_2') }}<br>
            • {{ __('admin.sm_warning_3') }}
        </div>

        <div style="display:flex;justify-content:flex-end;">
            <button type="submit" class="btn-primary" style="padding:9px 22px;font-size:13px;">{{ __('admin.sm_save_btn') }}</button>
        </div>
    </form>
</div>

<script>
(function() {
    const toggle = document.getElementById('sm-toggle');
    const slider = document.getElementById('sm-slider');
    const thumb  = document.getElementById('sm-thumb');
    toggle.addEventListener('change', function() {
        if (this.checked) {
            slider.style.background = '#ef4444';
            thumb.style.left = '27px';
        } else {
            slider.style.background = '#cbd5e1';
            thumb.style.left = '3px';
        }
    });
})();
</script>
@endsection
