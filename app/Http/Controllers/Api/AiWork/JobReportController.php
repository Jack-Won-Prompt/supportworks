<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobLogAppended;
use App\Events\AiWork\JobMessageAppended;
use App\Events\AiWork\JobStatusChanged;
use App\Events\AiWork\PermissionRequested;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobLog;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 데몬이 실행 상황을 보고하는 엔드포인트들.
 *
 * 상태 전이는 아직 여기서 직접 한다. Phase 4 의 JobStateMachine 이 들어오면
 * 전이 부분만 그쪽으로 옮기고 이 컨트롤러는 입출력만 담당하게 된다.
 */
class JobReportController extends AgentApiController
{
    /** 세션 시작 보고. session_chain 에 새 세션을 push 하고 running 으로 만든다. */
    public function start(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'session_id' => ['required', 'string', 'max:191'],
            'resumed'    => ['nullable', 'boolean'],
        ]);

        $chain = $job->session_chain ?? [];
        $chain[] = [
            'session_id' => $validated['session_id'],
            'started_at' => now()->toIso8601String(),
            'ended_at'   => null,
            'reason'     => null,
        ];

        $job->forceFill([
            'session_chain' => $chain,
            'status'        => AiwJobStatus::Running,
            'started_at'    => $job->started_at ?? now(),
        ])->save();

        // 복구된 세션은 툴 호출을 처음부터 다시 시도하므로 새 request_key 가 생긴다.
        // 옛 pending 을 남겨두면 사용자가 무효한 카드를 누르게 된다.
        if ($request->boolean('resumed')) {
            $job->permissionRequests()->pending()->update([
                'status'     => 'expired',
                'decided_at' => now(),
            ]);
        }

        $this->emit(new JobStatusChanged($job));

        return response()->json($this->controlFlags($job));
    }

    /**
     * 진행 로그 배치. seq 중복은 무시한다 — 데몬이 재전송해도 로그가 늘지 않는다.
     */
    public function logs(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'logs'           => ['required', 'array', 'min:1', 'max:200'],
            'logs.*.seq'     => ['required', 'integer', 'min:0'],
            'logs.*.type'    => ['required', 'in:system,tool_use,tool_result,result,error,daemon,handover'],
            'logs.*.content' => ['required', 'string'],
            'logs.*.raw'     => ['nullable', 'array'],
        ]);

        $now = now();
        $rows = collect($validated['logs'])->map(fn (array $l) => [
            'job_id'     => $job->id,
            'seq'        => $l['seq'],
            'type'       => $l['type'],
            'content'    => $l['content'],
            'raw'        => json_encode(AiwJobLog::truncateRaw($l['raw'] ?? null), JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
        ])->all();

        DB::table('aiw_job_logs')->insertOrIgnore($rows);

        // 방금 실제로 들어간 것만 브로드캐스트한다(중복 재전송분은 이미 화면에 있다).
        $inserted = AiwJobLog::query()
            ->where('job_id', $job->id)
            ->whereIn('seq', array_column($validated['logs'], 'seq'))
            ->where('created_at', $now)
            ->get();

        foreach ($inserted as $log) {
            $this->emit(new JobLogAppended($log));
        }

        return response()->json(['accepted' => $inserted->count()] + $this->controlFlags($job));
    }

    /** assistant / handover 메시지 배치 저장. */
    public function messages(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'messages'           => ['required', 'array', 'min:1', 'max:50'],
            'messages.*.seq'     => ['required', 'integer', 'min:0'],
            'messages.*.role'    => ['required', 'in:assistant,handover'],
            'messages.*.content' => ['required', 'string'],
        ]);

        $sessionIndex = $job->currentSessionIndex();
        $now = now();

        DB::table('aiw_job_messages')->insertOrIgnore(
            collect($validated['messages'])->map(fn (array $m) => [
                'job_id'        => $job->id,
                'seq'           => $m['seq'],
                'role'          => $m['role'],
                'content'       => $m['content'],
                'session_index' => $sessionIndex,
                'created_at'    => $now,
            ])->all()
        );

        $inserted = AiwJobMessage::query()
            ->where('job_id', $job->id)
            ->whereIn('seq', array_column($validated['messages'], 'seq'))
            ->where('created_at', $now)
            ->get();

        foreach ($inserted as $message) {
            $this->emit(new JobMessageAppended($message));
        }

        return response()->json(['accepted' => $inserted->count()] + $this->controlFlags($job));
    }

    /** 사용자 메시지 주입 완료 보고 → 화면의 "전달 대기" 표시가 풀린다. */
    public function delivered(Request $request, AiwJob $job, AiwJobMessage $message): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        abort_unless((int) $message->job_id === (int) $job->id, 404);

        if ($message->delivered_at === null) {
            $message->forceFill(['delivered_at' => now()])->save();
            $this->emit(new JobMessageAppended($message));
        }

        return response()->json($this->controlFlags($job));
    }

    /**
     * 상태 전이 + 게이지 갱신.
     *
     * context_tokens 는 덮어쓴다(누적 아님). 입력 토큰은 턴마다 전체 컨텍스트가
     * 재전송되므로 합산하면 실제의 수 배로 부풀어 인수인계가 조기에 터진다.
     * cost_usd 는 데몬이 계산한 누적값을 그대로 받는다.
     */
    public function status(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'status'         => ['required', 'in:running,waiting_input,waiting_permission,handover'],
            'context_tokens' => ['nullable', 'integer', 'min:0'],
            'cost_usd'       => ['nullable', 'numeric', 'min:0'],
        ]);

        $to = AiwJobStatus::from($validated['status']);

        abort_unless(
            $job->status->canTransitionTo($to),
            409,
            "상태 전이가 허용되지 않습니다: {$job->status->value} → {$to->value}"
        );

        $job->forceFill(array_filter([
            'status'         => $to,
            'context_tokens' => $validated['context_tokens'] ?? null,
            'cost_usd'       => $validated['cost_usd'] ?? null,
        ], fn ($v) => $v !== null))->save();

        $this->emit(new JobStatusChanged($job));

        return response()->json($this->controlFlags($job));
    }

    /**
     * 승인 요청 생성. request_key 로 idempotent 하다 — 데몬이 재시도해도
     * 레코드가 늘지 않고, 이미 결정된 건이면 그 결정을 즉시 돌려준다.
     */
    public function permissions(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'request_key' => ['required', 'string', 'max:191'],
            'tool_name'   => ['required', 'string', 'max:64'],
            'tool_input'  => ['nullable', 'array'],
        ]);

        $permission = AiwPermissionRequest::firstOrCreate(
            ['request_key' => $validated['request_key']],
            [
                'job_id'     => $job->id,
                'tool_name'  => $validated['tool_name'],
                'tool_input' => $validated['tool_input'] ?? [],
                'status'     => 'pending',
                'created_at' => now(),
            ]
        );

        // 다른 job 의 키를 재사용하려는 시도는 막는다.
        abort_unless((int) $permission->job_id === (int) $job->id, 409, 'request_key 가 다른 작업에 속합니다.');

        if ($permission->wasRecentlyCreated) {
            $this->emit(new PermissionRequested($permission));
        }

        return response()->json([
            'request_key' => $permission->request_key,
            'status'      => $permission->status,
            'decision'    => $permission->decisionForDaemon(),
        ] + $this->controlFlags($job));
    }

    /** 세션 교체 보고. 인수인계 요약을 대화에 남기고 컨텍스트를 0으로 리셋한다. */
    public function handover(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'ended_session_id' => ['required', 'string', 'max:191'],
            'new_session_id'   => ['required', 'string', 'max:191'],
            'document_path'    => ['required', 'string', 'max:512'],
            'summary'          => ['required', 'string'],
            'seq'              => ['required', 'integer', 'min:0'],
            'reason'           => ['nullable', 'string', 'max:191'],
        ]);

        $chain = $job->session_chain ?? [];

        for ($i = count($chain) - 1; $i >= 0; $i--) {
            if (($chain[$i]['session_id'] ?? null) === $validated['ended_session_id']) {
                $chain[$i]['ended_at'] = now()->toIso8601String();
                $chain[$i]['reason'] = $validated['reason'] ?? 'context';
                break;
            }
        }

        $chain[] = [
            'session_id' => $validated['new_session_id'],
            'started_at' => now()->toIso8601String(),
            'ended_at'   => null,
            'reason'     => null,
        ];

        $job->forceFill([
            'session_chain'  => $chain,
            'handover_count' => $job->handover_count + 1,
            'context_tokens' => 0,          // 새 세션은 컨텍스트가 비어 있다
            'status'         => AiwJobStatus::Running,
        ])->save();

        $message = AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => $validated['seq'],
            'role'          => 'handover',
            'content'       => $validated['summary'],
            'session_index' => max(0, count($chain) - 2),
            'created_at'    => now(),
        ]);

        $this->emit(new JobMessageAppended($message));
        $this->emit(new JobStatusChanged($job));

        return response()->json([
            'handover_count' => $job->handover_count,
            'document_path'  => $validated['document_path'],
        ] + $this->controlFlags($job));
    }

    /** 완료 보고. 큰 diff 는 DB 가 아니라 파일로 옮긴다. */
    public function complete(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'result_summary' => ['nullable', 'string'],
            'changed_files'  => ['nullable', 'array'],
            'git_diff'       => ['nullable', 'string'],
            'cost_usd'       => ['nullable', 'numeric', 'min:0'],
            'duration_ms'    => ['nullable', 'integer', 'min:0'],
        ]);

        abort_unless(
            $job->status->canTransitionTo(AiwJobStatus::Completed),
            409,
            "상태 전이가 허용되지 않습니다: {$job->status->value} → completed"
        );

        [$inline, $path] = $this->storeDiff($job, $validated['git_diff'] ?? null);

        $job->forceFill([
            'status'         => AiwJobStatus::Completed,
            'result_summary' => $validated['result_summary'] ?? null,
            'changed_files'  => $validated['changed_files'] ?? null,
            'git_diff'       => $inline,
            'git_diff_path'  => $path,
            'cost_usd'       => $validated['cost_usd'] ?? $job->cost_usd,
            'duration_ms'    => $validated['duration_ms'] ?? null,
            'finished_at'    => now(),
        ])->save();

        $this->expirePendingPermissions($job);
        $this->emit(new JobStatusChanged($job));

        return response()->json($this->controlFlags($job));
    }

    /** 실패 보고. */
    public function fail(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'error_message' => ['required', 'string'],
            'cost_usd'      => ['nullable', 'numeric', 'min:0'],
            'duration_ms'   => ['nullable', 'integer', 'min:0'],
        ]);

        abort_unless(
            $job->status->canTransitionTo(AiwJobStatus::Failed),
            409,
            "상태 전이가 허용되지 않습니다: {$job->status->value} → failed"
        );

        $job->forceFill([
            'status'        => AiwJobStatus::Failed,
            'error_message' => $validated['error_message'],
            'cost_usd'      => $validated['cost_usd'] ?? $job->cost_usd,
            'duration_ms'   => $validated['duration_ms'] ?? null,
            'finished_at'   => now(),
        ])->save();

        $this->expirePendingPermissions($job);
        $this->emit(new JobStatusChanged($job));

        return response()->json($this->controlFlags($job));
    }

    /**
     * @return array{0: ?string, 1: ?string} [인라인 저장할 diff, 파일 경로]
     */
    private function storeDiff(AiwJob $job, ?string $diff): array
    {
        if ($diff === null || $diff === '') {
            return [null, null];
        }

        $max = (int) config('aiw.git_diff_max_inline', 200 * 1024);

        if (strlen($diff) <= $max) {
            return [$diff, null];
        }

        $path = "aiw/diffs/job-{$job->id}.diff";
        Storage::disk('local')->put($path, $diff);

        return [null, $path];
    }

    /** 종료된 job 에 승인 대기가 남아 있으면 사용자가 무효한 카드를 누르게 된다. */
    private function expirePendingPermissions(AiwJob $job): void
    {
        $job->permissionRequests()->pending()->update([
            'status'     => 'expired',
            'decided_at' => now(),
        ]);
    }
}
