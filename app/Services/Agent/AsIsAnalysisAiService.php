<?php

namespace App\Services\Agent;

use App\Models\AiSetting;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactFile;
use App\Services\Agent\Parsers\ParsedFileContent;
use Illuminate\Support\Facades\Storage;

class AsIsAnalysisAiService
{
    public function __construct(
        private readonly AgentUsageLogService  $usageLog,
        private readonly PromptLibraryService  $prompts,
        private readonly TraceabilityService   $traceability,
    ) {}

    /**
     * 동기 분석 — 결과 배열만 반환.
     */
    public function analyze(AiAgentArtifact $artifact, int $userId): array
    {
        return $this->run($artifact, $userId)['result'];
    }

    /**
     * 동기 분석 — 결과 + 토큰/비용 통계를 함께 반환 (SSE 엔드포인트용).
     *
     * @return array{result: array, tokensIn: int, tokensOut: int, costUsd: float, model: string}
     */
    public function analyzeWithStats(AiAgentArtifact $artifact, int $userId): array
    {
        return $this->run($artifact, $userId);
    }

    /**
     * 공통 분석 실행 로직.
     */
    private function run(AiAgentArtifact $artifact, int $userId): array
    {
        $artifact->load('files');

        $parsedFiles = $artifact->files
            ->filter(fn($f) => $f->parse_status === 'completed')
            ->values();

        if ($parsedFiles->isEmpty()) {
            throw new \RuntimeException('파싱 완료된 파일이 없습니다. 파일을 먼저 업로드하세요.');
        }

        $systemPrompt = $this->buildSystemPrompt($artifact, $parsedFiles);
        $messages     = $this->buildMessages($parsedFiles);
        $tools        = [$this->getAnalysisTool()];
        $extraHeaders = $this->needsPdfBeta($parsedFiles)
            ? ['anthropic-beta' => 'pdfs-2024-09-25']
            : [];

        $apiKey   = AiSetting::current()->anthropicKey();
        $provider = new AnthropicProvider($apiKey);

        $toolResponse = $this->usageLog->callAndLog(
            provider:   $provider,
            call:       fn() => $provider->generateWithTools(
                systemPrompt: $systemPrompt,
                messages:     $messages,
                tools:        $tools,
                options:      ['max_tokens' => 8000],
                extraHeaders: $extraHeaders,
            )->toAIResponse(),
            userId:     $userId,
            projectId:  $artifact->project_id,
            artifactId: $artifact->id,
            stage:      'planning',
            taskType:   'as_is_analysis',
        );

        $result = json_decode($toolResponse->text, true) ?? [];

        $this->persistResult($artifact, $result, $toolResponse, $userId);

        return [
            'result'    => $result,
            'tokensIn'  => $toolResponse->inputTokens,
            'tokensOut' => $toolResponse->outputTokens,
            'costUsd'   => $this->usageLog->calculateCost($toolResponse->model, $toolResponse->inputTokens, $toolResponse->outputTokens),
            'model'     => $toolResponse->model,
        ];
    }

    /**
     * 멀티모달 메시지 빌드 — 파싱된 파일들을 Claude API 메시지 형식으로 변환.
     */
    public function buildMessages(iterable $files): array
    {
        $contentBlocks = [];

        foreach ($files as $file) {
            /** @var AiAgentArtifactFile $file */
            $parsed = $file->getParsedResult();
            if (!$parsed) {
                continue;
            }

            if ($parsed->needsAiVisual) {
                $block = $this->buildVisualBlock($file, $parsed);
                if ($block) {
                    $contentBlocks[] = $block;
                    $contentBlocks[] = ['type' => 'text', 'text' => "위는 [{$file->file_name}] 파일입니다."];
                }
            } else {
                $text = $parsed->toPromptText($file->file_name);
                if ($text) {
                    $contentBlocks[] = ['type' => 'text', 'text' => $text];
                }
            }
        }

        if (empty($contentBlocks)) {
            throw new \RuntimeException('분석 가능한 파일 내용이 없습니다.');
        }

        array_unshift($contentBlocks, [
            'type' => 'text',
            'text' => '다음 자료들을 분석해주세요:',
        ]);

        return [['role' => 'user', 'content' => $contentBlocks]];
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildSystemPrompt(AiAgentArtifact $artifact, iterable $files): string
    {
        $scopeLabel = $artifact->scope_label;

        $fileList = collect($files)
            ->map(fn($f) => "{$f->file_name} ({$f->mime_type})")
            ->implode(', ');

        $rendered = $this->prompts->render('planning', 'as_is_analysis', [
            'scope_label' => $scopeLabel,
            'file_list'   => $fileList,
        ]);

        return $rendered ?? $this->fallbackSystemPrompt($scopeLabel, $fileList);
    }

    private function fallbackSystemPrompt(string $scopeLabel, string $fileList): string
    {
        return <<<PROMPT
당신은 IT 시스템 현황 분석 전문가입니다.
분석 대상: {$scopeLabel}
첨부 파일: {$fileList}

제공된 자료를 분석하여 AS-IS 현황 보고서를 작성하고, record_as_is_analysis 도구를 사용해 구조화된 형태로 반환해주세요.
PROMPT;
    }

    private function buildVisualBlock(AiAgentArtifactFile $file, ParsedFileContent $parsed): ?array
    {
        $storagePath = $parsed->imageReferences[0] ?? $file->storage_path;

        try {
            $absolutePath = Storage::disk('local')->path($storagePath);
            if (!file_exists($absolutePath)) {
                return null;
            }

            $data = base64_encode(file_get_contents($absolutePath));

            if ($file->file_type === 'pdf') {
                return [
                    'type'   => 'document',
                    'source' => [
                        'type'       => 'base64',
                        'media_type' => 'application/pdf',
                        'data'       => $data,
                    ],
                ];
            }

            $mediaType = match (strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png'         => 'image/png',
                'gif'         => 'image/gif',
                'webp'        => 'image/webp',
                default       => $file->mime_type,
            };

            return [
                'type'   => 'image',
                'source' => [
                    'type'       => 'base64',
                    'media_type' => $mediaType,
                    'data'       => $data,
                ],
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function needsPdfBeta(iterable $files): bool
    {
        foreach ($files as $file) {
            if ($file->file_type === 'pdf') {
                return true;
            }
        }
        return false;
    }

    private function getAnalysisTool(): array
    {
        return [
            'name'         => 'record_as_is_analysis',
            'description'  => 'AS-IS 현황 분석 결과를 구조화된 형태로 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'summary' => [
                        'type'        => 'string',
                        'description' => '현황 요약 (2~3 문단)',
                    ],
                    'issues' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'category'    => ['type' => 'string', 'enum' => ['성능', 'UX', '기능', '보안', '기타']],
                                'title'       => ['type' => 'string'],
                                'description' => ['type' => 'string'],
                                'source_files'=> ['type' => 'array', 'items' => ['type' => 'string']],
                                'severity'    => ['type' => 'string', 'enum' => ['high', 'medium', 'low']],
                            ],
                            'required' => ['category', 'title', 'description', 'severity'],
                        ],
                    ],
                    'categories' => [
                        'type'                 => 'object',
                        'description'          => '카테고리별 종합 분석 (key: 카테고리명, value: 분석 내용)',
                        'additionalProperties' => ['type' => 'string'],
                    ],
                    'source_mapping' => [
                        'type'                 => 'object',
                        'description'          => '파일별 핵심 발견사항 (key: 파일명, value: 발견사항 목록)',
                        'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']],
                    ],
                ],
                'required' => ['summary', 'issues', 'categories', 'source_mapping'],
            ],
        ];
    }

    private function persistResult(
        AiAgentArtifact $artifact,
        array           $result,
        object          $toolResponse,
        int             $userId
    ): void {
        $meta = [
            'change_type'   => 'ai_generated',
            'model'         => $toolResponse->model,
            'tokens_in'     => $toolResponse->inputTokens,
            'tokens_out'    => $toolResponse->outputTokens,
            'analyzed_at'   => now()->toIso8601String(),
            'file_count'    => $artifact->files->count(),
            'issue_count'   => count($result['issues'] ?? []),
        ];

        $artifact->updateWithVersion(
            content: json_encode($result, JSON_UNESCAPED_UNICODE),
            userId:  $userId,
            meta:    $meta,
        );

        // 추적성 링크
        if ($artifact->scope_type === 'screen') {
            $this->traceability->link(
                projectId:  $artifact->project_id,
                sourceType: 'artifact',
                sourceId:   $artifact->id,
                sourceRef:  "AS-IS#{$artifact->id}",
                targetType: 'screen',
                targetId:   $artifact->scope_id,
                targetRef:  "SCR-{$artifact->scope_id}",
                linkType:   'analyzes',
            );
        }

        foreach ($artifact->files as $file) {
            $this->traceability->link(
                projectId:  $artifact->project_id,
                sourceType: 'artifact_file',
                sourceId:   $file->id,
                sourceRef:  $file->file_name,
                targetType: 'artifact',
                targetId:   $artifact->id,
                targetRef:  "AS-IS#{$artifact->id}",
                linkType:   'source_for',
            );
        }
    }
}
