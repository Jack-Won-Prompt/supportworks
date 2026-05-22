@extends('layouts.admin')

@section('title', __('admin.ai_settings'))

@section('content')
<div class="admin-card" style="max-width:680px;">
    <div style="margin-bottom:24px;">
        <h2 style="font-size:16px;font-weight:700;color:#1e293b;margin:0 0 4px;">{{ __('admin.ai_settings') }}</h2>
        <p style="font-size:13px;color:#64748b;margin:0;">{{ __('admin.aiset_manage_desc') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.ai-settings.update') }}">
        @csrf
        @method('PUT')

        {{-- Anthropic (Claude) --}}
        <div style="margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#8b5cf6);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">Anthropic (Claude)</div>
                    <div style="font-size:12px;color:#64748b;">{{ __('admin.aiset_anthropic_desc') }}</div>
                </div>
                @if($setting->anthropic_key)
                <span class="badge badge-green" style="margin-left:auto;">{{ __('admin.aiset_key_set') }}</span>
                @else
                <span class="badge badge-gray" style="margin-left:auto;">{{ __('admin.aiset_key_unset') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <div style="flex:1;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.aiset_api_key_label') }}</label>
                    <input type="password" name="anthropic_key"
                           value="{{ $setting->anthropic_key ? '__MASKED__' : '' }}"
                           placeholder="sk-ant-api03-..."
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;"
                           onfocus="if(this.value==='__MASKED__')this.value=''"
                           onblur="if(this.value==='')this.value='{{ $setting->anthropic_key ? '__MASKED__' : '' }}'">
                </div>
                @if($setting->anthropic_key)
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#ef4444;cursor:pointer;padding-bottom:10px;white-space:nowrap;">
                    <input type="checkbox" name="clear_anthropic" value="1" style="width:14px;height:14px;">
                    {{ __('admin.aiset_key_delete') }}
                </label>
                @endif
            </div>
        </div>

        {{-- OpenAI --}}
        <div style="margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#10b981,#059669);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">OpenAI (GPT)</div>
                    <div style="font-size:12px;color:#64748b;">{{ __('admin.aiset_openai_desc') }}</div>
                </div>
                @if($setting->openai_key)
                <span class="badge badge-green" style="margin-left:auto;">{{ __('admin.aiset_key_set') }}</span>
                @else
                <span class="badge badge-gray" style="margin-left:auto;">{{ __('admin.aiset_key_unset') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <div style="flex:1;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.aiset_api_key_label') }}</label>
                    <input type="password" name="openai_key"
                           value="{{ $setting->openai_key ? '__MASKED__' : '' }}"
                           placeholder="sk-..."
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;"
                           onfocus="if(this.value==='__MASKED__')this.value=''"
                           onblur="if(this.value==='')this.value='{{ $setting->openai_key ? '__MASKED__' : '' }}'">
                </div>
                @if($setting->openai_key)
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#ef4444;cursor:pointer;padding-bottom:10px;white-space:nowrap;">
                    <input type="checkbox" name="clear_openai" value="1" style="width:14px;height:14px;">
                    {{ __('admin.aiset_key_delete') }}
                </label>
                @endif
            </div>
        </div>

        {{-- Figma --}}
        <div style="margin-bottom:28px;padding-bottom:28px;border-bottom:1px solid #f1f5f9;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">Figma</div>
                    <div style="font-size:12px;color:#64748b;">{{ __('admin.aiset_figma_desc') }}</div>
                </div>
                @if($setting->figma_token)
                <span class="badge badge-green" style="margin-left:auto;">{{ __('admin.aiset_key_set') }}</span>
                @else
                <span class="badge badge-gray" style="margin-left:auto;">{{ __('admin.aiset_key_unset') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;">
                <div style="flex:1;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">Personal Access Token</label>
                    <input type="password" name="figma_token"
                           value="{{ $setting->figma_token ? '__MASKED__' : '' }}"
                           placeholder="figd_..."
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;"
                           onfocus="if(this.value==='__MASKED__')this.value=''"
                           onblur="if(this.value==='')this.value='{{ $setting->figma_token ? '__MASKED__' : '' }}'">
                </div>
                @if($setting->figma_token)
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#ef4444;cursor:pointer;padding-bottom:10px;white-space:nowrap;">
                    <input type="checkbox" name="clear_figma" value="1" style="width:14px;height:14px;">
                    {{ __('admin.aiset_key_delete') }}
                </label>
                @endif
            </div>
        </div>

        {{-- Manus --}}
        <div style="margin-bottom:28px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px;">
                <div style="width:36px;height:36px;background:linear-gradient(135deg,#06b6d4,#0891b2);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                    <div style="font-size:14px;font-weight:700;color:#1e293b;">{{ __('admin.aiset_manus_label') }}</div>
                    <div style="font-size:12px;color:#64748b;">{{ __('admin.aiset_manus_desc') }}</div>
                </div>
                @if($setting->manus_key)
                <span class="badge badge-green" style="margin-left:auto;">{{ __('admin.aiset_key_set') }}</span>
                @else
                <span class="badge badge-gray" style="margin-left:auto;">{{ __('admin.aiset_key_unset') }}</span>
                @endif
            </div>
            <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:10px;">
                <div style="flex:1;">
                    <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.aiset_api_key_label') }}</label>
                    <input type="password" name="manus_key"
                           value="{{ $setting->manus_key ? '__MASKED__' : '' }}"
                           placeholder="Manus API Key"
                           style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;"
                           onfocus="if(this.value==='__MASKED__')this.value=''"
                           onblur="if(this.value==='')this.value='{{ $setting->manus_key ? '__MASKED__' : '' }}'">
                </div>
                @if($setting->manus_key)
                <label style="display:flex;align-items:center;gap:4px;font-size:12px;color:#ef4444;cursor:pointer;padding-bottom:10px;white-space:nowrap;">
                    <input type="checkbox" name="clear_manus" value="1" style="width:14px;height:14px;">
                    {{ __('admin.aiset_key_delete') }}
                </label>
                @endif
            </div>
            <div>
                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:6px;">{{ __('admin.aiset_endpoint_label') }}</label>
                <input type="url" name="manus_endpoint"
                       value="{{ $setting->manus_endpoint }}"
                       placeholder="https://api.manus.im/v1"
                       style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:monospace;outline:none;">
                <p style="font-size:11px;color:#94a3b8;margin:5px 0 0;">{{ __('admin.aiset_endpoint_hint') }}</p>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;padding-top:4px;">
            <button type="submit" class="btn-primary" style="padding:10px 28px;font-size:14px;">
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ __('admin.aiset_save_btn') }}
            </button>
        </div>
    </form>
</div>

{{-- 환경변수 안내 --}}
<div class="admin-card" style="max-width:680px;margin-top:16px;background:#f8fafc;">
    <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
        <svg width="15" height="15" fill="none" stroke="#64748b" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span style="font-size:12px;font-weight:600;color:#475569;">{{ __('admin.aiset_env_fallback_title') }}</span>
    </div>
    <p style="font-size:12px;color:#64748b;margin:0;line-height:1.6;">
        {{ __('admin.aiset_env_fallback_desc') }}
    </p>
</div>
@endsection
