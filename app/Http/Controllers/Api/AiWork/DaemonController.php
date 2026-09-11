<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 데몬 ↔ 서버 공통 엔드포인트: 하트비트, 브로드캐스트 인가, 대기 job 조회.
 */
class DaemonController extends AgentApiController
{
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
        ]);

        $agent = $this->agent($request);

        $agent->forceFill([
            'last_seen_at' => now(),
            'capabilities' => $validated['capabilities'] ?? $agent->capabilities,
        ])->saveQuietly();

        $jobs = AiwJob::query()
            ->where('agent_id', $agent->id)
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

    /** 실행 대기 중인 job 전체. 데몬 기동·재접속 시 누락을 보충한다. */
    public function pendingJobs(Request $request): JsonResponse
    {
        $agent = $this->agent($request);

        $jobs = AiwJob::query()
            ->where('agent_id', $agent->id)
            ->whereIn('status', [AiwJobStatus::Queued, AiwJobStatus::Dispatched])
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
            ->orderBy('seq')
            ->get(['id', 'seq', 'content']);

        $decided = $job->permissionRequests()
            ->whereIn('status', ['allowed', 'denied', 'expired'])
            ->orderBy('id')
            ->get(['request_key', 'status', 'deny_reason']);

        return response()->json([
            'messages'    => $messages->map(fn ($m) => [
                'message_id' => $m->id,
                'seq'        => (int) $m->seq,
                'content'    => $m->content,
            ])->values(),
            'permissions' => $decided->map(fn ($p) => [
                'request_key' => $p->request_key,
                'status'      => $p->status,
                'deny_reason' => $p->deny_reason,
            ])->values(),
        ] + $this->controlFlags($job));
    }
}
