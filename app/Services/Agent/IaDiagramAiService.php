<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentScreen;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class IaDiagramAiService
{
    public const DIAGRAMS = [
        'ia_diagram' => [
            'title'      => 'IA 구조도',
            'task_type'  => 'ia_flow_ia_diagram',
            'max_tokens' => 1200,
        ],
        'flow_diagram' => [
            'title'      => '화면 흐름도',
            'task_type'  => 'ia_flow_screen_flow',
            'max_tokens' => 1200,
        ],
    ];

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * IA + 화면 흐름도 두 다이어그램을 순차 생성하고 저장.
     *
     * @return array{total: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generate(
        AiAgentArtifact  $artifact,
        AiAgentArtifact  $planningDoc,
        \Illuminate\Support\Collection $screens,
        int              $userId,
        callable         $onProgress,
    ): array {
        $provider  = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $diagrams  = [];
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';
        $total     = count(self::DIAGRAMS);
        $done      = 0;

        $docContent = $this->truncateDoc($planningDoc->content ?? '');
        $screenList = $this->formatScreenList($screens);

        foreach (self::DIAGRAMS as $key => $def) {
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $def['title'], 'status' => 'processing']);

            try {
                $result = $this->generateDiagram(
                    provider:   $provider,
                    projectId:  $artifact->project_id,
                    key:        $key,
                    def:        $def,
                    docContent: $docContent,
                    screenList: $screenList,
                    screens:    $screens,
                    artifactId: $artifact->id,
                    userId:     $userId,
                );
                $diagrams[$key] = $result['content'];
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$key]  = $e->getMessage();
                $diagrams[$key] = '';
            }

            $done++;
            $onProgress(['done' => $done, 'total' => $total, 'key' => $key, 'title' => $def['title'], 'status' => isset($failed[$key]) ? 'failed' : 'done']);
        }

        $markdown = $this->renderMarkdown($planningDoc, $diagrams);

        $artifact->updateWithVersion(
            content: $markdown,
            userId:  $userId,
            meta: [
                'change_type'     => 'ai_generated',
                'model'           => $lastModel,
                'tokens_in'       => $totalIn,
                'tokens_out'      => $totalOut,
                'cost_usd'        => $totalCost,
                'ia_diagram'      => $diagrams['ia_diagram'] ?? '',
                'flow_diagram'    => $diagrams['flow_diagram'] ?? '',
                'failed_diagrams' => $failed,
                'generated_at'    => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($artifact->project_id, $artifact, $planningDoc);

        return [
            'total'        => $total,
            'failed_count' => count($failed),
            'failed'       => $failed,
            'tokens_in'    => $totalIn,
            'tokens_out'   => $totalOut,
            'cost'         => $totalCost,
            'model'        => $lastModel,
        ];
    }

    /**
     * 단일 다이어그램 재생성.
     *
     * @return array{content: string, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function regenerateDiagram(
        AiAgentArtifact  $artifact,
        string           $diagramKey,
        AiAgentArtifact  $planningDoc,
        \Illuminate\Support\Collection $screens,
        int              $userId,
    ): array {
        if (!isset(self::DIAGRAMS[$diagramKey])) {
            throw new \InvalidArgumentException("알 수 없는 다이어그램 키: {$diagramKey}");
        }

        $provider   = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $docContent = $this->truncateDoc($planningDoc->content ?? '');
        $screenList = $this->formatScreenList($screens);
        $def        = self::DIAGRAMS[$diagramKey];

        $result = $this->generateDiagram(
            provider:   $provider,
            projectId:  $artifact->project_id,
            key:        $diagramKey,
            def:        $def,
            docContent: $docContent,
            screenList: $screenList,
            screens:    $screens,
            artifactId: $artifact->id,
            userId:     $userId,
        );

        $meta    = $artifact->meta ?? [];
        $failed  = $meta['failed_diagrams'] ?? [];
        unset($failed[$diagramKey]);

        $meta[$diagramKey] = $result['content'];
        $diagrams = [
            'ia_diagram'   => $meta['ia_diagram']   ?? '',
            'flow_diagram' => $meta['flow_diagram']  ?? '',
        ];

        $markdown = $this->renderMarkdown($planningDoc, $diagrams);

        $artifact->updateWithVersion(
            content: $markdown,
            userId:  $userId,
            meta: array_merge($meta, [
                'failed_diagrams'   => $failed,
                'change_type'       => 'diagram_regenerated',
                'regenerated_key'   => $diagramKey,
                'updated_at'        => now()->toIso8601String(),
            ]),
        );

        return $result;
    }

    // ── 내부 생성 ─────────────────────────────────────────────────────────────

    private function generateDiagram(
        AnthropicProvider              $provider,
        int                            $projectId,
        string                         $key,
        array                          $def,
        string                         $docContent,
        string                         $screenList,
        \Illuminate\Support\Collection $screens,
        int                            $artifactId,
        int                            $userId,
    ): array {
        $systemPrompt = $this->prompts->render('planning', $def['task_type'])
            ?? $this->getFallbackPrompt($key);

        $userContent = $this->buildInput($key, $docContent, $screenList);

        $response = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generate(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                options:      ['max_tokens' => $def['max_tokens']],
            ),
            userId:     $userId,
            projectId:  $projectId,
            artifactId: $artifactId,
            stage:      'planning',
            taskType:   $def['task_type'],
        );

        $raw = trim($response->text);
        // Strip accidental code fences if the model included them
        $raw = preg_replace('/^```(?:mermaid)?\s*/i', '', $raw);
        $raw = preg_replace('/\s*```\s*$/i', '', $raw);

        return [
            'content'    => trim($raw),
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens),
            'model'      => $response->model,
        ];
    }

    private function buildInput(string $key, string $docContent, string $screenList): string
    {
        return match ($key) {
            'ia_diagram' => implode("\n\n---\n\n", [
                "# 기획서 내용 (발췌)\n{$docContent}",
                "# 화면 목록\n{$screenList}",
            ]),
            'flow_diagram' => implode("\n\n---\n\n", [
                "# 화면 목록\n{$screenList}",
                "# 기획서 내용 (발췌)\n{$docContent}",
            ]),
            default => $docContent,
        };
    }

    private function renderMarkdown(AiAgentArtifact $planningDoc, array $diagrams): string
    {
        $ia   = $diagrams['ia_diagram']  ?? '';
        $flow = $diagrams['flow_diagram'] ?? '';

        $parts = ["# IA / 화면 흐름도\n\n> 기획서 기반 자동 생성 — " . now()->format('Y-m-d H:i')];

        if ($ia) {
            $parts[] = "## IA 구조도\n\n```mermaid\n{$ia}\n```";
        }
        if ($flow) {
            $parts[] = "## 화면 흐름도\n\n```mermaid\n{$flow}\n```";
        }

        return implode("\n\n", $parts);
    }

    // ── 추적성 링크 ───────────────────────────────────────────────────────────

    private function createTraceabilityLinks(int $projectId, AiAgentArtifact $ia, AiAgentArtifact $planningDoc): void
    {
        $this->traceability->link(
            projectId: $projectId, sourceType: 'artifact', sourceId: $ia->id,
            sourceRef: "IA_FLOW#{$ia->id}", targetType: 'artifact', targetId: $planningDoc->id,
            targetRef: "PLANNING_DOC#{$planningDoc->id}", linkType: 'derived_from',
        );

        foreach (AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->get() as $screen) {
            $this->traceability->link(
                projectId: $projectId, sourceType: 'artifact', sourceId: $ia->id,
                sourceRef: "IA_FLOW#{$ia->id}", targetType: 'screen', targetId: $screen->id,
                targetRef: $screen->screen_id, linkType: 'documents',
            );
        }
    }

    // ── 유틸리티 ─────────────────────────────────────────────────────────────

    private function truncateDoc(string $content, int $maxChars = 3000): string
    {
        if (mb_strlen($content) <= $maxChars) {
            return $content;
        }
        return mb_substr($content, 0, $maxChars) . "\n...(이하 생략)";
    }

    private function formatScreenList(\Illuminate\Support\Collection $screens): string
    {
        if ($screens->isEmpty()) {
            return '(등록된 화면 없음)';
        }
        return $screens->map(fn($s) => "- [{$s->screen_id}] {$s->title}: {$s->description}")->join("\n");
    }

    private function getFallbackPrompt(string $key): string
    {
        return match ($key) {
            'ia_diagram'   => '당신은 IA 설계 전문가입니다. 화면 목록과 기획서를 바탕으로 Mermaid graph LR 형식의 IA 구조도를 작성하세요. 코드만 출력하세요.',
            'flow_diagram' => '당신은 UX 설계 전문가입니다. 화면 목록을 바탕으로 Mermaid flowchart TD 형식의 화면 흐름도를 작성하세요. 코드만 출력하세요.',
            default        => '당신은 IA 설계 전문가입니다. Mermaid 다이어그램을 작성하세요.',
        };
    }
}
