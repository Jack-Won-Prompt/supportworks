<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\ScreenMappingService;
use Illuminate\Console\Command;

class AutoMapScreensCommand extends Command
{
    protected $signature = 'ai-agent:design:auto-map
                            {projectId : 프로젝트 ID}
                            {figmaFileKey : Figma 파일 키 또는 URL}
                            {--user= : 사용자 ID (PAT 소유자, 미지정 시 첫 번째 관리자)}
                            {--dry-run : 실제 매핑 없이 제안만 출력}
                            {--threshold=0.7 : 유사도 임계값 (기본: 0.7)}';

    protected $description = 'SCR-XXX 화면을 Figma 프레임에 이름 기반 자동 매핑합니다';

    public function __construct(
        private readonly ScreenMappingService $mappingService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $projectId  = (int) $this->argument('projectId');
        $figmaInput = $this->argument('figmaFileKey');

        $project = Project::find($projectId);
        if (!$project) {
            $this->error("프로젝트 ID {$projectId}를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        // Figma URL 또는 파일 키 처리
        $fileKey = strlen($figmaInput) > 22
            ? (\App\Services\Agent\Figma\FigmaUrlParser::parseFileKey($figmaInput) ?? $figmaInput)
            : $figmaInput;

        // 사용자 결정
        $userId = $this->option('user');
        $user   = $userId
            ? User::find((int) $userId)
            : User::whereHas('aiAgentCredential', fn($q) => $q->whereNotNull('figma_pat'))->first();

        if (!$user) {
            $this->error('Figma PAT을 보유한 사용자를 찾을 수 없습니다. --user 옵션으로 사용자 ID를 지정하세요.');
            return self::FAILURE;
        }

        $this->info("프로젝트: {$project->name}");
        $this->info("Figma 파일 키: {$fileKey}");
        $this->info("사용자: {$user->name}");
        $this->newLine();

        // 현재 매핑 상태
        $before = $this->mappingService->getMappingStatus($projectId);
        $this->line("현재 매핑 상태: {$before['mapped']}/{$before['total']} ({$before['percent']}%)");
        $this->newLine();

        // 제안 생성
        $this->info('매핑 제안 생성 중...');
        try {
            $suggestions = $this->mappingService->suggestMappings($projectId, $fileKey, $user);
        } catch (\Exception $e) {
            $this->error('제안 생성 실패: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (empty($suggestions)) {
            $this->warn('매핑 가능한 화면이 없습니다. (이미 모두 매핑되었거나 일치 항목 없음)');
            return self::SUCCESS;
        }

        $this->info(count($suggestions) . "개 매핑 제안:");
        $this->table(
            ['화면 ID', '화면명', 'Figma 프레임', '유사도'],
            array_map(fn($s) => [
                $s['screen_screen_id'],
                mb_strimwidth($s['screen_name'], 0, 30, '…'),
                mb_strimwidth($s['figma_frame_name'], 0, 30, '…'),
                number_format($s['similarity'] * 100, 0) . '%',
            ], $suggestions)
        );

        if ($this->option('dry-run')) {
            $this->warn('--dry-run 모드: 실제 매핑은 적용되지 않습니다.');
            return self::SUCCESS;
        }

        if (!$this->confirm('위 제안을 모두 적용하시겠습니까?', true)) {
            $this->info('취소되었습니다.');
            return self::SUCCESS;
        }

        $applied = $this->mappingService->applySuggestionsBatch($suggestions, $user);

        $after = $this->mappingService->getMappingStatus($projectId);
        $this->newLine();
        $this->info("{$applied}개 화면 매핑 완료.");
        $this->line("매핑 상태: {$after['mapped']}/{$after['total']} ({$after['percent']}%)");

        return self::SUCCESS;
    }
}
