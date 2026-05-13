<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\ReleasePackageService;
use Illuminate\Console\Command;

class GenerateReleasePackageCommand extends Command
{
    protected $signature = 'ai-agent:release:package
                            {projectId : 프로젝트 ID}
                            {--output= : ZIP 저장 경로 (미지정 시 기본 storage 경로)}
                            {--user=   : 사용자 ID (미지정 시 첫 번째 사용자)}
                            {--force   : Phase 1-4 승인 여부 무시하고 강제 생성}';

    protected $description = '통합 릴리즈 패키지를 생성합니다 (T48)';

    public function __construct(
        private readonly ReleasePackageService $service,
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

        $user = $this->option('user')
            ? User::findOrFail((int) $this->option('user'))
            : (User::first() ?? throw new \RuntimeException('사용자가 없습니다.'));

        $this->info("프로젝트: {$project->name}");
        $this->newLine();

        // 사전 조건 확인
        if (!$this->option('force')) {
            $prereqs = $this->service->checkPrerequisites($project->id);
            if (!$prereqs['can_build']) {
                $this->warn('사전 조건 미충족:');
                foreach ($prereqs['items'] as $item) {
                    $icon = $item['approved'] ? '✅' : '❌';
                    $this->line("  {$icon} {$item['label']}");
                }
                $this->newLine();
                if (!$this->confirm('그래도 계속하시겠습니까?')) {
                    return self::FAILURE;
                }
            } else {
                $this->info('사전 조건 ✅ 모두 충족');
            }
        }

        $this->info('패키지 생성 중...');
        $start = microtime(true);

        try {
            $zipPath = $this->service->generatePackage($project, $user);
        } catch (\Throwable $e) {
            $this->error("생성 실패: " . $e->getMessage());
            return self::FAILURE;
        }

        if ($output = $this->option('output')) {
            copy($zipPath, $output);
            $zipPath = $output;
        }

        $elapsed = round(microtime(true) - $start, 1);
        $sizeMb  = round(filesize($zipPath) / 1024 / 1024, 2);

        $this->newLine();
        $this->info("✅ 패키지 생성 완료 ({$elapsed}초)");
        $this->line("📦 경로: {$zipPath}");
        $this->line("📊 크기: {$sizeMb} MB");

        // 폴더 구조 요약
        $nodes = $this->service->previewStructure($zipPath);
        if ($nodes) {
            $this->newLine();
            $this->info('폴더 구조:');
            foreach ($nodes as $node) {
                if ($node['type'] === 'folder') {
                    $folderSizeKb = round($node['total_size'] / 1024, 1);
                    $this->line("  📂 {$node['name']} ({$node['file_count']}개 파일, {$folderSizeKb} KB)");
                } else {
                    $fileKb = round($node['size'] / 1024, 1);
                    $this->line("  📄 {$node['name']} ({$fileKb} KB)");
                }
            }
        }

        return self::SUCCESS;
    }
}
