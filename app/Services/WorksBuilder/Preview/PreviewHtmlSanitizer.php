<?php

namespace App\Services\WorksBuilder\Preview;

/**
 * srcdoc iframe 표시용 HTML 정제기.
 *
 * AI가 만든 HTML은 srcdoc로 부모와 같은 출처에서 렌더되므로
 * 안에서 `<a href="/login">` 같은 링크를 클릭하면 부모 앱(SupportWorks)으로
 * 이동해버린다. `<base href="about:blank" target="_top">`를 head에 주입해
 * 모든 상대 URL을 about:blank로 해석시키고, target="_top"을 sandbox="" 와 결합해
 * 상위 창 이동을 막아 클릭을 무력화한다.
 */
class PreviewHtmlSanitizer
{
    private const BASE_TAG = '<base href="about:blank" target="_top">';

    public static function prepareForIframe(string $html): string
    {
        // 이미 base 태그가 있으면 그대로 둠
        if (preg_match('/<base\s/i', $html)) {
            return $html;
        }

        // <head> 직후 삽입
        if (preg_match('/<head\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            return substr($html, 0, $insertAt) . self::BASE_TAG . substr($html, $insertAt);
        }

        // <head> 없으면 <html> 직후
        if (preg_match('/<html\b[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE)) {
            $insertAt = $m[0][1] + strlen($m[0][0]);
            return substr($html, 0, $insertAt) . '<head>' . self::BASE_TAG . '</head>' . substr($html, $insertAt);
        }

        // 둘 다 없으면 맨 앞에 head 통째로 추가
        return '<head>' . self::BASE_TAG . '</head>' . $html;
    }
}
