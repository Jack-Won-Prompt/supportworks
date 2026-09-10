<?php

namespace App\Http\Controllers\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\HandoverRequested;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobEndRequested;
use App\Events\AiWork\JobUserMessage;
use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\Project;
use App\Services\AiWork\HandoverService;
use App\Services\AiWork\JobDispatcher;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\PermissionService;
use App\Services\AiWork\ToolPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * AI 작업 지시 화면.
 *
 * 비즈니스 로직은 전부 Phase 4 서비스에 있다. 이 컨트롤러는 권한 확인, 입력
 * 검증, 서비스 호출, 화면 반환만 한다.
 */
class AiwJobController extends Controller
{
    public function __construct(
        private JobDispatcher $dispatcher,
        private JobStateMachine $states,
        private PermissionService $permissions,
        private HandoverService $handovers,
        private ToolPolicy $tools,
    ) {}

    /** 화면 2: 지시 목록 */
    public function index(Request $request, Project $project): View
    {
        $this->authorize('viewAny', [AiwJob::class, $project]);

        $agents = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->with(['agentProjects' => fn ($q) => $q->where('project_id', $project->id)])
            ->get();

        // 작업 PC별 "실행 중 N / 상한 M". M 은 데몬이 하트비트로 보고한 값이다.
        $runningByAgent = AiwJob::query()
            ->whereIn('agent_id', $agents->pluck('id'))
            ->whereIn('status', $this->activeStatuses())
            ->selectRaw('agent_id, count(*) as c')
            ->groupBy('agent_id')
            ->pluck('c', 'agent_id');

        $jobs = AiwJob::query()
            ->where('project_id', $project->id)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->with(['agent:id,name', 'creator:id,name'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('aiw.jobs.index', compact('project', 'agents', 'jobs', 'runningByAgent'));
    }

    /** 화면 3: 새 지시 등록 폼 */
    public function create(Request $request, Project $project): View
    {
        $this->authorize('create', [AiwJob::class, $project]);

        $agents = AiwAgent::query()
            ->online()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->get();

        // 후속 지시: 원 job 의 설정을 그대로 물려받되 원 job 은 건드리지 않는다.
        $parent = null;
        if ($request->filled('parent')) {
            $parent = AiwJob::where('project_id', $project->id)->find($request->integer('parent'));
            if ($parent) {
                $this->authorize('view', $parent);
            }
        }

        // 같은 폴더에서 이미 실행 중인 job 이 있으면 대기하게 된다는 것을 미리 알린다.
        $busyByPath = $this->busyLocalPaths($project);

        return view('aiw.jobs.create', [
            'project'       => $project,
            'agents'        => $agents,
            'parent'        => $parent,
            'supportedTools' => ToolPolicy::supported(),
            'defaultTools'  => ToolPolicy::defaults(),
            'autoApprovable' => ToolPolicy::autoApprovable(),
            'busyByPath'    => $busyByPath,
            'defaultCost'   => (float) config('aiw.default_cost_limit_usd', 2.0),
            'contextLimits' => (array) config('aiw.model_context_limits', []),
            'defaultContext' => (int) config('aiw.default_context_limit_tokens', 200000),
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorize('create', [AiwJob::class, $project]);

        $validated = $request->validate([
            'title'           => ['required', 'string', 'max:191'],
            'agent_id'        => ['required', 'integer'],
            'instruction'     => ['required', 'string'],
            'mode'            => ['required', 'in:batch,interactive'],
            'model'           => ['nullable', 'string', 'max:100'],
            'allowed_tools'   => ['required', 'array', 'min:1'],
            'permission_mode' => ['required', 'in:acceptEdits,default'],
            'cost_limit_usd'  => ['required', 'numeric', 'gt:0', 'max:1000'],
            'use_branch'      => ['nullable', 'boolean'],
            'parent_job_id'   => ['nullable', 'integer'],
        ]);

        $agent = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->findOrFail($validated['agent_id']);

        // 툴 목록은 서버가 강제한다. 클라이언트가 보낸 값을 그대로 쓰지 않는다.
        $tools = $this->tools->sanitize($validated['allowed_tools']);

        $job = AiwJob::create([
            'project_id'           => $project->id,
            'agent_id'             => $agent->id,
            'parent_job_id'        => $validated['parent_job_id'] ?? null,
            'title'                => $validated['title'],
            'instruction'          => $validated['instruction'],
            'mode'                 => $validated['mode'],
            'model'                => $validated['model'] ?? null,
            'context_limit_tokens' => $this->contextLimitFor($validated['model'] ?? null),
            'allowed_tools'        => $tools,
            'permission_mode'      => $validated['permission_mode'],
            'cost_limit_usd'       => $validated['cost_limit_usd'],
            'use_branch'           => (bool) ($validated['use_branch'] ?? true),
            'created_by'           => $request->user()->id,
        ]);

        // 최초 지시문을 대화의 첫 메시지로 남긴다(화면 4 의 첫 말풍선).
        AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => 0,
            'role'          => 'user',
            'content'       => $job->instruction,
            'user_id'       => $request->user()->id,
            'session_index' => 0,
            'delivered_at'  => now(),   // 지시문은 JobDispatched 로 함께 전달된다
        ]);

        $sent = $job->parent_job_id
            ? $this->dispatcher->dispatchFollowUp($job)
            : $this->dispatcher->dispatch($job);

        return redirect()
            ->route('projects.ai-works.show', [$project, $job])
            ->with('status', $sent
                ? '지시를 작업 PC 로 보냈습니다.'
                : '작업 PC 가 오프라인입니다. 접속하면 자동으로 시작됩니다.');
    }

    /** 화면 4: 지시 상세 */
    public function show(Project $project, AiwJob $job): View
    {
        $this->authorize('view', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $job->load(['agent:id,name', 'creator:id,name', 'parent:id,title']);

        return view('aiw.jobs.show', [
            'project'  => $project,
            'job'      => $job,
            'messages' => $job->messages()->orderBy('seq')->with('author:id,name')->get(),
            'logs'     => $job->logs()->orderBy('seq')->get(),
            // pending 카드는 새로고침해도 유지돼야 한다.
            'pending'  => $job->permissionRequests()->pending()->orderBy('id')->get(),
            'decided'  => $job->permissionRequests()->whereIn('status', ['allowed', 'denied', 'expired'])
                ->with('decider:id,name')->orderBy('id')->get(),
            'canEdit'  => auth()->user()->can('sendMessage', $job),
        ]);
    }

    /** 대화형: 사용자 메시지 전송 */
    public function message(Request $request, Project $project, AiwJob $job): RedirectResponse
    {
        $this->authorize('sendMessage', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $validated = $request->validate(['content' => ['required', 'string', 'max:20000']]);

        abort_if($job->status->isTerminal(), 409, '종료된 작업에는 메시지를 보낼 수 없습니다.');
        abort_if($job->mode !== 'interactive', 409, '대화형 작업에서만 메시지를 보낼 수 있습니다.');

        $message = AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => (int) $job->messages()->max('seq') + 1,
            'role'          => 'user',
            'content'       => $validated['content'],
            'user_id'       => $request->user()->id,
            'session_index' => $job->currentSessionIndex(),
        ]);

        $this->emit(new JobUserMessage($message, $job->agent_id), $job->id);

        return back()->with('status', '메시지를 보냈습니다.');
    }

    /** 승인 카드 결정 */
    public function decide(Request $request, Project $project, AiwJob $job, AiwPermissionRequest $permission): RedirectResponse
    {
        $this->authorize('decidePermission', $job);
        abort_unless((int) $permission->job_id === (int) $job->id, 404);

        $validated = $request->validate([
            'decision'    => ['required', 'in:allow,deny'],
            'deny_reason' => ['nullable', 'string', 'max:191'],
        ]);

        $changed = $this->permissions->decide(
            $permission,
            $request->user(),
            $validated['decision'] === 'allow',
            $validated['deny_reason'] ?? null,
        );

        return back()->with('status', $changed ? '승인 요청을 처리했습니다.' : '이미 결정된 요청입니다.');
    }

    /** 취소 / 세션 종료 / 컨텍스트 정리 / 재전송 */
    public function action(Request $request, Project $project, AiwJob $job, string $action): RedirectResponse
    {
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        return match ($action) {
            'cancel'    => $this->cancel($job),
            'end'       => $this->end($job),
            'handover'  => $this->handover($job),
            'redispatch' => $this->redispatch($job),
            default     => abort(404),
        };
    }

    private function cancel(AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);
        abort_unless($job->status->isActive() || $job->status === AiwJobStatus::Dispatched, 409, '취소할 수 없는 상태입니다.');

        $this->emit(new JobCancelRequested($job, 'user cancelled'), $job->id);
        $this->states->transition($job, AiwJobStatus::Cancelled, ['error_message' => '사용자가 취소했습니다.']);

        return back()->with('status', '작업을 취소했습니다.');
    }

    private function end(AiwJob $job): RedirectResponse
    {
        $this->authorize('end', $job);
        abort_unless($job->mode === 'interactive' && $job->status->isActive(), 409, '종료할 수 없는 상태입니다.');

        // 상태는 데몬이 최종 결과를 보고할 때 completed 로 바뀐다. 여기서는 요청만 보낸다.
        $this->emit(new JobEndRequested($job), $job->id);

        return back()->with('status', '세션 종료를 요청했습니다. 데몬이 마지막 턴을 마치면 완료됩니다.');
    }

    private function handover(AiwJob $job): RedirectResponse
    {
        $this->authorize('handover', $job);
        abort_unless(
            $job->mode === 'interactive'
                && in_array($job->status, [AiwJobStatus::Running, AiwJobStatus::WaitingInput], true),
            409,
            '컨텍스트 정리를 요청할 수 없는 상태입니다.',
        );

        $this->emit(new HandoverRequested($job), $job->id);

        return back()->with('status', '컨텍스트 정리를 요청했습니다. 턴이 끝나는 대로 세션이 교체됩니다.');
    }

    private function redispatch(AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);   // 재전송도 편집 권한

        $sent = $this->dispatcher->redispatch($job);

        return back()->with('status', $sent ? '지시를 다시 보냈습니다.' : '작업 PC 가 아직 오프라인입니다.');
    }

    /** 인수인계 항목을 CLAUDE.md 로 승격 — 후속 job 을 만든다(파일을 직접 쓰지 않는다). */
    public function promote(Request $request, Project $project, AiwJob $job): RedirectResponse
    {
        $this->authorize('create', [AiwJob::class, $project]);

        $validated = $request->validate([
            'sections'   => ['required', 'array', 'min:1'],
            'sections.*' => ['required', 'string', 'max:1000'],
        ]);

        $follow = $this->handovers->promoteToClaudeMd($job, $validated['sections'], $request->user());
        $this->dispatcher->dispatch($follow);

        return redirect()
            ->route('projects.ai-works.show', [$project, $follow])
            ->with('status', 'CLAUDE.md 승격 작업을 생성했습니다. 결과를 확인한 뒤 병합하세요.');
    }

    // ── 내부 ────────────────────────────────────────────────────────────────

    /** @return list<AiwJobStatus> */
    private function activeStatuses(): array
    {
        return [
            AiwJobStatus::Running, AiwJobStatus::WaitingInput,
            AiwJobStatus::WaitingPermission, AiwJobStatus::Handover,
        ];
    }

    /** 같은 local_path 에서 실행 중인 job. 데몬이 폴더 단위로 직렬 실행한다. */
    private function busyLocalPaths(Project $project): array
    {
        return AiwJob::query()
            ->whereIn('status', $this->activeStatuses())
            ->whereHas('agent.agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->pluck('id', 'agent_id')
            ->all();
    }

    private function contextLimitFor(?string $model): int
    {
        $limits = (array) config('aiw.model_context_limits', []);

        return (int) ($limits[$model] ?? config('aiw.default_context_limit_tokens', 200000));
    }

    private function emit(object $event, int $jobId): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            // 브로드캐스트 실패로 사용자 액션을 실패시키지 않는다. 데몬은 /inbox 로 따라잡는다.
            Log::warning('AI Works: 브로드캐스트 실패(요청은 저장됨)', [
                'job_id' => $jobId,
                'event'  => $event::class,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
