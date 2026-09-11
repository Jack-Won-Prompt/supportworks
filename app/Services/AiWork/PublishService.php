<?php

namespace App\Services\AiWork;

use App\Events\AiWork\PublishRequested;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPublish;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 작업 결과를 원격 저장소에 올린다.
 *
 * 담당자는 git push 를 할 수 없다 — 샌드박스가 승인 여부와 무관하게 막는다.
 * 사람이 결과를 확인하고 버튼을 눌렀을 때만, 데몬이 서버 지시를 받아 직접
 * 실행한다. 담당자의 권한을 넓히는 것이 아니라 별도 경로를 여는 것이다.
 */
class PublishService
{
    /**
     * @throws RuntimeException 지금 올릴 수 없는 상태일 때
     */
    public function request(AiwJob $job, User $user, ?string $message = null): AiwPublish
    {
        $branch = $job->branchName();

        if ($branch === null) {
            throw new RuntimeException(
                '브랜치 분리를 끄고 실행한 작업입니다. 변경이 현재 브랜치에 섞여 있어 '
                .'이 작업만 골라 올릴 수 없습니다. 직접 확인 후 커밋하세요.'
            );
        }

        if (! $job->status->isTerminal()) {
            throw new RuntimeException('작업이 끝난 뒤에 올릴 수 있습니다.');
        }

        // 같은 저장소에 두 번 밀어 넣으면 서로를 덮거나 충돌한다.
        if ($job->publishes()->whereIn('status', ['pending', 'running'])->exists()) {
            throw new RuntimeException('이미 올리는 중입니다. 끝나면 다시 시도하세요.');
        }

        if ($job->publishes()->where('status', 'succeeded')->exists()) {
            throw new RuntimeException('이미 올린 작업입니다. 추가 변경은 후속 지시로 진행하세요.');
        }

        $mapping = AiwAgentProject::query()
            ->where('agent_id', $job->agent_id)
            ->where('project_id', $job->project_id)
            ->first();

        if (! $mapping) {
            throw new RuntimeException('담당자에 이 프로젝트의 로컬 경로가 매핑되어 있지 않습니다.');
        }

        if (! $job->agent?->is_online) {
            throw new RuntimeException('담당자가 오프라인입니다. 접속한 뒤 다시 시도하세요.');
        }

        $publish = AiwPublish::create([
            'job_id'         => $job->id,
            'requested_by'   => $user->id,
            'source_branch'  => $branch,
            // 기본 브랜치가 비어 있으면 데몬이 현재 기본 브랜치를 쓴다.
            'target_branch'  => $mapping->default_branch ?: 'HEAD',
            'commit_message' => $this->message($job, $message),
            'status'         => 'pending',
            'created_at'     => now(),
        ]);

        $this->emit($publish, $mapping->local_path, (int) $job->agent_id);

        return $publish;
    }

    /** 커밋 메시지. 사람이 준 것이 있으면 그것을, 없으면 작업 제목을 쓴다. */
    private function message(AiwJob $job, ?string $custom): string
    {
        $body = trim((string) $custom);

        if ($body !== '') {
            return mb_substr($body, 0, 480);
        }

        return mb_substr(sprintf('%s (작업 지시 #%d)', $job->title, $job->id), 0, 480);
    }

    private function emit(AiwPublish $publish, string $localPath, int $agentId): void
    {
        try {
            event(new PublishRequested($publish, $localPath, $agentId));
        } catch (\Throwable $e) {
            // 이벤트가 나가지 않으면 데몬이 요청을 못 받는다. 기록은 남기고
            // 실패로 닫아 사용자가 다시 누를 수 있게 한다.
            $publish->forceFill([
                'status'      => 'failed',
                'output'      => '담당자에게 요청을 전달하지 못했습니다: '.$e->getMessage(),
                'finished_at' => now(),
            ])->save();

            Log::error('AI Works: 푸시 요청 전달 실패', [
                'publish_id' => $publish->id,
                'error'      => $e->getMessage(),
            ]);

            throw new RuntimeException('담당자에게 요청을 전달하지 못했습니다. 잠시 후 다시 시도하세요.');
        }
    }
}
