<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\FrontendStack;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\ProjectAiAgentConfig;

class ApiIntegrationService
{
    public function __construct(
        private readonly ApiCallExtractor         $callExtractor,
        private readonly BackendEndpointExtractor $endpointExtractor,
    ) {}

    /**
     * Frontend ↔ Backend API 매칭 분석
     */
    public function analyze(int $projectId): array
    {
        $frontendCalls    = $this->callExtractor->extractFromFrontend($projectId);
        $backendEndpoints = $this->endpointExtractor->extractFromBackend($projectId);

        $matches               = [];
        $unmatchedFrontend     = [];
        $matchedBackendIndices = [];

        foreach ($frontendCalls as $call) {
            $matchIdx = $this->findMatchingEndpoint($call, $backendEndpoints);

            if ($matchIdx !== null) {
                $matches[] = [
                    'frontend_call'    => $call,
                    'backend_endpoint' => $backendEndpoints[$matchIdx],
                    'status'           => 'matched',
                ];
                $matchedBackendIndices[] = $matchIdx;
            } else {
                $unmatchedFrontend[] = $call;
            }
        }

        $unmatchedBackend = array_values(
            array_filter($backendEndpoints, fn($_, $idx) => !in_array($idx, $matchedBackendIndices), ARRAY_FILTER_USE_BOTH)
        );

        $feCount  = count($frontendCalls);
        $matched  = count($matches);
        $rate     = $feCount > 0 ? round($matched / $feCount * 100, 1) : ($backendEndpoints ? 0.0 : 100.0);

        return [
            '$metadata' => [
                'frontend_calls'      => $feCount,
                'backend_endpoints'   => count($backendEndpoints),
                'matched'             => $matched,
                'unmatched_frontend'  => count($unmatchedFrontend),
                'unmatched_backend'   => count($unmatchedBackend),
                'compliance_rate'     => $rate,
                'analyzed_at'         => now()->toIso8601String(),
            ],
            'matches'           => $matches,
            'unmatched_frontend' => array_map(fn($c) => [
                'frontend_call' => $c,
                'status'        => 'unmatched_frontend',
                'issue'         => '백엔드에 해당 엔드포인트 없음',
                'suggestion'    => 'Backend 코드에 엔드포인트 추가 또는 Frontend 호출 제거',
            ], $unmatchedFrontend),
            'unmatched_backend' => array_map(fn($e) => [
                'backend_endpoint' => $e,
                'status'           => 'unmatched_backend',
                'issue'            => 'Frontend에서 호출되지 않음',
                'suggestion'       => '사용하지 않는 엔드포인트 제거 또는 화면에서 호출 추가',
            ], $unmatchedBackend),
        ];
    }

    /**
     * 통합 설정 파일 생성 (.env, api client, CORS)
     */
    public function generateIntegrationFiles(int $projectId): array
    {
        $stack = $this->resolveStack($projectId);
        $ext   = match($stack) {
            FrontendStack::REACT, FrontendStack::VUE => 'ts',
            FrontendStack::HTML => 'js',
        };

        return [
            'frontend/.env.example'             => $this->makeFrontendEnv($stack),
            "frontend/src/utils/api.{$ext}"     => $this->makeApiClient($stack),
            'backend/.env.example'              => $this->makeBackendEnv(),
            'backend/config/cors.php'           => $this->makeCorsConfig(),
        ];
    }

    /**
     * 분석 결과 + 통합 파일을 API_INTEGRATION 산출물로 저장
     */
    public function persistResult(int $projectId, array $analysis, array $integrationFiles, int $userId): AiAgentArtifact
    {
        $stage = $this->resolveDevStage($projectId);

        return AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::API_INTEGRATION,
            scopeType: 'project',
            scopeId:   $projectId,
            title:     'API 연계 분석 — ' . now()->format('Y-m-d H:i'),
            content:   json_encode([
                'analysis'          => $analysis,
                'integration_files' => $integrationFiles,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $userId,
            meta:      $analysis['$metadata'],
        );
    }

    // ── Matching logic ────────────────────────────────────────────────────────

    private function findMatchingEndpoint(array $call, array $endpoints): ?int
    {
        foreach ($endpoints as $idx => $ep) {
            if ($call['method'] !== $ep['method']) continue;
            if ($this->urlsMatch($call['url'], $ep['uri'])) {
                return $idx;
            }
        }
        return null;
    }

    private function urlsMatch(string $a, string $b): bool
    {
        if ($a === $b) return true;

        // Build regex from each side: replace {param} with ([^/]+)
        $escape = fn(string $u) => '#^' . str_replace(
            ['{param}', '\{param\}'],
            '([^/]+)',
            preg_quote(preg_replace('/\{[^}]+\}/', '{param}', $u), '#')
        ) . '$#';

        return (bool) preg_match($escape($a), $b) || (bool) preg_match($escape($b), $a);
    }

    // ── Integration file generators ───────────────────────────────────────────

    private function makeFrontendEnv(FrontendStack $stack): string
    {
        $prefix = match($stack) {
            FrontendStack::REACT => 'REACT_APP',
            FrontendStack::VUE   => 'VITE',
            FrontendStack::HTML  => 'API',
        };

        return <<<ENV
# API Configuration
{$prefix}_API_BASE_URL=http://localhost:8000/api
{$prefix}_API_TIMEOUT=10000

# Authentication
{$prefix}_AUTH_TOKEN_KEY=auth_token
ENV;
    }

    private function makeApiClient(FrontendStack $stack): string
    {
        return match($stack) {
            FrontendStack::REACT => $this->makeAxiosClientReact(),
            FrontendStack::VUE   => $this->makeAxiosClientVue(),
            FrontendStack::HTML  => $this->makeFetchWrapper(),
        };
    }

    private function makeAxiosClientReact(): string
    {
        return <<<'TS'
import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from 'axios';

const BASE_URL = process.env.REACT_APP_API_BASE_URL ?? '/api';
const TIMEOUT  = Number(process.env.REACT_APP_API_TIMEOUT) || 10000;

const api: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: TIMEOUT,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  withCredentials: true,
});

// Request interceptor — attach Bearer token
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(process.env.REACT_APP_AUTH_TOKEN_KEY ?? 'auth_token');
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Response interceptor — handle 401 globally
api.interceptors.response.use(
  (res: AxiosResponse) => res,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem(process.env.REACT_APP_AUTH_TOKEN_KEY ?? 'auth_token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  },
);

export default api;
TS;
    }

    private function makeAxiosClientVue(): string
    {
        return <<<'TS'
import axios, { AxiosInstance } from 'axios';

const BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';
const TIMEOUT  = Number(import.meta.env.VITE_API_TIMEOUT) || 10000;
const TOKEN_KEY = import.meta.env.VITE_AUTH_TOKEN_KEY ?? 'auth_token';

const api: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: TIMEOUT,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
  withCredentials: true,
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token && config.headers) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (res) => res,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY);
      window.location.href = '/login';
    }
    return Promise.reject(error);
  },
);

export default api;
TS;
    }

    private function makeFetchWrapper(): string
    {
        return <<<'JS'
const API_BASE = window.API_BASE_URL ?? '/api';
const TOKEN_KEY = 'auth_token';

async function apiFetch(method, path, body = null) {
  const token = localStorage.getItem(TOKEN_KEY);
  const headers = {
    'Content-Type': 'application/json',
    'Accept':       'application/json',
    ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
  };

  const res = await fetch(API_BASE + path, {
    method,
    headers,
    ...(body ? { body: JSON.stringify(body) } : {}),
  });

  if (res.status === 401) {
    localStorage.removeItem(TOKEN_KEY);
    window.location.href = '/login';
    return;
  }

  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw Object.assign(new Error(err.message ?? 'API Error'), { status: res.status, data: err });
  }

  return res.status === 204 ? null : res.json();
}

const api = {
  get:    (path)        => apiFetch('GET', path),
  post:   (path, body)  => apiFetch('POST', path, body),
  put:    (path, body)  => apiFetch('PUT', path, body),
  patch:  (path, body)  => apiFetch('PATCH', path, body),
  delete: (path)        => apiFetch('DELETE', path),
};

export default api;
JS;
    }

    private function makeBackendEnv(): string
    {
        return <<<'ENV'
APP_NAME=SupportWorks
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=root
DB_PASSWORD=

# CORS — add your frontend origin
FRONTEND_URL=http://localhost:3000

# Sanctum
SANCTUM_STATEFUL_DOMAINS=localhost:3000

# Cache / Session
CACHE_STORE=database
SESSION_DRIVER=database
ENV;
    }

    private function makeCorsConfig(): string
    {
        return <<<'PHP'
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
PHP;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resolveStack(int $projectId): FrontendStack
    {
        $config = ProjectAiAgentConfig::forProject($projectId);
        return $config?->frontend_stack ?? FrontendStack::REACT;
    }

    private function resolveDevStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $projectId)
            ->where('type', StageType::DEVELOPMENT)
            ->first()
            ?? AiAgentProjectStage::create([
                'project_id' => $projectId,
                'type'       => StageType::DEVELOPMENT,
                'name'       => '개발',
                'status'     => StageStatus::IN_PROGRESS,
                'order'      => 4,
            ]);
    }
}
