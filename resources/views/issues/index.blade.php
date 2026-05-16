@extends('layouts.app')

@section('title', $project->name . ' - ' . __('issues.issues'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('projects.project') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('issues.breadcrumb_issue') }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
    <a href="{{ route('projects.issues.export', $project) }}"
       style="padding:6px 13px;font-size:12px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;text-decoration:none;background:#fff;"
       onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('issues.csv_export') }}</a>
    <button onclick="openCreateModal()"
            style="padding:6px 14px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;"
            onmouseover="this.style.background='var(--t600)'" onmouseout="this.style.background='var(--t500)'">{{ __('issues.new_issue') }}</button>
@endsection

@section('content')
@include('partials.project-nav', ['project' => $project, 'active' => 'issues'])

{{-- 필터 바 --}}
<form method="GET" id="filter-form"
      style="background:#fff;border:1px solid #f3f4f6;border-radius:10px;padding:12px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;align-items:center;gap:8px;">
    <input type="hidden" name="view" value="{{ $view }}">

    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('issues.search_placeholder') }}"
           style="flex:1;min-width:160px;padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;outline:none;"
           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">

    <select name="status" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">{{ __('issues.filter_all_status') }}</option>
        @foreach(\App\Models\Issue::STATUS_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="priority" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">{{ __('issues.filter_all_priority') }}</option>
        @foreach(\App\Models\Issue::PRIORITY_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('priority') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="category" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">{{ __('issues.filter_all_category') }}</option>
        @foreach(\App\Models\Issue::CATEGORY_LABELS as $val => $label)
            <option value="{{ $val }}" {{ request('category') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
    </select>

    <select name="assignee" onchange="this.form.submit()"
            style="padding:6px 10px;border:1.5px solid #e4e4e7;border-radius:7px;font-size:13px;color:#374151;outline:none;background:#fff;">
        <option value="">{{ __('issues.filter_all_assignee') }}</option>
        @foreach($members as $m)
            <option value="{{ $m->id }}" {{ request('assignee') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
        @endforeach
    </select>

    @if(request()->hasAny(['status','priority','category','assignee','search']))
    <a href="{{ route('projects.issues.index', $project) }}"
       style="padding:6px 10px;font-size:12px;color:#6b7280;border:1.5px solid #e4e4e7;border-radius:7px;text-decoration:none;">{{ __('common.reset') }}</a>
    @endif

    {{-- 뷰 토글 --}}
    <div style="margin-left:auto;display:flex;gap:4px;">
        <a href="?{{ http_build_query(array_merge(request()->except(['view','page']), ['view'=>'table'])) }}"
           style="padding:5px 10px;font-size:12px;border-radius:6px;text-decoration:none;border:1.5px solid;
                  {{ $view==='table' ? 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;font-weight:600;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">{{ __('issues.view_table') }}</a>
        <a href="?{{ http_build_query(array_merge(request()->except(['view','page']), ['view'=>'kanban'])) }}"
           style="padding:5px 10px;font-size:12px;border-radius:6px;text-decoration:none;border:1.5px solid;
                  {{ $view==='kanban' ? 'background:#eef2ff;color:#4f46e5;border-color:#c7d2fe;font-weight:600;' : 'background:#fff;color:#6b7280;border-color:#e5e7eb;' }}">{{ __('issues.view_kanban') }}</a>
    </div>
</form>

@if($view === 'kanban')
{{-- ─── 칸반 보드 ─────────────────────────────────────── --}}
<div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:12px;align-items:flex-start;">
    @foreach(\App\Models\Issue::STATUS_LABELS as $status => $label)
    @php
        $color = \App\Models\Issue::STATUS_COLORS[$status] ?? ['bg'=>'#f3f4f6','text'=>'#6b7280'];
        $cols  = $kanbanGroups[$status] ?? collect();
    @endphp
    <div style="min-width:230px;flex:0 0 230px;background:#f9fafb;border-radius:10px;border:1px solid #e5e7eb;display:flex;flex-direction:column;">
        <div style="padding:10px 12px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:8px;justify-content:space-between;">
            <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $color['bg'] }};color:{{ $color['text'] }};">{{ $label }}</span>
            <span style="font-size:11px;color:#9ca3af;font-weight:500;">{{ $cols->count() }}</span>
        </div>
        <div style="padding:8px;display:flex;flex-direction:column;gap:7px;min-height:80px;">
            @forelse($cols as $issue)
            <a href="{{ route('projects.issues.show', [$project, $issue]) }}"
               style="display:block;background:#fff;border:1px solid #e5e7eb;border-radius:8px;padding:10px 12px;text-decoration:none;transition:box-shadow .12s;"
               onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='none'">
                <p style="font-size:12px;font-weight:600;color:#111827;margin:0 0 6px;line-height:1.4;">{{ Str::limit($issue->title, 50) }}</p>
                <div style="display:flex;align-items:center;gap:5px;flex-wrap:wrap;">
                    @php $pc = \App\Models\Issue::PRIORITY_COLORS[$issue->priority] ?? ['bg'=>'#f3f4f6','text'=>'#6b7280']; @endphp
                    <span style="padding:1px 6px;border-radius:4px;font-size:10px;font-weight:600;background:{{ $pc['bg'] }};color:{{ $pc['text'] }};">{{ $issue->priority_label }}</span>
                    <span style="font-size:10px;color:#9ca3af;">{{ $issue->category }}</span>
                    @if($issue->assignee)
                    <span style="margin-left:auto;font-size:10px;color:#6b7280;background:#f3f4f6;padding:1px 6px;border-radius:4px;">{{ $issue->assignee->name }}</span>
                    @endif
                </div>
            </a>
            @empty
            <p style="font-size:11px;color:#d1d5db;text-align:center;padding:12px 0;">{{ __('issues.kanban_empty') }}</p>
            @endforelse
        </div>
    </div>
    @endforeach
</div>

@else
{{-- ─── 테이블 뷰 ─────────────────────────────────────── --}}
<div style="background:#fff;border:1px solid #f3f4f6;border-radius:10px;overflow:hidden;">
    <div style="display:grid;grid-template-columns:60px 80px 1fr 70px 80px 90px 90px;font-size:11px;font-weight:600;color:#9ca3af;text-transform:uppercase;padding:0 16px;background:#f9fafb;border-bottom:1px solid #f3f4f6;">
        <div style="padding:10px 0;">#</div>
        <div style="padding:10px 0;">{{ __('issues.col_category') }}</div>
        <div style="padding:10px 0;">{{ __('issues.col_title') }}</div>
        <div style="padding:10px 0;">{{ __('issues.col_priority') }}</div>
        <div style="padding:10px 0;">{{ __('issues.col_status') }}</div>
        <div style="padding:10px 0;">{{ __('issues.col_assignee') }}</div>
        <div style="padding:10px 0;">{{ __('issues.col_created_at') }}</div>
    </div>

    @forelse($issues as $issue)
    @php
        $sc = $issue->status_color;
        $pc = $issue->priority_color;
    @endphp
    <div style="display:grid;grid-template-columns:60px 80px 1fr 70px 80px 90px 90px;padding:0 16px;border-bottom:1px solid #f9fafb;align-items:center;transition:background .1s;"
         onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background='#fff'">
        <div style="padding:12px 0;font-size:12px;color:#9ca3af;">#{{ $issue->id }}</div>
        <div style="padding:12px 0;font-size:11px;color:#6b7280;">{{ $issue->category }}</div>
        <div style="padding:12px 0;">
            <a href="{{ route('projects.issues.show', [$project, $issue]) }}"
               style="font-size:13px;font-weight:500;color:#111827;text-decoration:none;"
               onmouseover="this.style.color='var(--t600)'" onmouseout="this.style.color='#111827'">{{ $issue->title }}</a>
            @if($issue->linkedRequirement)
            <span style="margin-left:6px;font-size:10px;color:#6b7280;background:#f3f4f6;padding:1px 6px;border-radius:4px;">{{ __('issues.requirement_linked') }}</span>
            @endif
            @if($issue->tags)
            <div style="display:flex;flex-wrap:wrap;gap:3px;margin-top:3px;">
                @foreach($issue->tags as $tag)
                <span style="font-size:10px;color:#6b7280;background:#f3f4f6;padding:0px 5px;border-radius:3px;">{{ $tag }}</span>
                @endforeach
            </div>
            @endif
        </div>
        <div style="padding:12px 0;">
            <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $pc['bg'] }};color:{{ $pc['text'] }};">{{ $issue->priority_label }}</span>
        </div>
        <div style="padding:12px 0;">
            <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:600;background:{{ $sc['bg'] }};color:{{ $sc['text'] }};">{{ $issue->status }}</span>
        </div>
        <div style="padding:12px 0;font-size:12px;color:#374151;">{{ $issue->assignee?->name ?? '-' }}</div>
        <div style="padding:12px 0;font-size:11px;color:#9ca3af;">{{ $issue->created_at->format('m-d') }}</div>
    </div>
    @empty
    <div style="padding:48px;text-align:center;">
        <p style="font-size:14px;color:#9ca3af;">{{ __('issues.no_issues') }}</p>
    </div>
    @endforelse
</div>

{{ $issues->links() }}
@endif

{{-- ─── 새 이슈 모달 ─────────────────────────────────────── --}}
<div id="create-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:1000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:14px;padding:28px;width:560px;max-width:95vw;max-height:90vh;overflow-y:auto;position:relative;">
        <button onclick="closeCreateModal()" style="position:absolute;top:16px;right:16px;background:none;border:none;font-size:18px;color:#9ca3af;cursor:pointer;">✕</button>
        <h3 style="font-size:16px;font-weight:700;color:#111827;margin:0 0 20px;">{{ __('issues.create_modal_title') }}</h3>
        <form id="create-form" onsubmit="submitCreate(event)">
            <div style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_title') }}</label>
                    <input name="title" required style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_description') }}</label>
                    <textarea name="description" rows="3" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;resize:vertical;box-sizing:border-box;"
                              onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_category') }}</label>
                        <select name="category" required style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::CATEGORY_LABELS as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_priority') }}</label>
                        <select name="priority" required style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            @foreach(\App\Models\Issue::PRIORITY_LABELS as $v => $l)
                            <option value="{{ $v }}" {{ $v==='medium' ? 'selected' : '' }}>{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_severity') }}</label>
                        <select name="severity" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::SEVERITY_LABELS as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_environment') }}</label>
                        <select name="environment" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                            <option value="">-</option>
                            @foreach(\App\Models\Issue::ENVIRONMENT_LABELS as $v => $l)
                            <option value="{{ $v }}">{{ $l }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_assignee') }}</label>
                    <select name="assignee_id" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;background:#fff;box-sizing:border-box;">
                        <option value="">{{ __('issues.unassigned') }}</option>
                        @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:#374151;display:block;margin-bottom:5px;">{{ __('issues.field_tags') }}</label>
                    <input name="tags" placeholder="{{ __('issues.tags_placeholder') }}" style="width:100%;padding:8px 10px;border:1.5px solid #e4e4e7;border-radius:8px;font-size:13px;outline:none;box-sizing:border-box;"
                           onfocus="this.style.borderColor='var(--t500)'" onblur="this.style.borderColor='#e4e4e7'">
                </div>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="closeCreateModal()"
                        style="padding:8px 18px;font-size:13px;font-weight:500;color:#374151;border:1.5px solid #e4e4e7;border-radius:8px;background:#fff;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button type="submit" id="create-btn"
                        style="padding:8px 20px;font-size:13px;font-weight:600;color:#fff;background:var(--t500);border:none;border-radius:8px;cursor:pointer;">{{ __('issues.create_submit') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('create-modal').style.display = 'flex';
    document.querySelector('#create-form input[name="title"]').focus();
}
function closeCreateModal() {
    document.getElementById('create-modal').style.display = 'none';
    document.getElementById('create-form').reset();
}
async function submitCreate(e) {
    e.preventDefault();
    const btn = document.getElementById('create-btn');
    btn.disabled = true; btn.textContent = @json(__('issues.creating'));
    const fd = new FormData(e.target);
    const res = await fetch('{{ route('projects.issues.store', $project) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
        body: fd,
    });
    const data = await res.json();
    if (data.ok) {
        window.location.href = '{{ route('projects.issues.index', $project) }}';
    } else {
        alert(@json(__('issues.create_failed')));
        btn.disabled = false; btn.textContent = @json(__('issues.create_submit'));
    }
}
document.getElementById('create-modal').addEventListener('click', function(e) {
    if (e.target === this) closeCreateModal();
});
</script>
@endsection
