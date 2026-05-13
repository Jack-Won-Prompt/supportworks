<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Services\Agent\Figma\DesignTokenSet;

/**
 * Loads T28/T29/T30 design artifacts into a structured context
 * that the 웍스 reviewer uses as "the standard to compare against."
 */
class ReviewContextLoader
{
    // ── Public API ─────────────────────────────────────────────────────────────

    public function load(Project $project): array
    {
        $tokenArtifact     = $this->getArtifact($project, ArtifactType::DESIGN_TOKENS);
        $componentArtifact = $this->getArtifact($project, ArtifactType::COMPONENT_SPEC);
        $layoutArtifact    = $this->getArtifact($project, ArtifactType::LAYOUT_SPEC);

        $tokenSummary     = $this->buildTokenSummary($tokenArtifact);
        $componentSummary = $this->buildComponentSummary($componentArtifact);
        $layoutSummary    = $this->buildLayoutSummary($layoutArtifact);

        $mappedScreens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->whereNotNull('figma_frame_id')
            ->count();

        $totalScreens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->count();

        return [
            'has_tokens'       => $tokenArtifact !== null,
            'has_components'   => $componentArtifact !== null,
            'has_layouts'      => $layoutArtifact !== null,
            'mapped_screens'   => $mappedScreens,
            'total_screens'    => $totalScreens,
            'token_summary'    => $tokenSummary,
            'component_summary'=> $componentSummary,
            'layout_summary'   => $layoutSummary,
            'token_artifact'   => $tokenArtifact,
            'system_prompt'    => $this->buildSystemPrompt($tokenSummary, $componentSummary, $layoutSummary),
        ];
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function getArtifact(Project $project, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', $type->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    private function buildTokenSummary(?AiAgentArtifact $artifact): string
    {
        if (!$artifact || empty($artifact->content)) {
            return '(Design Token 데이터 없음 — T28 미실행)';
        }

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return '(토큰 파싱 실패)';

        $tokenSet = new DesignTokenSet();
        foreach ($data as $cat => $catData) {
            if (str_starts_with($cat, '$') || !is_array($catData)) continue;
            $this->rehydrateCategory($tokenSet, $cat, $catData, [$cat]);
        }

        $lines   = [];
        $colors  = $tokenSet->flattenCategory('color');
        $typos   = $tokenSet->flattenCategory('typography');

        $lines[] = '## Design Tokens';
        $lines[] = '';

        if (!empty($colors)) {
            $lines[] = '### 색상 토큰 (' . count($colors) . '개)';
            foreach (array_slice($colors, 0, 30) as $c) {
                $val = is_string($c['value']) ? $c['value'] : json_encode($c['value']);
                $lines[] = "- `{$c['path']}`: {$val}";
            }
            if (count($colors) > 30) $lines[] = '... (외 ' . (count($colors) - 30) . '개)';
            $lines[] = '';
        }

        if (!empty($typos)) {
            $lines[] = '### 타이포그래피 토큰 (' . count($typos) . '개)';
            foreach (array_slice($typos, 0, 20) as $t) {
                $val = is_array($t['value']) ? json_encode($t['value'], JSON_UNESCAPED_UNICODE) : (string) $t['value'];
                $lines[] = "- `{$t['path']}`: {$val}";
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function buildComponentSummary(?AiAgentArtifact $artifact): string
    {
        if (!$artifact || empty($artifact->content)) {
            return '(Component 명세 데이터 없음 — T29 미실행)';
        }

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return '(컴포넌트 파싱 실패)';

        $components = $data['components'] ?? [];
        if (empty($components)) return '(컴포넌트 목록 비어있음)';

        $lines   = ['## 표준 컴포넌트 라이브러리', ''];
        $count   = 0;

        foreach ($components as $key => $comp) {
            if ($count >= 30) { $lines[] = '... (외 ' . (count($components) - 30) . '개)'; break; }
            $type     = $comp['type'] ?? 'component';
            $name     = $comp['name'] ?? $key;
            $variants = isset($comp['props']) ? implode(', ', array_map(
                fn($p, $v) => "{$p}: [" . implode(', ', (array) $v) . ']',
                array_keys($comp['props']),
                array_values($comp['props'])
            )) : '';

            $badge = $type === 'component_set' ? '(Set)' : '(단일)';
            $lines[] = "- **{$name}** {$badge}" . ($variants ? " — {$variants}" : '');
            $count++;
        }

        return implode("\n", $lines);
    }

    private function buildLayoutSummary(?AiAgentArtifact $artifact): string
    {
        if (!$artifact || empty($artifact->content)) {
            return '(Layout 데이터 없음 — T30 미실행)';
        }

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        if (!$data) return '(레이아웃 파싱 실패)';

        $standards = $data['standard_layouts'] ?? [];
        if (empty($standards)) return '(표준 레이아웃 없음)';

        $lines = ['## 표준 레이아웃', ''];

        foreach ($standards as $key => $layout) {
            $spec    = $layout['spec'] ?? [];
            $name    = $layout['name'] ?? $key;
            $cols    = $spec['columns'] ?? 'N/A';
            $gutter  = $spec['gutter_size'] ?? 'N/A';
            $margin  = $spec['offset'] ?? 'N/A';
            $bp      = $spec['breakpoint'] ?? '';
            $usage   = $layout['usage_count'] ?? 0;
            $lines[] = "- **{$name}** ({$bp}): {$cols}컬럼, 거터 {$gutter}px, 여백 {$margin}px — {$usage}개 화면에서 사용";
        }

        $spacing = $data['spacing_scale'] ?? [];
        if (!empty($spacing)) {
            $lines[] = '';
            $lines[] = '### 표준 간격 값';
            $vals = array_map(fn($v) => "{$v}px", array_column($spacing, 'value'));
            $lines[] = '- ' . implode(', ', array_slice($vals, 0, 10));
        }

        return implode("\n", $lines);
    }

    private function buildSystemPrompt(string $tokenSummary, string $componentSummary, string $layoutSummary): string
    {
        return <<<PROMPT
당신은 시니어 UX/UI 디자이너이자 디자인 시스템 전문가입니다.

주어진 Figma 화면 이미지와 컨텍스트를 분석하여 프로젝트 디자인 표준 준수 여부를 검수합니다.

---

# 프로젝트 디자인 표준

{$tokenSummary}

{$componentSummary}

{$layoutSummary}

---

## 검수 카테고리

1. **color** — 정의된 색상 토큰 이외의 색상 사용 여부
2. **typography** — 정의된 타이포그래피 토큰 이외의 폰트/사이즈/굵기 사용 여부
3. **component** — 표준 컴포넌트 라이브러리 사용 여부 (유사한 요소를 직접 구현한 경우)
4. **layout** — 표준 그리드/컬럼/간격 시스템 준수 여부

## 위반 심각도

- **critical** — 명확한 표준 위반 (예: 토큰에 없는 색상, 임의 폰트)
- **warning** — 권고 위반 (예: 표준 컴포넌트 미사용, 비표준 간격)
- **info** — 참고 사항 (최적화 제안, 일관성 개선)

## 작성 원칙

- **구체적**: "primary 버튼에 #FF1234 사용 → color.primary.500 권장"처럼 명시
- **실행 가능**: 어떻게 수정해야 할지 명시
- **균형적**: 잘된 점도 1~3개 strengths에 기록
- **정직**: 확실하지 않은 것은 info로 처리

화면 이미지가 제공된 경우 시각적으로도 검수하세요.
`record_screen_review` 도구를 반드시 사용하여 구조화된 결과를 응답해주세요.
PROMPT;
    }

    /**
     * Reconstructs a DesignTokenSet from persisted JSON data.
     */
    private function rehydrateCategory(DesignTokenSet $tokenSet, string $cat, array $data, array $path): void
    {
        if (isset($data['$value'])) {
            $tokenSet->addToken($cat, array_slice($path, 1), $data['$value'], $data['$type'] ?? 'unknown');
            return;
        }
        foreach ($data as $key => $child) {
            if (str_starts_with((string) $key, '$') || !is_array($child)) continue;
            $this->rehydrateCategory($tokenSet, $cat, $child, [...$path, $key]);
        }
    }
}
