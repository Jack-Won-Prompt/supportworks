<?php

namespace App\Services\Agent;

use App\Enums\Agent\FrontendStack;

class FrontendCodePreviewService
{
    /**
     * 스택에 맞는 iframe-ready HTML을 생성.
     */
    public function buildPreviewHtml(array $artifactContent): string
    {
        $stack = FrontendStack::tryFrom($artifactContent['$metadata']['stack'] ?? '') ?? FrontendStack::HTML;
        $files = $artifactContent['files'] ?? [];

        return match ($stack) {
            FrontendStack::HTML  => $this->buildHtmlPreview($files, $artifactContent),
            FrontendStack::REACT => $this->buildReactPreview($files, $artifactContent),
            FrontendStack::VUE   => $this->buildVuePreview($files, $artifactContent),
        };
    }

    // ── HTML Stack ────────────────────────────────────────────────────────────

    private function buildHtmlPreview(array $files, array $artifactContent): string
    {
        $htmlFile  = $this->findFile($files, 'index.html') ?? $this->findFileByExt($files, '.html');
        $cssFile   = $this->findFile($files, 'style.css') ?? $this->findFileByExt($files, '.css');
        $jsFile    = $this->findFile($files, 'script.js') ?? $this->findFileByExt($files, '.js');

        if ($htmlFile) {
            $html    = $htmlFile['content'];
            $cssInj  = $cssFile ? "<style>\n{$cssFile['content']}\n</style>" : '';
            $jsInj   = $jsFile  ? "<script>\n{$this->sanitizeJs($jsFile['content'])}\n</script>" : '';

            if ($cssInj) $html = str_replace('</head>', "{$cssInj}\n</head>", $html);
            if ($jsInj && !str_contains($html, '<script')) {
                $html = str_replace('</body>', "{$jsInj}\n</body>", $html);
            }
            if (!str_contains($html, 'cdn.tailwindcss.com')) {
                $twCdn = '<script src="https://cdn.tailwindcss.com"></script>';
                $html  = str_replace('</head>', "{$twCdn}\n</head>", $html);
            }
            return $html;
        }

        // 파일이 없으면 합쳐서 기본 페이지 생성
        $allHtml = $this->combineHtmlFiles($files);
        return $this->wrapInBasePage($allHtml, FrontendStack::HTML);
    }

    private function sanitizeJs(string $js): string
    {
        // Remove node-style module imports (not available in browser)
        $js = preg_replace('/^import\s+.*?from\s+[\'"].*?[\'"];?\s*$/m', '', $js ?? '');
        $js = preg_replace('/^const\s+\w+\s*=\s*require\([\'"].*?[\'"]\);?\s*$/m', '', $js ?? '');
        return $js ?? '';
    }

    private function combineHtmlFiles(array $files): string
    {
        $out = '';
        foreach ($files as $f) {
            if (str_ends_with($f['path'], '.html')) $out .= $f['content'] . "\n";
        }
        foreach ($files as $f) {
            if (str_ends_with($f['path'], '.css')) $out .= "<style>\n{$f['content']}\n</style>\n";
        }
        foreach ($files as $f) {
            if (str_ends_with($f['path'], '.js')) $out .= "<script>\n{$this->sanitizeJs($f['content'])}\n</script>\n";
        }
        return $out;
    }

    // ── React Stack ───────────────────────────────────────────────────────────

    private function buildReactPreview(array $files, array $artifactContent): string
    {
        $mainPath  = $artifactContent['main_file_path'] ?? '';
        $mainFile  = $this->findFile($files, $mainPath) ?? ($files[0] ?? null);
        $cssFile   = $this->findFileByExt($files, '.css');

        $babelScripts = $this->buildBabelScripts($files, $mainFile['path'] ?? '');
        $cssBlock     = $cssFile ? "<style>\n{$cssFile['content']}\n</style>" : '';
        $mainCompName = $mainFile ? $this->inferComponentName($mainFile['path']) : 'App';

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/react@18/umd/react.development.js" crossorigin></script>
<script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js" crossorigin></script>
<script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
{$cssBlock}
<style>body{margin:0;font-family:system-ui,sans-serif;}</style>
</head>
<body>
<div id="root"></div>

<!-- 가상 모듈 시스템 (베스트에포트 미리보기) -->
<script>
window.__modules = {};
window.__define = function(name, fn) { window.__modules[name] = fn; };
window.__require = function(name) { return window.__modules[name] || {}; };
// 가상 axios
window.axios = {
  get: (url, cfg) => fetch(url, cfg).then(r => r.json()).then(data => ({data})),
  post: (url, data, cfg) => fetch(url, {method:'POST', body:JSON.stringify(data), headers:{'Content-Type':'application/json'}, ...cfg}).then(r => r.json()).then(data => ({data})),
  put: (url, data, cfg) => fetch(url, {method:'PUT', body:JSON.stringify(data), headers:{'Content-Type':'application/json'}, ...cfg}).then(r => r.json()).then(data => ({data})),
  delete: (url, cfg) => fetch(url, {method:'DELETE', ...cfg}).then(r => r.json()).then(data => ({data})),
};
</script>

{$babelScripts}

<script type="text/babel" data-presets="react,typescript">
const { useState, useEffect, useContext, useRef, useCallback, useMemo } = React;
// 마지막 컴포넌트를 루트로 마운트
try {
  const RootComp = typeof {$mainCompName} !== 'undefined' ? {$mainCompName} : (() => <div style={{padding:'20px',color:'#888'}}>미리보기 진입점을 찾을 수 없습니다.</div>);
  ReactDOM.createRoot(document.getElementById('root')).render(<RootComp />);
} catch(e) {
  document.getElementById('root').innerHTML = '<div style="padding:20px;color:#b91c1c;">미리보기 렌더링 오류: ' + e.message + '</div>';
}
</script>
<div style="position:fixed;bottom:0;left:0;right:0;background:#1e1b2e;color:#94a3b8;padding:4px 10px;font-size:11px;text-align:center;">
  ⚠️ 베스트에포트 미리보기 — 실제 환경과 다를 수 있습니다
</div>
</body>
</html>
HTML;
    }

    private function buildBabelScripts(array $files, string $mainPath): string
    {
        // CSS 제외, main 파일 마지막 순서
        $ordered  = array_filter($files, fn($f) => !str_ends_with($f['path'], '.css'));
        $ordered  = array_values($ordered);
        usort($ordered, fn($a, $b) => ($a['path'] === $mainPath ? 1 : 0) - ($b['path'] === $mainPath ? 1 : 0));

        $scripts = '';
        foreach ($ordered as $file) {
            $content = $this->sanitizeReactImports($file['content']);
            $scripts .= "<script type=\"text/babel\" data-presets=\"react,typescript\">\n{$content}\n</script>\n";
        }
        return $scripts;
    }

    private function sanitizeReactImports(string $content): string
    {
        // Strip ES module import/export statements (globals are available via CDN)
        $content = preg_replace('/^import\s+.*?;\s*$/m', '', $content ?? '');
        $content = preg_replace('/^export\s+default\s+/m', '', $content ?? '');
        $content = preg_replace('/^export\s+\{[^}]*\};\s*$/m', '', $content ?? '');
        $content = preg_replace('/^export\s+(function|const|class|type|interface)\s+/m', '$1 ', $content ?? '');
        return $content ?? '';
    }

    private function inferComponentName(string $path): string
    {
        $name = basename($path, '.tsx');
        $name = basename($name, '.jsx');
        $name = basename($name, '.ts');
        $name = basename($name, '.js');
        return $name ?: 'App';
    }

    // ── Vue Stack ─────────────────────────────────────────────────────────────

    private function buildVuePreview(array $files, array $artifactContent): string
    {
        $mainPath = $artifactContent['main_file_path'] ?? '';
        $mainFile = $this->findFile($files, $mainPath) ?? ($files[0] ?? null);
        $cssFile  = $this->findFileByExt($files, '.css');

        $vueCode  = $mainFile ? $this->extractVueComponent($mainFile['content']) : null;
        $cssBlock = $cssFile ? "<style>\n{$cssFile['content']}\n</style>" : '';

        $template = $vueCode['template'] ?? '<div>{{ message }}</div>';
        $script   = $vueCode['script']   ?? "import { ref } from 'vue'; export default { setup() { return { message: '미리보기' }; } }";

        // Sanitize script
        $script = $this->sanitizeVueScript($script);

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vue Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
{$cssBlock}
<style>body{margin:0;font-family:system-ui,sans-serif;}</style>
</head>
<body>
<div id="app">{$template}</div>
<script>
const { createApp, ref, reactive, computed, onMounted, watch } = Vue;
window.axios = {
  get: (url, cfg) => fetch(url, cfg).then(r => r.json()).then(data => ({data})),
  post: (url, data, cfg) => fetch(url, {method:'POST', body:JSON.stringify(data), headers:{'Content-Type':'application/json'}, ...cfg}).then(r => r.json()).then(data => ({data})),
};
try {
  {$script}
  createApp(typeof __vueComponent !== 'undefined' ? __vueComponent : {template: '<div>컴포넌트를 찾을 수 없습니다</div>'}).mount('#app');
} catch(e) {
  document.getElementById('app').innerHTML = '<div style="padding:20px;color:#b91c1c;">미리보기 오류: ' + e.message + '</div>';
}
</script>
<div style="position:fixed;bottom:0;left:0;right:0;background:#1e1b2e;color:#94a3b8;padding:4px 10px;font-size:11px;text-align:center;">
  ⚠️ 베스트에포트 미리보기 — 실제 환경과 다를 수 있습니다
</div>
</body>
</html>
HTML;
    }

    private function extractVueComponent(string $content): array
    {
        preg_match('/<template>(.*?)<\/template>/s', $content, $tmpl);
        preg_match('/<script[^>]*>(.*?)<\/script>/s', $content, $scr);

        return [
            'template' => trim($tmpl[1] ?? ''),
            'script'   => trim($scr[1]  ?? ''),
        ];
    }

    private function sanitizeVueScript(string $script): string
    {
        $script = preg_replace('/^import\s+.*?;\s*$/m', '', $script ?? '');
        $script = preg_replace('/^export\s+default\s+/m', '__vueComponent = ', $script ?? '');
        return $script ?? '';
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function findFile(array $files, string $path): ?array
    {
        foreach ($files as $f) {
            if ($f['path'] === $path) return $f;
        }
        return null;
    }

    private function findFileByExt(array $files, string $ext): ?array
    {
        foreach ($files as $f) {
            if (str_ends_with($f['path'], $ext)) return $f;
        }
        return null;
    }

    private function wrapInBasePage(string $body, FrontendStack $stack): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
{$body}
</body>
</html>
HTML;
    }
}
