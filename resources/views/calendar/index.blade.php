@extends('layouts.app')
@section('title', __('calendar.title'))

@section('content')
@php
    $today     = \Carbon\Carbon::today();
    $firstDay  = $startOfMonth->copy();
    $startDow  = $firstDay->dayOfWeek; // 0=일 ~ 6=토
    $daysInMonth = $firstDay->daysInMonth;
    $weeks = ceil(($startDow + $daysInMonth) / 7);

    $dayNames = [
        __('calendar.day_sun'),
        __('calendar.day_mon'),
        __('calendar.day_tue'),
        __('calendar.day_wed'),
        __('calendar.day_thu'),
        __('calendar.day_fri'),
        __('calendar.day_sat'),
    ];

    $statusColor = [
        'pending'     => '#fbbf24',
        'in_progress' => 'var(--t300)',
        'completed'   => '#34d399',
        'cancelled'   => '#d1d5db',
    ];
    $statusLabel = [
        'pending'     => __('calendar.status_pending'),
        'in_progress' => __('calendar.status_in_progress'),
        'completed'   => __('calendar.status_completed'),
        'cancelled'   => __('calendar.status_cancelled'),
    ];
@endphp

<style>
#cal-grid { display:grid; grid-template-columns:repeat(7,1fr); border-left:1px solid #e4e4e7; }
.cal-head { text-align:center; padding:8px 0; font-size:12px; font-weight:600; color:#71717a; background:#f8fafc; border-right:1px solid #e4e4e7; border-bottom:1px solid #e4e4e7; }
.cal-head.sun { color:#ef4444; }
.cal-head.sat { color:#93c5fd; }
.cal-cell { border-right:1px solid #e4e4e7; border-bottom:1px solid #e4e4e7; min-height:110px; padding:6px 6px 4px; background:#fff; vertical-align:top; position:relative; }
.cal-cell.other-month { background:#f9fafb; }
.cal-cell.today { background:#f0edff; }
.cal-cell.today .cal-day-num { background:#7c6cf0; color:#fff; }
.cal-cell.holiday-cell { background:#fff8f8; }
.cal-day-num { display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; font-size:12.5px; font-weight:600; color:#3f3f46; margin-bottom:4px; }
.cal-day-num.sun { color:#ef4444; }
.cal-day-num.sat { color:#93c5fd; }
.cal-day-num.holiday { color:#ef4444; }
.cal-holiday-name { font-size:10px; color:#ef4444; font-weight:600; line-height:1.3; margin-bottom:3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cal-event { display:flex; align-items:center; gap:4px; padding:2px 6px; border-radius:4px; font-size:11.5px; font-weight:500; color:#fff; margin-bottom:2px; cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; line-height:1.5; transition:filter .1s; }
.cal-event:hover { filter:brightness(0.92); }
.cal-event.cancelled { color:#6b7280; }
.cal-more { font-size:11px; color:#94a3b8; padding:1px 4px; cursor:pointer; }
.cal-more:hover { color:#7c6cf0; }

/* 날짜별 점 + 호버 팝오버 */
.cal-cell.has-events { cursor:pointer; }
.cal-cell.has-events:hover { background:#f5f3ff; }
.cal-cell.today.has-events:hover { background:#e8e3ff; }
.cal-dots { display:flex; flex-wrap:wrap; gap:4px; align-items:center; margin-top:2px; }
.cal-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.cal-more-dot { font-size:10px; color:#94a3b8; font-weight:600; line-height:8px; }
#cal-hover-pop { display:none; position:fixed; z-index:9997; background:#fff; border:1px solid #e4e4e7; border-radius:10px; box-shadow:0 8px 28px rgba(0,0,0,.16); padding:9px 11px; min-width:190px; max-width:300px; pointer-events:none; }
.chp-row { display:flex; align-items:center; gap:7px; padding:3px 2px; }
.chp-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.chp-title { font-size:12px; color:#3f3f46; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

/* Detail panel */
#cal-detail { display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:9999; background:#fff; border:1px solid #e4e4e7; border-radius:14px; box-shadow:0 12px 40px rgba(0,0,0,.15); min-width:300px; max-width:380px; max-height:80vh; flex-direction:column; overflow:hidden; }
#cal-detail-header { display:flex; justify-content:space-between; align-items:center; padding:16px 20px 12px; border-bottom:1px solid #f4f4f5; flex-shrink:0; }
#cd-body { overflow-y:auto; padding:12px 20px 16px; }
#cal-overlay { display:none; position:fixed; inset:0; z-index:9998; background:rgba(0,0,0,.15); }
</style>

{{-- 캘린더 컨테이너 --}}
<div style="background:#fff;border:1px solid #e4e4e7;border-radius:12px;overflow:hidden;">

    {{-- 달력 위 좌측 날짜 네비게이션 --}}
    <div style="display:flex;align-items:center;gap:8px;padding:10px 14px;border-bottom:1px solid var(--color-bg-muted);">
        <a href="{{ route('calendar', ['year'=>$prev->year,'month'=>$prev->month]) }}"
           style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid #e4e4e7;border-radius:7px;background:#fff;color:var(--color-text-secondary);text-decoration:none;"
           onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <span style="font-size:14px;font-weight:600;color:var(--color-text-primary);min-width:90px;text-align:center;">{{ __('calendar.year_month', ['year' => $year, 'month' => $month]) }}</span>
        <a href="{{ route('calendar', ['year'=>$next->year,'month'=>$next->month]) }}"
           style="display:flex;align-items:center;justify-content:center;width:30px;height:30px;border:1px solid #e4e4e7;border-radius:7px;background:#fff;color:var(--color-text-secondary);text-decoration:none;"
           onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">
            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <a href="{{ route('calendar', ['year'=>now()->year,'month'=>now()->month]) }}"
           style="padding:5px 12px;font-size:13px;font-weight:500;border:1px solid #e4e4e7;border-radius:7px;background:#fff;color:var(--color-text-secondary);text-decoration:none;"
           onmouseover="this.style.background='#f4f4f5'" onmouseout="this.style.background='#fff'">{{ __('calendar.today') }}</a>
    </div>

{{-- 캘린더 그리드 --}}
<div id="cal-grid">
    {{-- 요일 헤더 --}}
    @foreach($dayNames as $i => $dn)
    <div class="cal-head {{ $i===0?'sun':($i===6?'sat':'') }}">{{ $dn }}</div>
    @endforeach

    {{-- 날짜 셀 --}}
    @php $cellDay = 1 - $startDow; @endphp
    @for($w = 0; $w < $weeks; $w++)
        @for($d = 0; $d < 7; $d++)
            @php
                $cur  = $firstDay->copy()->addDays($cellDay - 1);
                $isThis = $cur->month == $month;
                $isToday = $cur->isSameDay($today);
                $key  = $cur->format('Y-m-d');
                $evts = $events[$key] ?? [];
                $dow  = $cur->dayOfWeek;
                $isHoliday = $isThis && isset($holidays[$key]);
                $holidayName = $holidays[$key] ?? '';
                $cellDay++;
            @endphp
            <div class="cal-cell {{ !$isThis?'other-month':'' }} {{ $isToday?'today':'' }} {{ $isHoliday?'holiday-cell':'' }} {{ count($evts)?'has-events':'' }}"
                 @if(count($evts))
                 onmouseenter="calHoverShow(this,'{{ $key }}')" onmouseleave="calHoverHide()"
                 onclick="showMore('{{ $key }}')"
                 @endif>
                <div class="cal-day-num {{ $isHoliday ? 'holiday' : ($dow===0?'sun':($dow===6?'sat':'')) }}"
                     style="{{ !$isThis?'opacity:.35;':'' }}"
                     title="{{ $holidayName }}">{{ $cur->day }}</div>
                @if($isHoliday)
                <div class="cal-holiday-name">{{ $holidayName }}</div>
                @endif

                @if(count($evts))
                <div class="cal-dots">
                    @foreach(array_slice($evts, 0, 10) as $ev)
                        @php
                            $col = match($ev['type'] ?? 'schedule') {
                                'meeting'    => '#7c3aed',
                                'discussion' => '#0ea5e9',
                                default      => $statusColor[$ev['status']] ?? '#94a3b8',
                            };
                        @endphp
                        <span class="cal-dot" style="background:{{ $col }};"></span>
                    @endforeach
                    @if(count($evts) > 10)
                    <span class="cal-more-dot">+{{ count($evts) - 10 }}</span>
                    @endif
                </div>
                @endif
            </div>
        @endfor
    @endfor
</div>
</div>{{-- /캘린더 컨테이너 --}}

{{-- 상세 패널 --}}
<div id="cal-overlay" onclick="closeDetail()"></div>
<div id="cal-detail">
    <div id="cal-detail-header">
        <span id="cd-date" style="font-size:13px;font-weight:600;color:var(--color-text-secondary);"></span>
        <button onclick="closeDetail()" style="background:none;border:none;cursor:pointer;color:var(--color-text-tertiary);font-size:20px;line-height:1;">&times;</button>
    </div>
    <div id="cd-body"></div>
</div>

{{-- 호버 팝오버 (날짜 셀에 마우스 오버 시 그날 일정 미리보기) --}}
<div id="cal-hover-pop"></div>

{{-- 날짜별 전체 이벤트 JSON --}}
<script>
const ALL_EVENTS = @json($events);

const STATUS_COLOR = {
    pending:    '#fbbf24',
    in_progress:'var(--t300)',
    completed:  '#34d399',
    cancelled:  '#d1d5db',
};
const STATUS_LABEL = {
    pending:    '{{ __('calendar.status_pending') }}',
    in_progress:'{{ __('calendar.status_in_progress') }}',
    completed:  '{{ __('calendar.status_completed') }}',
    cancelled:  '{{ __('calendar.status_cancelled') }}',
};
const STR_UNASSIGNED = '{{ __('calendar.unassigned') }}';

function renderEventCard(ev) {
    // 회의·논의 카드
    if (ev.type === 'meeting' || ev.type === 'discussion') {
        const isM = ev.type === 'meeting';
        const col = isM ? '#7c3aed' : '#0ea5e9';
        const typeLabel = isM ? '회의' : '논의';
        const sub = isM
            ? (ev.start + (ev.time ? ' ' + ev.time : '') + (ev.location ? ' · ' + ev.location : ''))
            : ev.start;
        return `<a href="${ev.show_url}" style="display:block;text-decoration:none;margin-bottom:8px;">
            <div style="border:1px solid var(--color-bg-muted);border-radius:9px;padding:10px 12px;transition:background .1s;"
                 onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                    <span style="background:${col};color:#fff;font-size:11px;font-weight:500;padding:1px 7px;border-radius:4px;">${typeLabel}</span>
                    <span style="font-size:11px;color:var(--color-text-tertiary);">${ev.project || ''}</span>
                </div>
                <div style="font-size:13px;font-weight:600;color:var(--color-text-primary);margin-bottom:3px;">${ev.title}</div>
                <div style="font-size:11.5px;color:#71717a;">${sub}</div>
            </div>
        </a>`;
    }

    const col   = STATUS_COLOR[ev.status] || '#94a3b8';
    const label = STATUS_LABEL[ev.status] || ev.status;
    const textCol = ev.status === 'cancelled' ? '#6b7280' : '#fff';
    return `<a href="${ev.show_url}" style="display:block;text-decoration:none;margin-bottom:8px;">
        <div style="border:1px solid var(--color-bg-muted);border-radius:9px;padding:10px 12px;transition:background .1s;"
             onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
                <span style="background:${ev.status==='cancelled'?'var(--color-bg-muted)':col};color:${textCol};font-size:11px;font-weight:500;padding:1px 7px;border-radius:4px;">${label}</span>
                <span style="font-size:11px;color:var(--color-text-tertiary);">${ev.project}</span>
            </div>
            <div style="font-size:13px;font-weight:600;color:var(--color-text-primary);margin-bottom:3px;">${ev.title}</div>
            <div style="font-size:11.5px;color:#71717a;">${ev.start}${ev.end && ev.end !== ev.start ? ' ~ ' + ev.end : ''} · ${ev.assignee || STR_UNASSIGNED}</div>
        </div>
    </a>`;
}

function showDetail(ev) {
    document.getElementById('cd-date').textContent = ev.start + (ev.end && ev.end !== ev.start ? ' ~ ' + ev.end : '');
    document.getElementById('cd-body').innerHTML = renderEventCard(ev);
    document.getElementById('cal-overlay').style.display = 'block';
    document.getElementById('cal-detail').style.display  = 'flex';
}

function showMore(dateKey) {
    const evts = ALL_EVENTS[dateKey] || [];
    document.getElementById('cd-date').textContent = dateKey;
    document.getElementById('cd-body').innerHTML = evts.map(renderEventCard).join('');
    document.getElementById('cal-overlay').style.display = 'block';
    document.getElementById('cal-detail').style.display  = 'flex';
}

function closeDetail() {
    document.getElementById('cal-overlay').style.display = 'none';
    document.getElementById('cal-detail').style.display  = 'none';
}

// ── 날짜 셀 호버 팝오버 ──────────────────────────────
function calEsc(s) {
    return String(s == null ? '' : s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
}
function calHoverShow(cell, key) {
    const pop  = document.getElementById('cal-hover-pop');
    const evts = ALL_EVENTS[key] || [];
    if (!evts.length) return;

    pop.innerHTML = `<div style="font-size:11px;font-weight:700;color:var(--color-text-secondary);margin-bottom:5px;">${key} · ${evts.length}건</div>`
        + evts.map(ev => {
            const col = ev.type === 'meeting'    ? '#7c3aed'
                      : ev.type === 'discussion' ? '#0ea5e9'
                      : (STATUS_COLOR[ev.status] || '#94a3b8');
            const tag = ev.type === 'meeting' ? '[회의] ' : ev.type === 'discussion' ? '[논의] ' : '';
            return `<div class="chp-row">
                <span class="chp-dot" style="background:${col};"></span>
                <span class="chp-title">${tag}${calEsc(ev.title)}</span>
            </div>`;
        }).join('');

    pop.style.display = 'block';
    // 해당 날짜의 동그라미(점) 바로 아래에 배치
    const anchor = cell.querySelector('.cal-dots') || cell;
    const r   = anchor.getBoundingClientRect();
    const pw  = pop.offsetWidth, ph = pop.offsetHeight;
    let left = r.left;
    let top  = r.bottom + 4;
    if (left + pw > window.innerWidth - 8) left = window.innerWidth - pw - 8;
    if (left < 8) left = 8;
    // 아래 공간이 부족하면 점 위쪽으로
    if (top + ph > window.innerHeight - 8) top = r.top - ph - 4;
    if (top < 8) top = 8;
    pop.style.left = left + 'px';
    pop.style.top  = top + 'px';
}
function calHoverHide() {
    document.getElementById('cal-hover-pop').style.display = 'none';
}
</script>
@endsection
