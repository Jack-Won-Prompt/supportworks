<?php

namespace App\Jobs\AiWork;

use App\Models\AiWork\AiwDeploy;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

/**
 * 등록된 배포 명령을 서버에서 실행한다.
 *
 * 웹 요청 안에서 돌리지 않는 이유: deploy.sh 는 composer·migrate 를 포함해 수 분이
 * 걸린다. 요청을 붙잡으면 타임아웃으로 끊기고, 그 시점에 명령이 어디까지 갔는지
 * 아무도 모르게 된다. 큐에서 돌려 출력을 끝까지 모은다.
 *
 * 명령은 요청이 아니라 관리자가 등록한 대상 행에서 온다. 셸에 사용자 입력이
 * 섞이지 않는다는 것이 이 기능의 안전장치 전부다.
 */
class RunDeploy implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** 워커가 중간에 죽이지 않도록 명령 자체 상한보다 넉넉히 잡는다. */
    public int $timeout = 1800;

    /** 배포는 재시도하면 안 된다. 절반쯤 진행된 상태에서 다시 도는 것이 더 위험하다. */
    public int $tries = 1;

    public function __construct(public int $deployId) {}

    public function handle(): void
    {
        $deploy = AiwDeploy::with('target')->find($this->deployId);

        if (! $deploy || $deploy->status !== 'queued') {
            return;
        }

        $target = $deploy->target;

        if (! $target || ! $target->enabled) {
            $this->finish($deploy, 'failed', null, '배포 대상이 비활성 상태이거나 삭제되었습니다.');

            return;
        }

        if (! is_dir($target->working_dir)) {
            $this->finish($deploy, 'failed', null, '작업 디렉터리를 찾을 수 없습니다: '.$target->working_dir);

            return;
        }

        $deploy->forceFill(['status' => 'running', 'started_at' => now()])->save();

        // 명령 문자열은 등록된 값 그대로다. 사용자 입력이 섞이지 않는다.
        $process = Process::fromShellCommandline(
            $target->command,
            $target->working_dir,
            $this->childEnvironment(),
            null,
            $target->timeout_sec,
        );

        $output = '';

        try {
            $process->run(function ($type, $buffer) use (&$output) {
                $output .= $buffer;
            });

            $this->finish(
                $deploy,
                $process->isSuccessful() ? 'succeeded' : 'failed',
                $process->getExitCode(),
                $output,
            );
        } catch (\Throwable $e) {
            // 시간 초과 포함. 여기까지 모은 출력이 원인 추적의 전부다.
            $this->finish($deploy, 'failed', $process->getExitCode(), $output."\n\n[중단] ".$e->getMessage());
        }
    }

    /**
     * 배포 스크립트에 넘길 환경.
     *
     * **이 앱의 설정이 새어 들어가면 안 된다.** Symfony Process 는 넘긴 배열을
     * 부모 환경에 *덧씌울* 뿐 대체하지 않는다. 그런데 Laravel 은 .env 를 읽으며
     * putenv 로 값을 프로세스 환경에 올리므로, 큐 워커의 DB_DATABASE 가 그대로
     * 자식에게 간다. 대상 앱의 env() 는 파일보다 실제 환경변수를 먼저 보기 때문에
     * **자기 .env 를 무시하고 이 앱의 DB 를 쓰게 된다.**
     *
     * 실제로 그렇게 mangoshop 배포가 supportworks DB 에 자기 테이블 20개를
     * 만들었다(2026-09-11). 다행히 전부 빈 테이블이었지만, 한 끗 차이로
     * 남의 운영 데이터를 고칠 수 있었다.
     *
     * 값을 false 로 주면 Symfony 가 그 변수를 자식 환경에서 지운다.
     *
     * @return array<string, string|false>
     */
    private function childEnvironment(): array
    {
        $env = [
            'PATH' => getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin',
            'HOME' => getenv('HOME') ?: '/home/ubuntu',
        ];

        // 앱 설정으로 보이는 것은 모두 지운다. 대상 앱이 자기 .env 를 읽게 한다.
        $appConfig = '/^(APP|DB|CACHE|SESSION|QUEUE|MAIL|REDIS|BROADCAST|PUSHER|REVERB'
            .'|AWS|VITE|LOG|FILESYSTEM|MEMCACHED|SCOUT|SENTRY|TELESCOPE|NIGHTWATCH'
            .'|FCM|FIREBASE|OPENAI|ANTHROPIC|SW)_/';

        foreach (array_keys($_ENV + $_SERVER) as $key) {
            if (is_string($key) && preg_match($appConfig, $key)) {
                $env[$key] = false;
            }
        }

        return $env;
    }

    public function failed(\Throwable $e): void
    {
        $deploy = AiwDeploy::find($this->deployId);

        // 워커가 죽어도 화면이 "실행 중"으로 영원히 남지 않게 한다.
        if ($deploy && $deploy->isRunning()) {
            $this->finish($deploy, 'failed', null, '배포 작업이 비정상 종료했습니다: '.$e->getMessage());
        }
    }

    private function finish(AiwDeploy $deploy, string $status, ?int $exitCode, string $output): void
    {
        $deploy->forceFill([
            'status'      => $status,
            'exit_code'   => $exitCode,
            'output'      => AiwDeploy::truncateOutput($output),
            'finished_at' => now(),
        ])->save();

        Log::info('AI Works: 배포 종료', [
            'deploy_id' => $deploy->id,
            'target'    => $deploy->target?->name,
            'status'    => $status,
            'exit_code' => $exitCode,
        ]);
    }
}
