<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobCancelRequested;
use App\Events\AiWork\JobEndRequested;
use App\Events\AiWork\JobUserMessage;
use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\AiWork\AiwPublish;
use App\Models\Project;
use App\Services\AiWork\AiwNotifier;
use App\Services\AiWork\JobCreator;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\MessageWriter;
use App\Services\AiWork\PermissionService;
use App\Services\AiWork\ToolPolicy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * 모바일 앱의 작업 지시(AI Works) API. Base URL: /api/mobile
 *
 * 웹 화면(AiWork\AiwJobController)과 같은 서비스를 부른다 — 지시는 같은 모양으로
 * 작업 PC 에 전달되고, 회신·승인·종료도 같은 경로를 탄다. 다른 것은 두 가지다.
 *
 *   1. 등록 항목을 줄였다. 모바일 지시는 대화형 + acceptEdits + 기본 툴로 고정한다.
 *      기본 툴은 모두 자동 승인 목록에 있어 승인 요청 없이 끝까지 돈다.
 *   2. 실시간 스트림이 없다. 사람이 필요한 순간은 푸시(AiwNotifier)로 알리고, 화면은
 *      열 때와 당겨서 새로고침할 때 이 API 를 다시 읽는다.
 *
 * 권한은 AiwJobPolicy(시스템 관리자 전용)를 그대로 쓴다. 모바일 토큰 미들웨어는
 * auth() 가드를 채우지 않으므로 $this->authorize() 대신 Gate::forUser() 로 부른다
 * — authorize() 를 쓰면 사용자가 없는 것으로 판정돼 늘 403 이다.
 */
class AiwJobController extends Controller
{
    /** 사람의 답을 기다리는 상태. */
    private const ATTENTION = ['waiting_input', 'waiting_permission'];

    /** 끝나지 않은 상태. */
    private const OPEN = ['queued', 'dispatched', 'running', 'waiting_input', 'waiting_permission', 'handover'];

    /** 담당자가 세션을 붙들고 있는 상태(같은 폴더의 다음 지시가 줄 선다). */
    private const ACTIVE = ['running', 'waiting_input', 'waiting_permission', 'handover'];

    public function __construct(
        private JobCreator $creator,
        private JobStateMachine $states,
        private PermissionService $permissions,
        private MessageWriter $messages,
    ) {}

    /** 작업 PC 가 매핑된 프로젝트. 메뉴의 첫 화면이다. */
    public function projects(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $mappings = AiwAgentProject::with(['project:id,name', 'agent'])->get()
            ->filter(fn (AiwAgentProject $m) => $m->project !== null && $m->agent !== null)
            ->groupBy('project_id');

        $counts = AiwJob::query()
            ->whereIn('project_id', $mappings->keys())
            ->whereIn('status', self::OPEN)
            ->toBase()
            ->selectRaw('project_id, status, count(*) as c')
            ->groupBy('project_id', 'status')
            ->get()
            ->groupBy('project_id');

        $data = $mappings->map(function (Collection $rows, $projectId) use ($counts) {
            $byStatus = ($counts[$projectId] ?? collect())->pluck('c', 'status');

            return [
                'id'              => (int) $projectId,
                'name'            => $rows->first()->project->name,
                'agents'          => $rows->map(fn (AiwAgentProject $m) => [
                    'id'     => $m->agent_id,
                    'name'   => $m->displayName(),
                    'online' => $m->is_online,
                ])->values(),
                'online'          => $rows->contains(fn (AiwAgentProject $m) => $m->is_online),
                'open_count'      => (int) $byStatus->sum(),
                'attention_count' => (int) collect(self::ATTENTION)->sum(fn ($s) => (int) ($byStatus[$s] ?? 0)),
            ];
        })->sortBy('name')->values();

        return response()->json(['data' => $data]);
    }

    /** 프로젝트를 가로질러 사람의 답을 기다리는 작업. */
    public function attention(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $jobs = $this->listQuery()
            ->whereIn('status', self::ATTENTION)
            ->with('project:id,name')
            ->limit(50)
            ->get();

        $names = $this->agentNames($jobs);

        return response()->json([
            'data' => $jobs->map(fn (AiwJob $j) => $this->summary($j, $names))->values(),
        ]);
    }

    /** 등록 화면에 필요한 것: 이 프로젝트의 작업 PC, 배포 대상, 고정 설정. */
    public function options(Request $request, Project $project): JsonResponse
    {
        $this->can($request, 'create', [AiwJob::class, $project]);

        $mappings = AiwAgentProject::where('project_id', $project->id)->with('agent')->get()
            ->filter(fn (AiwAgentProject $m) => $m->agent !== null);

        // 같은 작업 PC 가 이 프로젝트에서 붙들고 있는 작업. 새 지시는 그 뒤에 줄 선다.
        $busy = AiwJob::query()
            ->where('project_id', $project->id)
            ->whereIn('agent_id', $mappings->pluck('agent_id'))
            ->whereIn('status', self::ACTIVE)
            ->latest('id')
            ->get(['id', 'title', 'status', 'agent_id'])
            ->unique('agent_id')
            ->keyBy('agent_id');

        return response()->json([
            'project'        => ['id' => $project->id, 'name' => $project->name],
            'agents'         => $mappings->map(fn (AiwAgentProject $m) => [
                'id'       => $m->agent_id,
                'name'     => $m->displayName(),
                'online'   => $m->is_online,
                'ready'    => $m->isReady(),
                'busy_job' => isset($busy[$m->agent_id]) ? [
                    'id'           => $busy[$m->agent_id]->id,
                    'title'        => $busy[$m->agent_id]->title,
                    'status_label' => $busy[$m->agent_id]->status->label(),
                ] : null,
            ])->values(),
            'deploy_targets' => AiwDeployTarget::where('project_id', $project->id)
                ->where('enabled', true)->orderBy('name')->get(['id', 'name'])
                ->map(fn (AiwDeployTarget $t) => ['id' => $t->id, 'name' => $t->name])
                ->values(),
            'fixed'          => [
                'mode'            => 'interactive',
                'permission_mode' => 'acceptEdits',
                'allowed_tools'   => ToolPolicy::defaults(),
            ],
            'default_cost_limit_usd' => (float) config('aiw.default_cost_limit_usd', 2.0),
        ]);
    }

    /** filter: all(기본) | open | attention | done */
    public function index(Request $request, Project $project): JsonResponse
    {
        $this->can($request, 'viewAny', [AiwJob::class, $project]);

        $filter = (string) $request->query('filter', 'all');

        $paginator = $this->listQuery()
            ->where('project_id', $project->id)
            ->when($filter === 'open', fn ($q) => $q->whereIn('status', self::OPEN))
            ->when($filter === 'attention', fn ($q) => $q->whereIn('status', self::ATTENTION))
            ->when($filter === 'done', fn ($q) => $q->whereIn('status', ['completed', 'failed', 'cancelled']))
            ->paginate(20);

        $names = $this->agentNames($paginator->getCollection());

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (AiwJob $j) => $this->summary($j, $names))->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $this->can($request, 'create', [AiwJob::class, $project]);

        $validated = $request->validate([
            'title'                 => ['required', 'string', 'max:191'],
            'agent_id'              => ['required', 'integer'],
            'instruction'           => ['required', 'string', 'max:20000'],
            'use_branch'            => ['nullable', 'boolean'],
            'no_cost_limit'         => ['nullable', 'boolean'],
            'auto_deploy'           => ['nullable', 'boolean'],
            'auto_deploy_target_id' => ['nullable', 'integer'],
            'parent_job_id'         => ['nullable', 'integer'],
        ]);

        // 후속 지시는 같은 프로젝트의 작업만 이어받는다.
        $parentId = ! empty($validated['parent_job_id'])
            ? AiwJob::where('project_id', $project->id)->findOrFail($validated['parent_job_id'])->id
            : null;

        try {
            [$job, $sent] = $this->creator->create($project, $request->user(), [
                'title'                 => $validated['title'],
                'agent_id'              => (int) $validated['agent_id'],
                'instruction'           => $validated['instruction'],
                // 모바일 지시는 이 조합으로 고정한다. 기본 툴은 모두 자동 승인 목록에
                // 있어(config/aiw.php) 승인 요청 없이 끝까지 돈다. 앱이 무엇을 보내오든
                // 이 값을 쓴다.
                'mode'                  => 'interactive',
                'model'                 => null,
                'allowed_tools'         => ToolPolicy::defaults(),
                'permission_mode'       => 'acceptEdits',
                'cost_limit_usd'        => $request->boolean('no_cost_limit')
                    ? null
                    : (float) config('aiw.default_cost_limit_usd', 2.0),
                // 안전한 기본값은 켬이다(웹 등록 화면과 같다).
                'use_branch'            => $request->has('use_branch') ? $request->boolean('use_branch') : true,
                'parent_job_id'         => $parentId,
                'auto_deploy'           => $request->boolean('auto_deploy'),
                'auto_deploy_target_id' => $validated['auto_deploy_target_id'] ?? null,
            ]);
        } catch (ModelNotFoundException $e) {
            // 이 프로젝트를 맡지 않은 작업 PC. RuntimeException 의 자식이라 아래에
            // 잡히지 않게 먼저 올려 보낸다(404).
            throw $e;
        } catch (\RuntimeException $e) {
            // 매핑이 사라진 경우처럼 지금 보낼 수 없는 상태. 이유를 그대로 보여 준다.
            return $this->fail($e->getMessage());
        }

        $notices = [];

        if (! $sent) {
            $notices[] = '작업 PC 가 오프라인입니다. 켜지면 자동으로 시작됩니다.';
        }

        // 켜 달라고 했는데 켜지 못했으면 말한다. 켰다고 믿고 기다리게 두지 않는다.
        if ($request->boolean('auto_deploy') && ! $job->auto_deploy) {
            $notices[] = '자동 배포는 켜지 않았습니다 — 브랜치 분리를 켜고 이 프로젝트의 배포 대상을 골라야 합니다.';
        }

        return response()->json($this->detail($request, $job) + [
            'sent'   => $sent,
            'notice' => $notices === [] ? null : implode(' ', $notices),
        ], 201);
    }

    public function show(Request $request, Project $project, AiwJob $job): JsonResponse
    {
        $this->can($request, 'view', $job);
        $this->sameProject($project, $job);

        return response()->json($this->detail($request, $job));
    }

    /** 담당자에게 답한다. 선택지 버튼도 그 문구를 이 경로로 보낸다(웹과 같다). */
    public function message(Request $request, Project $project, AiwJob $job): JsonResponse
    {
        $this->can($request, 'sendMessage', $job);
        $this->sameProject($project, $job);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:20000'],
        ]);

        // 화면을 열어 둔 사이 작업이 끝나는 건 흔한 일이다. 예외가 아니라 안내로 돌려준다.
        if ($job->status->isTerminal()) {
            return $this->fail('이미 끝난 작업입니다(현재 상태: '.$job->status->label().'). 이어서 진행하려면 후속 지시를 만드세요.');
        }

        if ($job->mode !== 'interactive') {
            return $this->fail('단발 작업에는 메시지를 보낼 수 없습니다. 대화형으로 등록한 지시에서만 가능합니다.');
        }

        // 번호는 MessageWriter 가 잠금을 잡고 매긴다(같은 순간에 답하는 담당자와 겹치지 않게).
        $message = $this->messages->appendOne($job, [
            'role'          => 'user',
            'content'       => $validated['content'],
            'user_id'       => $request->user()->id,
            'session_index' => $job->currentSessionIndex(),
        ]);

        $this->emit(new JobUserMessage($message->fresh(), $job->agent_id), $job->id);

        return response()->json($this->detail($request, $job->fresh()));
    }

    /** 승인 요청 허용/거부. 결정은 먼저 누른 쪽 하나만 반영된다(웹과 동시에 눌러도 안전). */
    public function decide(Request $request, Project $project, AiwJob $job, AiwPermissionRequest $permission): JsonResponse
    {
        $this->can($request, 'decidePermission', $job);
        $this->sameProject($project, $job);
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

        return response()->json($this->detail($request, $job->fresh()) + [
            'notice' => $changed ? null : '이미 결정된 요청입니다.',
        ]);
    }

    /** cancel | end — 웹의 같은 버튼과 조건·동작이 같다. */
    public function action(Request $request, Project $project, AiwJob $job, string $action): JsonResponse
    {
        $this->sameProject($project, $job);

        if ($action === 'cancel') {
            $this->can($request, 'cancel', $job);

            if (! $job->status->isActive() && $job->status !== AiwJobStatus::Dispatched) {
                return $this->fail('취소할 수 없는 상태입니다(현재 상태: '.$job->status->label().').');
            }

            $this->emit(new JobCancelRequested($job, 'user cancelled'), $job->id);
            $this->states->transition($job, AiwJobStatus::Cancelled, ['error_message' => '사용자가 취소했습니다.']);
        } else {
            $this->can($request, 'end', $job);

            if ($job->mode !== 'interactive' || ! $job->status->isActive()) {
                return $this->fail('종료할 수 없는 상태입니다(현재 상태: '.$job->status->label().').');
            }

            // 상태는 데몬이 마지막 턴을 마치고 보고할 때 completed 로 바뀐다. 여기서는 요청만 보낸다.
            $this->emit(new JobEndRequested($job), $job->id);
        }

        return response()->json($this->detail($request, $job->fresh()));
    }

    // ── 응답 모양 ───────────────────────────────────────────────────────────

    private function listQuery()
    {
        return AiwJob::query()
            ->with(['agent:id,name', 'creator:id,name'])
            ->withCount(['permissionRequests as pending_permissions_count' => fn ($q) => $q->pending()])
            ->latest('id');
    }

    /** @param  array<string, string>  $agentNames  "agentId-projectId" => 이 프로젝트에서 부르는 이름 */
    private function summary(AiwJob $job, array $agentNames): array
    {
        return [
            'id'                        => $job->id,
            'project_id'                => $job->project_id,
            'project_name'              => $job->relationLoaded('project') ? $job->project?->name : null,
            'title'                     => $job->title,
            'status'                    => $job->status->value,
            'status_label'              => $job->status->label(),
            'mode'                      => $job->mode,
            'is_terminal'               => $job->status->isTerminal(),
            'needs_action'              => in_array($job->status->value, self::ATTENTION, true),
            'agent_name'                => $agentNames[$job->agent_id.'-'.$job->project_id] ?? $job->agent?->name,
            'creator_name'              => $job->creator?->name,
            'auto_deploy'               => (bool) $job->auto_deploy,
            'pending_permissions_count' => (int) ($job->pending_permissions_count ?? 0),
            'error_message'             => $job->status === AiwJobStatus::Failed
                ? Str::limit((string) $job->error_message, 160)
                : null,
            'created_at'                => $job->created_at?->toIso8601String(),
            'finished_at'               => $job->finished_at?->toIso8601String(),
        ];
    }

    private function detail(Request $request, AiwJob $job): array
    {
        $job->load(['agent:id,name,capabilities', 'creator:id,name', 'parent:id,title', 'autoDeployTarget:id,name']);
        $job->loadCount(['permissionRequests as pending_permissions_count' => fn ($q) => $q->pending()]);

        $messages = $job->messages()->orderBy('seq')
            ->with('author:id,name')
            ->withCount('attachments')
            ->get();

        // 선택지는 마지막 질문에만 띄운다(지난 질문의 버튼은 이미 답한 것이다 — 웹과 같은 규칙).
        $latestChoiceId = $messages->last(fn (AiwJobMessage $m) => ! empty($m->choices))?->id;

        $gate = Gate::forUser($request->user());
        $interactiveOpen = $job->mode === 'interactive' && ! $job->status->isTerminal();

        return $this->summary($job, $this->agentNames(collect([$job]))) + [
            'instruction'         => $job->instruction,
            'result_summary'      => $job->result_summary,
            'result_summary_html' => $this->markdown($job->result_summary),
            'changed_files'       => array_values((array) ($job->changed_files ?? [])),
            'error_message'       => $job->error_message,
            'failure_label'       => $job->failureCode()?->label(),
            'use_branch'          => (bool) $job->use_branch,
            'branch'              => $job->branchName(),
            'auto_deploy_target'  => $job->autoDeployTarget?->name,
            'parent'              => $job->parent ? ['id' => $job->parent->id, 'title' => $job->parent->title] : null,
            'cost'                => [
                'label'     => $job->agent?->costLabel() ?? '예상 사용량',
                'usd'       => (float) $job->cost_usd,
                'limit_usd' => $job->cost_limit_usd !== null ? (float) $job->cost_limit_usd : null,
            ],
            'messages'            => $messages->map(fn (AiwJobMessage $m) => [
                'id'                => $m->id,
                'seq'               => (int) $m->seq,
                'role'              => $m->role,
                'content'           => $m->content,
                // 사람이 쓴 글은 그대로, 담당자·데몬이 쓴 것은 마크다운을 렌더해 준다.
                'html'              => $m->role === 'user' ? null : $this->markdown($m->content),
                'choices'           => $m->id === $latestChoiceId && ! $job->status->isTerminal()
                    ? array_values($m->choices ?? [])
                    : [],
                'author'            => $m->role === 'user' ? ($m->author?->name ?? '나') : null,
                'pending_delivery'  => $m->isPendingDelivery(),
                'attachments_count' => (int) $m->attachments_count,
                'created_at'        => $m->created_at?->toIso8601String(),
            ])->values(),
            'pending_permissions' => $job->permissionRequests()->pending()->orderBy('id')->get()
                ->map(fn (AiwPermissionRequest $p) => $this->permissionResource($p))->values(),
            'decided_permissions' => $job->permissionRequests()
                ->whereIn('status', ['allowed', 'denied', 'expired'])
                ->with('decider:id,name')->orderByDesc('id')->limit(10)->get()
                ->map(fn (AiwPermissionRequest $p) => $this->permissionResource($p))->values(),
            'publishes'           => $job->publishes()->latest('id')->limit(3)->get()
                ->map(fn (AiwPublish $p) => [
                    'id'            => $p->id,
                    'status'        => $p->status,
                    'status_label'  => $p->statusLabel(),
                    'automatic'     => (bool) $p->automatic,
                    'commit_sha'    => $p->commit_sha,
                    'target_branch' => $p->target_branch,
                    'finished_at'   => $p->finished_at?->toIso8601String(),
                ])->values(),
            'deploys'             => AiwDeploy::where('job_id', $job->id)->with('target:id,name')
                ->latest('id')->limit(3)->get()
                ->map(fn (AiwDeploy $d) => [
                    'id'           => $d->id,
                    'status'       => $d->status,
                    'status_label' => $d->statusLabel(),
                    'automatic'    => (bool) $d->automatic,
                    'target'       => $d->target?->name,
                    'exit_code'    => $d->exit_code,
                    // 실패 이유는 대개 끝에 있다.
                    'output_tail'  => $d->output ? Str::substr($d->output, -1500) : null,
                    'finished_at'  => $d->finished_at?->toIso8601String(),
                ])->values(),
            // 같은 작업 PC 가 다른 작업을 붙들고 있으면 이 작업은 줄 서 있다. 말해 주지
            // 않으면 "보냈는데 아무 일도 없는" 상태로 보인다.
            'blocking_job'        => $job->status === AiwJobStatus::Dispatched
                ? AiwJob::where('agent_id', $job->agent_id)
                    ->whereIn('status', self::ACTIVE)
                    ->whereKeyNot($job->id)
                    ->first(['id', 'title'])?->only(['id', 'title'])
                : null,
            'can'                 => [
                'message' => $interactiveOpen && $gate->allows('sendMessage', $job),
                'end'     => $job->mode === 'interactive' && $job->status->isActive() && $gate->allows('end', $job),
                'cancel'  => ($job->status->isActive() || $job->status === AiwJobStatus::Dispatched)
                    && $gate->allows('cancel', $job),
                'decide'  => $gate->allows('decidePermission', $job),
            ],
        ];
    }

    private function permissionResource(AiwPermissionRequest $p): array
    {
        return [
            'id'          => $p->id,
            'tool_name'   => $p->tool_name,
            'summary'     => AiwNotifier::describeToolInput((array) $p->tool_input),
            'input'       => Str::limit(
                json_encode($p->tool_input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '',
                4000,
            ),
            'status'      => $p->status,
            'decider'     => $p->relationLoaded('decider') ? $p->decider?->name : null,
            'deny_reason' => $p->deny_reason,
            'created_at'  => $p->created_at?->toIso8601String(),
            // 이 시각이 지나면 스케줄러가 거부로 만료시킨다.
            'expires_at'  => $p->isPending()
                ? $p->created_at?->copy()->addMinutes((int) config('aiw.permission_timeout_min', 30))->toIso8601String()
                : null,
        ];
    }

    // ── 내부 ────────────────────────────────────────────────────────────────

    /** @return array<string, string> "agentId-projectId" => 이 프로젝트에서 부르는 이름 */
    private function agentNames(Collection $jobs): array
    {
        if ($jobs->isEmpty()) {
            return [];
        }

        return AiwAgentProject::query()
            ->whereIn('agent_id', $jobs->pluck('agent_id')->unique())
            ->whereIn('project_id', $jobs->pluck('project_id')->unique())
            ->with('agent:id,name')
            ->get()
            ->mapWithKeys(fn (AiwAgentProject $m) => [$m->agent_id.'-'.$m->project_id => $m->displayName()])
            ->all();
    }

    private function can(Request $request, string $ability, mixed $arguments): void
    {
        Gate::forUser($request->user())->authorize($ability, $arguments);
    }

    /**
     * 프로젝트를 가로지르는 목록. 정책의 능력들은 프로젝트나 job 을 인자로 받으므로
     * 여기서는 같은 기준(시스템 관리자)을 직접 확인한다 — AiwJobPolicy 참조.
     */
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, '작업 지시는 관리자만 사용할 수 있습니다.');
    }

    private function sameProject(Project $project, AiwJob $job): void
    {
        abort_unless((int) $job->project_id === (int) $project->id, 404);
    }

    private function fail(string $message): JsonResponse
    {
        return response()->json(['error' => $message], 422);
    }

    /** 담당자가 만든 마크다운은 신뢰하지 않는다. HTML 입력은 이스케이프하고 위험한 링크는 버린다. */
    private function markdown(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        return Str::markdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    private function emit(object $event, int $jobId): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            // 브로드캐스트 실패로 사용자 동작을 실패시키지 않는다. 데몬은 /inbox 로 따라잡는다.
            Log::warning('AI Works(모바일): 브로드캐스트 실패(요청은 저장됨)', [
                'job_id' => $jobId,
                'event'  => $event::class,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
