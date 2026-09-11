<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Enums\AiWork\AiwFailureCode;
use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobLogAppended;
use App\Events\AiWork\JobMessageAppended;
use App\Events\AiWork\JobStatusChanged;
use App\Events\AiWork\PermissionRequested;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobLog;
use App\Models\AiWork\AiwJobMessage;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\AiWork\AiwPublish;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Exceptions\AiWork\InvalidJobTransitionException;
use App\Services\AiWork\AttachmentService;
use App\Services\AiWork\AutoPipeline;
use App\Services\AiWork\CostGuard;
use App\Services\AiWork\HandoverService;
use App\Services\AiWork\JobStateMachine;
use App\Services\AiWork\PermissionService;
use Illuminate\Support\Facades\Storage;

/**
 * 데몬이 실행 상황을 보고하는 엔드포인트들.
 *
 * 상태 전이는 아직 여기서 직접 한다. Phase 4 의 JobStateMachine 이 들어오면
 * 전이 부분만 그쪽으로 옮기고 이 컨트롤러는 입출력만 담당하게 된다.
 */
class JobReportController extends AgentApiController
{
    public function __construct(
        private JobStateMachine $states,
        private PermissionService $permissions,
        private HandoverService $handovers,
        private CostGuard $costGuard,
        private AutoPipeline $autoPipeline,
    ) {}

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

        // 복구된 세션은 툴 호출을 처음부터 다시 시도하므로 새 request_key 가 생긴다.
        // 옛 pending 을 남겨두면 사용자가 무효한 카드를 누르게 된다.
        if ($request->boolean('resumed')) {
            $job->permissionRequests()->pending()->update([
                'status'     => 'expired',
                'decided_at' => now(),
            ]);
        }

        $this->states->transition($job, AiwJobStatus::Running, ['session_chain' => $chain]);

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
            // 모델이 제시한 선택지. 화면이 버튼으로 그린다.
            'messages.*.choices'   => ['nullable', 'array', 'max:6'],
            'messages.*.choices.*' => ['required', 'string', 'max:200'],
        ]);

        $sessionIndex = $job->currentSessionIndex();
        $now = now();

        DB::table('aiw_job_messages')->insertOrIgnore(
            collect($validated['messages'])->map(fn (array $m) => [
                'job_id'        => $job->id,
                'seq'           => $m['seq'],
                'role'          => $m['role'],
                'content'       => $m['content'],
                'choices'       => isset($m['choices'])
                    ? json_encode(array_values($m['choices']), JSON_UNESCAPED_UNICODE)
                    : null,
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

    /**
     * 담당자가 만든 결과물(스크린샷 등)을 대화에 첨부한다.
     *
     * 작업 폴더의 docs/aiw/outbox 에 놓인 이미지를 데몬이 올린다. 글로 설명할 수
     * 있는 것은 답변에 쓰면 되고, 이 경로는 "보여 줘야 아는 것"을 위한 것이다.
     * 사람이 화면을 눈으로 확인한 뒤 배포를 결정할 수 있게 하는 것이 목적이다.
     *
     * 어느 발언에 붙일지는 seq 로 지정한다 — 데몬은 메시지를 먼저 보내고
     * 그 seq 로 파일을 올리므로 id 를 알 필요가 없다.
     */
    public function attachments(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'seq'  => ['required', 'integer', 'min:0'],
            'file' => ['required', 'image', 'max:'.(AttachmentService::MAX_UPLOAD_BYTES / 1024)],
        ]);

        $message = $job->messages()->where('seq', $validated['seq'])->firstOrFail();

        // 담당자는 사람이 아니다. 이 job 을 만든 사람의 것으로 귀속시킨다.
        $owner = $job->creator ?? User::findOrFail($job->created_by);

        $saved = app(AttachmentService::class)->attach($message, [$request->file('file')], $owner);

        // 화면이 새로고침 없이 받아볼 수 있게 메시지를 다시 브로드캐스트한다.
        $this->emit(new JobMessageAppended($message->fresh()));

        return response()->json([
            'attachment_id' => $saved[0]->id ?? null,
        ] + $this->controlFlags($job));
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

        try {
            $this->states->transition($job, $to, array_filter([
                'context_tokens' => $validated['context_tokens'] ?? null,
            ], fn ($v) => $v !== null));
        } catch (InvalidJobTransitionException $e) {
            abort(409, $e->getMessage());
        }

        // 비용 갱신은 상한 검사와 한 몸이다. 넘으면 여기서 job 이 중단된다.
        if (isset($validated['cost_usd'])) {
            $this->costGuard->apply($job, (float) $validated['cost_usd']);
        }

        return response()->json($this->controlFlags($job->refresh()));
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

        $permission = $this->permissions->request(
            $job,
            $validated['request_key'],
            $validated['tool_name'],
            $validated['tool_input'] ?? [],
        );

        // 다른 job 의 키를 재사용하려는 시도는 막는다.
        abort_unless((int) $permission->job_id === (int) $job->id, 409, 'request_key 가 다른 작업에 속합니다.');

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

        $this->handovers->record($job, $validated);

        $job->refresh();

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

        // 이미 끝난 job 이면 diff 를 다시 저장하지 않는다. 첫 보고가 이미 기록했고,
        // 취소된 job 이라면 애초에 결과를 남길 이유가 없다.
        if ($job->status->isTerminal()) {
            $this->states->reportTerminal($job, AiwJobStatus::Completed);

            return response()->json($this->controlFlags($job));
        }

        [$inline, $path] = $this->storeDiff($job, $validated['git_diff'] ?? null);

        try {
            $this->states->reportTerminal($job, AiwJobStatus::Completed, [
                'result_summary' => $validated['result_summary'] ?? null,
                'changed_files'  => $validated['changed_files'] ?? null,
                'git_diff'       => $inline,
                'git_diff_path'  => $path,
                'cost_usd'       => $validated['cost_usd'] ?? $job->cost_usd,
                'duration_ms'    => $validated['duration_ms'] ?? null,
            ]);
        } catch (InvalidJobTransitionException $e) {
            abort(409, $e->getMessage());
        }

        // "배포까지 자동으로" 를 켰다면 여기서 다음 단계가 시작된다.
        $this->autoPipeline->afterComplete($job->refresh());

        return response()->json($this->controlFlags($job));
    }

    /**
     * 커밋·푸시 결과 보고.
     *
     * 원격을 바꾸는 동작이라 출력을 통째로 남긴다 — 실패했을 때 화면에서
     * 이유(충돌·거부 등)를 그대로 볼 수 있어야 한다.
     */
    public function publishResult(Request $request, AiwJob $job, AiwPublish $publish): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        abort_unless((int) $publish->job_id === (int) $job->id, 404);

        $validated = $request->validate([
            'status'     => ['required', 'in:running,succeeded,failed'],
            'output'     => ['nullable', 'string'],
            'commit_sha' => ['nullable', 'string', 'max:60'],
        ]);

        // 이미 끝난 건은 덮지 않는다. 재시도 보고가 결과를 바꾸면 안 된다.
        if ($publish->isFinished()) {
            return response()->json(['status' => $publish->status] + $this->controlFlags($job));
        }

        $publish->forceFill(array_filter([
            'status'      => $validated['status'],
            'output'      => AiwPublish::truncateOutput($validated['output'] ?? null),
            'commit_sha'  => $validated['commit_sha'] ?? null,
            'finished_at' => $validated['status'] === 'running' ? null : now(),
        ], fn ($v) => $v !== null))->save();

        // 진행 상황이 화면에 바로 보이도록 활동 로그로도 남긴다.
        if ($validated['status'] !== 'running') {
            $this->appendPublishLog($job, $publish);
            $this->autoPipeline->afterPublish($job, $publish->refresh());
        }

        return response()->json(['status' => $publish->status] + $this->controlFlags($job));
    }

    private function appendPublishLog(AiwJob $job, AiwPublish $publish): void
    {
        $text = $publish->status === 'succeeded'
            ? sprintf('원격에 올렸습니다: %s → %s (%s)', $publish->source_branch, $publish->target_branch, $publish->commit_sha ?: '커밋 없음')
            : '커밋·푸시 실패: '.\Illuminate\Support\Str::limit((string) $publish->output, 300);

        try {
            $log = AiwJobLog::create([
                'job_id'  => $job->id,
                'seq'     => (int) AiwJobLog::where('job_id', $job->id)->max('seq') + 1,
                'type'    => $publish->status === 'succeeded' ? 'daemon' : 'error',
                'content' => $text,
            ]);

            $this->emit(new JobLogAppended($log));
        } catch (\Throwable $e) {
            // 로그 한 줄 때문에 결과 기록이 실패하면 안 된다.
        }
    }

    /** 실패 보고. */
    public function fail(Request $request, AiwJob $job): JsonResponse
    {
        $job = $this->ownedJob($request, $job);

        $validated = $request->validate([
            'error_message' => ['required', 'string'],
            'cost_usd'      => ['nullable', 'numeric', 'min:0'],
            'duration_ms'   => ['nullable', 'integer', 'min:0'],
            // 화이트리스트로 검증한다. 데몬이 서버가 모르는 코드를 보내면 조용히
            // 무시되는 대신 422 로 드러나야 한다 — 화면은 아는 코드만 그릴 수 있다.
            'error_code'    => ['nullable', Rule::enum(AiwFailureCode::class)],
            'error_detail'  => ['nullable', 'array'],
        ]);

        try {
            $this->states->reportTerminal($job, AiwJobStatus::Failed, [
                'error_message' => $validated['error_message'],
                'error_code'    => $validated['error_code'] ?? null,
                'error_detail'  => $validated['error_detail'] ?? null,
                'cost_usd'      => $validated['cost_usd'] ?? $job->cost_usd,
                'duration_ms'   => $validated['duration_ms'] ?? null,
            ]);
        } catch (InvalidJobTransitionException $e) {
            abort(409, $e->getMessage());
        }

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

}
