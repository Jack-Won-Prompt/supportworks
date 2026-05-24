<?php

namespace App\Console\Commands;

use App\Models\GitCommit;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * 일회성: 로컬 git 클론에서 직접 커밋을 적재.
 * 첫 대용량 sync 가 GitHub API timeout 으로 어려우므로 로컬에서 한 번에 처리.
 *
 * 사용:
 *   php artisan withworks:ingest-local --path=E:\xampp\htdocs\withworks --since=30
 *
 * 이후 증분은 기존 WithWorksGitIngestService (GitHub API) 가 처리.
 */
class WithWorksIngestLocal extends Command
{
    protected $signature = 'withworks:ingest-local
        {--path=E:\\xampp\\htdocs\\withworks : 로컬 git 클론 경로}
        {--from= : 절대 날짜 이후만 적재 (예: 2026-01-01). 미지정 시 올해 1월 1일.}';

    protected $description = '로컬 withworks 클론에서 커밋을 직접 적재 (일회성 대용량 sync)';

    public function handle(): int
    {
        $path = $this->option('path');
        $from = $this->option('from') ?: (now()->year . '-01-01');   // 기본: 올해 1월 1일

        if (!is_dir($path) || !is_dir($path . DIRECTORY_SEPARATOR . '.git')) {
            $this->error("git 저장소가 아닙니다: {$path}");
            return self::FAILURE;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $this->info("로컬 저장소: {$path}");
        $this->info("범위: {$from} 이후");

        // 0) SR 담당자 이메일 → user_id 매핑 — 매칭되는 커밋만 적재
        $emailToUser = User::where('is_sr_agent', true)
            ->whereNotNull('email')
            ->pluck('id', 'email');
        if ($emailToUser->isEmpty()) {
            $this->error('SR 담당자(is_sr_agent=true)가 한 명도 없습니다. 적재 대상 없음.');
            return self::FAILURE;
        }
        // 매칭 검사는 lowercase 로 일관화
        $emailSetLower = collect($emailToUser->keys())->map(fn($e) => mb_strtolower(trim($e)))->all();
        $this->info("SR 담당자: {$emailToUser->count()}명 (커밋 author email 이 일치하는 것만 적재)");

        // 1) 브랜치 목록 + 각 브랜치의 sha 집합 (역매핑용)
        $this->info('브랜치 → 커밋 매핑 수집 중...');
        $branches = $this->runGit($path, ['for-each-ref', '--format=%(refname:short)', 'refs/heads', 'refs/remotes']);
        $branchList = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $branches)),
            fn($b) => $b !== '' && !str_starts_with($b, 'origin/HEAD')
        ));

        // 브랜치 정렬: 작업 브랜치 먼저, main/master/develop/staging/release 마지막
        $mains = ['main', 'master', 'develop', 'staging', 'release'];
        usort($branchList, function ($a, $b) use ($mains) {
            $strip = fn($x) => str_starts_with($x, 'origin/') ? substr($x, 7) : $x;
            $am = in_array($strip($a), $mains, true) || str_starts_with($strip($a), 'release/');
            $bm = in_array($strip($b), $mains, true) || str_starts_with($strip($b), 'release/');
            if ($am !== $bm) return $am ? 1 : -1;
            return strcmp($a, $b);
        });

        // sha → branches[]
        $shaToBranches = [];
        foreach ($branchList as $br) {
            $args = ['log', $br, '--format=%H', '--since=' . $from];
            $log = $this->runGit($path, $args);
            foreach (preg_split('/\R/', $log) as $sha) {
                $sha = trim($sha);
                if ($sha === '') continue;
                $label = str_starts_with($br, 'origin/') ? substr($br, 7) : $br;
                $label = mb_substr($label, 0, 100);
                if (!isset($shaToBranches[$sha])) $shaToBranches[$sha] = [];
                if (!in_array($label, $shaToBranches[$sha], true)) {
                    $shaToBranches[$sha][] = $label;
                }
            }
        }
        $this->info('적재 대상 sha: ' . count($shaToBranches) . '건');

        // 2) 모든 sha 목록 조회 (--all union, since 필터)
        $this->info('전체 sha 목록 수집 중...');
        $allShasRaw = $this->runGit($path, ['log', '--all', '--reverse', '--since=' . $from, '--format=%H']);
        $allShas = array_values(array_unique(array_filter(
            array_map('trim', preg_split('/\R/', $allShasRaw)),
            fn($s) => $s !== ''
        )));
        $this->info('처리할 sha: ' . count($allShas) . '건');

        // 3) sha-by-sha 처리 (작은 git show 호출들 — 안정적)
        $totalInserted = 0; $totalSkipped = 0; $totalSkippedNotSr = 0; $totalErrors = 0;
        $bar = $this->output->createProgressBar(count($allShas));
        $bar->setFormat('%current%/%max% [%bar%] %elapsed:6s%  ins=%ins% skip=%skip% notSr=%notSr%');
        $bar->setMessage('0', 'ins'); $bar->setMessage('0', 'skip'); $bar->setMessage('0', 'notSr');
        $bar->start();

        foreach ($allShas as $sha) {
            try {
                // 메타 한 줄
                $meta = $this->runGit($path, ['show', '--no-patch', '--format=%aI|%an|%ae|%s', $sha]);
                $meta = trim(explode("\n", $meta)[0] ?? '');
                $parts = explode('|', $meta, 4);
                if (count($parts) < 4) { $totalErrors++; $bar->advance(); continue; }
                [$date, $author, $email, $subject] = $parts;

                $authorEmailLower = $email ? mb_strtolower(trim($email)) : null;
                if (!$authorEmailLower || !in_array($authorEmailLower, $emailSetLower, true)) {
                    $totalSkippedNotSr++;
                    $bar->setMessage((string) $totalSkippedNotSr, 'notSr');
                    $bar->advance();
                    continue;
                }

                // 이미 있으면 branches 만 갱신
                $branchesForSha = $shaToBranches[$sha] ?? [];
                $existing = GitCommit::where('sha', $sha)->first();
                if ($existing) {
                    $cur = is_array($existing->branches) ? $existing->branches : [];
                    $merged = $cur;
                    foreach ($branchesForSha as $b) {
                        if (!in_array($b, $merged, true)) $merged[] = $b;
                    }
                    if ($merged !== $cur) {
                        $existing->branches = $merged;
                        $existing->save();
                    }
                    $totalSkipped++;
                    $bar->setMessage((string) $totalSkipped, 'skip');
                    $bar->advance();
                    continue;
                }

                // numstat
                $numstat = $this->runGit($path, ['show', '--numstat', '--format=', $sha]);
                $files = [];
                $add = 0; $del = 0;
                foreach (preg_split('/\R/', $numstat) as $ln) {
                    if (preg_match('/^(\d+|-)\s+(\d+|-)\s+(.+)$/', trim($ln), $m)) {
                        $a = $m[1] === '-' ? 0 : (int) $m[1];
                        $d = $m[2] === '-' ? 0 : (int) $m[2];
                        $add += $a; $del += $d;
                        $files[] = [
                            'path'      => mb_substr($m[3], 0, 500),
                            'status'    => 'modified',
                            'additions' => $a,
                            'deletions' => $d,
                        ];
                    }
                }

                GitCommit::create([
                    'source'        => 'withworks',
                    'branch'        => $branchesForSha[0] ?? null,
                    'branches'      => $branchesForSha,
                    'sha'           => $sha,
                    'author_name'   => mb_substr($author, 0, 100),
                    'author_email'  => $email,
                    'user_id'       => $emailToUser[$email] ?? $emailToUser[$authorEmailLower] ?? null,
                    'committed_at'  => Carbon::parse($date),
                    'subject'       => mb_substr($subject, 0, 1000),
                    'body'          => null,
                    'files_changed' => count($files),
                    'files_json'    => $files ? array_slice($files, 0, 50) : null,
                    'insertions'    => $add,
                    'deletions'     => $del,
                    'difficulty'    => $this->computeDifficulty($add, $del, count($files), $subject),
                ]);
                $totalInserted++;
                $bar->setMessage((string) $totalInserted, 'ins');
                $bar->advance();
            } catch (\Throwable $e) {
                $totalErrors++;
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine();

        $this->info("완료 — inserted={$totalInserted}, skipped(branches 갱신)={$totalSkipped}, not-SR(제외)={$totalSkippedNotSr}, errors={$totalErrors}, total={$bar->getMaxSteps()}");
        return self::SUCCESS;
    }

    private function runGit(string $path, array $args): string
    {
        $proc = new Process(array_merge(['git', '-C', $path], $args), null, null, null, 120);
        $proc->run();
        if (!$proc->isSuccessful()) {
            throw new \RuntimeException('git ' . implode(' ', $args) . ' 실패: ' . $proc->getErrorOutput());
        }
        return $proc->getOutput();
    }

    private function computeDifficulty(int $add, int $del, int $files, string $subject): float
    {
        $loc = $add + $del;
        $score = match(true) {
            $loc < 30 => 1.0, $loc < 100 => 2.0, $loc < 300 => 3.0, $loc < 800 => 4.0, default => 5.0,
        };
        if      ($files >= 15) $score += 1.0;
        elseif  ($files >= 6)  $score += 0.5;
        elseif  ($files >= 2)  $score += 0.3;
        $s = mb_strtolower($subject);
        foreach (['typo', 'wip', 'minor', 'docs', 'comment', 'readme', 'lint', 'format'] as $kw) {
            if (str_contains($s, $kw)) { $score -= 0.5; break; }
        }
        foreach (['security', 'vuln', 'migration', 'schema', 'breaking', 'critical', 'hotfix', 'perf', 'optimi', 'refactor'] as $kw) {
            if (str_contains($s, $kw)) { $score += 0.5; break; }
        }
        return round(max(1.0, min(5.0, $score)), 1);
    }
}
