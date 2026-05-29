<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Services\ClaudeService;
use App\Services\ManusService;
use App\Services\OpenAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranslateController extends Controller
{
    private const LANG_NAMES = [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh' => '中文(简体)',
    ];

    private const SYSTEM_PROMPT = "You are a professional translator. Translate the following text into %s. Output ONLY the translated text — no explanations, no quotes, no extra text.";

    public function translate(Request $request)
    {
        $request->validate([
            'text'   => 'required|string|max:50000',
            'target' => 'required|string|in:ko,en,ja,zh',
        ]);

        $translated = self::translateText($request->input('text'), $request->input('target'));

        if ($translated !== null && trim($translated) !== '') {
            return response()->json(['ok' => true, 'translated' => trim($translated)]);
        }

        return response()->json(['error' => '번역에 실패했습니다. 웍스 API 키를 확인해 주세요.'], 503);
    }

    /**
     * 텍스트를 대상 언어로 번역해 문자열로 반환한다. (Google→Claude→OpenAI→Manus 폴백)
     * HTTP 엔드포인트와 서버 내부 호출(예: Word 내보내기 자동 번역)에서 공용.
     * 모든 서비스 실패 시 null 반환. 빈 입력은 빈 문자열 반환.
     */
    public static function translateText(string $text, string $target): ?string
    {
        if (trim($text) === '') return '';

        $targetName = self::LANG_NAMES[$target] ?? $target;
        $system     = sprintf(self::SYSTEM_PROMPT, $targetName);
        $msgs       = [['role' => 'user', 'content' => $text]];
        $setting    = AiSetting::current();

        $errors = [];

        // 1차: Google Translate (API 키 불필요)
        try {
            $translated = self::googleTranslate($text, $target);
            if (trim($translated) !== '') return trim($translated);
        } catch (\Throwable $e) {
            $errors[] = 'Google: ' . $e->getMessage();
            Log::warning('[Translate] Google failed: ' . $e->getMessage());
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        // 2차: Claude API
        try {
            $claudeKey = $setting->anthropicKey();
            if ($claudeKey) {
                $translated = (new ClaudeService($claudeKey))->chatRawTranslate($msgs, $system);
                if (trim($translated) !== '') return trim($translated);
            }
        } catch (\Throwable $e) {
            $errors[] = 'Claude: ' . $e->getMessage();
            Log::warning('[Translate] Claude failed: ' . $e->getMessage());
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        // 3차: OpenAI
        try {
            $openaiKey = $setting->openaiKey();
            if ($openaiKey) {
                $translated = (new OpenAiService($openaiKey))->chatRawTranslate($msgs, $system);
                if (trim($translated) !== '') return trim($translated);
            }
        } catch (\Throwable $e) {
            $errors[] = 'OpenAI: ' . $e->getMessage();
            Log::warning('[Translate] OpenAI failed: ' . $e->getMessage());
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        // 4차: Manus API
        try {
            $manusKey      = $setting->manusKey();
            $manusEndpoint = $setting->manusEndpoint();
            if ($manusKey && $manusEndpoint) {
                $translated = (new ManusService($manusKey, $manusEndpoint))->chatRawTranslate($msgs, $system);
                if (trim($translated) !== '') return trim($translated);
            }
        } catch (\Throwable $e) {
            $errors[] = 'Manus: ' . $e->getMessage();
            Log::warning('[Translate] Manus failed: ' . $e->getMessage());
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        Log::error('[Translate] All services failed', ['errors' => $errors]);
        \App\Models\SystemErrorLog::log('error', '[Translate] 모든 번역 서비스 실패: ' . implode('; ', $errors));

        return null;
    }

    private static function googleTranslate(string $text, string $target): string
    {
        // 텍스트가 길면 단락 단위로 분할 번역 (GET URL 길이 제한 우회)
        $chunks = self::splitIntoChunks($text, 3000);
        $results = [];

        foreach ($chunks as $chunk) {
            $results[] = self::googleTranslateChunk($chunk, $target);
        }

        return implode("\n", $results);
    }

    private static function googleTranslateChunk(string $text, string $target): string
    {
        // POST 방식으로 URL 길이 제한 우회
        $baseUrl = 'https://translate.googleapis.com/translate_a/single?'
            . http_build_query(['client' => 'gtx', 'sl' => 'auto', 'tl' => $target, 'dt' => 't']);

        $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->timeout(15)
            ->asForm()
            ->post($baseUrl, ['q' => $text]);

        if (!$response->successful()) {
            throw new \RuntimeException('HTTP ' . $response->status());
        }

        $data  = $response->json();
        $parts = collect($data[0] ?? [])->pluck(0)->filter()->implode('');

        if ($parts === '') {
            throw new \RuntimeException('Empty response');
        }

        return $parts;
    }

    private static function splitIntoChunks(string $text, int $maxLen): array
    {
        if (mb_strlen($text) <= $maxLen) {
            return [$text];
        }

        // 단락(\n\n) 기준으로 분할, 그래도 크면 줄(\n) 기준으로 분할
        $chunks  = [];
        $current = '';

        foreach (preg_split('/(\n\n+)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
            if (mb_strlen($current) + mb_strlen($part) > $maxLen && $current !== '') {
                $chunks[]  = $current;
                $current   = $part;
            } else {
                $current  .= $part;
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
