<?php

namespace App\Console\Commands;

use App\Models\AiFixJob;
use Illuminate\Console\Command;

/**
 * AI Fix Job 목록을 콘솔에 표시.
 *
 * 사용 예:
 *   php artisan ai-fix:list
 *   php artisan ai-fix:list --status=blocked
 *   php artisan ai-fix:list --status=all --limit=50
 */
class AiFixListCommand extends Command
{
    protected $signature = 'ai-fix:list
                            {--status=awaiting_approval : 상태 필터 (또는 active, terminal, all)}
                            {--limit=20 : 최대 표시 개수}';

    protected $description = 'List AI fix jobs (filter by status)';

    public function handle(): int
    {
        $status = (string) $this->option('status');
        $limit  = (int) $this->option('limit');

        $query = AiFixJob::query()->with('systemErrorLog')->latest();
        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'terminal') {
            $query->terminal();
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $jobs = $query->limit($limit)->get();
        if ($jobs->isEmpty()) {
            $this->info('No jobs match.');
            return self::SUCCESS;
        }

        $rows = $jobs->map(fn(AiFixJob $j) => [
            $j->id,
            $j->status,
            $j->decision ?? '-',
            optional($j->created_at)->diffForHumans() ?? '-',
            mb_substr((string) ($j->proposed_fix_summary ?? '-'), 0, 50),
        ])->all();

        $this->table(['ID', 'Status', 'Decision', 'Created', 'Summary'], $rows);
        $this->newLine();
        $this->info("Showing {$jobs->count()} job(s) (status={$status}, limit={$limit})");

        return self::SUCCESS;
    }
}