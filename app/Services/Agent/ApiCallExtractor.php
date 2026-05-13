<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;

class ApiCallExtractor
{
    /**
     * Frontend 코드 산출물에서 모든 API 호출 패턴을 추출합니다.
     *
     * @return array<array{screen_id:string|null,file:string,line:int,method:string,url:string}>
     */
    public function extractFromFrontend(int $projectId): array
    {
        $artifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->get();

        $allCalls = [];

        foreach ($artifacts as $artifact) {
            $screen  = AiAgentScreen::find($artifact->scope_id);
            $decoded = json_decode($artifact->content, true) ?? [];

            foreach ($decoded['files'] ?? [] as $file) {
                $calls = $this->extractCallsFromFile(
                    $file['content'] ?? '',
                    $file['path'] ?? '',
                    $screen,
                );
                $allCalls = array_merge($allCalls, $calls);
            }
        }

        return $allCalls;
    }

    /**
     * 단일 파일 내 API 호출 패턴 추출 (정규식 기반)
     *
     * @return array<array{screen_id:string|null,file:string,line:int,method:string,url:string}>
     */
    public function extractCallsFromFile(string $content, string $path, ?AiAgentScreen $screen): array
    {
        $calls = [];

        // Pattern groups:
        // 1) axios.METHOD('url') / api.METHOD('url')
        $calls = array_merge($calls, $this->matchAxiosStyle($content, $path, $screen));

        // 2) fetch('url', { method: 'METHOD' }) or fetch('url') [defaults GET]
        $calls = array_merge($calls, $this->matchFetchStyle($content, $path, $screen));

        // 3) useFetch('url') or useQuery('url') hooks
        $calls = array_merge($calls, $this->matchHookStyle($content, $path, $screen));

        // Deduplicate by file+line
        $seen   = [];
        $unique = [];
        foreach ($calls as $call) {
            $key = $call['file'] . ':' . $call['line'] . ':' . $call['method'] . ':' . $call['url'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[]   = $call;
            }
        }

        return $unique;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function matchAxiosStyle(string $content, string $path, ?AiAgentScreen $screen): array
    {
        $calls = [];
        // axios.METHOD(...) or api.METHOD(...) or http.METHOD(...)
        $pattern = '/(?:axios|api|http|client|request)\.(get|post|put|patch|delete)\s*\(\s*[`\'"]([^`\'"]+)[`\'"]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }
        foreach ($matches as $m) {
            $offset = $m[0][1];
            $line   = substr_count(substr($content, 0, $offset), "\n") + 1;
            $url    = $this->normalizeUrl($m[2][0]);
            if (!$this->isApiUrl($url)) continue;
            $calls[] = $this->makeCall($screen, $path, $line, strtoupper($m[1][0]), $url);
        }
        return $calls;
    }

    private function matchFetchStyle(string $content, string $path, ?AiAgentScreen $screen): array
    {
        $calls = [];

        // fetch('url', { method: 'METHOD' })
        $pattern = '/fetch\s*\(\s*[`\'"]([^`\'"]+)[`\'"]\s*,\s*\{[^}]*method\s*:\s*[`\'"](\w+)[`\'"]/is';
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $m) {
                $offset = $m[0][1];
                $line   = substr_count(substr($content, 0, $offset), "\n") + 1;
                $url    = $this->normalizeUrl($m[1][0]);
                if (!$this->isApiUrl($url)) continue;
                $calls[] = $this->makeCall($screen, $path, $line, strtoupper($m[2][0]), $url);
            }
        }

        // fetch('url') without method → GET
        $pattern2 = '/fetch\s*\(\s*[`\'"]([^`\'"]+)[`\'"]\s*\)/';
        if (preg_match_all($pattern2, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            foreach ($matches as $m) {
                $offset = $m[0][1];
                $line   = substr_count(substr($content, 0, $offset), "\n") + 1;
                $url    = $this->normalizeUrl($m[1][0]);
                if (!$this->isApiUrl($url)) continue;
                $calls[] = $this->makeCall($screen, $path, $line, 'GET', $url);
            }
        }

        return $calls;
    }

    private function matchHookStyle(string $content, string $path, ?AiAgentScreen $screen): array
    {
        $calls = [];
        // useFetch / useQuery / useMutation with a URL string
        $pattern = '/use(?:Fetch|Query|Mutation|Get|Post|Patch|Delete)\s*\(\s*[`\'"]([^`\'"]+)[`\'"]/i';
        if (!preg_match_all($pattern, $content, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
            return [];
        }
        foreach ($matches as $m) {
            $offset    = $m[0][1];
            $line      = substr_count(substr($content, 0, $offset), "\n") + 1;
            $url       = $this->normalizeUrl($m[1][0]);
            if (!$this->isApiUrl($url)) continue;
            // Infer method from hook name
            $hookName = strtoupper(preg_replace('/use(Fetch|Query|Mutation|Get|Post|Patch|Delete).*/i', '$1', $m[0][0]));
            $method   = match($hookName) {
                'POST', 'MUTATION' => 'POST',
                'PATCH'  => 'PATCH',
                'DELETE' => 'DELETE',
                default  => 'GET',
            };
            $calls[] = $this->makeCall($screen, $path, $line, $method, $url);
        }
        return $calls;
    }

    private function normalizeUrl(string $url): string
    {
        // Remove template literal interpolation prefix ${...}
        $url = preg_replace('/\$\{[^}]+\}/', '{param}', $url);
        // Remove JS variable concatenation artifacts
        $url = preg_replace('/\s*\+\s*\w+\s*/', '/{param}', $url);
        // Remove VITE env prefix pattern
        $url = preg_replace('/^\$\{?import\.meta\.env\.[A-Z_]+\}?/', '', $url);

        // Normalize /api prefix: keep URI after /api
        if (str_starts_with($url, '/api/') || str_starts_with($url, 'api/')) {
            $url = '/' . ltrim(preg_replace('#^/?api/?#', '', $url), '/');
        } elseif (!str_starts_with($url, '/')) {
            $url = '/' . $url;
        }

        // Colon-style path params :id → {id}
        $url = preg_replace('/:(\w+)/', '{$1}', $url);

        return rtrim($url, '/') ?: '/';
    }

    private function isApiUrl(string $url): bool
    {
        // Skip static file references
        if (preg_match('/\.(png|jpg|gif|svg|css|js|html|ico|woff|ttf)$/i', $url)) return false;
        // Skip external URLs
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return false;
        // Must look like a path
        return str_starts_with($url, '/') || str_starts_with($url, '{');
    }

    private function makeCall(?AiAgentScreen $screen, string $path, int $line, string $method, string $url): array
    {
        return [
            'screen_id' => $screen?->screen_id,
            'file'      => $path,
            'line'      => $line,
            'method'    => $method,
            'url'       => $url,
        ];
    }
}
