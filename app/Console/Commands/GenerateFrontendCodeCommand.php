<?php

namespace App\Console\Commands;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\FrontendCodeAiService;
use Illuminate\Console\Command;

class GenerateFrontendCodeCommand extends Command
{
    protected $signature = 'ai-agent:dev:code-generate
                            {projectId : 프로젝트 ID}
                            {--screen=  : 단일 화면 screen_id (예: SCR-001)}
                            {--screens= : 화면 DB ID 목록 (쉼표 구분)}
                            {--only-missing : 코드가 없는 화면만 생성}
                            {--confirm-cost : 비용 확인 없이 진행}
                            {--user=    : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = '화면별 Frontend 코드를 AI로 자동 생성합니다 (T40)';

    public function __construct(
        private readonly FrontendCodeAiService $service,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $projectId = (int) $this->argument('projectId');
        $project   = Project::find($projectId);

        if (!$project) {
            $this->error("프로젝트 ID {$projectId}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $userId = $this->option('user')
            ? (int) $this->option('user')
            : (User::first()?->id ?? 1);

        $stack       = $this->service->resolveStack($projectId);
        $onlyMissing = (bool) $this->option('only-missing');

        $this->info("프로젝트: {$project->name}");
        $this->line("  스택: {$stack->label()}");
        $this->newLine();

        // 단일 화면 생성 모드
        if ($screenSid = $this->option('screen')) {
            return $this->generateSingle($projectId, $screenSid, $userId);
        }

        // 배치 생성 모드
        $screenIds = null;
        if ($this->option('screens')) {
            $screenIds = array_map('intval', explode(',', $this->option('screens')));
        }

        $totalCount = AiAgentScreen::where('project_id', $projectId)->active()->count();
        $this->line("  전체 화면 수: {$totalCount}개");

        $estimatedCost = round($totalCount * 0.80, 2);
        if (!$this->option('confirm-cost')) {
            $this->warn("  예상 비용: \${$estimatedCost} (화면당 \$0.80 기준)");
            if (!$this->confirm('계속하시겠습니까?')) {
                $this->line('취소되었습니다.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $bar = $this->output->createProgressBar(100);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('시작 중...');
        $bar->start();

        try {
            $result = $this->service->generateBatch(
                projectId:   $projectId,
                screenIds:   $screenIds,
                onlyMissing: $onlyMissing,
                userId:      $userId,
                onProgress:  function (array $p) use ($bar) {
                    $bar->setProgress($p['progress'] ?? 0);
                    $icon = match($p['status'] ?? '') {
                        'processing' => '🔄', 'done' => '✅', 'failed' => '❌', default => '',
                    };
                    $bar->setMessage("{$icon} [{$p['screen_id']}] {$p['title']} ({$p['done']}/{$p['total']})");
                },
            );

            $bar->finish();
            $this->newLine(2);

            $this->info("Frontend 코드 생성 완료!");
            $this->line("  처리 화면: {$result['total']}개");
            $this->line("  성공: " . ($result['total'] - $result['failed_count']) . "개");
            if ($result['failed_count'] > 0) {
                $this->warn("  실패: {$result['failed_count']}개");
                foreach ($result['failed'] as $sid => $msg) {
                    $this->line("    - [{$sid}] {$msg}");
                }
            }
            if ($result['tokens_in'] > 0) {
                $this->line("  모델: {$result['model']}");
                $this->line("  토큰: in={$result['tokens_in']}, out={$result['tokens_out']}");
                $this->line("  비용: $" . number_format($result['cost'], 4));
            }

        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function generateSingle(int $projectId, string $screenSid, int $userId): int
    {
        $screen = AiAgentScreen::where('project_id', $projectId)
            ->where('screen_id', $screenSid)->first();

        if (!$screen) {
            $this->error("화면 ID {$screenSid}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $this->line("화면: [{$screen->screen_id}] {$screen->title}");
        $this->line('코드 생성 중 (AI 호출)...');

        try {
            $result = $this->service->generateForScreen($projectId, $screen, $userId);

            $this->info("생성 완료!");
            $this->line("  파일 수: {$result['files_count']}개");
            $this->line("  산출물 ID: {$result['artifact']->id} (v{$result['artifact']->version})");
            $this->line("  모델: {$result['model']}");
            $this->line("  토큰: in={$result['tokens_in']}, out={$result['tokens_out']}");
            $this->line("  비용: $" . number_format($result['cost'], 4));

            $decoded = json_decode($result['artifact']->content, true);
            $this->newLine();
            $this->line('생성된 파일:');
            foreach ($decoded['files'] ?? [] as $file) {
                $lines = substr_count($file['content'], "\n") + 1;
                $this->line("  📄 {$file['path']} ({$lines}줄) — {$file['purpose']}");
            }

        } catch (\Throwable $e) {
            $this->error("생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
