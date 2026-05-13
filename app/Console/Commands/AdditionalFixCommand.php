<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\FixOrchestrator;
use App\Services\Agent\IssueAggregator;
use Illuminate\Console\Command;

class AdditionalFixCommand extends Command
{
    protected $signature = 'ai-agent:dev:additional-fix
                            {projectId : 프로젝트 ID}
                            {--severity=all : 심각도 필터 (critical|warning|all)}
                            {--auto-fix     : 자동 수정 가능한 이슈를 일괄 수정}
                            {--reverify     : 재검증 안내 출력 (T41/T45 재실행 안내)}
                            {--user=        : 사용자 ID (미지정 시 첫 번째 사용자)}';

    protected $description = 'AI 추가 수정 대시보드 — T41+T45 이슈 집계 및 일괄 자동 수정 (T46)';

    public function __construct(
        private readonly IssueAggregator $aggregator,
        private readonly FixOrchestrator $orchestrator,
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

        $severity = $this->option('severity') ?: 'all';
        if (!in_array($severity, ['all', 'critical', 'warning'])) {
            $this->error("--severity 옵션은 all|critical|warning 중 하나여야 합니다.");
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        if ($this->option('reverify')) {
            return $this->showReverifyGuide($project);
        }

        try {
            $groups = $this->aggregator->aggregateIssues($project->id);

            if (empty($groups)) {
                $this->info('집계된 이슈가 없습니다. T41 Output 검증과 T45 AI 코드 리뷰를 먼저 실행하세요.');
                return self::SUCCESS;
            }

            $this->printStats($groups);
            $this->newLine();

            if ($this->option('auto-fix')) {
                return $this->runAutoFix($project, $userId, $severity, $groups);
            }

            $this->printGroupTable($groups, $severity);

        } catch (\Throwable $e) {
            $this->error("실행 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function printStats(array $groups): void
    {
        $total    = count($groups);
        $pending  = count(array_filter($groups, fn($g) => $g['status'] === 'pending'));
        $fixed    = count(array_filter($groups, fn($g) => $g['status'] === 'fixed'));
        $ignored  = count(array_filter($groups, fn($g) => $g['status'] === 'ignored'));
        $critical = count(array_filter($groups, fn($g) => $g['severity'] === 'critical' && $g['status'] === 'pending'));
        $autoFix  = count(array_filter($groups, fn($g) => $g['auto_fixable'] && $g['status'] === 'pending'));

        $this->line("총 그룹: {$total}건  |  미해결: {$pending}건  |  완료: {$fixed}건  |  무시: {$ignored}건");
        $this->line("Critical(미해결): {$critical}건  |  자동수정가능: {$autoFix}건");
    }

    private function printGroupTable(array $groups, string $severity): void
    {
        $filtered = array_filter($groups, function ($g) use ($severity) {
            if ($severity !== 'all' && $g['severity'] !== $severity) return false;
            return $g['status'] === 'pending';
        });

        if (empty($filtered)) {
            $this->info('조건에 해당하는 미해결 이슈가 없습니다.');
            return;
        }

        $rows = [];
        foreach ($filtered as $g) {
            $rows[] = [
                mb_substr($g['title'], 0, 40),
                $g['category'],
                strtoupper($g['severity']),
                $g['auto_fixable'] ? '가능' : '불가',
                count($g['occurrences']) . '건',
                implode(', ', array_map('strtoupper', $g['sources'])),
            ];
        }

        $this->table(
            ['제목', '카테고리', '심각도', '자동수정', '발생', '출처'],
            $rows,
        );
    }

    private function runAutoFix(Project $project, int $userId, string $severity, array $groups): int
    {
        $toFix = array_filter(
            $groups,
            fn($g) => $g['status'] === 'pending'
                && $g['auto_fixable']
                && ($severity === 'all' || $g['severity'] === $severity)
        );

        $count = count($toFix);
        if ($count === 0) {
            $this->info('자동 수정 가능한 미해결 이슈가 없습니다.');
            return self::SUCCESS;
        }

        $this->info("자동 수정 대상: {$count}개 그룹");

        if (!$this->confirm('자동 수정을 진행하시겠습니까?')) {
            $this->line('취소되었습니다.');
            return self::SUCCESS;
        }

        $this->newLine();
        $totalFixed  = 0;
        $totalFailed = 0;

        $this->orchestrator->runBatch(
            project:        $project,
            userId:         $userId,
            onEvent:        function (string $event, array $data) use (&$totalFixed, &$totalFailed) {
                match ($event) {
                    'group_start' => $this->line("  ▶ {$data['title']} ({$data['severity']})"),
                    'group_done'  => $this->line("  ✓ {$data['occurrences_fixed']}/{$data['occurrences_total']}건 수정"),
                    'group_error' => $this->warn("  ✗ {$data['error']}"),
                    'complete'    => $this->info("완료: {$data['total_fixed']}건 수정, {$data['failed_groups']}개 그룹 실패"),
                    default       => null,
                };
            },
            severityFilter: $severity,
        );

        return self::SUCCESS;
    }

    private function showReverifyGuide(Project $project): int
    {
        $this->info('재검증 안내:');
        $this->newLine();
        $this->line('  1. Output 검증 재실행:');
        $this->line('     php artisan ai-agent:dev:validate-code ' . $project->id);
        $this->newLine();
        $this->line('  2. AI 코드 리뷰 재실행:');
        $this->line('     php artisan ai-agent:dev:code-review ' . $project->id);
        $this->newLine();
        $this->line('  3. 추가 수정 재집계:');
        $this->line('     php artisan ai-agent:dev:additional-fix ' . $project->id);

        return self::SUCCESS;
    }
}
