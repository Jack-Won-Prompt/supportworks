<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Agent\DesignSystemAiService;
use App\Services\Agent\DesignSystemDataContext;
use App\Services\Agent\DesignSystemTemplateService;
use Illuminate\Console\Command;

class ExportDesignSystemCommand extends Command
{
    protected $signature = 'ai-agent:design:system-export
                            {projectId : 프로젝트 ID}
                            {--format=html : 출력 형식 (html|md|both)}
                            {--output= : 출력 디렉터리 경로 (미지정 시 storage/app/exports)}
                            {--save : 산출물 DB 저장 (--user 필요)}
                            {--user= : 저장 시 사용자 ID}';

    protected $description = '디자인 시스템 문서를 HTML/Markdown으로 내보냅니다 (T33)';

    public function __construct(
        private readonly DesignSystemDataContext   $dataContext,
        private readonly DesignSystemTemplateService $templateService,
        private readonly DesignSystemAiService    $aiService,
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

        // Prerequisite check
        $this->line('데이터 상태 확인 중...');
        $status  = $this->dataContext->getDataStatus($project->id);
        $missing = $this->dataContext->getMissingRequired($project->id);

        $rows = [];
        foreach ($status as $key => $s) {
            $rows[] = [
                $s['label'],
                $s['ready'] ? '✅' : ($s['optional'] ? '⚠️ 선택' : '❌ 필수'),
                $s['ready'] ? ($s['count'] ?? 0) . ($s['optional'] && isset($s['total']) ? '/' . $s['total'] : '') : '-',
            ];
        }
        $this->table(['데이터', '상태', '수량'], $rows);

        if (!empty($missing)) {
            $this->error('필수 데이터 누락: ' . implode(', ', $missing));
            $this->line('T28(Tokens), T29(Components), T30(Layouts)를 먼저 실행하세요.');
            return self::FAILURE;
        }

        // Build context
        $this->line('데이터 컨텍스트 빌드 중...');
        $data = $this->dataContext->build($project->id);

        // Load existing ai_sections from artifact if any
        $existingArtifact = \App\Models\Agent\AiAgentArtifact::where('project_id', $project->id)
            ->where('type', \App\Enums\Agent\ArtifactType::DESIGN_SYSTEM_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $data['ai_sections']     = $existingArtifact
            ? ((is_array($existingArtifact->content) ? $existingArtifact->content : json_decode($existingArtifact->content, true))['ai_sections'] ?? [])
            : [];
        $data['flat_colors']     = DesignSystemTemplateService::flattenColors($data['tokens'] ?? []);
        $data['flat_typography'] = DesignSystemTemplateService::flattenTypography($data['tokens'] ?? []);
        $data['flat_shadows']    = DesignSystemTemplateService::flattenShadows($data['tokens'] ?? []);

        // Output path
        $outputDir = $this->option('output')
            ? rtrim($this->option('output'), '/\\')
            : storage_path('app/exports');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $slug = \Illuminate\Support\Str::slug($project->name);
        $date = now()->format('Ymd');
        $format = $this->option('format');

        $exported = [];

        if (in_array($format, ['html', 'both'])) {
            $this->line('HTML 렌더링 중...');
            $html = $this->templateService->renderHtml($data);
            $path = "{$outputDir}/design-system-{$slug}-{$date}.html";
            file_put_contents($path, $html);
            $exported[] = $path;
            $this->line("  HTML: {$path}");
        }

        if (in_array($format, ['md', 'both'])) {
            $this->line('Markdown 렌더링 중...');
            $md   = $this->templateService->renderMarkdown($data);
            $path = "{$outputDir}/design-system-{$slug}-{$date}.md";
            file_put_contents($path, $md);
            $exported[] = $path;
            $this->line("  MD: {$path}");
        }

        // Optional DB save
        if ($this->option('save')) {
            $userId = $this->option('user');
            $user   = $userId
                ? \App\Models\User::find((int) $userId)
                : \App\Models\User::first();

            if ($user) {
                $this->line('산출물 DB 저장 중...');
                $artifact = $this->aiService->saveArtifact($project, $data, $user);
                $this->line("  저장 완료 (v{$artifact->version})");
            } else {
                $this->warn('--user 옵션이 없거나 사용자를 찾을 수 없어 DB 저장을 건너뜁니다.');
            }
        }

        $this->newLine();
        $this->info("내보내기 완료: " . count($exported) . "개 파일");
        foreach ($exported as $p) {
            $this->line("  - {$p}");
        }

        return self::SUCCESS;
    }
}
