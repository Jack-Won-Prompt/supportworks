@extends(request()->has('popup') ? 'layouts.popup' : 'layouts.app')
@section('title', $project->name . ' — ' . __('work.planning_title'))

@if(!request()->has('popup'))
@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('common.list') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('work.planning_title') }}</span>
@endsection
@endif

@section('header-actions')@endsection

@section('content')
@if(!request()->has('popup'))
@include('partials.project-nav', ['project'=>$project, 'active'=>'planning'])
@endif

<div style="display:flex;align-items:center;justify-content:center;padding:60px 20px;">
    <div style="background:#fff;border:1px solid #e4e4e7;border-radius:16px;padding:36px 40px;width:100%;max-width:600px;box-shadow:0 4px 24px rgba(0,0,0,.06);">
        <div style="text-align:center;margin-bottom:28px;">
            <div style="width:56px;height:56px;background:var(--t50);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;">
                <svg width="28" height="28" fill="none" stroke="var(--t500)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h2 style="font-size:18px;font-weight:700;color:#18181b;margin:0 0 6px;">{{ __('work.planning_new_heading') }}</h2>
            <p style="font-size:13px;color:#6b7280;margin:0;">{{ __('work.planning_empty_hint') }}</p>
        </div>

        <form method="POST" action="{{ route('projects.planning.store', $project) }}">
            @csrf
            @if(request()->has('popup'))
            <input type="hidden" name="popup" value="1">
            @endif

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('common.title') }} <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" required value="{{ old('title', __('planning.default_title', ['name' => $project->name])) }}"
                    style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t400)';this.style.outline='none'" onblur="this.style.borderColor='#d1d5db'">
            </div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('common.description') }}</label>
                <textarea name="description" rows="2" placeholder="{{ __('work.planning_form_desc_label') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t400)';this.style.outline='none'" onblur="this.style.borderColor='#d1d5db'">{{ old('description') }}</textarea>
            </div>

            <div style="margin-bottom:24px;">
                <label style="display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;">{{ __('work.planning_form_content_label') }}</label>
                <textarea name="content" rows="6" placeholder="# {{ __('work.planning_title') }}&#10;&#10;## {{ __('planning.content_section_label') }}"
                    style="width:100%;padding:9px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:13px;font-family:monospace;resize:vertical;box-sizing:border-box;"
                    onfocus="this.style.borderColor='var(--t400)';this.style.outline='none'" onblur="this.style.borderColor='#d1d5db'">{{ old('content') }}</textarea>
                <p style="margin-top:4px;font-size:11px;color:#9ca3af;">{{ __('work.planning_form_md_hint') }}</p>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;">
                <a href="{{ route('projects.show', $project) }}"
                    style="padding:9px 18px;font-size:13px;color:#6b7280;background:#f9fafb;border:1px solid #d1d5db;border-radius:8px;cursor:pointer;text-decoration:none;">{{ __('common.cancel') }}</a>
                <button type="submit"
                    style="padding:9px 24px;font-size:13px;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;font-weight:600;">
                    {{ __('work.planning_new') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
