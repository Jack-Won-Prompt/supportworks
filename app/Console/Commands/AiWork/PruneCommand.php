<?php

namespace App\Console\Commands\AiWork;

use App\Models\AiWork\AiwJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * 오래된 로그 원문과 고아 diff 파일 정리.
 *
 * job 레코드 자체는 남긴다 — 감사 추적이 끊기면 안 된다. 용량을 차지하는
 * raw 페이로드와 파일만 비운다.
 */
class PruneCommand extends Command
{
    protected $signature = 'aiw:prune {--days= : 보관 일수(기본 config aiw.retention_days)}';

    protected $description = 'AI Works: 보관 기간이 지난 로그 원문과 고아 diff 파일을 정리한다';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('aiw.retention_days', 90));
        $cutoff = now()->subDays($days);

        $jobIds = AiwJob::query()
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->where('finished_at', '<', $cutoff)
            ->pluck('id');

        if ($jobIds->isEmpty()) {
            $this->info('정리할 대상이 없습니다.');

            return self::SUCCESS;
        }

        $cleared = DB::table('aiw_job_logs')
            ->whereIn('job_id', $jobIds)
            ->whereNotNull('raw')
            ->update(['raw' => null]);

        $this->info("로그 원문 {$cleared}건을 비웠습니다 ({$days}일 초과).");

        $this->pruneDiffs();

        return self::SUCCESS;
    }

    /** DB 가 더 이상 참조하지 않는 diff 파일 삭제. */
    private function pruneDiffs(): void
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('aiw/diffs')) {
            return;
        }

        $referenced = AiwJob::query()
            ->whereNotNull('git_diff_path')
            ->pluck('git_diff_path')
            ->all();

        $removed = 0;

        foreach ($disk->files('aiw/diffs') as $file) {
            if (! in_array($file, $referenced, true)) {
                $disk->delete($file);
                $removed++;
            }
        }

        if ($removed > 0) {
            $this->info("고아 diff 파일 {$removed}건을 삭제했습니다.");
        }
    }
}
