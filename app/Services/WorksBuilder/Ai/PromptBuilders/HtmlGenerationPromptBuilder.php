<?php

namespace App\Services\WorksBuilder\Ai\PromptBuilders;

use App\Models\WorksBuilder\Task;

/**
 * 초기 HTML 생성 (1차).
 *
 * 옵션 + 기획서(모드 B) + 체크리스트 → HTML 1개 출력 요청.
 */
class HtmlGenerationPromptBuilder extends BasePromptBuilder
{
    protected function purpose(): string
    {
        return 'html_generation';
    }

    protected function userPrompt(Task $task): string
    {
        $parts = [
            '# 표준 HTML 생성 요청',
            '',
            $this->contextBlock($task),
        ];

        $plan = $this->planBlock($task);
        if ($plan !== '') {
            $parts[] = '';
            $parts[] = $plan;
        }

        $parts[] = '';
        $parts[] = $this->optionsBlock($task->currentOption);

        $theme = $this->themeBlock($task);
        if ($theme !== '') {
            $parts[] = '';
            $parts[] = $theme;
        }

        $parts[] = '';
        $parts[] = $this->checklistBlock($task);
        $parts[] = '';
        $parts[] = '## 작업';
        $parts[] = '위 조건을 만족하는 **단일 HTML 파일**을 한 번에 작성하세요. 출력은 HTML 자체 외 어떤 텍스트도 포함하지 마세요.';

        return implode("\n", $parts);
    }
}
