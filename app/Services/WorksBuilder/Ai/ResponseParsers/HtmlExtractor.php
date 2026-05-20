<?php

namespace App\Services\WorksBuilder\Ai\ResponseParsers;

/**
 * AI 응답에서 단일 HTML 문서를 추출.
 *
 * 우선 순위:
 *   1. ```html ... ``` 코드블록
 *   2. `<!DOCTYPE html>` ~ `</html>` 매칭
 *   3. 그 외 → ParseError
 */
class HtmlExtractor
{
    public function extract(string $rawResponse): string
    {
        // 1. ```html``` 코드블록
        if (preg_match('/```html\s*([\s\S]+?)\s*```/i', $rawResponse, $m)) {
            $candidate = trim($m[1]);
            if ($this->looksLikeFullHtml($candidate)) {
                return $candidate;
            }
        }

        // 2. <!DOCTYPE html> ~ </html>
        if (preg_match('/<!DOCTYPE\s+html[\s\S]+?<\/html>/i', $rawResponse, $m)) {
            return trim($m[0]);
        }

        // 3. <html> ~ </html> (DOCTYPE 누락 시)
        if (preg_match('/<html[\s\S]+?<\/html>/i', $rawResponse, $m)) {
            return trim($m[0]);
        }

        throw new \RuntimeException('HtmlExtractor: 응답에서 HTML 문서를 추출할 수 없습니다.');
    }

    private function looksLikeFullHtml(string $s): bool
    {
        return (bool) preg_match('/<\/html>/i', $s);
    }
}
