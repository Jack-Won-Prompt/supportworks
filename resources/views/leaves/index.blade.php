@extends('layouts.app')

@section('title', $project->name . ' · ' . __('work.leave_title'))

@section('breadcrumb')
<a href="{{ route('projects.index') }}" class="hover:text-indigo-500 transition-colors">{{ __('common.list') }}</a>
<span>›</span>
<a href="{{ route('projects.show', $project) }}" class="hover:text-indigo-500 transition-colors">{{ $project->name }}</a>
<span>›</span>
<span style="color:#374151;font-weight:500;">{{ __('work.leave_title') }}</span>
@endsection

@section('header-actions')@endsection

@section('page-actions')
<button onclick="openLeaveModal()"
    style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:var(--t600);color:#fff;font-size:13px;font-weight:500;border-radius:8px;border:none;cursor:pointer;transition:opacity .15s;"
    onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    {{ __('work.leave_apply_btn') }}
</button>
@endsection

@section('content')
@include('partials.project-nav', ['project'=>$project, 'active'=>'leaves'])
<div class="space-y-5">

{{-- 월 네비게이션 --}}
@php
    $prevMonth = \Carbon\Carbon::create($year, $month, 1)->subMonth();
    $nextMonth = \Carbon\Carbon::create($year, $month, 1)->addMonth();
    $monthLabel = \Carbon\Carbon::create($year, $month, 1)->locale(app()->getLocale())->isoFormat(__('work.leave_month_format'));
    $firstDay = \Carbon\Carbon::create($year, $month, 1);
    $daysInMonth = $firstDay->daysInMonth;
    $startDow = $firstDay->dayOfWeek; // 0=Sun
@endphp

{{-- 연간/월간 사용 통계 --}}
@php
$fmtDays   = fn(float $v) => $v == (int)$v ? (int)$v : number_format($v, 1);
$monthName = \Carbon\Carbon::create($year, $month, 1)->locale(app()->getLocale())->isoFormat(__('work.leave_month_name_format'));
@endphp
<div style="background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 1px 6px rgba(0,0,0,.04);overflow:hidden;">
    <div style="display:grid;grid-template-columns:1fr 1px 1fr;">

        {{-- 연간 --}}
        <div style="padding:10px 18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.07em;">{{ __('work.leave_my_annual_stat', ['year' => $year]) }}</span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="font-size:12px;font-weight:700;color:#374151;">{{ $fmtDays($yearUsed['total']) }}{{ __('work.leave_unit_days') }}</span>
                    @if($yearUpcoming['total'] > 0)
                    <span style="font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;background:#ede9fe;color:#6d28d9;">+{{ $fmtDays($yearUpcoming['total']) }}{{ __('work.leave_status_upcoming') }}</span>
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:stretch;gap:0;border:1px solid #f3f4f6;border-radius:8px;overflow:hidden;">
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_type_annual') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#6366f1;line-height:1;">{{ $fmtDays($yearUsed['annual']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($yearUpcoming['annual'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($yearUpcoming['annual']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_half') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#0ea5e9;line-height:1;">{{ $yearUsed['half'] }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_times') }}</div>
                    @if($yearUpcoming['half'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $yearUpcoming['half'] }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_sick') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#f59e0b;line-height:1;">{{ $fmtDays($yearUsed['sick']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($yearUpcoming['sick'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($yearUpcoming['sick']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_other') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#64748b;line-height:1;">{{ $fmtDays($yearUsed['other']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($yearUpcoming['other'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($yearUpcoming['other']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- 구분선 --}}
        <div style="background:#f3f4f6;"></div>

        {{-- 이달 --}}
        <div style="padding:10px 18px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
                <span style="font-size:10.5px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.07em;">{{ __('work.leave_month_stat', ['month' => $monthName]) }}</span>
                <span style="display:flex;align-items:center;gap:5px;">
                    <span style="font-size:12px;font-weight:700;color:#374151;">{{ $fmtDays($monthUsed['total']) }}{{ __('work.leave_unit_days') }}</span>
                    @if($monthUpcoming['total'] > 0)
                    <span style="font-size:10px;font-weight:600;padding:1px 6px;border-radius:20px;background:#ede9fe;color:#6d28d9;">+{{ $fmtDays($monthUpcoming['total']) }}{{ __('work.leave_status_upcoming') }}</span>
                    @endif
                </span>
            </div>
            <div style="display:flex;align-items:stretch;gap:0;border:1px solid #f3f4f6;border-radius:8px;overflow:hidden;">
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_type_annual') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#6366f1;line-height:1;">{{ $fmtDays($monthUsed['annual']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($monthUpcoming['annual'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($monthUpcoming['annual']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_half') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#0ea5e9;line-height:1;">{{ $monthUsed['half'] }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_times') }}</div>
                    @if($monthUpcoming['half'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $monthUpcoming['half'] }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;border-right:1px solid #f3f4f6;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_sick') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#f59e0b;line-height:1;">{{ $fmtDays($monthUsed['sick']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($monthUpcoming['sick'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($monthUpcoming['sick']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
                <div style="flex:1;padding:6px 0;text-align:center;">
                    <div style="font-size:9.5px;color:#9ca3af;margin-bottom:2px;">{{ __('work.leave_legend_other') }}</div>
                    <div style="font-size:15px;font-weight:700;color:#64748b;line-height:1;">{{ $fmtDays($monthUsed['other']) }}</div>
                    <div style="font-size:9px;color:#cbd5e1;margin-top:1px;">{{ __('work.leave_unit_days') }}</div>
                    @if($monthUpcoming['other'] > 0)
                    <div style="font-size:8.5px;color:#7c3aed;margin-top:2px;">+{{ $fmtDays($monthUpcoming['other']) }}{{ __('work.leave_status_upcoming') }}</div>
                    @endif
                </div>
            </div>
        </div>

    </div>
</div>

{{-- 팀 휴가 현황 (매니저용) --}}
@if($isManager && $teamStats->isNotEmpty())
<div style="background:#fff;border-radius:12px;border:1px solid #f3f4f6;box-shadow:0 1px 6px rgba(0,0,0,.04);overflow:hidden;">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 18px;border-bottom:1px solid #f3f4f6;cursor:pointer;user-select:none;" onclick="toggleTeamStats()">
        <div style="display:flex;align-items:center;gap:7px;">
            <svg width="13" height="13" fill="none" stroke="#7c3aed" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
            <span style="font-size:10.5px;font-weight:700;color:#6d28d9;text-transform:uppercase;letter-spacing:.07em;">{{ __('work.leave_team_stats_heading', ['year' => $year]) }}</span>
        </div>
        <svg id="team-stats-chevron" width="14" height="14" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="transition:transform .2s;transform:rotate(-90deg);"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </div>
    <div id="team-stats-body" style="display:none;">
        <table style="width:100%;border-collapse:collapse;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #f1f5f9;">
                    <th style="text-align:left;padding:7px 18px;font-size:10.5px;font-weight:700;color:#64748b;letter-spacing:.04em;">{{ __('work.leave_col_member') }}</th>
                    <th style="text-align:center;padding:7px 12px;font-size:10.5px;font-weight:700;color:#6366f1;letter-spacing:.04em;">{{ __('work.leave_legend_annual') }}</th>
                    <th style="text-align:center;padding:7px 12px;font-size:10.5px;font-weight:700;color:#0ea5e9;letter-spacing:.04em;">{{ __('work.leave_legend_half') }}</th>
                    <th style="text-align:center;padding:7px 12px;font-size:10.5px;font-weight:700;color:#f59e0b;letter-spacing:.04em;">{{ __('work.leave_legend_sick') }}</th>
                    <th style="text-align:center;padding:7px 12px;font-size:10.5px;font-weight:700;color:#64748b;letter-spacing:.04em;">{{ __('work.leave_legend_other') }}</th>
                    <th style="text-align:center;padding:7px 18px;font-size:10.5px;font-weight:700;color:#374151;letter-spacing:.04em;">{{ __('work.leave_team_col_total') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($teamStats as $stat)
                <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                    <td style="padding:7px 18px;">
                        <div style="display:flex;align-items:center;gap:7px;">
                            <div style="width:24px;height:24px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">{{ mb_substr($stat['user_name'], 0, 1) }}</div>
                            <span style="font-size:12.5px;font-weight:500;color:#1e293b;">{{ $stat['user_name'] }}</span>
                            @if($stat['user_id'] === $myId)<span style="font-size:10px;padding:1px 5px;border-radius:4px;background:#ede9fe;color:#7c3aed;font-weight:600;margin-left:2px;">{{ __('work.leave_self_badge') }}</span>@endif
                        </div>
                    </td>
                    <td style="text-align:center;padding:7px 12px;">
                        <span style="font-size:13px;font-weight:700;color:#6366f1;">{{ $fmtDays($stat['used']['annual']) }}</span>
                        @if($stat['upcoming']['annual'] > 0)<span style="font-size:10px;color:#a78bfa;margin-left:2px;">+{{ $fmtDays($stat['upcoming']['annual']) }}</span>@endif
                        <div style="font-size:9px;color:#cbd5e1;">{{ __('work.leave_unit_days') }}</div>
                    </td>
                    <td style="text-align:center;padding:7px 12px;">
                        <span style="font-size:13px;font-weight:700;color:#0ea5e9;">{{ $stat['used']['half'] }}</span>
                        @if($stat['upcoming']['half'] > 0)<span style="font-size:10px;color:#7dd3fc;margin-left:2px;">+{{ $stat['upcoming']['half'] }}</span>@endif
                        <div style="font-size:9px;color:#cbd5e1;">{{ __('work.leave_unit_times') }}</div>
                    </td>
                    <td style="text-align:center;padding:7px 12px;">
                        <span style="font-size:13px;font-weight:700;color:#f59e0b;">{{ $fmtDays($stat['used']['sick']) }}</span>
                        @if($stat['upcoming']['sick'] > 0)<span style="font-size:10px;color:#fcd34d;margin-left:2px;">+{{ $fmtDays($stat['upcoming']['sick']) }}</span>@endif
                        <div style="font-size:9px;color:#cbd5e1;">{{ __('work.leave_unit_days') }}</div>
                    </td>
                    <td style="text-align:center;padding:7px 12px;">
                        <span style="font-size:13px;font-weight:700;color:#64748b;">{{ $fmtDays($stat['used']['other']) }}</span>
                        @if($stat['upcoming']['other'] > 0)<span style="font-size:10px;color:#94a3b8;margin-left:2px;">+{{ $fmtDays($stat['upcoming']['other']) }}</span>@endif
                        <div style="font-size:9px;color:#cbd5e1;">{{ __('work.leave_unit_days') }}</div>
                    </td>
                    <td style="text-align:center;padding:7px 18px;">
                        <span style="font-size:13px;font-weight:800;color:#374151;">{{ $fmtDays($stat['used']['total']) }}</span>
                        @if($stat['upcoming']['total'] > 0)<span style="font-size:10.5px;color:#a78bfa;margin-left:2px;">+{{ $fmtDays($stat['upcoming']['total']) }}</span>@endif
                        <div style="font-size:9px;color:#cbd5e1;">{{ __('work.leave_unit_days') }}</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- 2열 레이아웃: 왼쪽 달력 / 오른쪽 목록 --}}
<div style="display:flex;gap:16px;align-items:flex-start;">

{{-- 왼쪽: 달력 --}}
<div style="flex:0 0 58%;min-width:0;">
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('projects.leaves.index', [$project, 'year' => $prevMonth->year, 'month' => $prevMonth->month]) }}"
               class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500 transition-colors">‹</a>
            <h2 class="text-base font-bold text-gray-900">{{ $monthLabel }}</h2>
            <a href="{{ route('projects.leaves.index', [$project, 'year' => $nextMonth->year, 'month' => $nextMonth->month]) }}"
               class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 text-gray-500 transition-colors">›</a>
            <a href="{{ route('projects.leaves.index', $project) }}"
               class="text-xs text-indigo-500 hover:text-indigo-700 font-medium ml-1">{{ __('work.leave_today') }}</a>
        </div>
        {{-- 범례 --}}
        <div class="flex items-center gap-3 text-xs text-gray-500">
            <span class="flex items-center gap-1"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#eef2ff;border:1px solid #6366f1;"></span>{{ __('work.leave_legend_annual') }}</span>
            <span class="flex items-center gap-1"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#e0f2fe;border:1px solid #0ea5e9;"></span>{{ __('work.leave_legend_half') }}</span>
            <span class="flex items-center gap-1"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#fef3c7;border:1px solid #f59e0b;"></span>{{ __('work.leave_legend_sick') }}</span>
            <span class="flex items-center gap-1"><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#f1f5f9;border:1px solid #64748b;"></span>{{ __('work.leave_legend_other') }}</span>
        </div>
    </div>

    {{-- 달력 헤더 --}}
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px;">
        @foreach([
            __('work.leave_dow_sun'),
            __('work.leave_dow_mon'),
            __('work.leave_dow_tue'),
            __('work.leave_dow_wed'),
            __('work.leave_dow_thu'),
            __('work.leave_dow_fri'),
            __('work.leave_dow_sat'),
        ] as $di => $dow)
        <div style="text-align:center;font-size:11px;font-weight:700;padding:6px 0;color:{{ $di===0 ? '#ef4444' : ($di===6 ? '#3b82f6' : '#64748b') }};">{{ $dow }}</div>
        @endforeach
    </div>

    {{-- 달력 셀 --}}
    @php
        $today        = now()->format('Y-m-d');
        $krHolidays   = \App\Helpers\KoreanHolidays::getHolidays($year);
    @endphp
    <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:2px;">
        {{-- 빈 셀 --}}
        @for($i = 0; $i < $startDow; $i++)
        <div style="min-height:72px;padding:4px;border-radius:6px;background:#fafafa;"></div>
        @endfor

        @for($d = 1; $d <= $daysInMonth; $d++)
        @php
            $dateStr    = sprintf('%04d-%02d-%02d', $year, $month, $d);
            $isToday    = $dateStr === $today;
            $isFuture   = $dateStr > $today;
            $dow        = \Carbon\Carbon::parse($dateStr)->dayOfWeek;
            $dayLeaves  = $calendarLeaves[$dateStr] ?? [];
            $holidayName = $krHolidays[$dateStr] ?? null;
            $isHoliday  = $holidayName !== null;
            $isRed      = $isHoliday || $dow === 0;
        @endphp
        <div style="min-height:72px;padding:5px;border-radius:6px;border:1px solid {{ $isToday ? '#6366f1' : ($isHoliday && !$isToday ? '#fee2e2' : '#f1f5f9') }};background:{{ $isToday ? '#faf5ff' : ($isHoliday ? '#fff9f9' : ($isFuture ? '#fdfcff' : '#fff')) }};transition:background .12s;"
             onmouseover="this.style.background='{{ $isToday ? '#f3e8ff' : '#f8fafc' }}'" onmouseout="this.style.background='{{ $isToday ? '#faf5ff' : ($isHoliday ? '#fff9f9' : ($isFuture ? '#fdfcff' : '#fff')) }}'">
            <div style="font-size:11px;font-weight:{{ $isToday ? '800' : '500' }};color:{{ $isToday ? '#6366f1' : ($isRed ? '#ef4444' : ($dow===6 ? '#3b82f6' : '#475569')) }};margin-bottom:1px;">{{ $d }}</div>
            @if($holidayName)
            <div style="font-size:9px;color:#f87171;margin-bottom:3px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;line-height:1.2;" title="{{ $holidayName }}">{{ $holidayName }}</div>
            @endif
            @foreach($dayLeaves as $lv)
            @php
                $lvUpcoming = $isFuture && $lv->status === 'approved';
            @endphp
            <div style="font-size:10px;padding:1px 5px;border-radius:4px;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
                    border:1px solid {{ $lvUpcoming ? '#a78bfa' : $lv->leave_type_color }};
                    background:{{ $lvUpcoming ? '#f5f3ff' : $lv->leave_type_bg }};
                    color:{{ $lvUpcoming ? '#7c3aed' : $lv->leave_type_color }};
                    opacity:{{ $lv->status === 'rejected' ? '.4' : '1' }};
                    {{ $lv->status === 'pending' ? 'border-style:dashed;' : ($lvUpcoming ? 'border-style:dotted;' : '') }}"
                 title="{{ $lv->user->name }} · {{ $lv->leave_type_label }} · {{ $lvUpcoming ? __('work.leave_status_upcoming') : $lv->status_label }}">
                {{ mb_substr($lv->user->name, 0, 3) }}
            </div>
            @endforeach
        </div>
        @endfor

        {{-- 뒷 빈 셀 --}}
        @php $remain = (7 - ($startDow + $daysInMonth) % 7) % 7; @endphp
        @for($i = 0; $i < $remain; $i++)
        <div style="min-height:72px;padding:4px;border-radius:6px;background:#fafafa;"></div>
        @endfor
    </div>
</div>
</div>{{-- /왼쪽 달력 --}}

{{-- 오른쪽: 목록 --}}
<div style="flex:1;min-width:0;display:flex;flex-direction:column;gap:16px;max-height:calc(100vh - 220px);overflow-y:auto;">

{{-- 내 결재 대기 건 --}}
@if($pendingForMe->isNotEmpty())
<div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;overflow:hidden;">
    <div style="display:flex;align-items:center;gap:8px;padding:14px 20px 10px;border-bottom:1px solid #fde68a;background:#fef3c7;">
        <svg width="15" height="15" fill="none" stroke="#d97706" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19H19a2 2 0 001.75-2.96L13.75 4a2 2 0 00-3.5 0L3.25 16.04A2 2 0 005.07 19z"/></svg>
        <span style="font-size:13px;font-weight:700;color:#92400e;">
            @if($isManager)
            {{ __('work.leave_all_pending_heading', ['count' => $pendingForMe->count()]) }}
            @else
            {{ __('work.leave_pending_approvals', ['count' => $pendingForMe->count()]) }}
            @endif
        </span>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <tbody>
            @foreach($pendingForMe as $lv)
            @include('leaves._row', ['lv' => $lv, 'highlightApprover' => true])
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- 리스트 --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px 12px;flex-wrap:wrap;gap:8px;">
        <h3 style="font-size:14px;font-weight:700;color:#1e293b;">{{ __('work.leave_list_heading', ['month' => $monthLabel]) }}</h3>
        <div style="display:flex;align-items:center;gap:8px;">
            @if($isManager)
            <select id="leave-member-filter" onchange="filterLeaveByMember(this.value)"
                style="padding:4px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:12px;color:#374151;background:#fff;outline:none;">
                <option value="">{{ __('work.leave_filter_all_members') }}</option>
                @foreach($members as $m)
                <option value="{{ $m->user_id }}">{{ $m->user->name }}</option>
                @endforeach
            </select>
            @endif
            <span style="font-size:12px;color:#94a3b8;">{{ __('work.leave_count', ['count' => $leaves->count()]) }}</span>
        </div>
    </div>
    <table style="width:100%;border-collapse:collapse;">
        <thead>
            <tr style="background:#f8fafc;border-top:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;">
                <th style="text-align:left;padding:8px 20px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ __('work.leave_col_member') }}</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ __('work.leave_col_type') }}</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ __('work.leave_col_period') }}</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ __('work.leave_col_reason') }}</th>
                <th style="text-align:left;padding:8px 12px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.05em;">{{ __('work.leave_col_status') }}</th>
                <th style="padding:8px 20px;"></th>
            </tr>
        </thead>
        <tbody id="leave-list">
            @forelse($leaves as $lv)
            @include('leaves._row', ['lv' => $lv])
            @empty
            <tr id="leave-empty-row">
                <td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">{{ __('work.leave_empty_month') }}</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>{{-- /오른쪽 목록 --}}
</div>{{-- /2열 flex --}}

</div>{{-- /space-y-5 --}}

{{-- 휴무 신청/수정 모달 --}}
<div id="leave-modal" onclick="if(event.target===this)closeLeaveModal()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9000;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:16px;width:480px;max-width:95vw;box-shadow:0 20px 60px rgba(0,0,0,.2);">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 22px 14px;border-bottom:1px solid #f1f5f9;">
            <h3 id="lm-title" style="font-size:15px;font-weight:700;color:#1e293b;margin:0;">{{ __('work.leave_modal_heading') }}</h3>
            <button onclick="closeLeaveModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:20px;line-height:1;">×</button>
        </div>
        <div style="padding:20px 22px 24px;display:flex;flex-direction:column;gap:14px;">
            <div id="lm-error" style="display:none;padding:10px 14px;background:#fef2f2;border:1px solid #fca5a5;border-radius:8px;font-size:12px;color:#dc2626;"></div>

            {{-- 멤버 선택 (매니저만) --}}
            @if($isManager)
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_member_label') }} <span style="color:#ef4444;">*</span></label>
                <select id="lm-user" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                    @foreach($members as $m)
                    <option value="{{ $m->user_id }}">{{ $m->user->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <input type="hidden" id="lm-user" value="{{ auth()->id() }}">
            @endif

            {{-- 유형 --}}
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_type_label') }} <span style="color:#ef4444;">*</span></label>
                <select id="lm-type" onchange="handleTypeChange()" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                    <option value="annual">{{ __('work.leave_type_annual') }}</option>
                    <option value="half_day_am">{{ __('work.leave_type_half_am') }}</option>
                    <option value="half_day_pm">{{ __('work.leave_type_half_pm') }}</option>
                    <option value="sick">{{ __('work.leave_type_sick') }}</option>
                    <option value="other">{{ __('work.leave_legend_other') }}</option>
                </select>
            </div>

            {{-- 날짜 --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_start_label') }} <span style="color:#ef4444;">*</span></label>
                    <input id="lm-start" type="date" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;"
                           onchange="syncEndDate()">
                </div>
                <div id="lm-end-wrap">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_end_label') }} <span style="color:#ef4444;">*</span></label>
                    <input id="lm-end" type="date" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;">
                </div>
            </div>

            {{-- 사유 --}}
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_reason_label') }} <span style="color:#94a3b8;font-weight:400;">{{ __('work.leave_form_reason_optional') }}</span></label>
                <textarea id="lm-reason" rows="2" placeholder="{{ __('work.leave_form_reason_placeholder') }}"
                    style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:none;box-sizing:border-box;"></textarea>
            </div>

            {{-- 결재 대상자 --}}
            @if(!$isManager)
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">
                    {{ __('work.leave_form_approver_label') }}
                    <span style="color:#94a3b8;font-weight:400;">{{ __('work.leave_form_approver_hint') }}</span>
                </label>
                <select id="lm-approver" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                    <option value="">{{ __('work.leave_form_approver_none') }}</option>
                    @foreach($members->where('user_id', '!=', auth()->id()) as $m)
                    <option value="{{ $m->user_id }}">{{ $m->user->name }}</option>
                    @endforeach
                </select>
            </div>
            @else
            {{-- 매니저는 결재자 선택 옵션 + 승인 상태 --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_approver_label') }} <span style="color:#94a3b8;font-weight:400;">{{ __('work.leave_form_reason_optional') }}</span></label>
                    <select id="lm-approver" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                        <option value="">{{ __('work.leave_form_no_approver') }}</option>
                        @foreach($members as $m)
                        <option value="{{ $m->user_id }}">{{ $m->user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="lm-status-wrap">
                    <label style="display:block;font-size:12px;font-weight:600;color:#475569;margin-bottom:5px;">{{ __('work.leave_form_status_label') }}</label>
                    <select id="lm-status" style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;box-sizing:border-box;">
                        <option value="pending">{{ __('work.leave_status_pending') }}</option>
                        <option value="approved">{{ __('work.leave_status_approved') }}</option>
                        <option value="rejected">{{ __('work.leave_status_rejected') }}</option>
                    </select>
                </div>
            </div>
            @endif

            <div style="display:flex;justify-content:flex-end;gap:8px;padding-top:4px;border-top:1px solid #f1f5f9;">
                <button onclick="closeLeaveModal()" style="padding:8px 18px;font-size:13px;color:#64748b;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;cursor:pointer;">{{ __('common.cancel') }}</button>
                <button id="lm-submit" onclick="submitLeave()" style="padding:8px 22px;font-size:13px;font-weight:600;color:#fff;background:#6366f1;border:none;border-radius:8px;cursor:pointer;">{{ __('work.leave_submit_btn') }}</button>
            </div>
        </div>
    </div>
</div>

@php
$storeUrl    = route('projects.leaves.store', $project);
$isManagerJs = $isManager ? 'true' : 'false';
$myUserId    = auth()->id();
@endphp

<script>
const _LV_CSRF      = document.querySelector('meta[name="csrf-token"]').content;
const _LV_STORE_URL = '{{ $storeUrl }}';
const _LV_BASE_URL  = '{{ url("projects/" . $project->id . "/leaves") }}';
const _LV_IS_MGR    = {{ $isManagerJs }};
const _LV_MY_ID     = {{ $myUserId }};
let _lv_editId = null;

const _LV_STR = {
    modalHeading:      '{{ __("work.leave_modal_heading") }}',
    modalEditHeading:  '{{ __("work.leave_modal_edit_heading") }}',
    submitBtn:         '{{ __("work.leave_submit_btn") }}',
    saveBtn:           '{{ __("common.save") }}',
    processing:        '{{ __("work.leave_processing") }}',
    errorNoStart:      '{{ __("work.leave_error_no_start") }}',
    errorNoEnd:        '{{ __("work.leave_error_no_end") }}',
    errorEndBefore:    '{{ __("work.leave_error_end_before_start") }}',
    toastUpdated:      '{{ __("work.leave_toast_updated") }}',
    toastCreated:      '{{ __("work.leave_toast_created") }}',
    toastDeleted:      '{{ __("work.leave_toast_deleted") }}',
    toastApproved:     '{{ __("work.leave_toast_approved") }}',
    toastRejected:     '{{ __("work.leave_toast_rejected") }}',
    processingFailed:  '{{ __("work.leave_processing_failed") }}',
    deleteFailed:      '{{ __("work.leave_delete_failed") }}',
    confirmDelete:     '{{ __("work.leave_confirm_delete") }}',
    emptyMonth:        '{{ __("work.leave_empty_month") }}',
    approverLabel:     '{{ __("work.leave_approver_label") }}',
    statusApprove:     '{{ __("work.leave_status_approve") }}',
    statusReject:      '{{ __("work.leave_status_reject") }}',
    editBtn:           '{{ __("common.edit") }}',
    deleteBtn:         '{{ __("common.delete") }}',
    halfDayUnit:       '{{ __("work.leave_half_day_unit") }}',
    unitDays:          '{{ __("work.leave_unit_days") }}',
    upcoming:          '{{ __("work.leave_status_upcoming") }}',
};

async function openLeaveModal(data) {
    _lv_editId = data?.id || null;
    document.getElementById('lm-title').textContent = _lv_editId ? _LV_STR.modalEditHeading : _LV_STR.modalHeading;
    document.getElementById('lm-submit').textContent = _lv_editId ? _LV_STR.saveBtn : _LV_STR.submitBtn;
    document.getElementById('lm-error').style.display = 'none';

    const userEl = document.getElementById('lm-user');
    if (userEl?.tagName === 'SELECT' && data?.user_id) userEl.value = data.user_id;

    document.getElementById('lm-type').value    = data?.leave_type  || 'annual';
    document.getElementById('lm-start').value   = data?.start_date  || '';
    document.getElementById('lm-end').value     = data?.end_date    || '';
    document.getElementById('lm-reason').value  = data?.reason      || '';
    const approverEl = document.getElementById('lm-approver');
    if (approverEl) approverEl.value = data?.approver_id || '';
    const statusEl = document.getElementById('lm-status');
    if (statusEl && data?.status) statusEl.value = data.status;

    handleTypeChange();
    document.getElementById('leave-modal').style.display = 'flex';
}

async function closeLeaveModal() {
    document.getElementById('leave-modal').style.display = 'none';
}

async function handleTypeChange() {
    const t = document.getElementById('lm-type').value;
    const isHalf = t === 'half_day_am' || t === 'half_day_pm';
    const endWrap = document.getElementById('lm-end-wrap');
    if (isHalf) {
        endWrap.style.opacity = '.4';
        endWrap.style.pointerEvents = 'none';
        syncEndDate();
    } else {
        endWrap.style.opacity = '1';
        endWrap.style.pointerEvents = '';
    }
}

async function syncEndDate() {
    const t = document.getElementById('lm-type').value;
    const isHalf = t === 'half_day_am' || t === 'half_day_pm';
    if (isHalf) {
        document.getElementById('lm-end').value = document.getElementById('lm-start').value;
    }
}

async function submitLeave() {
    const start = document.getElementById('lm-start').value;
    const end   = document.getElementById('lm-end').value;
    if (!start) { showLvError(_LV_STR.errorNoStart); return; }
    if (!end)   { showLvError(_LV_STR.errorNoEnd); return; }
    if (end < start) { showLvError(_LV_STR.errorEndBefore); return; }

    const userEl = document.getElementById('lm-user');
    const userId = userEl?.tagName === 'SELECT' ? userEl.value : userEl?.value || _LV_MY_ID;

    const approverEl = document.getElementById('lm-approver');
    const approverId = approverEl?.value ? parseInt(approverEl.value) : null;

    const payload = {
        user_id:     parseInt(userId),
        approver_id: approverId,
        start_date:  start,
        end_date:    end,
        leave_type:  document.getElementById('lm-type').value,
        reason:      document.getElementById('lm-reason').value.trim(),
    };
    const statusEl = document.getElementById('lm-status');
    if (statusEl) payload.status = statusEl.value;

    const btn = document.getElementById('lm-submit');
    btn.disabled = true; btn.textContent = _LV_STR.processing;

    const url    = _lv_editId ? `${_LV_BASE_URL}/${_lv_editId}` : _LV_STORE_URL;
    const method = _lv_editId ? 'PATCH' : 'POST';

    try {
        const res = await fetch(url, {
            method,
            headers: { 'X-CSRF-TOKEN': _LV_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });
        const d = await res.json().catch(() => ({}));

        if (!res.ok || !d.ok) {
            showLvError(d.errors ? Object.values(d.errors).flat().join('\n') : (d.message || _LV_STR.processingFailed));
            return;
        }

        closeLeaveModal();
        if (_lv_editId) {
            updateLeaveRow(d.leave);
        } else {
            appendLeaveRow(d.leave);
        }
        showLvToast(_lv_editId ? _LV_STR.toastUpdated : _LV_STR.toastCreated, true);
    } finally {
        btn.disabled = false;
        btn.textContent = _lv_editId ? _LV_STR.saveBtn : _LV_STR.submitBtn;
    }
}

async function deleteLeave(id) {
    if (!await __confirm(_LV_STR.confirmDelete)) return;
    const res = await fetch(`${_LV_BASE_URL}/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': _LV_CSRF, 'Accept': 'application/json' },
    });
    const d = await res.json().catch(() => ({}));
    if (d.ok) {
        document.getElementById(`lv-row-${id}`)?.remove();
        checkEmpty();
        showLvToast(_LV_STR.toastDeleted, true);
    } else {
        showLvToast(d.message || _LV_STR.deleteFailed, false);
    }
}

async function changeStatus(id, status) {
    const res = await fetch(`${_LV_BASE_URL}/${id}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': _LV_CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ status }),
    });
    const d = await res.json().catch(() => ({}));
    if (d.ok) {
        updateLeaveRow(d.leave);
        showLvToast(status === 'approved' ? _LV_STR.toastApproved : _LV_STR.toastRejected, true);
    } else {
        showLvToast(d.message || _LV_STR.processingFailed, false);
    }
}

async function appendLeaveRow(lv) {
    const empty = document.getElementById('leave-empty-row');
    if (empty) empty.remove();
    const tbody = document.getElementById('leave-list');
    tbody.insertAdjacentHTML('beforeend', buildRow(lv));
}

async function updateLeaveRow(lv) {
    const row = document.getElementById(`lv-row-${lv.id}`);
    if (row) row.outerHTML = buildRow(lv);
}

function buildRow(lv) {
    const isMine = lv.user_id === _LV_MY_ID;
    const canEdit = isMine || _LV_IS_MGR;
    const dateRange = lv.start_date === lv.end_date ? lv.start_date : `${lv.start_date} ~ ${lv.end_date}`;
    const days = lv.leave_type === 'half_day_am' || lv.leave_type === 'half_day_pm'
        ? _LV_STR.halfDayUnit
        : `${lv.days_count}${_LV_STR.unitDays}`;

    const todayStr   = new Date().toISOString().split('T')[0];
    const isUpcoming = lv.status === 'approved' && lv.start_date > todayStr;
    const statusLabel = isUpcoming ? _LV_STR.upcoming : lv.status_label;
    const statusBg    = isUpcoming ? '#ede9fe' : lv.status_bg;
    const statusColor = isUpcoming ? '#6d28d9' : lv.status_color;
    const statusBorder = isUpcoming ? 'border:1px dashed #a78bfa;' : '';

    const canDecide = (_LV_IS_MGR || lv.approver_id === _LV_MY_ID) && lv.status === 'pending';
    const statusBtns = canDecide
        ? `<button onclick="changeStatus(${lv.id},'approved')" style="font-size:11px;padding:3px 9px;border-radius:6px;background:#d1fae5;color:#059669;border:none;cursor:pointer;font-weight:600;">${_LV_STR.statusApprove}</button>
           <button onclick="changeStatus(${lv.id},'rejected')" style="font-size:11px;padding:3px 9px;border-radius:6px;background:#fee2e2;color:#dc2626;border:none;cursor:pointer;font-weight:600;margin-left:4px;">${_LV_STR.statusReject}</button>`
        : '';

    const editBtn = canEdit
        ? `<button onclick="openLeaveModal(${JSON.stringify(lv).replace(/"/g,'&quot;')})" style="font-size:12px;color:#6366f1;background:none;border:none;cursor:pointer;padding:0 4px;">${_LV_STR.editBtn}</button>`
        : '';
    const delBtn = canEdit
        ? `<button onclick="deleteLeave(${lv.id})" style="font-size:12px;color:#ef4444;background:none;border:none;cursor:pointer;padding:0 4px;">${_LV_STR.deleteBtn}</button>`
        : '';

    return `<tr id="lv-row-${lv.id}" data-user-id="${lv.user_id}" style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
        <td style="padding:12px 20px;">
            <div style="display:flex;align-items:center;gap:8px;">
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#c4b5fd,#a78bfa);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0;">${lv.user_name.charAt(0)}</div>
                <span style="font-size:13px;font-weight:500;color:#1e293b;">${lv.user_name}</span>
            </div>
        </td>
        <td style="padding:12px;">
            <span style="font-size:12px;padding:2px 9px;border-radius:20px;font-weight:600;background:${lv.leave_type_bg};color:${lv.leave_type_color};border:1px solid ${lv.leave_type_color};">${lv.leave_type_label}</span>
        </td>
        <td style="padding:12px;">
            <div style="font-size:12px;color:#374151;">${dateRange}</div>
            <div style="font-size:11px;color:#94a3b8;">${days}</div>
        </td>
        <td style="padding:12px;max-width:200px;">
            <span style="font-size:12px;color:#64748b;">${lv.reason || '—'}</span>
        </td>
        <td style="padding:12px;">
            <span style="font-size:12px;padding:2px 9px;border-radius:20px;font-weight:600;background:${statusBg};color:${statusColor};${statusBorder}">${statusLabel}</span>
            ${lv.approver_name ? `<div style="font-size:11px;color:#94a3b8;margin-top:3px;">${_LV_STR.approverLabel}${lv.approver_name}</div>` : ''}
            ${statusBtns ? '<div style="margin-top:5px;">' + statusBtns + '</div>' : ''}
        </td>
        <td style="padding:12px 20px;text-align:right;white-space:nowrap;">
            ${editBtn}${delBtn}
        </td>
    </tr>`;
}

async function checkEmpty() {
    const tbody = document.getElementById('leave-list');
    if (!tbody.querySelector('tr[id^="lv-row-"]')) {
        tbody.innerHTML = `<tr id="leave-empty-row"><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">${_LV_STR.emptyMonth}</td></tr>`;
    }
}

async function showLvError(msg) {
    const el = document.getElementById('lm-error');
    el.textContent = msg;
    el.style.display = 'block';
}

async function showLvToast(msg, ok) {
    const t = document.createElement('div');
    t.textContent = msg;
    t.style.cssText = `position:fixed;bottom:24px;right:24px;padding:10px 18px;border-radius:8px;font-size:13px;font-weight:600;z-index:99999;color:#fff;background:${ok?'#059669':'#dc2626'};`;
    document.body.appendChild(t);
    setTimeout(() => { t.style.opacity='0'; setTimeout(() => t.remove(), 300); }, 2500);
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLeaveModal(); });

async function filterLeaveByMember(userId) {
    const rows = document.querySelectorAll('#leave-list tr[id^="lv-row-"]');
    let visible = 0;
    rows.forEach(row => {
        const show = !userId || row.dataset.userId === userId;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });
    const emptyRow = document.getElementById('leave-empty-row');
    if (rows.length > 0) {
        if (visible === 0 && !emptyRow) {
            document.getElementById('leave-list').insertAdjacentHTML('beforeend',
                `<tr id="leave-empty-row"><td colspan="6" style="text-align:center;padding:40px;color:#94a3b8;font-size:13px;">${_LV_STR.emptyMonth}</td></tr>`);
        } else if (visible > 0 && emptyRow) {
            emptyRow.remove();
        }
    }
}

function toggleTeamStats() {
    const body    = document.getElementById('team-stats-body');
    const chevron = document.getElementById('team-stats-chevron');
    if (!body) return;
    const hidden = body.style.display === 'none';
    body.style.display = hidden ? '' : 'none';
    chevron.style.transform = hidden ? '' : 'rotate(-90deg)';
}
</script>
@endsection
