<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\MaintenanceScreen;
use App\Models\MaintenanceVersion;

class MaintenanceService
{
    private AiOrchestrator $ai;

    public function __construct()
    {
        $settings = AiSetting::current();
        $this->ai = new AiOrchestrator(
            $settings->anthropicKey(),
            $settings->openaiKey(),
            $settings->manusKey(),
            $settings->manusEndpoint(),
        );
    }

    // ── 화면 파일 읽기 ──────────────────────────────────────────────

    public function readScreenContent(MaintenanceScreen $screen): array
    {
        $bladePath = $screen->absoluteBladePath();
        $content   = file_exists($bladePath) ? file_get_contents($bladePath) : '';

        return [
            'blade' => [
                'path'    => $screen->blade_path,
                'content' => $content,
            ],
        ];
    }

    // ── 웍스: 구조화 프롬프트 생성 ────────────────────────────────────

    public function generatePrompt(MaintenanceScreen $screen, string $userRequest): array
    {
        $files    = $this->readScreenContent($screen);
        $bladeLen = mb_strlen($files['blade']['content'] ?? '');

        // 파일이 너무 크면 앞부분만 전달
        $bladeContent = $bladeLen > 30000
            ? mb_substr($files['blade']['content'], 0, 30000) . "\n... (이하 생략)"
            : $files['blade']['content'];

        $systemPrompt = <<<'SYS'
당신은 Laravel Blade 화면 유지보수 전문 웍스입니다.
사용자의 수정 요청을 분석하여 아래 JSON 구조로 구조화 프롬프트를 반환하세요.

반드시 다음 JSON만 반환하고 다른 텍스트는 금지:
{
  "goal": "수정 목표 (1문장)",
  "role": "웍스 역할 정의",
  "input": "주어진 입력 요소",
  "constraints": "반드시 지켜야 할 제약사항",
  "output_format": "기대하는 출력 형식",
  "refined_prompt": "최종 실행 프롬프트 (상세, 구체적)"
}
SYS;

        $userMsg = <<<MSG
화면 정보:
- screen_key: {$screen->screen_key}
- 화면명: {$screen->name}
- Blade 경로: {$screen->blade_path}
- URL: {$screen->url_pattern}

현재 Blade 코드:
```blade
{$bladeContent}
```

사용자 수정 요청:
{$userRequest}

위 정보를 바탕으로 구조화 프롬프트 JSON을 생성하세요.
MSG;

        $res  = $this->ai->chatRaw([['role' => 'user', 'content' => $userMsg]], $systemPrompt);
        $text = trim($res['text']);

        $text = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $text = preg_replace('/```\s*$/m', '', $text);
        $text = trim($text);

        $p1 = strpos($text, '{');
        $p2 = strrpos($text, '}');
        if ($p1 !== false && $p2 !== false) {
            $text = substr($text, $p1, $p2 - $p1 + 1);
        }

        $draft = json_decode($text, true);
        if (!$draft) {
            // JSON 내 제어문자 제거 후 재시도
            $cleaned = preg_replace('/[\x00-\x1F\x7F](?<!["\n\r\t\\])/u', '', $text);
            $draft   = json_decode($cleaned, true);
        }
        if (!$draft) {
            throw new \RuntimeException('웍스 프롬프트 생성 응답을 파싱할 수 없습니다. (응답: ' . mb_substr($text, 0, 200) . ')');
        }

        return ['draft' => $draft, 'provider' => $res['provider']];
    }

    // ── 웍스: 수정안 생성 ─────────────────────────────────────────────

    public function generatePatch(MaintenanceScreen $screen, array $prompt, string $userRequest): array
    {
        $files        = $this->readScreenContent($screen);
        $bladeContent = $files['blade']['content'] ?? '';

        if (mb_strlen($bladeContent) > 30000) {
            $bladeContent = mb_substr($bladeContent, 0, 30000) . "\n... (이하 생략)";
        }

        $refinedPrompt = $prompt['refined_prompt'] ?? $userRequest;

        // 구분자 기반 포맷 사용 — JSON 인코딩 오류 원천 차단
        $systemPrompt = <<<'SYS'
당신은 Laravel Blade 화면 유지보수 전문 웍스입니다.

규칙:
1. 기존 코드 구조와 스타일을 반드시 유지하세요
2. 요청된 부분만 수정하세요 (전체 재작성 금지)
3. 기존 기능이 손상되지 않도록 하세요
4. 반드시 아래 형식으로만 응답하세요. 형식 외 텍스트 일절 금지.
5. ##CONTENT_START## 와 ##CONTENT_END## 는 반드시 각각 독립된 줄에 단독으로 작성하세요.
6. ##CONTENT_END## 와 ##PATCH_END## 를 절대 생략하지 마세요.

##PATCH_START##
SUMMARY: (변경 내용 요약, 한국어 2-3문장)
FILE_PATH: (파일 경로)
FILE_TYPE: blade
##CONTENT_START##
(수정된 전체 파일 내용을 그대로 출력 — 이스케이프 불필요)
##CONTENT_END##
##PATCH_END##
SYS;

        $userMsg = <<<MSG
화면 정보:
- screen_key: {$screen->screen_key}
- 화면명: {$screen->name}
- Blade 경로: {$screen->blade_path}

실행 프롬프트:
{$refinedPrompt}

원본 수정 요청:
{$userRequest}

현재 Blade 코드:
{$bladeContent}

위 코드에서 요청된 부분만 수정하여 지정된 형식으로 반환하세요.
MSG;

        $res  = $this->ai->chatRaw([['role' => 'user', 'content' => $userMsg]], $systemPrompt);
        $text = trim($res['text']);

        $patch = $this->parsePatchFormat($text, $screen->blade_path, $files['blade']['content'] ?? '');

        if (!$patch || empty($patch['files'])) {
            throw new \RuntimeException('웍스 수정안 응답을 파싱할 수 없습니다. (응답: ' . mb_substr($text, 0, 200) . ')');
        }

        return ['patch' => $patch, 'provider' => $res['provider']];
    }

    // ── 구분자 포맷 파서 ────────────────────────────────────────────

    private function parsePatchFormat(string $text, string $bladePath, string $originalContent): ?array
    {
        // 구분자를 항상 독립 줄에 위치시켜 정규식 매칭 보장
        // 웍스가 "##CONTENT_START## content..." 처럼 같은 줄에 붙여 반환해도 처리됨
        $normalized = preg_replace(
            '/\s*##(PATCH_START|PATCH_END|CONTENT_START|CONTENT_END)##\s*/',
            "\n##$1##\n",
            $text
        );

        // ##PATCH_START## ~ ##PATCH_END## 블록 추출 (없으면 전체 사용)
        if (preg_match('/##PATCH_START##(.*?)##PATCH_END##/s', $normalized, $m)) {
            $block = $m[1];
        } else {
            $block = $normalized;
        }

        // 메타 영역 (##CONTENT_START## 이전)
        $meta = preg_match('/^(.*?)##CONTENT_START##/s', $block, $mm) ? $mm[1] : $block;

        // SUMMARY — 키워드 기반 추출 (줄 위치 무관)
        $summary = '';
        if (preg_match('/SUMMARY:\s*(.+?)(?=\s*(?:FILE_PATH:|FILE_TYPE:|##)|$)/s', $meta, $ms)) {
            $summary = trim(preg_replace('/\s+/', ' ', $ms[1]));
        }

        // FILE_PATH
        $filePath = $bladePath;
        if (preg_match('/FILE_PATH:\s*(\S+)/', $meta, $mf) && trim($mf[1])) {
            $filePath = trim($mf[1]);
        }

        // FILE_TYPE
        $fileType = 'blade';
        if (preg_match('/FILE_TYPE:\s*(\S+)/', $meta, $mt) && trim($mt[1])) {
            $fileType = strtolower(trim($mt[1]));
        }

        // ##CONTENT_START## ~ ##CONTENT_END## 추출
        // CONTENT_END 누락 시 PATCH_END 또는 문자열 끝까지 폴백
        $modifiedContent = '';
        if (preg_match('/##CONTENT_START##\r?\n?(.*?)##CONTENT_END##/s', $normalized, $mc)) {
            $modifiedContent = $mc[1];
        } elseif (preg_match('/##CONTENT_START##\r?\n?(.*?)(?:##PATCH_END##|$)/s', $normalized, $mc)) {
            $modifiedContent = $mc[1];
        }

        $modifiedContent = preg_replace('/^\r?\n/', '', $modifiedContent);
        $modifiedContent = rtrim($modifiedContent, "\r\n");

        if ($modifiedContent === '') {
            return null;
        }

        return [
            'change_summary' => $summary,
            'files'          => [[
                'file_path'        => $filePath,
                'file_type'        => $fileType,
                'original_content' => $originalContent,
                'modified_content' => $modifiedContent,
                'diff_content'     => $this->buildLineDiff($originalContent, $modifiedContent),
            ]],
        ];
    }

    // ── 미리보기용 Blade 지시문 제거 ──────────────────────────────

    public function stripBladeForPreview(string $content): string
    {
        // 레이아웃·섹션 지시문 제거
        $content = preg_replace('/@extends\s*\([^)]+\)/', '', $content);
        $content = preg_replace('/@section\s*\([^)]+\)/', '', $content);
        $content = preg_replace('/@endsection\b/', '', $content);
        $content = preg_replace('/@push\s*\([^)]+\)/', '', $content);
        $content = preg_replace('/@endpush\b/', '', $content);
        $content = preg_replace('/@stack\s*\([^)]+\)/', '', $content);
        $content = preg_replace('/@yield\s*\([^)]+\)/', '', $content);

        // PHP 블록 제거
        $content = preg_replace('/@php\b.*?@endphp/s', '', $content);
        $content = preg_replace('/<\?php.*?\?>/s', '', $content);

        // Blade 주석 제거
        $content = preg_replace('/\{\{--(.*?)--\}\}/s', '', $content);

        // 변수 출력 → 플레이스홀더 표시
        $content = preg_replace('/\{!!\s*(.*?)\s*!!\}/s', '<span style="opacity:.4;">[동적 데이터]</span>', $content);
        $content = preg_replace('/\{\{\s*(.*?)\s*\}\}/s', '<span style="opacity:.4;">[동적 데이터]</span>', $content);

        // 조건/반복 지시문 — 태그만 제거, 내용은 유지
        $content = preg_replace('/@(if|elseif|else|endif|foreach|endforeach|for|endfor|while|endwhile|unless|endunless|isset|endisset|empty|endempty|auth|endauth|guest|endguest)\b[^\n]*/m', '', $content);

        // @csrf, @method 등 폼 헬퍼
        $content = preg_replace('/@\w+\s*(\([^)]*\))?/', '', $content);

        return trim($content);
    }

    // ── 버전 적용 (파일 쓰기) ──────────────────────────────────────

    public function applyVersion(MaintenanceVersion $version): void
    {
        foreach ($version->files as $file) {
            $absPath = base_path($file['file_path']);
            $dir     = dirname($absPath);

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($absPath, $file['modified_content'] ?? '');
        }
    }

    // ── 롤백 ───────────────────────────────────────────────────────

    public function rollbackTo(MaintenanceVersion $version): void
    {
        foreach ($version->files as $file) {
            $absPath = base_path($file['file_path']);
            $dir     = dirname($absPath);
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($absPath, $file['original_content'] ?? '');
        }
    }

    // ── Line Diff ──────────────────────────────────────────────────

    public function buildLineDiff(string $original, string $modified): string
    {
        $originalLines = explode("\n", $original);
        $modifiedLines = explode("\n", $modified);

        $diff  = [];
        $patch = $this->computeDiff($originalLines, $modifiedLines);

        foreach ($patch as $op) {
            [$type, $line] = $op;
            if ($type === 'eq')  $diff[] = '  ' . $line;
            if ($type === 'del') $diff[] = '- ' . $line;
            if ($type === 'ins') $diff[] = '+ ' . $line;
        }

        return implode("\n", $diff);
    }

    private function computeDiff(array $a, array $b): array
    {
        $la = count($a);
        $lb = count($b);
        $matrix = [];
        for ($i = 0; $i <= $la; $i++) $matrix[$i][0] = $i;
        for ($j = 0; $j <= $lb; $j++) $matrix[0][$j] = $j;

        for ($i = 1; $i <= $la; $i++) {
            for ($j = 1; $j <= $lb; $j++) {
                if ($a[$i - 1] === $b[$j - 1]) {
                    $matrix[$i][$j] = $matrix[$i - 1][$j - 1];
                } else {
                    $matrix[$i][$j] = 1 + min($matrix[$i - 1][$j], $matrix[$i][$j - 1], $matrix[$i - 1][$j - 1]);
                }
            }
        }

        $result = [];
        $i = $la; $j = $lb;
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $a[$i - 1] === $b[$j - 1]) {
                $result[] = ['eq', $a[$i - 1]]; $i--; $j--;
            } elseif ($j > 0 && ($i === 0 || $matrix[$i][$j - 1] <= $matrix[$i - 1][$j])) {
                $result[] = ['ins', $b[$j - 1]]; $j--;
            } else {
                $result[] = ['del', $a[$i - 1]]; $i--;
            }
        }

        return array_reverse($result);
    }
}
