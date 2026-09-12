<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwDeploy;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\AiWork\AiwPublish;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * AI Works 모바일 푸시.
 *
 * 사람의 손이 필요한 순간(회신·승인 요청)과 결과(완료·실패·자동 배포)를 지시한
 * 관리자의 휴대폰으로 알린다. 실시간 화면을 대신하려는 것이 아니라, 화면을 보고
 * 있지 않은 사람을 불러오는 것이 목적이다.
 *
 * 브로드캐스트 이벤트가 아니라 **모델 저장**에 붙는다. ShouldBroadcastNow 이벤트는
 * 리스너보다 브로드캐스트를 먼저 보내는데, Reverb 가 닿지 않으면 거기서 예외가 나
 * 리스너까지 건너뛴다 — 실시간이 죽은 순간 푸시도 같이 죽는다. 저장 이벤트는 그와
 * 무관하고, wasChanged('status') 로 "실제로 바뀐 순간"만 잡는다(데몬은 매 턴 같은
 * 상태를 다시 보고하므로, 이게 없으면 턴마다 울린다).
 *
 * 알림이 실패해도 작업 흐름은 멈추지 않는다. 모든 경로가 예외를 삼킨다.
 */
class AiwNotifier
{
    /** 앱이 알림 탭을 라우팅할 때 보는 값. */
    public const TYPE = 'aiw_job';

    /** 모델 저장 이벤트에 연결한다. AppServiceProvider 가 부팅 때 한 번 부른다. */
    public static function register(): void
    {
        AiwJob::updated(function (AiwJob $job) {
            if ($job->wasChanged('status')) {
                app(self::class)->safely(fn (self $n) => $n->statusChanged($job));
            }
        });

        AiwPermissionRequest::created(function (AiwPermissionRequest $request) {
            app(self::class)->safely(fn (self $n) => $n->permissionRequested($request));
        });

        AiwPublish::updated(function (AiwPublish $publish) {
            if ($publish->wasChanged('status')) {
                app(self::class)->safely(fn (self $n) => $n->publishFinished($publish));
            }
        });

        AiwDeploy::updated(function (AiwDeploy $deploy) {
            if ($deploy->wasChanged('status')) {
                app(self::class)->safely(fn (self $n) => $n->deployFinished($deploy));
            }
        });
    }

    public function statusChanged(AiwJob $job): void
    {
        // 취소는 알리지 않는다. 사람이 누른 것이라 누른 사람이 이미 안다.
        // 서버가 끊는 경우(비용 상한·무응답 담당자)는 failed 로 온다.
        $message = match ($job->status) {
            AiwJobStatus::WaitingInput => ['input', '담당자 회신', $this->replyPreview($job)],
            AiwJobStatus::Completed    => ['completed', '작업 완료', $this->completedBody($job)],
            AiwJobStatus::Failed       => ['failed', '작업 실패', $this->clip($job->error_message ?: '작업이 실패했습니다.')],
            default                    => null,
        };

        if ($message === null) {
            return;
        }

        [$event, $headline, $body] = $message;

        $this->toCreator($job, $headline, $body, $event);
    }

    public function permissionRequested(AiwPermissionRequest $request): void
    {
        $job = $request->job;

        if (! $job) {
            return;
        }

        // 한 작업에서 승인 요청이 연달아 오면 대기 중인 것이 없을 때의 첫 건만 알린다.
        // 화면을 열면 대기 중인 요청이 모두 보인다 — 건마다 울리면 습관적으로 누르게 된다.
        if ($job->permissionRequests()->pending()->whereKeyNot($request->id)->exists()) {
            return;
        }

        $this->toCreator(
            $job,
            '승인 요청',
            $request->tool_name.' · '.self::describeToolInput((array) $request->tool_input),
            'permission',
        );
    }

    public function publishFinished(AiwPublish $publish): void
    {
        // 사람이 누른 커밋·푸시는 누른 사람이 화면에서 본다. 자동 진행의 실패만 알린다
        // — 여기서 멈추면 배포가 시작되지 않아 배포 결과 알림도 오지 않는다.
        if (! $publish->automatic || $publish->status !== 'failed' || ! $publish->job) {
            return;
        }

        $this->toCreator($publish->job, '자동 배포 중단', '커밋·푸시가 실패해 배포하지 않았습니다.', 'publish_failed');
    }

    public function deployFinished(AiwDeploy $deploy): void
    {
        if (! $deploy->automatic || ! in_array($deploy->status, ['succeeded', 'failed'], true) || ! $deploy->job) {
            return;
        }

        $target = $deploy->target?->name ?? '배포 대상';

        if ($deploy->status === 'succeeded') {
            $this->toCreator($deploy->job, '배포 완료', "{$target} 배포가 끝났습니다.", 'deploy_succeeded');

            return;
        }

        $exit = $deploy->exit_code !== null ? " (exit {$deploy->exit_code})" : '';

        $this->toCreator($deploy->job, '배포 실패', "{$target} 배포가 실패했습니다{$exit}.", 'deploy_failed');
    }

    /**
     * 승인 카드에 보일 한 줄. 명령이면 명령 전문, 파일이면 경로.
     * 사람이 무엇을 허용하는지 보고 누르게 하는 것이 승인 게이트의 전부다.
     */
    public static function describeToolInput(array $input): string
    {
        $value = $input['command'] ?? $input['file_path'] ?? $input['path']
            ?? $input['pattern'] ?? $input['url'] ?? $input['query'] ?? null;

        if (! is_string($value) || $value === '') {
            $value = json_encode($input, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', $value)), 200);
    }

    /** 이벤트 처리 중 예외가 작업 흐름으로 번지지 않게 한다. */
    public function safely(\Closure $handler): void
    {
        try {
            $handler($this);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 모바일 푸시 준비 실패(작업은 계속된다)', ['error' => $e->getMessage()]);
        }
    }

    /**
     * 받는 사람은 지시한 사람이다. 작업 지시는 관리자 전용이므로, 그 사이 권한이
     * 바뀐 사람에게는 보내지 않는다(앱에서 메뉴도 열 수 없다).
     */
    private function toCreator(AiwJob $job, string $headline, string $body, string $event): void
    {
        $creator = $job->creator;

        if (! $creator || ! $creator->isAdmin()) {
            return;
        }

        $this->send($creator->id, $headline.' — '.$job->title, $body, [
            'type'       => self::TYPE,
            'job_id'     => $job->id,
            'project_id' => $job->project_id,
            'event'      => $event,
            'status'     => $job->status->value,
        ]);
    }

    /** 실제 발송. 테스트가 덮어쓴다. */
    protected function send(int $userId, string $title, string $body, array $data): void
    {
        $push = fn () => FcmService::notifyUser($userId, $title, $body, $data);

        // 웹 요청(대개 데몬의 보고) 안에서는 응답을 돌려준 뒤에 보낸다. FCM 이 느리면
        // 데몬의 보고가 시간 초과로 재시도되기 때문이다. 콘솔(스케줄러·큐 워커)에는
        // 요청이 끝나는 시점이 없으므로 바로 보낸다.
        app()->runningInConsole() ? $push() : app()->terminating($push);
    }

    private function replyPreview(AiwJob $job): string
    {
        $last = $job->messages()->where('role', 'assistant')->orderByDesc('seq')->first();

        if (! $last) {
            return '담당자가 답변을 기다립니다.';
        }

        $text = $this->clip($last->content);
        $choices = count($last->choices ?? []);

        return $choices > 0 ? "선택지 {$choices}개 · {$text}" : $text;
    }

    private function completedBody(AiwJob $job): string
    {
        $text = $job->result_summary ? $this->clip($job->result_summary) : '작업이 끝났습니다.';

        return $job->auto_deploy ? $text.' 이어서 커밋·푸시와 배포를 진행합니다.' : $text;
    }

    /** 알림 한 줄. 마크다운 기호와 줄바꿈을 걷어 낸다. */
    private function clip(?string $text): string
    {
        $plain = preg_replace('/[#*`>]+/u', '', strip_tags((string) $text));

        return Str::limit(trim((string) preg_replace('/\s+/u', ' ', (string) $plain)), 120);
    }
}
