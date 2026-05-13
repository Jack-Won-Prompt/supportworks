<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaResourceNotFoundException;
use App\Services\Agent\Figma\FigmaClientFactory;

class DevHandoffService
{
    public function __construct(
        private readonly FigmaClientFactory $clientFactory,
    ) {}

    // ── Data collection ────────────────────────────────────────────────────────

    public function collectDevModeData(int $projectId): array
    {
        return AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotNull('figma_frame_id')
            ->orderBy('screen_id')
            ->get()
            ->map(fn($screen) => [
                'screen_id'   => $screen->screen_id,
                'name'        => $screen->title,
                'description' => $screen->description,
                'figma'       => [
                    'file_key'    => $screen->figma_file_key,
                    'node_id'     => $screen->figma_frame_id,
                    'frame_name'  => $screen->figma_frame_name,
                    'view_url'    => $screen->getFigmaViewUrl(),
                    'dev_url'     => $screen->getFigmaDevModeUrl(),
                    'mapped_at'   => $screen->figma_mapped_at?->toIso8601String(),
                ],
                'standards' => [
                    'applied_layouts' => $screen->getAppliedLayouts(),
                ],
            ])
            ->all();
    }

    public function getUnmappedScreens(int $projectId): array
    {
        return AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNull('figma_frame_id')
            ->orderBy('screen_id')
            ->get()
            ->map(fn($s) => [
                'screen_id' => $s->screen_id,
                'name'      => $s->title,
                'reason'    => '디자인 매핑 안 됨',
            ])
            ->all();
    }

    public function getMappingStats(int $projectId): array
    {
        $total   = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->count();
        $mapped  = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->whereNotNull('figma_frame_id')->count();
        $devUrls = AiAgentScreen::where('project_id', $projectId)->whereNull('archived_at')->whereNotNull('figma_dev_mode_url')->count();

        return [
            'total'         => $total,
            'mapped'        => $mapped,
            'unmapped'      => $total - $mapped,
            'with_dev_urls' => $devUrls,
            'percent'       => $total > 0 ? round($mapped / $total * 100) : 0,
        ];
    }

    // ── Validation ─────────────────────────────────────────────────────────────

    public function validateDevModeUrls(int $projectId, User $user): array
    {
        $client  = $this->clientFactory->forUser($user);
        $screens = AiAgentScreen::where('project_id', $projectId)
            ->whereNull('archived_at')
            ->whereNotNull('figma_frame_id')
            ->orderBy('screen_id')
            ->get();

        $results  = [];
        $byFile   = $screens->groupBy('figma_file_key');

        foreach ($byFile as $fileKey => $fileScreens) {
            if (!$fileKey) continue;

            $nodeIds = $fileScreens->pluck('figma_frame_id')->filter()->values()->all();

            try {
                $nodes = $client->getFileNodes($fileKey, $nodeIds);
                foreach ($fileScreens as $screen) {
                    $found    = isset($nodes[$screen->figma_frame_id]);
                    $results[$screen->screen_id] = [
                        'screen_id'   => $screen->screen_id,
                        'name'        => $screen->title,
                        'is_valid'    => $found,
                        'status'      => $found ? 'ok' : 'missing',
                        'error'       => $found ? null : '노드를 찾을 수 없음 (Figma에서 삭제되었을 가능성)',
                        'dev_url'     => $screen->getFigmaDevModeUrl(),
                    ];
                }
            } catch (FigmaResourceNotFoundException) {
                foreach ($fileScreens as $screen) {
                    $results[$screen->screen_id] = [
                        'screen_id' => $screen->screen_id,
                        'name'      => $screen->title,
                        'is_valid'  => false,
                        'status'    => 'missing',
                        'error'     => '파일 또는 노드를 찾을 수 없음',
                        'dev_url'   => $screen->getFigmaDevModeUrl(),
                    ];
                }
            } catch (FigmaAccessDeniedException) {
                foreach ($fileScreens as $screen) {
                    $results[$screen->screen_id] = [
                        'screen_id' => $screen->screen_id,
                        'name'      => $screen->title,
                        'is_valid'  => false,
                        'status'    => 'access_denied',
                        'error'     => '접근 권한 없음 (Figma 파일 공유 설정 확인)',
                        'dev_url'   => $screen->getFigmaDevModeUrl(),
                    ];
                }
            } catch (\Throwable $e) {
                foreach ($fileScreens as $screen) {
                    $results[$screen->screen_id] = [
                        'screen_id' => $screen->screen_id,
                        'name'      => $screen->title,
                        'is_valid'  => false,
                        'status'    => 'error',
                        'error'     => '검증 실패: ' . $e->getMessage(),
                        'dev_url'   => $screen->getFigmaDevModeUrl(),
                    ];
                }
            }
        }

        return array_values($results);
    }

    // ── Artifact generation ────────────────────────────────────────────────────

    public function generateHandoffArtifact(Project $project, User $user): AiAgentArtifact
    {
        $devData   = $this->collectDevModeData($project->id);
        $unmapped  = $this->getUnmappedScreens($project->id);
        $markdown  = $this->renderHandoffMarkdown($project, $devData, $unmapped);

        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DESIGN)
            ->first();

        return AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::DEV_HANDOFF,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "개발 핸드오프 — {$project->name}",
            content:   $markdown,
            userId:    $user->id,
            meta: [
                'mapped_screens'   => count($devData),
                'unmapped_screens' => count($unmapped),
                'generated_at'     => now()->toIso8601String(),
            ],
        );
    }

    public function renderHandoffMarkdown(Project $project, array $devData, array $unmapped): string
    {
        return view()->file(
            resource_path('templates/design/dev_handoff_v1.md.blade.php'),
            compact('project', 'devData', 'unmapped'),
        )->render();
    }

    // ── CSV helpers ────────────────────────────────────────────────────────────

    public function generateCsv(int $projectId): string
    {
        $devData  = $this->collectDevModeData($projectId);
        $unmapped = $this->getUnmappedScreens($projectId);

        $rows = [['화면 ID', '화면명', 'Figma View', 'Figma Dev Mode', '적용 레이아웃', '상태']];

        foreach ($devData as $screen) {
            $rows[] = [
                $screen['screen_id'],
                $screen['name'],
                $screen['figma']['view_url'] ?? '',
                $screen['figma']['dev_url'] ?? '',
                implode(' / ', array_column($screen['standards']['applied_layouts'], 'name')),
                '매핑됨',
            ];
        }

        foreach ($unmapped as $screen) {
            $rows[] = [$screen['screen_id'], $screen['name'], '', '', '', '미매핑'];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', (string) $v) . '"', $row)) . "\r\n";
        }

        return $csv;
    }

    // ── Package (ZIP) ──────────────────────────────────────────────────────────

    public function buildPackage(Project $project, User $user): string
    {
        $dir = storage_path('app/handoff/' . $project->id);
        if (!is_dir($dir)) mkdir($dir, 0775, true);

        $zipPath = $dir . '/handoff-' . now()->format('Ymd-His') . '.zip';
        $zip     = new \ZipArchive();

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('ZIP 파일 생성 실패: ' . $zipPath);
        }

        $slug = \Illuminate\Support\Str::slug($project->name);
        $date = now()->format('Ymd');

        // T34 handoff markdown
        $devData  = $this->collectDevModeData($project->id);
        $unmapped = $this->getUnmappedScreens($project->id);
        $zip->addFromString("handoff-{$slug}.md", $this->renderHandoffMarkdown($project, $devData, $unmapped));

        // T34 CSV
        $zip->addFromString("screens-mapping-{$slug}.csv", $this->generateCsv($project->id));

        // T28-T33 artifacts
        $artifactMap = [
            ArtifactType::DESIGN_TOKENS     => "design-tokens-{$slug}.json",
            ArtifactType::COMPONENT_SPEC    => "components-{$slug}.json",
            ArtifactType::LAYOUT_SPEC       => "layouts-{$slug}.json",
            ArtifactType::DESIGN_REVIEW     => "design-review-{$slug}.json",
            ArtifactType::DESIGN_SYSTEM_DOC => "design-system-{$slug}.json",
        ];

        foreach ($artifactMap as $type => $filename) {
            $artifact = AiAgentArtifact::where('project_id', $project->id)
                ->where('type', $type->value)
                ->where('scope_type', 'project')
                ->latest()
                ->first();

            if ($artifact && $artifact->content) {
                $content = is_string($artifact->content)
                    ? $artifact->content
                    : json_encode($artifact->content, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $zip->addFromString($filename, $content);
            }
        }

        // T33 HTML design system
        $dsArtifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_SYSTEM_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();

        if ($dsArtifact) {
            // Try to render fresh HTML
            try {
                $dataContext = app(DesignSystemDataContext::class);
                $templateSvc = app(DesignSystemTemplateService::class);
                $data        = $dataContext->build($project->id);
                $dsContent   = is_array($dsArtifact->content) ? $dsArtifact->content : json_decode($dsArtifact->content, true);
                $data['ai_sections']     = $dsContent['ai_sections'] ?? [];
                $data['flat_colors']     = DesignSystemTemplateService::flattenColors($data['tokens'] ?? []);
                $data['flat_typography'] = DesignSystemTemplateService::flattenTypography($data['tokens'] ?? []);
                $data['flat_shadows']    = DesignSystemTemplateService::flattenShadows($data['tokens'] ?? []);
                $zip->addFromString("design-system-{$slug}.html", $templateSvc->renderHtml($data));
                $zip->addFromString("design-system-{$slug}.md",   $templateSvc->renderMarkdown($data));
            } catch (\Throwable) {
                // Skip HTML/MD if context build fails
            }
        }

        // README
        $zip->addFromString('README.md', $this->generateReadme($project, count($devData), count($unmapped), $date));

        $zip->close();

        return $zipPath;
    }

    private function generateReadme(Project $project, int $mapped, int $unmapped, string $date): string
    {
        return <<<MD
        # {$project->name} — Phase 3 핸드오프 패키지

        생성일: {$date}

        ## 포함 파일

        | 파일 | 설명 |
        |------|------|
        | `handoff-*.md` | 화면별 Dev Mode 링크 목록 (T34) |
        | `screens-mapping-*.csv` | 화면 매핑 CSV |
        | `design-system-*.html` | 디자인 시스템 문서 HTML (T33) |
        | `design-system-*.md` | 디자인 시스템 문서 Markdown (T33) |
        | `design-tokens-*.json` | Design Token JSON (T28) |
        | `components-*.json` | 컴포넌트 명세서 JSON (T29) |
        | `layouts-*.json` | 표준 레이아웃 JSON (T30) |
        | `design-review-*.json` | 일관성 검수 결과 JSON (T32) |

        ## 매핑 현황
        - 매핑된 화면: **{$mapped}개**
        - 미매핑 화면: **{$unmapped}개**

        ## Phase 4 시작 가이드
        1. `handoff-*.md` 파일을 참조하여 각 화면의 Dev Mode 링크 접근
        2. `design-system-*.html`을 브라우저로 열어 디자인 표준 확인
        3. `design-tokens-*.json`을 프로젝트에 임포트하여 토큰 활용
        4. 미매핑 화면은 디자이너와 별도 협의 후 진행
        MD;
    }
}
