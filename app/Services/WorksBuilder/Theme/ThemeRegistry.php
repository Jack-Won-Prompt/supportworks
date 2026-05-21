<?php

namespace App\Services\WorksBuilder\Theme;

use RuntimeException;

/**
 * 파일시스템 기반 테마 레지스트리.
 *
 * 테마는 `storage/app/wb-themes/<key>/` 디렉터리에 살고,
 *   - theme.json (manifest: key, name, version, description, assets, prompt_file, is_default)
 *   - prompt.md  (HTML 생성용 규칙 텍스트)
 *   - assets...  (css/js/images)
 * 를 포함한다.
 *
 * 새 테마는 디렉터리를 추가하는 것만으로 즉시 인식된다 (DB 마이그레이션 불요).
 */
class ThemeRegistry
{
    public function __construct(
        private ?string $rootDir = null,
    ) {
        $this->rootDir ??= resource_path('wb-themes');
    }

    /** 전체 테마 매니페스트 목록 (디렉터리에 theme.json 있는 것만). */
    public function list(): array
    {
        if (!is_dir($this->rootDir)) return [];

        $themes = [];
        foreach (scandir($this->rootDir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $dir = $this->rootDir . DIRECTORY_SEPARATOR . $entry;
            if (!is_dir($dir)) continue;

            $manifest = $this->readManifest($dir);
            if ($manifest !== null) {
                $themes[$manifest['key']] = $manifest;
            }
        }
        return $themes;
    }

    /** 단일 테마 매니페스트. 없으면 RuntimeException. */
    public function get(string $key): array
    {
        $list = $this->list();
        if (!isset($list[$key])) {
            throw new RuntimeException("Unknown theme: {$key}");
        }
        return $list[$key];
    }

    /** 존재 여부 (예외 없이). */
    public function exists(string $key): bool
    {
        return isset($this->list()[$key]);
    }

    /** 기본 테마 키. is_default=true 또는 첫 번째. */
    public function defaultKey(): ?string
    {
        $list = $this->list();
        if (empty($list)) return null;

        foreach ($list as $key => $m) {
            if (!empty($m['is_default'])) return $key;
        }
        return array_key_first($list);
    }

    /** 테마 prompt.md 의 원문 텍스트. 파일 없으면 빈 문자열. */
    public function promptText(string $key): string
    {
        $m = $this->get($key);
        $promptFile = $m['prompt_file'] ?? null;
        if (!$promptFile) return '';

        $path = $this->themeDir($key) . DIRECTORY_SEPARATOR . $promptFile;
        if (!is_file($path)) return '';

        return (string) file_get_contents($path);
    }

    /** 테마 디렉터리 절대 경로. */
    public function themeDir(string $key): string
    {
        // 존재 검증
        $this->get($key);
        return $this->rootDir . DIRECTORY_SEPARATOR . $key;
    }

    /** 매니페스트의 assets 를 [type => [relativePath, ...]] 그대로 반환. */
    public function assets(string $key): array
    {
        $m = $this->get($key);
        return $m['assets'] ?? [];
    }

    /** 실재하는 asset 의 절대 경로만 반환 [relativePath => absolutePath]. */
    public function resolvedAssetMap(string $key): array
    {
        $dir = $this->themeDir($key);
        $out = [];
        foreach ($this->assets($key) as $type => $files) {
            foreach ((array) $files as $rel) {
                $abs = $dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel);
                if (is_file($abs)) {
                    $out[$rel] = $abs;
                }
            }
        }
        return $out;
    }

    private function readManifest(string $dir): ?array
    {
        $path = $dir . DIRECTORY_SEPARATOR . 'theme.json';
        if (!is_file($path)) return null;

        $json = json_decode((string) file_get_contents($path), true);
        if (!is_array($json) || empty($json['key'])) return null;

        return $json;
    }
}
