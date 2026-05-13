<?php

namespace App\Console\Commands;

use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Services\Agent\DevCompletionService;
use Illuminate\Console\Command;

class CheckDevCompletionCommand extends Command
{
    protected $signature = 'ai-agent:dev:check-completion
                            {projectId : 프로젝트 ID}';

    protected $description = '개발 단계 완성도를 진단합니다 (T47)';

    public function __construct(
        private readonly DevCompletionService $service,
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

        $stage = AiAgentProjectStage::where('project_id', $projectId)
            ->where('type', StageType::DEVELOPMENT)
            ->first();

        if (!$stage) {
            $this->error('개발 단계(DEVELOPMENT) 레코드가 없습니다.');
            return self::FAILURE;
        }

        $report = $this->service->analyze($projectId, $stage->id);

        $this->newLine();
        $this->info("📊 개발 단계 완성도 — {$project->name}");
        $this->newLine();
        $this->line("  진척도: {$report['overall_percent']}%");
        $this->line("  승인 가능: " . ($report['can_request'] ? '✅ Yes' : '❌ No'));

        if (!empty($report['missing_required'])) {
            $this->line("  미완성 필수: " . implode(', ', $report['missing_required']));
        }

        $this->newLine();
        $this->info("필수 산출물 ({$report['blocking_complete']}/{$report['blocking_total']})");
        $this->table(
            ['산출물', '상태', '상세'],
            array_map(fn($item) => [
                $item['label'] . ' [' . $item['source_task'] . ']',
                $item['complete'] ? '✅ 충족' : '❌ 미달',
                $item['note'] ?: '—',
            ], $report['blocking']),
        );

        $this->newLine();
        $this->info("권장 사항 ({$report['warning_complete']}/{$report['warning_total']})");
        $this->table(
            ['항목', '상태', '상세'],
            array_map(fn($item) => [
                $item['label'] . ' [' . $item['source_task'] . ']',
                $item['complete'] ? '✅ 충족' : '⚠️ 미달',
                $item['note'] ?: '—',
            ], $report['warnings']),
        );

        if ($report['can_request']) {
            $this->newLine();
            $this->info('🎉 모든 필수 조건 충족! 승인 요청이 가능합니다.');
        }

        return self::SUCCESS;
    }
}
