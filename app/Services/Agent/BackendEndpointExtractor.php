<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;

class BackendEndpointExtractor
{
    /**
     * Backend 코드 산출물의 routes 배열에서 엔드포인트 목록을 추출합니다.
     *
     * @return array<array{resource:string,method:string,uri:string,controller:string,middleware:array}>
     */
    public function extractFromBackend(int $projectId): array
    {
        $artifacts = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->get();

        $endpoints = [];

        foreach ($artifacts as $artifact) {
            $decoded  = json_decode($artifact->content, true) ?? [];
            $resource = $decoded['$metadata']['resource'] ?? 'Unknown';

            foreach ($decoded['routes'] ?? [] as $route) {
                $uri = $this->normalizeUri($route['uri'] ?? '');
                if (!$uri) continue;

                $endpoints[] = [
                    'resource'   => $resource,
                    'table'      => $decoded['$metadata']['table'] ?? '',
                    'method'     => strtoupper($route['method'] ?? 'GET'),
                    'uri'        => $uri,
                    'controller' => $route['controller'] ?? '',
                    'middleware' => $route['middleware'] ?? [],
                ];
            }
        }

        return $endpoints;
    }

    private function normalizeUri(string $uri): string
    {
        // Strip /api prefix if present — match against normalised FE URLs
        $uri = preg_replace('#^/?api/?#', '/', $uri);
        if (!str_starts_with($uri, '/')) {
            $uri = '/' . $uri;
        }
        return rtrim($uri, '/') ?: '/';
    }
}
