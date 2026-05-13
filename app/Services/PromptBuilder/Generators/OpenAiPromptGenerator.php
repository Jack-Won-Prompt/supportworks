<?php

namespace App\Services\PromptBuilder\Generators;

use App\Models\Project;
use App\Models\PromptBuilder\Workspace;

class OpenAiPromptGenerator
{
    public function generate(array $context, array $purpose, array $analysis, array $inputSources): string
    {
        $project   = Project::find($context['project_id']);
        $workspace = Workspace::find($context['workspace_id']);

        $sections = [];
        $sections[] = $this->buildSystemContext($project, $workspace);
        $sections[] = $this->buildPriorityRules();
        $sections[] = $this->buildStandards($analysis);

        if (!empty($inputSources['figma_url'])) {
            $sections[] = $this->buildFigmaSection($inputSources, $analysis);
        }

        if (!empty($inputSources['source_files'])) {
            $sections[] = $this->buildReferenceFiles($inputSources);
        }

        $sections[] = $this->buildTask($purpose);
        $sections[] = $this->buildOutputRequirements($workspace);

        return implode("\n\n---\n\n", array_filter($sections));
    }

    private function buildSystemContext(Project $project, Workspace $workspace): string
    {
        return <<<MD
# 시스템 컨텍스트

**프로젝트**: {$project->name}
**프레임워크**: {$workspace->framework} {$workspace->framework_version}
**언어**: {$workspace->language}
**스타일링**: {$workspace->styling}

당신은 위 프로젝트의 프론트엔드 개발 전문가입니다.
MD;
    }

    private function buildPriorityRules(): string
    {
        return <<<MD
# 우선순위 규칙

1. **Layout 영역** (헤더, 사이드바): 표준 레이아웃을 절대 따른다
2. **메인 콘텐츠 영역**: 표준 컴포넌트 우선, 없으면 Figma 디자인을 따른다
3. **CSS**: 표준 CSS 토큰만 사용한다 (색상 하드코딩 금지)
4. **인터랙션**: 표준 JS 공통 함수를 재사용한다
5. **신규 컴포넌트**: `// STANDARD_CANDIDATE: [컴포넌트명]` 주석으로 표시한다
MD;
    }

    private function buildStandards(array $analysis): string
    {
        $appliedStandards = $analysis['mapping']['applied_standards'] ?? [];

        if (empty($appliedStandards)) {
            return "# 표준 자산\n(적용할 표준 없음)";
        }

        $items = [];
        foreach ($appliedStandards as $standard) {
            $items[] = "### {$standard['name']} (v{$standard['version']})\n```\n{$standard['content']}\n```";
        }

        return "# 표준 자산\n\n" . implode("\n\n", $items);
    }

    private function buildFigmaSection(array $inputSources, array $analysis): string
    {
        $url        = $inputSources['figma_url'];
        $components = $analysis['figma']['components'] ?? [];

        $componentLines = [];
        foreach ($components as $comp) {
            $status           = !empty($comp['matched_standard']) ? '✅ 표준 매칭' : '🆕 신규';
            $componentLines[] = "- {$comp['name']}: {$status}";
        }

        $componentList = implode("\n", $componentLines);

        return <<<MD
# Figma 디자인 입력

**URL**: {$url}

**컴포넌트 목록**:
{$componentList}
MD;
    }

    private function buildReferenceFiles(array $inputSources): string
    {
        $files = $inputSources['source_files'] ?? [];
        $list  = implode("\n", array_map(fn($f) => "- `{$f}`", $files));

        return "# 참조 소스 파일\n\n{$list}";
    }

    private function buildTask(array $purpose): string
    {
        $taskText = match ($purpose['purpose_type'] ?? '') {
            'standard_assets'   => '위 Figma 디자인을 분석하여 표준 자산을 생성하라.',
            'screen_generation' => '위 표준과 우선순위 규칙을 준수하여 화면을 생성하라.',
            'sequence_step'     => '시퀀스의 현재 단계 작업을 수행하라.',
            default             => '주어진 요구사항을 구현하라.',
        };

        $targets = implode(', ', $purpose['targets'] ?? []);

        return <<<MD
# 작업 지시

{$taskText}

**대상**: {$targets}

표준에 없는 신규 컴포넌트는 반드시 `// STANDARD_CANDIDATE: [컴포넌트명]` 주석을 포함하라.
MD;
    }

    private function buildOutputRequirements(Workspace $workspace): string
    {
        $extension = match ($workspace->framework) {
            'React', 'NextJS' => $workspace->language === 'typescript' ? 'tsx' : 'jsx',
            'Vue', 'Nuxt'     => 'vue',
            default           => 'js',
        };

        return <<<MD
# 출력 요구사항

- 파일 확장자: `.{$extension}`
- 컴포넌트 명명: PascalCase
- 함수 명명: camelCase
- 50줄 이상의 컴포넌트는 별도 파일로 분리
- 타입 정의 포함 (TypeScript 사용 시)
MD;
    }
}
