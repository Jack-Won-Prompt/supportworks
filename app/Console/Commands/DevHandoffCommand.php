<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\DevHandoffService;
use Illuminate\Console\Command;

class DevHandoffCommand extends Command
{
    protected $signature = 'ai-agent:design:handoff
                            {projectId : 프로젝트 ID}
                            {--user=   : 사용자 ID (Figma PAT 소유자)}
                            {--validate : Dev Mode URL 유효성 검증 실행}
                            {--package  : 통합 zip 패키지 생성}
                            {--output=  : 패키지 출력 경로 (기본: storage/app/handoff/{projectId})}';

    protected $description = 'Figma Dev Mode URL 수집, 검증, 핸드오프 산출물/패키지 생성 (T34)';

    public function __construct(
        private readonly DevHandoffService $service,
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

        // Stats
        $stats = $this->service->getMappingStats($project->id);
        $this->line("매핑 현황: {$stats['mapped']}/{$stats['total']} ({$stats['percent']}%)");

        if ($stats['unmapped'] > 0) {
            $this->warn("미매핑 화면 {$stats['unmapped']}건 — 핸드오프 산출물에 미매핑으로 표시됩니다.");
        }
        $this->newLine();

        if ($stats['mapped'] === 0) {
            $this->warn('매핑된 화면이 없습니다. T31 화면 매핑을 먼저 완료하세요.');
        }

        // Resolve user
        $userId = $this->option('user');
        $user   = $userId
            ? User::find((int) $userId)
            : User::whereHas('aiAgentCredential', fn($q) => $q->whereNotNull('figma_pat'))->first();

        // URL Validation
        if ($this->option('validate')) {
            if (!$user) {
                $this->error('--validate 옵션에는 Figma PAT을 보유한 사용자가 필요합니다. --user 옵션으로 지정하세요.');
                return self::FAILURE;
            }

            $this->line('URL 유효성 검증 중...');
            $results = $this->service->validateDevModeUrls($project->id, $user);

            if (empty($results)) {
                $this->warn('검증할 매핑된 화면이 없습니다.');
            } else {
                $valid   = count(array_filter($results, fn($r) => $r['is_valid']));
                $invalid = count($results) - $valid;

                $this->table(
                    ['화면 ID', '화면명', '상태', '오류'],
                    array_map(fn($r) => [
                        $r['screen_id'],
                        mb_strimwidth($r['name'], 0, 30, '…'),
                        $r['is_valid'] ? '✅ 정상' : '❌ 오류',
                        $r['error'] ?? '',
                    ], $results)
                );

                $this->info("결과: 정상 {$valid}건 / 오류 {$invalid}건");
                $this->newLine();
            }
        }

        // Generate artifact
        if (!$user) {
            $this->warn('사용자를 찾을 수 없어 산출물 저장을 건너뜁니다.');
        } else {
            $this->line('핸드오프 산출물 생성 중...');
            $artifact = $this->service->generateHandoffArtifact($project, $user);
            $this->info("산출물 저장 완료 (ID: {$artifact->id}, v{$artifact->version})");
            $this->newLine();
        }

        // Package
        if ($this->option('package')) {
            if (!$user) {
                $this->error('--package 옵션에는 사용자 지정이 필요합니다.');
                return self::FAILURE;
            }

            $this->line('통합 패키지 생성 중...');
            $zipPath = $this->service->buildPackage($project, $user);

            if ($this->option('output')) {
                $dest = rtrim($this->option('output'), '/\\') . '/' . basename($zipPath);
                rename($zipPath, $dest);
                $zipPath = $dest;
            }

            $this->info("패키지 생성 완료: {$zipPath}");
            $this->line("  파일 크기: " . round(filesize($zipPath) / 1024, 1) . " KB");
        }

        $this->newLine();
        $this->info('T34 완료');

        return self::SUCCESS;
    }
}
