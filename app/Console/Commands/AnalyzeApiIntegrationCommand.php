<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\ApiIntegrationService;
use Illuminate\Console\Command;

class AnalyzeApiIntegrationCommand extends Command
{
    protected $signature = 'ai-agent:dev:api-integration
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--save    : 결과를 산출물로 저장}';

    protected $description = 'Frontend ↔ Backend API 매칭을 분석합니다 (T44)';

    public function __construct(
        private readonly ApiIntegrationService $service,
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

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        try {
            $analysis = $this->service->analyze($projectId);
            $meta     = $analysis['$metadata'];

            $this->line("매칭률: <comment>{$meta['compliance_rate']}%</comment>");
            $this->newLine();

            $this->table(['항목', '개수'], [
                ['Frontend API 호출',    $meta['frontend_calls']],
                ['Backend 엔드포인트',   $meta['backend_endpoints']],
                ['매칭됨',               $meta['matched']],
                ['매칭 안 된 FE 호출',   $meta['unmatched_frontend']],
                ['매칭 안 된 BE 엔드포인트', $meta['unmatched_backend']],
            ]);

            if (!empty($analysis['unmatched_frontend'])) {
                $this->newLine();
                $this->warn("매칭 안 된 Frontend 호출 ({$meta['unmatched_frontend']}건):");
                foreach ($analysis['unmatched_frontend'] as $u) {
                    $fe = $u['frontend_call'];
                    $screen = $fe['screen_id'] ? "[{$fe['screen_id']}] " : '';
                    $this->line("  ⚠️  {$fe['method']} {$fe['url']}  — {$screen}{$fe['file']}:{$fe['line']}");
                }
            }

            if (!empty($analysis['unmatched_backend'])) {
                $this->newLine();
                $this->line("매칭 안 된 Backend 엔드포인트 ({$meta['unmatched_backend']}건):");
                foreach ($analysis['unmatched_backend'] as $u) {
                    $be = $u['backend_endpoint'];
                    $this->line("  ℹ️   {$be['method']} {$be['uri']}  ({$be['resource']})");
                }
            }

            if ($this->option('save')) {
                $userId   = $this->option('user') ? (int) $this->option('user') : (User::first()?->id ?? 1);
                $intFiles = $this->service->generateIntegrationFiles($projectId);
                $artifact = $this->service->persistResult($projectId, $analysis, $intFiles, $userId);
                $this->newLine();
                $this->info("산출물 저장됨 (ID: {$artifact->id}, v{$artifact->version})");
            }

        } catch (\Throwable $e) {
            $this->error("분석 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
