<?php

namespace App\Services\Concerns;

/**
 * 웍스 응답 파싱 로직 공유 트레이트.
 * ClaudeService와 OpenAiService의 동일한 파싱 코드를 단일 위치에서 관리합니다.
 */
trait ParsesAiResponse
{
    private function parseResponse(string $raw): array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $text = preg_replace('/\s*```$/m', '', $text);
        $text = trim($text);

        $data = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($data['html'])) {
            if (empty($data['lang'])) {
                $data['lang'] = (empty($data['css']) && empty($data['js'])) ? 'html' : 'web';
            }
            return $data;
        }

        // JSON 파싱 실패 시 마크다운 코드블록에서 추출
        $html = $this->extractBlock($raw, 'html');
        $css  = $this->extractBlock($raw, 'css');
        $js   = $this->extractBlock($raw, 'javascript') ?: $this->extractBlock($raw, 'js');

        $lang = 'web';
        if (!$html && !$css && !$js) {
            foreach (['python','sql','php','java','typescript','bash','shell','json','yaml','ruby','go','rust','c','cpp','kotlin','swift'] as $l) {
                $code = $this->extractBlock($raw, $l);
                if ($code) {
                    $html = $code;
                    $lang = $l;
                    break;
                }
            }
        } else {
            $lang = (empty($css) && empty($js)) ? 'html' : 'web';
        }

        return [
            'explanation' => $this->stripCodeBlocks($raw),
            'html'        => $html,
            'css'         => $css,
            'js'          => $js,
            'lang'        => $lang,
        ];
    }

    private function parseRefineResponse(string $raw): array
    {
        $text = preg_replace('/^```(?:json)?\s*/m', '', $raw);
        $text = preg_replace('/\s*```$/m', '', $text);
        $data = json_decode(trim($text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('프롬프트 정제 결과를 파싱할 수 없습니다.');
        }

        return $data;
    }

    private function extractBlock(string $text, string $lang): string
    {
        if (preg_match('/```' . preg_quote($lang, '/') . '\s*([\s\S]*?)```/i', $text, $m)) {
            return trim($m[1]);
        }
        return '';
    }

    private function stripCodeBlocks(string $text): string
    {
        $text = preg_replace('/```[\s\S]*?```/', '', $text);
        return trim(preg_replace('/\n{3,}/', "\n\n", $text));
    }
}
