<?php

namespace App\Services\Agent\Figma;

use App\Services\Agent\Figma\Contracts\FigmaClient;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaApiException;
use App\Services\Agent\Figma\Exceptions\FigmaInvalidTokenException;
use App\Services\Agent\Figma\Exceptions\FigmaRateLimitException;
use App\Services\Agent\Figma\Exceptions\FigmaResourceNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FigmaApiClient implements FigmaClient
{
    private const BASE_URL = 'https://api.figma.com';

    // Cache TTLs (seconds)
    private const TTL_FILE       = 300;   // 5 min
    private const TTL_STYLES     = 300;
    private const TTL_COMPONENTS = 300;
    private const TTL_IMAGES     = 600;   // 10 min
    private const TTL_ME         = 3600;  // 1 hour

    public function __construct(
        private readonly string          $personalAccessToken,
        private readonly CacheRepository $cache,
    ) {}

    public function validateToken(): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get(self::BASE_URL . '/v1/me');

            return $response->successful();
        } catch (\Exception) {
            return false;
        }
    }

    public function getMe(): array
    {
        return $this->cachedRequest(
            'figma:me:' . md5($this->personalAccessToken),
            self::TTL_ME,
            fn () => $this->httpGet('/v1/me')
        );
    }

    public function getFile(string $fileKey): FigmaFile
    {
        $data = $this->cachedRequest(
            "figma:file:{$fileKey}",
            self::TTL_FILE,
            fn () => $this->httpGet("/v1/files/{$fileKey}")
        );

        return FigmaFile::fromArray($data);
    }

    public function getFileNodes(string $fileKey, array $nodeIds): array
    {
        $ids = implode(',', $nodeIds);
        return $this->cachedRequest(
            "figma:nodes:{$fileKey}:{$ids}",
            self::TTL_FILE,
            fn () => $this->httpGet("/v1/files/{$fileKey}/nodes", ['ids' => $ids])
        );
    }

    public function getFileStyles(string $fileKey): array
    {
        $data = $this->cachedRequest(
            "figma:styles:{$fileKey}",
            self::TTL_STYLES,
            fn () => $this->httpGet("/v1/files/{$fileKey}/styles")
        );

        return $data['meta']['styles'] ?? [];
    }

    public function getFileComponents(string $fileKey): array
    {
        $data = $this->cachedRequest(
            "figma:components:{$fileKey}",
            self::TTL_COMPONENTS,
            fn () => $this->httpGet("/v1/files/{$fileKey}/components")
        );

        return $data['meta']['components'] ?? [];
    }

    public function getImages(string $fileKey, array $nodeIds, string $format = 'png', float $scale = 1.0): array
    {
        $ids      = implode(',', $nodeIds);
        $cacheKey = "figma:images:{$fileKey}:{$ids}:{$format}:{$scale}";

        $data = $this->cachedRequest(
            $cacheKey,
            self::TTL_IMAGES,
            fn () => $this->httpGet("/v1/images/{$fileKey}", [
                'ids'    => $ids,
                'format' => $format,
                'scale'  => $scale,
            ])
        );

        return $data['images'] ?? [];
    }

    private function headers(): array
    {
        return ['X-Figma-Token' => $this->personalAccessToken];
    }

    private function httpGet(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->timeout(30)
            ->get(self::BASE_URL . $endpoint, $params);

        $this->handleResponse($response, $endpoint);

        return $response->json();
    }

    private function handleResponse(Response $response, string $endpoint): void
    {
        if ($response->successful()) return;

        Log::warning("Figma API error {$response->status()} on {$endpoint}");

        throw match ($response->status()) {
            401     => new FigmaInvalidTokenException(),
            403     => new FigmaAccessDeniedException($endpoint),
            404     => new FigmaResourceNotFoundException($endpoint),
            429     => new FigmaRateLimitException($response->header('Retry-After')),
            default => new FigmaApiException(
                "Figma API 오류: HTTP {$response->status()} ({$endpoint})",
                $response->status()
            ),
        };
    }

    private function cachedRequest(string $key, int $ttl, callable $fetch): mixed
    {
        return $this->cache->remember($key, $ttl, $fetch);
    }
}
