<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageStatus;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentStackStandard;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\AiSetting;
use App\Models\SystemErrorLog;

class CodeGenPromptAiService
{
    private const TASK_TYPE  = 'code_gen_prompt_v1';
    private const MAX_TOKENS = 4000;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    /**
     * @return array{artifact: AiAgentArtifact, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateForScreen(int $projectId, AiAgentScreen $screen, int $userId): array
    {
        $provider     = new AnthropicProvider(AiSetting::current()->anthropicKey());
        $context      = $this->buildContext($projectId, $screen);
        $systemPrompt = $this->prompts->render('dev_prep', self::TASK_TYPE)
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
            stage:      'dev_prep',
            taskType:   self::TASK_TYPE,
        );

        $content = trim($response->text);
        $cost    = $this->usageLog->calculateCost($response->model, $response->inputTokens, $response->outputTokens);

        $stage    = $this->resolveDevPrepStage($projectId);
        $artifact = AiAgentArtifact::upsertForScope(
            projectId: $projectId,
            stageId:   $stage->id,
            type:      ArtifactType::CODE_GEN_PROMPT,
            scopeType: 'screen',
            scopeId:   $screen->id,
            title:     "[{$screen->screen_id}] {$screen->title} 코드 생성 프롬프트",
            content:   $content,
            userId:    $userId,
            meta: [
                'change_type'  => 'ai_generated',
                'model'        => $response->model,
                'tokens_in'    => $response->inputTokens,
                'tokens_out'   => $response->outputTokens,
                'cost_usd'     => $cost,
                'generated_at' => now()->toIso8601String(),
            ],
        );

        $this->createTraceabilityLinks($projectId, $artifact, $screen, $context);

        return [
            'artifact'   => $artifact,
            'tokens_in'  => $response->inputTokens,
            'tokens_out' => $response->outputTokens,
            'cost'       => $cost,
            'model'      => $response->model,
        ];
    }

    /**
     * @param  int[]|null $screenIds  null = 전체 화면
     * @return array{total: int, done: int, failed_count: int, failed: array, tokens_in: int, tokens_out: int, cost: float, model: string}
     */
    public function generateBatch(
        int      $projectId,
        ?array   $screenIds,
        bool     $onlyMissing,
        int      $userId,
        callable $onProgress,
    ): array {
        $query = AiAgentScreen::where('project_id', $projectId)->active()->orderBy('order');
        if ($screenIds !== null) {
            $query->whereIn('id', $screenIds);
        }
        $screens = $query->get();

        if ($onlyMissing) {
            $existingIds = AiAgentArtifact::where('project_id', $projectId)
                ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
                ->where('scope_type', 'screen')
                ->pluck('scope_id')
                ->map(fn($id) => (int) $id)
                ->toArray();
            $screens = $screens->filter(fn($s) => !in_array($s->id, $existingIds))->values();
        }

        $total     = $screens->count();
        $done      = 0;
        $failed    = [];
        $totalIn   = 0;
        $totalOut  = 0;
        $totalCost = 0.0;
        $lastModel = '';

        foreach ($screens as $screen) {
            $onProgress([
                'done'      => $done,
                'total'     => $total,
                'screen_id' => $screen->screen_id,
                'title'     => $screen->title,
                'status'    => 'processing',
                'progress'  => $total > 0 ? round(($done / $total) * 90) : 0,
            ]);

            try {
                $result     = $this->generateForScreen($projectId, $screen, $userId);
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
                'progress'  => $total > 0 ? round(($done / $total) * 90) : 90,
            ]);
        }

        return [
            'total'        => $total,
            'done'         => $done,
            'failed_count' => count($failed),
            'failed'       => $failed,
            'tokens_in'    => $totalIn,
            'tokens_out'   => $totalOut,
            'cost'         => $totalCost,
            'model'        => $lastModel,
        ];
    }

    // ── Context builder ───────────────────────────────────────────────────────

    public function buildContext(int $projectId, AiAgentScreen $screen): array
    {
        $config    = ProjectAiAgentConfig::forProject($projectId);
        $stackType = $config?->frontend_stack;
        $stackInfo = [];
        if ($stackType) {
            $stackInfo = [
                'type'             => $stackType->value,
                'label'            => $stackType->label(),
                'folder_structure' => AiAgentStackStandard::getFolderStructure($stackType),
                'code_template'    => AiAgentStackStandard::getCodeTemplate($stackType),
            ];
        }

        $planDoc = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::PLANNING_DOC->value)->where('scope_type', 'project')
            ->latest('id')->first();

        $keyword = strtolower($screen->title ?? '');
        $allReqs = AiAgentRequirement::where('project_id', $projectId)->get();
        $relReqs = $keyword
            ? $allReqs->filter(fn($r) => str_contains(strtolower($r->title . ' ' . $r->description), $keyword))
            : collect();

        $designTokens  = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::DESIGN_TOKENS->value)->where('scope_type', 'project')
            ->latest('id')->first();
        $componentSpec = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::COMPONENT_SPEC->value)->where('scope_type', 'project')
            ->latest('id')->first();
        $layoutSpec = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::LAYOUT_SPEC->value)->where('scope_type', 'project')
            ->latest('id')->first();
        $erdArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)->latest('id')->first();
        $apiArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::API_SPEC->value)->latest('id')->first();
        $rbacArtifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)->latest('id')->first();

        return [
            'screen'         => $screen,
            'stack'          => $stackInfo,
            'plan_doc'       => $planDoc,
            'related_reqs'   => $relReqs->values(),
            'design_tokens'  => $designTokens,
            'component_spec' => $componentSpec,
            'layout_spec'    => $layoutSpec,
            'erd_artifact'   => $erdArtifact,
            'api_artifact'   => $apiArtifact,
            'rbac_artifact'  => $rbacArtifact,
        ];
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function buildUserMessage(array $ctx): string
    {
        $screen = $ctx['screen'];
        $parts  = [];

        $figmaLine = $screen->figma_url ? "\n- Figma URL: {$screen->figma_url}" : '';
        $parts[] = "# 화면 정보\n"
            . "- ID: {$screen->screen_id}\n"
            . "- 화면명: {$screen->title}\n"
            . "- 설명: " . ($screen->description ?: '(없음)')
            . $figmaLine;

        if (!empty($ctx['stack'])) {
            $s        = $ctx['stack'];
            $folderStr = empty($s['folder_structure'])
                ? '(없음)'
                : json_encode($s['folder_structure'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $codeStr  = empty($s['code_template'])
                ? '(없음)'
                : json_encode($s['code_template'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $parts[] = "# 프론트엔드 스택: {$s['label']}\n폴더 구조:\n{$folderStr}\n\n컴포넌트 패턴:\n{$codeStr}";
        }

        if ($ctx['related_reqs']->isNotEmpty()) {
            $reqText = $ctx['related_reqs']->map(function ($r) {
                $pri = strtoupper(is_object($r->priority) ? $r->priority->value : ($r->priority ?? ''));
                return "- [{$r->req_id}][{$pri}] {$r->title}: {$r->description}";
            })->join("\n");
            $parts[] = "# 관련 요구사항\n{$reqText}";
        }

        if ($ctx['plan_doc']?->content) {
            $parts[] = "# 기획서 (발췌)\n" . mb_substr($ctx['plan_doc']->content, 0, 1500);
        }

        if ($ctx['design_tokens']?->content) {
            $parts[] = "# 디자인 토큰 (발췌)\n" . mb_substr($ctx['design_tokens']->content, 0, 1200);
        }

        if ($ctx['component_spec']?->content) {
            $parts[] = "# 컴포넌트 명세서 (발췌)\n" . mb_substr($ctx['component_spec']->content, 0, 1200);
        }

        if ($ctx['layout_spec']?->content) {
            $parts[] = "# 표준 레이아웃 (발췌)\n" . mb_substr($ctx['layout_spec']->content, 0, 800);
        }

        if ($ctx['erd_artifact']?->content) {
            $parts[] = "# ERD (발췌)\n" . $this->extractErdExcerpt($ctx['erd_artifact']->content);
        }

        if ($ctx['api_artifact']?->content) {
            $parts[] = "# API 명세서 (발췌)\n" . $this->extractApiExcerpt($ctx['api_artifact']->content);
        }

        if ($ctx['rbac_artifact']?->content) {
            $parts[] = "# RBAC 권한 모델 (발췌)\n" . $this->extractRbacExcerpt($ctx['rbac_artifact']->content);
        }

        return implode("\n\n---\n\n", $parts);
    }

    private function extractErdExcerpt(string $content): string
    {
        $data   = json_decode($content, true);
        $tables = $data['tables'] ?? [];
        if (empty($tables)) return mb_substr($content, 0, 1500);

        $lines = [];
        foreach ($tables as $name => $table) {
            $lines[] = "**{$name}**: " . ($table['description'] ?? '');
            foreach (array_slice($table['columns'] ?? [], 0, 4) as $col => $def) {
                $colType = is_array($def) ? ($def['type'] ?? '') : (string) $def;
                $lines[] = "  - {$col}: {$colType}";
            }
        }
        return mb_substr(implode("\n", $lines), 0, 1500);
    }

    private function extractApiExcerpt(string $content): string
    {
        $data  = json_decode($content, true);
        $paths = $data['spec']['paths'] ?? [];
        if (empty($paths)) return mb_substr($content, 0, 1500);

        $lines = [];
        foreach ($paths as $path => $methods) {
            foreach ($methods as $method => $op) {
                $lines[] = strtoupper($method) . " {$path}: " . ($op['summary'] ?? '');
            }
        }
        return mb_substr(implode("\n", $lines), 0, 1500);
    }

    private function extractRbacExcerpt(string $content): string
    {
        $data  = json_decode($content, true);
        $roles = $data['roles'] ?? [];
        if (empty($roles)) return mb_substr($content, 0, 800);

        $lines = ['## 역할 목록'];
        foreach ($roles as $role) {
            $permCount = count($role['permissions'] ?? []);
            $lines[]   = "- **{$role['name']}** (`{$role['key']}`): 권한 {$permCount}개";
        }
        return mb_substr(implode("\n", $lines), 0, 800);
    }

    private function resolveDevPrepStage(int $projectId): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $projectId)
            ->where('type', StageType::DEV_PREP)
            ->first()
            ?? AiAgentProjectStage::create([
                'project_id' => $projectId,
                'type'       => StageType::DEV_PREP,
                'name'       => '개발 준비',
                'status'     => StageStatus::IN_PROGRESS,
                'order'      => 3,
            ]);
    }

    private function createTraceabilityLinks(
        int             $projectId,
        AiAgentArtifact $artifact,
        AiAgentScreen   $screen,
        array           $context,
    ): void {
        try {
            $this->traceability->link(
                $projectId,
                'screen', $screen->id, $screen->screen_id,
                'artifact', $artifact->id, "CODE_GEN_PROMPT#{$artifact->id}",
                'has_prompt',
            );
            foreach (['erd_artifact' => 'ERD', 'api_artifact' => 'API_SPEC', 'rbac_artifact' => 'RBAC'] as $key => $label) {
                $src = $context[$key];
                if ($src) {
                    $this->traceability->link(
                        $projectId,
                        'artifact', $artifact->id, "CODE_GEN_PROMPT#{$artifact->id}",
                        'artifact', $src->id, "{$label}#{$src->id}",
                        'derived_from',
                    );
                }
            }
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
        }
    }

    private function getFallbackSystemPrompt(): string
    {
        return <<<'PROMPT'
당신은 풀스택 개발 프롬프트 엔지니어입니다.

제공된 화면 정보와 프로젝트 아키텍처 컨텍스트를 분석하여,
개발자가 웍스 코드 생성 도구에 바로 붙여넣을 수 있는 "코드 생성 프롬프트"를 작성하세요.

이 프롬프트는 Claude, GPT-4 등의 웍스에 전달되어 실제 프로덕션 수준의 코드를 생성합니다.

작성 원칙:
1. **화면 목적과 범위** — 이 화면이 무엇을 하는지, 어떤 사용자가 사용하는지 명확히
2. **UI 요소와 레이아웃** — 구체적인 컴포넌트, 폼 필드, 버튼, 테이블, 카드 등
3. **상태 관리** — 로딩, 에러, 빈 상태, 성공 상태 처리 방법
4. **API 연동** — 사용할 엔드포인트, 요청/응답 데이터 구조, HTTP 메서드
5. **권한 체크** — RBAC 권한 검증 위치와 방법, 접근 불가 처리
6. **기술 스택 준수** — 폴더 위치, 컴포넌트 패턴, 디자인 토큰 사용 방법
7. **엣지 케이스** — 권한 없음, 데이터 없음, 네트워크 오류, 빈 목록 등

출력 지침:
- 마크다운 형식으로 구조화하여 작성
- 웍스가 바로 실행할 수 있을 정도로 구체적으로 작성
- 프롬프트 본문만 출력 (메타 설명 없이)
PROMPT;
    }
}
