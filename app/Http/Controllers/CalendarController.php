<?php

namespace App\Http\Controllers;

use App\Helpers\KoreanHolidays;
use App\Models\Discussion;
use App\Models\MeetingMinute;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $year  = max(2000, min(2100, $year));
        $month = max(1,    min(12,   $month));

        $startOfMonth = Carbon::create($year, $month, 1)->startOfDay();
        $endOfMonth   = $startOfMonth->copy()->endOfMonth()->endOfDay();

        $projectIds = $user->isAdmin()
            ? \App\Models\Project::pluck('id')
            : $user->projects()->pluck('projects.id');

        $schedules = Schedule::with(['project', 'assignee'])
            ->whereIn('project_id', $projectIds)
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end_date',   [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($q2) use ($startOfMonth, $endOfMonth) {
                      $q2->where('start_date', '<=', $startOfMonth)
                         ->where('end_date',   '>=', $endOfMonth);
                  });
            })
            ->orderBy('start_date')
            ->get();

        // 날짜별 이벤트 맵 생성
        $events = [];
        foreach ($schedules as $s) {
            $cur = $s->start_date->copy()->startOfDay();
            $end = $s->end_date ? $s->end_date->copy()->startOfDay() : $cur->copy();
            while ($cur <= $end) {
                $key = $cur->format('Y-m-d');
                $events[$key][] = [
                    'type'     => 'schedule',
                    'id'       => $s->id,
                    'title'    => $s->title,
                    'status'   => $s->status,
                    'priority' => $s->priority,
                    'project'  => $s->project->name ?? '',
                    'assignee' => $s->assignee->name ?? '',
                    'start'    => $s->start_date->format('Y-m-d'),
                    'end'      => $s->end_date ? $s->end_date->format('Y-m-d') : null,
                    'show_url' => route('schedules.show', $s->id),
                ];
                $cur->addDay();
            }
        }

        // ── 회의 일정 — 본인이 작성자이거나 참석자인 회의 ──
        $meetings = MeetingMinute::with('project')
            ->whereNotNull('meeting_date')
            ->whereBetween('meeting_date', [$startOfMonth, $endOfMonth])
            ->where(function ($q) use ($user) {
                $q->where('author_id', $user->id)
                  ->orWhereHas('attendees', fn($aq) => $aq->where('user_id', $user->id));
            })
            ->orderBy('meeting_date')
            ->get();
        foreach ($meetings as $m) {
            $key = $m->meeting_date->format('Y-m-d');
            $events[$key][] = [
                'type'     => 'meeting',
                'id'       => $m->id,
                'title'    => $m->title,
                'status'   => $m->status,
                'project'  => $m->project->name ?? '',
                'time'     => $m->meeting_date->format('H:i'),
                'location' => $m->location ?? '',
                'start'    => $key,
                'end'      => null,
                'show_url' => route('meeting-minutes.show', $m->id),
            ];
        }

        // ── 논의 — 본인이 작성자이거나 참여자인 논의 ──
        $discussions = Discussion::with('project')
            ->whereNotNull('discussion_date')
            ->whereBetween('discussion_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereHas('participants', fn($pq) => $pq->where('users.id', $user->id));
            })
            ->orderBy('discussion_date')
            ->get();
        foreach ($discussions as $d) {
            $key = $d->discussion_date->format('Y-m-d');
            $events[$key][] = [
                'type'     => 'discussion',
                'id'       => $d->id,
                'title'    => $d->title,
                'status'   => $d->status,
                'project'  => $d->project->name ?? '',
                'start'    => $key,
                'end'      => null,
                'show_url' => route('projects.discussions.index', $d->project_id) . '?open=' . $d->id,
            ];
        }

        // 각 날짜 셀은 3건까지만 노출되므로 회의·논의를 앞에 배치
        $typeOrder = ['meeting' => 0, 'discussion' => 1, 'schedule' => 2];
        foreach ($events as $k => $list) {
            usort($events[$k], fn($a, $b) => ($typeOrder[$a['type']] ?? 9) <=> ($typeOrder[$b['type']] ?? 9));
        }

        $prev = $startOfMonth->copy()->subMonth();
        $next = $startOfMonth->copy()->addMonth();
        $holidays = KoreanHolidays::getHolidays($year);

        return view('calendar.index', compact('year', 'month', 'startOfMonth', 'events', 'prev', 'next', 'holidays'));
    }
}
