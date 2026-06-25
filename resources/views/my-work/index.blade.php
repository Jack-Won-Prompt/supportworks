@extends('layouts.app')

@section('title', __('mywork.page_title'))

@section('breadcrumb')
<span style="color:var(--color-text-secondary);font-weight:500;">{{ __('mywork.page_title') }}</span>
@endsection

@push('styles')
<style>
.mw-widget{background:#fff;border:1px solid #e9e7fb;border-radius:14px;overflow:hidden;}
.mw-widget-hd{display:flex;align-items:center;gap:8px;padding:13px 18px;border-bottom:1px solid #f0f0f8;background:#fafafe;}
.mw-widget-title{font-size:13px;font-weight:700;color:#18181b;flex:1;}
.mw-cnt{display:inline-flex;align-items:center;border-radius:20px;padding:2px 9px;font-size:11px;font-weight:700;}
.mw-row{display:flex;align-items:flex-start;gap:10px;padding:11px 18px;border-bottom:1px solid #f5f5fb;cursor:pointer;transition:background .1s;}
.mw-row:last-child{border-bottom:none;}
.mw-row:hover{background:#fafaff;}
.mw-title{font-size:13px;font-weight:500;color:#18181b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.mw-meta{display:flex;align-items:center;gap:6px;margin-top:3px;flex-wrap:wrap;}
.mw-badge{display:inline-flex;align-items:center;border-radius:4px;padding:1px 6px;font-size:10.5px;font-weight:600;}
.mw-empty{padding:30px 18px;text-align:center;color:#9ca3af;font-size:12.5px;}
.mw-tab{padding:4px 10px;border-radius:6px;font-size:11.5px;font-weight:600;border:none;cursor:pointer;transition:all .12s;}
.mw-tab.on{background:var(--t600);color:#fff;}
.mw-tab:not(.on){background:#f0f0f8;color:#6b7280;}
.mw-tab-pane{display:none;}
.mw-tab-pane.on{display:block;}
.mw-more{padding:9px 18px;text-align:center;border-top:1px solid #f5f5fb;}
.mw-more a{font-size:12px;color:var(--tText);text-decoration:none;font-weight:600;}
.mw-pbar{margin-top:5px;height:3px;background:#e9e7fb;border-radius:2px;overflow:hidden;}
.mw-pbar-fill{height:100%;background:var(--t500);border-radius:2px;}

/* Stat cards */
.mw-stat{background:#fff;border:1.5px solid #e9e7fb;border-radius:12px;padding:14px 16px;display:flex;align-items:center;gap:12px;}
.mw-stat-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.mw-stat-num{font-size:24px;font-weight:800;line-height:1;}
.mw-stat-lbl{font-size:11px;color:#9ca3af;margin-top:2px;}

/* Slide-over */
#mw-so-bd{display:none;position:fixed;inset:0;background:rgba(0,0,0,.3);z-index:1299;}
#mw-so-bd.on{display:block;}
#mw-so{position:fixed;top:0;right:-460px;width:460px;height:100vh;background:#fff;box-shadow:-4px 0 40px rgba(0,0,0,.16);z-index:1300;display:flex;flex-direction:column;transition:right .25s cubic-bezier(.4,0,.2,1);overflow:hidden;}
#mw-so.on{right:0;}
#mw-so-body{flex:1;overflow-y:auto;padding:24px;}

/* iframe popup */
#mw-pop-bd{display:none;position:fixed;inset:0;z-index:1200;background:rgba(15,23,42,.55);align-items:center;justify-content:center;padding:20px;}
#mw-pop-bd.on{display:flex;}
#mw-pop-panel{background:#fff;border-radius:14px;width:min(940px,100%);height:min(88vh,900px);display:flex;flex-direction:column;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.22);}
#mw-pop-panel.compact{width:min(560px,100%);height:auto;max-height:min(80vh,700px);}
</style>
@endpush

@section('content')
@php $issueCount = $myIssues->count(); @endphp
<div class="space-y-5">

{{-- ── Header ── --}}
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
    <div>
        <h1 style="font-size:20px;font-weight:800;color:var(--color-text-primary);margin:0 0 2px;">{{ __('mywork.page_title') }}</h1>
        <p style="font-size:13px;color:var(--color-text-tertiary);margin:0;">{{ today()->locale(app()->getLocale())->isoFormat(__('mywork.date_format')) }} · {{ __('mywork.date_subtitle') }}</p>
    </div>
    <div style="display:flex;gap:8px;">
        <button onclick="document.getElementById('task-modal').style.display='flex'"
            style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;background:var(--t600);color:#fff;font-size:13px;font-weight:600;border-radius:8px;border:none;cursor:pointer;"
            onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('mywork.add_task') }}
        </button>
        <button onclick="document.getElementById('action-modal').style.display='flex'"
            style="display:inline-flex;align-items:center;gap:4px;padding:7px 14px;background:#fff;color:var(--color-text-secondary);font-size:13px;font-weight:600;border-radius:8px;border:1.5px solid var(--color-border-default);cursor:pointer;"
            onmouseover="this.style.borderColor='var(--t400)';this.style.color='var(--tText)'" onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#374151'">
            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            {{ __('mywork.add_action') }}
        </button>
    </div>
</div>

{{-- ── Stat Cards (5개) ── --}}
<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;">
    <div class="mw-stat" style="{{ $stats['overdue']>0?'border-color:#fca5a5;background:#fff5f5;':'' }}">
        <div class="mw-stat-icon" style="background:{{ $stats['overdue']>0?'var(--color-bg-danger-subtle)':'var(--color-bg-muted)' }};">
            <svg width="20" height="20" fill="none" stroke="{{ $stats['overdue']>0?'#dc2626':'#9ca3af' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        </div>
        <div>
            <div class="mw-stat-num" style="color:{{ $stats['overdue']>0?'var(--color-alert-warning-500)':'var(--color-text-tertiary)' }};">{{ $stats['overdue'] }}</div>
            <div class="mw-stat-lbl" style="{{ $stats['overdue']>0?'color:var(--color-alert-warning-500);font-weight:600;':'' }}">{{ __('mywork.stat_overdue') }}</div>
        </div>
    </div>
    <div class="mw-stat" style="{{ $stats['due_today']>0?'border-color:#fcd34d;background:#fffbeb;':'' }}">
        <div class="mw-stat-icon" style="background:{{ $stats['due_today']>0?'#fef3c7':'var(--color-bg-muted)' }};">
            <svg width="20" height="20" fill="none" stroke="{{ $stats['due_today']>0?'#d97706':'#9ca3af' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="mw-stat-num" style="color:{{ $stats['due_today']>0?'#d97706':'var(--color-text-tertiary)' }};">{{ $stats['due_today'] }}</div>
            <div class="mw-stat-lbl">{{ __('mywork.stat_due_today') }}</div>
        </div>
    </div>
    <div class="mw-stat">
        <div class="mw-stat-icon" style="background:#dbeafe;">
            <svg width="20" height="20" fill="none" stroke="#2563eb" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        </div>
        <div>
            <div class="mw-stat-num" style="color:#2563eb;">{{ $stats['in_progress'] }}</div>
            <div class="mw-stat-lbl">{{ __('mywork.stat_in_progress') }}</div>
        </div>
    </div>
    <div class="mw-stat" style="{{ $issueCount>0?'border-color:#fed7aa;background:#fff7ed;':'' }}">
        <div class="mw-stat-icon" style="background:{{ $issueCount>0?'#ffedd5':'var(--color-bg-muted)' }};">
            <svg width="20" height="20" fill="none" stroke="{{ $issueCount>0?'#ea580c':'#9ca3af' }}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="mw-stat-num" style="color:{{ $issueCount>0?'#ea580c':'var(--color-text-tertiary)' }};">{{ $issueCount }}</div>
            <div class="mw-stat-lbl">{{ __('mywork.stat_my_issues') }}</div>
        </div>
    </div>
    <div class="mw-stat">
        <div class="mw-stat-icon" style="background:var(--t100);">
            <svg width="20" height="20" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        </div>
        <div>
            <div class="mw-stat-num" style="color:var(--t600);">{{ $stats['total_open'] }}</div>
            <div class="mw-stat-lbl">{{ __('mywork.stat_total_open') }}</div>
        </div>
    </div>
</div>

{{-- ── Main 2-Column Grid ── --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;align-items:start;">

{{-- ╔══ LEFT ══════════════════════════════════════════╗ --}}
<div class="space-y-4">

{{-- Widget: Tasks --}}
@php
    $urgentAll = $overdueTasks->merge($dueTodayTasks)->unique('id')->sortBy('due_date');
    $urgentCnt = $urgentAll->count();
    $firstTab  = $urgentCnt > 0 ? 'urgent' : 'todo';
@endphp
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span class="mw-widget-title">Tasks</span>
        <div style="display:flex;gap:4px;flex-shrink:0;">
            @if($urgentCnt > 0)
            <button class="mw-tab {{ $firstTab==='urgent'?'on':'' }}" onclick="mwTab('task','urgent',this)">{{ __('mywork.tab_urgent') }} <b style="background:var(--color-bg-danger-subtle);color:var(--color-alert-warning-500);border-radius:10px;padding:0 5px;font-size:10px;font-weight:700;margin-left:2px;">{{ $urgentCnt }}</b></button>
            @endif
            <button class="mw-tab {{ $firstTab==='todo'?'on':'' }}" onclick="mwTab('task','todo',this)">{{ __('mywork.tab_todo') }} <b style="background:#f0f0f8;color:var(--color-text-secondary);border-radius:10px;padding:0 5px;font-size:10px;font-weight:700;margin-left:2px;">{{ $todoTasks->count() }}</b></button>
            <button class="mw-tab" onclick="mwTab('task','prog',this)">{{ __('mywork.tab_in_progress') }} <b style="background:#dbeafe;color:#2563eb;border-radius:10px;padding:0 5px;font-size:10px;font-weight:700;margin-left:2px;">{{ $inProgressTasks->count() }}</b></button>
        </div>
        <a href="{{ route('tasks.index') }}" style="font-size:11px;color:var(--tText);text-decoration:none;font-weight:600;flex-shrink:0;">{{ __('mywork.view_all') }}</a>
    </div>

    {{-- 탭: 긴급 --}}
    @if($urgentCnt > 0)
    <div id="tab-task-urgent" class="mw-tab-pane {{ $firstTab==='urgent'?'on':'' }}">
        @foreach($urgentAll->take(8) as $t)
        @php $isOvd=$t->due_date&&$t->due_date->lt(today()); $prC=$t->priority==='high'?'#ef4444':($t->priority==='medium'?'#f59e0b':'#94a3b8'); @endphp
        <div class="mw-row" onclick="mwOpenTask({{ $t->id }})">
            <div style="width:3px;align-self:stretch;border-radius:2px;flex-shrink:0;background:{{ $prC }};"></div>
            <div style="flex:1;min-width:0;">
                <div class="mw-title">{{ $t->title }}</div>
                <div class="mw-meta">
                    @if($t->project)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ $t->project->name }}</span>@endif
                    <span style="font-size:11px;color:{{ $isOvd?'var(--color-alert-warning-500)':'#d97706' }};font-weight:700;">{{ $isOvd?__('mywork.badge_overdue'):__('mywork.badge_due_today') }} · {{ $t->due_date->format('m/d') }}</span>
                </div>
            </div>
            <div onclick="event.stopPropagation()" style="flex-shrink:0;">
                @php $ns=$t->status==='todo'?'in_progress':'done'; $nl=$t->status==='todo'?__('mywork.action_start'):__('mywork.action_done'); @endphp
                <form method="POST" action="{{ route('tasks.status',$t) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="{{ $ns }}">
                    <button type="submit" style="padding:3px 9px;font-size:11px;font-weight:600;border-radius:5px;border:1.5px solid #fca5a5;background:#fff5f5;color:var(--color-alert-warning-500);cursor:pointer;">{{ $nl }}</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- 탭: 할 일 --}}
    <div id="tab-task-todo" class="mw-tab-pane {{ $firstTab==='todo'?'on':'' }}">
        @forelse($todoTasks->take(10) as $t)
        @php $prC=$t->priority==='high'?'#ef4444':($t->priority==='medium'?'#f59e0b':'#94a3b8'); @endphp
        <div class="mw-row" onclick="mwOpenTask({{ $t->id }})">
            <div style="width:3px;align-self:stretch;border-radius:2px;flex-shrink:0;background:{{ $prC }};"></div>
            <div style="flex:1;min-width:0;">
                <div class="mw-title">{{ $t->title }}</div>
                <div class="mw-meta">
                    @if($t->project)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ $t->project->name }}</span>@endif
                    @if($t->due_date)<span style="font-size:11px;color:{{ $t->due_date->lt(today())?'var(--color-alert-warning-500)':'var(--color-text-secondary)' }};">{{ $t->due_date->format('m/d') }}</span>@endif
                    @if($t->priority==='high')<span class="mw-badge" style="background:var(--color-bg-danger-subtle);color:var(--color-alert-warning-500);">{{ __('mywork.badge_high') }}</span>@elseif($t->priority==='medium')<span class="mw-badge" style="background:#fef3c7;color:#92400e;">{{ __('mywork.badge_medium') }}</span>@endif
                </div>
            </div>
            <div onclick="event.stopPropagation()" style="flex-shrink:0;">
                <form method="POST" action="{{ route('tasks.status',$t) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="in_progress">
                    <button type="submit" style="padding:3px 9px;font-size:11px;font-weight:600;border-radius:5px;border:1.5px solid #d1d5db;background:#fff;color:var(--color-text-secondary);cursor:pointer;" onmouseover="this.style.borderColor='var(--t400)'" onmouseout="this.style.borderColor='#d1d5db'">{{ __('mywork.action_start') }}</button>
                </form>
            </div>
        </div>
        @empty
        <div class="mw-empty">{{ __('mywork.empty_todo') }}</div>
        @endforelse
        @if($todoTasks->count()>10)<div class="mw-more"><a href="{{ route('tasks.index') }}">{{ __('mywork.more_count', ['count' => $todoTasks->count()-10]) }}</a></div>@endif
    </div>

    {{-- 탭: 진행 중 --}}
    <div id="tab-task-prog" class="mw-tab-pane">
        @forelse($inProgressTasks->take(10) as $t)
        @php $prC=$t->priority==='high'?'#ef4444':($t->priority==='medium'?'#f59e0b':'#94a3b8'); @endphp
        <div class="mw-row" onclick="mwOpenTask({{ $t->id }})">
            <div style="width:3px;align-self:stretch;border-radius:2px;flex-shrink:0;background:{{ $prC }};"></div>
            <div style="flex:1;min-width:0;">
                <div class="mw-title">{{ $t->title }}</div>
                <div class="mw-meta">
                    @if($t->project)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ $t->project->name }}</span>@endif
                    @if($t->due_date)<span style="font-size:11px;color:var(--color-text-secondary);">{{ __('mywork.due_suffix', ['date' => $t->due_date->format('m/d')]) }}</span>@endif
                    <span class="mw-badge" style="background:#dbeafe;color:#1d4ed8;">{{ __('mywork.badge_in_progress') }}</span>
                </div>
            </div>
            <div onclick="event.stopPropagation()" style="flex-shrink:0;">
                <form method="POST" action="{{ route('tasks.status',$t) }}">@csrf @method('PATCH')
                    <input type="hidden" name="status" value="done">
                    <button type="submit" style="padding:3px 9px;font-size:11px;font-weight:600;border-radius:5px;border:1.5px solid #d1fae5;background:var(--color-bg-success-subtle);color:#065f46;cursor:pointer;">{{ __('mywork.action_done') }}</button>
                </form>
            </div>
        </div>
        @empty
        <div class="mw-empty">{{ __('mywork.empty_in_progress') }}</div>
        @endforelse
    </div>
</div>

{{-- Widget: Action Items --}}
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#8b5cf6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span class="mw-widget-title">Action Items</span>
        <span class="mw-cnt" style="background:var(--t100);color:var(--t700);">{{ $myActionItems->count() }}</span>
        <a href="{{ route('action-items.index') }}" style="font-size:11px;color:var(--tText);text-decoration:none;font-weight:600;">{{ __('mywork.view_all') }}</a>
    </div>
    @forelse($myActionItems->take(8) as $a)
    @php $aOvd=$a->due_date&&$a->due_date->lt(today()); @endphp
    <div class="mw-row" onclick="mwOpenAction({{ $a->id }})">
        <div onclick="event.stopPropagation()" style="flex-shrink:0;margin-top:1px;">
            <form method="POST" action="{{ route('action-items.toggle',$a) }}">@csrf @method('PATCH')
                <button type="submit" style="width:18px;height:18px;border-radius:50%;border:2px solid var(--t400);background:#fff;cursor:pointer;display:block;transition:all .15s;" onmouseover="this.style.background='#a78bfa'" onmouseout="this.style.background='#fff'"></button>
            </form>
        </div>
        <div style="flex:1;min-width:0;">
            <div class="mw-title">{{ $a->title }}</div>
            <div class="mw-meta">
                @if($a->project)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ $a->project->name }}</span>@endif
                @if($a->user_id!==$user->id&&$a->creator)<span style="font-size:11px;color:var(--color-text-tertiary);">{{ __('mywork.action_requested_by', ['name' => $a->creator->name]) }}</span>@endif
                @if($a->assigned_to&&$a->assigned_to!==$user->id&&$a->assignedUser)<span style="font-size:11px;color:var(--color-text-secondary);">→ {{ $a->assignedUser->name }}</span>@endif
                @if($a->due_date)<span style="font-size:11px;color:{{ $aOvd?'var(--color-alert-warning-500)':($a->isDueSoon()?'#d97706':'var(--color-text-secondary)') }};{{ $aOvd?'font-weight:700;':'' }}">{{ $aOvd?'⚠ ':'' }}{{ $a->due_date->format('m/d') }}</span>@endif
            </div>
        </div>
        <svg width="13" height="13" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" style="flex-shrink:0;margin-top:2px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    @empty
    <div class="mw-empty">{{ __('mywork.empty_action_items') }}</div>
    @endforelse
    @if($myActionItems->count()>8)<div class="mw-more"><a href="{{ route('action-items.index') }}">{{ __('mywork.more_count', ['count' => $myActionItems->count()-8]) }}</a></div>@endif
</div>

{{-- Widget: 프로젝트 담당 작업 (SubTasks) --}}
@if($mySubTasks->count())
@php $subsByProj = $mySubTasks->groupBy(fn($s)=>$s->project?->id ?? 0); @endphp
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#0891b2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span class="mw-widget-title">{{ __('mywork.project_subtasks') }}</span>
        <span class="mw-cnt" style="background:#cffafe;color:#0e7490;">{{ $mySubTasks->count() }}</span>
    </div>
    @foreach($subsByProj->take(3) as $pid => $subs)
    @php $projName=$subs->first()->project?->name??__('mywork.subtask_etc'); @endphp
    <div style="padding:7px 18px 2px;background:#f8f8fc;border-bottom:1px solid #f0f0f8;">
        <span style="font-size:11px;font-weight:700;color:var(--t600);">{{ $projName }}</span>
    </div>
    @foreach($subs->take(5) as $s)
    @php
        $sOvd=$s->end_date&&$s->end_date->lt(today());
        $sStat=match($s->status){'in_progress'=>['bg'=>'#dbeafe','c'=>'#1d4ed8','txt'=>__('mywork.badge_in_progress')],default=>['bg'=>'#f3f4f6','c'=>'#6b7280','txt'=>__('mywork.badge_not_started')]};
    @endphp
    <div class="mw-row" onclick="mwOpenSubTask({{ $s->id }})">
        <div style="flex:1;min-width:0;">
            <div class="mw-title">{{ $s->title }}</div>
            <div class="mw-meta">
                @if($s->taskGroup)<span style="font-size:11px;color:var(--color-text-tertiary);">{{ $s->taskGroup->title }}</span>@endif
                @if($s->start_date&&$s->end_date)<span style="font-size:11px;color:{{ $sOvd?'var(--color-alert-warning-500)':'var(--color-text-secondary)' }};{{ $sOvd?'font-weight:700;':'' }}">{{ $sOvd?'⚠ ':'' }}{{ $s->start_date->format('m/d') }}~{{ $s->end_date->format('m/d') }}</span>@endif
                <span class="mw-badge" style="background:{{ $sStat['bg'] }};color:{{ $sStat['c'] }};">{{ $sStat['txt'] }}</span>
            </div>
            @if($s->progress > 0)
            <div class="mw-pbar"><div class="mw-pbar-fill" style="width:{{ $s->progress }}%;"></div></div>
            @endif
        </div>
        <svg width="13" height="13" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    @endforeach
    @endforeach
</div>
@endif

</div>{{-- /LEFT --}}

{{-- ╔══ RIGHT ══════════════════════════════════════════╗ --}}
<div class="space-y-4">

{{-- Widget: 이슈 (담당) --}}
@if($myIssues->count())
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#ea580c" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="mw-widget-title">{{ __('mywork.my_issues') }}</span>
        <span class="mw-cnt" style="background:#ffedd5;color:#c2410c;">{{ $myIssues->count() }}</span>
    </div>
    @foreach($myIssues->take(8) as $iss)
    @php
        $iC=match($iss->priority){'critical'=>['l'=>'#dc2626','bg'=>'#fee2e2','c'=>'#dc2626','t'=>__('mywork.issue_critical')],'high'=>['l'=>'#ea580c','bg'=>'#ffedd5','c'=>'#c2410c','t'=>__('mywork.issue_high')],'medium'=>['l'=>'#d97706','bg'=>'#fef3c7','c'=>'#92400e','t'=>__('mywork.issue_medium')],default=>['l'=>'#94a3b8','bg'=>'#f3f4f6','c'=>'#6b7280','t'=>__('mywork.issue_low')]};
        $sC=match($iss->status){'처리중'=>['bg'=>'#dbeafe','c'=>'#1d4ed8'],'검증중'=>['bg'=>'#fef3c7','c'=>'#92400e'],default=>['bg'=>'#f3f4f6','c'=>'#6b7280']};
    @endphp
    <div class="mw-row" onclick="mwOpenPopup('{{ route('projects.issues.show',[$iss->project_id,$iss]) }}')">
        <div style="width:3px;align-self:stretch;border-radius:2px;flex-shrink:0;background:{{ $iC['l'] }};"></div>
        <div style="flex:1;min-width:0;">
            <div class="mw-title">{{ $iss->title }}</div>
            <div class="mw-meta">
                @if($iss->project)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ $iss->project->name }}</span>@endif
                <span class="mw-badge" style="background:{{ $iC['bg'] }};color:{{ $iC['c'] }};">{{ $iC['t'] }}</span>
                <span class="mw-badge" style="background:{{ $sC['bg'] }};color:{{ $sC['c'] }};">{{ $iss->status }}</span>
                @if($iss->category)<span style="font-size:11px;color:var(--color-text-tertiary);">{{ $iss->category }}</span>@endif
            </div>
        </div>
        <svg width="13" height="13" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    @endforeach
</div>
@endif

{{-- Widget: 회의 Action Items --}}
@if($myMeetingItems->count())
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#059669" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span class="mw-widget-title">{{ __('mywork.meeting_action_items') }}</span>
        <span class="mw-cnt" style="background:#d1fae5;color:#065f46;">{{ $myMeetingItems->count() }}</span>
    </div>
    @foreach($myMeetingItems as $mi)
    @php $miOvd=$mi->due_date&&$mi->due_date->lt(today()); $miS=match($mi->status){'in_progress'=>['bg'=>'#dbeafe','c'=>'#1d4ed8'],default=>['bg'=>'#f3f4f6','c'=>'#6b7280']}; @endphp
    <div class="mw-row" onclick="mwOpenPopup('{{ $mi->minute ? route('meeting-minutes.popup',$mi->minute) : '#' }}')">
        <div style="flex:1;min-width:0;">
            <div class="mw-title">{{ $mi->title }}</div>
            <div class="mw-meta">
                @if($mi->minute)<span style="font-size:11px;color:var(--t600);font-weight:500;">{{ Str::limit($mi->minute->title,22) }}</span>@endif
                <span class="mw-badge" style="background:{{ $miS['bg'] }};color:{{ $miS['c'] }};">{{ $mi->status }}</span>
                @if($mi->due_date)<span style="font-size:11px;color:{{ $miOvd?'var(--color-alert-warning-500)':'var(--color-text-secondary)' }};{{ $miOvd?'font-weight:700;':'' }}">{{ $miOvd?'⚠ ':'' }}{{ $mi->due_date->format('m/d') }}</span>@endif
            </div>
        </div>
        <svg width="13" height="13" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </div>
    @endforeach
</div>
@endif

{{-- Widget: 이번 주 위클리 --}}
@if(auth()->user()->hasFeature('weekly_reports'))
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span class="mw-widget-title">{{ __('mywork.this_week_weekly') }}</span>
        <span style="font-size:11px;color:var(--color-text-tertiary);">{{ $weekStart->format('m/d') }}~{{ $weekStart->copy()->addDays(6)->format('m/d') }}</span>
    </div>
    <div style="padding:16px 18px;">
        @if($thisWeekReport)
        <div style="display:flex;align-items:center;gap:12px;padding:12px 14px;background:var(--t50);border-radius:10px;cursor:pointer;transition:background .12s;"
            onclick="mwOpenPopup('{{ route('projects.weekly-reports.edit',[$thisWeekReport->project_id,$thisWeekReport]) }}')"
            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">
            <div style="width:38px;height:38px;background:linear-gradient(135deg,var(--t700),#4f46e5);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <svg width="18" height="18" fill="none" stroke="#fff" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:700;color:var(--color-text-primary);">{{ $thisWeekReport->week_label }}</div>
                @if($thisWeekReport->project)<div style="font-size:11.5px;color:var(--t600);margin-top:1px;">{{ $thisWeekReport->project->name }}</div>@endif
            </div>
            @if($thisWeekReport->status==='submitted')
            <span class="mw-badge" style="background:#d1fae5;color:#065f46;font-size:12px;padding:3px 9px;">{{ __('mywork.weekly_submitted') }}</span>
            @else
            <span class="mw-badge" style="background:#fef3c7;color:#92400e;font-size:12px;padding:3px 9px;">{{ __('mywork.weekly_draft') }}</span>
            @endif
        </div>
        @else
        <div style="text-align:center;padding:4px 0 8px;">
            <div style="font-size:12px;color:var(--color-text-tertiary);margin-bottom:12px;">{{ __('mywork.weekly_empty') }}</div>
        </div>
        @endif
        <a href="{{ route('my-weekly.index') }}" style="display:block;margin-top:10px;text-align:center;padding:7px;background:var(--t50);color:var(--tText);border-radius:7px;font-size:12px;font-weight:600;text-decoration:none;"
            onmouseover="this.style.background='#ede9fe'" onmouseout="this.style.background='#f5f3ff'">{{ __('mywork.weekly_list_view') }}</a>
    </div>
</div>
@endif

{{-- Widget: 최근 완료 --}}
@if($recentDoneTasks->count() || $recentDoneActions->count())
@php
    $allDone = $recentDoneTasks->map(fn($t)=>['tp'=>'Task','title'=>$t->title,'proj'=>$t->project?->name,'at'=>$t->updated_at,'bg'=>'#dbeafe','c'=>'#2563eb'])->toBase()
        ->merge($recentDoneActions->map(fn($a)=>['tp'=>'Action','title'=>$a->title,'proj'=>$a->project?->name,'at'=>$a->completed_at,'bg'=>'#ede9fe','c'=>'#7c3aed'])->toBase())
        ->sortByDesc('at')->take(6);
@endphp
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="mw-widget-title">{{ __('mywork.recent_done') }}</span>
    </div>
    @foreach($allDone as $d)
    <div style="display:flex;align-items:center;gap:12px;padding:9px 18px;border-bottom:1px solid #f5f5fb;">
        <svg width="14" height="14" fill="none" stroke="#10b981" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <div style="flex:1;min-width:0;">
            <div style="font-size:12.5px;color:var(--color-text-secondary);font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $d['title'] }}</div>
            <div style="display:flex;gap:8px;align-items:center;margin-top:1px;">
                @if($d['proj'])<span style="font-size:11px;color:var(--t600);">{{ $d['proj'] }}</span>@endif
                <span style="font-size:11px;color:var(--color-text-tertiary);">{{ $d['at']?->diffForHumans() }}</span>
            </div>
        </div>
        <span style="font-size:10px;color:{{ $d['c'] }};background:{{ $d['bg'] }};border-radius:4px;padding:1px 6px;flex-shrink:0;font-weight:600;">{{ $d['tp'] }}</span>
    </div>
    @endforeach
</div>
@endif

{{-- Widget: 바로가기 --}}
<div class="mw-widget">
    <div class="mw-widget-hd">
        <svg width="15" height="15" fill="none" stroke="#6b7280" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
        <span class="mw-widget-title">{{ __('mywork.shortcuts') }}</span>
    </div>
    <div style="padding:8px 10px;display:grid;grid-template-columns:1fr 1fr;gap:4px;">
        @if(auth()->user()->hasFeature('tasks'))
        <a href="{{ route('tasks.index') }}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--color-text-secondary);font-size:12.5px;transition:background .1s;" onmouseover="this.style.background='#f5f3ff';this.style.color='var(--tText)'" onmouseout="this.style.background='';this.style.color='#374151'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            {{ __('mywork.shortcut_kanban') }}
        </a>
        @endif
        @if(auth()->user()->hasFeature('action_items'))
        <a href="{{ route('action-items.index') }}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--color-text-secondary);font-size:12.5px;transition:background .1s;" onmouseover="this.style.background='#f5f3ff';this.style.color='var(--tText)'" onmouseout="this.style.background='';this.style.color='#374151'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Action Items
        </a>
        @endif
        @if(auth()->user()->hasFeature('weekly_reports'))
        <a href="{{ route('my-weekly.index') }}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--color-text-secondary);font-size:12.5px;transition:background .1s;" onmouseover="this.style.background='#f5f3ff';this.style.color='var(--tText)'" onmouseout="this.style.background='';this.style.color='#374151'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('mywork.shortcut_weekly') }}
        </a>
        @endif
        @if(auth()->user()->hasFeature('meeting_minutes'))
        <a href="{{ route('meeting-minutes.index') }}" style="display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:8px;text-decoration:none;color:var(--color-text-secondary);font-size:12.5px;transition:background .1s;" onmouseover="this.style.background='#f5f3ff';this.style.color='var(--tText)'" onmouseout="this.style.background='';this.style.color='#374151'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            {{ __('mywork.shortcut_minutes') }}
        </a>
        @endif
    </div>
</div>

</div>{{-- /RIGHT --}}
</div>{{-- /Grid --}}

@if(!$todoTasks->count()&&!$inProgressTasks->count()&&!$myActionItems->count()&&!$mySubTasks->count()&&!$myIssues->count()&&!$myMeetingItems->count())
<div class="mw-widget"><div style="padding:60px 24px;text-align:center;">
    <div style="width:56px;height:56px;background:var(--t50);border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;"><svg width="26" height="26" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
    <p style="font-size:16px;font-weight:700;color:var(--color-text-secondary);margin:0 0 6px;">{{ __('mywork.all_done_title') }}</p>
    <p style="font-size:13px;color:var(--color-text-tertiary);margin:0;">{{ __('mywork.all_done_desc') }}</p>
</div></div>
@endif

</div>{{-- /space-y-5 --}}

{{-- ══ 팝업 (iframe + HTML 직접 모드 공용) ══ --}}
<div id="mw-pop-bd" onclick="if(event.target===this)mwClosePopup()">
    <div id="mw-pop-panel">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-bottom:1px solid #e9e7fb;flex-shrink:0;">
            <span id="mw-pop-title" style="font-size:13px;font-weight:700;color:var(--color-text-secondary);">{{ __('mywork.detail_view') }}</span>
            <button onclick="mwClosePopup()" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);padding:4px;display:flex;align-items:center;">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <iframe id="mw-pop-iframe" src="" style="flex:1;border:none;width:100%;display:block;"></iframe>
        <div id="mw-pop-content" style="flex:1;overflow-y:auto;display:none;"></div>
    </div>
</div>

{{-- ══ Task 상세 슬라이드오버 ══ --}}
<div id="mw-so-bd" onclick="mwCloseSo()"></div>
<div id="mw-so">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid #f0f0f8;flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span id="mw-so-type-badge" style="font-size:10px;font-weight:700;border-radius:4px;padding:2px 8px;"></span>
            <span style="font-size:14px;font-weight:700;color:var(--color-text-primary);">{{ __('mywork.detail_info') }}</span>
        </div>
        <button onclick="mwCloseSo()" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);padding:4px;display:flex;">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div id="mw-so-body"></div>
</div>

{{-- ══ Task 추가 모달 ══ --}}
<div id="task-modal" style="display:none;position:fixed;inset:0;z-index:1400;align-items:center;justify-content:center;">
    <div onclick="document.getElementById('task-modal').style.display='none'" style="position:absolute;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;background:#fff;border-radius:16px;padding:24px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h3 style="font-size:15px;font-weight:700;color:var(--color-text-primary);margin:0;">{{ __('mywork.task_modal_heading') }}</h3>
            <button onclick="document.getElementById('task-modal').style.display='none'" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form action="{{ route('tasks.store') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            <input type="text" name="title" placeholder="{{ __('mywork.task_title_placeholder') }}" required autofocus style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);box-sizing:border-box;outline:none;" onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            <textarea name="description" placeholder="{{ __('mywork.desc_placeholder') }}" rows="2" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);resize:vertical;box-sizing:border-box;outline:none;" onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.priority_label') }}</label>
                    <select name="priority" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);outline:none;background:#fff;">
                        <option value="high">{{ __('mywork.badge_high') }}</option><option value="medium" selected>{{ __('mywork.badge_medium') }}</option><option value="low">{{ __('mywork.badge_low') }}</option>
                    </select></div>
                <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.due_date_label') }}</label>
                    <input type="date" name="due_date" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);box-sizing:border-box;outline:none;"></div>
            </div>
            @if($projects->count())
            <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.project_label') }}</label>
                <select name="project_id" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);outline:none;background:#fff;">
                    <option value="">{{ __('mywork.project_none') }}</option>
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select></div>
            @endif
            <button type="submit" style="padding:10px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">{{ __('mywork.submit_add') }}</button>
        </form>
    </div>
</div>

{{-- ══ Action Item 추가 모달 ══ --}}
<div id="action-modal" style="display:none;position:fixed;inset:0;z-index:1400;align-items:center;justify-content:center;">
    <div onclick="document.getElementById('action-modal').style.display='none'" style="position:absolute;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;background:#fff;border-radius:16px;padding:24px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h3 style="font-size:15px;font-weight:700;color:var(--color-text-primary);margin:0;">{{ __('mywork.action_modal_heading') }}</h3>
            <button onclick="document.getElementById('action-modal').style.display='none'" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);"><svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form action="{{ route('action-items.store') }}" method="POST" style="display:flex;flex-direction:column;gap:12px;">
            @csrf
            <input type="text" name="title" placeholder="{{ __('mywork.action_title_placeholder') }}" required style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);box-sizing:border-box;outline:none;" onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
            <textarea name="description" placeholder="{{ __('mywork.desc_placeholder') }}" rows="2" style="width:100%;padding:9px 12px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);resize:vertical;box-sizing:border-box;outline:none;" onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.assignee_label') }}</label>
                    <select name="assigned_to" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);outline:none;background:#fff;">
                        <option value="">{{ __('mywork.assignee_self') }}</option>
                        @foreach($teammates as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach
                    </select></div>
                <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.due_date_label') }}</label>
                    <input type="date" name="due_date" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);box-sizing:border-box;outline:none;"></div>
            </div>
            @if($projects->count())
            <div><label style="font-size:11.5px;font-weight:600;color:var(--color-text-secondary);display:block;margin-bottom:4px;">{{ __('mywork.project_label') }}</label>
                <select name="project_id" style="width:100%;padding:8px 10px;border:1.5px solid var(--color-border-default);border-radius:8px;font-size:13px;color:var(--color-text-secondary);outline:none;background:#fff;">
                    <option value="">{{ __('mywork.project_none') }}</option>
                    @foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select></div>
            @endif
            <button type="submit" style="padding:10px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">{{ __('mywork.submit_add') }}</button>
        </form>
    </div>
</div>
@endsection

@php
$_taskStatusBase  = route('tasks.status', 999999);
$_actionTogBase   = route('action-items.toggle', 999999);
$_allTasks = $todoTasks->merge($inProgressTasks)->unique('id');
$_mwTasksArr = [];
foreach ($_allTasks as $_t) {
    $_mwTasksArr[$_t->id] = [
        'id'        => $_t->id,
        'title'     => $_t->title,
        'desc'      => $_t->description ?? '',
        'priority'  => $_t->priority,
        'prLabel'   => $_t->priority_label,
        'status'    => $_t->status,
        'stLabel'   => $_t->status_label,
        'due'       => $_t->due_date?->format('Y-m-d') ?? '',
        'dueFmt'    => $_t->due_date?->format('Y.m.d') ?? '',
        'project'   => $_t->project?->name ?? '',
        'statusUrl' => str_replace('999999', ':id', $_taskStatusBase),
    ];
}
$_mwActionsArr = [];
foreach ($myActionItems as $_a) {
    $_mwActionsArr[$_a->id] = [
        'id'        => $_a->id,
        'title'     => $_a->title,
        'desc'      => $_a->description ?? '',
        'due'       => $_a->due_date?->format('Y-m-d') ?? '',
        'dueFmt'    => $_a->due_date?->format('Y.m.d') ?? '',
        'project'   => $_a->project?->name ?? '',
        'creator'   => $_a->creator?->name ?? '',
        'assignee'  => $_a->assignedUser?->name ?? '',
        'isOwn'     => $_a->user_id === $user->id,
        'toggleUrl' => str_replace('999999', ':id', $_actionTogBase),
    ];
}
$_mwSubsArr = [];
foreach ($mySubTasks as $_s) {
    $statMap = ['not_started'=>__('mywork.badge_not_started'),'in_progress'=>__('mywork.badge_in_progress'),'done'=>__('mywork.action_done')];
    $_mwSubsArr[$_s->id] = [
        'id'        => $_s->id,
        'title'     => $_s->title,
        'desc'      => $_s->description ?? '',
        'status'    => $_s->status,
        'stLabel'   => $statMap[$_s->status] ?? $_s->status,
        'progress'  => $_s->progress ?? 0,
        'start'     => $_s->start_date?->format('Y.m.d') ?? '',
        'end'       => $_s->end_date?->format('Y.m.d') ?? '',
        'endRaw'    => $_s->end_date?->format('Y-m-d') ?? '',
        'project'   => $_s->project?->name ?? '',
        'group'     => $_s->taskGroup?->title ?? '',
        'assignee'  => $_s->assignee?->name ?? '',
    ];
}
@endphp
@push('scripts')
<script>
// ── Task & Action 데이터 (슬라이드오버용) ──────────────
const mwTaskData   = @json($_mwTasksArr);
const mwActionData = @json($_mwActionsArr);
const mwSubData    = @json($_mwSubsArr);

const mwCsrf = '{{ csrf_token() }}';

// ── 번역 문자열 ──────────────────────────────────────
const mwT = {
    detailView:    @json(__('mywork.detail_view')),
    soTaskStart:   @json(__('mywork.so_task_next_start')),
    soTaskDone:    @json(__('mywork.so_task_next_done')),
    soDesc:        @json(__('mywork.so_section_desc')),
    soNoDesc:      @json(__('mywork.so_no_desc')),
    soDueOverdue:  @json(__('mywork.so_due_overdue')),
    soActionDone:  @json(__('mywork.so_action_complete')),
    soSubBadge:    @json(__('mywork.so_subtask_badge')),
    soSubDetail:   @json(__('mywork.so_subtask_detail')),
    soProgress:    @json(__('mywork.so_progress')),
    badgeNotStarted: @json(__('mywork.badge_not_started')),
    badgeInProgress: @json(__('mywork.badge_in_progress')),
    actionDone:    @json(__('mywork.action_done')),
};
function mwDueLabel(date) { return @json(__('mywork.so_due_label')).replace(':date', date); }
function mwRequested(name) { return @json(__('mywork.so_requested')).replace(':name', name); }
function mwAssignee(name) { return @json(__('mywork.so_assignee')).replace(':name', name); }

// ── 탭 전환 ──────────────────────────────────────────
function mwTab(group, name, btn) {
    document.querySelectorAll('[id^="tab-'+group+'-"]').forEach(p => p.classList.remove('on'));
    btn.closest('.mw-widget-hd').querySelectorAll('.mw-tab').forEach(b => b.classList.remove('on'));
    document.getElementById('tab-'+group+'-'+name).classList.add('on');
    btn.classList.add('on');
}

// ── 팝업 공통 열기/닫기 ──────────────────────────────
function mwOpenPopup(url) {
    if (!url || url === '#') return;
    const iframe  = document.getElementById('mw-pop-iframe');
    const content = document.getElementById('mw-pop-content');
    document.getElementById('mw-pop-panel').classList.remove('compact');
    iframe.style.display  = 'block';
    content.style.display = 'none';
    content.innerHTML = '';
    iframe.src = url + (url.includes('?') ? '&' : '?') + 'popup=1';
    document.getElementById('mw-pop-title').textContent = mwT.detailView;
    document.getElementById('mw-pop-bd').classList.add('on');
    document.body.style.overflow = 'hidden';
}
function mwOpenContentPopup(title, html) {
    const iframe  = document.getElementById('mw-pop-iframe');
    const content = document.getElementById('mw-pop-content');
    document.getElementById('mw-pop-panel').classList.add('compact');
    iframe.style.display  = 'none';
    iframe.src = '';
    content.style.display = 'block';
    content.innerHTML = html;
    document.getElementById('mw-pop-title').textContent = title;
    document.getElementById('mw-pop-bd').classList.add('on');
    document.body.style.overflow = 'hidden';
}
function mwClosePopup(refresh) {
    document.getElementById('mw-pop-bd').classList.remove('on');
    document.getElementById('mw-pop-iframe').src = '';
    document.getElementById('mw-pop-content').innerHTML = '';
    document.body.style.overflow = '';
    if (refresh) location.reload();
}
window.closeWeeklyReportPopup = function(r) { mwClosePopup(r); };

// ── 슬라이드오버 ─────────────────────────────────────
function mwOpenSo() {
    document.getElementById('mw-so').classList.add('on');
    document.getElementById('mw-so-bd').classList.add('on');
    document.body.style.overflow = 'hidden';
}
function mwCloseSo() {
    document.getElementById('mw-so').classList.remove('on');
    document.getElementById('mw-so-bd').classList.remove('on');
    document.body.style.overflow = '';
}

// ── Task 상세 슬라이드오버 ────────────────────────────
function mwOpenTask(id) {
    const t = mwTaskData[id];
    if (!t) return;
    const prMap = { high:['#fee2e2','#dc2626'], medium:['#fef3c7','#92400e'], low:['#f3f4f6','#6b7280'] };
    const pr = prMap[t.priority] || prMap.low;
    const stMap = { todo:['#f3f4f6','#374151'], in_progress:['#dbeafe','#1d4ed8'], done:['#d1fae5','#065f46'] };
    const st = stMap[t.status] || stMap.todo;
    const today = new Date().toISOString().split('T')[0];
    const isOverdue = t.due && t.due < today && t.status !== 'done';
    const dueColor = isOverdue ? '#dc2626' : '#374151';

    const badge = document.getElementById('mw-so-type-badge');
    badge.textContent = 'Task';
    badge.style.cssText = 'background:#dbeafe;color:#1d4ed8;font-size:10px;font-weight:700;border-radius:4px;padding:2px 8px;';

    const nextStatus = t.status === 'todo' ? 'in_progress' : (t.status === 'in_progress' ? 'done' : null);
    const nextLabel  = t.status === 'todo' ? mwT.soTaskStart : mwT.soTaskDone;
    const statusUrl  = t.statusUrl.replace(':id', t.id);

    document.getElementById('mw-so-body').innerHTML = `
<div style="padding:24px;">
  <div style="font-size:18px;font-weight:800;color:var(--color-text-primary);line-height:1.4;margin-bottom:16px;">${escHtml(t.title)}</div>
  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    <span style="background:${pr[0]};color:${pr[1]};border-radius:5px;padding:3px 10px;font-size:12px;font-weight:700;">${escHtml(t.prLabel)}</span>
    <span style="background:${st[0]};color:${st[1]};border-radius:5px;padding:3px 10px;font-size:12px;font-weight:700;">${escHtml(t.stLabel)}</span>
    ${t.project ? `<span style="background:var(--t100);color:var(--t700);border-radius:5px;padding:3px 10px;font-size:12px;font-weight:600;">${escHtml(t.project)}</span>` : ''}
  </div>
  ${t.due ? `
  <div style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:${isOverdue?'#fff5f5':'#f8f8fc'};border-radius:10px;margin-bottom:16px;">
    <svg width="16" height="16" fill="none" stroke="${dueColor}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span style="font-size:13px;color:${dueColor};font-weight:${isOverdue?'700':'500'};">${isOverdue?mwT.soDueOverdue:''}${mwDueLabel(escHtml(t.dueFmt))}</span>
  </div>` : ''}
  ${t.desc ? `
  <div style="margin-bottom:20px;">
    <div style="font-size:11.5px;font-weight:700;color:var(--color-text-tertiary);margin-bottom:8px;">${mwT.soDesc}</div>
    <div style="font-size:13.5px;color:var(--color-text-secondary);line-height:1.7;white-space:pre-wrap;background:#f8f8fc;border-radius:8px;padding:12px 14px;">${escHtml(t.desc)}</div>
  </div>` : `<div style="font-size:13px;color:var(--color-text-tertiary);margin-bottom:20px;padding:12px 14px;background:#f8f8fc;border-radius:8px;">${mwT.soNoDesc}</div>`}
  ${nextStatus ? `
  <form method="POST" action="${statusUrl}" style="margin-bottom:12px;">
    <input type="hidden" name="_token" value="${mwCsrf}">
    <input type="hidden" name="_method" value="PATCH">
    <input type="hidden" name="status" value="${nextStatus}">
    <button type="submit" style="width:100%;padding:11px;background:var(--t600);color:#fff;border:none;border-radius:9px;font-size:13.5px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='var(--t700)'" onmouseout="this.style.background='var(--t600)'">${nextLabel}</button>
  </form>` : ''}
</div>`;
    mwOpenSo();
}

// ── Action 상세 슬라이드오버 ──────────────────────────
function mwOpenAction(id) {
    const a = mwActionData[id];
    if (!a) return;
    const today = new Date().toISOString().split('T')[0];
    const isOverdue = a.due && a.due < today;
    const dueColor = isOverdue ? '#dc2626' : '#374151';
    const toggleUrl = a.toggleUrl.replace(':id', a.id);

    const badge = document.getElementById('mw-so-type-badge');
    badge.textContent = 'Action';
    badge.style.cssText = 'background:#ede9fe;color:#6d28d9;font-size:10px;font-weight:700;border-radius:4px;padding:2px 8px;';

    document.getElementById('mw-so-body').innerHTML = `
<div style="padding:24px;">
  <div style="font-size:18px;font-weight:800;color:var(--color-text-primary);line-height:1.4;margin-bottom:16px;">${escHtml(a.title)}</div>
  <div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;">
    ${a.project ? `<span style="background:var(--t100);color:var(--t700);border-radius:5px;padding:3px 10px;font-size:12px;font-weight:600;">${escHtml(a.project)}</span>` : ''}
    ${a.creator ? `<span style="background:var(--color-bg-muted);color:var(--color-text-secondary);border-radius:5px;padding:3px 10px;font-size:12px;">${mwRequested(escHtml(a.creator))}</span>` : ''}
    ${a.assignee ? `<span style="background:var(--color-bg-muted);color:var(--color-text-secondary);border-radius:5px;padding:3px 10px;font-size:12px;">${mwAssignee(escHtml(a.assignee))}</span>` : ''}
  </div>
  ${a.due ? `
  <div style="display:flex;align-items:center;gap:8px;padding:12px 14px;background:${isOverdue?'#fff5f5':'#f8f8fc'};border-radius:10px;margin-bottom:16px;">
    <svg width="16" height="16" fill="none" stroke="${dueColor}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span style="font-size:13px;color:${dueColor};font-weight:${isOverdue?'700':'500'};">${isOverdue?mwT.soDueOverdue:''}${mwDueLabel(escHtml(a.dueFmt))}</span>
  </div>` : ''}
  ${a.desc ? `
  <div style="margin-bottom:20px;">
    <div style="font-size:11.5px;font-weight:700;color:var(--color-text-tertiary);margin-bottom:8px;">${mwT.soDesc}</div>
    <div style="font-size:13.5px;color:var(--color-text-secondary);line-height:1.7;white-space:pre-wrap;background:#f8f8fc;border-radius:8px;padding:12px 14px;">${escHtml(a.desc)}</div>
  </div>` : `<div style="font-size:13px;color:var(--color-text-tertiary);margin-bottom:20px;padding:12px 14px;background:#f8f8fc;border-radius:8px;">${mwT.soNoDesc}</div>`}
  <form method="POST" action="${toggleUrl}" style="margin-bottom:12px;">
    <input type="hidden" name="_token" value="${mwCsrf}">
    <input type="hidden" name="_method" value="PATCH">
    <button type="submit" style="width:100%;padding:11px;background:var(--color-bg-success-subtle);color:#065f46;border:1.5px solid #d1fae5;border-radius:9px;font-size:13.5px;font-weight:700;cursor:pointer;" onmouseover="this.style.background='#dcfce7'" onmouseout="this.style.background='#f0fdf4'">${mwT.soActionDone}</button>
  </form>
</div>`;
    mwOpenSo();
}

// ── SubTask 상세 팝업 ─────────────────────────────────
function mwOpenSubTask(id) {
    const s = mwSubData[id];
    if (!s) return;
    const today = new Date().toISOString().split('T')[0];
    const isOverdue = s.endRaw && s.endRaw < today && s.status !== 'done';
    const stMap = {};
    stMap[mwT.badgeNotStarted] = ['#f3f4f6','#6b7280'];
    stMap[mwT.badgeInProgress] = ['#dbeafe','#1d4ed8'];
    stMap[mwT.actionDone]      = ['#d1fae5','#065f46'];
    const st = stMap[s.stLabel] || stMap[mwT.badgeNotStarted];
    const html = `
<div style="padding:32px;max-width:640px;margin:0 auto;">
  <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
    <span style="background:#cffafe;color:#0e7490;font-size:11px;font-weight:700;border-radius:4px;padding:2px 9px;">${mwT.soSubBadge}</span>
  </div>
  <div style="font-size:20px;font-weight:800;color:var(--color-text-primary);line-height:1.4;margin-bottom:18px;">${escHtml(s.title)}</div>
  <div style="display:flex;gap:8px;margin-bottom:24px;flex-wrap:wrap;">
    <span style="background:${st[0]};color:${st[1]};border-radius:5px;padding:4px 12px;font-size:13px;font-weight:700;">${escHtml(s.stLabel)}</span>
    ${s.project ? `<span style="background:var(--t100);color:var(--t700);border-radius:5px;padding:4px 12px;font-size:13px;font-weight:600;">${escHtml(s.project)}</span>` : ''}
    ${s.group   ? `<span style="background:var(--color-bg-muted);color:var(--color-text-secondary);border-radius:5px;padding:4px 12px;font-size:13px;">${escHtml(s.group)}</span>` : ''}
    ${s.assignee? `<span style="background:var(--color-bg-success-subtle);color:#065f46;border-radius:5px;padding:4px 12px;font-size:13px;">${mwAssignee(escHtml(s.assignee))}</span>` : ''}
  </div>
  ${(s.start || s.end) ? `
  <div style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:${isOverdue?'#fff5f5':'#f8f8fc'};border-radius:10px;margin-bottom:20px;border:1px solid ${isOverdue?'#fca5a5':'#e9e7fb'};">
    <svg width="18" height="18" fill="none" stroke="${isOverdue?'#dc2626':'#6b7280'}" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
    <span style="font-size:14px;color:${isOverdue?'var(--color-alert-warning-500)':'var(--color-text-secondary)'};font-weight:${isOverdue?'700':'500'};">${isOverdue?mwT.soDueOverdue:''}${escHtml(s.start)} ~ ${escHtml(s.end)}</span>
  </div>` : ''}
  ${s.progress > 0 ? `
  <div style="margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
      <span style="font-size:12px;font-weight:700;color:var(--color-text-tertiary);">${mwT.soProgress}</span>
      <span style="font-size:14px;font-weight:800;color:var(--t700);">${s.progress}%</span>
    </div>
    <div style="height:10px;background:#e9e7fb;border-radius:5px;overflow:hidden;">
      <div style="height:100%;width:${s.progress}%;background:linear-gradient(90deg,var(--t600),#4f46e5);border-radius:5px;"></div>
    </div>
  </div>` : ''}
  ${s.desc ? `
  <div>
    <div style="font-size:12px;font-weight:700;color:var(--color-text-tertiary);margin-bottom:10px;text-transform:uppercase;letter-spacing:.05em;">${mwT.soDesc}</div>
    <div style="font-size:14px;color:var(--color-text-secondary);line-height:1.8;white-space:pre-wrap;background:#f8f8fc;border-radius:10px;padding:16px 18px;border:1px solid #e9e7fb;">${escHtml(s.desc)}</div>
  </div>` : `<div style="font-size:14px;color:var(--color-text-tertiary);padding:16px 18px;background:#f8f8fc;border-radius:10px;text-align:center;">${mwT.soNoDesc}</div>`}
</div>`;
    mwOpenContentPopup(mwT.soSubDetail, html);
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── ESC 닫기 ─────────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if (e.key !== 'Escape') return;
    if (document.getElementById('mw-so').classList.contains('on')) { mwCloseSo(); return; }
    if (document.getElementById('mw-pop-bd').classList.contains('on')) { mwClosePopup(false); return; }
    ['task-modal','action-modal'].forEach(id => { document.getElementById(id).style.display = 'none'; });
});
</script>
@endpush
