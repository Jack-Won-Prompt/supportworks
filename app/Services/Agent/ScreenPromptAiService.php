<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentStackStandard;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class ScreenPromptAiService
{
    private const TASK_TYPE  = 'screen_prompt_generator';
    private const MAX_TOKENS = 2000;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * 단일 화면 프롬프트 생성 및 저장.
     *
     * @return array{artifact: AiAgentArtifact, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateForScreen(
        int           $projectId,
        int           $stageId,
        AiAgentScreen $screen,
        int           $userId,
    ): array {
        $provider     = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context      = $this->buildContext($projectId, $screen);
        $systemPrompt = $this->prompts->render('planning', self::TASK_TYPE)
            ?? $this->getFallbackSystemPrompt();
        $userContent  = $this->buildUserMessage($context);

        $response = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generate(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userContent]],
                options:      ['max_tokens' => self::MAX_TOKENS],
            ),
            userId:     $userId,
            projectId:  $projectId,
            stage:      'planning',
            taskType:   self::TASK_TYPE,
        );

        $content = trim($response->text);
        $cost    = $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens);

        $artifact = $this->persistPrompt($projectId, $stageId, $screen, $content, [
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'model'      => $response->model,
        ], $userId);

        return [
            'artifact'   => $artifact,
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'model'      => $response->model,
        ];
    }

    /**
     * 일괄 생성 — 지정된 screen ID 목록을 순차 처리.
     *
     * @param  int[]     $screenIds
     * @return array{total: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateBatch(
        int      $projectId,
        int      $stageId,
        array    $screenIds,
        int      $userId,
        callable $onProgress,
    ): array {
        $screens   = AiAgentScreen::whereIn('id', $screenIds)->orderBy('order')->get();
        $total     = $screens->count();
        $done      = 0;
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';

        foreach ($screens as $screen) {
            $onProgress(['done' => $done, 'total' => $total, 'screen_id' => $screen->screen_id, 'title' => $screen->title, 'status' => 'processing']);

            try {
                $result     = $this->generateForScreen($projectId, $stageId, $screen, $userId);
                $totalIn   += $result['tokens_in'];
                $totalOut  += $result['tokens_out'];
                $totalCost += $result['cost'];
                $lastModel  = $result['model'];
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $failed[$screen->screen_id] = $e->getMessage();
            }

            $done++;
            $onProgress([
                'done'      => $done,
                'total'     => $total,
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'status'    => isset($failed[$screen->screen_id]) ? 'failed' : 'done',
            ]);
        }

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

    // ── 컨텍스트 빌더 ─────────────────────────────────────────────────────────

    private function buildContext(int $projectId, AiAgentScreen $screen): array
    {
        $config    = ProjectAiAgentConfig::forProject($projectId);
        $stackType = $config?->frontend_stack;

        $stackInfo = [];
        if ($stackType) {
            $folderStructure = AiAgentStackStandard::getFolderStructure($stackType);
            $codeTemplate    = AiAgentStackStandard::getCodeTemplate($stackType);
            $stackInfo = [
                'type'             => $stackType->value,
                'label'            => $stackType->label(),
                'folder_structure' => $folderStructure,
                'code_template'    => $codeTemplate,
            ];
        }

        $planningDoc = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        $keyword     = strtolower($screen->title ?? '');
        $requirements = AiAgentRequirement::where('project_id', $projectId)->get();
        $relatedReqs  = $keyword
            ? $requirements->filter(fn($r) => str_contains(strtolower($r->title . ' ' . $r->description), $keyword))
            : collect();

        return [
            'screen'       => $screen,
            'stack'        => $stackInfo,
            'planning_doc' => $planningDoc,
            'related_reqs' => $relatedReqs->values(),
        ];
    }

    private function buildUserMessage(array $context): string
    {
        $screen      = $context['screen'];
        $stack       = $context['stack'];
        $planDoc     = $context['planning_doc'];
        $relatedReqs = $context['related_reqs'];

        $parts = [];

        $parts[] = "# 화면 정보\n"
            . "- ID: {$screen->screen_id}\n"
            . "- 이름: {$screen->title}\n"
            . "- 설명: " . ($screen->description ?: '(없음)');

        if (!empty($stack)) {
            $folderStr = empty($stack['folder_structure'])
                ? '(없음)'
                : json_encode($stack['folder_structure'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $codeStr   = empty($stack['code_template'])
                ? '(없음)'
                : json_encode($stack['code_template'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $parts[] = "# 프론트엔드 스택: {$stack['label']}\n"
                . "폴더 구조:\n{$folderStr}\n\n"
                . "컴포넌트 패턴:\n{$codeStr}";
        }

        if ($relatedReqs->isNotEmpty()) {
            $reqText = $relatedReqs->map(function ($r) {
                $pri = strtoupper($r->priority->value ?? $r->priority ?? '');
                return "- [{$r->req_id}][{$pri}] {$r->title}: {$r->description}";
            })->join("\n");
            $parts[] = "# 관련 요구사항\n{$reqText}";
        } else {
            $parts[] = "# 관련 요구사항\n(관련 요구사항 없음)";
        }

        if ($planDoc && $planDoc->content) {
            $excerpt = mb_substr($planDoc->content, 0, 2000);
            if (mb_strlen($planDoc->content) > 2000) {
                $excerpt .= "\n...(이하 생략)";
            }
            $parts[] = "# 기획서 내용 (발췌)\n{$excerpt}";
        }

        return implode("\n\n---\n\n", $parts);
    }

    // ── 저장 + 추적성 ─────────────────────────────────────────────────────────

    private function persistPrompt(
        int           $projectId,
        int           $stageId,
        AiAgentScreen $screen,
        string        $content,
        array         $usage,
        int           $userId,
    ): AiAgentArtifact {
        /** @var AiAgentArtifact $artifact */
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stageId,
            type:      ArtifactType::SCREEN_PROMPTS,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} 프롬프트",
            content:   $content,
            userId:    $userId,
            meta: [
                'change_type'  => 'ai_generated',
                'model'        => $usage['model'],
                'tokens_in'    => $usage['tokens_in'],
                'tokens_out'   => $usage['tokens_out'],
                'cost_usd'     => $usage['cost'],
                'generated_at' => now()->toIso8601String(),
            ],
        );

        // Quick-access field on screen record
        $screen->update(['generation_prompt' => $content]);

        // Traceability: screen → prompt artifact
        $this->traceability->link(
            $projectId,
            'screen', $screen->id, $screen->screen_id,
            'artifact', $artifact->id, "SCREEN_PROMPT#{$artifact->id}",
            'has_prompt'
        );

        // Traceability: prompt → planning doc (where it was derived from)
        $planDoc = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)
            ->where('scope_type', 'project')
            ->latest()->first();
        if ($planDoc) {
            $this->traceability->link(
                $projectId,
                'artifact', $artifact->id, "SCREEN_PROMPT#{$artifact->id}",
                'artifact', $planDoc->id, "PLANNING_DOC#{$planDoc->id}",
                'derived_from'
            );
        }

        // Traceability: related requirements → prompt artifact
        $keyword     = strtolower($screen->title ?? '');
        $requirements = AiAgentRequirement::where('project_id', $projectId)->get();
        $relatedReqs  = $keyword
            ? $requirements->filter(fn($r) => str_contains(strtolower($r->title . ' ' . $r->description), $keyword))
            : collect();
        foreach ($relatedReqs as $req) {
            $this->traceability->link(
                $projectId,
                'requirement', $req->id, $req->req_id,
                'artifact', $artifact->id, "SCREEN_PROMPT#{$artifact->id}",
                'reflected_in'
            );
        }

        return $artifact;
    }

    // ── 유틸리티 ─────────────────────────────────────────────────────────────

    private function getFallbackSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 UI/UX 프롬프트 엔지니어입니다.
주어진 화면 정보와 요구사항을 바탕으로, 웍스가 목업 코드를 생성할 때 사용할 구체적인 프롬프트를 마크다운으로 작성하세요.
화면 개요, 디자인 가이드, 구성 요소, 인터랙션, 화면 흐름, 기술 가이드, 제약 사항 섹션을 포함하세요.
응답은 프롬프트 본문만 작성하세요.
PROMPT;
    }
}
