<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\WeeklyReport;
use App\Services\DocxWriter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagerWeeklySummaryController extends Controller
{
    private function authorizeManager(Project $project): void
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $project->getMemberRole($user) !== 'manager') {
            abort(403);
        }
    }

    public function index(Request $request, Project $project): View
    {
        $this->authorizeManager($project);

        $allWeeks = WeeklyReport::where('project_id', $project->id)
            ->selectRaw('week_start_date, year, week_number')
            ->distinct()
            ->orderByDesc('week_start_date')
            ->get();

        $selectedWeek = $request->get('week');
        $showAll      = $selectedWeek === 'all';

        if (!$selectedWeek) {
            $selectedWeek = $allWeeks->first()?->week_start_date?->format('Y-m-d');
        }

        $query = WeeklyReport::where('project_id', $project->id)
            ->with(['user:id,name', 'tasks'])
            ->orderByDesc('week_start_date')
            ->orderBy('author_name');

        if (!$showAll && $selectedWeek) {
            $query->where('week_start_date', $selectedWeek);
        }

        $reports = $query->get();
        $grouped = $reports->groupBy(fn($r) => $r->week_start_date->format('Y-m-d'))->sortKeysDesc();

        return view('weekly-reports.manager-summary', compact(
            'project', 'allWeeks', 'selectedWeek', 'showAll', 'grouped'
        ));
    }

    public function download(Request $request, Project $project): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
    {
        $this->authorizeManager($project);

        $weekFilter = $request->input('week');

        $query = WeeklyReport::where('project_id', $project->id)
            ->with(['project', 'user', 'tasks'])
            ->orderByDesc('week_start_date')
            ->orderBy('author_name');

        if ($weekFilter && $weekFilter !== 'all') {
            $query->where('week_start_date', $weekFilter);
        }

        $reports = $query->get();

        if ($reports->isEmpty()) {
            return back()->with('error', '다운로드할 보고서가 없습니다.');
        }

        $writer = new DocxWriter();
        $writer->buildManagerSummary($project, $reports, $weekFilter);

        $sanitize   = static fn(string $s): string => str_replace(
            ['/', '\\', ':', '*', '?', '"', '<', '>', '|', ' '], '_', $s
        );
        $weekSuffix = ($weekFilter && $weekFilter !== 'all')
            ? '_' . Carbon::parse($weekFilter)->format('Ymd')
            : '_전체주차';

        $filename = $sanitize($project->name) . '_AI서머리종합' . $weekSuffix . '_' . now()->format('Ymd') . '.docx';

        $dir = storage_path('app/temp');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $path = $dir . '/' . $filename;
        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }
}
