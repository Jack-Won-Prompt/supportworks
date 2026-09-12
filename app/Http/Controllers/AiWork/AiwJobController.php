<?php

namespace App\Http\Controllers\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\HandoverRequested;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobEndRequested;
use App\Events\AiWork\JobLogAppended;
use App\Events\AiWork\JobMessageAppended;
use App\Events\AiWork\JobUserMessage;
use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJobAttachment;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\Project;
use App\Services\AiWork\AttachmentService;
use App\Services\AiWork\PublishService;
use App\Services\AiWork\HandoverService;
use App\Services\AiWork\JobDispatcher;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\MessageWriter;
use App\Services\AiWork\PermissionService;
use App\Services\AiWork\ToolPolicy;
use Illuminate\Http\JsonResponse;
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
        private AttachmentService $attachments,
        private PublishService $publishes,
        private MessageWriter $messages,
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
            // capabilities 는 비용 라벨(실제 청구 vs 추정치) 판단에 필요하다.
            ->with(['agent:id,name,capabilities', 'creator:id,name'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('aiw.jobs.index', [
            'project'        => $project,
            'agents'         => $agents,
            'jobs'           => $jobs,
            'runningByAgent' => $runningByAgent,
            // 소스 경로·등록자·소요·작업량은 운영 정보다. 지시하는 사람에게는
            // 필요 없고, 매핑과 비용을 실제로 다루는 관리자에게만 보인다.
            'isAdmin'        => auth()->user()->can('manageAgents', AiwJob::class),
            // 담당자 하나가 여러 프로젝트를 맡을 때, 이 프로젝트에서 부를 이름.
            'agentNames'     => $agents->mapWithKeys(fn ($a) => [
                $a->id => $a->agentProjects->first()?->displayName() ?? $a->name,
            ]),
        ]);
    }

    /** 화면 3: 새 지시 등록 폼 */
    public function create(Request $request, Project $project): View
    {
        $this->authorize('create', [AiwJob::class, $project]);

        // 온라인 판정은 담당자 전체가 아니라 **이 프로젝트를 맡은 프로세스** 기준이다.
        // 한 PC 가 프로젝트마다 따로 띄우면 그중 하나만 죽을 수 있다.
        $agents = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->with(['agentProjects' => fn ($q) => $q->where('project_id', $project->id)])
            ->get()
            ->filter(fn (AiwAgent $a) => $a->agentProjects->first()?->is_online ?? $a->is_online)
            ->values();

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
            // 실패 화면의 "브랜치 없이 다시 지시" 가 ?use_branch=0 으로 보낸다.
            // 쿼리가 없으면 원 job 설정을, 그것도 없으면 켬(안전한 기본값)을 쓴다.
            // "배포까지 자동으로" 체크박스는 등록된 배포 대상이 있을 때만 뜬다.
            'deployTargets' => AiwDeployTarget::where('project_id', $project->id)
                ->where('enabled', true)->orderBy('name')->get(),
            'prefillUseBranch' => $request->has('use_branch')
                ? $request->boolean('use_branch')
                : (bool) ($parent->use_branch ?? true),
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
            // 비워 두면 제한 없음. 구독 로그인으로 도는 담당자는 화면의 금액이
            // 실제 청구가 아니라 환산값이라, 상한을 강제할 이유가 없다.
            'cost_limit_usd'  => ['nullable', 'numeric', 'gt:0', 'max:1000'],
            'no_cost_limit'   => ['nullable', 'boolean'],
            'use_branch'      => ['nullable', 'boolean'],
            'parent_job_id'   => ['nullable', 'integer'],
            'auto_deploy'     => ['nullable', 'boolean'],
            'auto_deploy_target_id' => ['nullable', 'integer'],
            'images'          => ['nullable', 'array', 'max:'.AttachmentService::MAX_PER_MESSAGE],
            'images.*'        => ['image', 'max:'.(AttachmentService::MAX_UPLOAD_BYTES / 1024)],
        ]);

        $agent = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->findOrFail($validated['agent_id']);

        // 툴 목록은 서버가 강제한다. 클라이언트가 보낸 값을 그대로 쓰지 않는다.
        $tools = $this->tools->sanitize($validated['allowed_tools']);

        // 배포 대상도 서버가 확인한다. 다른 프로젝트의 대상을 끼워 넣을 수 없다.
        $autoTarget = $request->filled('auto_deploy_target_id')
            ? AiwDeployTarget::where('project_id', $project->id)->where('enabled', true)
                ->find($request->integer('auto_deploy_target_id'))
            : null;

        $autoDeploy = $request->boolean('auto_deploy')
            && $request->boolean('use_branch')
            && $autoTarget !== null;

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
            // 체크하면 상한 없이 돈다. 숫자가 비어 있어도 같은 뜻으로 본다.
            'cost_limit_usd'       => $request->boolean('no_cost_limit')
                ? null
                : ($validated['cost_limit_usd'] ?? null),
            // 폼의 hidden 이 "0" 을 보내므로 여기서 그대로 해석한다. 예전에는
            // 값이 없으면 true 로 봤는데, 해제한 체크박스는 아무것도 보내지 않아
            // 브랜치 분리를 끌 수 없었다.
            'use_branch'           => $request->boolean('use_branch'),
            // 자동 배포는 브랜치 분리가 켜져 있어야 성립한다 — 변경이 현재
            // 브랜치에 섞이면 이 작업만 골라 올릴 수 없다.
            'auto_deploy'          => $autoDeploy,
            'auto_deploy_target_id' => $autoDeploy ? $autoTarget?->id : null,
            'created_by'           => $request->user()->id,
        ]);

        // 최초 지시문을 대화의 첫 메시지로 남긴다(화면 4 의 첫 말풍선).
        $first = AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => 0,
            'role'          => 'user',
            'content'       => $job->instruction,
            'user_id'       => $request->user()->id,
            'session_index' => 0,
            'delivered_at'  => now(),   // 지시문은 JobDispatched 로 함께 전달된다
        ]);

        // 첨부는 dispatch 전에 저장해야 담당자가 받는 payload 에 함께 실린다.
        $this->attachments->attach($first, $request->file('images', []), $request->user());

        $sent = $job->parent_job_id
            ? $this->dispatcher->dispatchFollowUp($job)
            : $this->dispatcher->dispatch($job);

        return redirect()
            ->route('projects.ai-works.show', [$project, $job])
            ->with('status', $sent
                ? '지시를 담당자에게 보냈습니다.'
                : '담당자가 오프라인입니다. 접속하면 자동으로 시작됩니다.');
    }

    /** 화면 4: 지시 상세 */
    public function show(Project $project, AiwJob $job): View
    {
        $this->authorize('view', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $job->load(['agent:id,name,capabilities', 'creator:id,name', 'parent:id,title']);

        return view('aiw.jobs.show', [
            'project'  => $project,
            'job'      => $job,
            'messages' => $job->messages()->orderBy('seq')->with(['author:id,name', 'attachments'])->get(),
            'logs'     => $job->logs()->orderBy('seq')->get(),
            // pending 카드는 새로고침해도 유지돼야 한다.
            'pending'  => $job->permissionRequests()->pending()->orderBy('id')->get(),
            'decided'  => $job->permissionRequests()->whereIn('status', ['allowed', 'denied', 'expired'])
                ->with('decider:id,name')->orderBy('id')->get(),
            'canEdit'  => auth()->user()->can('sendMessage', $job),
            // 실패 복구 버튼 중 매핑 수정은 관리자만 할 수 있다.
            'canManageAgents' => auth()->user()->can('manageAgents', AiwJob::class),
            'publishes' => $job->publishes()->with('requester:id,name')->latest('id')->get(),
            'agentName' => AiwAgentProject::where('agent_id', $job->agent_id)
                ->where('project_id', $job->project_id)->first()?->displayName()
                ?? $job->agent?->name,
            // 배포 대상은 관리자가 미리 등록한 것만 고를 수 있다.
            'deployTargets' => AiwDeployTarget::where('project_id', $job->project_id)
                ->where('enabled', true)->orderBy('name')->get(),
            'deploys' => AiwDeploy::where('job_id', $job->id)
                ->with(['target:id,name', 'requester:id,name'])->latest('id')->get(),
            // 같은 담당자가 다른 작업을 붙들고 있으면 이 작업은 줄 서 있다.
            // 화면이 말해 주지 않으면 "보냈는데 아무 일도 없는" 상태로 보인다.
            'blockingJob' => $job->status === AiwJobStatus::Dispatched
                ? AiwJob::where('agent_id', $job->agent_id)
                    ->whereIn('status', $this->activeStatuses())
                    ->where('id', '!=', $job->id)
                    ->first(['id', 'title'])
                : null,
        ]);
    }

    /** 대화형: 사용자 메시지 전송 */
    public function message(Request $request, Project $project, AiwJob $job): RedirectResponse
    {
        $this->authorize('sendMessage', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'content'  => ['required', 'string', 'max:20000'],
            'images'   => ['nullable', 'array', 'max:'.AttachmentService::MAX_PER_MESSAGE],
            'images.*' => ['image', 'max:'.(AttachmentService::MAX_UPLOAD_BYTES / 1024)],
            // 대화 도중에도 "배포까지 자동으로" 를 켜고 끌 수 있다.
            'auto_deploy'           => ['nullable', 'boolean'],
            'auto_deploy_target_id' => ['nullable', 'integer'],
        ]);

        // 화면에서 온 폼 전송이므로 abort() 로 끊지 않는다. 오류 페이지가 뜨면
        // 사용자는 이유도 모르고 입력하던 내용도 잃는다. 상태가 어긋나는 건
        // 예외 상황이 아니라 흔한 일이다 — 화면을 열어 둔 사이 작업이 끝난 경우.
        if ($job->status->isTerminal()) {
            return back()
                ->withInput()
                ->with('error', '이미 '.$job->status->label().'된 작업이라 메시지를 보낼 수 없습니다. 이어서 진행하려면 재전송으로 후속 지시를 만드세요.');
        }

        if ($job->mode !== 'interactive') {
            return back()
                ->withInput()
                ->with('error', '단발 작업에는 메시지를 보낼 수 없습니다. 대화형으로 등록한 지시에서만 가능합니다.');
        }

        // 메시지보다 먼저 반영한다. 담당자가 곧바로 끝내 버려도 이 설정이
        // 제때 적용되도록 — 순서가 뒤집히면 켠 적 없는 것처럼 보인다.
        [$autoNote, $autoWarning] = $this->applyAutoDeploy($request, $project, $job);

        // 번호는 MessageWriter 가 잠금을 잡고 매긴다. 여기서 max+1 을 직접
        // 계산하면 같은 순간에 답하는 담당자와 번호가 겹친다.
        $message = $this->messages->appendOne($job, [
            'role'          => 'user',
            'content'       => $validated['content'],
            'user_id'       => $request->user()->id,
            'session_index' => $job->currentSessionIndex(),
        ]);

        $this->attachments->attach($message, $request->file('images', []), $request->user());

        $this->emit(new JobUserMessage($message->fresh(), $job->agent_id), $job->id);

        $redirect = back()->with('status', trim('메시지를 보냈습니다. '.($autoNote ?? '')));

        return $autoWarning ? $redirect->with('error', $autoWarning) : $redirect;
    }

    /**
     * 대화 도중 "배포까지 자동으로" 를 켜고 끈다.
     *
     * 등록할 때 한 번만 정하게 하면, 결과를 보고 마음이 바뀐 사람은 새 지시를
     * 만드는 수밖에 없다. 대화를 이어 가면서 정할 수 있어야 한다.
     *
     * 폼이 이 값을 아예 보내지 않았으면(배포 대상이 없어 체크박스를 그리지 않은
     * 경우) 손대지 않는다. 보내지 않은 것을 "끔" 으로 읽으면 켜 둔 설정이
     * 조용히 꺼진다.
     *
     * 켜지 못할 때 말없이 무시하지 않는다 — 사용자는 켰다고 믿고 기다리게 된다.
     *
     * @return array{0: ?string, 1: ?string}  [안내 문구, 경고 문구]
     */
    private function applyAutoDeploy(Request $request, Project $project, AiwJob $job): array
    {
        if (! $request->has('auto_deploy')) {
            return [null, null];
        }

        if (! $request->boolean('auto_deploy')) {
            if ($job->auto_deploy) {
                $job->forceFill(['auto_deploy' => false, 'auto_deploy_target_id' => null])->save();

                return ['자동 배포는 껐습니다.', null];
            }

            return [null, null];
        }

        // 브랜치 분리가 없으면 이 작업의 변경만 골라 올릴 수 없다.
        if (! $job->use_branch) {
            return [null, '자동 배포를 켜지 못했습니다 — 이 지시는 브랜치 분리 없이 등록돼'
                .' 이 작업의 변경만 골라 올릴 수 없습니다. 메시지는 전달했습니다.'];
        }

        // 배포 대상도 서버가 확인한다. 다른 프로젝트의 대상을 끼워 넣을 수 없다.
        $target = AiwDeployTarget::where('project_id', $project->id)
            ->where('enabled', true)
            ->find($request->integer('auto_deploy_target_id'));

        if (! $target) {
            return [null, '자동 배포를 켜지 못했습니다 — 배포 대상을 고르지 않았거나'
                .' 사용할 수 없는 대상입니다. 메시지는 전달했습니다.'];
        }

        $job->forceFill(['auto_deploy' => true, 'auto_deploy_target_id' => $target->id])->save();

        return [sprintf('작업이 끝나면 커밋·푸시 후 배포까지 자동으로 진행합니다 (%s).', $target->name), null];
    }

    /**
     * 화면이 놓친 로그·메시지를 따라잡는다.
     *
     * 브로드캐스트는 "빠른 길"이고 이쪽이 "정확한 길"이다. 데몬은 이미 같은
     * 방식으로 재접속마다 누락을 메우는데, 웹 화면에는 그 장치가 없었다.
     *
     * 실제로 이런 일이 있었다: 지시를 등록한 **같은 초에** 작업이 실패했다.
     * 페이지가 그려질 때는 로그가 아직 없었고, 브로드캐스트는 Echo 가 구독하기
     * 전에 지나갔다. 그래서 실패 사유가 화면에 영영 뜨지 않았다 —
     * 사용자에게는 "지시했는데 활동 로그가 안 보인다" 로 보였다.
     */
    public function feed(Request $request, Project $project, AiwJob $job): JsonResponse
    {
        $this->authorize('view', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $afterLog = (int) $request->integer('log_after', -1);
        $afterMsg = (int) $request->integer('message_after', -1);

        $logs = $job->logs()->where('seq', '>', $afterLog)->orderBy('seq')->get();

        $messages = $job->messages()
            ->where('seq', '>', $afterMsg)
            ->with(['author:id,name', 'attachments'])
            ->orderBy('seq')
            ->get();

        return response()->json([
            'status' => $job->status->value,
            // 브로드캐스트와 같은 모양으로 돌려준다. 화면이 한 가지 처리만 알면 된다.
            'logs'     => $logs->map(fn ($l) => (new JobLogAppended($l))->broadcastWith())->values(),
            'messages' => $messages->map(fn ($m) => (new JobMessageAppended($m))->broadcastWith())->values(),
        ]);
    }

    /**
     * 첨부 이미지 열람.
     *
     * 비공개 디스크에 있으므로 URL 로 바로 접근할 수 없다. 이 라우트가 권한을
     * 확인하고 내보낸다 — 프로젝트 멤버만 자기 프로젝트의 첨부를 볼 수 있다.
     */
    public function attachment(Project $project, AiwJob $job, AiwJobAttachment $attachment)
    {
        $this->authorize('view', $job);
        abort_unless((int) $job->project_id === (int) $project->id, 404);
        abort_unless((int) $attachment->job_id === (int) $job->id, 404);
        abort_unless($attachment->exists(), 404);

        return response($attachment->contents(), 200, [
            'Content-Type'        => $attachment->mime,
            'Content-Disposition' => 'inline; filename="'.addslashes($attachment->original_name).'"',
            // 내용이 바뀌지 않는 파일이다. 다만 비공개이므로 공유 캐시는 막는다.
            'Cache-Control'       => 'private, max-age=86400',
        ]);
    }

    /**
     * 결과를 원격 저장소에 올린다.
     *
     * 담당자는 push 를 할 수 없다. 사람이 결과를 확인하고 누른 이 버튼만이
     * 데몬에게 커밋·머지·푸시를 시킨다.
     */
    public function publish(Request $request, Project $project, AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);   // 결과 반영도 편집 권한
        abort_unless((int) $job->project_id === (int) $project->id, 404);

        $validated = $request->validate([
            'commit_message' => ['nullable', 'string', 'max:480'],
        ]);

        try {
            $this->publishes->request($job, $request->user(), $validated['commit_message'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', '담당자에게 커밋·푸시를 요청했습니다. 결과가 화면에 표시됩니다.');
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

    /**
     * 상태가 맞지 않아 실행할 수 없는 화면 동작.
     *
     * abort(409) 로 끊으면 Symfony 기본 오류 페이지가 뜨고 사용자는 이유를 못 본다.
     * 화면을 열어 둔 사이 작업이 끝나는 건 흔한 일이라 예외 취급할 것이 아니다.
     */
    private function conflict(AiwJob $job, string $reason): RedirectResponse
    {
        return back()->with('error', $reason.' (현재 상태: '.$job->status->label().')');
    }

    private function cancel(AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);

        if (! $job->status->isActive() && $job->status !== AiwJobStatus::Dispatched) {
            return $this->conflict($job, '취소할 수 없는 상태입니다');
        }

        $this->emit(new JobCancelRequested($job, 'user cancelled'), $job->id);
        $this->states->transition($job, AiwJobStatus::Cancelled, ['error_message' => '사용자가 취소했습니다.']);

        return back()->with('status', '작업을 취소했습니다.');
    }

    private function end(AiwJob $job): RedirectResponse
    {
        $this->authorize('end', $job);

        if ($job->mode !== 'interactive' || ! $job->status->isActive()) {
            return $this->conflict($job, '종료할 수 없는 상태입니다');
        }

        // 상태는 데몬이 최종 결과를 보고할 때 completed 로 바뀐다. 여기서는 요청만 보낸다.
        $this->emit(new JobEndRequested($job), $job->id);

        return back()->with('status', '세션 종료를 요청했습니다. 데몬이 마지막 턴을 마치면 완료됩니다.');
    }

    private function handover(AiwJob $job): RedirectResponse
    {
        $this->authorize('handover', $job);

        if ($job->mode !== 'interactive'
            || ! in_array($job->status, [AiwJobStatus::Running, AiwJobStatus::WaitingInput], true)) {
            return $this->conflict($job, '컨텍스트 정리를 요청할 수 없는 상태입니다');
        }

        $this->emit(new HandoverRequested($job), $job->id);

        return back()->with('status', '컨텍스트 정리를 요청했습니다. 턴이 끝나는 대로 세션이 교체됩니다.');
    }

    private function redispatch(AiwJob $job): RedirectResponse
    {
        $this->authorize('cancel', $job);   // 재전송도 편집 권한

        // 오래된 화면에서 누르면 여기 걸린다. RuntimeException 이 그대로 나가면 500 이다.
        if (! in_array($job->status, [AiwJobStatus::Queued, AiwJobStatus::Dispatched], true)) {
            return $this->conflict($job, '재전송은 대기·전달 상태에서만 가능합니다');
        }

        $sent = $this->dispatcher->redispatch($job);

        return back()->with('status', $sent ? '지시를 다시 보냈습니다.' : '담당자가 아직 오프라인입니다.');
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
