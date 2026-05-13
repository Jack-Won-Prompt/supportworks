<?php

namespace App\Console\Commands;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\CodeValidationService;
use Illuminate\Console\Command;

class ValidateCodeCommand extends Command
{
    protected $signature = 'ai-agent:dev:validate-code
                            {projectId : 프로젝트 ID}
                            {--screen=  : 단일 화면 screen_id (예: SCR-001)}
                            {--screens= : 화면 DB ID 목록 (쉼표 구분)}
                            {--user=    : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--auto-fix : 자동 수정 가능한 위반 사항 자동 적용}';

    protected $description = '화면별 Frontend 코드를 AI + 정적 분석으로 자동 검증합니다 (T41)';

    public function __construct(
        private readonly CodeValidationService $service,
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

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        // Check Node.js
        $analyzer = app(\App\Services\Agent\CodeStaticAnalyzer::class);
        if ($analyzer->isAvailable()) {
            $this->line("  정적 분석: ✅ Node.js 감지됨");
        } else {
            $this->warn("  정적 분석: ⚠️ Node.js 미감지 — AI 검수만 진행");
        }
        $this->newLine();

        // Single screen mode
        if ($screenSid = $this->option('screen')) {
            return $this->validateSingle($project, $screenSid, $userId);
        }

        // Batch mode
        $screenIds = null;
        if ($this->option('screens')) {
            $screenIds = array_map('intval', explode(',', $this->option('screens')));
        }

        $bar = $this->output->createProgressBar(100);
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('시작 중...');
        $bar->start();

        $results = [];

        try {
            $this->service->runBatch(
                project:   $project,
                userId:    $userId,
                screenIds: $screenIds,
                onEvent:   function (string $event, array $data) use ($bar, &$results) {
                    if ($event === 'screen_start') {
                        $bar->setMessage("🔍 [{$data['screen_id']}] {$data['title']}");
                        $bar->setProgress($data['progress'] ?? 0);
                    } elseif ($event === 'screen_done') {
                        $icon = $data['status'] === 'done' ? '✅' : '❌';
                        $bar->setMessage("{$icon} [{$data['screen_id']}] {$data['compliance_score']}점");
                        $bar->setProgress($data['progress'] ?? 0);
                        $results[$data['screen_id']] = $data;
                    } elseif ($event === 'screen_error') {
                        $bar->setMessage("❌ [{$data['screen_id']}] 오류");
                    } elseif ($event === 'complete') {
                        $bar->setProgress(100);
                    }
                },
            );

            $bar->finish();
            $this->newLine(2);

            // Summary table
            $this->info("검증 완료!");
            $rows = [];
            foreach ($results as $sid => $r) {
                $rows[] = [
                    $sid,
                    $r['title'] ?? '',
                    ($r['compliance_score'] ?? '—') . '점',
                    $r['violations_count'] ?? '—',
                    $r['status'] === 'done' ? '✅' : '❌',
                ];
            }
            if (!empty($rows)) {
                $this->table(['화면 ID', '화면명', '점수', '위반', '상태'], $rows);
            }

            // Auto-fix pass
            if ($this->option('auto-fix')) {
                $this->newLine();
                $this->info("자동 수정 패스...");
                // Note: auto-fix requires individual violation IDs from artifacts
                $this->warn("  --auto-fix는 현재 버전에서 웹 UI를 통해 개별 적용해주세요.");
            }

        } catch (\Throwable $e) {
            $bar->finish();
            $this->newLine(2);
            $this->error("검증 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function validateSingle(Project $project, string $screenSid, int $userId): int
    {
        $screen = AiAgentScreen::where('project_id', $project->id)
            ->where('screen_id', $screenSid)->first();

        if (!$screen) {
            $this->error("화면 ID {$screenSid}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $this->line("화면: [{$screen->screen_id}] {$screen->title}");
        $this->line('코드 검증 중 (AI + 정적 분석)...');

        try {
            $result = $this->service->validateScreen($project, $screen, $userId);

            $score = $result['compliance_score'];
            $this->info("검증 완료!");
            $this->line("  점수: {$score}/100");
            $this->line("  위반: {$result['violations_count']}건");
            $this->line("  산출물 ID: {$result['artifact']->id} (v{$result['artifact']->version})");
            $this->line("  모델: {$result['model']}");
            $this->line("  토큰: in={$result['tokensIn']}, out={$result['tokensOut']}");

            $decoded    = json_decode($result['artifact']->content, true) ?? [];
            $violations = $decoded['violations'] ?? [];

            if (!empty($violations)) {
                $this->newLine();
                $this->line("위반 사항:");
                foreach ($violations as $v) {
                    $icon = match ($v['severity'] ?? 'info') {
                        'critical' => '🔴', 'warning' => '🟡', default => '🔵'
                    };
                    $file = !empty($v['file']) ? " ({$v['file']}" . (!empty($v['line']) ? ":{$v['line']}" : '') . ')' : '';
                    $fix  = !empty($v['auto_fixable']) ? ' [자동수정가능]' : '';
                    $this->line("  {$icon} [{$v['category']}] {$v['title']}{$file}{$fix}");
                }
            }

        } catch (\Throwable $e) {
            $this->error("검증 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
