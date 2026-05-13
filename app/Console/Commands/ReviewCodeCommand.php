<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\CodeReviewService;
use Illuminate\Console\Command;

class ReviewCodeCommand extends Command
{
    protected $signature = 'ai-agent:dev:code-review
                            {projectId : 프로젝트 ID}
                            {--screens=  : 쉼표 구분 화면 ID (예: 1,2,3). 미지정 시 전체}
                            {--system-only : 시스템 종합 리뷰만 실행}
                            {--user=     : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = 'Frontend + Backend 통합 AI 코드 리뷰를 실행합니다 (T45)';

    public function __construct(
        private readonly CodeReviewService $service,
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

        try {
            if ($this->option('system-only')) {
                return $this->runSystemOnly($project, $userId);
            }

            $screenIds = null;
            if ($this->option('screens')) {
                $screenIds = array_map('intval', explode(',', $this->option('screens')));
            }

            $this->info('코드 리뷰 시작...');
            $this->newLine();

            $this->service->runBatch(
                project:   $project,
                userId:    $userId,
                onEvent:   function (string $event, array $data) {
                    match ($event) {
                        'screen_start'  => $this->line("  ▶ [{$data['screen_id']}] {$data['title']}"),
                        'screen_done'   => $this->line("  ✓ [{$data['screen_id']}] {$data['compliance_score']}점, 추가발견 {$data['findings_count']}건"),
                        'screen_error'  => $this->warn("  ✗ [{$data['screen_id']}] {$data['error']}"),
                        'system_start'  => $this->line("  ▶ 시스템 종합 리뷰 중..."),
                        'system_done'   => $this->info("  ✓ 시스템 종합 — {$data['overall_score']}점"),
                        'complete'      => $this->info("완료: {$data['done']}개 처리, {$data['failed']}개 실패"),
                        default         => null,
                    };
                },
                screenIds: $screenIds,
            );

        } catch (\Throwable $e) {
            $this->error("실행 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function runSystemOnly(Project $project, int $userId): int
    {
        $this->info('화면별 리뷰 결과를 로드하여 시스템 종합 리뷰를 실행합니다...');

        $screenArtifacts = \App\Models\Agent\AiAgentArtifact::where('project_id', $project->id)
            ->where('type', \App\Enums\Agent\ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'screen')
            ->get();

        if ($screenArtifacts->isEmpty()) {
            $this->error('화면별 리뷰 결과가 없습니다. 먼저 전체 리뷰를 실행하세요.');
            return self::FAILURE;
        }

        $screenReviews = $screenArtifacts
            ->map(fn($a) => json_decode($a->content, true) ?? [])
            ->filter()
            ->values()
            ->all();

        $result = $this->service->reviewSystem($project, $screenReviews, $userId);

        $this->info("시스템 종합 점수: {$result['overall_score']}/100");
        $this->info("산출물 저장됨 (ID: {$result['artifact']->id}, v{$result['artifact']->version})");

        return self::SUCCESS;
    }
}
