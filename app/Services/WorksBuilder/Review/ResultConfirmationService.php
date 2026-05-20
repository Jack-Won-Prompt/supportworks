<?php

namespace App\Services\WorksBuilder\Review;

use App\Models\User;
use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\ResultConfirmation;
use App\Models\WorksBuilder\Task;

/**
 * 명세 v11 §1.5 — AI 생성 결과 1차 확인.
 *
 * v11에선 HTML은 사용자가 업로드하지 않고 GenerateHtmlJob 등이 생성한다.
 * 본 서비스는 결정(재생성/검수 진행)만 기록.
 */
class ResultConfirmationService
{
    public function decide(Task $task, GeneratedHtml $html, string $decision, ?string $note, User $confirmer): ResultConfirmation
    {
        if (!in_array($decision, ['regenerate', 'proceed_to_review'], true)) {
            throw new \InvalidArgumentException("invalid decision: {$decision}");
        }

        return ResultConfirmation::create([
            'task_id'           => $task->id,
            'generated_html_id' => $html->id,
            'decision'          => $decision,
            'note'              => $note,
            'confirmed_by'      => $confirmer->id,
            'confirmed_at'      => now(),
        ]);
    }
}
