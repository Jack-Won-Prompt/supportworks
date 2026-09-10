<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Events\AiWork\JobMessageAppended;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 컨텍스트 인수인계(세션 교체) 기록과 CLAUDE.md 승격.
 */
class HandoverService
{
    public function __construct(private JobStateMachine $states) {}

    /**
     * 데몬의 세션 교체 보고를 반영한다.
     *
     * context_tokens 를 0 으로 리셋하는 것이 핵심이다. 새 세션은 인수인계 문서만
     * 들고 시작하므로 이전 세션의 컨텍스트 크기를 물려받지 않는다.
     * cost_usd 는 리셋하지 않는다 — 비용은 job 전체의 누적이다.
     *
     * @param array{ended_session_id:string,new_session_id:string,summary:string,seq:int,reason?:string} $payload
     */
    public function record(AiwJob $job, array $payload): AiwJobMessage
    {
        $chain = $job->session_chain ?? [];

        for ($i = count($chain) - 1; $i >= 0; $i--) {
            if (($chain[$i]['session_id'] ?? null) === $payload['ended_session_id']) {
                $chain[$i]['ended_at'] = now()->toIso8601String();
                $chain[$i]['reason'] = $payload['reason'] ?? 'context';
                break;
            }
        }

        $chain[] = [
            'session_id' => $payload['new_session_id'],
            'started_at' => now()->toIso8601String(),
            'ended_at'   => null,
            'reason'     => null,
        ];

        $job->forceFill([
            'session_chain'  => $chain,
            'handover_count' => $job->handover_count + 1,
            'context_tokens' => 0,
        ])->save();

        $message = AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => $payload['seq'],
            'role'          => 'handover',
            'content'       => $payload['summary'],
            'session_index' => max(0, count($chain) - 2),
            'created_at'    => now(),
        ]);

        $this->emit(new JobMessageAppended($message), $job->id);

        // 교체가 끝났으니 다시 실행 상태로 돌린다(상태 전이는 상태머신이 담당).
        // 이미 running 이어도 no-op 전이가 허용되므로 분기할 필요가 없다.
        $this->states->transition($job, AiwJobStatus::Running);

        return $message;
    }

    /**
     * 인수인계 문서의 선택 항목을 CLAUDE.md 로 승격한다.
     *
     * **파일을 직접 쓰지 않는다.** 후속 지시(job)를 만들어 돌려준다. AI 가 만든
     * 내용이 사람의 확인 없이 저장소의 규범 문서에 들어가면 안 되고, 실제 반영은
     * 다른 작업과 똑같이 diff 로 검토돼야 한다.
     *
     * @param  array<int, string>  $selectedSections  사용자가 고른 항목 본문
     */
    public function promoteToClaudeMd(AiwJob $job, array $selectedSections, User $user): AiwJob
    {
        $sections = array_values(array_filter(array_map('trim', $selectedSections)));

        if ($sections === []) {
            throw new \InvalidArgumentException('승격할 항목을 하나 이상 선택해야 합니다.');
        }

        $body = implode("\n", array_map(fn (string $s) => '- '.$s, $sections));

        $instruction = <<<TXT
        저장소 루트의 CLAUDE.md 에 아래 항목을 추가해 주세요.

        {$body}

        규칙:
        - 기존 내용을 지우거나 재배치하지 말고, 성격이 맞는 섹션에 덧붙이기만 하세요.
        - 맞는 섹션이 없으면 문서 끝에 새 섹션을 만드세요.
        - 이미 같은 취지의 문장이 있으면 중복해서 넣지 말고 그대로 두세요.
        - CLAUDE.md 외의 파일은 수정하지 마세요.
        TXT;

        return AiwJob::create([
            'project_id'           => $job->project_id,
            'agent_id'             => $job->agent_id,
            'parent_job_id'        => $job->id,
            'title'                => "CLAUDE.md 승격 (작업 #{$job->id})",
            'instruction'          => $instruction,
            'mode'                 => 'batch',
            'model'                => $job->model,
            'context_limit_tokens' => $job->context_limit_tokens,
            'allowed_tools'        => ['Read', 'Edit', 'Write', 'Glob', 'Grep'],
            'permission_mode'      => 'acceptEdits',
            'cost_limit_usd'       => $job->cost_limit_usd,
            'use_branch'           => true,
            'created_by'           => $user->id,
        ]);
    }

    private function emit(object $event, int $jobId): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 인수인계 브로드캐스트 실패(기록은 저장됨)', [
                'job_id' => $jobId,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
