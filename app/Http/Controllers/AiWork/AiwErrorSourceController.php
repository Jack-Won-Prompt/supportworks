<?php

namespace App\Http\Controllers\AiWork;

use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwErrorSource;
use App\Models\AiWork\AiwJob;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 오류 수집 출처(운영 사이트) 관리.
 *
 * 이 화면에서 발급하는 토큰은 "그 프로젝트에 오류를 보낼 자격" 이다. 다음
 * 단계에서 오류가 작업 지시로 이어지면, 그 지시는 작업 PC 에서 코드를 고치고
 * 운영에 배포까지 한다. 그래서 담당자 등록과 같은 급으로 관리자만 다룬다.
 *
 * 토큰 원문은 만들 때 한 번만 보여 준다. 저장하는 것은 SHA-256 뿐이라 우리도
 * 다시 꺼내 줄 수 없다 — 잃어버리면 재발급이다.
 */
class AiwErrorSourceController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageAgents', AiwJob::class);

        return view('aiw.errors.sources', [
            'sources'  => AiwErrorSource::with(['project:id,name', 'creator:id,name'])
                ->withCount('reports')
                ->orderBy('project_id')->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $validated = $request->validate([
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'name'       => ['required', 'string', 'max:100'],
        ]);

        $raw = AiwErrorSource::generateToken();

        AiwErrorSource::create([
            'project_id' => $validated['project_id'],
            'name'       => $validated['name'],
            'token_hash' => AiwErrorSource::hashToken($raw),
            'enabled'    => true,
            'created_by' => $request->user()->id,
        ]);

        // 한 번만 보여 준다. 세션에 담아 다음 화면에서 띄우고 끝이다.
        return back()->with('aiw_error_token', $raw)
            ->with('status', $validated['name'].' 출처를 등록했습니다. 아래 토큰을 지금 복사하세요.');
    }

    /** 토큰이 샜을 때. 지우지 않고 끄면 되돌리기 쉽다. */
    public function toggle(AiwErrorSource $source): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $source->update(['enabled' => ! $source->enabled]);

        return back()->with('status', $source->name.' 출처를 '.($source->enabled ? '켰습니다' : '껐습니다').'.');
    }

    public function reissue(AiwErrorSource $source): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $raw = AiwErrorSource::generateToken();

        $source->update(['token_hash' => AiwErrorSource::hashToken($raw)]);

        return back()->with('aiw_error_token', $raw)
            ->with('status', $source->name.' 토큰을 다시 발급했습니다. 기존 토큰은 무효입니다.');
    }

    public function destroy(AiwErrorSource $source): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $name = $source->name;

        $source->delete();

        return back()->with('status', $name.' 출처를 삭제했습니다.');
    }

    // ── 쌓인 오류 ───────────────────────────────────────────────────────────

    /**
     * 프로젝트별 오류 목록.
     *
     * 자동 생성을 붙이기 전에 이 화면이 먼저 있어야 한다. 눈으로 보지 못한 채
     * 자동 생성을 켜면, 잘못 묶인 오류가 작업 지시 수십 개로 번진 뒤에야 안다.
     */
    public function reports(Request $request, Project $project): View
    {
        $this->authorize('viewAny', [AiwJob::class, $project]);

        $status = $request->string('status')->toString();

        $reports = AiwErrorReport::query()
            ->where('project_id', $project->id)
            ->when($status !== '', fn ($q) => $q->where('status', $status))
            ->with(['source:id,name', 'job:id,title,status'])
            ->orderByDesc('last_seen_at')
            ->paginate(30)
            ->withQueryString();

        return view('aiw.errors.index', [
            'project' => $project,
            'reports' => $reports,
            'status'  => $status,
            'counts'  => AiwErrorReport::where('project_id', $project->id)
                ->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    /** 고칠 대상이 아니라고 사람이 표시한다(봇 스캔·클라이언트 유발 등). */
    public function ignore(Project $project, AiwErrorReport $report): RedirectResponse
    {
        $this->authorize('viewAny', [AiwJob::class, $project]);
        abort_unless($report->project_id === $project->id, 404);

        $report->update([
            'status' => $report->status === AiwErrorReport::STATUS_IGNORED
                ? AiwErrorReport::STATUS_NEW
                : AiwErrorReport::STATUS_IGNORED,
        ]);

        return back();
    }
}
