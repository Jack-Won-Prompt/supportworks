<?php

namespace App\Services\PromptBuilder\Generators;

use App\Models\Project;
use App\Models\PromptBuilder\Workspace;

class CursorPromptGenerator
{
    public function generate(array $context, array $purpose, array $analysis, array $inputSources): string
    {
        $project   = Project::find($context['project_id']);
        $workspace = Workspace::find($context['workspace_id']);

        $sections = [];
        $sections[] = "# Project: {$project->name} - {$workspace->framework}";
        $sections[] = $this->buildPriorityRules();
        $sections[] = $this->buildReferenceFiles($analysis);

        if (!empty($inputSources['figma_url'])) {
            $sections[] = $this->buildFigmaSection($inputSources, $analysis);
        }

        $sections[] = $this->buildTask($purpose);
        $sections[] = $this->buildConventions($workspace);

        return implode("\n\n", array_filter($sections));
    }

    private function buildPriorityRules(): string
    {
        return <<<MD
## 우선순위 규칙
- Layout 영역은 @standards/layouts/MainLayout 그대로 사용
- 메인 영역: 표준 컴포넌트 우선, 없으면 Figma 따름
- 색상은 표준 CSS 토큰만 사용 (하드코딩 금지)
- 인터랙션은 표준 JS의 공통 함수 재사용
- 표준에 없는 컴포넌트는 // STANDARD_CANDIDATE: [이름] 주석 표시
MD;
    }

    private function buildReferenceFiles(array $analysis): string
    {
        $files = [];
        foreach ($analysis['mapping']['applied_standards'] ?? [] as $standard) {
            $type  = $standard['asset_type'];
            $name  = $standard['name'];
            $files[] = "@standards/{$type}/{$name}";
        }

        if (empty($files)) {
            return "## 참조 파일\n(적용할 표준 없음)";
        }

        return "## 참조 파일\n" . implode("\n", $files);
    }

    private function buildFigmaSection(array $inputSources, array $analysis): string
    {
        $url        = $inputSources['figma_url'];
        $components = $analysis['figma']['components'] ?? [];
        $names      = array_map(fn($c) => $c['name'], $components);

        return "## Figma 디자인\nURL: {$url}\n주요 컴포넌트: " . implode(', ', $names);
    }

    private function buildTask(array $purpose): string
    {
        $taskText = match ($purpose['purpose_type'] ?? '') {
            'standard_assets'   => '위 Figma를 분석하여 표준 자산을 생성하라.',
            'screen_generation' => '위 규칙을 준수하여 화면을 생성하라.',
            'sequence_step'     => '시퀀스의 현재 단계 작업을 수행하라.',
            default             => '주어진 요구사항을 구현하라.',
        };

        return "## 작업\n{$taskText}";
    }

    private function buildConventions(Workspace $workspace): string
    {
        $conventions = [];

        if (in_array($workspace->framework, ['React', 'NextJS'])) {
            $conventions[] = '- 함수형 컴포넌트 + Hooks';
        }
        if ($workspace->language === 'typescript') {
            $conventions[] = '- TypeScript strict mode';
        }
        if ($workspace->styling === 'tailwind') {
            $conventions[] = '- Tailwind CSS 클래스만 사용';
        }

        return "## 코드 컨벤션\n" . (implode("\n", $conventions) ?: '- 프로젝트 기본 컨벤션 따름');
    }
}
