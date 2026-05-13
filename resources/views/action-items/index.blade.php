@extends('layouts.app')

@section('title', 'Action Items')

@section('header-actions')
<button onclick="actionModal(true)"
    style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--t600);color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    {{ __('work.action_new') }}
</button>
@endsection

{{-- 모달 --}}
@section('modals')
<div id="actionModal" style="display:none;position:fixed;inset:0;z-index:1000;align-items:center;justify-content:center;">
    <div onclick="actionModal(false)" style="position:absolute;inset:0;background:rgba(0,0,0,.4);backdrop-filter:blur(2px);"></div>
    <div style="position:relative;background:#fff;border-radius:16px;padding:24px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,.2);margin:16px;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <h3 style="font-size:15px;font-weight:700;color:#111827;">{{ __('work.action_modal_heading') }}</h3>
            <button onclick="actionModal(false)" style="background:none;border:none;cursor:pointer;color:#9ca3af;padding:4px;line-height:0;" onmouseover="this.style.color='#374151'" onmouseout="this.style.color='#9ca3af'">
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form action="{{ route('action-items.store') }}" method="POST">
            @csrf
            <div style="display:flex;flex-direction:column;gap:12px;">
                <div>
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px;">{{ __('work.action_title_label') }}</label>
                    <input type="text" name="title" placeholder="{{ __('work.action_title_placeholder') }}" required value="{{ old('title') }}"
                        style="width:100%;padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px;">{{ __('work.action_desc_label') }} <span style="color:#9ca3af;font-weight:400;">({{ __('common.optional') }})</span></label>
                    <textarea name="description" placeholder="{{ __('work.action_desc_placeholder') }}" rows="3"
                        style="width:100%;padding:9px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;resize:vertical;box-sizing:border-box;"
                        onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">{{ old('description') }}</textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px;">{{ __('work.action_assignee_label') }}</label>
                        <select name="assigned_to" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;"
                            onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                            <option value="">{{ __('work.action_assignee_self') }}</option>
                            @foreach($teammates as $mate)
                            <option value="{{ $mate->id }}" {{ old('assigned_to')==$mate->id?'selected':'' }}>{{ $mate->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px;">{{ __('work.action_due_date_label') }}</label>
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;box-sizing:border-box;"
                            onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                    </div>
                </div>
                @if($projects->count())
                <div>
                    <label style="font-size:12px;font-weight:500;color:#374151;display:block;margin-bottom:4px;">{{ __('work.action_project_label') }} <span style="color:#9ca3af;font-weight:400;">({{ __('common.optional') }})</span></label>
                    <select name="project_id" style="width:100%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;color:#374151;"
                        onfocus="this.style.borderColor='var(--t400)'" onblur="this.style.borderColor='#e5e7eb'">
                        <option value="">{{ __('work.action_project_none') }}</option>
                        @foreach($projects as $proj)
                        <option value="{{ $proj->id }}" {{ old('project_id')==$proj->id?'selected':'' }}>{{ $proj->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:20px;">
                <button type="button" onclick="actionModal(false)"
                    style="padding:9px 18px;background:#f9fafb;color:#374151;border:1px solid #e5e7eb;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;"
                    onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">{{ __('common.cancel') }}</button>
                <button type="submit"
                    style="padding:9px 22px;background:var(--t600);color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;"
                    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">{{ __('common.add') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
function actionModal(open) {
    const el = document.getElementById('actionModal');
    el.style.display = open ? 'flex' : 'none';
    if (open) { el.querySelector('input[name=title]').focus(); }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') actionModal(false); });
@if($errors->any() || old('title'))
document.addEventListener('DOMContentLoaded', () => actionModal(true));
@endif
</script>
@endsection

@section('content')
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">

    {{-- ===== 왼쪽: 목록 ===== --}}
    <div>
        {{-- 필터 탭 + 프로젝트 드롭다운 --}}
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:14px;flex-wrap:wrap;">

            {{-- 상태 필터 탭 --}}
            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                @foreach([
                    'all'     => __('work.action_filter_all'),
                    'pending' => __('work.action_filter_pending'),
                    'mine'    => __('work.action_filter_mine'),
                ] as $key => $label)
                <a href="{{ route('action-items.index', ['filter'=>$key, 'project_id'=>$projectId]) }}"
                    style="padding:5px 14px;border-radius:20px;font-size:12px;font-weight:500;text-decoration:none;
                           {{ $filter===$key ? 'background:var(--t600);color:#fff;' : 'background:#fff;color:#6b7280;border:1px solid #e5e7eb;' }}">
                    {{ $label }}
                    @if($key==='pending') <span style="font-size:10px;opacity:.8;">({{ $stats['pending'] }})</span> @endif
                </a>
                @endforeach
            </div>

            {{-- 프로젝트 드롭다운 --}}
            @if($projects->count())
            <div x-data="{ open: false }" style="position:relative;">
                <button @click="open=!open"
                    style="display:inline-flex;align-items:center;gap:7px;padding:5px 13px;border-radius:20px;font-size:12px;font-weight:500;border:1px solid {{ $projectId ? 'var(--t400)' : '#e5e7eb' }};background:{{ $projectId ? 'var(--t50)' : '#fff' }};color:{{ $projectId ? 'var(--tText)' : '#6b7280' }};cursor:pointer;transition:all .15s;">
                    <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
                    {{ $selectedProject ? $selectedProject->name : __('work.task_project_label') }}
                    @if($projectId)
                    <a href="{{ route('action-items.index', ['filter'=>$filter]) }}"
                        @click.stop
                        style="display:inline-flex;align-items:center;justify-content:center;width:14px;height:14px;border-radius:50%;background:var(--t300);color:#fff;text-decoration:none;font-size:10px;line-height:1;">×</a>
                    @endif
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24" :style="open?'transform:rotate(180deg)':''" style="transition:transform .15s;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div x-show="open" @click.outside="open=false" x-transition
                    style="position:absolute;right:0;top:calc(100% + 6px);background:#fff;border:1px solid #e5e7eb;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:50;min-width:180px;padding:6px;">
                    <a href="{{ route('action-items.index', ['filter'=>$filter]) }}"
                        style="display:block;padding:7px 12px;font-size:12px;border-radius:7px;text-decoration:none;color:{{ !$projectId ? 'var(--tText)' : '#374151' }};background:{{ !$projectId ? 'var(--t50)' : 'transparent' }};">
                        {{ __('work.action_all_projects') }}
                    </a>
                    <div style="height:1px;background:#f3f4f6;margin:4px 0;"></div>
                    @foreach($projects as $proj)
                    <a href="{{ route('action-items.index', ['filter'=>$filter, 'project_id'=>$proj->id]) }}"
                        @click="open=false"
                        style="display:flex;align-items:center;gap:8px;padding:7px 12px;font-size:12px;border-radius:7px;text-decoration:none;color:{{ $projectId == $proj->id ? 'var(--tText)' : '#374151' }};background:{{ $projectId == $proj->id ? 'var(--t50)' : 'transparent' }};">
                        <span style="width:7px;height:7px;border-radius:2px;flex-shrink:0;background:var(--t400);"></span>
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $proj->name }}</span>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>

        {{-- 프로젝트 필터 활성 시 안내 바 --}}
        @if($selectedProject)
        <div style="display:flex;align-items:center;gap:8px;padding:8px 12px;background:var(--t50);border:1px solid var(--t200);border-radius:10px;margin-bottom:12px;font-size:12px;color:var(--tText);">
            <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"/></svg>
            <span><strong>{{ $selectedProject->name }}</strong> {{ __('work.action_project_active', ['name' => '']) }}</span>
            <a href="{{ route('action-items.index', ['filter'=>$filter]) }}" style="margin-left:auto;color:var(--t600);text-decoration:none;font-weight:500;">{{ __('work.action_filter_clear') }}</a>
        </div>
        @endif

        @php
            $pendingItems   = $items->where('is_completed', false);
            $completedItems = $items->where('is_completed', true)->sortByDesc('completed_at');
        @endphp

        {{-- 미완료 목록 --}}
        @if($pendingItems->isEmpty() && $completedItems->isEmpty())
        <div style="text-align:center;padding:50px;background:#fff;border-radius:14px;border:1px solid #e5e7eb;">
            <svg width="36" height="36" fill="none" stroke="#d1d5db" viewBox="0 0 24 24" style="margin:0 auto 10px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <p style="font-size:14px;color:#9ca3af;">{{ __('work.action_empty') }}</p>
        </div>
        @else

        @if($pendingItems->isEmpty())
        <div style="text-align:center;padding:28px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;margin-bottom:14px;">
            <svg width="22" height="22" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="margin:0 auto 6px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p style="font-size:13px;color:#16a34a;font-weight:500;">{{ __('work.action_all_done') }}</p>
        </div>
        @else
        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px;">
            @foreach($pendingItems as $item)
            @include('action-items._item', ['item' => $item])
            @endforeach
        </div>
        @endif

        {{-- 완료 항목 토글 --}}
        @if($completedItems->count())
        <div x-data="{ open: false }">
            <button @click="open = !open"
                style="display:flex;align-items:center;gap:8px;width:100%;padding:10px 14px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:10px;cursor:pointer;font-size:12px;font-weight:500;color:#6b7280;transition:background .15s;"
                onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='#f9fafb'">
                <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span style="color:#374151;">{{ __('work.action_completed_toggle') }}</span>
                <span style="background:#dcfce7;color:#16a34a;font-size:11px;font-weight:600;padding:1px 8px;border-radius:10px;">{{ $completedItems->count() }}</span>
                <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-left:auto;transition:transform .2s;" :style="open ? 'transform:rotate(180deg)' : ''">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div x-show="open" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                style="display:flex;flex-direction:column;gap:6px;margin-top:6px;">
                @foreach($completedItems as $item)
                @include('action-items._item', ['item' => $item])
                @endforeach
            </div>
        </div>
        @endif

        @endif
    </div>

    {{-- ===== 오른쪽: 통계 패널 ===== --}}
    <div style="display:flex;flex-direction:column;gap:14px;position:sticky;top:16px;">

        {{-- 통계 카드 --}}
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.05);">
            <div style="font-size:12px;font-weight:600;color:#9ca3af;letter-spacing:.05em;text-transform:uppercase;margin-bottom:12px;">{{ __('work.action_stat_overview') }}</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <div style="background:#f9fafb;border-radius:10px;padding:12px;text-align:center;">
                    <p style="font-size:22px;font-weight:700;color:#111827;line-height:1;">{{ $stats['total'] }}</p>
                    <p style="font-size:11px;color:#6b7280;margin-top:3px;">{{ __('work.action_stat_total') }}</p>
                </div>
                <div style="background:#fef3c7;border-radius:10px;padding:12px;text-align:center;">
                    <p style="font-size:22px;font-weight:700;color:#d97706;line-height:1;">{{ $stats['pending'] }}</p>
                    <p style="font-size:11px;color:#92400e;margin-top:3px;">{{ __('work.action_stat_pending') }}</p>
                </div>
                <div style="background:#f0fdf4;border-radius:10px;padding:12px;text-align:center;">
                    <p style="font-size:22px;font-weight:700;color:#16a34a;line-height:1;">{{ $stats['completed'] }}</p>
                    <p style="font-size:11px;color:#166534;margin-top:3px;">{{ __('work.action_stat_completed') }}</p>
                </div>
                <div style="background:#fee2e2;border-radius:10px;padding:12px;text-align:center;">
                    <p style="font-size:22px;font-weight:700;color:#dc2626;line-height:1;">{{ $stats['overdue'] }}</p>
                    <p style="font-size:11px;color:#991b1b;margin-top:3px;">{{ __('work.action_stat_overdue') }}</p>
                </div>
            </div>
            @if($stats['total'] > 0)
            <div style="margin-top:12px;">
                <div style="display:flex;justify-content:space-between;font-size:11px;color:#6b7280;margin-bottom:4px;">
                    <span>{{ __('work.action_progress') }}</span>
                    <span>{{ $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0 }}%</span>
                </div>
                <div style="height:6px;background:#f3f4f6;border-radius:10px;overflow:hidden;">
                    <div style="height:100%;background:linear-gradient(to right,var(--t400),var(--t600));border-radius:10px;width:{{ $stats['total'] > 0 ? round($stats['completed'] / $stats['total'] * 100) : 0 }}%;transition:width .4s;"></div>
                </div>
            </div>
            @endif
        </div>

        {{-- 이번 주 마감 --}}
        @if($dueThisWeek->count())
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.05);">
            <div style="font-size:12px;font-weight:600;color:#9ca3af;letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px;">{{ __('work.action_due_this_week') }}</div>
            <div style="display:flex;flex-direction:column;gap:7px;">
                @foreach($dueThisWeek as $item)
                <div style="display:flex;align-items:center;gap:8px;">
                    <span style="width:6px;height:6px;border-radius:50%;flex-shrink:0;background:{{ $item->isOverdue() ? '#ef4444' : '#f59e0b' }};"></span>
                    <p style="font-size:12px;color:#374151;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $item->title }}</p>
                    <span style="font-size:11px;color:{{ $item->isOverdue() ? '#dc2626' : '#d97706' }};flex-shrink:0;">{{ $item->due_date->format('m/d') }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 담당자별 현황 --}}
        @if($assigneeStats->count() > 1)
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.05);">
            <div style="font-size:12px;font-weight:600;color:#9ca3af;letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px;">{{ __('work.action_assignee_stat') }}</div>
            <div style="display:flex;flex-direction:column;gap:8px;">
                @foreach($assigneeStats as $stat)
                @php $pct = $stats['pending'] > 0 ? round($stat['count'] / $stats['pending'] * 100) : 0; @endphp
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:3px;">
                        <span style="color:#374151;font-weight:500;">{{ $stat['name'] }}</span>
                        <span style="color:#6b7280;">{{ __('work.action_item_count', ['count' => $stat['count']]) }}</span>
                    </div>
                    <div style="height:5px;background:#f3f4f6;border-radius:10px;overflow:hidden;">
                        <div style="height:100%;background:var(--t400);border-radius:10px;width:{{ $pct }}%;"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- 최근 완료 --}}
        @if($recentlyDone->count())
        <div style="background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:16px;box-shadow:0 1px 6px rgba(0,0,0,.05);">
            <div style="font-size:12px;font-weight:600;color:#9ca3af;letter-spacing:.05em;text-transform:uppercase;margin-bottom:10px;">{{ __('work.action_recently_done') }}</div>
            <div style="display:flex;flex-direction:column;gap:7px;">
                @foreach($recentlyDone as $item)
                <div style="display:flex;align-items:center;gap:8px;">
                    <svg width="13" height="13" fill="none" stroke="#16a34a" viewBox="0 0 24 24" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    <p style="font-size:12px;color:#6b7280;flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;text-decoration:line-through;">{{ $item->title }}</p>
                    @if($item->completed_at)
                    <span style="font-size:11px;color:#9ca3af;flex-shrink:0;">{{ $item->completed_at->format('m/d') }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
