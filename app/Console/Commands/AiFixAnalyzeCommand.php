<?php

namespace App\Console\Commands;

use App\Models\SystemErrorLog;
use App\Services\AiFix\AiFixOrchestrator;
use Illuminate\Console\Command;

/**
 * 특정 SystemErrorLog 에 대해 AI Fix 분석을 수동으로 트리거.
 *
 * 사용 예:
 *   php artisan ai-fix:analyze 123
 *
 * 멱등성: 이미 활성 job 이 있으면 그걸 반환 (재트리거되지 않음).
 */
class AiFixAnalyzeCommand extends Command
{
    protected $signature = 'ai-fix:analyze {error_id : SystemErrorLog ID}';
    protected $description = 'Manually trigger AI fix analysis for a specific system error';

    public function handle(AiFixOrchestrator $orchestrator): int
    {
        $id  = (int) $this->argument('error_id');
        $err = SystemErrorLog::find($id);
        if ($err === null) {
            $this->error("SystemErrorLog #{$id} not found");
            return self::FAILURE;
        }

        $this->info("Analyzing #{$err->id}: {$err->exception} - " . mb_substr((string) $err->message, 0, 80));

        try {
            $job = $orchestrator->analyzeError($err);
        } catch (\Throwable $e) {
            $this->error('Analysis failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->newLine();
        $this->table(['Field', 'Value'], [
            ['Job ID',      $job->id],
            ['Status',      $job->status],
            ['Decision',    $job->decision ?? '-'],
            ['Branch',      $job->branch_name ?? '-'],
            ['Blocked path',$job->blocked_path ?? '-'],
            ['Red signals',   implode(', ', $job->red_signals ?? [])    ?: '-'],
            ['Yellow signals',implode(', ', $job->yellow_signals ?? []) ?: '-'],
            ['Changed files', implode(', ', $job->changed_files ?? [])  ?: '-'],
            ['Reason',      $job->decision_reason ?? '-'],
        ]);

        return self::SUCCESS;
    }
}