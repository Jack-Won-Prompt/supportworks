<?php

namespace App\Console\Commands;

use App\Services\WithWorks\WithWorksGitIngestService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class WithWorksSyncCommitsCommand extends Command
{
    protected $signature = 'withworks:sync-commits
                            {--days=30 : 동기화할 과거 일수 (기본 30)}
                            {--since= : ISO 형식 시작일 (--days 보다 우선)}';

    protected $description = 'WITHWORKS 저장소의 origin/master 커밋을 git_commits 테이블로 동기화';

    public function handle(WithWorksGitIngestService $svc): int
    {
        $since = $this->option('since')
            ? Carbon::parse($this->option('since'))
            : now()->subDays((int) $this->option('days'));

        $this->info('WITHWORKS 동기화 시작 — since=' . $since->toIso8601String());
        $run = $svc->sync($since);

        $this->table(
            ['Status', 'Inserted', 'Skipped', 'Branch', 'Error'],
            [[$run->status, $run->inserted, $run->skipped, $run->branch, $run->error_message ?? '']]
        );

        return $run->status === 'success' ? self::SUCCESS : self::FAILURE;
    }
}
