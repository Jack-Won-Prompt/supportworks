<?php

namespace App\Services\PromptBuilder\Generators;

use App\Models\Project;
use App\Models\PromptBuilder\Workspace;

class ClaudePromptGenerator
{
    public function generate(array $context, array $purpose, array $analysis, array $inputSources): string
    {
        $project   = Project::find($context['project_id']);
        $workspace = Workspace::find($context['workspace_id']);

        $sections = [];
        $sections[] = $this->buildContextSection($project, $workspace, $purpose);
        $sections[] = $this->buildPriorityRulesSection();
        $sections[] = $this->buildStandardsSection($analysis);

        if (!empty($inputSources['figma_url']) || !empty($analysis['figma'])) {
            $sections[] = $this->buildFigmaSection($inputSources, $analysis);
        }

        if (!empty($inputSources['source_files'])) {
            $sections[] = $this->buildReferenceSection($inputSources);
        }

        $sections[] = $this->buildTaskSection($purpose);
        $sections[] = $this->buildOutputFormatSection($workspace, $purpose);

        return implode("\n\n", array_filter($sections));
    }

    private function buildContextSection(Project $project, Workspace $workspace, array $purpose): string
    {
        $purposeLabel = match ($purpose['purpose_type'] ?? '') {
            'standard_assets'   => '표준 자산 생성',
            'screen_generation' => '화면 생성',
            'sequence_step'     => '시퀀스 단계',
            default             => '일반',
        };

        return <<<XML
<context>
  <project>{$project->name}</project>
  <tech_stack>
    <framework>{$workspace->framework} {$workspace->framework_version}</framework>
    <language>{$workspace->language}</language>
    <styling>{$workspace->styling}</styling>
  </tech_stack>
  <task_type>{$purposeLabel}</task_type>
</context>
XML;
    }

    private function buildPriorityRulesSection(): string
    {
        return <<<XML
<priority_rules>
  <rule priority="1">Layout 영역(헤더, 사이드바)은 표준을 절대 따른다.</rule>
  <rule priority="2">메인 콘텐츠 영역은 표준 컴포넌트 우선, 없으면 Figma를 따른다.</rule>
  <rule priority="3">표준 CSS 토큰만 사용. 색상 하드코딩 금지.</rule>
  <rule priority="4">표준 JS의 공통 함수를 재사용한다.</rule>
  <rule priority="5">표준에 없고 Figma에만 있는 컴포넌트는 // STANDARD_CANDIDATE: [이름] 주석으로 표시한다.</rule>
</priority_rules>
XML;
    }

    private function buildStandardsSection(array $analysis): string
    {
        $appliedStandards = $analysis['mapping']['applied_standards'] ?? [];

        if (empty($appliedStandards)) {
            return "<standards>\n  <!-- 적용할 표준이 없습니다 -->\n</standards>";
        }

        $items = [];
        foreach ($appliedStandards as $standard) {
            $type    = $standard['asset_type'];
            $name    = htmlspecialchars($standard['name'], ENT_XML1);
            $version = htmlspecialchars($standard['version'] ?? '1.0.0', ENT_XML1);
            $content = htmlspecialchars($standard['content'], ENT_XML1);
            $items[] = "  <{$type} name=\"{$name}\" version=\"{$version}\">\n{$content}\n  </{$type}>";
        }

        return "<standards>\n" . implode("\n", $items) . "\n</standards>";
    }

    private function buildFigmaSection(array $inputSources, array $analysis): string
    {
        $url        = htmlspecialchars($inputSources['figma_url'] ?? '', ENT_XML1);
        $components = $analysis['figma']['components'] ?? [];

        $componentItems = [];
        foreach ($components as $comp) {
            $status = !empty($comp['matched_standard']) ? 'standard' : 'new';
            $name   = htmlspecialchars($comp['name'], ENT_XML1);
            $componentItems[] = "    <component status=\"{$status}\">{$name}</component>";
        }

        $componentsList = implode("\n", $componentItems);

        return <<<XML
<figma_input>
  <url>{$url}</url>
  <components>
{$componentsList}
  </components>
</figma_input>
XML;
    }

    private function buildReferenceSection(array $inputSources): string
    {
        $files = $inputSources['source_files'] ?? [];
        $items = array_map(fn($f) => '  <file>' . htmlspecialchars($f, ENT_XML1) . '</file>', $files);

        return "<reference_source>\n" . implode("\n", $items) . "\n</reference_source>";
    }

    private function buildTaskSection(array $purpose): string
    {
        $taskText = match ($purpose['purpose_type'] ?? '') {
            'standard_assets'   => '위 Figma 디자인을 분석하여 표준 자산을 생성하라.',
            'screen_generation' => '위 표준과 우선순위 규칙을 준수하여 화면을 생성하라.',
            'sequence_step'     => '시퀀스의 현재 단계 작업을 수행하라.',
            default             => '주어진 요구사항을 구현하라.',
        };

        return <<<XML
<task>
  {$taskText}
  Figma의 메인 영역 디자인 중 표준에 없는 컴포넌트가 있다면
  코드 주석에 // STANDARD_CANDIDATE: [컴포넌트명] 으로 표시하라.
</task>
XML;
    }

    private function buildOutputFormatSection(Workspace $workspace, array $purpose): string
    {
        $extension = match ($workspace->framework) {
            'React', 'NextJS' => $workspace->language === 'typescript' ? 'tsx' : 'jsx',
            'Vue', 'Nuxt'     => 'vue',
            'HTML'            => 'html',
            default           => 'js',
        };

        return <<<XML
<output_format>
  <file_extension>{$extension}</file_extension>
  <component_split>50줄 이상 시 별도 파일로 분리</component_split>
  <naming>
    <component>PascalCase</component>
    <function>camelCase</function>
  </naming>
</output_format>
XML;
    }
}
