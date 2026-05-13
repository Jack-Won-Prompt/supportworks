<?php

namespace App\Services\Agent;

use App\Enums\Agent\FrontendStack;
use App\Models\Agent\AiAgentScreen;
use App\Models\AiSetting;

/**
 * Calls Claude (Tool Use) to review a single screen's frontend code.
 * Uses the record_code_validation tool for structured output.
 */
class AiCodeReviewer
{
    private const MAX_TOKENS = 6000;
    private const TIMEOUT    = 180;

    private const FALLBACK_SYSTEM_PROMPT = <<<'PROMPT'
당신은 시니어 풀스택 개발자이자 코드 리뷰어입니다.

주어진 화면의 Frontend 코드를 검토하여 다음 5가지 영역을 평가합니다:

1. 명세 부합도 (spec_compliance) — T37 API 명세와 일치하는 호출? T38 RBAC 권한 검증 포함? T36 ERD 데이터 모델 부합?
2. 코드 품질 (code_quality) — 명명 규칙(스택별), 함수 분리/응집도, 중복 제거
3. 보안 (security) — XSS 위험, 비밀 정보 하드코딩, 권한 검증 누락, localStorage 토큰 저장
4. 베스트 프랙티스 (best_practices) — 스택별 관례 준수, 에러 처리, 접근성
5. 성능 (performance) — 불필요한 연산, 메모리 누수 가능성, 렌더링 최적화

각 위반에는 severity(critical/warning/info), 구체적 파일·라인, 수정 제안, auto_fixable 여부를 포함하세요.
정적 분석 결과(static_analysis)가 있다면 그것을 보강하되 중복 보고는 피하세요.

record_code_validation 도구로 응답하세요.
PROMPT;

    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
    ) {}

    /**
     * Review a single screen's code.
     *
     * @param  array  $codeContent  decoded T40 artifact content
     * @param  array  $staticResult  from CodeStaticAnalyzer::analyze()
     * @param  array  $context       from CodeValidationService::buildContext()
     * @return array{result:array, tokensIn:int, tokensOut:int, model:string}
     */
    public function reviewScreen(
        AiAgentScreen $screen,
        array         $codeContent,
        array         $staticResult,
        array         $context,
        int           $userId,
        int           $projectId,
    ): array {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());

        $systemPrompt = $this->prompts->render('dev', 'code_review_v1')
            ?? self::FALLBACK_SYSTEM_PROMPT;

        $userMessage = $this->buildUserMessage($screen, $codeContent, $staticResult, $context);

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     [['role' => 'user', 'content' => $userMessage]],
                tools:        [$this->getReviewTool()],
                options:      ['max_tokens' => self::MAX_TOKENS, 'timeout' => self::TIMEOUT],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev',
            taskType:  'code_review_v1',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        // Normalize / ensure required fields
        $toolInput['compliance_score']  ??= 50;
        $toolInput['category_scores']   ??= $this->zeroScores();
        $toolInput['violations']        ??= [];
        $toolInput['strengths']         ??= [];

        // Tag 웍스 violations as source=ai and assign ids
        foreach ($toolInput['violations'] as &$v) {
            $v['id']           ??= substr(md5(($v['title'] ?? '') . ($v['file'] ?? '') . ($v['line'] ?? 0)), 0, 12);
            $v['auto_fixable'] ??= false;
            $v['ignored']      ??= false;
            $v['source']       ??= 'ai';
        }
        unset($v);

        return [
            'result'    => $toolInput,
            'tokensIn'  => $response->inputTokens,
            'tokensOut' => $response->outputTokens,
            'model'     => $response->model,
        ];
    }

    /**
     * Generate a fix for a single auto_fixable violation.
     *
     * @return array{fixed_content:string, explanation:string, tokensIn:int, tokensOut:int}
     */
    public function generateAutoFix(
        AiAgentScreen $screen,
        array         $violation,
        array         $fileContent,
        int           $userId,
        int           $projectId,
    ): array {
        $provider = new AnthropicProvider(AiSetting::current()->anthropicKey());

        $filePath = $violation['file'] ?? '';
        $line     = $violation['line'] ?? 0;

        $userMessage = <<<MSG
다음 코드 파일에서 발견된 위반 사항을 자동 수정해주세요.

## 화면: [{$screen->screen_id}] {$screen->title}
## 파일: {$filePath}

## 위반 내용
- 카테고리: {$violation['category']}
- 심각도: {$violation['severity']}
- 설명: {$violation['description']}
- 라인: {$line}
- 수정 제안: {$violation['suggestion']}

## 현재 파일 내용
```
{$fileContent}
```

수정된 파일 전체 내용을 반환하고, 무엇을 어떻게 바꿨는지 간단히 설명해주세요.
apply_code_fix 도구로 응답하세요.
MSG;

        $fixTool = [
            'name'         => 'apply_code_fix',
            'description'  => '코드 파일의 위반 사항을 수정하고 결과를 반환합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'fixed_content' => ['type' => 'string', 'description' => '수정된 파일 전체 내용'],
                    'explanation'   => ['type' => 'string', 'description' => '수정 내용 설명 (한국어)'],
                ],
                'required' => ['fixed_content', 'explanation'],
            ],
        ];

        $response = $this->usageLog->callAndLog(
            provider:  $provider,
            call:      fn() => $provider->generateWithTools(
                systemPrompt: '당신은 코드 수정 전문가입니다. 지시된 위반 사항만 최소한으로 수정하고 나머지 코드는 그대로 유지하세요.',
                messages:     [['role' => 'user', 'content' => $userMessage]],
                tools:        [$fixTool],
                options:      ['max_tokens' => 8000, 'timeout' => 120],
            )->toAIResponse(),
            userId:    $userId,
            projectId: $projectId,
            stage:     'dev',
            taskType:  'code_auto_fix',
        );

        $toolInput = json_decode($response->text, true) ?? [];

        return [
            'fixed_content' => $toolInput['fixed_content'] ?? $fileContent,
            'explanation'   => $toolInput['explanation'] ?? '자동 수정이 적용되었습니다.',
            'tokensIn'      => $response->inputTokens,
            'tokensOut'     => $response->outputTokens,
        ];
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function buildUserMessage(
        AiAgentScreen $screen,
        array         $codeContent,
        array         $staticResult,
        array         $context,
    ): string {
        $stack    = $context['stack'] ?? 'unknown';
        $files    = $codeContent['files'] ?? [];
        $mainFile = $codeContent['main_file_path'] ?? '';

        // Build file listing
        $fileSections = '';
        foreach ($files as $file) {
            $lines     = $file['lines'] ?? (substr_count($file['content'] ?? '', "\n") + 1);
            $purpose   = $file['purpose'] ?? '';
            $fileSections .= "\n### {$file['path']} ({$lines}줄) — {$purpose}\n```\n{$file['content']}\n```\n";
        }

        // Static analysis summary
        $staticSummary = '';
        if (!empty($staticResult['available']) && !empty($staticResult['issues'])) {
            $staticSummary = "\n## 정적 분석 결과 (이미 발견된 이슈 — 중복 보고 금지)\n";
            foreach ($staticResult['issues'] as $issue) {
                $staticSummary .= "- [{$issue['severity']}][{$issue['category']}] {$issue['title']} ({$issue['file']}:{$issue['line']})\n";
            }
        } elseif (!empty($staticResult['available']) && empty($staticResult['issues'])) {
            $staticSummary = "\n## 정적 분석 결과: 이슈 없음\n";
        }

        // Context
        $erdSummary  = $this->summarizeErd($context['erd'] ?? null);
        $apiSummary  = $this->summarizeApi($context['api_spec'] ?? null);
        $rbacSummary = $this->summarizeRbac($context['rbac'] ?? null);

        return <<<MSG
## 검수 대상 화면
- 화면 ID: {$screen->screen_id}
- 화면명: {$screen->title}
- 스택: {$stack}
- 메인 파일: {$mainFile}

{$erdSummary}
{$apiSummary}
{$rbacSummary}
{$staticSummary}

## 생성된 코드 파일
{$fileSections}

위 코드를 5개 카테고리(spec_compliance, code_quality, security, best_practices, performance)로 검수하고 record_code_validation 도구로 결과를 기록하세요.
종합 점수(compliance_score)는 0~100으로 평가합니다.
MSG;
    }

    private function summarizeErd(?array $erd): string
    {
        if (!$erd || empty($erd['entities'])) return '';
        $names = array_column($erd['entities'] ?? [], 'name');
        return "\n## ERD 엔티티 목록\n" . implode(', ', array_slice($names, 0, 15));
    }

    private function summarizeApi(?array $api): string
    {
        if (!$api || empty($api['paths'])) return '';
        $paths = array_keys($api['paths']);
        $sample = array_slice($paths, 0, 12);
        return "\n## API 엔드포인트 (주요 12개)\n" . implode("\n", $sample);
    }

    private function summarizeRbac(?array $rbac): string
    {
        if (!$rbac || empty($rbac['roles'])) return '';
        $roles = array_column($rbac['roles'], 'name');
        return "\n## RBAC 역할\n" . implode(', ', $roles);
    }

    private function getReviewTool(): array
    {
        return [
            'name'         => 'record_code_validation',
            'description'  => '화면 Frontend 코드 검수 결과를 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'compliance_score' => [
                        'type'        => 'integer',
                        'minimum'     => 0,
                        'maximum'     => 100,
                        'description' => '종합 준수 점수 (0~100)',
                    ],
                    'category_scores' => [
                        'type'       => 'object',
                        'properties' => [
                            'spec_compliance' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'code_quality'    => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'security'        => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'best_practices'  => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                            'performance'     => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                        ],
                        'required' => ['spec_compliance', 'code_quality', 'security', 'best_practices', 'performance'],
                    ],
                    'strengths' => [
                        'type'        => 'array',
                        'items'       => ['type' => 'string'],
                        'description' => '잘된 점 1~3개',
                    ],
                    'violations' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'category'     => ['type' => 'string', 'enum' => ['spec_compliance', 'code_quality', 'security', 'best_practices', 'performance']],
                                'severity'     => ['type' => 'string', 'enum' => ['critical', 'warning', 'info']],
                                'title'        => ['type' => 'string'],
                                'description'  => ['type' => 'string'],
                                'file'         => ['type' => 'string'],
                                'line'         => ['type' => 'integer'],
                                'suggestion'   => ['type' => 'string'],
                                'auto_fixable' => ['type' => 'boolean', 'description' => '웍스가 자동으로 수정 가능한지 여부'],
                            ],
                            'required' => ['category', 'severity', 'title', 'description', 'suggestion'],
                        ],
                    ],
                ],
                'required' => ['compliance_score', 'category_scores', 'violations'],
            ],
        ];
    }

    private function zeroScores(): array
    {
        return ['spec_compliance' => 0, 'code_quality' => 0, 'security' => 0, 'best_practices' => 0, 'performance' => 0];
    }
}
