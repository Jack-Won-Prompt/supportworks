<?php

namespace App\Services\Analysis;

use App\Models\Project;
use App\Models\PlanningDoc;
use App\Models\Requirement;
use App\Models\Agent\Deliverable;
use App\Services\Agent\AnthropicProvider;
use App\Services\Agent\Contracts\AIProvider;
use App\Services\Agent\FallbackAIProvider;
use App\Services\Agent\OpenAiProvider;
use App\Services\Agent\AiProviderFactory;
use App\Services\AiError;

/**
 * 신규 요구사항이 프로젝트의 기획서/URS/FRS 범위 내인지, 기존 요구사항과 중복인지 검증.
 *
 * 정책:
 *   - OpenAI primary → Claude(Anthropic) fallback
 *   - 응답 JSON 강제 (yes/no 이분법 + 이유 텍스트 + 중복 시 원본 ID)
 *   - 실패 시 "검증 불가" 상태 반환 (등록 자체는 막지 않음 — 사용자에게 위임)
 */
class RequirementValidationService
{
    public function __construct(private readonly AiProviderFactory $factory) {}

    /**
     * @return array{
     *   ok: bool,
     *   out_of_scope: bool,
     *   scope_reason: ?string,
     *   duplicate_of_id: ?int,
     *   duplicate_of_title: ?string,
     *   duplicate_reason: ?string,
     *   error: ?string
     * }
     */
    public function validate(Project $project, string $title, string $description): array
    {
        $context = $this->collectContext($project);

        $systemPrompt = $this->systemPrompt();
        $userPrompt   = $this->userPrompt($title, $description, $context);

        try {
            $provider = $this->makeProvider();
            $response = $provider->generate($systemPrompt, [
                ['role' => 'user', 'content' => $userPrompt],
            ], [
                'response_format' => 'json',
                'temperature'     => 0.1,
                'max_tokens'      => 1200,
            ]);

            $raw = (string) ($response->text ?? '');
            $parsed = $this->parseJson($raw);

            if ($parsed === null) {
                return $this->errorResult('AI 응답을 해석하지 못했습니다.');
            }

            // 중복 ID가 기존 요구사항 목록에 실재하는지 검증 (할루시네이션 방지)
            $duplicateOfId = $parsed['duplicate_of_id'] ?? null;
            $duplicateTitle = null;
            if ($duplicateOfId !== null) {
                $duplicateOfId = (int) $duplicateOfId;
                $match = collect($context['existing'])->firstWhere('id', $duplicateOfId);
                if (!$match) {
                    $duplicateOfId = null; // 환각된 ID 무효화
                } else {
                    $duplicateTitle = $match['title'];
                }
            }

            return [
                'ok'                 => true,
                'out_of_scope'       => (bool) ($parsed['out_of_scope'] ?? false),
                'scope_reason'       => $this->trimReason($parsed['scope_reason'] ?? null),
                'duplicate_of_id'    => $duplicateOfId,
                'duplicate_of_title' => $duplicateTitle,
                'duplicate_reason'   => $this->trimReason($parsed['duplicate_reason'] ?? null),
                'error'              => null,
            ];
        } catch (\Throwable $e) {
            AiError::record($e);
            return $this->errorResult('AI 검증 호출이 실패했습니다: ' . $e->getMessage());
        }
    }

    private function errorResult(string $msg): array
    {
        return [
            'ok'                 => false,
            'out_of_scope'       => false,
            'scope_reason'       => null,
            'duplicate_of_id'    => null,
            'duplicate_of_title' => null,
            'duplicate_reason'   => null,
            'error'              => $msg,
        ];
    }

    /** OpenAI primary + Anthropic fallback (정책상 OpenAI를 우선) */
    private function makeProvider(): AIProvider
    {
        $primary   = $this->factory->make(AiProviderFactory::OPENAI);
        $secondary = null;
        try {
            $secondary = $this->factory->make(AiProviderFactory::ANTHROPIC);
        } catch (\Throwable) {
            // Anthropic 키 없으면 primary 단독
        }

        if ($secondary === null) {
            return $primary;
        }
        return new FallbackAIProvider($primary, $secondary);
    }

    /**
     * 프로젝트의 기획서 + URS + FRS + 기존 요구사항(취소 제외) 수집.
     * @return array{plans:array<int,array>, urs:?string, frs:?string, existing:array<int,array>}
     */
    private function collectContext(Project $project): array
    {
        $plans = PlanningDoc::where('project_id', $project->id)
            ->whereIn('status', ['approved', 'pending_review', 'ai_processed'])
            ->orderByDesc('version')
            ->limit(3)
            ->get(['id', 'title', 'content', 'version', 'status']);

        $plansArr = $plans->map(fn ($p) => [
            'id'      => $p->id,
            'title'   => (string) ($p->title ?? ''),
            'version' => (int) ($p->version ?? 1),
            'content' => $this->truncate((string) ($p->content ?? ''), 6000),
        ])->all();

        // URS / FRS 텍스트 수집
        $urs = $this->collectDeliverableText($project->id, ['USR', 'URS']);
        $frs = $this->collectDeliverableText($project->id, ['FRS']);

        // 기존 요구사항 (취소 제외, deleted 제외)
        $existing = Requirement::where('project_id', $project->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('id')
            ->get(['id', 'title', 'description', 'category'])
            ->map(fn ($r) => [
                'id'          => $r->id,
                'title'       => (string) $r->title,
                'description' => $this->truncate((string) ($r->description ?? ''), 600),
                'category'    => (string) $r->category,
            ])
            ->all();

        return [
            'plans'    => $plansArr,
            'urs'      => $urs,
            'frs'      => $frs,
            'existing' => $existing,
        ];
    }

    /** Deliverable 본문 텍스트를 type_id 후보 중 첫 매치로 반환. */
    private function collectDeliverableText(int $projectId, array $typeIds): ?string
    {
        $deliverable = Deliverable::where('project_id', $projectId)
            ->whereIn('type_id', $typeIds)
            ->with(['stepData'])
            ->first();

        if (!$deliverable) return null;

        $parts = [];
        foreach ($deliverable->stepData ?? [] as $sd) {
            $val = (string) ($sd->value ?? '');
            if ($val !== '') $parts[] = $val;
        }
        $text = trim(implode("\n\n", $parts));
        return $text === '' ? null : $this->truncate($text, 8000);
    }

    private function truncate(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) return $s;
        return mb_substr($s, 0, $max) . "\n...(이하 생략)";
    }

    private function trimReason(?string $s): ?string
    {
        if ($s === null) return null;
        $s = trim($s);
        return $s === '' ? null : mb_substr($s, 0, 800);
    }

    private function parseJson(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') return null;

        // ```json ... ``` 블록 안에 들어있을 경우 추출
        if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $raw, $m)) {
            $raw = $m[1];
        }
        // 응답이 JSON 단독이 아닌 경우 첫 { ... } 매치
        if (!str_starts_with($raw, '{')) {
            if (preg_match('/\{.*\}/s', $raw, $m)) {
                $raw = $m[0];
            }
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function systemPrompt(): string
    {
        return <<<'PROMPT'
당신은 소프트웨어 프로젝트의 요구사항 검토 전문가입니다.
주어진 [프로젝트 컨텍스트(기획서, URS, FRS)]와 [기존 요구사항 목록]을 기준으로
새 요구사항이 (1) 프로젝트 범위를 벗어났는지, (2) 기존 요구사항과 중복인지를 판단합니다.

판단 원칙:
- 범위(scope): 기획서·URS·FRS에 명시된 시스템 경계·기능·비기능 요건을 기준으로 판단.
  명시되지 않은 새로운 도메인·시스템 외부 기능·과도한 확장은 "범위 벗어남"으로 본다.
  단순 보완·세부화는 범위 안으로 본다.
- 중복(duplicate): 기존 요구사항과 동일/근사 기능을 다른 표현으로 요청한 경우만 중복.
  관련은 있지만 다른 측면(예: 같은 화면의 다른 동작)은 중복 아님.
- 둘 다 판정은 boolean (true/false) 이분법.
- 중복인 경우 반드시 기존 목록의 정확한 id 를 반환. 추측이나 임의 ID 금지.
- 이유(reason)는 한국어로 1~3문장, 사용자가 납득할 수 있게 구체적으로.

출력은 다음 JSON 스키마를 정확히 따른다. 추가 텍스트 금지:
{
  "out_of_scope": true|false,
  "scope_reason": "...범위 판단 근거 (out_of_scope=false면 null 가능)",
  "duplicate_of_id": null | <기존 요구사항 id>,
  "duplicate_reason": "...중복 판단 근거 (duplicate_of_id=null이면 null 가능)"
}
PROMPT;
    }

    private function userPrompt(string $title, string $description, array $context): string
    {
        $plansText = '';
        foreach ($context['plans'] as $p) {
            $plansText .= "## 기획서: {$p['title']} (v{$p['version']})\n{$p['content']}\n\n";
        }
        if ($plansText === '') $plansText = "(등록된 기획서 없음)\n";

        $ursText = $context['urs'] ?? '(URS 본문 없음)';
        $frsText = $context['frs'] ?? '(FRS 본문 없음)';

        $existingText = '';
        foreach ($context['existing'] as $r) {
            $existingText .= "[#{$r['id']}] ({$r['category']}) {$r['title']}\n  설명: {$r['description']}\n";
        }
        if ($existingText === '') $existingText = "(기존 요구사항 없음)\n";

        $newDesc = $description !== '' ? $description : '(설명 없음)';

        return <<<PROMPT
# 프로젝트 컨텍스트

{$plansText}

## URS (User Requirements Specification)
{$ursText}

## FRS (Functional Requirements Specification)
{$frsText}

# 기존 요구사항 목록 (취소 제외)
{$existingText}

# 검토 대상 — 새 요구사항
제목: {$title}
설명: {$newDesc}

위 새 요구사항에 대해 JSON 스키마에 맞춰 판단하세요.
PROMPT;
    }
}
