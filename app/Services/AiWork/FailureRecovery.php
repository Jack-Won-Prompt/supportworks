<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobAttachment;
use App\Models\AiWork\AiwJobMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * 환경 때문에 끊긴 작업을 서버가 스스로 다시 보낸다.
 *
 * 원격에 사람이 없다는 전제에서, 담당자 PC 가 잠깐 사라졌다는 이유로 지시가 죽고
 * 사람이 올 때까지 아무 일도 일어나지 않는 것은 아깝다. 그런 실패는 코드나 지시가
 * 잘못된 것이 아니라 **환경이 사라진 것**이라, 같은 지시를 그대로 다시 보내면 된다.
 *
 * 다시 보내지 않는 것:
 *   - 비용·시간 상한 — 그대로 보내면 같은 자리에서 또 멈춘다. 사람이 정해야 한다.
 *   - 경로·브랜치·매핑 문제 — 설정을 고쳐야 풀린다.
 *   - 모델이 만든 실패(테스트 깨짐 등) — 무엇을 고칠지는 사람이 판단한다.
 *
 * 원본을 되살리지 않고 **후속 job 을 만든다**. 종료된 작업은 불변이고(감사 추적),
 * 그래야 "몇 번째 시도였는지" 가 화면에 남는다.
 */
class FailureRecovery
{
    public function __construct(private JobDispatcher $dispatcher) {}

    /** 실패가 기록된 직후. 다시 보낼 만하면 후속 job 을 만들어 보낸다. */
    public function afterFail(AiwJob $job): ?AiwJob
    {
        $code = $job->failureCode();
        $max  = (int) config('aiw.auto_retry_max', 1);

        if ($code === null || ! $code->autoRetryable() || $max <= 0) {
            return null;
        }

        if ((int) $job->retry_count >= $max) {
            $this->note($job, sprintf('자동 재시도 상한(%d회)에 닿아 멈춥니다. 이어서 하려면 후속 지시를 만들어 주세요.', $max));

            return null;
        }

        try {
            $retry = $this->duplicate($job);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 자동 재시도 생성 실패', ['job_id' => $job->id, 'error' => $e->getMessage()]);

            return null;
        }

        $attempt = (int) $retry->retry_count;

        $this->note($job, sprintf(
            '%s — 같은 지시를 자동으로 다시 보냅니다 (%d/%d). 새 작업 #%d.',
            $code->label(),
            $attempt,
            $max,
            $retry->id,
        ));

        $this->dispatcher->dispatchFollowUp($retry);

        return $retry;
    }

    /** 같은 설정·지시문·첨부로 후속 job 을 만든다. */
    private function duplicate(AiwJob $job): AiwJob
    {
        $retry = AiwJob::create([
            'project_id'            => $job->project_id,
            'agent_id'              => $job->agent_id,
            'parent_job_id'         => $job->id,
            'title'                 => $job->title,
            'instruction'           => $job->instruction,
            'mode'                  => $job->mode,
            'model'                 => $job->model,
            'context_limit_tokens'  => $job->context_limit_tokens,
            'allowed_tools'         => $job->allowed_tools,
            'permission_mode'       => $job->permission_mode,
            'cost_limit_usd'        => $job->cost_limit_usd,
            'use_branch'            => $job->use_branch,
            'auto_deploy'           => $job->auto_deploy,
            'auto_deploy_target_id' => $job->auto_deploy_target_id,
            'created_by'            => $job->created_by,
            'retry_count'           => (int) $job->retry_count + 1,
        ]);

        $first = AiwJobMessage::create([
            'job_id'        => $retry->id,
            'seq'           => 0,
            'role'          => 'user',
            'content'       => $retry->instruction,
            'user_id'       => $job->created_by,
            'session_index' => 0,
            'delivered_at'  => now(),
        ]);

        $this->copyAttachments($job, $retry, $first);

        return $retry;
    }

    /**
     * 지시문에 붙었던 파일을 새 job 으로 옮겨 붙인다.
     *
     * 파일까지 복사한다. 경로를 공유하면 한쪽 job 을 지울 때 다른 쪽 파일이 함께
     * 사라진다 — 지금은 job 을 지우면 그 폴더를 통째로 지우기 때문이다.
     */
    private function copyAttachments(AiwJob $from, AiwJob $to, AiwJobMessage $message): void
    {
        $originals = AiwJobAttachment::where('job_id', $from->id)
            ->whereHas('message', fn ($q) => $q->where('seq', 0))
            ->get();

        foreach ($originals as $original) {
            try {
                $ext  = pathinfo($original->path, PATHINFO_EXTENSION);
                $path = sprintf('aiw/attachments/%d/%s.%s', $to->id, Str::uuid(), $ext !== '' ? $ext : 'bin');

                if (! Storage::disk('local')->exists($original->path)) {
                    continue;   // 원본이 이미 정리됐다면 건너뛴다
                }

                Storage::disk('local')->copy($original->path, $path);

                AiwJobAttachment::create([
                    'job_id'        => $to->id,
                    'message_id'    => $message->id,
                    'path'          => $path,
                    'mime'          => $original->mime,
                    'bytes'         => $original->bytes,
                    'width'         => $original->width,
                    'height'        => $original->height,
                    'original_name' => $original->original_name,
                    'created_by'    => $original->created_by,
                ]);
            } catch (\Throwable $e) {
                // 첨부 하나 때문에 재시도 자체를 버리지 않는다.
                Log::warning('AI Works: 재시도 첨부 복사 실패', [
                    'job_id' => $from->id,
                    'attachment_id' => $original->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /** 원본 작업의 대화에 무슨 일이 있었는지 남긴다. */
    private function note(AiwJob $job, string $text): void
    {
        try {
            app(MessageWriter::class)->appendOne($job, [
                'role'          => 'system',
                'content'       => $text,
                'session_index' => $job->currentSessionIndex(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 자동 재시도 안내 기록 실패', ['job_id' => $job->id, 'error' => $e->getMessage()]);
        }
    }
}
