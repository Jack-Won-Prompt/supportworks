<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Enums\AiWork\AiwSetupStatus;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 데몬 ↔ 서버 공통 엔드포인트: 하트비트, 브로드캐스트 인가, 대기 job 조회.
 */
class DaemonController extends AgentApiController
{
    /**
     * 이 PC 가 맡은 프로젝트 목록.
     *
     * 셋업 데몬이 이걸 보고 프로젝트마다 프로세스를 띄운다. 프로젝트가 늘어도
     * 사람이 PC 에 와서 설정 파일을 만들 필요가 없다 — 매핑이 곧 목록이다.
     *
     * 토큰을 새로 내주지 않는다. PC 당 토큰 하나로 모든 프로젝트를 맡고,
     * 프로세스만 나눈다. 프로젝트마다 토큰을 만들면 그 토큰을 PC 에 전달할
     * 방법이 필요해지고, 그 경로가 곧 자격증명 유출 통로가 된다.
     */
    public function mappings(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        $mappings = AiwAgentProject::query()
            ->where('agent_id', $agent->id)
            ->with('project:id,name')
            ->orderBy('project_id')
            ->get();

        return response()->json([
            'agent_id' => $agent->id,
            'mappings' => $mappings->map(fn (AiwAgentProject $m) => [
                'project_id'     => (int) $m->project_id,
                'project_name'   => $m->project?->name,
                'display_name'   => $m->displayName(),
                'local_path'     => $m->local_path,
                'default_branch' => $m->default_branch,
                'setup_status'   => $m->setup_status?->value,
            ])->values(),
        ]);
    }

    /**
     * 셋업 점검 결과 보고.
     *
     * 담당자 PC 만 알 수 있는 것들이다 — 폴더가 있는지, git 저장소인지, 매핑에
     * 적은 브랜치가 실제로 있는지, 작업트리가 깨끗한지. 지금까지는 지시를 넣어
     * 봐야 드러났고 화면에는 이유 없이 멈춘 것처럼 보였다.
     */
    public function reportSetup(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        $validated = $request->validate([
            'project_id' => ['required', 'integer'],
            'status'     => ['required', Rule::enum(AiwSetupStatus::class)],
            'message'    => ['nullable', 'string', 'max:2000'],
        ]);

        $mapping = AiwAgentProject::where('agent_id', $agent->id)
            ->where('project_id', $validated['project_id'])
            ->firstOrFail();

        $mapping->forceFill([
            'setup_status'     => $validated['status'],
            'setup_message'    => $validated['message'] ?? null,
            'setup_checked_at' => now(),
        ])->saveQuietly();

        return response()->json([
            'project_id' => (int) $mapping->project_id,
            'status'     => $mapping->setup_status->value,
            'ready'      => $mapping->setup_status->isReady(),
        ]);
    }

    /**
     * 담당자 PC 가 실행한 배포의 결과 보고.
     *
     * 운영 서버가 이 서버와 다른 프로젝트는 담당자 PC 가 배포한다. 그 PC 는
     * 각 서버 접속 키를 이미 들고 있어, 이 서버가 남의 운영 서버 키를 갖지
     * 않아도 된다.
     *
     * 자기 프로젝트의 배포만 건드릴 수 있다 — 담당자는 매핑된 프로젝트 밖의
     * 기록을 고칠 수 없어야 한다.
     */
    public function deployResult(Request $request, AiwDeploy $deploy): JsonResponse
    {
        $agent = $this->agent($request);

        $mine = AiwAgentProject::where('agent_id', $agent->id)
            ->where('project_id', $deploy->target?->project_id)
            ->exists();

        abort_unless($mine, 404);

        $validated = $request->validate([
            'status'    => ['required', 'in:running,succeeded,failed'],
            'exit_code' => ['nullable', 'integer'],
            'output'    => ['nullable', 'string'],
        ]);

        // 이미 끝난 건은 덮지 않는다. 재전송이 결과를 바꾸면 안 된다.
        if (in_array($deploy->status, ['succeeded', 'failed'], true)) {
            return response()->json(['status' => $deploy->status]);
        }

        $deploy->forceFill(array_filter([
            'status'      => $validated['status'],
            'exit_code'   => $validated['exit_code'] ?? null,
            'output'      => AiwDeploy::truncateOutput($validated['output'] ?? ''),
            'started_at'  => $deploy->started_at ?? now(),
            'finished_at' => $validated['status'] === 'running' ? null : now(),
        ], fn ($v) => $v !== null))->save();

        return response()->json(['status' => $deploy->status]);
    }

    /**
     * 하트비트. last_seen_at 과 capabilities 를 갱신하고, 데몬이 놓쳤을 수 있는
     * 작업 목록을 함께 돌려준다(Reverb 유실 대비 폴백의 1차 관문).
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'capabilities'                     => ['nullable', 'array'],
            'capabilities.max_parallel_jobs'   => ['nullable', 'integer', 'min:1', 'max:16'],
            'capabilities.auth_mode'           => ['nullable', 'in:api_key,subscription'],
            // 프로젝트마다 프로세스를 나눠 띄운 경우, 자기가 맡은 프로젝트를 밝힌다.
            'project_id'                       => ['nullable', 'integer'],
        ]);

        $agent = $this->agent($request);

        $agent->forceFill([
            'last_seen_at' => now(),
            'capabilities' => $validated['capabilities'] ?? $agent->capabilities,
        ])->saveQuietly();

        // 매핑 단위로도 남긴다. 프로젝트 하나만 멈췄을 때 화면이 그걸 말할 수 있어야 한다.
        $projectId = $validated['project_id'] ?? null;

        if ($projectId !== null) {
            AiwAgentProject::where('agent_id', $agent->id)
                ->where('project_id', $projectId)
                ->update(['last_seen_at' => now()]);
        }

        $jobs = AiwJob::query()
            ->where('agent_id', $agent->id)
            // 프로세스를 나눴으면 남의 프로젝트 일감을 집어가면 안 된다.
            ->when($projectId !== null, fn ($q) => $q->where('project_id', $projectId))
            ->whereIn('status', [
                AiwJobStatus::Queued, AiwJobStatus::Dispatched, AiwJobStatus::Running,
                AiwJobStatus::WaitingInput, AiwJobStatus::WaitingPermission, AiwJobStatus::Handover,
            ])
            ->get(['id', 'status']);

        return response()->json([
            // 데몬은 토큰으로만 자신을 아는데 채널 구독에는 id 가 필요하다.
            // 사람이 화면에서 번호를 복사해 .env 에 넣게 하지 않고 여기서 알려준다.
            'agent_id'        => $agent->id,
            'server_time'     => now()->toIso8601String(),
            'pending_job_ids' => $jobs->whereIn('status', [AiwJobStatus::Queued, AiwJobStatus::Dispatched])
                ->pluck('id')->values(),
            'active_job_ids'  => $jobs->filter(fn ($j) => $j->status->isActive())
                ->pluck('id')->values(),
        ]);
    }

    /**
     * 데몬 채널 인가 서명.
     *
     * 기본 /broadcasting/auth 는 web guard 기준이라 데몬이 쓸 수 없고, 기본
     * 커넥션(pusher) secret 으로 서명해 Reverb 가 거부한다. 그래서 여기서
     * reverb secret 으로 직접 HMAC 을 만든다. 채널명이 자신의 것일 때만 발급한다.
     */
    public function broadcastingAuth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'channel_name' => ['required', 'string'],
            'socket_id'    => ['required', 'string'],
        ]);

        $agent = $this->agent($request);
        $expected = 'private-aiw.agent.'.$agent->id;

        abort_unless($validated['channel_name'] === $expected, 403, '허용되지 않은 채널입니다.');

        $reverb = config('broadcasting.connections.reverb');

        abort_if(
            blank($reverb['key'] ?? null) || blank($reverb['secret'] ?? null),
            503,
            'Reverb 가 설정되지 않았습니다.'
        );

        $signature = hash_hmac(
            'sha256',
            $validated['socket_id'].':'.$validated['channel_name'],
            $reverb['secret']
        );

        return response()->json(['auth' => $reverb['key'].':'.$signature]);
    }

    /**
     * 첨부 이미지 원본. 담당자가 프롬프트에 넣기 위해 내려받는다.
     *
     * 이벤트에 base64 로 실어 보내지 않는 이유: Reverb 메시지가 수 MB 로 불어나고,
     * 재전송·폴링마다 같은 양이 다시 흐른다. 담당자가 필요할 때 한 번만 가져간다.
     */
    public function attachment(Request $request, AiwJob $job, AiwJobAttachment $attachment)
    {
        $job = $this->ownedJob($request, $job);

        abort_unless((int) $attachment->job_id === (int) $job->id, 404);
        abort_unless($attachment->exists(), 404);

        return response($attachment->contents(), 200, [
            'Content-Type'   => $attachment->mime,
            'Content-Length' => (string) $attachment->bytes,
        ]);
    }

    /**
     * 실행 대기 중인 job 전체. 데몬 기동·재접속 시 누락을 보충한다.
     *
     * `?resume=1` 이면 활성 job 도 함께 내려준다. 데몬이 재기동하면 세션은
     * 사라지는데 서버 상태는 running 그대로라, 스펙을 받지 못하면 그 job 은
     * 아무도 돌보지 않는 고아가 된다. 스펙에는 resume_session_id 가 실려 있어
     * 데몬이 이어붙이기를 시도할 수 있다.
     */
    public function pendingJobs(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        $statuses = [AiwJobStatus::Queued, AiwJobStatus::Dispatched];

        if ($request->boolean('resume')) {
            $statuses = array_merge($statuses, array_filter(
                AiwJobStatus::cases(),
                fn (AiwJobStatus $s) => $s->isActive(),
            ));
        }

        $jobs = AiwJob::query()
            ->where('agent_id', $agent->id)
            // 프로젝트별로 프로세스를 나눈 경우. 걸러 주지 않으면 셋이 같은 일감을
            // 동시에 집어가 같은 폴더에서 git 이 부딪힌다.
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->integer('project_id')))
            ->whereIn('status', $statuses)
            ->orderBy('id')
            ->get();

        // local_path 는 job 이 아니라 매핑 테이블에 있다. N+1 을 피해 한 번에 읽는다.
        $mappings = AiwAgentProject::query()
            ->where('agent_id', $agent->id)
            ->whereIn('project_id', $jobs->pluck('project_id')->unique())
            ->get()
            ->keyBy('project_id');

        return response()->json([
            'jobs' => $jobs->map(function (AiwJob $job) use ($mappings) {
                $map = $mappings->get($job->project_id);

                return [
                    'job_id'               => $job->id,
                    'project_id'           => $job->project_id,
                    'local_path'           => $map?->local_path,
                    'default_branch'       => $map?->default_branch,
                    'use_branch'           => (bool) $job->use_branch,
                    'mode'                 => $job->mode,
                    'model'                => $job->model,
                    'instruction'          => $job->instruction,
                    'allowed_tools'        => $job->allowed_tools,
                    'permission_mode'      => $job->permission_mode,
                    'context_limit_tokens' => (int) $job->context_limit_tokens,
                    'cost_limit_usd'       => (float) $job->cost_limit_usd,
                    'resume_session_id'    => $job->currentSessionId(),
                    'status'               => $job->status->value,
                    // 최초 지시문에 붙은 이미지. 담당자가 id 로 내려받는다.
                    'attachments'          => $job->attachments()
                        ->whereHas('message', fn ($q) => $q->where('seq', 0))
                        ->get(['id', 'mime', 'original_name'])
                        ->map(fn ($a) => ['id' => $a->id, 'mime' => $a->mime, 'name' => $a->original_name])
                        ->values(),
                ];
            })->values(),
        ]);
    }

    /**
     * Reverb 누락 대비 폴백. 아직 데몬에 전달되지 않은 사용자 메시지와
     * 결정이 끝난 승인 요청을 함께 돌려준다.
     */
    public function inbox(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $messages = $job->messages()
            ->undelivered()
            ->with('attachments:id,message_id,mime')
            ->orderBy('seq')
            ->get(['id', 'seq', 'content']);

        $decided = $job->permissionRequests()
            ->whereIn('status', ['allowed', 'denied', 'expired'])
            ->orderBy('id')
            ->get(['request_key', 'status', 'deny_reason']);

        return response()->json([
            'messages'    => $messages->map(fn ($m) => [
                'message_id'  => $m->id,
                'seq'         => (int) $m->seq,
                'content'     => $m->content,
                'attachments' => $m->attachments
                    ->map(fn ($a) => ['id' => $a->id, 'mime' => $a->mime, 'name' => $a->original_name])
                    ->values(),
            ])->values(),
            'permissions' => $decided->map(fn ($p) => [
                'request_key' => $p->request_key,
                'status'      => $p->status,
                'deny_reason' => $p->deny_reason,
            ])->values(),
        ] + $this->controlFlags($job));
    }
}
