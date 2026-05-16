@extends('layouts.app')
@section('title', __('ai.exec_history_title'))

@push('styles')
<style>
.ex-card { background:#fff; border:1.5px solid #e8e3ff; border-radius:12px; padding:16px 20px; margin-bottom:12px; transition:border-color .15s; }
.ex-card:hover { border-color:#c4b5fd; }
.ex-header { display:flex; align-items:center; gap:12px; margin-bottom:10px; }
.ex-badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.ex-badge-completed { background:#dcfce7; color:#16a34a; }
.ex-badge-failed { background:#fee2e2; color:#dc2626; }
.ex-input { font-size:13px; color:#1e293b; font-weight:500; margin-bottom:6px; }
.ex-meta { font-size:11px; color:#94a3b8; }
.ex-response { font-size:12px; color:#64748b; max-height:48px; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; margin-bottom:8px; }
.ex-code-tags { display:flex; gap:6px; flex-wrap:wrap; }
.ex-code-tag { padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; background:#1e1b2e; color:#e9d5ff; }
</style>
@endpush

@section('content')
<div style="max-width:900px;">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
        <div>
            <h1 style="font-size:18px;font-weight:800;color:#1e293b;margin:0 0 4px;">{{ __('ai.exec_history_title') }}</h1>
            <p style="font-size:13px;color:#94a3b8;margin:0;">{{ __('ai.exec_history_desc') }}</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="{{ route('ai.prompts.index') }}" style="padding:7px 14px;background:#f8f7ff;color:#7c3aed;border:1px solid #c4b5fd;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">{{ __('ai.prompt_library_link') }}</a>
            <a href="{{ route('ai.index') }}" style="padding:7px 14px;background:linear-gradient(135deg,#7c3aed,#6366f1);color:#fff;border-radius:8px;font-size:12px;font-weight:600;text-decoration:none;">{{ __('ai.ai_agent') }}</a>
        </div>
    </div>

    @forelse($executions as $ex)
    <div class="ex-card">
        <div class="ex-header">
            <span class="ex-badge ex-badge-{{ $ex->status }}">{{ $ex->status === 'completed' ? __('ai.exec_status_completed') : __('ai.exec_status_failed') }}</span>
            @if($ex->prompt)
            <span style="font-size:11px;background:#ede9fe;color:#7c3aed;padding:3px 10px;border-radius:20px;font-weight:600;">{{ $ex->prompt->name }}</span>
            @endif
            @if($ex->project)
            <span style="font-size:11px;background:#dbeafe;color:#1d4ed8;padding:3px 10px;border-radius:20px;font-weight:600;">{{ $ex->project->name }}</span>
            @endif
            <span class="ex-meta" style="margin-left:auto;">{{ $ex->created_at->format(__('ai.php_date_format')) }}</span>
        </div>
        <div class="ex-input">{{ Str::limit($ex->raw_input, 120) }}</div>
        @if($ex->ai_response)
        <div class="ex-response">{{ $ex->ai_response }}</div>
        @endif
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <div class="ex-code-tags">
                @if($ex->html_output) <span class="ex-code-tag">HTML</span> @endif
                @if($ex->css_output)  <span class="ex-code-tag">CSS</span> @endif
                @if($ex->js_output)   <span class="ex-code-tag">JS</span> @endif
            </div>
            @if($ex->files->count())
            <span style="font-size:11px;color:#64748b;background:#f1f5f9;padding:2px 8px;border-radius:6px;">{{ __('ai.exec_files_count', ['count' => $ex->files->count()]) }}</span>
            @endif
            <a href="{{ route('ai.executions.show', $ex) }}" style="margin-left:auto;font-size:12px;color:#7c3aed;font-weight:600;text-decoration:none;">{{ __('ai.exec_detail') }}</a>
        </div>
    </div>
    @empty
    <div style="text-align:center;padding:60px 20px;color:#94a3b8;">
        <div style="font-size:48px;opacity:.3;margin-bottom:12px;">📋</div>
        <div style="font-size:14px;font-weight:600;color:#64748b;">{{ __('ai.exec_no_history') }}</div>
        <div style="font-size:12px;margin-top:4px;">{{ __('ai.exec_no_history_hint') }}</div>
    </div>
    @endforelse

    @if($executions->hasPages())
    <div style="margin-top:16px;">{{ $executions->links() }}</div>
    @endif
</div>
@endsection
