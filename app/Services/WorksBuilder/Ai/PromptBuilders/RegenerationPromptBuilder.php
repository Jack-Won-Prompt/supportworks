<?php

namespace App\Services\WorksBuilder\Ai\PromptBuilders;

use App\Models\WorksBuilder\InternalPrompt;
use App\Models\WorksBuilder\NgInput;
use App\Models\WorksBuilder\Task;
use Illuminate\Support\Facades\Auth;

/**
 * NG 후 재생성 — 이전 HTML + NG 미스 입력 반영.
 *
 * build()에서 NgInput을 함께 받기 위해 본 클래스만 별도 buildWithNg() 제공.
 */
class RegenerationPromptBuilder extends BasePromptBuilder
{
    protected function purpose(): string
    {
        return 'regeneration';
    }

    public function buildWithNg(Task $task, NgInput $ng, ?int $reviewRound = null): InternalPrompt
    {
        $task->loadMissing('currentOption', 'project');

        $userPrompt = $this->buildUserPromptWithNg($task, $ng);

        return InternalPrompt::create([
            'task_id'         => $task->id,
            'purpose'         => $this->purpose(),
            'review_round'    => $reviewRound,
            'system_prompt'   => $this->systemPrompt(),
            'user_prompt'     => $userPrompt,
            'payload_metadata' => [
                'task_uuid'        => $task->task_uuid,
                'ng_input_id'      => $ng->id,
                'previous_round'   => $ng->review_round,
                'options_version'  => $task->currentOption?->version,
            ],
            'created_by'      => Auth::id(),
        ]);
    }

    /** BasePromptBuilder 추상 메서드 — 직접 build() 호출 시 안전한 fallback */
    protected function userPrompt(Task $task): string
    {
        return $this->buildUserPromptWithNg($task, null);
    }

    private function buildUserPromptWithNg(Task $task, ?NgInput $ng): string
    {
        $parts = [
            '# HTML 재생성 요청',
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

        if ($ng) {
            $parts[] = '';
            $parts[] = '## 직전 검수 결과 (NG)';
            $parts[] = "- 직전 차수: {$ng->review_round}";
            if (!empty($ng->miss_description)) {
                $parts[] = '';
                $parts[] = '### 미스 항목 설명';
                $parts[] = $ng->miss_description;
            }
            if (!empty($ng->command_box)) {
                $parts[] = '';
                $parts[] = '### 수정 지시 (담당자 명령어 박스)';
                $parts[] = $ng->command_box;
            }
            $hls = $ng->highlights_snapshot ?? [];
            if (!empty($hls)) {
                $parts[] = '';
                $parts[] = '### 담당자가 지목한 요소들';
                foreach ($hls as $h) {
                    $sel  = $h['selector_path'] ?? $h['selector'] ?? '?';
                    $text = $h['text_snippet']  ?? '';
                    $parts[] = "- {$sel}" . ($text ? " — \"{$text}\"" : '');
                }
            }
        }

        $parts[] = '';
        $parts[] = '## 작업';
        $parts[] = '위의 미스 항목을 반영하여 **단일 HTML 파일**을 다시 생성하세요. 이전과 동일한 클래스명·구조는 유지하되 지적된 부분만 수정합니다. HTML 외 텍스트는 출력하지 마세요.';

        return implode("\n", $parts);
    }
}
