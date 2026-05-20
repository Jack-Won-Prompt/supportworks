<?php

namespace App\Services\WorksBuilder\Review;

use App\Models\WorksBuilder\GeneratedHtml;
use App\Models\WorksBuilder\ReviewSession;

/**
 * 명세 §1.6 / §1.7 핵심:
 * - 검수 시작 시 start_hash 기록, 종료 시 end_hash 검증
 * - 오버레이가 원본 HTML을 절대 변조하지 않았음을 보장
 */
class HtmlIntegrityValidator
{
    public function hash(string $html): string
    {
        return hash('sha256', $html);
    }

    public function verify(GeneratedHtml $html): string
    {
        $expected = $html->html_hash;
        $actual   = $this->hash($html->html_content);

        if (! hash_equals($expected, $actual)) {
            throw new \RuntimeException(sprintf(
                'HTML integrity check failed: expected %s, got %s',
                $expected,
                $actual
            ));
        }
        return $actual;
    }

    public function verifySession(ReviewSession $session): bool
    {
        if ($session->end_hash === null) {
            return false;
        }
        return hash_equals($session->start_hash, $session->end_hash);
    }
}
