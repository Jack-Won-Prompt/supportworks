<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiFixJob;
use App\Services\AiFix\AiFixOrchestrator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 관리자 웹 패널의 AI Fix Job 관리.
 *
 * Base path: /admin/ai-fix-jobs   (admin guard)
 *
 * D-1 단계에선 컨트롤러 + 라우트만 준비. 실제 Blade 뷰는 D-2 에서 추가.
 */
class AdminAiFixJobController extends Controller
{
    public function __construct(
        private readonly AiFixOrchestrator $orchestrator,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('status', 'awaiting_approval');
        $query  = AiFixJob::query()->with('systemErrorLog')->latest();

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'terminal') {
            $query->terminal();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $jobs  = $query->paginate(30)->withQueryString();
        $stats = [
            'awaiting' => AiFixJob::awaitingApproval()->count(),
            'active'   => AiFixJob::active()->count(),
            'total'    => AiFixJob::count(),
        ];

        return view('admin.ai-fix-jobs.index', compact('jobs', 'status', 'stats'));
    }

    public function show(AiFixJob $aiFixJob): View
    {
        return view('admin.ai-fix-jobs.show', [
            'job'   => $aiFixJob->load('systemErrorLog'),
            'error' => $aiFixJob->systemErrorLog,
        ]);
    }

    /** 목록 모달 전용 — partial(_detail.blade) 만 반환. layout 없음. */
    public function modal(AiFixJob $aiFixJob): View
    {
        return view('admin.ai-fix-jobs._detail', [
            'job'   => $aiFixJob->load('systemErrorLog'),
            'error' => $aiFixJob->systemErrorLog,
        ]);
    }

    public function approve(Request $request, AiFixJob $aiFixJob): RedirectResponse
    {
        $admin = auth('admin')->user();
        try {
            $this->orchestrator->approve($aiFixJob, $admin->id);
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
        return redirect()->route('admin.ai-fix-jobs.show', $aiFixJob)
            ->with('success', '승인되었습니다.');
    }

    public function reject(Request $request, AiFixJob $aiFixJob): RedirectResponse
    {
        $admin  = auth('admin')->user();
        $reason = (string) $request->input('reason', '');
        try {
            $this->orchestrator->reject($aiFixJob, $admin->id, $reason ?: null);
        } catch (\DomainException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
        return redirect()->route('admin.ai-fix-jobs.show', $aiFixJob)
            ->with('success', '거부되었습니다.');
    }
}