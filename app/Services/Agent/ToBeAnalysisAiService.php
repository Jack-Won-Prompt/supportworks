<?php

namespace App\Services\Agent;

use App\Models\AiSetting;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentArtifactFile;
use App\Models\Agent\AiAgentRequirement;
use App\Services\Agent\Parsers\ParsedFileContent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ToBeAnalysisAiService
{
    public function __construct(
        private readonly AgentUsageLogService $usageLog,
        private readonly PromptLibraryService $prompts,
        private readonly TraceabilityService  $traceability,
    ) {}

    public function analyze(AiAgentArtifact $artifact, int $userId): array
    {
        return $this->run($artifact, $userId)['result'];
    }

    /**
     * @return array{result: array, tokensIn: int, tokensOut: int, costUsd: float, model: string}
     */
    public function analyzeWithStats(AiAgentArtifact $artifact, int $userId): array
    {
        return $this->run($artifact, $userId);
    }

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
            taskType:   'requirements_extraction',
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
            'text' => '다음 자료들을 분석하여 TO-BE 요구사항을 도출해주세요:',
        ]);

        return [['role' => 'user', 'content' => $contentBlocks]];
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function buildSystemPrompt(AiAgentArtifact $artifact, iterable $files): string
    {
        $fileList = collect($files)
            ->map(fn($f) => "{$f->file_name} ({$f->mime_type})")
            ->implode(', ');

        $rendered = $this->prompts->render('planning', 'to_be_analysis', [
            'scope_label' => '프로젝트 전체',
            'file_list'   => $fileList,
        ]);

        return $rendered ?? $this->fallbackSystemPrompt($fileList);
    }

    private function fallbackSystemPrompt(string $fileList): string
    {
        return <<<PROMPT
당신은 IT 시스템 요구사항 분석 전문가입니다.
첨부 파일: {$fileList}

제공된 자료를 분석하여 TO-BE 요구사항을 도출하고, record_to_be_requirements 도구를 사용해 구조화된 형태로 반환해주세요.
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
            'name'         => 'record_to_be_requirements',
            'description'  => 'TO-BE 요구사항 분석 결과를 구조화된 형태로 기록합니다.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'overview' => [
                        'type'        => 'string',
                        'description' => '전체 TO-BE 요구사항 개요 (2~3 문단)',
                    ],
                    'requirements' => [
                        'type'  => 'array',
                        'items' => [
                            'type'       => 'object',
                            'properties' => [
                                'title'        => ['type' => 'string'],
                                'description'  => ['type' => 'string'],
                                'priority'     => ['type' => 'string', 'enum' => ['must', 'should', 'could', 'wont']],
                                'category'     => ['type' => 'string'],
                                'rationale'    => ['type' => 'string', 'description' => '도출 근거 및 AS-IS 문제와의 연관성'],
                                'source_files' => ['type' => 'array', 'items' => ['type' => 'string']],
                            ],
                            'required' => ['title', 'description', 'priority', 'category'],
                        ],
                    ],
                    'priority_summary' => [
                        'type'        => 'object',
                        'description' => '우선순위별 요구사항 수',
                        'properties'  => [
                            'must'   => ['type' => 'integer'],
                            'should' => ['type' => 'integer'],
                            'could'  => ['type' => 'integer'],
                            'wont'   => ['type' => 'integer'],
                        ],
                    ],
                ],
                'required' => ['overview', 'requirements', 'priority_summary'],
            ],
        ];
    }

    private function persistResult(
        AiAgentArtifact $artifact,
        array           $result,
        object          $toolResponse,
        int             $userId
    ): void {
        $requirements = $result['requirements'] ?? [];

        // Wrap full persist in a transaction for atomicity + lockForUpdate safety
        DB::transaction(function () use ($artifact, $result, $toolResponse, $userId, $requirements) {

            // Delete all previous requirements for this artifact (reanalysis policy)
            AiAgentRequirement::where('project_id', $artifact->project_id)
                ->where('artifact_id', $artifact->id)
                ->delete();

            // Re-create from 웍스 output
            foreach ($requirements as $req) {
                // nextReqId() uses its own inner transaction with lockForUpdate
                $reqId = AiAgentRequirement::nextReqId($artifact->project_id);

                $record = AiAgentRequirement::create([
                    'project_id'   => $artifact->project_id,
                    'artifact_id'  => $artifact->id,
                    'req_id'       => $reqId,
                    'title'        => $req['title'],
                    'description'  => $req['description'] ?? null,
                    'rationale'    => $req['rationale'] ?? null,
                    'source_files' => $req['source_files'] ?? null,
                    'priority'     => $req['priority'] ?? 'should',
                    'category'     => $req['category'] ?? null,
                    'source'       => 'to_be',
                    'status'       => 'draft',
                ]);

                $this->traceability->link(
                    projectId:  $artifact->project_id,
                    sourceType: 'artifact',
                    sourceId:   $artifact->id,
                    sourceRef:  "TO-BE#{$artifact->id}",
                    targetType: 'requirement',
                    targetId:   $record->id,
                    targetRef:  $reqId,
                    linkType:   'generates',
                );
            }

            // Persist overview + stats to artifact content
            $meta = [
                'change_type'    => 'ai_generated',
                'model'          => $toolResponse->model,
                'tokens_in'      => $toolResponse->inputTokens,
                'tokens_out'     => $toolResponse->outputTokens,
                'analyzed_at'    => now()->toIso8601String(),
                'file_count'     => $artifact->files->count(),
                'req_count'      => count($requirements),
            ];

            $artifact->updateWithVersion(
                content: json_encode([
                    'overview'         => $result['overview'] ?? '',
                    'priority_summary' => $result['priority_summary'] ?? [],
                ], JSON_UNESCAPED_UNICODE),
                userId:  $userId,
                meta:    $meta,
            );

            // Traceability: each uploaded file → artifact
            foreach ($artifact->files as $file) {
                $this->traceability->link(
                    projectId:  $artifact->project_id,
                    sourceType: 'artifact_file',
                    sourceId:   $file->id,
                    sourceRef:  $file->file_name,
                    targetType: 'artifact',
                    targetId:   $artifact->id,
                    targetRef:  "TO-BE#{$artifact->id}",
                    linkType:   'source_for',
                );
            }
        });
    }
}
