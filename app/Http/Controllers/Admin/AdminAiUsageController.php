<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent\AiAgentUsageLog;
use App\Models\CompanyGroup;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAiUsageController extends Controller
{
    public function index(Request $request)
    {
        $admin    = auth('admin')->user();
        $groups   = $this->accessibleGroups($admin);
        $groupIds = $groups->pluck('id');

        $accessibleProjectIds = Project::when(!$admin->isSuperAdmin(),
                fn($q) => $q->whereIn('company_group_id', $groupIds)
            )->pluck('id');

        $period = $request->get('period', '30');
        $fromDate = match($period) {
            '7'   => now()->subDays(7),
            '30'  => now()->subDays(30),
            '90'  => now()->subDays(90),
            'all' => null,
            default => now()->subDays(30),
        };

        $baseQuery = AiAgentUsageLog::whereIn('project_id', $accessibleProjectIds)
            ->when($fromDate, fn($q) => $q->where('created_at', '>=', $fromDate))
            ->when($request->group_id, fn($q) =>
                $q->whereIn('project_id',
                    Project::where('company_group_id', $request->group_id)->pluck('id')
                )
            )
            ->when($request->model, fn($q) => $q->where('model', 'like', $request->model.'%'))
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        $stats = [
            'total_cost'     => (clone $baseQuery)->successful()->sum('cost_usd'),
            'total_calls'    => (clone $baseQuery)->successful()->count(),
            'input_tokens'   => (clone $baseQuery)->successful()->sum('input_tokens'),
            'output_tokens'  => (clone $baseQuery)->successful()->sum('output_tokens'),
            'error_count'    => (clone $baseQuery)->where('status', 'error')->count(),
        ];

        $byModel = (clone $baseQuery)->successful()
            ->select('model', DB::raw('COUNT(*) as calls'), DB::raw('SUM(cost_usd) as cost'), DB::raw('SUM(input_tokens+output_tokens) as tokens'))
            ->groupBy('model')
            ->orderByDesc('cost')
            ->get();

        $byProject = (clone $baseQuery)->successful()
            ->with('project.companyGroup')
            ->select('project_id', DB::raw('COUNT(*) as calls'), DB::raw('SUM(cost_usd) as cost'), DB::raw('SUM(input_tokens+output_tokens) as tokens'))
            ->groupBy('project_id')
            ->orderByDesc('cost')
            ->limit(10)
            ->get();

        $logs = (clone $baseQuery)
            ->with(['project', 'user'])
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $models = AiAgentUsageLog::whereIn('project_id', $accessibleProjectIds)
            ->distinct()->pluck('model')->filter()->sort()->values();

        return view('admin.ai-usage.index', compact(
            'stats', 'byModel', 'byProject', 'logs', 'groups', 'models', 'period'
        ));
    }

    private function accessibleGroups($admin)
    {
        return $admin->isSuperAdmin()
            ? CompanyGroup::orderBy('name')->get()
            : $admin->companyGroups()->orderBy('name')->get();
    }
}
