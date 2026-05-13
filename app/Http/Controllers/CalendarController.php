<?php

namespace App\Http\Controllers;

use App\Helpers\KoreanHolidays;
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

        $prev = $startOfMonth->copy()->subMonth();
        $next = $startOfMonth->copy()->addMonth();
        $holidays = KoreanHolidays::getHolidays($year);

        return view('calendar.index', compact('year', 'month', 'startOfMonth', 'events', 'prev', 'next', 'holidays'));
    }
}
