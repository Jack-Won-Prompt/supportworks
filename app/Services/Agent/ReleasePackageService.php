<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentApprovalGate;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentRequirement;
use App\Models\Agent\AiAgentScreen;
use App\Models\Agent\AiAgentUsageLog;
use App\Models\Agent\ProjectAiAgentConfig;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Str;

class ReleasePackageService
{
    public function __construct(
        private readonly ?DeployGuideService    $deployGuideService    = null,
        private readonly ?UserManualService     $userManualService     = null,
        private readonly ?MigrationGuideService $migrationGuideService = null,
    ) {}
    private const STORAGE_DIR = 'release-packages';

    // ── Public API ─────────────────────────────────────────────────────────────

    /**
     * 전체 릴리즈 패키지 ZIP을 생성하고 경로를 반환합니다.
     */
    public function generatePackage(Project $project, User $user): string
    {
        $slug = Str::slug($project->name) ?: 'project';
        $date = now()->format('Y-m-d');
        $name = "release-package-{$slug}-{$date}.zip";

        $dir     = storage_path('app/' . self::STORAGE_DIR . '/' . $project->id);
        $zipPath = $dir . '/' . $name;

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("ZIP 파일 생성 실패: {$zipPath}");
        }

        $this->addPlanningArtifacts($zip, $project);
        $this->addDesignArtifacts($zip, $project);
        $this->addDevPrepArtifacts($zip, $project);
        $this->addFrontendCode($zip, $project);
        $this->addBackendCode($zip, $project);
        $this->addIntegrationArtifacts($zip, $project);

        // T50: 사용자 매뉴얼
        if ($this->userManualService) {
            try {
                $manualArtifact = $this->userManualService->generate($project, $user);
                $zip->addFromString('MANUAL.md', $manualArtifact->content ?? '');
            } catch (\Throwable) {
                // 매뉴얼 생성 실패 시 패키지 생성 계속 진행
            }
        }

        // T49: 배포 가이드
        if ($this->deployGuideService) {
            try {
                $deployArtifact = $this->deployGuideService->generate($project, $user);
                $zip->addFromString('DEPLOY.md', $deployArtifact->content ?? '');
            } catch (\Throwable) {
                // 배포 가이드 실패 시 패키지 생성은 계속 진행
            }
        }

        // T51: 마이그레이션 가이드
        if ($this->migrationGuideService) {
            try {
                $migrationArtifact = $this->migrationGuideService->generate($project, $user);
                $zip->addFromString('MIGRATION.md', $migrationArtifact->content ?? '');
            } catch (\Throwable) {
                // 마이그레이션 가이드 실패 시 패키지 생성은 계속 진행
            }
        }

        $manifest = $this->buildManifest($project);
        $zip->addFromString('manifest.json', json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        $zip->addFromString('README.md', $this->buildReadme($project, $manifest));

        $zip->close();

        $this->persistArtifact($project, $zipPath, $manifest, $user);

        return $zipPath;
    }

    /**
     * 기존 패키지 정보를 로드합니다 (있으면 artifact + 경로).
     */
    public function loadExisting(int $projectId): ?array
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RELEASE_PACKAGE->value)
            ->latest('created_at')
            ->first();

        if (!$artifact) return null;

        $content = json_decode($artifact->content, true) ?? [];
        $path    = $content['package_path'] ?? null;

        return [
            'artifact' => $artifact,
            'path'     => $path,
            'exists'   => $path && file_exists($path),
            'size'     => ($path && file_exists($path)) ? filesize($path) : 0,
            'manifest' => $content['manifest'] ?? [],
        ];
    }

    /**
     * 패키지 폴더 구조 미리보기 (ZIP 목록 기반).
     */
    public function previewStructure(string $zipPath): array
    {
        if (!file_exists($zipPath)) return [];

        $zip   = new \ZipArchive();
        $nodes = [];

        if ($zip->open($zipPath) !== true) return [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if (!$stat) continue;

            $name = $stat['name'];
            $size = $stat['comp_size'];

            $parts  = explode('/', rtrim($name, '/'));
            $folder = count($parts) > 1 ? $parts[0] : '';

            $nodes[] = [
                'name'   => $name,
                'folder' => $folder,
                'size'   => $size,
                'is_dir' => str_ends_with($name, '/'),
            ];
        }

        $zip->close();

        // Group by top-level folder
        $byFolder = [];
        $rootFiles = [];
        foreach ($nodes as $node) {
            if ($node['is_dir']) continue;
            if ($node['folder']) {
                $byFolder[$node['folder']][] = $node;
            } else {
                $rootFiles[] = $node;
            }
        }

        $result = [];
        foreach ($byFolder as $folder => $files) {
            $result[] = [
                'name'       => $folder . '/',
                'type'       => 'folder',
                'file_count' => count($files),
                'total_size' => array_sum(array_column($files, 'size')),
                'files'      => array_map(fn($f) => [
                    'name' => basename($f['name']),
                    'size' => $f['size'],
                ], array_slice($files, 0, 20)),
            ];
        }
        foreach ($rootFiles as $f) {
            $result[] = [
                'name' => basename($f['name']),
                'type' => 'file',
                'size' => $f['size'],
            ];
        }

        return $result;
    }

    /**
     * 패키지 사전 조건 확인 (Phase 1-4 승인 여부).
     */
    public function checkPrerequisites(int $projectId): array
    {
        $stages = AiAgentProjectStage::where('project_id', $projectId)
            ->whereIn('type', [
                StageType::PLANNING->value,
                StageType::DESIGN->value,
                StageType::DEV_PREP->value,
                StageType::DEVELOPMENT->value,
            ])
            ->get()
            ->keyBy(fn($s) => $s->type->value);

        $items = [];
        foreach ([
            StageType::PLANNING    => 'Phase 2: 기획',
            StageType::DESIGN      => 'Phase 3: 디자인',
            StageType::DEV_PREP    => 'Phase 4a: 개발 준비',
            StageType::DEVELOPMENT => 'Phase 4b: 개발',
        ] as $type => $label) {
            $stage    = $stages->get($type->value);
            $approved = $stage?->status?->value === 'approved';
            $items[]  = [
                'label'    => $label,
                'approved' => $approved,
                'status'   => $stage?->status?->value ?? 'locked',
            ];
        }

        return [
            'items'     => $items,
            'can_build' => collect($items)->every(fn($i) => $i['approved']),
        ];
    }

    /**
     * 패키지 수동 삭제.
     */
    public function deletePackage(int $projectId): void
    {
        $artifact = AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RELEASE_PACKAGE->value)
            ->latest('created_at')
            ->first();

        if (!$artifact) return;

        $content = json_decode($artifact->content, true) ?? [];
        $path    = $content['package_path'] ?? null;

        if ($path && file_exists($path)) {
            @unlink($path);
        }

        $artifact->delete();
    }

    // ── Private: ZIP sections ─────────────────────────────────────────────────

    private function addPlanningArtifacts(\ZipArchive $zip, Project $project): void
    {
        $folder = '01-planning/';

        foreach ([
            ArtifactType::AS_IS_ANALYSIS     => 'as-is.json',
            ArtifactType::TO_BE_REQUIREMENTS => 'to-be-requirements.json',
            ArtifactType::GAP_ANALYSIS       => 'gap-analysis.json',
            ArtifactType::PLANNING_DOC       => 'planning-document.md',
            ArtifactType::IA_FLOW            => 'ia-flow.json',
            ArtifactType::SCREEN_PROMPTS     => 'screen-prompts.json',
            ArtifactType::MOCKUP             => 'mockups.json',
        ] as $type => $filename) {
            $artifact = $this->latest($project->id, $type);
            if ($artifact) {
                $zip->addFromString($folder . $filename, $artifact->content ?? '');
            }
        }

        // Requirements CSV
        $reqCsv = $this->buildRequirementsCsv($project->id);
        if ($reqCsv) {
            $zip->addFromString($folder . 'requirements.csv', $reqCsv);
        }
    }

    private function addDesignArtifacts(\ZipArchive $zip, Project $project): void
    {
        $folder = '02-design/';

        foreach ([
            ArtifactType::DESIGN_TOKENS    => 'design-tokens.json',
            ArtifactType::COMPONENT_SPEC   => 'components.json',
            ArtifactType::LAYOUT_SPEC      => 'layouts.json',
            ArtifactType::DESIGN_REVIEW    => 'design-review.json',
            ArtifactType::DESIGN_SYSTEM_DOC=> 'design-system.json',
            ArtifactType::DEV_HANDOFF      => 'dev-handoff.md',
        ] as $type => $filename) {
            $artifact = $this->latest($project->id, $type);
            if ($artifact) {
                $zip->addFromString($folder . $filename, $artifact->content ?? '');
            }
        }
    }

    private function addDevPrepArtifacts(\ZipArchive $zip, Project $project): void
    {
        $folder = '03-dev-prep/';

        // ERD → JSON + Mermaid + SQL
        $erd = $this->latest($project->id, ArtifactType::ERD);
        if ($erd) {
            $erdData = json_decode($erd->content, true) ?? [];
            $zip->addFromString($folder . 'erd.json',    $erd->content);
            $zip->addFromString($folder . 'erd.mermaid', $erdData['mermaid_diagram'] ?? '');
            $zip->addFromString($folder . 'erd.sql',     $this->erdToSql($erdData));
        }

        // API Spec → JSON + YAML
        $api = $this->latest($project->id, ArtifactType::API_SPEC);
        if ($api) {
            $apiData = json_decode($api->content, true) ?? [];
            $zip->addFromString($folder . 'api-spec.json', $api->content);
            $zip->addFromString($folder . 'api-spec.yaml', $this->arrayToYaml($apiData));
        }

        // RBAC → JSON + Markdown + PHP Policy stub
        $rbac = $this->latest($project->id, ArtifactType::RBAC_MODEL);
        if ($rbac) {
            $rbacData = json_decode($rbac->content, true) ?? [];
            $zip->addFromString($folder . 'rbac.json',           $rbac->content);
            $zip->addFromString($folder . 'rbac.md',             $this->rbacToMarkdown($rbacData));
            $zip->addFromString($folder . 'RolePolicy.php',      $this->rbacToLaravelPolicy($rbacData));
        }

        // Code gen prompts (screen별)
        $prompts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_GEN_PROMPT->value)
            ->where('scope_type', 'screen')
            ->get();

        foreach ($prompts as $prompt) {
            $screen = AiAgentScreen::find($prompt->scope_id);
            if ($screen) {
                $zip->addFromString(
                    $folder . 'code-prompts/' . $screen->screen_id . '.md',
                    $prompt->content ?? ''
                );
            }
        }
    }

    private function addFrontendCode(\ZipArchive $zip, Project $project): void
    {
        $folder = '04-frontend/';

        $artifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::FRONTEND_CODE->value)
            ->where('scope_type', 'screen')
            ->get();

        foreach ($artifacts as $artifact) {
            $screen = AiAgentScreen::find($artifact->scope_id);
            if (!$screen) continue;

            $code = json_decode($artifact->content, true) ?? [];
            $screenFolder = $folder . $screen->screen_id . '/';

            foreach ($code['files'] ?? [] as $file) {
                $path    = ltrim($file['path'] ?? '', '/');
                $content = $file['content'] ?? '';
                if ($path) {
                    $zip->addFromString($screenFolder . $path, $content);
                }
            }

            // TODO 항목 메모
            if (!empty($code['todo_items'])) {
                $todos = implode("\n", array_map(fn($t) => "- [ ] {$t}", $code['todo_items']));
                $zip->addFromString($screenFolder . 'TODO.md', "# TODO — {$screen->screen_id}\n\n{$todos}\n");
            }
        }
    }

    private function addBackendCode(\ZipArchive $zip, Project $project): void
    {
        $folder = '05-backend/';

        $artifacts = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::BACKEND_CODE->value)
            ->where('scope_type', 'resource')
            ->get();

        foreach ($artifacts as $artifact) {
            $code     = json_decode($artifact->content, true) ?? [];
            $resource = $code['$metadata']['resource'] ?? ('resource-' . $artifact->scope_id);
            $resFolder = $folder . $resource . '/';

            foreach ($code['files'] ?? [] as $file) {
                $path    = ltrim($file['path'] ?? '', '/');
                $content = $file['content'] ?? '';
                if ($path) {
                    $zip->addFromString($resFolder . $path, $content);
                }
            }

            // Routes 메모
            if (!empty($code['routes'])) {
                $lines = ["# Routes — {$resource}", ''];
                foreach ($code['routes'] as $route) {
                    $lines[] = "- `{$route['method']} {$route['uri']}` → {$route['action']}";
                }
                $zip->addFromString($resFolder . 'ROUTES.md', implode("\n", $lines) . "\n");
            }
        }
    }

    private function addIntegrationArtifacts(\ZipArchive $zip, Project $project): void
    {
        $folder = '06-integration/';

        // T44 API Integration
        $integration = $this->latest($project->id, ArtifactType::API_INTEGRATION);
        if ($integration) {
            $intData = json_decode($integration->content, true) ?? [];
            $zip->addFromString($folder . 'api-integration.json', $integration->content);

            // 통합 설정 파일
            foreach ($intData['integration_files'] ?? [] as $path => $content) {
                $safePath = ltrim($path, '/');
                $zip->addFromString($folder . 'integration-files/' . $safePath, $content);
            }

            // 미매칭 API 목록
            $unmatched = $intData['analysis']['unmatched_frontend'] ?? [];
            if (!empty($unmatched)) {
                $lines = ["# 미연결 API 목록\n"];
                foreach ($unmatched as $item) {
                    $fc = $item['frontend_call'] ?? [];
                    $lines[] = "- `{$fc['method']} {$fc['url']}`" . (isset($fc['screen_id']) ? " ({$fc['screen_id']})" : '');
                }
                $zip->addFromString($folder . 'unmatched-apis.md', implode("\n", $lines) . "\n");
            }
        }

        // T41 Code Validation (project-level 또는 가장 최근 screen-level)
        $validation = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_VALIDATION->value)
            ->latest('created_at')
            ->first();
        if ($validation) {
            $zip->addFromString($folder . 'code-validation.json', $validation->content);
        }

        // T45 Code Review (system-level)
        $review = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::CODE_REVIEW->value)
            ->where('scope_type', 'project')
            ->latest('created_at')
            ->first();
        if ($review) {
            $reviewData = json_decode($review->content, true) ?? [];
            $zip->addFromString($folder . 'code-review.json', $review->content);
            $zip->addFromString($folder . 'code-review-summary.md', $this->reviewToMarkdown($reviewData));
        }
    }

    // ── Private: manifest + README ────────────────────────────────────────────

    private function buildManifest(Project $project): array
    {
        $config    = ProjectAiAgentConfig::forProject($project->id);
        $usageLogs = AiAgentUsageLog::where('project_id', $project->id)->get();

        $stats = [
            'phases_completed'  => 4,
            'total_artifacts'   => AiAgentArtifact::where('project_id', $project->id)->count(),
            'total_screens'     => AiAgentScreen::where('project_id', $project->id)->whereNull('archived_at')->count(),
            'total_requirements'=> AiAgentRequirement::where('project_id', $project->id)->count(),
            'total_ai_calls'    => $usageLogs->count(),
            'total_tokens'      => $usageLogs->sum('input_tokens') + $usageLogs->sum('output_tokens'),
            'total_cost_usd'    => round((float) $usageLogs->sum('cost_usd'), 2),
        ];

        $phaseSummaries = [];
        foreach ([
            StageType::PLANNING    => ['phase' => 2, 'label' => '기획'],
            StageType::DESIGN      => ['phase' => 3, 'label' => '디자인'],
            StageType::DEV_PREP    => ['phase' => '4a', 'label' => '개발 준비'],
            StageType::DEVELOPMENT => ['phase' => '4b', 'label' => '개발'],
        ] as $stageType => $meta) {
            $stage = AiAgentProjectStage::where('project_id', $project->id)
                ->where('type', $stageType->value)
                ->first();
            $artifactCount = $stage
                ? AiAgentArtifact::where('project_id', $project->id)->where('stage_id', $stage->id)->count()
                : 0;

            $phaseSummaries[] = [
                'phase'          => $meta['phase'],
                'label'          => $meta['label'],
                'stage_type'     => $stageType->value,
                'artifact_count' => $artifactCount,
                'approved_at'    => $stage?->approved_at?->toIso8601String(),
            ];
        }

        // Approvals
        $approvals = AiAgentApprovalGate::where('project_id', $project->id)
            ->where('status', 'approved')
            ->with('reviewedBy', 'stage')
            ->get()
            ->map(fn($g) => [
                'stage'       => $g->stage?->type?->value,
                'stage_label' => $g->stage?->name,
                'approved_at' => $g->reviewed_at?->toIso8601String(),
                'approver'    => $g->reviewedBy?->name,
            ])
            ->values()
            ->all();

        return [
            '$metadata' => [
                'version'          => '1.0.0',
                'generated_at'     => now()->toIso8601String(),
                'ai_agent_version' => '1.0',
                'project'          => [
                    'id'   => $project->id,
                    'name' => $project->name,
                    'stack' => [
                        'frontend' => $config?->frontend_stack?->value ?? 'unknown',
                        'backend'  => 'laravel',
                    ],
                ],
            ],
            'stats'          => $stats,
            'phase_summaries'=> $phaseSummaries,
            'approvals'      => $approvals,
            'navigation'     => [
                'start_here' => 'README.md',
                'planning'   => '01-planning/',
                'design'     => '02-design/',
                'dev_prep'   => '03-dev-prep/',
                'frontend'   => '04-frontend/',
                'backend'    => '05-backend/',
                'integration'=> '06-integration/',
            ],
        ];
    }

    private function buildReadme(Project $project, array $manifest): string
    {
        $stats    = $manifest['stats'];
        $meta     = $manifest['$metadata'];
        $genAt    = now()->format('Y-m-d H:i');
        $stack    = strtoupper($meta['project']['stack']['frontend'] ?? '') . ' + LARAVEL';

        $approvalLines = '';
        foreach ($manifest['approvals'] as $a) {
            $at              = $a['approved_at'] ? date('Y-m-d', strtotime($a['approved_at'])) : '-';
            $approvalLines  .= "| {$a['stage_label']} | {$a['approver']} | {$at} |\n";
        }

        return <<<MD
        # {$project->name} — 통합 릴리즈 패키지

        > 생성일: {$genAt} | 스택: {$stack} | 버전: 1.0.0

        ## 📦 패키지 구성

        | 폴더 | 내용 |
        |------|------|
        | `01-planning/` | 기획 산출물 (AS-IS, TO-BE, GAP, 기획서, IA) |
        | `02-design/` | 디자인 산출물 (토큰, 컴포넌트, 레이아웃, 시스템 문서) |
        | `03-dev-prep/` | 개발 준비 산출물 (ERD, API 명세, RBAC) |
        | `04-frontend/` | 화면별 Frontend 코드 (SCR-XXX/ 폴더) |
        | `05-backend/` | 리소스별 Backend 코드 (Model/Controller/Policy) |
        | `06-integration/` | API 연계 결과, 코드 리뷰 |
        | `manifest.json` | 패키지 메타데이터 |

        ## 📊 통계

        | 항목 | 수치 |
        |------|------|
        | 완료 Phase | {$stats['phases_completed']}단계 |
        | 전체 산출물 | {$stats['total_artifacts']}개 |
        | 화면 수 | {$stats['total_screens']}개 |
        | 요구사항 | {$stats['total_requirements']}개 |
        | 웍스 호출 횟수 | {$stats['total_ai_calls']}회 |
        | 총 토큰 | {$stats['total_tokens']}개 |
        | 웍스 비용 | \${$stats['total_cost_usd']} |

        ## ✅ 승인 이력

        | 단계 | 승인자 | 승인일 |
        |------|--------|--------|
        {$approvalLines}
        ## 🚀 빠른 시작

        ### Frontend 실행
        ```bash
        cd 04-frontend/SCR-001/
        # 스택에 따라 npm install && npm run dev (React/Vue)
        # 또는 브라우저에서 index.html 직접 열기 (HTML 스택)
        ```

        ### Backend 실행
        ```bash
        # 각 05-backend/{Resource}/ 폴더의 파일을 Laravel 프로젝트에 복사
        cd 03-dev-prep/
        mysql -u root -p < erd.sql   # ERD 스키마 적용
        ```

        ### API 명세 확인
        ```bash
        # 03-dev-prep/api-spec.yaml → Swagger UI 또는 Postman 임포트
        ```

        ---
        *이 패키지는 웍스 Agent (T48)에 의해 자동 생성되었습니다.*
        MD;
    }

    // ── Private: format converters ────────────────────────────────────────────

    private function erdToSql(array $erdData): string
    {
        $lines   = ["-- ERD SQL Schema", "-- Generated by 웍스 Agent T48", "-- Generated: " . now()->format('Y-m-d H:i'), ""];
        $tables  = $erdData['tables'] ?? [];

        foreach ($tables as $table) {
            $name    = $table['name'] ?? 'unknown';
            $columns = $table['columns'] ?? [];

            $lines[] = "CREATE TABLE `{$name}` (";
            $colDefs = [];

            foreach ($columns as $col) {
                $colName    = $col['name'] ?? 'col';
                $colType    = $this->mapErdTypeTomysql($col['type'] ?? 'varchar(255)');
                $nullable   = ($col['nullable'] ?? true) ? 'NULL' : 'NOT NULL';
                $pk         = !empty($col['primary_key']) ? ' PRIMARY KEY AUTO_INCREMENT' : '';
                $default    = isset($col['default']) && $col['default'] !== null && !$pk
                    ? " DEFAULT " . (is_string($col['default']) ? "'{$col['default']}'" : $col['default'])
                    : '';

                $colDefs[] = "  `{$colName}` {$colType}{$pk} {$nullable}{$default}";
            }

            // timestamps
            if (!empty($table['timestamps'])) {
                $colDefs[] = "  `created_at` TIMESTAMP NULL DEFAULT NULL";
                $colDefs[] = "  `updated_at` TIMESTAMP NULL DEFAULT NULL";
            }

            $lines[] = implode(",\n", $colDefs);
            $lines[] = ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            $lines[] = "";
        }

        // Foreign keys
        foreach ($erdData['relationships'] ?? [] as $rel) {
            if (($rel['type'] ?? '') === 'belongs_to' || ($rel['type'] ?? '') === 'foreign_key') {
                $from   = $rel['from'] ?? null;
                $to     = $rel['to'] ?? null;
                $fromCol= $rel['foreign_key'] ?? null;
                if ($from && $to && $fromCol) {
                    $lines[] = "ALTER TABLE `{$from}` ADD CONSTRAINT `fk_{$from}_{$fromCol}`";
                    $lines[] = "  FOREIGN KEY (`{$fromCol}`) REFERENCES `{$to}` (`id`);";
                    $lines[] = "";
                }
            }
        }

        return implode("\n", $lines);
    }

    private function mapErdTypeTomysql(string $type): string
    {
        return match(strtolower(trim($type))) {
            'integer', 'int', 'biginteger', 'bigint' => 'BIGINT',
            'string', 'varchar'                       => 'VARCHAR(255)',
            'text', 'longtext'                        => 'TEXT',
            'boolean', 'bool', 'tinyint'              => 'TINYINT(1)',
            'timestamp', 'datetime'                   => 'TIMESTAMP',
            'date'                                    => 'DATE',
            'decimal', 'float', 'double'              => 'DECIMAL(10,2)',
            'json'                                    => 'JSON',
            default                                   => strtoupper($type),
        };
    }

    private function arrayToYaml(array $data, int $indent = 0): string
    {
        $yaml   = '';
        $pad    = str_repeat('  ', $indent);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (array_is_list($value)) {
                    $yaml .= "{$pad}{$key}:\n";
                    foreach ($value as $item) {
                        if (is_array($item)) {
                            $yaml .= "{$pad}-\n" . $this->arrayToYaml($item, $indent + 1);
                        } else {
                            $yaml .= "{$pad}- " . $this->yamlScalar($item) . "\n";
                        }
                    }
                } else {
                    $yaml .= "{$pad}{$key}:\n" . $this->arrayToYaml($value, $indent + 1);
                }
            } else {
                $yaml .= "{$pad}{$key}: " . $this->yamlScalar($value) . "\n";
            }
        }

        return $yaml;
    }

    private function yamlScalar(mixed $value): string
    {
        if ($value === null) return 'null';
        if ($value === true)  return 'true';
        if ($value === false) return 'false';
        if (is_numeric($value)) return (string) $value;
        $str = (string) $value;
        if (str_contains($str, ':') || str_contains($str, '#') || str_contains($str, "\n")) {
            return '"' . addslashes($str) . '"';
        }
        return $str;
    }

    private function rbacToMarkdown(array $rbacData): string
    {
        $lines = ["# 권한 모델 (RBAC)", "", "## 역할 정의", ""];

        foreach ($rbacData['roles'] ?? [] as $role) {
            $lines[] = "### {$role['name']}";
            $lines[] = $role['description'] ?? '';
            $lines[] = "";
        }

        $lines[] = "## 권한 매트릭스";
        $lines[] = "";

        $roles = array_column($rbacData['roles'] ?? [], 'name');
        if ($roles) {
            $header  = "| 리소스/액션 | " . implode(" | ", $roles) . " |";
            $divider = "|" . str_repeat("---|", count($roles) + 1);
            $lines[] = $header;
            $lines[] = $divider;

            foreach ($rbacData['permissions'] ?? [] as $perm) {
                $row = "| {$perm['resource']} / {$perm['action']} |";
                foreach ($roles as $role) {
                    $allowed = in_array($role, $perm['allowed_roles'] ?? []) ? "✅" : "❌";
                    $row    .= " {$allowed} |";
                }
                $lines[] = $row;
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function rbacToLaravelPolicy(array $rbacData): string
    {
        $perms = $rbacData['permissions'] ?? [];
        $methods = [];

        foreach ($perms as $perm) {
            $resource = Str::camel($perm['resource'] ?? 'resource');
            $action   = Str::camel($perm['action'] ?? 'action');
            $allowed  = $perm['allowed_roles'] ?? [];
            $roleStr  = empty($allowed)
                ? 'return false;'
                : "return in_array(\$user->role, ['" . implode("', '", $allowed) . "']);";

            $methodName = lcfirst($resource) . ucfirst($action);
            $methods[$methodName] = <<<PHP
                public function {$methodName}(User \$user): bool
                {
                    {$roleStr}
                }
            PHP;
        }

        $methodBlock = implode("\n\n", $methods);

        return <<<PHP
        <?php

        namespace App\Policies;

        use App\Models\User;
        use Illuminate\Auth\Access\HandlesAuthorization;

        /**
         * Auto-generated by 웍스 Agent T48 from RBAC model.
         */
        class RolePolicy
        {
            use HandlesAuthorization;

        {$methodBlock}
        }
        PHP;
    }

    private function reviewToMarkdown(array $data): string
    {
        $score   = $data['overall_score'] ?? 0;
        $summary = $data['executive_summary'] ?? '';
        $arch    = $data['architecture_assessment'] ?? '';

        $lines = [
            "# 웍스 코드 리뷰 요약",
            "",
            "**종합 점수**: {$score}/100",
            "",
            "## 개요",
            $summary,
            "",
            "## 아키텍처 평가",
            $arch,
            "",
        ];

        if (!empty($data['data_flow_issues'])) {
            $lines[] = "## 데이터 흐름 이슈";
            $lines[] = "";
            foreach ($data['data_flow_issues'] as $issue) {
                $lines[] = "### {$issue['title']}";
                $lines[] = "- **심각도**: {$issue['severity']}";
                $lines[] = "- **설명**: {$issue['description']}";
                $lines[] = "- **제안**: {$issue['suggestion']}";
                $lines[] = "";
            }
        }

        if (!empty($data['strengths'])) {
            $lines[] = "## 강점";
            foreach ($data['strengths'] as $s) {
                $lines[] = "- {$s}";
            }
        }

        return implode("\n", $lines) . "\n";
    }

    private function buildRequirementsCsv(int $projectId): string
    {
        $reqs = AiAgentRequirement::where('project_id', $projectId)
            ->orderBy('req_id')
            ->get();

        if ($reqs->isEmpty()) return '';

        $rows = [['요구사항 ID', '제목', '설명', '우선순위', '카테고리', '상태']];
        foreach ($reqs as $req) {
            $rows[] = [
                $req->req_id,
                $req->title,
                $req->description ?? '',
                $req->priority?->value ?? '',
                $req->category ?? '',
                $req->status ?? '',
            ];
        }

        $csv = '';
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', (string) $v) . '"',
                $row
            )) . "\r\n";
        }

        return $csv;
    }

    // ── Private: helpers ──────────────────────────────────────────────────────

    private function latest(int $projectId, ArtifactType $type): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', $type->value)
            ->latest('created_at')
            ->first();
    }

    private function persistArtifact(Project $project, string $zipPath, array $manifest, User $user): void
    {
        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::RELEASE)
            ->first();

        AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::RELEASE_PACKAGE,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "릴리즈 패키지 — {$project->name} (" . now()->format('Y-m-d') . ")",
            content:   json_encode([
                'package_path' => $zipPath,
                'manifest'     => $manifest,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:    $user->id,
            meta: [
                'package_size_bytes' => file_exists($zipPath) ? filesize($zipPath) : 0,
                'generated_at'       => now()->toIso8601String(),
            ],
        );
    }
}
