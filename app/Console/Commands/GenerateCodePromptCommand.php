<?php

namespace App\Console\Commands;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\CodeGenPromptAiService;
use Illuminate\Console\Command;

class GenerateCodePromptCommand extends Command
{
    protected $signature = 'ai-agent:dev:code-prompts-generate
                            {projectId : 프로젝트 ID}
                            {--screens=  : 화면 ID 목록 (쉼표 구분, 미지정 시 전체)}
                            {--only-missing : 프롬프트가 없는 화면만 생성}
                            {--user=     : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = '화면별 코드 생성 프롬프트를 AI로 자동 생성합니다 (T39)';

    public function __construct(
        private readonly CodeGenPromptAiService $service,
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

        $screenIds   = null;
        $onlyMissing = (bool) $this->option('only-missing');

        if ($this->option('screens')) {
            $screenIds = array_map('intval', explode(',', $this->option('screens')));
        }

        $this->info("프로젝트: {$project->name}");
        if ($onlyMissing) {
            $this->line("  모드: 프롬프트 없는 화면만 생성");
        }

        $totalScreens = AiAgentScreen::where('project_id', $projectId)->active()->count();
        $this->line("  전체 화면 수: {$totalScreens}개");
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
                    $status = match ($p['status'] ?? '') {
                        'processing' => "생성 중: [{$p['screen_id']}] {$p['title']}",
                        'done'       => "완료: [{$p['screen_id']}] {$p['title']}",
                        'failed'     => "실패: [{$p['screen_id']}] {$p['title']}",
                        default      => $p['title'] ?? '',
                    };
                    $bar->setMessage($status . " ({$p['done']}/{$p['total']})");
                },
            );

            $bar->finish();
            $this->newLine(2);

            $this->info("코드 생성 프롬프트 생성 완료!");
            $this->line("  처리 화면: {$result['total']}개");
            $this->line("  성공: " . ($result['total'] - $result['failed_count']) . "개");
            if ($result['failed_count'] > 0) {
                $this->warn("  실패: {$result['failed_count']}개");
                foreach ($result['failed'] as $screenId => $msg) {
                    $this->line("    - [{$screenId}] {$msg}");
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
}
