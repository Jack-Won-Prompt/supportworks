<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\AgentUsageLogService;
use App\Services\Agent\AnthropicProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiStreamController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:stream:';
    private const SESSION_TTL  = 1800; // seconds

    public function __construct(private readonly AgentUsageLogService $usageLog) {}

    // ─────────────────────────────────────────────────────────────────────
    // Demo page
    // GET /ai-agent/stream/demo
    // ─────────────────────────────────────────────────────────────────────

    public function demo(): View
    {
        return view('ai-agent.demo.progress-demo', [
            'demoSseBaseUrl'  => route('ai-agent.stream.demo-sse', ['scenario' => 'SCENARIO']),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel',   ['sessionId' => 'SESSION_ID']),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Demo SSE — simulated streaming, no real API call
    // GET /ai-agent/stream/demo-sse/{scenario}?sessionId=xxx
    // ─────────────────────────────────────────────────────────────────────

    public function demoSse(Request $request, string $scenario): StreamedResponse
    {
        $sessionId = $request->query('sessionId', Str::uuid()->toString());

        return response()->stream(function () use ($scenario, $sessionId) {
            $this->clearOutputBuffer();

            match ($scenario) {
                'short' => $this->runShortDemo($sessionId),
                'long'  => $this->runLongDemo($sessionId),
                'error' => $this->runErrorDemo(),
                'job'   => $this->runJobDemo($sessionId),
                default => $this->runShortDemo($sessionId),
            };
        }, 200, $this->sseHeaders());
    }

    // ─────────────────────────────────────────────────────────────────────
    // Session start — registers session in cache, returns sessionId
    // POST /ai-agent/projects/{project}/stream/start
    // ─────────────────────────────────────────────────────────────────────

    public function start(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'prompt'     => 'required|string|max:50000',
            'system'     => 'nullable|string|max:10000',
            'max_tokens' => 'nullable|integer|min:100|max:16000',
            'stage'      => 'nullable|string',
            'task_type'  => 'nullable|string',
        ]);

        $sessionId = Str::uuid()->toString();

        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'status'     => 'STARTING',
            'prompt'     => $validated['prompt'],
            'system'     => $validated['system'] ?? '당신은 친절하고 전문적인 웍스 어시스턴트입니다.',
            'max_tokens' => $validated['max_tokens'] ?? 4000,
            'project_id' => $project->id,
            'user_id'    => auth()->id(),
            'stage'      => $validated['stage'] ?? null,
            'task_type'  => $validated['task_type'] ?? null,
            'created_at' => now()->toIso8601String(),
            'tokens_in'  => 0,
            'tokens_out' => 0,
            'text'       => '',
            'elapsed'    => 0,
            'cost_usd'   => 0.0,
            'error'      => null,
            'cancel'     => false,
        ], self::SESSION_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Real SSE — calls Anthropic API and forwards chunks
    // GET /ai-agent/projects/{project}/stream/sse/{sessionId}
    // ─────────────────────────────────────────────────────────────────────

    public function sse(Request $request, Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        if (!$session || ($session['project_id'] ?? null) !== $project->id) {
            return response()->stream(function () {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
            }, 200, $this->sseHeaders());
        }

        return response()->stream(function () use ($sessionId, $session) {
            $this->clearOutputBuffer();

            if ($this->isCancelled($sessionId)) {
                $this->sseEvent('cancelled', ['status' => 'CANCELLED', 'message' => '작업이 취소되었습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STREAMING']);

            $startedAt   = microtime(true);
            $accumulated = '';

            try {
                $apiKey   = AiSetting::current()->anthropicKey();
                $provider = new AnthropicProvider($apiKey);

                $response = $this->usageLog->callAndLog(
                    provider:  $provider,
                    call:      function () use ($provider, $session, $sessionId, $startedAt, &$accumulated) {
                        return $provider->stream(
                            systemPrompt: $session['system'],
                            messages:     [['role' => 'user', 'content' => $session['prompt']]],
                            onChunk:      function (string $chunk) use ($sessionId, $startedAt, &$accumulated) {
                                if ($this->isCancelled($sessionId)) {
                                    throw new \RuntimeException('CANCELLED');
                                }
                                $accumulated .= $chunk;
                                $this->sseEvent('token', [
                                    'text'    => $chunk,
                                    'elapsed' => round(microtime(true) - $startedAt, 1),
                                ]);
                            },
                            options: ['max_tokens' => $session['max_tokens'] ?? 4000],
                        );
                    },
                    userId:    $session['user_id'],
                    projectId: $session['project_id'],
                    stage:     $session['stage'],
                    taskType:  $session['task_type'],
                );

                $elapsed = round(microtime(true) - $startedAt, 2);
                $costUsd = $this->usageLog->calculateCost(
                    $provider->modelId(), $response->inputTokens, $response->outputTokens
                );

                $this->updateSession($sessionId, [
                    'status'     => 'COMPLETED',
                    'tokens_in'  => $response->inputTokens,
                    'tokens_out' => $response->outputTokens,
                    'text'       => $accumulated,
                    'elapsed'    => $elapsed,
                    'cost_usd'   => $costUsd,
                ]);

                $this->sseEvent('complete', [
                    'status'    => 'COMPLETED',
                    'tokensIn'  => $response->inputTokens,
                    'tokensOut' => $response->outputTokens,
                    'elapsed'   => $elapsed,
                    'costUsd'   => $costUsd,
                    'text'      => $accumulated,
                ]);

            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'CANCELLED') {
                    $elapsed = round(microtime(true) - $startedAt, 2);
                    $this->updateSession($sessionId, ['status' => 'CANCELLED']);
                    $this->sseEvent('cancelled', [
                        'status'  => 'CANCELLED',
                        'message' => '작업이 취소되었습니다.',
                        'elapsed' => $elapsed,
                    ]);
                } else {
                    $this->streamError($sessionId, $e->getMessage());
                }
            } catch (\Throwable $e) {
                $this->streamError($sessionId, $e->getMessage());
            }
        }, 200, $this->sseHeaders());
    }

    // ─────────────────────────────────────────────────────────────────────
    // Cancel — sets cancel flag; works for both demo and real sessions
    // POST /ai-agent/stream/{sessionId}/cancel
    // ─────────────────────────────────────────────────────────────────────

    public function cancel(Request $request, string $sessionId): JsonResponse
    {
        $this->updateSession($sessionId, ['cancel' => true]);
        return response()->json(['success' => true, 'message' => '취소 신호를 전송했습니다.']);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Status polling (for polling mode)
    // GET /ai-agent/projects/{project}/stream/{sessionId}/status
    // ─────────────────────────────────────────────────────────────────────

    public function status(Request $request, Project $project, string $sessionId): JsonResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        if (!$session || ($session['project_id'] ?? null) !== $project->id) {
            return response()->json(['success' => false, 'message' => '세션을 찾을 수 없습니다.'], 404);
        }

        return response()->json(['success' => true, 'session' => $session]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────

    private function sseHeaders(): array
    {
        return [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ];
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    private function isCancelled(string $sessionId): bool
    {
        return (bool) (Cache::get(self::CACHE_PREFIX . $sessionId)['cancel'] ?? false);
    }

    private function updateSession(string $sessionId, array $updates): void
    {
        $existing = Cache::get(self::CACHE_PREFIX . $sessionId, []);
        Cache::put(self::CACHE_PREFIX . $sessionId, array_merge($existing, $updates), self::SESSION_TTL);
    }

    private function streamError(string $sessionId, string $message): void
    {
        $this->updateSession($sessionId, ['status' => 'ERROR', 'error' => $message]);
        $this->sseEvent('error', ['status' => 'ERROR', 'message' => $message]);
    }

    private function authorizeProject(Project $project): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', auth()->id())->exists(),
            403
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // Demo simulation scenarios
    // ─────────────────────────────────────────────────────────────────────

    private function runShortDemo(string $sessionId): void
    {
        $this->sseEvent('status', ['status' => 'STREAMING']);
        $start = microtime(true);

        $chunks = [
            "안녕하세요! 웍스 Agent가 분석을 시작합니다.\n\n",
            "**분석 결과 요약:**\n",
            "1. 현재 시스템의 주요 기능을 파악했습니다.\n",
            "2. 개선 가능한 영역 3개를 발견했습니다.\n",
            "3. 아래에 구체적인 개선안을 제안합니다.\n\n",
            "작업이 성공적으로 완료되었습니다. ✓",
        ];

        $totalText = '';
        foreach ($chunks as $chunk) {
            if ($this->isCancelled($sessionId)) {
                $this->sseEvent('cancelled', ['status' => 'CANCELLED', 'message' => '작업이 취소되었습니다.', 'elapsed' => round(microtime(true) - $start, 1)]);
                return;
            }
            $totalText .= $chunk;
            usleep(350_000);
            $this->sseEvent('token', ['text' => $chunk, 'elapsed' => round(microtime(true) - $start, 1)]);
        }

        $elapsed = round(microtime(true) - $start, 2);
        $this->sseEvent('complete', [
            'status'    => 'COMPLETED',
            'tokensIn'  => 142,
            'tokensOut' => 87,
            'elapsed'   => $elapsed,
            'costUsd'   => 0.0018,
            'text'      => $totalText,
        ]);
    }

    private function runLongDemo(string $sessionId): void
    {
        $this->sseEvent('status', ['status' => 'STREAMING']);
        $start = microtime(true);

        $chunks = [
            "# AS-IS 시스템 분석 보고서\n\n",
            "## 1. 현황 분석\n\n",
            "현재 시스템은 레거시 아키텍처를 기반으로 운영되고 있습니다. ",
            "주요 구성요소로는 모놀리식 백엔드, 단순 HTML 프론트엔드, MySQL 8 데이터베이스가 포함됩니다.\n\n",
            "## 2. 프로세스 흐름\n\n",
            "사용자 요청 → 단일 서버 처리 → DB 조회 → 응답 반환의 단순한 구조입니다. ",
            "이 구조는 소규모 트래픽에서 적합하지만 동시 사용자 증가 시 병목이 발생합니다.\n\n",
            "## 3. 주요 문제점\n\n",
            "**성능 문제:**\n- 쿼리 최적화 부재\n- 캐싱 미적용\n- N+1 쿼리 패턴 다수 발견\n\n",
            "**유지보수 문제:**\n- 테스트 코드 부재\n- 문서화 미흡\n- 강한 결합도\n\n",
            "**보안 문제:**\n- SQL 인젝션 취약점 존재\n- 입력값 검증 미흡\n\n",
            "## 4. 개선 권고사항\n\n",
            "단기적으로는 인덱스 추가와 쿼리 최적화를 통해 성능을 30% 이상 향상시킬 수 있습니다. ",
            "중장기적으로는 마이크로서비스 아키텍처로의 전환을 권장합니다.\n\n",
            "## 5. 결론\n\n",
            "이번 AS-IS 분석을 통해 시스템의 현재 상태와 개선 방향을 명확히 파악했습니다. ",
            "TO-BE 설계 단계에서 이 분석 결과를 바탕으로 최적화된 아키텍처를 설계할 것을 권장합니다.",
        ];

        $totalText = '';
        foreach ($chunks as $chunk) {
            if ($this->isCancelled($sessionId)) {
                $this->sseEvent('cancelled', [
                    'status'    => 'CANCELLED',
                    'message'   => '작업이 취소되었습니다.',
                    'elapsed'   => round(microtime(true) - $start, 1),
                    'tokensOut' => intval(mb_strlen($totalText) / 4),
                ]);
                return;
            }
            $totalText .= $chunk;
            usleep(500_000);
            $this->sseEvent('token', ['text' => $chunk, 'elapsed' => round(microtime(true) - $start, 1)]);
        }

        $elapsed = round(microtime(true) - $start, 2);
        $this->sseEvent('complete', [
            'status'    => 'COMPLETED',
            'tokensIn'  => 312,
            'tokensOut' => 487,
            'elapsed'   => $elapsed,
            'costUsd'   => 0.0084,
            'text'      => $totalText,
        ]);
    }

    private function runErrorDemo(): void
    {
        $this->sseEvent('status', ['status' => 'STREAMING']);
        usleep(1_000_000);
        $this->sseEvent('token', ['text' => "분석을 시작합니다...", 'elapsed' => 1.0]);
        usleep(1_500_000);
        $this->sseEvent('error', [
            'status'  => 'ERROR',
            'message' => 'API 오류: 서버에서 예기치 못한 응답을 받았습니다. (시뮬레이션)',
        ]);
    }

    private function runJobDemo(string $sessionId): void
    {
        $this->sseEvent('status', ['status' => 'STREAMING', 'progress' => 0]);
        $start = microtime(true);

        $steps = [
            ['delay' => 600_000,   'progress' => 15, 'message' => '파일을 분석하는 중...'],
            ['delay' => 1_000_000, 'progress' => 35, 'message' => '요구사항을 추출하는 중...'],
            ['delay' => 1_000_000, 'progress' => 58, 'message' => '우선순위를 분류하는 중...'],
            ['delay' => 1_000_000, 'progress' => 80, 'message' => '보고서를 작성하는 중...'],
            ['delay' => 1_200_000, 'progress' => 97, 'message' => '검토 및 최종화 중...'],
        ];

        foreach ($steps as $step) {
            if ($this->isCancelled($sessionId)) {
                $this->sseEvent('cancelled', ['status' => 'CANCELLED', 'message' => '작업이 취소되었습니다.', 'elapsed' => round(microtime(true) - $start, 1)]);
                return;
            }
            usleep($step['delay']);
            $this->sseEvent('progress', [
                'status'   => 'STREAMING',
                'progress' => $step['progress'],
                'message'  => $step['message'],
                'elapsed'  => round(microtime(true) - $start, 1),
            ]);
        }

        $elapsed    = round(microtime(true) - $start, 2);
        $resultText = "작업이 완료되었습니다.\n\n분석된 요구사항: 24개\nMust-have: 12개  /  Should-have: 8개  /  Could-have: 4개";
        $this->sseEvent('complete', [
            'status'    => 'COMPLETED',
            'tokensIn'  => 1150,
            'tokensOut' => 820,
            'elapsed'   => $elapsed,
            'costUsd'   => 0.0158,
            'progress'  => 100,
            'text'      => $resultText,
        ]);
    }
}
