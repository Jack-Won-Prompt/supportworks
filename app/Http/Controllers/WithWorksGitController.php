<?php

namespace App\Http\Controllers;

use App\Services\WithWorks\WithWorksGitIngestService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WithWorksGitController extends Controller
{
    public function sync(Request $request, WithWorksGitIngestService $svc): JsonResponse
    {
        $user = $request->user();
        // 관리자 또는 SR 담당자만 트리거 가능
        abort_unless($user && ($user->isAdmin() || (bool) ($user->is_sr_agent ?? false)), 403);

        $days  = (int) $request->input('days', 30);
        $since = $request->filled('since')
            ? Carbon::parse($request->input('since'))
            : now()->subDays(max(1, $days));

        $run = $svc->sync($since, null, $user->id);

        return response()->json([
            'ok'       => $run->status === 'success',
            'status'   => $run->status,
            'inserted' => $run->inserted,
            'skipped'  => $run->skipped,
            'branch'   => $run->branch,
            'since'    => optional($run->since)->toIso8601String(),
            'error'    => $run->error_message,
        ]);
    }
}
