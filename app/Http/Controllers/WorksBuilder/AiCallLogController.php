<?php

namespace App\Http\Controllers\WorksBuilder;

use App\Http\Controllers\Controller;
use App\Models\WorksBuilder\AiCallLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AiCallLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = Auth::user();

        $query = AiCallLog::with('task.project')
            ->whereHas('task', function ($q) use ($user) {
                if ($user->isAdmin()) return;
                $q->where('assignee_id', $user->id);
            })
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('provider')) {
            $query->where('final_provider', $request->provider);
        }
        if ($request->filled('fallback') && $request->fallback === '1') {
            $query->where('fallback_used', true);
        }

        $logs = $query->paginate(30)->withQueryString();

        $totals = [
            'tokens' => (int) $query->clone()->sum('total_tokens'),
            'cost'   => (float) $query->clone()->sum('estimated_cost_usd'),
            'count'  => $query->clone()->count(),
        ];

        return view('works-builder.ai-call-logs.index', compact('logs', 'totals'));
    }
}
