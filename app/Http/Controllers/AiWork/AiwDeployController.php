<?php

namespace App\Http\Controllers\AiWork;

use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Services\AiWork\DeployService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 배포 대상 관리와 실행.
 *
 * 등록은 관리자만 한다. 여기 등록된 명령이 서버에서 그대로 실행되므로, 이 화면의
 * 권한이 사실상 서버 셸 권한이다.
 *
 * 실행은 프로젝트 편집 권한이 있는 사람이 할 수 있다 — 무엇이 실행되는지는
 * 이미 관리자가 정해 두었기 때문이다.
 */
class AiwDeployController extends Controller
{
    public function __construct(private DeployService $deploys) {}

    public function index(): View
    {
        $this->authorize('manageAgents', AiwJob::class);

        return view('aiw.deploys.index', [
            'targets'  => AiwDeployTarget::with(['project:id,name', 'creator:id,name'])
                ->orderBy('project_id')->get(),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            'recent'   => AiwDeploy::with(['target:id,name', 'requester:id,name'])
                ->latest('id')->limit(20)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $validated = $request->validate([
            'project_id'  => ['required', 'integer', 'exists:projects,id'],
            'name'        => ['required', 'string', 'max:100'],
            'working_dir' => ['required', 'string', 'max:500'],
            'command'     => ['required', 'string', 'max:500'],
            // 운영 서버가 이 서버와 다르면 담당자 PC 가 실행해야 한다.
            'runs_on'     => ['nullable', 'in:server,agent'],
            'timeout_sec' => ['nullable', 'integer', 'min:30', 'max:3600'],
        ]);

        AiwDeployTarget::create($validated + [
            'runs_on'     => $validated['runs_on'] ?? 'server',
            'timeout_sec' => $validated['timeout_sec'] ?? 900,
            'enabled'     => true,
            'created_by'  => $request->user()->id,
        ]);

        return back()->with('status', '배포 대상을 등록했습니다.');
    }

    public function toggle(AiwDeployTarget $target): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $target->forceFill(['enabled' => ! $target->enabled])->save();

        return back()->with('status', $target->enabled ? '활성화했습니다.' : '비활성화했습니다.');
    }

    public function destroy(AiwDeployTarget $target): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $target->delete();

        return back()->with('status', '배포 대상을 삭제했습니다.');
    }

    /** 작업 화면에서 배포를 실행한다. */
    public function run(Request $request, Project $project, AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);   // 배포도 편집 권한
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'target_id'    => ['required', 'integer'],
            'confirmation' => ['required', 'string', 'max:100'],
        ]);

        $target = AiwDeployTarget::where('project_id', $project->id)
            ->findOr($validated['target_id'], fn () => abort(404));

        try {
            $this->deploys->request($target, $request->user(), $validated['confirmation'], $job);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', '배포를 시작했습니다. 진행 상황이 이 화면에 표시됩니다.');
    }
}
