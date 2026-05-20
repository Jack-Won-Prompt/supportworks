<?php

namespace App\Services\WorksBuilder\Packaging;

/**
 * AI 생성 HTML에서 인라인 자산을 분리.
 *
 *   <style>...</style>            → assets/css/main.css   + <link rel="stylesheet" href="assets/css/main.css">
 *   <script>...</script>          → assets/js/main.js     + <script src="assets/js/main.js"></script>
 *   <svg>...</svg> (인라인 아이콘) → assets/icon/icon-N.svg + <img src="assets/icon/icon-N.svg" ...>
 *
 * 결과 HTML은 zip을 풀어서 더블클릭으로 단독 뷰어 가능 (상대 경로 사용).
 *
 * 외부 src를 가진 <script src="...">는 분리하지 않고 그대로 둔다.
 * <svg use href="..."> 처럼 외부 참조형도 그대로 둔다 (이미 분리된 셈).
 */
class HtmlAssetSplitter
{
    /**
     * @return array{
     *   html: string,
     *   css: string|null,
     *   js: string|null,
     *   icons: array<string, string>  // 'icon-1.svg' => '<svg ...>...</svg>'
     * }
     */
    public function split(string $html): array
    {
        $css = $this->extractCss($html);   // $html 변형됨
        $js  = $this->extractJs($html);    // $html 변형됨
        $icons = $this->extractIcons($html); // $html 변형됨

        $html = $this->injectLinks($html, css: $css !== null, js: $js !== null);

        return [
            'html'  => $html,
            'css'   => $css,
            'js'    => $js,
            'icons' => $icons,
        ];
    }

    /** 인라인 <style>...</style> 모두 제거 후 묶어 반환 */
    private function extractCss(string &$html): ?string
    {
        $blocks = [];
        $html = preg_replace_callback(
            '/<style\b[^>]*>([\s\S]*?)<\/style>/i',
            function ($m) use (&$blocks) {
                $blocks[] = trim($m[1]);
                return '';
            },
            $html
        );

        if (empty($blocks)) return null;
        return implode("\n\n/* ───── */\n\n", array_filter($blocks, fn($b) => $b !== ''));
    }

    /** src 없는 인라인 <script> 추출 */
    private function extractJs(string &$html): ?string
    {
        $blocks = [];
        $html = preg_replace_callback(
            '/<script\b((?:(?!\bsrc=)[^>])*)>([\s\S]*?)<\/script>/i',
            function ($m) use (&$blocks) {
                $body = trim($m[2]);
                if ($body !== '') {
                    $blocks[] = $body;
                }
                return '';
            },
            $html
        );

        if (empty($blocks)) return null;
        return implode("\n\n/* ───── */\n\n", $blocks);
    }

    /**
     * 인라인 <svg>...</svg>을 파일로 추출하고 위치에 <img src="..."> 삽입.
     * 부모가 button/a 안의 아이콘 같은 경우 ws·class를 그대로 보존하기 어렵기 때문에
     * 가장 단순한 형태(<img src class width height alt>)로 교체한다.
     */
    private function extractIcons(string &$html): array
    {
        $icons = [];
        $idx   = 0;

        $html = preg_replace_callback(
            '/<svg\b([^>]*)>([\s\S]*?)<\/svg>/i',
            function ($m) use (&$icons, &$idx) {
                $idx++;
                $attrs = $this->parseAttributes($m[1]);
                $width  = $attrs['width']  ?? '24';
                $height = $attrs['height'] ?? '24';
                $cls    = $attrs['class']  ?? '';

                $fileName = "icon-{$idx}.svg";

                // viewBox/xmlns 없으면 보강
                $standalone = $this->normalizeStandaloneSvg($m[0]);
                $icons[$fileName] = $standalone;

                $clsAttr = $cls !== '' ? " class=\"{$cls}\"" : '';
                return sprintf(
                    '<img src="assets/icon/%s" alt=""%s width="%s" height="%s">',
                    $fileName, $clsAttr, htmlspecialchars($width), htmlspecialchars($height),
                );
            },
            $html
        );

        return $icons;
    }

    /** <head> 안에 <link> 와 </body> 직전에 <script src>를 삽입 */
    private function injectLinks(string $html, bool $css, bool $js): string
    {
        if ($css) {
            $tag = '<link rel="stylesheet" href="assets/css/main.css">';
            // </head> 직전 삽입, 없으면 <html> 다음 또는 맨 앞
            if (preg_match('/<\/head>/i', $html)) {
                $html = preg_replace('/<\/head>/i', "  {$tag}\n</head>", $html, 1);
            } else {
                $html = $tag . "\n" . $html;
            }
        }
        if ($js) {
            $tag = '<script src="assets/js/main.js" defer></script>';
            if (preg_match('/<\/body>/i', $html)) {
                $html = preg_replace('/<\/body>/i', "  {$tag}\n</body>", $html, 1);
            } else {
                $html .= "\n" . $tag;
            }
        }
        return $html;
    }

    /** 단순 attribute 파서 (key="value" 또는 key='value') */
    private function parseAttributes(string $attrString): array
    {
        $out = [];
        if (preg_match_all('/(\w[\w-]*)\s*=\s*"([^"]*)"|(\w[\w-]*)\s*=\s*\'([^\']*)\'/', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                if (!empty($m[1])) { $out[strtolower($m[1])] = $m[2]; }
                else                { $out[strtolower($m[3])] = $m[4]; }
            }
        }
        return $out;
    }

    /** SVG가 standalone 파일로 열릴 수 있도록 xmlns 보강 */
    private function normalizeStandaloneSvg(string $svg): string
    {
        if (!preg_match('/\bxmlns\s*=/i', $svg)) {
            $svg = preg_replace('/<svg\b/i', '<svg xmlns="http://www.w3.org/2000/svg"', $svg, 1);
        }
        return $svg;
    }
}
