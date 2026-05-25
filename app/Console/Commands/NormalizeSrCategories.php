<?php

namespace App\Console\Commands;

use App\Models\Maint\MaintRequest;
use Illuminate\Console\Command;

/**
 * SR 요청 category 정리 — 콜로플라스트 기준 5종 표준화.
 *
 *   에러            오류·버그·동작 안 함
 *   개선            기존 기능 변경/조정 요청
 *   추가개발        새로운 기능·메뉴·컬럼 추가
 *   데이터수정요청  데이터 삭제·복구·수정·재등록 요청
 *   재확인          확인·검토·점검 요청
 *
 * 처리 순서:
 *   1) 기존 카테고리 머지 (추가개선→개선, 데이터확인→데이터수정요청)
 *   2) LSE FREE/PAY → category 비움, PAY는 paid_dev_enabled=true
 *   3) 미지정/머지 후 null 인 행을 summary+content 키워드로 5종 분류
 *
 * 옵션:
 *   --dry-run    실제 UPDATE 없이 분포만 출력
 *   --company=ID 특정 회사만 처리 (생략 시 전체)
 */
class NormalizeSrCategories extends Command
{
    protected $signature   = 'sr:normalize-categories {--dry-run} {--company=}';
    protected $description = 'SR 요청 category 를 5종 표준 (에러/개선/추가개발/데이터수정요청/재확인) 으로 정리';

    private const STANDARD = ['에러', '개선', '추가개발', '데이터수정요청', '재확인'];

    /** 우선순위 순서 — 위에서 매칭되면 확정 */
    private const RULES = [
        // 1) 에러 — 동작 안 함, 오류, 깨짐
        '에러' => [
            '오류', '에러', '오작동', '안 됨', '안됨', '안 돼', '안돼', '안되', '안나옴', '안 나옴',
            '안보임', '안 보임', '보이지 않', '보이지않', '안떠', '안 떠', '튕기', '멈춤',
            '깨짐', '깨져', '막힘', '안열', '안 열', '안 먹', '안먹', '동작 안', '동작안',
            '작동 안', '작동안', '표시 안 됨', '표시안됨', '처리 안 됨', '처리안됨',
            '적용 안', '적용안', '실패', '누락', '빠짐', '빠져있', '빠져 있',
            '잘못 출력', '잘못 표시', '잘못나옴', '잘못 나옴', '뒤바', '중복 라인',
            '안되고', '안 되고', '오류팝업', '에러 팝업', '카운팅 안', '안잡', '안 잡',
        ],
        // 2) 데이터수정요청 — 데이터 자체 수정/삭제/복구
        '데이터수정요청' => [
            '데이터 수정', '데이터수정', '데이터 변경', '데이터변경',
            '삭제 요청', '삭제요청', '삭제 부탁', '삭제부탁',
            '복구 요청', '복구요청', '데이터 복구', '데이터복구',
            '잘못 업로드', '잘못업로드', '다시 등록', '재등록', '재 등록',
            '데이터 보정', '데이터보정', '값 수정', '값수정',
            '일괄 수정', '일괄수정', '값 변경 요청', '값변경요청',
        ],
        // 3) 추가개발 — 새 기능/메뉴/컬럼
        '추가개발' => [
            '추가 요청', '추가요청', '추가 기능', '추가기능', '신규 기능', '신규기능',
            '신규 개발', '신규개발', '추가 개발', '추가개발',
            '새 기능', '새 메뉴', '신규 메뉴', '신규메뉴',
            '컬럼 추가', '컬럼추가', '항목 추가', '항목추가', '기능 추가', '기능추가',
            '신규 화면', '신규화면', '추가 화면', '추가화면', '추가 메뉴', '추가메뉴',
            '새로 추가', '새로추가', '추가 개선',
        ],
        // 4) 재확인 — 확인·검토·점검
        '재확인' => [
            '재확인', '재 확인', '확인 부탁', '확인부탁', '확인 필요', '확인필요',
            '확인이 필요', '확인 후 진행', '확인후 진행', '확인 해주세요', '확인해주세요',
            '점검 부탁', '점검부탁', '검토 요청', '검토요청', '검토 부탁', '검토부탁',
            '확인 요청', '확인요청',
        ],
        // 5) 개선 — 기본 폴백 (변경/수정/요청 등)
        '개선' => [
            '변경 요청', '변경요청', '수정 요청', '수정요청', '개선 요청', '개선요청',
            '개선', '변경', '수정', '추가', '노출', '보이게', '가능하도록',
            '요청', '바꿔', '바꾸', '교체',
        ],
    ];

    /** 기존 라벨 머지 매핑 (5종 외 → 5종) */
    private const MERGE = [
        '추가개선'   => '개선',
        '데이터확인' => '데이터수정요청',
    ];

    public function handle(): int
    {
        $dry  = (bool) $this->option('dry-run');
        $only = $this->option('company');

        $q = MaintRequest::query();
        if ($only) $q->where('company_group_id', (int) $only);

        $total = (clone $q)->count();
        $this->info("처리 대상: {$total} 건" . ($dry ? ' (DRY RUN)' : ''));
        $this->newLine();

        // ── 0. LSE FREE/PAY 선처리 ────────────────────────────────────
        $lsePay   = (clone $q)->where('category', 'PAY')->get();
        $lseFree  = (clone $q)->where('category', 'FREE')->get();

        if ($lsePay->isNotEmpty() || $lseFree->isNotEmpty()) {
            $this->line("LSE FREE/PAY 정리 — FREE: {$lseFree->count()}건, PAY: {$lsePay->count()}건 (paid_dev_enabled=true)");
            if (! $dry) {
                foreach ($lsePay as $r) {
                    $r->paid_dev_enabled = true;
                    $r->category         = null;
                    $r->save();
                }
                foreach ($lseFree as $r) {
                    $r->category = null;
                    $r->save();
                }
            }
        }

        // ── 1. 기존 라벨 머지 ────────────────────────────────────────
        foreach (self::MERGE as $from => $to) {
            $cnt = (clone $q)->where('category', $from)->count();
            if ($cnt > 0) {
                $this->line("머지: {$from} → {$to} ({$cnt} 건)");
                if (! $dry) {
                    (clone $q)->where('category', $from)->update(['category' => $to]);
                }
            }
        }

        // ── 2. 미지정 자동 분류 ──────────────────────────────────────
        $targets = (clone $q)
            ->where(function ($w) {
                $w->whereNull('category')->orWhereNotIn('category', self::STANDARD);
            })
            ->get(['id', 'summary', 'content', 'category']);

        $this->line("자동 분류 대상: {$targets->count()} 건");
        $this->newLine();

        $distribution = array_fill_keys(self::STANDARD, 0);
        $distribution['(미분류)'] = 0;

        foreach ($targets as $r) {
            $cat = $this->classify(($r->summary ?? '') . "\n" . strip_tags($r->content ?? ''));
            $distribution[$cat ?? '(미분류)']++;
            if (! $dry && $cat !== null) {
                $r->category = $cat;
                $r->save();
            }
        }

        $this->line('── 자동 분류 결과 ──');
        foreach ($distribution as $k => $v) {
            $this->line(sprintf('  %-15s %d', $k, $v));
        }
        $this->newLine();

        // ── 3. 최종 분포 출력 ────────────────────────────────────────
        $final = (clone $q)
            ->selectRaw('category, COUNT(*) as cnt')
            ->groupBy('category')
            ->orderByDesc('cnt')
            ->get();

        $this->line('── 최종 분포 ──');
        foreach ($final as $row) {
            $this->line(sprintf('  %-20s %d', $row->category ?? '(null)', $row->cnt));
        }

        if ($dry) {
            $this->newLine();
            $this->warn('DRY RUN — 실제 DB 변경 없음. --dry-run 옵션 제거 후 재실행하세요.');
        }

        return self::SUCCESS;
    }

    /** 키워드 우선순위 매칭. 매칭 안 되면 '개선' 폴백 (요청/수정/변경 키워드라도 있을 때) */
    private function classify(string $text): ?string
    {
        if (trim($text) === '') return null;
        $t = mb_strtolower($text);

        foreach (self::RULES as $cat => $kws) {
            foreach ($kws as $kw) {
                if (mb_stripos($t, mb_strtolower($kw)) !== false) {
                    return $cat;
                }
            }
        }
        // 마지막 폴백: 아무 키워드도 안 잡히면 '개선' 으로 (요청 SR 의 일반 패턴)
        return '개선';
    }
}
