<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\BackendCodeAiService;
use Illuminate\Console\Command;

class GenerateBackendCodeCommand extends Command
{
    protected $signature = 'ai-agent:dev:backend-generate
                            {projectId : 프로젝트 ID}
                            {--resource= : 단일 테이블명 (예: users)}
                            {--all       : 전체 리소스 생성}
                            {--missing   : 미생성 리소스만}
                            {--user=     : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--confirm-cost : 비용 확인 없이 바로 실행}';

    protected $description = 'ERD 테이블 단위 Laravel 백엔드 코드를 AI로 자동 생성합니다 (T43)';

    public function __construct(
        private readonly BackendCodeAiService $service,
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

        $resources = $this->service->getResources($project->id);
        if (empty($resources)) {
            $this->error("ERD 테이블이 없습니다. T36에서 ERD를 먼저 생성하세요.");
            return self::FAILURE;
        }

        $this->info("ERD 테이블: " . count($resources) . "개");
        $this->newLine();

        // Single resource mode
        if ($tableName = $this->option('resource')) {
            return $this->generateSingle($project->id, $tableName, $userId);
        }

        // Batch mode
        if (!$this->option('all') && !$this->option('missing')) {
            $this->warn("--all 또는 --missing 옵션을 지정하거나 --resource=테이블명 을 사용하세요.");
            return self::FAILURE;
        }

        $onlyMissing = (bool) $this->option('missing');

        // Cost confirmation
        if (!$this->option('confirm-cost')) {
            $count     = $onlyMissing ? $this->countMissing($project->id, $resources) : count($resources);
            $estimated = round($count * 0.65, 2);
            $this->line("생성 대상: {$count}개 리소스");
            $this->line("예상 비용: \${$estimated}");
            if (!$this->confirm("계속하시겠습니까?")) {
                return self::SUCCESS;
            }
        }

        $bar = $this->output->createProgressBar(count($resources));
        $bar->setFormat('%current%/%max% [%bar%] %percent:3s%% — %message%');
        $bar->setMessage('시작 중...');
        $bar->start();

        $results = [];

        $this->service->generateBatch(
            projectId:   $project->id,
            tableNames:  null,
            onlyMissing: $onlyMissing,
            userId:      $userId,
            onProgress:  function (array $p) use ($bar, &$results) {
                if ($p['status'] === 'processing') {
                    $bar->setMessage("⏳ [{$p['table']}] {$p['resource']}");
                    $bar->advance();
                } elseif ($p['status'] === 'done') {
                    $bar->setMessage("✅ [{$p['table']}]");
                    $results[$p['table']] = $p;
                } elseif ($p['status'] === 'failed') {
                    $bar->setMessage("❌ [{$p['table']}] 오류");
                    $results[$p['table']] = $p;
                }
            },
        );

        $bar->finish();
        $this->newLine(2);
        $this->info("생성 완료!");

        $rows = [];
        foreach ($results as $tbl => $r) {
            $rows[] = [$tbl, $r['resource'] ?? '', $r['status'] === 'done' ? '✅' : '❌'];
        }
        if (!empty($rows)) {
            $this->table(['테이블', '리소스', '상태'], $rows);
        }

        return self::SUCCESS;
    }

    private function generateSingle(int $projectId, string $tableName, int $userId): int
    {
        $this->line("리소스: {$tableName}");
        $this->line('코드 생성 중...');

        try {
            $result = $this->service->generateForResource($projectId, $tableName, $userId);
            $this->info("생성 완료!");
            $this->line("  파일: {$result['files_count']}개");
            $this->line("  비용: \${$result['cost']}");
            $this->line("  모델: {$result['model']}");
            $this->line("  토큰: in={$result['tokens_in']}, out={$result['tokens_out']}");
        } catch (\Throwable $e) {
            $this->error("생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function countMissing(int $projectId, array $resources): int
    {
        $existingScopes = \App\Models\Agent\AiAgentArtifact::where('project_id', $projectId)
            ->where('type', \App\Enums\Agent\ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->pluck('scope_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        return count(array_filter(
            $resources,
            fn($r) => !in_array($this->service->getScopeId($r['table']), $existingScopes)
        ));
    }
}
