<?php

namespace App\Services\AiFix;

use App\Models\AiFixJob;
use App\Models\SystemErrorLog;

/**
 * AI 자동 수정 파이프라인의 진입점.
 *
 * 책임:
 *   1) 들어온 SystemErrorLog 에 대해 AiFixJob 을 생성 (없으면)
 *   2) 분석기 호출 → AnalysisResult
 *   3) FixContext 구성 (분석 결과 + 같은 에러 발생 횟수 + schema 변경 감지)
 *   4) EscalationEvaluator 로 결정
 *   5) 결정에 따라 상태 전이: block / awaiting_approval / auto_approved
 *
 * 책임이 아닌 것 (다음 단계에서):
 *   - worktree 생성 / 실제 코드 수정 / 테스트 실행 (applying~testing 단계)
 *   - 관리자 알림 발송 (notifyAdmins; 별도 서비스가 처리)
 *   - 배포 (deploy.sh 호출)
 *
 * 멱등성: 같은 errorLog 에 대해 아직 터미널이 아닌 job 이 있으면 그걸 그대로 반환.
 */
final class AiFixOrchestrator
{
    public function __construct(
        private readonly AiAnalyzer $analyzer,
        private readonly EscalationEvaluator $evaluator,
    ) {}

    /** 컨테이너에서 자동 조립 (config + StubAiAnalyzer 기본) */
    public static function default(): self
    {
        return new self(
            analyzer:  new StubAiAnalyzer(),
            evaluator: EscalationEvaluator::fromConfig(),
        );
    }

    public function analyzeError(SystemErrorLog $errorLog): AiFixJob
    {
        // ── 0) 멱등성 ────────────────────────────────────────────────────────
        $existing = AiFixJob::where('system_error_log_id', $errorLog->id)
            ->active()
            ->latest()
            ->first();
        if ($existing !== null) {
            return $existing;
        }

        // ── 1) job 생성 ───────────────────────────────────────────────────────
        $job = AiFixJob::create([
            'system_error_log_id' => $errorLog->id,
            'status'              => AiFixJob::STATUS_PENDING,
        ]);
        $job->transitionTo(AiFixJob::STATUS_ANALYZING);

        // ── 2) AI 분석 (현재는 stub) ──────────────────────────────────────────
        $analysis = $this->analyzer->analyze($errorLog);

        // ── 3) FixContext 구성 ────────────────────────────────────────────────
        $sameCount = $this->countRecentSameErrors($errorLog);
        $ctx = new FixContext(
            changedFiles:             $analysis->changedFiles,
            errorCategory:            $analysis->category,
            classificationConfidence: $analysis->confidence,
            sameErrorOccurrenceCount: $sameCount,
            errorBlob:                trim(($errorLog->message ?? '') . "\n" . ($errorLog->trace ?? '')),
            testsPassed:              false,    // 아직 테스트 안 돌림
            coverageDeltaLines:       0,
            aiSelfUnsure:             $analysis->unsure,
            touchesSchema:            $this->touchesSchema($analysis->changedFiles),
        );

        // ── 4) 평가 ─────────────────────────────────────────────────────────
        $decision = $this->evaluator->evaluate($ctx);

        // ── 5) 결정에 따른 상태 전이 ─────────────────────────────────────────
        $extras = [
            'decision'             => $decision->verdict,
            'red_signals'          => $decision->redSignals,
            'yellow_signals'       => $decision->yellowSignals,
            'decision_reason'      => $decision->reason,
            'blocked_path'         => $decision->blockedPath,
            'proposed_fix_summary' => $analysis->summary,
            'changed_files'        => $analysis->changedFiles,
            'branch_name'          => 'ai-fix/' . $job->id,
        ];

        $next = match ($decision->verdict) {
            EscalationDecision::BLOCK    => AiFixJob::STATUS_BLOCKED,
            EscalationDecision::ESCALATE => AiFixJob::STATUS_AWAITING_APPROVAL,
            EscalationDecision::AUTO     => AiFixJob::STATUS_AUTO_APPROVED,
            default                      => AiFixJob::STATUS_AWAITING_APPROVAL,
        };

        if ($next === AiFixJob::STATUS_AWAITING_APPROVAL) {
            $extras['escalated_at'] = now();
        }

        $job->transitionTo($next, $extras);

        return $job->fresh();
    }

    private function countRecentSameErrors(SystemErrorLog $errorLog): int
    {
        $window = (int) (config('ai-fix.signals.same_error_window_minutes') ?? 60);
        return SystemErrorLog::query()
            ->where('exception', $errorLog->exception)
            ->where('file',      $errorLog->file)
            ->where('line',      $errorLog->line)
            ->where('created_at', '>=', now()->subMinutes($window))
            ->count();
    }

    private function touchesSchema(array $files): bool
    {
        foreach ($files as $f) {
            $norm = str_replace('\\', '/', (string) $f);
            if (str_starts_with($norm, 'database/migrations/')) {
                return true;
            }
        }
        return false;
    }
}