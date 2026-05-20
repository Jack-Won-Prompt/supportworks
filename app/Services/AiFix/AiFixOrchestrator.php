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
        // 알림 발송 (null 이면 발송 생략 — 테스트 등에서 사용)
        private readonly ?AiFixNotifier $notifier = null,
    ) {}

    /** 컨테이너에서 자동 조립 (config + StubAiAnalyzer + AiFixNotifier 기본) */
    public static function default(): self
    {
        return new self(
            analyzer:  new StubAiAnalyzer(),
            evaluator: EscalationEvaluator::fromConfig(),
            notifier:  new AiFixNotifier(),
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
            hasExistingTests:         $this->hasExistingTests($analysis->changedFiles),
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

        // 정책에 해당하는 상태(awaiting_approval / blocked / …) 면 관리자에게 알림.
        // notifier 가 null 이면 침묵 (PoC/테스트 시 외부 발송 차단).
        $this->notifier?->notify($job);

        return $job->fresh();
    }

    /**
     * 관리자 승인. awaiting_approval -> applying 또는 ready_to_deploy -> deploying.
     * approvedBy 는 어떤 가드의 사용자든 id 만 받으면 됨 (admin_users.id).
     *
     * applying 진입 시 ApplyAiFixJob 자동 dispatch — worktree 생성 / 코드 적용 / 테스트 실행.
     * deploying 진입 시 DeployAiFixJob 자동 dispatch — PR 머지 / SSH deploy.sh / 헬스체크.
     * QUEUE_CONNECTION=sync 면 inline 실행.
     */
    public function approve(AiFixJob $job, int $adminUserId): AiFixJob
    {
        $next = match ($job->status) {
            AiFixJob::STATUS_AWAITING_APPROVAL => AiFixJob::STATUS_APPLYING,
            AiFixJob::STATUS_READY_TO_DEPLOY   => AiFixJob::STATUS_DEPLOYING,
            default => throw new \DomainException(
                "Cannot approve from status '{$job->status}'"
            ),
        };

        $job->transitionTo($next, [
            'approved_at'          => now(),
            'approved_by_admin_id' => $adminUserId,
        ]);

        // 다음 상태가 transient(applying/deploying)면 notifier 정책상 침묵.
        $this->notifier?->notify($job);

        // applying 진입 시 후속 파이프라인(worktree·코드 적용·테스트) 시작.
        if ($next === AiFixJob::STATUS_APPLYING) {
            \App\Jobs\ApplyAiFixJob::dispatch($job->id);
        }
        // deploying 진입 시 PR 머지 + 운영 SSH deploy.sh 시작.
        if ($next === AiFixJob::STATUS_DEPLOYING) {
            \App\Jobs\DeployAiFixJob::dispatch($job->id);
        }

        return $job;
    }

    /**
     * 관리자 거부. awaiting_approval / ready_to_deploy 에서만 허용.
     */
    public function reject(AiFixJob $job, int $adminUserId, ?string $reason = null): AiFixJob
    {
        if (!in_array($job->status, [AiFixJob::STATUS_AWAITING_APPROVAL, AiFixJob::STATUS_READY_TO_DEPLOY], true)) {
            throw new \DomainException("Cannot reject from status '{$job->status}'");
        }

        $extras = [
            'approved_by_admin_id' => $adminUserId,
            'approved_at'          => now(),
        ];
        if ($reason !== null && $reason !== '') {
            $extras['error_message'] = mb_substr($reason, 0, 500);
        }

        $job->transitionTo(AiFixJob::STATUS_REJECTED, $extras);
        $this->notifier?->notify($job);

        return $job;
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

    /**
     * 변경 파일 *전부* 에 대응되는 기존 테스트 파일이 있으면 true.
     * 매핑 규칙: app/Foo/Bar.php → tests/{Unit,Feature}/Foo/BarTest.php
     * (PSR-4 컨벤션 기반; 둘 중 하나라도 존재하면 매칭으로 본다)
     *
     * 변경 파일이 0개거나, app/ 가 아닌 파일(blade/lang 등) 이면 그 파일은 검사 대상에서 제외.
     * 모든 검사 대상 파일에 테스트가 있으면 true; 하나라도 없으면 false.
     */
    private function hasExistingTests(array $files): bool
    {
        $base = base_path();
        $relevantCount = 0;
        foreach ($files as $f) {
            $norm = str_replace('\\', '/', (string) $f);
            if (!str_starts_with($norm, 'app/')) {
                continue;
            }
            $relevantCount++;
            $rel = substr($norm, strlen('app/'));
            $rel = preg_replace('/\.php$/', '', $rel);
            $testRel = $rel . 'Test.php';
            $candidates = [
                $base . '/tests/Unit/'    . $testRel,
                $base . '/tests/Feature/' . $testRel,
            ];
            $found = false;
            foreach ($candidates as $c) {
                if (file_exists($c)) { $found = true; break; }
            }
            if (!$found) return false;
        }
        // 검사 대상이 0개면 신호 발동 안 함 (기본값 true 유지)
        return true;
    }
}
