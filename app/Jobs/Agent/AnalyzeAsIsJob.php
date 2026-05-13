<?php

namespace App\Jobs\Agent;

use App\Models\Agent\AiAgentArtifact;
use App\Services\Agent\AsIsAnalysisAiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class AnalyzeAsIsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 1;

    private const CACHE_PREFIX = 'ai-agent:as-is:';
    private const CACHE_TTL    = 1800;

    public function __construct(
        public readonly int    $analysisId,
        public readonly int    $userId,
        public readonly string $sessionId,
    ) {}

    public function handle(AsIsAnalysisAiService $service): void
    {
        $this->updateProgress('RUNNING', '분석 중...', []);

        try {
            $artifact = AiAgentArtifact::findOrFail($this->analysisId);
            $result   = $service->analyze($artifact, $this->userId);
            $this->updateProgress('COMPLETED', '분석 완료', $result);
        } catch (\Throwable $e) {
            $this->updateProgress('ERROR', $e->getMessage(), []);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        $this->updateProgress('ERROR', $e->getMessage(), []);
    }

    public static function cacheKey(string $sessionId): string
    {
        return self::CACHE_PREFIX . $sessionId;
    }

    private function updateProgress(string $status, string $message, array $result): void
    {
        Cache::put(self::cacheKey($this->sessionId), [
            'status'      => $status,
            'message'     => $message,
            'result'      => $result,
            'analysis_id' => $this->analysisId,
            'updated_at'  => now()->toIso8601String(),
        ], self::CACHE_TTL);
    }
}
