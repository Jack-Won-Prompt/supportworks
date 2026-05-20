<?php

namespace App\Jobs;

use App\Models\SystemErrorLog;
use App\Services\AiFix\AiFixOrchestrator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SystemErrorLog 한 건에 대해 AiFixOrchestrator 를 비동기로 호출.
 *
 * SystemErrorLog::record() 의 hook 또는 ai-fix:analyze artisan 명령에서 dispatch.
 * QUEUE_CONNECTION=sync 면 즉시 inline 실행, 그 외는 큐 워커가 처리.
 *
 * 멱등성: orchestrator 가 이미 활성 job 이 있으면 그대로 반환하므로 재시도 안전.
 */
class AnalyzeSystemErrorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(public readonly int $errorLogId) {}

    public function handle(AiFixOrchestrator $orchestrator): void
    {
        $err = SystemErrorLog::find($this->errorLogId);
        if ($err === null) {
            Log::info("[AnalyzeSystemErrorJob] error #{$this->errorLogId} not found — skipping");
            return;
        }

        try {
            $orchestrator->analyzeError($err);
        } catch (\Throwable $e) {
            // 분석 실패는 로그에 남기고 더 이상 진행하지 않음.
            // 재시도(tries=2) 후에도 실패하면 Laravel 이 failed_jobs 에 기록.
            Log::error('[AnalyzeSystemErrorJob] failed: ' . $e->getMessage(), [
                'error_log_id' => $this->errorLogId,
                'exception'    => get_class($e),
            ]);
            throw $e;
        }
    }
}