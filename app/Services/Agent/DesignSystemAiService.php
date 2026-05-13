<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Contracts\AIProvider;

class DesignSystemAiService
{
    public function __construct(
        private readonly AIProvider           $provider,
        private readonly AgentUsageLogService $usageLog,
    ) {}

    /**
     * Generate a short design philosophy paragraph from the system data.
     */
    public function generatePhilosophy(array $context, int $userId, int $projectId): string
    {
        $projectName = $context['project']?->name ?? 'Unknown Project';
        $tokenCount  = count($context['tokens'] ?? []);
        $compCount   = count(($context['components'] ?? [])['components'] ?? []);
        $score       = $context['review']['$metadata']['stats']['compliance_score'] ?? null;

        $prompt = <<<EOT
        You are a UX/UI design consultant. Based on the design system data below, write a concise design philosophy paragraph (2-3 sentences) in Korean for the project design documentation.

        Project: {$projectName}
        Token count: {$tokenCount}
        Component count: {$compCount}
        Consistency score: {$score}

        Write a philosophy that explains the core design principles, visual language, and quality standards. Be specific and professional.
        EOT;

        $response = $this->usageLog->callAndLog(
            provider:   $this->provider,
            call:       fn() => $this->provider->generate(
                systemPrompt: '당신은 UI/UX 디자인 컨설턴트입니다. 디자인 시스템 문서의 철학 섹션을 한국어로 작성합니다.',
                messages:     [['role' => 'user', 'content' => $prompt]],
                options:      ['max_tokens' => 400],
            ),
            userId:    $userId,
            projectId: $projectId,
            stage:     StageType::DESIGN->value,
            taskType:  'design_system_philosophy',
        );

        return trim($response->text);
    }

    /**
     * Fill in empty component descriptions.
     * Returns map of component key => description string.
     */
    public function enrichComponentDescriptions(array $components, int $userId, int $projectId): array
    {
        $toEnrich = [];
        foreach ($components['components'] ?? [] as $key => $comp) {
            if (empty($comp['description'])) {
                $toEnrich[$key] = $comp;
            }
        }

        if (empty($toEnrich)) {
            return [];
        }

        $list = collect($toEnrich)
            ->take(20)
            ->map(fn($c, $k) => "- {$k}: {$c['name']} (variants: {$c['variants_count']}개)")
            ->join("\n");

        $prompt = <<<EOT
        아래 UI 컴포넌트 목록의 각 컴포넌트에 대해 한 줄 설명을 한국어로 작성하세요.
        형식: 컴포넌트키: 설명 (한 줄)

        {$list}
        EOT;

        $response = $this->usageLog->callAndLog(
            provider:   $this->provider,
            call:       fn() => $this->provider->generate(
                systemPrompt: '당신은 UI 컴포넌트 문서 작성 전문가입니다. 각 컴포넌트에 대해 한 줄 설명을 작성합니다.',
                messages:     [['role' => 'user', 'content' => $prompt]],
                options:      ['max_tokens' => 1000],
            ),
            userId:    $userId,
            projectId: $projectId,
            stage:     StageType::DESIGN->value,
            taskType:  'design_system_component_desc',
        );

        // Parse "key: description" lines
        $enriched = [];
        foreach (explode("\n", $response->text) as $line) {
            if (preg_match('/^([^:]+):\s*(.+)$/', trim($line), $m)) {
                $enriched[trim($m[1])] = trim($m[2]);
            }
        }

        return $enriched;
    }

    /**
     * Save artifact and return it.
     */
    public function saveArtifact(Project $project, array $data, User $user): AiAgentArtifact
    {
        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        return AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::DESIGN_SYSTEM_DOC,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "디자인 시스템 — {$project->name}",
            content:   json_encode($data, JSON_UNESCAPED_UNICODE),
            userId:    $user->id,
            meta: [
                'generated_at'    => now()->toIso8601String(),
                'has_ai_sections' => !empty($data['ai_sections']),
                'token_count'     => count($data['tokens'] ?? []),
                'component_count' => count(($data['components'] ?? [])['components'] ?? []),
            ],
        );
    }
}
