<?php

namespace App\Services\WorksBuilder\Ai\PromptBuilders;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\Task;
use Illuminate\Support\Str;

/**
 * 완료 Task 재실행(Reopen).
 *
 * 신규 Task의 parent에서 원본 최종 HTML을 가져와 "초기 입력"으로 제공하고,
 * 신규 옵션·체크리스트가 변경됐다면 그것을 반영해 다시 만든다.
 */
class ReopenPromptBuilder extends BasePromptBuilder
{
    protected function purpose(): string
    {
        return 'reopen';
    }

    protected function userPrompt(Task $task): string
    {
        $parts = [
            '# 완료 Task 재실행 요청',
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
        $parts[] = '';
        $parts[] = $this->checklistBlock($task);

        // 부모 Task의 최종 HTML
        if ($task->parent_task_id) {
            $latest = GeneratedHtml::where('task_id', $task->parent_task_id)
                ->orderByDesc('version')
                ->orderByDesc('review_round')
                ->first();
            if ($latest) {
                $parts[] = '';
                $parts[] = '## 원본 Task 최종 HTML (참고)';
                $parts[] = '```html';
                $parts[] = Str::limit($latest->html_content, 6000);
                $parts[] = '```';
            }
        }

        $parts[] = '';
        $parts[] = '## 작업';
        $parts[] = '원본 HTML의 구조·클래스명·디자인 토큰을 가능한 한 유지하면서, 위 옵션·체크리스트가 변경된 부분만 반영하여 **단일 HTML 파일**을 다시 작성하세요. HTML 외 텍스트는 출력하지 마세요.';

        return implode("\n", $parts);
    }
}
