<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Agent\ReleaseCompletionService;
use Illuminate\Console\Command;

class CheckReleaseCompletionCommand extends Command
{
    protected $signature = 'ai-agent:release:check-completion
                            {projectId : 프로젝트 ID}';

    protected $description = '릴리즈 단계 완성도를 진단합니다 (T52)';

    public function handle(ReleaseCompletionService $service): int
    {
        $projectId = (int) $this->argument('projectId');
        $project   = Project::find($projectId);

        if (!$project) {
            $this->error("프로젝트 ID {$projectId}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        $report = $service->analyze($projectId);
        $stats  = $service->collectProjectStats($projectId);

        $this->info("📊 릴리즈 단계 완성도");
        $this->line("진척도: {$report['overall_percent']}%");
        $this->line("승인 가능: " . ($report['can_request'] ? '✅ Yes' : '❌ No'));

        $this->newLine();
        $this->line("── 필수 산출물 ({$report['blocking_complete']}/{$report['blocking_total']}) ──");
        foreach ($report['blocking'] as $item) {
            $icon = $item['complete'] ? '✅' : '❌';
            $this->line("  {$icon} [{$item['source_task']}] {$item['label']}");
        }

        $this->newLine();
        $this->line("── 권장 사항 ({$report['warning_complete']}/{$report['warning_total']}) ──");
        foreach ($report['warnings'] as $item) {
            $icon = $item['complete'] ? '✅' : '⚠️';
            $this->line("  {$icon} [{$item['source_task']}] {$item['label']}");
        }

        $this->newLine();
        $this->info("📈 전체 프로젝트 종합");
        $this->table(
            ['항목', '값'],
            [
                ['Phase 완료', $stats['completed_phases'] . '/' . $stats['total_phases']],
                ['총 산출물', $stats['total_artifacts'] . '개'],
                ['총 화면', $stats['total_screens'] . '개'],
                ['총 요구사항', $stats['total_requirements'] . '개'],
                ['총 AI 호출', number_format($stats['total_ai_calls']) . '회'],
                ['총 토큰', number_format($stats['total_tokens'])],
                ['총 비용', '$' . $stats['total_cost_usd']],
                ['소요 기간', $stats['duration_days'] . '일'],
            ]
        );

        if (!empty($stats['approvals'])) {
            $this->newLine();
            $this->info("✅ 승인 이력");
            foreach ($stats['approvals'] as $a) {
                $this->line("  [{$a['approved_at']}] {$a['stage_label']} — {$a['approver']}");
            }
        }

        if ($report['can_request']) {
            $this->newLine();
            $this->info("🎊 모든 조건 충족! Phase 5 승인을 통해 프로젝트 100% 완성!");
        } else {
            $this->newLine();
            $this->warn("미완성 필수 항목: " . implode(', ', $report['missing_required']));
        }

        return self::SUCCESS;
    }
}
