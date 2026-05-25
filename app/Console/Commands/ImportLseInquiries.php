<?php

namespace App\Console\Commands;

use App\Models\Maint\MaintRequest;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * 일회성: docs/LSE.sql (helpdesk.inquiries) 에서 LS일렉트릭 SR 을 maint_requests 로 적재.
 *
 * 사용:
 *   php artisan lse:import-inquiries --since=2026-03-01 --assignee=1 --cg=11
 *
 * 매핑:
 *   inquiry.id          → excel_no
 *   inquiry.title       → summary
 *   inquiry.content     → content
 *   inquiry.status      → maint_request.status (01→requested 매핑)
 *   inquiry.type        → category
 *   inquiry.created_at  → request_date + created_at
 *   inquiry.duration_to → completed_at (if 있음)
 *   진세종 (MaintUser id=1) → assignee_id
 *   LS일렉트릭 (cg id=11) → company_group_id
 */
class ImportLseInquiries extends Command
{
    protected $signature = 'lse:import-inquiries
        {--path=docs/LSE.sql : SQL 덤프 경로}
        {--since=2026-03-01 : 이 날짜 이후 created_at 만 import}
        {--assignee=1 : MaintUser id (담당자)}
        {--cg=11 : CompanyGroup id (회사)}';

    protected $description = 'LSE.sql 의 helpdesk.inquiries 를 LS일렉트릭 maint_requests 로 import';

    public function handle(): int
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $path = base_path($this->option('path'));
        if (!is_file($path)) { $this->error("파일 없음: {$path}"); return self::FAILURE; }

        $since      = Carbon::parse($this->option('since'))->startOfDay();
        $assigneeId = (int) $this->option('assignee');
        $cgId       = (int) $this->option('cg');

        $this->info("입력: {$path}");
        $this->info("범위: {$since->toDateString()} 이후");
        $this->info("담당자(maint_user.id): {$assigneeId} · 회사(cg.id): {$cgId}");

        $statusMap = [
            '01' => 'requested',
            '02' => 'in_progress',
            '03' => 'reviewing',
            '04' => 'completed',
            '05' => 'completed',
        ];

        // maint_menus 에 LSE-Import 행 firstOrCreate
        $menuId = \DB::table('maint_menus')->where('name', 'LSE-Import')->value('id');
        if (!$menuId) {
            $menuId = \DB::table('maint_menus')->insertGetId([
                'name' => 'LSE-Import', 'request_cnt' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->info("menu_id (LSE-Import): {$menuId}");

        // 전체 파일 읽고 state machine 으로 row 추출 (content 안에 줄바꿈 있어도 안전)
        $content = file_get_contents($path);
        $rows = $this->extractValueRows($content);
        $this->info("추출된 row: " . count($rows) . "건");

        $inserted = 0; $skipped = 0; $errors = 0;
        $bar = $this->output->createProgressBar(count($rows));
        $bar->setFormat('%current%/%max% [%bar%] %elapsed:6s%  ins=%ins% skip=%skip%');
        $bar->setMessage('0','ins'); $bar->setMessage('0','skip');
        $bar->start();

        // maint_requests.excel_no → maint_requests.id 매핑 (notes 외래키용)
        $excelToReqId = \App\Models\Maint\MaintRequest::where('company_group_id', $cgId)
            ->pluck('id', 'excel_no')->all();
        $notesInserted = 0;

        foreach ($rows as $rowText) {
            $fields = $this->parseSqlValues($rowText);
            // 진짜 데이터 row 만 — 첫 필드가 숫자(id)가 아니면 주석·컬럼정의·기타 noise
            if (!is_numeric(trim((string) ($fields[0] ?? '')))) continue;

            // 22 fields = inquiry_details (SR 답변/노트)
            if (count($fields) >= 21 && count($fields) <= 22) {
                $inquiryId = (int) ($fields[2] ?? 0);
                $reqId     = $excelToReqId[$inquiryId] ?? null;
                if (!$reqId) continue;   // 적재 범위 밖 inquiry
                $body      = $fields[3] ?? '';
                $userType  = $fields[4] ?? null;
                $detCreated = $fields[17] ?? null;
                $detDeleted = $fields[21] ?? null;
                if ($detDeleted !== null) continue;

                // 중복 방지 — request_id + created_at + body 길이
                $exists = \DB::table('maint_request_notes')
                    ->where('request_id', $reqId)
                    ->where('created_at', $detCreated)
                    ->exists();
                if ($exists) continue;

                \DB::table('maint_request_notes')->insert([
                    'request_id' => $reqId,
                    'note_type'  => $userType === 'M' ? 'reply' : 'comment',
                    'body'       => $body,
                    'created_at' => $detCreated ?: now(),
                    'updated_at' => $detCreated ?: now(),
                ]);
                $notesInserted++;
                $bar->advance();
                continue;
            }

            if (count($fields) < 29) continue;

            $id           = $fields[0];
            $inquiryNo    = $fields[2];
            $title        = $fields[3];
            $content      = $fields[4];
            $status       = $fields[5];
            $type         = $fields[6];
            $durationTo   = $fields[10];
            $createdAt    = $fields[24];
            $deletedAt    = $fields[28];

            if ($deletedAt !== null && $deletedAt !== 'NULL') { $skipped++; $bar->advance(); continue; }
            if ($createdAt === null) { $skipped++; $bar->advance(); continue; }

            $createdAtC = Carbon::parse($createdAt);
            if ($createdAtC->lt($since)) {
                static $firstSkip = null;
                if ($firstSkip === null) $firstSkip = $createdAtC->toDateString();
                static $maxSkip = null;
                if ($maxSkip === null || $createdAtC->gt(Carbon::parse($maxSkip))) $maxSkip = $createdAtC->toDateString();
                $skipped++; $bar->advance(); continue;
            }

            $statusFinal = $statusMap[$status] ?? 'requested';
            $completedAt = ($statusFinal === 'completed' && $durationTo) ? Carbon::parse($durationTo) : null;

            // 중복 방지 — excel_no + company_group_id
            $exists = MaintRequest::where('excel_no', (int) $id)
                ->where('company_group_id', $cgId)->exists();
            if ($exists) { $skipped++; $bar->advance(); continue; }

            try {
                MaintRequest::create([
                    'excel_no'         => (int) $id,
                    'source_sheet'     => 'LSE.sql:'.$inquiryNo,
                    'menu_id'          => $menuId,
                    'company_group_id' => $cgId,
                    'request_date'     => $createdAtC->toDateString(),
                    'priority'         => 'normal',
                    'category'         => $type ?: null,
                    'summary'          => mb_substr($title ?? '', 0, 500),
                    'content'          => $content,
                    'status'           => $statusFinal,
                    'assignee_id'      => $assigneeId,
                    'assigned_at'      => $createdAtC,
                    'completed_at'     => $completedAt,
                ]);
                $inserted++;
                $bar->setMessage((string) $inserted, 'ins');
            } catch (\Throwable $e) {
                $errors++;
                static $shownErr = false;
                if (!$shownErr) {
                    $this->newLine();
                    $this->warn('first error: ' . $e->getMessage());
                    $shownErr = true;
                }
            }
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // SQL 의 created_at 범위 진단
        $sample = collect($rows)->map(function($r) {
            $f = $this->parseSqlValues($r);
            return (is_numeric($f[0] ?? '') && !empty($f[24])) ? $f[24] : null;
        })->filter()->values();
        if ($sample->count() > 0) {
            $dates = $sample->map(fn($d) => Carbon::parse($d));
            $this->info("SQL 데이터 범위: " . $dates->min()->toDateString() . " ~ " . $dates->max()->toDateString());
        }

        $this->info("완료 — SR inserted={$inserted}, notes inserted={$notesInserted}, skipped={$skipped}, errors={$errors}");
        return self::SUCCESS;
    }

    /**
     * SQL dump 전체에서 ( ... ) row 들만 추출 (state machine — content 안 줄바꿈 안전).
     * INSERT INTO ... VALUES 키워드 이후의 ( ... ) 를 누적해서 row 단위로 끊어 반환.
     */
    private function extractValueRows(string $sql): array
    {
        $rows = [];
        $cur  = '';
        $depth = 0; $inStr = false;
        $len = strlen($sql);
        for ($i = 0; $i < $len; $i++) {
            $ch = $sql[$i];
            if ($inStr) {
                if ($ch === '\\' && $i + 1 < $len) { $cur .= $ch . $sql[$i+1]; $i++; continue; }
                if ($ch === "'") {
                    if ($i + 1 < $len && $sql[$i+1] === "'") { $cur .= "''"; $i++; continue; }
                    $inStr = false;
                }
                $cur .= $ch;
                continue;
            }
            if ($ch === "'") { $inStr = true; $cur .= $ch; continue; }
            if ($ch === '(') {
                if ($depth === 0) { $cur = ''; }
                else $cur .= $ch;
                $depth++;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                if ($depth === 0) {
                    $rows[] = $cur;
                    $cur = '';
                } else {
                    $cur .= $ch;
                }
                continue;
            }
            if ($depth > 0) $cur .= $ch;
        }
        return $rows;
    }

    /** SQL VALUES 한 행을 28개 필드로 파싱. NULL / 숫자 / 단일따옴표 escape 처리. */
    private function parseSqlValues(string $row): array
    {
        $out = []; $cur = ''; $inStr = false; $i = 0; $len = strlen($row);
        while ($i < $len) {
            $ch = $row[$i];
            if ($inStr) {
                if ($ch === '\\' && $i + 1 < $len) { $cur .= $row[$i] . $row[$i+1]; $i += 2; continue; }
                if ($ch === "'") {
                    // SQL 의 '' (두 개 단일따옴표) = escape
                    if ($i + 1 < $len && $row[$i+1] === "'") { $cur .= "'"; $i += 2; continue; }
                    $inStr = false; $i++; continue;
                }
                $cur .= $ch; $i++; continue;
            }
            if ($ch === "'") { $inStr = true; $i++; continue; }
            if ($ch === ',') {
                $out[] = $this->normalizeSqlValue($cur);
                $cur = ''; $i++;
                while ($i < $len && $row[$i] === ' ') $i++;
                continue;
            }
            $cur .= $ch; $i++;
        }
        $out[] = $this->normalizeSqlValue($cur);
        return $out;
    }

    private function normalizeSqlValue(string $raw): ?string
    {
        $raw = trim($raw);
        if ($raw === '' || strtoupper($raw) === 'NULL') return null;
        // SQL 의 \\' 또는 '' escape 복원 — 위 parser 에서 '' 만 복원했으므로 \\ 만 추가 처리
        $raw = str_replace(['\\n', '\\r', '\\t', '\\\\'], ["\n", "\r", "\t", "\\"], $raw);
        return $raw;
    }
}
