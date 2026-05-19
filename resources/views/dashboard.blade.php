@extends('layouts.app')

@section('title', __('dashboard.title'))

@section('header-actions')
<div style="display:flex;align-items:center;gap:8px;">
    <span id="gs-saved" style="font-size:11px;color:#22c55e;opacity:0;transition:opacity .4s;white-space:nowrap;">{{ __('dashboard.layout_saved') }}</span>
    <button onclick="resetDashboardLayout()"
            style="font-size:12px;padding:5px 12px;border:1px solid #e5e7eb;border-radius:7px;background:#fff;color:#6b7280;cursor:pointer;display:flex;align-items:center;gap:5px;white-space:nowrap;"
            onmouseover="this.style.borderColor='#c4b5fd';this.style.color='#7c3aed'"
            onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280'">
        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        {{ __('dashboard.reset_layout') }}
    </button>
</div>
@endsection

@section('content')
@php
$today = now();
$greeting = $today->hour < 12 ? __('dashboard.greeting_morning') : ($today->hour < 18 ? __('dashboard.greeting_afternoon') : __('dashboard.greeting_evening'));
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/gridstack@10.3.1/dist/gridstack.min.css"/>

<style>
/* ── GridStack base ── */
.grid-stack { background: transparent; }
.grid-stack-item-content { overflow: hidden; border-radius: 14px; }

/* ── Widget card ── */
.gs-card {
    background: #fff;
    border: 1px solid #f0eeff;
    border-radius: 14px;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}
.gs-card-dark {
    background: linear-gradient(160deg,#1e1b2e 0%,#2d2970 100%);
    border-color: #3d3880;
}

/* ── Drag handle (card header) ── */
.gs-drag-handle {
    cursor: grab;
    padding: 15px 20px 11px;
    flex-shrink: 0;
    user-select: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.gs-drag-handle:active { cursor: grabbing; }

/* Grip icon shown on hover */
.gs-grip {
    opacity: 0;
    transition: opacity .15s;
    flex-shrink: 0;
}
.gs-card:hover .gs-grip,
.gs-card-dark:hover .gs-grip { opacity: 1; }

/* ── Scrollable body ── */
.gs-card-body {
    flex: 1;
    overflow-y: auto;
    padding: 0 20px 16px;
    min-height: 0;
}
.gs-card-body::-webkit-scrollbar { width: 4px; }
.gs-card-body::-webkit-scrollbar-track { background: transparent; }
.gs-card-body::-webkit-scrollbar-thumb { background: #e0d9f7; border-radius: 4px; }

/* ── Stats KPI cards (fixed section above GridStack) ── */
.gs-kpi-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 10px;
}

/* ── Existing row hovers ── */
.db-row-hover:hover { background:#faf9ff; border-radius:8px; }
.ai-row-hover:hover { background:rgba(255,255,255,.04); border-radius:8px; }
.cal-day-hover:hover { background:#ede9fe !important; }
.action-row-hover:hover { background:#fff7ed; }

/* ── Today highlight (오늘 일정 강조) ── */
.db-today { background:#fffbeb !important; box-shadow:inset 3px 0 0 #f59e0b; border-radius:8px !important; }
.db-today::before {
    content:'오늘';
    font-size:9px; font-weight:800; color:#fff; background:#f59e0b;
    padding:2px 6px; border-radius:10px; flex-shrink:0; line-height:1.3;
    align-self:center;
}

/* ── Resize handle ── */
.ui-resizable-se {
    right:4px !important;
    bottom:4px !important;
    width:14px !important;
    height:14px !important;
    cursor:se-resize;
}

/* ── 온보딩 투어 (가벼운 1회용 가이드) ── */
#tour-block { position:fixed; inset:0; z-index:10000; background:transparent; }
#tour-hole {
    position:fixed; z-index:10001; border-radius:14px; pointer-events:none; display:none;
    box-shadow:0 0 0 9999px rgba(15,10,40,.66), 0 0 0 3px rgba(167,139,250,.95);
    transition:top .3s ease, left .3s ease, width .3s ease, height .3s ease;
}
#tour-pop {
    position:fixed; z-index:10002; width:320px; max-width:calc(100vw - 32px);
    background:#fff; border-radius:14px; padding:18px 18px 16px;
    box-shadow:0 20px 60px rgba(15,10,40,.45);
}
#tour-pop .tour-step-no { font-size:11px; font-weight:800; color:var(--t500); letter-spacing:.5px; }
#tour-pop .tour-title { font-size:15px; font-weight:800; color:#1e1b2e; margin:7px 0 6px; }
#tour-pop .tour-text { font-size:12.5px; color:#64748b; line-height:1.65; }
#tour-pop .tour-actions { display:flex; align-items:center; gap:8px; margin-top:16px; }
#tour-pop .tour-btn { font-size:12px; font-weight:700; padding:8px 15px; border-radius:8px; cursor:pointer; border:none; }
#tour-pop .tour-btn-skip { background:none; color:#94a3b8; padding:8px 4px; }
#tour-pop .tour-btn-skip:hover { color:#64748b; }
#tour-pop .tour-btn-prev { background:#f1f5f9; color:#475569; }
#tour-pop .tour-btn-prev:hover { background:#e2e8f0; }
#tour-pop .tour-btn-next { background:var(--t600); color:#fff; }
#tour-pop .tour-btn-next:hover { background:var(--t700); }
</style>

<div style="padding:20px 0 0;">

{{-- 인사말 --}}
<div style="margin-bottom:16px;">
    <div style="font-size:19px;font-weight:700;color:#1e1b2e;">{{ $greeting }}, {{ auth()->user()->name }}{{ __('dashboard.greeting_suffix') }}</div>
    <div style="font-size:13px;color:#94a3b8;margin-top:3px;">{{ $today->format(__('dashboard.date_format_full')) }} · {{ __('dashboard.greeting_wish') }}</div>
</div>

{{-- ═══════════════════════════════════════════
     KPI 통계 카드 (고정 영역 · GridStack 외부)
═══════════════════════════════════════════ --}}
<div class="gs-kpi-grid">

                    {{-- 전체 프로젝트 --}}
                    <div style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_total_projects') }}</span>
                            <div style="width:28px;height:28px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#1e1b2e;line-height:1;">{{ $totalProjects }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ __('dashboard.stat_active_count') }} <strong style="color:#22c55e;">{{ $activeProjects }}</strong>{{ __('dashboard.stat_active_suffix') }}</div>
                    </div>

                    {{-- 이번달 회의록 --}}
                    <div style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_minutes_month') }}</span>
                            <div style="width:28px;height:28px;background:#fce7f3;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="#ec4899" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#1e1b2e;line-height:1;">{{ $minutesThisMonth }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ $today->format('m') }}{{ __('dashboard.stat_month_label') }} {{ __('dashboard.stat_minutes_count') }}</div>
                    </div>

                    {{-- 내 Tasks --}}
                    <div style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_my_tasks') }}</span>
                            <div style="width:28px;height:28px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="#3b82f6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#1e1b2e;line-height:1;">{{ $todoTasks }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ __('dashboard.stat_tasks_in_progress') }}</div>
                    </div>

                    {{-- Action 아이템 --}}
                    <div style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_action_items') }}</span>
                            <div style="width:28px;height:28px;background:#fff7ed;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="#f97316" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#1e1b2e;line-height:1;">{{ $pendingActionItems }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ __('dashboard.stat_pending_items') }}</div>
                    </div>

                    {{-- 미답변 Q&A --}}
                    <div style="background:#fff;border:1px solid #f0eeff;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_unanswered_qa') }}</span>
                            <div style="width:28px;height:28px;background:#dcfce7;border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="#22c55e" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#1e1b2e;line-height:1;">{{ $pendingQuestions }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ __('dashboard.stat_awaiting_answer') }}</div>
                    </div>

                    {{-- 웍스 대화 --}}
                    <div style="background:linear-gradient(135deg,#1e1b2e 0%,#312e6e 100%);border:1px solid #3d3880;border-radius:14px;padding:14px 14px;display:flex;flex-direction:column;gap:5px;">
                        <div style="display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:10px;font-weight:600;color:#a5b4c8;text-transform:uppercase;letter-spacing:.5px;">{{ __('dashboard.stat_ai_chat') }}</span>
                            <div style="width:28px;height:28px;background:rgba(167,139,250,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;">
                                <svg width="14" height="14" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.414 2.798H4.213c-1.444 0-2.414-1.798-1.414-2.798L4.8 15.3"/></svg>
                            </div>
                        </div>
                        <div style="font-size:26px;font-weight:800;color:#fff;line-height:1;">{{ $totalAiSessions }}</div>
                        <div style="font-size:10px;color:#a5b4c8;">{{ __('dashboard.stat_ai_sessions') }}</div>
                    </div>

</div>{{-- /gs-kpi-grid --}}

<div class="grid-stack" id="dashboard-grid">

    {{-- ═══════════════════════════════════════════
         WIDGET: 최근 프로젝트
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="recent-projects" gs-x="0" gs-y="0" gs-w="4" gs-h="7" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#ede9fe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="var(--t600)" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.recent_projects') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="{{ route('projects.index') }}" style="font-size:12px;color:var(--t500);text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @php
                    $statusStyle = [
                        'active'    => 'background:#dcfce7;color:#16a34a',
                        'on_hold'   => 'background:#fef9c3;color:#ca8a04',
                        'completed' => 'background:#dbeafe;color:#2563eb',
                        'cancelled' => 'background:#fee2e2;color:#dc2626',
                    ];
                    @endphp
                    @forelse($projects as $project)
                    <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8f7ff;">
                        <div style="width:36px;height:36px;background:linear-gradient(135deg,var(--t400),var(--t600));border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;color:#fff;flex-shrink:0;">
                            {{ mb_substr($project->name, 0, 1) }}
                        </div>
                        <div style="flex:1;min-width:0;">
                            <a href="{{ route('projects.show', $project) }}" style="font-size:13px;font-weight:600;color:#1e1b2e;text-decoration:none;display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $project->name }}</a>
                            <div style="font-size:11px;color:#94a3b8;margin-top:1px;">{{ $project->creator->name }}</div>
                        </div>
                        <span style="font-size:11px;padding:3px 9px;border-radius:20px;font-weight:600;flex-shrink:0;{{ $statusStyle[$project->status] ?? 'background:#f1f5f9;color:#64748b' }}">
                            {{ $project->status_label }}
                        </span>
                    </div>
                    @empty
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:13px;">
                        {{ __('dashboard.no_projects') }}<br>
                        <a href="#" onclick="openNewProjectModal();return false;" style="color:var(--t500);font-size:12px;font-weight:600;text-decoration:none;">{{ __('dashboard.add_project') }}</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 달력
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="calendar" gs-x="0" gs-y="7" gs-w="4" gs-h="10" gs-min-w="2" gs-min-h="5">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                @php
                $calNow      = now();
                $firstDow    = $calNow->copy()->startOfMonth()->dayOfWeek;
                $daysInMonth = $calNow->daysInMonth;
                $todayNum    = $calNow->day;
                $priDot      = ['high'=>'#ef4444','medium'=>'#f59e0b','low'=>'#22c55e'];

                $schedByDay      = $calendarSchedules->groupBy(fn($s) => (int)$s->start_date->format('j'));
                $actionByDay     = $calendarActionItems->groupBy(fn($a) => (int)$a->due_date->format('j'));
                $meetingByDay    = $calendarMeetings->groupBy(fn($m) => (int)$m->meeting_date->format('j'));
                $discussionByDay = $calendarDiscussions->groupBy(fn($d) => (int)$d->discussion_date->format('j'));

                $todayStart = $calNow->copy()->startOfDay();
                $upcomingMerged = collect();
                foreach ($calendarSchedules->filter(fn($s) => $s->start_date->gte($todayStart)) as $s) {
                    $upcomingMerged->push(['type'=>'schedule','date'=>$s->start_date,'item'=>$s]);
                }
                foreach ($calendarActionItems->filter(fn($a) => $a->due_date->gte($todayStart->toDateString())) as $a) {
                    $upcomingMerged->push(['type'=>'action','date'=>$a->due_date,'item'=>$a]);
                }
                foreach ($calendarMeetings->filter(fn($m) => $m->meeting_date->gte($todayStart)) as $m) {
                    $upcomingMerged->push(['type'=>'meeting','date'=>$m->meeting_date,'item'=>$m]);
                }
                foreach ($calendarDiscussions->filter(fn($d) => $d->discussion_date->gte($todayStart)) as $d) {
                    $upcomingMerged->push(['type'=>'discussion','date'=>$d->discussion_date,'item'=>$d]);
                }
                $upcomingMerged = $upcomingMerged->sortBy('date')->values();
                @endphp

                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#dcfce7;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ $calNow->format(__('dashboard.cal_month_format')) }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:6px;">
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#dcfce7;color:#16a34a;font-weight:600;">{{ __('dashboard.cal_schedule_badge') }} {{ $calendarSchedules->count() }}</span>
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#fff7ed;color:#f97316;font-weight:600;">Action {{ $calendarActionItems->count() }}</span>
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#ede9fe;color:#7c3aed;font-weight:600;">회의 {{ $calendarMeetings->count() }}</span>
                        <span style="font-size:10px;padding:2px 7px;border-radius:10px;background:#e0f2fe;color:#0284c7;font-weight:600;">논의 {{ $calendarDiscussions->count() }}</span>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>

                <div class="gs-card-body">
                    {{-- 범례 --}}
                    <div style="display:flex;gap:10px;margin-bottom:8px;flex-wrap:wrap;">
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#7c3aed;"></div>{{ __('dashboard.legend_high') }}</div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#f59e0b;"></div>{{ __('dashboard.legend_medium') }}</div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#f97316;border:1.5px solid #fed7aa;"></div>Action</div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#7c3aed;"></div>회의</div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#0ea5e9;"></div>논의</div>
                        <div style="display:flex;align-items:center;gap:4px;font-size:10px;color:#64748b;"><div style="width:7px;height:7px;border-radius:50%;background:#ef4444;"></div>{{ __('dashboard.legend_delayed') }}</div>
                    </div>

                    {{-- 요일 헤더 --}}
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px;">
                        @foreach(__('dashboard.day_names') as $dow)
                        <div style="text-align:center;font-size:10px;font-weight:700;color:{{ $loop->first ? '#ef4444' : ($loop->last ? '#3b82f6' : '#94a3b8') }};padding:3px 0;">{{ $dow }}</div>
                        @endforeach
                    </div>

                    {{-- 날짜 셀 --}}
                    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;" id="cal-grid">
                        @php $cell = 0; @endphp
                        @for($row = 0; $row < 6; $row++)
                            @for($col = 0; $col < 7; $col++)
                                @php
                                $dayNum      = $cell - $firstDow + 1;
                                $isValid     = $dayNum >= 1 && $dayNum <= $daysInMonth;
                                $isToday     = $isValid && $dayNum === $todayNum;
                                $dayScheds      = $isValid ? ($schedByDay->get($dayNum) ?? collect()) : collect();
                                $dayActions     = $isValid ? ($actionByDay->get($dayNum) ?? collect()) : collect();
                                $dayMeetings    = $isValid ? ($meetingByDay->get($dayNum) ?? collect()) : collect();
                                $dayDiscussions = $isValid ? ($discussionByDay->get($dayNum) ?? collect()) : collect();
                                $hasAnything = $dayScheds->isNotEmpty() || $dayActions->isNotEmpty()
                                            || $dayMeetings->isNotEmpty() || $dayDiscussions->isNotEmpty();
                                $dayDots = collect();
                                foreach ($dayScheds as $ds)      $dayDots->push(['c'=>$priDot[$ds->priority] ?? '#7c3aed','b'=>false]);
                                foreach ($dayMeetings as $dm)    $dayDots->push(['c'=>'#7c3aed','b'=>false]);
                                foreach ($dayDiscussions as $dd) $dayDots->push(['c'=>'#0ea5e9','b'=>false]);
                                foreach ($dayActions as $da)     $dayDots->push(['c'=>$da->isOverdue() ? '#ef4444' : '#f97316','b'=>true]);
                                $cell++;
                                @endphp
                                <div onclick="{{ $hasAnything ? "showDayEvents($dayNum)" : '' }}"
                                     style="border-radius:8px;padding:5px 2px;text-align:center;min-height:44px;position:relative;
                                        {{ $isToday ? 'background:#f59e0b;box-shadow:0 0 0 2px #fde68a;'.($hasAnything ? 'cursor:pointer;' : '') : ($hasAnything ? 'background:#f5f3ff;cursor:pointer;' : '') }}
                                        {{ !$isValid ? 'opacity:0;pointer-events:none;' : '' }}"
                                     class="{{ $hasAnything && !$isToday ? 'cal-day-hover' : '' }}">
                                    @if($isValid)
                                    <div style="font-size:12px;font-weight:{{ $isToday ? '800' : '500' }};color:{{ $isToday ? '#fff' : ($col===0 ? '#ef4444' : ($col===6 ? '#3b82f6' : '#374151')) }};line-height:1.2;">{{ $dayNum }}</div>
                                    @if($isToday)
                                    <div style="font-size:7px;font-weight:800;color:#fff;letter-spacing:.5px;line-height:1;margin-top:1px;">오늘</div>
                                    @endif
                                    @if($hasAnything)
                                    <div style="display:flex;justify-content:center;gap:2px;margin-top:3px;flex-wrap:wrap;align-items:center;">
                                        @foreach($dayDots->take(4) as $dot)
                                        <div style="width:5px;height:5px;border-radius:50%;background:{{ $isToday ? 'rgba(255,255,255,.8)' : $dot['c'] }};{{ $dot['b'] && !$isToday ? 'border:1px solid rgba(249,115,22,.3);' : '' }}flex-shrink:0;"></div>
                                        @endforeach
                                        @if($dayDots->count() > 4)
                                        <div style="font-size:8px;color:{{ $isToday ? 'rgba(255,255,255,.8)' : '#94a3b8' }};line-height:5px;">+{{ $dayDots->count() - 4 }}</div>
                                        @endif
                                    </div>
                                    @endif
                                    @endif
                                </div>
                            @endfor
                            @if($cell > $firstDow + $daysInMonth) @break @endif
                        @endfor
                    </div>

                    {{-- 일별 팝업 --}}
                    <div id="cal-day-detail" style="display:none;margin-top:12px;padding:10px 12px;background:#f8f7ff;border-radius:10px;border:1px solid #e8e3ff;">
                        <div style="font-size:11px;font-weight:700;color:var(--t600);margin-bottom:8px;" id="cal-day-title"></div>
                        <div id="cal-day-events"></div>
                    </div>

                    {{-- 통합 이벤트 목록 --}}
                    @if($upcomingMerged->isNotEmpty())
                    <div style="margin-top:14px;padding-top:12px;border-top:1px solid #f0eeff;">
                        <div style="font-size:11px;font-weight:700;color:#94a3b8;margin-bottom:8px;">{{ __('dashboard.upcoming_events') }}</div>
                        @foreach($upcomingMerged->take(4) as $ev)
                        @php $evToday = $ev['date'] && \Illuminate\Support\Carbon::parse($ev['date'])->isToday(); @endphp
                        @if($ev['type'] === 'schedule')
                        @php $s = $ev['item']; @endphp
                        <div class="{{ $evToday ? 'db-today' : '' }}" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f8f7ff;">
                            <div style="width:6px;height:6px;border-radius:50%;background:{{ $priDot[$s->priority] ?? '#7c3aed' }};flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $s->title }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $s->start_date->format(__('dashboard.date_format_day_time')) }}{{ $s->project ? ' · '.$s->project->name : '' }}</div>
                            </div>
                            <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;flex-shrink:0;{{ $s->status==='pending' ? 'background:#fef9c3;color:#ca8a04' : 'background:#dbeafe;color:#2563eb' }}">{{ $s->status_label }}</span>
                        </div>
                        @elseif($ev['type'] === 'action')
                        @php $a = $ev['item']; @endphp
                        <div class="{{ $evToday ? 'db-today' : '' }}" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f8f7ff;">
                            <div style="width:6px;height:6px;border-radius:50%;background:{{ $a->isOverdue() ? '#ef4444' : '#f97316' }};flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $a->title }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ __('dashboard.deadline') }} {{ $a->due_date->format(__('dashboard.date_format_day')) }}{{ $a->project ? ' · '.$a->project->name : '' }}</div>
                            </div>
                            <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;flex-shrink:0;{{ $a->isOverdue() ? 'background:#fee2e2;color:#dc2626' : 'background:#fff7ed;color:#f97316' }}">{{ $a->isOverdue() ? __('dashboard.delayed') : 'Action' }}</span>
                        </div>
                        @elseif($ev['type'] === 'meeting')
                        @php $m = $ev['item']; @endphp
                        <div class="{{ $evToday ? 'db-today' : '' }}" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f8f7ff;">
                            <div style="width:6px;height:6px;border-radius:50%;background:#7c3aed;flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $m->title }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $m->meeting_date->format('n/j H:i') }}{{ $m->project ? ' · '.$m->project->name : '' }}</div>
                            </div>
                            <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;flex-shrink:0;background:#ede9fe;color:#7c3aed;">회의</span>
                        </div>
                        @else
                        @php $d = $ev['item']; @endphp
                        <div class="{{ $evToday ? 'db-today' : '' }}" style="display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid #f8f7ff;">
                            <div style="width:6px;height:6px;border-radius:50%;background:#0ea5e9;flex-shrink:0;"></div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $d->title }}</div>
                                <div style="font-size:10px;color:#94a3b8;">{{ $d->discussion_date->format('n/j') }}{{ $d->project ? ' · '.$d->project->name : '' }}</div>
                            </div>
                            <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;flex-shrink:0;background:#e0f2fe;color:#0284c7;">논의</span>
                        </div>
                        @endif
                        @endforeach
                    </div>
                    @else
                    <div style="text-align:center;padding:10px 0 0;font-size:12px;color:#94a3b8;">{{ __('dashboard.no_remaining_events') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 최근 파일
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="recent-files" gs-x="4" gs-y="0" gs-w="4" gs-h="5" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#f0fdf4;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#16a34a" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.recent_files') }}</span>
                    </div>
                    <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                        <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                        <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                        <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                    </svg>
                </div>
                <div class="gs-card-body">
                    @forelse($recentFiles as $file)
                    <div style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid #f8f7ff;">
                        <span style="font-size:18px;flex-shrink:0;">{{ $file->icon }}</span>
                        <div style="flex:1;min-width:0;">
                            <button onclick="openPreview({{ $file->id }}, {{ $file->project_id }})"
                                    style="background:none;border:none;cursor:pointer;padding:0;text-align:left;width:100%;">
                                <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;">{{ $file->original_name }}</div>
                            </button>
                            <div style="font-size:10px;color:#94a3b8;margin-top:2px;display:flex;align-items:center;gap:6px;">
                                <span>{{ $file->project->name }}</span>
                                <span>·</span>
                                <span>{{ $file->created_at->format('m.d') }}</span>
                                @if($file->comments_count > 0)
                                <span style="color:#7c3aed;font-weight:600;">{{ __('dashboard.opinions') }} {{ $file->comments_count }}</span>
                                @endif
                            </div>
                        </div>
                        <button onclick="openPreview({{ $file->id }}, {{ $file->project_id }})"
                                style="flex-shrink:0;background:none;border:1px solid #e5e7eb;border-radius:6px;padding:4px 8px;font-size:11px;color:#6b7280;cursor:pointer;white-space:nowrap;"
                                onmouseover="this.style.background='#f5f3ff';this.style.borderColor='#c4b5fd';this.style.color='#7c3aed'"
                                onmouseout="this.style.background='none';this.style.borderColor='#e5e7eb';this.style.color='#6b7280'">
                            {{ __('dashboard.open_file') }}
                        </button>
                    </div>
                    @empty
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:13px;">{{ __('dashboard.no_files') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 최근 회의록
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="recent-minutes" gs-x="4" gs-y="5" gs-w="4" gs-h="7" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#fce7f3;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#ec4899" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.recent_minutes') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="{{ route('meeting-minutes.index') }}" style="font-size:12px;color:var(--t500);text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @forelse($recentMinutes as $minute)
                    <a href="#" onclick="event.preventDefault(); dbOpenMinutePopup({{ $minute->id }})" style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid #f8f7ff;text-decoration:none;" class="db-row-hover{{ $minute->meeting_date->isToday() ? ' db-today' : '' }}">
                        <div style="width:42px;text-align:center;flex-shrink:0;">
                            <div style="font-size:16px;font-weight:800;color:var(--t600);line-height:1;">{{ $minute->meeting_date->format('d') }}</div>
                            <div style="font-size:10px;color:#94a3b8;font-weight:600;">{{ $minute->meeting_date->format('M') }}</div>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:13px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $minute->title }}</div>
                            <div style="font-size:11px;color:#94a3b8;margin-top:2px;">
                                {{ $minute->author->name }}
                                @if($minute->project) · {{ $minute->project->name }}@endif
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0;">
                            <span style="font-size:11px;padding:2px 8px;border-radius:20px;font-weight:600;{{ $minute->type === 'project' ? 'background:#ede9fe;color:var(--t600)' : 'background:#f0f9ff;color:#0284c7' }};">{{ $minute->type_label }}</span>
                            @if($minute->actionItems->count())
                            <span style="font-size:10px;color:#f97316;">Action {{ $minute->actionItems->count() }}{{ __('dashboard.count_items') }}</span>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div style="text-align:center;padding:28px 0;color:#94a3b8;font-size:13px;">
                        <div style="font-size:28px;margin-bottom:6px;">📋</div>
                        {{ __('dashboard.no_minutes') }}<br>
                        <a href="{{ route('meeting-minutes.index') }}?new=1" style="color:var(--t500);font-size:12px;font-weight:600;text-decoration:none;margin-top:4px;display:inline-block;">{{ __('dashboard.write_minutes') }}</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 웍스 최근 대화
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="ai-chat" gs-x="8" gs-y="0" gs-w="4" gs-h="8" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card gs-card-dark">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:rgba(167,139,250,.2);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .03 2.798-1.414 2.798H4.213c-1.444 0-2.414-1.798-1.414-2.798L4.8 15.3"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#e2e8f0;">{{ __('dashboard.ai_recent_chat') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="{{ route('ai.index') }}" style="font-size:12px;color:#a78bfa;text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#6b7a9e" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @forelse($recentAiSessions as $session)
                    <a href="{{ route('ai.index', ['session' => $session->id]) }}" style="display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,.06);text-decoration:none;" class="ai-row-hover">
                        <div style="width:32px;height:32px;background:rgba(167,139,250,.15);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="13" height="13" fill="none" stroke="#a78bfa" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;font-weight:600;color:#e2e8f0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $session->title ?: __('dashboard.new_chat_title') }}</div>
                            <div style="font-size:10px;color:#6b7a9e;margin-top:2px;">{{ __('dashboard.messages_count') }} {{ $session->messages_count }}{{ __('dashboard.count_messages') }} · {{ $session->updated_at->diffForHumans() }}</div>
                        </div>
                        @if($session->is_shared)
                        <span style="font-size:10px;padding:2px 6px;border-radius:10px;background:rgba(167,139,250,.2);color:#a78bfa;flex-shrink:0;">{{ __('dashboard.shared_badge') }}</span>
                        @endif
                    </a>
                    @empty
                    <div style="text-align:center;padding:20px 0;color:#6b7a9e;font-size:12px;">
                        <div style="font-size:22px;margin-bottom:6px;">🤖</div>
                        {{ __('dashboard.no_ai_chats') }}<br>
                        <a href="{{ route('ai.index') }}" style="color:#a78bfa;font-size:12px;font-weight:600;text-decoration:none;margin-top:4px;display:inline-block;">{{ __('dashboard.start_ai_chat') }}</a>
                    </div>
                    @endforelse
                    <a href="{{ route('ai.index') }}" style="display:flex;align-items:center;justify-content:center;gap:4px;padding:8px;border:1.5px dashed rgba(167,139,250,.3);border-radius:8px;font-size:12px;font-weight:600;color:#a78bfa;text-decoration:none;margin-top:10px;">
                        {{ __('dashboard.new_chat_btn') }}
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 내 Tasks
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="my-tasks" gs-x="8" gs-y="8" gs-w="4" gs-h="6" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#dbeafe;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#3b82f6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.my_tasks') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="{{ route('tasks.index') }}" style="font-size:12px;color:var(--t500);text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @php $priColor = ['high'=>'#ef4444','medium'=>'#f59e0b','low'=>'#22c55e']; @endphp
                    @forelse($myTasks as $task)
                    <div style="display:flex;align-items:center;gap:8px;padding:8px 0;border-bottom:1px solid #f8f7ff;">
                        <div style="width:7px;height:7px;border-radius:50%;background:{{ $priColor[$task->priority] ?? '#94a3b8' }};flex-shrink:0;"></div>
                        <div style="flex:1;min-width:0;">
                            <div style="font-size:12px;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $task->title }}</div>
                            @if($task->project)<div style="font-size:10px;color:#94a3b8;margin-top:1px;">{{ $task->project->name }}</div>@endif
                        </div>
                        <span style="font-size:10px;padding:2px 7px;border-radius:20px;font-weight:600;flex-shrink:0;{{ $task->status === 'in_progress' ? 'background:#dbeafe;color:#2563eb' : 'background:#f1f5f9;color:#64748b' }}">
                            {{ $task->status === 'in_progress' ? __('dashboard.task_in_progress') : __('dashboard.task_todo') }}
                        </span>
                    </div>
                    @empty
                    <div style="text-align:center;padding:20px 0;color:#94a3b8;font-size:12px;">{{ __('dashboard.no_tasks') }}</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 최근 커뮤니티
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="community" gs-x="8" gs-y="14" gs-w="4" gs-h="7" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#ecfdf5;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#10b981" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.recent_community') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <a href="{{ route('community.index') }}" style="font-size:12px;color:var(--t500);text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @forelse($recentCommunityPosts as $post)
                    <a href="{{ route('community.show', $post) }}" style="display:flex;align-items:flex-start;gap:10px;padding:9px 0;border-bottom:1px solid #f8f7ff;text-decoration:none;" class="db-row-hover">
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:center;gap:5px;margin-bottom:3px;">
                                <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;color:#fff;background:{{ $post->category_color }};flex-shrink:0;">{{ $post->category_label }}</span>
                                @if($post->pinned)<span style="font-size:10px;color:#ef4444;font-weight:600;flex-shrink:0;">📌</span>@endif
                            </div>
                            <div style="font-size:12px;font-weight:600;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $post->title }}</div>
                            <div style="font-size:10px;color:#94a3b8;margin-top:2px;">{{ $post->user->name }} · {{ $post->created_at->diffForHumans() }}</div>
                        </div>
                        <div style="display:flex;flex-direction:column;align-items:flex-end;gap:3px;flex-shrink:0;">
                            <div style="display:flex;align-items:center;gap:3px;font-size:10px;color:#94a3b8;">
                                <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                {{ $post->all_comments_count }}
                            </div>
                            @if($post->votes > 0)
                            <div style="display:flex;align-items:center;gap:2px;font-size:10px;color:#10b981;font-weight:600;">
                                <svg width="10" height="10" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"/></svg>
                                {{ $post->votes }}
                            </div>
                            @endif
                        </div>
                    </a>
                    @empty
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:12px;">
                        <div style="font-size:24px;margin-bottom:6px;">💬</div>
                        {{ __('dashboard.no_posts') }}<br>
                        <a href="{{ route('community.index') }}" style="color:var(--t500);font-size:12px;font-weight:600;text-decoration:none;margin-top:4px;display:inline-block;">{{ __('dashboard.write_first_post') }}</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         WIDGET: 나의 Action 아이템
    ═══════════════════════════════════════════ --}}
    <div class="grid-stack-item" gs-id="action-items" gs-x="8" gs-y="21" gs-w="4" gs-h="9" gs-min-w="2" gs-min-h="3">
        <div class="grid-stack-item-content">
            <div class="gs-card">
                <div class="gs-drag-handle">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <div style="width:28px;height:28px;background:#fff7ed;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg width="14" height="14" fill="none" stroke="#f97316" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <span style="font-size:13px;font-weight:700;color:#1e1b2e;">{{ __('dashboard.my_action_items') }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:11px;color:#94a3b8;">{{ $pendingActions->count() }}{{ __('dashboard.pending_count') }}</span>
                        <a href="{{ route('action-items.index') }}" style="font-size:12px;color:var(--t500);text-decoration:none;font-weight:600;">{{ __('dashboard.view_all') }}</a>
                        <svg class="gs-grip" width="14" height="14" fill="#c4b5fd" viewBox="0 0 14 14">
                            <circle cx="3" cy="3" r="1.5"/><circle cx="11" cy="3" r="1.5"/>
                            <circle cx="3" cy="7" r="1.5"/><circle cx="11" cy="7" r="1.5"/>
                            <circle cx="3" cy="11" r="1.5"/><circle cx="11" cy="11" r="1.5"/>
                        </svg>
                    </div>
                </div>
                <div class="gs-card-body">
                    @php $actionsByProject = $pendingActions->groupBy(fn($a) => $a->project_id ?? 'none'); @endphp

                    @if($pendingActions->isEmpty())
                    <div style="text-align:center;padding:24px 0;color:#94a3b8;font-size:12px;">
                        <div style="font-size:24px;margin-bottom:6px;">✅</div>
                        {{ __('dashboard.no_pending') }}
                    </div>
                    @else
                    @foreach($actionsByProject as $projectId => $actions)
                    @php $proj = $actions->first()->project; @endphp
                    <div style="margin-bottom:12px;">
                        <div style="display:flex;align-items:center;gap:6px;padding:5px 8px;background:#f8f7ff;border-radius:7px;margin-bottom:6px;">
                            <div style="width:6px;height:6px;border-radius:50%;background:{{ $proj ? 'var(--t500)' : '#94a3b8' }};flex-shrink:0;"></div>
                            <span style="font-size:11px;font-weight:700;color:{{ $proj ? 'var(--t600)' : '#94a3b8' }};">{{ $proj ? $proj->name : __('dashboard.no_project') }}</span>
                            <span style="font-size:10px;color:#94a3b8;margin-left:auto;">{{ $actions->count() }}{{ __('dashboard.count_items') }}</span>
                        </div>
                        @foreach($actions as $action)
                        <div style="display:flex;align-items:center;gap:8px;padding:7px 8px;border-radius:7px;margin-bottom:2px;" class="action-row-hover{{ $action->due_date && $action->due_date->isToday() ? ' db-today' : '' }}">
                            <form action="{{ route('action-items.toggle', $action) }}" method="POST" style="flex-shrink:0;">
                                @csrf @method('PATCH')
                                <button type="submit"
                                        style="width:15px;height:15px;border-radius:4px;border:1.5px solid #d1d5db;background:#fff;cursor:pointer;display:block;padding:0;flex-shrink:0;"
                                        title="{{ __('dashboard.mark_done') }}"></button>
                            </form>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;color:#1e1b2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $action->title }}</div>
                            </div>
                            @if($action->due_date)
                            <span style="font-size:10px;font-weight:600;flex-shrink:0;{{ $action->isOverdue() ? 'color:#ef4444;' : ($action->isDueSoon() ? 'color:#f59e0b;' : 'color:#94a3b8;') }}">
                                {{ $action->due_date->format('m/d') }}
                                @if($action->isOverdue()) {{ __('dashboard.overdue') }}@elseif($action->isDueSoon()) {{ __('dashboard.due_soon') }}@endif
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

</div>{{-- /grid-stack --}}
</div>

{{-- ─── 달력 이벤트 데이터 ─── --}}
@php
$calEventsJson = collect();
foreach ($calendarSchedules as $s) {
    $calEventsJson->push([
        'type'        => 'schedule',
        'day'         => (int)$s->start_date->format('j'),
        'title'       => $s->title,
        'sub'         => $s->start_date->format('H:i') . (optional($s->project)->name ? ' · '.$s->project->name : ''),
        'dot'         => match($s->priority) { 'high'=>'#ef4444','medium'=>'#f59e0b', default=>'#7c3aed' },
        'badge'       => $s->status_label,
        'badge_style' => $s->status==='pending' ? 'background:#fef9c3;color:#ca8a04' : 'background:#dbeafe;color:#2563eb',
    ]);
}
foreach ($calendarActionItems as $a) {
    $calEventsJson->push([
        'type'        => 'action',
        'day'         => (int)$a->due_date->format('j'),
        'title'       => $a->title,
        'sub'         => __('dashboard.deadline').' '.$a->due_date->format(__('dashboard.date_format_day')) . (optional($a->project)->name ? ' · '.$a->project->name : ''),
        'dot'         => $a->isOverdue() ? '#ef4444' : '#f97316',
        'badge'       => $a->isOverdue() ? __('dashboard.delayed') : 'Action',
        'badge_style' => $a->isOverdue() ? 'background:#fee2e2;color:#dc2626' : 'background:#fff7ed;color:#f97316',
    ]);
}
foreach ($calendarMeetings as $m) {
    $calEventsJson->push([
        'type'        => 'meeting',
        'day'         => (int)$m->meeting_date->format('j'),
        'title'       => $m->title,
        'sub'         => $m->meeting_date->format('H:i') . (optional($m->project)->name ? ' · '.$m->project->name : '') . ($m->location ? ' · '.$m->location : ''),
        'dot'         => '#7c3aed',
        'badge'       => '회의',
        'badge_style' => 'background:#ede9fe;color:#7c3aed',
    ]);
}
foreach ($calendarDiscussions as $d) {
    $calEventsJson->push([
        'type'        => 'discussion',
        'day'         => (int)$d->discussion_date->format('j'),
        'title'       => $d->title,
        'sub'         => optional($d->project)->name ?: '',
        'dot'         => '#0ea5e9',
        'badge'       => '논의',
        'badge_style' => 'background:#e0f2fe;color:#0284c7',
    ]);
}
@endphp

<script src="https://cdn.jsdelivr.net/npm/gridstack@10.3.1/dist/gridstack-all.js"></script>
<script>
// ── 달력 ──────────────────────────────────────────
const CAL_EVENTS = @json($calEventsJson->values());
const STR = {
    day_events:     '{{ __("dashboard.day_events") }}',
    count_unit:     '{{ __("dashboard.count_unit") }}',
    schedule_label: '{{ __("dashboard.schedule_label") }}',
    action_label:   '{{ __("dashboard.action_label") }}',
};

async function showDayEvents(day) {
    const detail = document.getElementById('cal-day-detail');
    const title  = document.getElementById('cal-day-title');
    const list   = document.getElementById('cal-day-events');
    const events = CAL_EVENTS.filter(e => e.day === day);
    if (!events.length) { detail.style.display = 'none'; return; }

    const scheds      = events.filter(e => e.type === 'schedule');
    const actions     = events.filter(e => e.type === 'action');
    const meetings    = events.filter(e => e.type === 'meeting');
    const discussions = events.filter(e => e.type === 'discussion');
    title.textContent = day + STR.day_events + ' (' + events.length + STR.count_unit + ')';

    const row = e => `
        <div style="display:flex;align-items:center;gap:8px;padding:5px 0;border-bottom:1px solid #e8e3ff;">
            <div style="width:6px;height:6px;border-radius:50%;background:${e.dot};flex-shrink:0;"></div>
            <div style="flex:1;min-width:0;">
                <div style="font-size:12px;font-weight:600;color:#1e1b2e;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${e.title}</div>
                <div style="font-size:10px;color:#94a3b8;">${e.sub}</div>
            </div>
            <span style="font-size:10px;padding:1px 7px;border-radius:20px;font-weight:600;${e.badge_style};flex-shrink:0;">${e.badge}</span>
        </div>`;

    let html = '';
    if (scheds.length)      html += `<div style="font-size:10px;font-weight:700;color:#7c3aed;margin:4px 0 2px;">${STR.schedule_label}</div>` + scheds.map(row).join('');
    if (meetings.length)    html += `<div style="font-size:10px;font-weight:700;color:#7c3aed;margin:8px 0 2px;">회의</div>` + meetings.map(row).join('');
    if (discussions.length) html += `<div style="font-size:10px;font-weight:700;color:#0ea5e9;margin:8px 0 2px;">논의</div>` + discussions.map(row).join('');
    if (actions.length)     html += `<div style="font-size:10px;font-weight:700;color:#f97316;margin:8px 0 2px;">${STR.action_label}</div>` + actions.map(row).join('');
    list.innerHTML = html;
    detail.style.display = 'block';
}

document.addEventListener('DOMContentLoaded', () => {
    const today = {{ now()->day }};
    if (CAL_EVENTS.some(e => e.day === today)) showDayEvents(today);
});

// ── GridStack ────────────────────────────────────
const LAYOUT_KEY = 'sw_dash_v2_{{ auth()->id() }}';

const DEFAULT_LAYOUT = [
    { id: 'recent-projects', x: 0,  y: 0,  w: 4,  h: 7  },
    { id: 'calendar',        x: 0,  y: 7,  w: 4,  h: 10 },
    { id: 'recent-files',    x: 4,  y: 0,  w: 4,  h: 5  },
    { id: 'recent-minutes',  x: 4,  y: 5,  w: 4,  h: 7  },
    { id: 'ai-chat',         x: 8,  y: 0,  w: 4,  h: 8  },
    { id: 'my-tasks',        x: 8,  y: 8,  w: 4,  h: 6  },
    { id: 'community',       x: 8,  y: 14, w: 4,  h: 7  },
    { id: 'action-items',    x: 8,  y: 21, w: 4,  h: 9  },
];

const grid = GridStack.init({
    cellHeight: 60,
    column: 12,
    animate: true,
    margin: 6,
    handle: '.gs-drag-handle',
    resizable: { handles: 'se' },
    minRow: 1,
});

// 저장된 레이아웃 복원
(async function restoreLayout() {
    try {
        const saved = localStorage.getItem(LAYOUT_KEY);
        if (saved) grid.load(JSON.parse(saved));
    } catch (e) { /* ignore */ }
})();

// 저장 표시
async function flashSaved() {
    const el = document.getElementById('gs-saved');
    el.style.opacity = '1';
    clearTimeout(window._gsSaveFlash);
    window._gsSaveFlash = setTimeout(() => el.style.opacity = '0', 2000);
}

// 레이아웃 자동 저장 (drag/resize 완료 후 400ms 디바운스)
let _saveTimer;
async function saveLayout() {
    localStorage.setItem(LAYOUT_KEY, JSON.stringify(grid.save(false)));
    flashSaved();
}

grid.on('dragstop resizestop', () => {
    clearTimeout(_saveTimer);
    _saveTimer = setTimeout(saveLayout, 400);
});

// 레이아웃 초기화
async function resetDashboardLayout() {
    if (!await __confirm(@json(__('dashboard.confirm_reset_layout')))) return;
    localStorage.removeItem(LAYOUT_KEY);
    grid.load(DEFAULT_LAYOUT);
    flashSaved();
}
</script>

@include('partials.file-preview-modal')

{{-- 회의록 상세 팝업 --}}
<div id="db-minute-popup-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(15,10,40,.6);z-index:2000;align-items:center;justify-content:center;padding:20px;"
     onclick="if(event.target===this)dbCloseMinutePopup()">
    <div style="background:#fff;border-radius:16px;width:min(1060px,100%);height:calc(100vh - 40px);display:flex;flex-direction:column;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden;">
        <iframe id="db-minute-popup-frame" src="" style="width:100%;flex:1;border:none;border-radius:16px;" allowfullscreen></iframe>
    </div>
</div>
<script>
const DB_MINUTE_BASE = '{{ url('meeting-minutes') }}';
function dbOpenMinutePopup(id) {
    document.getElementById('db-minute-popup-frame').src = DB_MINUTE_BASE + '/' + id + '/popup';
    document.getElementById('db-minute-popup-overlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function dbCloseMinutePopup() {
    document.getElementById('db-minute-popup-overlay').style.display = 'none';
    document.getElementById('db-minute-popup-frame').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') dbCloseMinutePopup();
});
</script>

{{-- ─── 대시보드 온보딩 투어 (처음 접속 시 자동 1회) ─── --}}
<script>
(function () {
    const TOUR_KEY = 'sw_dashboard_tour_v1_{{ auth()->id() }}';
    if (localStorage.getItem(TOUR_KEY)) return;   // 이미 본 사용자는 건너뜀

    const STEPS = [
        { sel: null, title: '대시보드에 오신 것을 환영합니다 👋',
          text: '주요 영역을 빠르게 둘러볼게요. 각 위젯은 제목 부분을 잡고 드래그하면 자유롭게 배치를 바꿀 수 있습니다.' },
        { sel: '.gs-kpi-grid', title: '한눈에 보는 핵심 지표',
          text: '프로젝트·회의록·내 Tasks·Action 아이템 등 핵심 수치를 화면 상단에서 바로 확인할 수 있습니다.' },
        { sel: '[gs-id="recent-projects"]', title: '최근 프로젝트',
          text: '최근 참여한 프로젝트와 진행 상태를 보여줍니다. 프로젝트명을 클릭해 바로 이동하세요.' },
        { sel: '[gs-id="calendar"]', title: '달력 · 일정',
          text: '일정·회의·논의·Action 아이템을 달력에서 확인합니다. 오늘 날짜와 다가오는 일정이 강조 표시됩니다.' },
        { sel: '[gs-id="recent-files"]', title: '최근 파일',
          text: '최근 업로드되거나 수정된 파일에 빠르게 접근할 수 있습니다.' },
        { sel: '[gs-id="recent-minutes"]', title: '최근 회의록',
          text: '최근 회의록 목록입니다. 항목을 클릭하면 팝업으로 바로 열람할 수 있습니다.' },
        { sel: '[gs-id="ai-chat"]', title: '웍스 AI 대화',
          text: '웍스 어시스턴트와의 최근 대화를 이어가거나 새 대화를 시작할 수 있습니다.' },
        { sel: '[gs-id="my-tasks"]', title: '내 Tasks',
          text: '나에게 할당된 할 일과 진행 상태를 한곳에서 관리합니다.' },
        { sel: '[gs-id="community"]', title: '커뮤니티',
          text: '최근 커뮤니티 글과 Q&A를 확인하고 참여할 수 있습니다.' },
        { sel: '[gs-id="action-items"]', title: '나의 Action 아이템',
          text: '프로젝트별로 처리할 Action 아이템입니다. 체크 버튼으로 완료 처리하세요. 이것으로 둘러보기를 마칩니다 🎉' },
    ];

    let idx = 0, block, hole, pop, elNo, elTitle, elText, btnPrev, btnNext, btnSkip;

    function build() {
        block = document.createElement('div'); block.id = 'tour-block';
        hole  = document.createElement('div'); hole.id  = 'tour-hole';
        pop   = document.createElement('div'); pop.id   = 'tour-pop';
        pop.innerHTML =
            '<div class="tour-step-no"></div>' +
            '<div class="tour-title"></div>' +
            '<div class="tour-text"></div>' +
            '<div class="tour-actions">' +
              '<button type="button" class="tour-btn tour-btn-skip">건너뛰기</button>' +
              '<div style="flex:1"></div>' +
              '<button type="button" class="tour-btn tour-btn-prev">이전</button>' +
              '<button type="button" class="tour-btn tour-btn-next">다음</button>' +
            '</div>';
        document.body.appendChild(block);
        document.body.appendChild(hole);
        document.body.appendChild(pop);
        elNo    = pop.querySelector('.tour-step-no');
        elTitle = pop.querySelector('.tour-title');
        elText  = pop.querySelector('.tour-text');
        btnPrev = pop.querySelector('.tour-btn-prev');
        btnNext = pop.querySelector('.tour-btn-next');
        btnSkip = pop.querySelector('.tour-btn-skip');
        btnPrev.addEventListener('click', () => { if (idx > 0) { idx--; render(); } });
        btnNext.addEventListener('click', () => { idx < STEPS.length - 1 ? (idx++, render()) : end(); });
        btnSkip.addEventListener('click', end);
        window.addEventListener('keydown', onKey);
        window.addEventListener('resize', render);
    }

    function onKey(e) { if (e.key === 'Escape') end(); }

    function end() {
        try { localStorage.setItem(TOUR_KEY, '1'); } catch (e) {}
        window.removeEventListener('keydown', onKey);
        window.removeEventListener('resize', render);
        [block, hole, pop].forEach(el => el && el.remove());
    }

    function placePop(r) {
        pop.style.transform = 'none';
        const pw = pop.offsetWidth, ph = pop.offsetHeight, gap = 14;
        let top = r ? r.bottom + gap : 0;
        if (r && top + ph > window.innerHeight - 12) top = r.top - gap - ph;   // 위쪽으로
        if (!r || top < 12) {                                                  // 가운데 정렬
            pop.style.top = '50%'; pop.style.left = '50%';
            pop.style.transform = 'translate(-50%,-50%)';
            return;
        }
        let left = r.left + r.width / 2 - pw / 2;
        left = Math.max(12, Math.min(window.innerWidth - pw - 12, left));
        pop.style.top = top + 'px';
        pop.style.left = left + 'px';
    }

    function render() {
        const step = STEPS[idx];
        elNo.textContent = (idx + 1) + ' / ' + STEPS.length;
        elTitle.textContent = step.title;
        elText.textContent = step.text;
        btnPrev.style.display = idx === 0 ? 'none' : '';
        btnNext.textContent = idx === STEPS.length - 1 ? '완료' : '다음';

        const el = step.sel ? document.querySelector(step.sel) : null;
        if (!el) {
            hole.style.display = 'none';
            block.style.background = 'rgba(15,10,40,.66)';
            placePop(null);
            return;
        }
        block.style.background = 'transparent';
        el.scrollIntoView({ block: 'center', inline: 'nearest' });
        requestAnimationFrame(() => {
            const r = el.getBoundingClientRect(), pad = 6;
            hole.style.display = 'block';
            hole.style.top    = (r.top - pad) + 'px';
            hole.style.left   = (r.left - pad) + 'px';
            hole.style.width  = (r.width + pad * 2) + 'px';
            hole.style.height = (r.height + pad * 2) + 'px';
            placePop(r);
        });
    }

    function start() { build(); idx = 0; render(); }

    // GridStack 레이아웃이 자리잡은 뒤 시작
    if (document.readyState === 'complete') setTimeout(start, 700);
    else window.addEventListener('load', () => setTimeout(start, 700));
})();
</script>

@endsection
