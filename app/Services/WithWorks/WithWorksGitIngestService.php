<?php

namespace App\Services\WithWorks;

use App\Models\AiSetting;
use App\Models\GitCommit;
use App\Models\GitSyncRun;
use App\Models\SystemErrorLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * WITHWORKS GitHub 저장소(dhlogitsticsPlatform/withworks) 의 main 브랜치 커밋을
 * GitHub REST API 로 가져와 git_commits 테이블에 적재.
 *
 * 토큰: AiSetting->withworks_github_token (관리자 UI 에서 입력)
 * 저장소: dhlogitsticsPlatform/withworks
 * 브랜치: main (고정)
 *
 * 동기화 전략 (idempotent):
 *  - sha unique → 중복 commit 자동 스킵
 *  - --since (기본 30일)
 *  - GET /repos/{owner}/{repo}/commits → 메타데이터 100건/page
 *  - 페이지마다 30 commit 까지 추가 stats(GET /repos/{owner}/{repo}/commits/{sha}) 호출하여 LOC 집계
 *    (대량 호출은 API rate limit 부담 → since 범위로 제한)
 */
class WithWorksGitIngestService
{
    private const OWNER  = 'dhlogitsticsPlatform';
    private const REPO   = 'withworks';
    private const SOURCE = 'withworks';
    private const API    = 'https://api.github.com';

    public function sync(?Carbon $since = null, ?Carbon $until = null, ?int $triggeredBy = null): GitSyncRun
    {
        // since 미지정 시 — DB 의 가장 최신 커밋 시각 - 1일 (안전 버퍼) 부터 증분 sync.
        // DB 가 비어있으면 30일 전부터.
        if (!$since) {
            $maxAt = GitCommit::where('source', self::SOURCE)->max('committed_at');
            $since = $maxAt ? Carbon::parse($maxAt)->subDay() : now()->subDays(30);
        }

        $run = GitSyncRun::create([
            'source'       => self::SOURCE,
            'branch'       => 'all',
            'since'        => $since,
            'until'        => $until,
            'inserted'     => 0,
            'skipped'      => 0,
            'status'       => 'running',
            'triggered_by' => $triggeredBy,
        ]);

        try {
            $token = AiSetting::current()->withWorksGithubToken();
            if (!$token) {
                throw new \RuntimeException('GitHub PAT 미설정 — 관리자 AI 설정에서 WITHWORKS GitHub 토큰을 입력하세요.');
            }

            $owner = self::OWNER;
            $repo  = self::REPO;
            $emailToUser = User::whereNotNull('email')->pluck('id', 'email');

            // 1) 모든 브랜치 목록 조회
            $branches = $this->listBranches($token, $owner, $repo);
            if (empty($branches)) {
                throw new \RuntimeException('브랜치 목록을 가져올 수 없습니다.');
            }

            $inserted = 0; $skipped = 0;

            // 2) 브랜치별로 커밋 수집 — sha unique 라 중복은 자동 스킵됨
            //    같은 sha 가 여러 브랜치에 있어도 committed_at(author date) 은 동일하므로
            //    "시작일 = 가장 이른 작성일" 은 committed_at 자체로 충족됨
            foreach ($branches as $branch) {
                $page    = 1;
                $perPage = 100;
                $maxPages = 30;

                while ($page <= $maxPages) {
                    $resp = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/vnd.github+json',
                        'X-GitHub-Api-Version' => '2022-11-28',
                    ])->timeout(30)->get(self::API . "/repos/{$owner}/{$repo}/commits", [
                        'sha'      => $branch,
                        'since'    => $since->toIso8601String(),
                        'until'    => $until?->toIso8601String(),
                        'per_page' => $perPage,
                        'page'     => $page,
                    ]);

                    if ($resp->status() === 401) {
                        throw new \RuntimeException('GitHub 인증 실패 (PAT 무효 또는 권한 부족).');
                    }
                    if ($resp->status() === 404) {
                        // 일부 브랜치가 삭제됐을 수 있음 — 다음 브랜치로
                        break;
                    }
                    if (!$resp->successful()) {
                        // 단일 브랜치 실패는 건너뛰고 진행 (다른 브랜치는 계속)
                        SystemErrorLog::record(new \RuntimeException("GitHub API {$branch} HTTP " . $resp->status()), 'warning');
                        break;
                    }

                    $items = (array) $resp->json();
                    if (empty($items)) break;

                    foreach ($items as $c) {
                        $sha = $c['sha'] ?? null;
                        if (!$sha) continue;
                        // GitHub API 가 반환하는 브랜치 이름 원본 그대로 사용 (origin/ prefix 강제 부착 안 함)
                        $branchLabel = mb_substr($branch, 0, 100);

                        // 이미 저장된 sha 면 — 새 브랜치 발견 여부만 확인 후 추가
                        $existing = GitCommit::where('sha', $sha)->first();
                        if ($existing) {
                            $branches = is_array($existing->branches) ? $existing->branches : [];
                            if (!in_array($branchLabel, $branches, true)) {
                                $branches[] = $branchLabel;
                                $existing->branches = $branches;
                                $existing->save();
                            }
                            $skipped++;
                            continue;
                        }

                        // stats 호출 (LOC + 파일 경로 목록) — 추가/삭제 라인 + 변경 파일 상세
                        $filesCount = 0; $add = 0; $del = 0; $filesList = [];
                        try {
                            $detail = Http::withHeaders([
                                'Authorization' => 'Bearer ' . $token,
                                'Accept'        => 'application/vnd.github+json',
                            ])->timeout(20)->get(self::API . "/repos/{$owner}/{$repo}/commits/{$sha}");
                            if ($detail->successful()) {
                                $d = $detail->json();
                                $add   = (int) ($d['stats']['additions'] ?? 0);
                                $del   = (int) ($d['stats']['deletions'] ?? 0);
                                if (is_array($d['files'] ?? null)) {
                                    $filesCount = count($d['files']);
                                    // 최대 50개 파일 — JSON 비대화 방지
                                    foreach (array_slice($d['files'], 0, 50) as $f) {
                                        $filesList[] = [
                                            'path'      => (string) ($f['filename'] ?? ''),
                                            'status'    => (string) ($f['status'] ?? ''),
                                            'additions' => (int)    ($f['additions'] ?? 0),
                                            'deletions' => (int)    ($f['deletions'] ?? 0),
                                        ];
                                    }
                                }
                            }
                        } catch (\Throwable) { /* stats 실패 시 0 */ }

                        $authorName  = $c['commit']['author']['name'] ?? ($c['author']['login'] ?? 'unknown');
                        $authorEmail = $c['commit']['author']['email'] ?? null;
                        $committedAt = isset($c['commit']['author']['date'])
                            ? Carbon::parse($c['commit']['author']['date'])
                            : now();
                        $message = (string) ($c['commit']['message'] ?? '');
                        $subject = explode("\n", $message)[0] ?? '';

                        GitCommit::create([
                            'source'        => self::SOURCE,
                            'branch'        => $branchLabel,
                            'branches'      => [$branchLabel],
                            'sha'           => $sha,
                            'author_name'   => mb_substr($authorName, 0, 100),
                            'author_email'  => $authorEmail,
                            'user_id'       => $authorEmail ? ($emailToUser[$authorEmail] ?? null) : null,
                            'committed_at'  => $committedAt,
                            'subject'       => mb_substr($subject, 0, 1000),
                            'body'          => null,
                            'sr_ids'        => GitCommit::parseSrIds($subject, (string) ($c['commit']['message'] ?? '')) ?: null,
                            'is_merge'      => !empty($c['parents']) && count($c['parents']) > 1,
                            'files_changed' => $filesCount,
                            'files_json'    => $filesList ?: null,
                            'insertions'    => $add,
                            'deletions'     => $del,
                            'difficulty'    => $this->computeDifficulty($add, $del, $filesCount, $subject),
                        ]);
                        $inserted++;
                    }

                    if (count($items) < $perPage) break;
                    $page++;
                }
            }

            $run->update([
                'inserted' => $inserted,
                'skipped'  => $skipped,
                'status'   => 'success',
            ]);
        } catch (\Throwable $e) {
            $run->update([
                'status'        => 'failed',
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            SystemErrorLog::record($e, 'warning');
        }

        return $run->refresh();
    }

    /**
     * 저장소의 모든 브랜치명 목록 (paginated).
     * 정렬 의도: feature/* hotfix/* fix/* 등 작업 브랜치를 먼저, main/master/develop/release 를 마지막에.
     * → branches JSON 의 [0] = 작업 브랜치(최초 커밋된 브랜치), [end] = 메인 브랜치(최후 머지된 브랜치) 가 자연스럽게 정렬됨.
     */
    private function listBranches(string $token, string $owner, string $repo): array
    {
        $all = [];
        $page = 1;
        while ($page <= 10) {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/vnd.github+json',
            ])->timeout(20)->get(self::API . "/repos/{$owner}/{$repo}/branches", [
                'per_page' => 100, 'page' => $page,
            ]);
            if (!$resp->successful()) break;
            $items = (array) $resp->json();
            if (empty($items)) break;
            foreach ($items as $b) {
                if (!empty($b['name'])) $all[] = (string) $b['name'];
            }
            if (count($items) < 100) break;
            $page++;
        }

        // 메인 계열 브랜치는 마지막으로 정렬 — 작업 브랜치가 먼저 검사돼 branches[0] 가 됨
        $mains = ['main', 'master', 'develop', 'staging', 'release'];
        usort($all, function ($a, $b) use ($mains) {
            $aMain = in_array($a, $mains, true) || str_starts_with($a, 'release/');
            $bMain = in_array($b, $mains, true) || str_starts_with($b, 'release/');
            if ($aMain !== $bMain) return $aMain ? 1 : -1;
            return strcmp($a, $b);
        });
        return $all;
    }

    /**
     * 휴리스틱 난이도 산정 (1.0 ~ 5.0).
     * - 베이스: 추가/삭제 라인 합산 → 5 구간 (1~5)
     * - 가중: 파일 수 (분산도 → 더 어려움)
     * - 키워드 보정: typo/wip/cleanup(-) 또는 security/migration/perf(+)
     */
    private function computeDifficulty(int $add, int $del, int $files, string $subject): float
    {
        $loc = $add + $del;
        $score = match(true) {
            $loc < 30   => 1.0,
            $loc < 100  => 2.0,
            $loc < 300  => 3.0,
            $loc < 800  => 4.0,
            default     => 5.0,
        };

        // 파일 수 가중
        if      ($files >= 15) $score += 1.0;
        elseif  ($files >= 6)  $score += 0.5;
        elseif  ($files >= 2)  $score += 0.3;

        // 메시지 키워드 보정
        $s = mb_strtolower($subject);
        $down = ['typo', 'wip', 'minor', 'docs', 'comment', 'readme', 'lint', 'format'];
        $up   = ['security', 'vuln', 'migration', 'schema', 'breaking', 'critical', 'hotfix', 'perf', 'optimi', 'refactor'];
        foreach ($down as $kw) if (str_contains($s, $kw)) { $score -= 0.5; break; }
        foreach ($up   as $kw) if (str_contains($s, $kw)) { $score += 0.5; break; }

        // 클램프 + 소수 첫 자리 반올림
        $score = max(1.0, min(5.0, $score));
        return round($score, 1);
    }
}
