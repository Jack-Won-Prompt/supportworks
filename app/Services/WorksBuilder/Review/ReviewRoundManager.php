<?php

namespace App\Services\WorksBuilder\Review;

use App\Models\User;
use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\ReviewSession;
use App\Models\WorksBuilder\Task;
use Illuminate\Support\Facades\DB;

/**
 * 명세 §1.6: 매 검수 차수마다 별도 세션 + 무결성 검증.
 * - 1차수: review_round=1로 시작
 * - NG → 재생성 → 새 HTML 업로드 → review_round +1
 */
class ReviewRoundManager
{
    public function __construct(private HtmlIntegrityValidator $validator) {}

    public function startSession(Task $task, GeneratedHtml $html, User $reviewer): ReviewSession
    {
        return DB::transaction(function () use ($task, $html, $reviewer) {
            $round = $task->current_review_round + 1;

            $task->update([
                'current_review_round' => $round,
                'current_stage'        => 'qa_review',
            ]);

            return ReviewSession::create([
                'task_id'           => $task->id,
                'review_round'      => $round,
                'generated_html_id' => $html->id,
                'started_at'        => now(),
                'decision'          => 'pending',
                'start_hash'        => $html->html_hash,
                'reviewer_id'       => $reviewer->id,
            ]);
        });
    }

    public function endSession(ReviewSession $session, string $decision): ReviewSession
    {
        if (! in_array($decision, ['ok', 'ng'], true)) {
            throw new \InvalidArgumentException("invalid review decision: {$decision}");
        }

        $html     = $session->html;
        $endHash  = $this->validator->hash($html->html_content);
        $passed   = hash_equals($session->start_hash, $endHash);

        $session->update([
            'ended_at'         => now(),
            'decision'         => $decision,
            'end_hash'         => $endHash,
            'integrity_passed' => $passed,
        ]);

        return $session->refresh();
    }
}
