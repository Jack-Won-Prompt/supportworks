<?php

namespace App\Services\Agent;

use App\Enums\Agent\FrontendStack;

/**
 * Optional Node.js-based static analysis for generated frontend code.
 * Gracefully degrades when Node.js / tools are unavailable.
 */
class CodeStaticAnalyzer
{
    private const TEMP_PREFIX = 'ai_cv_';

    public function isAvailable(): bool
    {
        $output = $this->exec('node --version');
        return $output !== null && str_starts_with(trim($output), 'v');
    }

    /**
     * Run static analysis on generated files.
     *
     * @param  array<array{path:string,content:string}>  $files
     * @return array{available:bool, reason?:string, issues:array, summary:array}
     */
    public function analyze(array $files, FrontendStack $stack): array
    {
        if (!$this->isAvailable()) {
            return [
                'available' => false,
                'reason'    => 'Node.js not detected on this server. 웍스 review only.',
                'issues'    => [],
                'summary'   => [],
            ];
        }

        $tmpDir = $this->createTempDir($files);
        if (!$tmpDir) {
            return [
                'available' => false,
                'reason'    => 'Could not create temporary directory for analysis.',
                'issues'    => [],
                'summary'   => [],
            ];
        }

        try {
            $issues = [];

            // Rule-based textual checks (no tooling needed)
            foreach ($files as $file) {
                $path    = $file['path'];
                $content = $file['content'] ?? '';
                $issues  = array_merge($issues, $this->runTextChecks($path, $content));
            }

            // ESLint (if installed globally or locally)
            $eslintIssues = $this->runEslintIfAvailable($tmpDir, $stack);
            if ($eslintIssues !== null) {
                $issues = array_merge($issues, $eslintIssues);
            }

            $critical = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'critical'));
            $warning  = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'warning'));
            $info     = count(array_filter($issues, fn($i) => ($i['severity'] ?? '') === 'info'));

            return [
                'available' => true,
                'issues'    => $issues,
                'summary'   => [
                    'total'    => count($issues),
                    'critical' => $critical,
                    'warning'  => $warning,
                    'info'     => $info,
                ],
            ];
        } finally {
            $this->cleanupTempDir($tmpDir);
        }
    }

    // ── Text-based checks (always available) ──────────────────────────────────

    private function runTextChecks(string $path, string $content): array
    {
        $issues = [];
        $lines  = explode("\n", $content);

        foreach ($lines as $lineNo => $line) {
            $lineNum = $lineNo + 1;

            // Hardcoded secrets
            if (preg_match('/(?:password|secret|api_key|apikey|token)\s*=\s*["\'][^"\']{8,}/i', $line)
                && !preg_match('/process\.env|getenv|config\(/', $line)) {
                $issues[] = $this->issue('security', 'critical', '하드코딩된 비밀값 가능성', $path, $lineNum,
                    "환경변수 또는 설정 파일에서 값을 읽도록 변경하세요.");
            }

            // localStorage token storage
            if (preg_match('/localStorage\s*\.\s*setItem\s*\([^)]*[Tt]oken/i', $line)) {
                $issues[] = $this->issue('security', 'warning', 'localStorage에 토큰 저장',
                    $path, $lineNum, 'httpOnly 쿠키 또는 메모리 저장을 권장합니다.');
            }

            // console.log left in code
            if (preg_match('/console\s*\.\s*log\s*\(/', $line) && !str_contains($path, 'test')) {
                $issues[] = $this->issue('code_quality', 'info', 'console.log 잔류',
                    $path, $lineNum, '프로덕션 코드에서 console.log를 제거하세요.');
            }

            // TODO/FIXME comments
            if (preg_match('/\b(TODO|FIXME|HACK|XXX)\b/', $line)) {
                $issues[] = $this->issue('code_quality', 'info', 'TODO/FIXME 미처리',
                    $path, $lineNum, '배포 전 해당 항목을 처리하세요.');
            }

            // Eval usage
            if (preg_match('/\beval\s*\(/', $line)) {
                $issues[] = $this->issue('security', 'critical', 'eval() 사용',
                    $path, $lineNum, 'eval()은 XSS 위험이 있습니다. 대안 로직을 사용하세요.');
            }

            // dangerouslySetInnerHTML
            if (str_contains($line, 'dangerouslySetInnerHTML')) {
                $issues[] = $this->issue('security', 'warning', 'dangerouslySetInnerHTML 사용',
                    $path, $lineNum, 'XSS 위험. 입력값 검증 및 sanitization 필요.');
            }
        }

        return $issues;
    }

    // ── ESLint ─────────────────────────────────────────────────────────────────

    private function runEslintIfAvailable(string $dir, FrontendStack $stack): ?array
    {
        // Check if ESLint is globally available
        $eslintPath = $this->exec('npx --yes eslint --version 2>&1');
        if (!$eslintPath || !str_contains($eslintPath, '.')) {
            return null;
        }

        $ext = $stack === FrontendStack::HTML ? 'js,html' : 'js,jsx,ts,tsx,vue';

        // Write minimal .eslintrc
        $config = $this->buildEslintConfig($stack);
        file_put_contents($dir . '/.eslintrc.json', json_encode($config));

        $output = $this->exec(
            "npx eslint --ext {$ext} --format json {$dir} 2>/dev/null",
            60
        );

        if (!$output) return null;

        $parsed = json_decode($output, true);
        if (!is_array($parsed)) return null;

        $issues = [];
        foreach ($parsed as $file) {
            $relPath = ltrim(str_replace($dir, '', $file['filePath'] ?? ''), '/\\');
            foreach ($file['messages'] ?? [] as $msg) {
                $severity = match ($msg['severity'] ?? 1) {
                    2       => 'warning',
                    default => 'info',
                };
                $issues[] = $this->issue(
                    'code_quality',
                    $severity,
                    '[ESLint] ' . ($msg['message'] ?? ''),
                    $relPath,
                    $msg['line'] ?? 0,
                    $msg['ruleId'] ?? ''
                );
            }
        }

        return $issues;
    }

    private function buildEslintConfig(FrontendStack $stack): array
    {
        $base = ['env' => ['browser' => true, 'es2021' => true], 'parserOptions' => ['ecmaVersion' => 'latest']];

        if ($stack === FrontendStack::REACT) {
            $base['extends']       = ['eslint:recommended'];
            $base['parserOptions'] = array_merge($base['parserOptions'], ['ecmaFeatures' => ['jsx' => true]]);
        } elseif ($stack === FrontendStack::VUE) {
            $base['extends'] = ['eslint:recommended'];
        } else {
            $base['extends'] = ['eslint:recommended'];
        }

        return $base;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function issue(
        string $category,
        string $severity,
        string $title,
        string $file,
        int    $line,
        string $suggestion = '',
    ): array {
        return [
            'id'           => substr(md5($category . $severity . $title . $file . $line), 0, 12),
            'category'     => $category,
            'severity'     => $severity,
            'title'        => $title,
            'description'  => $title,
            'file'         => $file,
            'line'         => $line,
            'suggestion'   => $suggestion,
            'auto_fixable' => false,
            'ignored'      => false,
            'source'       => 'static',
        ];
    }

    private function createTempDir(array $files): ?string
    {
        $tmpBase = sys_get_temp_dir();
        $tmpDir  = $tmpBase . DIRECTORY_SEPARATOR . self::TEMP_PREFIX . uniqid();

        if (!mkdir($tmpDir, 0755, true)) return null;

        foreach ($files as $file) {
            $fullPath = $tmpDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file['path']);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($fullPath, $file['content'] ?? '');
        }

        return $tmpDir;
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname());
        }
        rmdir($dir);
    }

    private function exec(string $cmd, int $timeout = 10): ?string
    {
        try {
            $output = shell_exec($cmd);
            return is_string($output) ? $output : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
