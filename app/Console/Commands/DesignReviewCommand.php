<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\DesignReviewService;
use App\Services\Agent\ReviewContextLoader;
use App\Services\Agent\AiDesignReviewer;
use App\Services\Agent\Figma\FigmaClientFactory;
use App\Models\Agent\AiAgentScreen;
use Illuminate\Console\Command;

class DesignReviewCommand extends Command
{
    protected $signature = 'ai-agent:design:review
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (Figma PAT 소유자, 미지정 시 첫 번째 PAT 보유자)}
                            {--screens= : 검수할 화면 ID 목록 (쉼표 구분, 예: SCR-001,SCR-002)}
                            {--dry-run : AI 호출 없이 컨텍스트만 출력}';

    protected $description = '매핑된 화면에 대해 디자인 일관성 AI 검수를 실행합니다 (T32)';

    public function __construct(
        private readonly ReviewContextLoader $contextLoader,
        private readonly AiDesignReviewer   $aiReviewer,
        private readonly DesignReviewService $reviewService,
        private readonly FigmaClientFactory  $clientFactory,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $projectId = (int) $this->argument('projectId');

        $project = Project::find($projectId);
        if (!$project) {
            $this->error("프로젝트 ID {$projectId}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        // Resolve user
        $userId = $this->option('user');
        $user   = $userId
            ? User::find((int) $userId)
            : User::whereHas('aiAgentCredential', fn($q) => $q->whereNotNull('figma_pat'))->first();

        if (!$user) {
            $this->error('Figma PAT을 보유한 사용자를 찾을 수 없습니다. --user 옵션으로 사용자 ID를 지정하세요.');
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->info("사용자: {$user->name}");
        $this->newLine();

        // Load context
        $this->line('컨텍스트 로딩 중...');
        $context = $this->contextLoader->load($project);

        $this->line("  토큰: " . ($context['has_tokens'] ? '✓' : '✗'));
        $this->line("  컴포넌트: " . ($context['has_components'] ? '✓' : '✗'));
        $this->line("  레이아웃: " . ($context['has_layouts'] ? '✓' : '✗'));
        $this->line("  매핑된 화면: {$context['mapped_screens']} / {$context['total_screens']}");
        $this->newLine();

        if ($context['mapped_screens'] === 0) {
            $this->error('매핑된 화면이 없습니다. T31 화면 매핑을 먼저 완료하세요.');
            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('--dry-run 모드: AI 검수를 실행하지 않습니다.');
            $this->newLine();
            $this->line('=== 시스템 프롬프트 미리보기 ===');
            $this->line(mb_strimwidth($context['system_prompt'] ?? '(없음)', 0, 500, '...'));
            return self::SUCCESS;
        }

        // Resolve screens to review
        $screenFilter = $this->option('screens');
        $query = AiAgentScreen::where('project_id', $project->id)
            ->whereNotNull('figma_frame_id');

        if ($screenFilter) {
            $ids = array_map('trim', explode(',', $screenFilter));
            $query->whereIn('screen_id', $ids);
        }

        $screens = $query->orderBy('screen_id')->get();

        if ($screens->isEmpty()) {
            $this->error('검수할 화면이 없습니다. (매핑된 화면 또는 지정한 화면 ID 확인 필요)');
            return self::FAILURE;
        }

        $this->info("{$screens->count()}개 화면 검수 시작...");
        $this->newLine();

        // Batch fetch Figma images grouped by file key
        $imagesByScreen = [];
        $byFileKey = $screens->filter(fn($s) => $s->figma_file_key)->groupBy('figma_file_key');

        if ($byFileKey->isNotEmpty()) {
            $this->line('Figma 이미지 로딩 중...');
            $client = $this->clientFactory->forUser($user);

            foreach ($byFileKey as $fileKey => $group) {
                $frameIds = $group->pluck('figma_frame_id')->toArray();
                try {
                    $urls = $client->getImages($fileKey, $frameIds, 'png', 0.75);
                    foreach ($group as $screen) {
                        $imagesByScreen[$screen->id] = $urls[$screen->figma_frame_id] ?? null;
                    }
                } catch (\Throwable $e) {
                    $this->warn("  파일 {$fileKey} 이미지 로딩 실패: " . $e->getMessage());
                }
            }
            $loaded = count(array_filter($imagesByScreen));
            $this->line("  {$loaded}/{$screens->count()} 이미지 로딩 완료");
            $this->newLine();
        }

        // Review each screen
        $results    = [];
        $totalIn    = 0;
        $totalOut   = 0;
        $bar        = $this->output->createProgressBar($screens->count());
        $bar->start();

        foreach ($screens as $screen) {
            try {
                $reviewResult = $this->aiReviewer->reviewScreen(
                    screen:        $screen,
                    context:       $context,
                    userId:        $user->id,
                    projectId:     $project->id,
                    figmaImageUrl: $imagesByScreen[$screen->id] ?? null,
                );

                $results[$screen->screen_id] = array_merge(
                    $reviewResult['result'],
                    ['screen_name' => $screen->title, 'figma_url' => $screen->figma_url]
                );

                $totalIn  += $reviewResult['tokensIn']  ?? 0;
                $totalOut += $reviewResult['tokensOut'] ?? 0;

                $score = $reviewResult['result']['compliance_score'] ?? 0;
                $bar->advance();
                $this->output->write("  {$screen->screen_id}: {$score}점");
            } catch (\Throwable $e) {
                $bar->advance();
                $this->output->write("  {$screen->screen_id}: 오류 - " . $e->getMessage());
            }
        }

        $bar->finish();
        $this->newLine(2);

        if (empty($results)) {
            $this->error('검수된 화면이 없습니다.');
            return self::FAILURE;
        }

        // Save artifact via DesignReviewService
        $sessionId = 'cli-' . uniqid();
        $this->reviewService->createSession($project, $user, $sessionId);

        $this->line('결과 저장 중...');
        $this->reviewService->saveReviewResults(
            project:   $project,
            user:      $user,
            sessionId: $sessionId,
            results:   $results,
            tokensIn:  $totalIn,
            tokensOut: $totalOut,
        );

        // Summary
        $scores = array_column(array_values($results), 'compliance_score');
        $avg    = count($scores) ? round(array_sum($scores) / count($scores)) : 0;

        $allViolations = array_merge(...array_map(
            fn($r) => $r['violations'] ?? [],
            array_values($results)
        ));
        $critical = count(array_filter($allViolations, fn($v) => ($v['severity'] ?? '') === 'critical'));
        $warning  = count(array_filter($allViolations, fn($v) => ($v['severity'] ?? '') === 'warning'));

        $this->info("검수 완료!");
        $this->table(
            ['항목', '값'],
            [
                ['검수 화면 수',   count($results)],
                ['평균 점수',      "{$avg}점"],
                ['총 위반 사항',   count($allViolations) . '건'],
                ['Critical',       "{$critical}건"],
                ['Warning',        "{$warning}건"],
                ['토큰 사용 (in)', number_format($totalIn)],
                ['토큰 사용 (out)', number_format($totalOut)],
            ]
        );

        return self::SUCCESS;
    }
}
