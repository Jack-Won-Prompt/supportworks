<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentUsageLog;
use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\Contracts\AIResponse;

class AgentUsageLogService
{
    // 모델별 USD/1K 토큰 단가 (input, output)
    private const PRICING = [
        'claude-sonnet-4-6' => ['input' => 0.003, 'output' => 0.015],
        'claude-opus-4-7'   => ['input' => 0.015, 'output' => 0.075],
        'claude-haiku-4-5'  => ['input' => 0.00025, 'output' => 0.00125],
        'gpt-4o'            => ['input' => 0.005, 'output' => 0.015],
        'gpt-4o-mini'       => ['input' => 0.00015, 'output' => 0.0006],
    ];

    /**
     * AI 호출을 실행하고 결과를 usage_logs에 기록.
     * T06 AIProvider와 통합된 단일 진입점.
     *
     * @param  callable(): AIResponse  $call  실제 AI 호출을 감싸는 클로저
     */
    public function callAndLog(
        AIProvider $provider,
        callable   $call,
        int        $userId,
        ?int       $projectId = null,
        ?int       $artifactId = null,
        ?string    $stage = null,
        ?string    $taskType = null
    ): AIResponse {
        $startedAt = microtime(true);

        try {
            /** @var AIResponse $response */
            $response = $call();

            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);
            $costUsd    = $this->calculateCost($response->model, $response->inputTokens, $response->outputTokens);

            AiAgentUsageLog::record(
                userId:       $userId,
                model:        $response->model,
                provider:     $this->providerName($response->model),
                inputTokens:  $response->inputTokens,
                outputTokens: $response->outputTokens,
                costUsd:      $costUsd,
                projectId:    $projectId,
                artifactId:   $artifactId,
                stage:        $stage,
                taskType:     $taskType,
                durationMs:   $durationMs,
                status:       'success',
            );

            return $response;
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startedAt) * 1000);

            AiAgentUsageLog::record(
                userId:       $userId,
                model:        $provider->modelId(),
                provider:     $this->providerName($provider->modelId()),
                inputTokens:  0,
                outputTokens: 0,
                costUsd:      0.0,
                projectId:    $projectId,
                artifactId:   $artifactId,
                stage:        $stage,
                taskType:     $taskType,
                durationMs:   $durationMs,
                status:       'error',
                errorMessage: substr($e->getMessage(), 0, 500),
            );

            throw $e;
        }
    }

    /**
     * 프로젝트의 토큰/비용 통계 집계.
     *
     * @return array{total_cost_usd: float, input_tokens: int, output_tokens: int, call_count: int}
     */
    public function projectStats(int $projectId): array
    {
        $row = AiAgentUsageLog::forProject($projectId)
            ->successful()
            ->selectRaw('SUM(cost_usd) as total_cost, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, COUNT(*) as call_count')
            ->first();

        return [
            'total_cost_usd' => (float) ($row->total_cost ?? 0),
            'input_tokens'   => (int) ($row->input_tokens ?? 0),
            'output_tokens'  => (int) ($row->output_tokens ?? 0),
            'call_count'     => (int) ($row->call_count ?? 0),
        ];
    }

    private function calculateCost(string $model, int $inputTokens, int $outputTokens): float
    {
        $modelKey = $this->resolveModelKey($model);
        $pricing  = self::PRICING[$modelKey] ?? ['input' => 0.003, 'output' => 0.015];

        return ($inputTokens / 1000 * $pricing['input']) + ($outputTokens / 1000 * $pricing['output']);
    }

    private function resolveModelKey(string $model): string
    {
        foreach (array_keys(self::PRICING) as $key) {
            if (str_starts_with($model, $key)) {
                return $key;
            }
        }
        return $model;
    }

    private function providerName(string $model): string
    {
        return str_starts_with($model, 'claude') ? 'anthropic' : 'openai';
    }
}
