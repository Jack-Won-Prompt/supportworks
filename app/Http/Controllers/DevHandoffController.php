<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\DevHandoffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DevHandoffController extends Controller
{
    public function __construct(
        private readonly DevHandoffService $handoffService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $stats    = $this->handoffService->getMappingStats($project->id);
        $devData  = $this->handoffService->collectDevModeData($project->id);
        $unmapped = $this->handoffService->getUnmappedScreens($project->id);
        $artifact = $this->getCurrent($project);

        // Summarize which Phase 3 artifacts exist
        $designArtifacts = $this->getPhase3ArtifactStatus($project);

        $pageTitle  = 'Figma Dev Mode URL';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.dev-handoff.index', compact(
            'project', 'stats', 'devData', 'unmapped', 'artifact', 'designArtifacts', 'pageTitle', 'stageLabel'
        ));
    }

    public function validate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->aiAgentCredential?->hasPat()) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다.'], 403);
        }

        try {
            $results = $this->handoffService->validateDevModeUrls($project->id, $user);

            $valid   = count(array_filter($results, fn($r) => $r['is_valid']));
            $invalid = count($results) - $valid;

            return response()->json([
                'success' => true,
                'results' => $results,
                'summary' => [
                    'total'   => count($results),
                    'valid'   => $valid,
                    'invalid' => $invalid,
                ],
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '검증 실패: ' . $e->getMessage()], 500);
        }
    }

    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $artifact = $this->handoffService->generateHandoffArtifact($project, $user);

            return response()->json([
                'success'     => true,
                'message'     => '핸드오프 산출물이 생성되었습니다.',
                'artifact_id' => $artifact->id,
                'version'     => $artifact->version,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '생성 실패: ' . $e->getMessage()], 500);
        }
    }

    public function export(Request $request, Project $project): \Illuminate\Http\Response|JsonResponse
    {
        $this->authorizeProject($project);

        $format = $request->query('format', 'md');
        $slug   = Str::slug($project->name);
        $date   = now()->format('Ymd');

        $devData  = $this->handoffService->collectDevModeData($project->id);
        $unmapped = $this->handoffService->getUnmappedScreens($project->id);

        if ($format === 'csv') {
            $content  = $this->handoffService->generateCsv($project->id);
            $filename = "dev-handoff-{$slug}-{$date}.csv";
            $mime     = 'text/csv';
        } else {
            $content  = $this->handoffService->renderHandoffMarkdown($project, $devData, $unmapped);
            $filename = "dev-handoff-{$slug}-{$date}.md";
            $mime     = 'text/markdown';
        }

        return response($content, 200, [
            'Content-Type'        => $mime . '; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function package(Request $request, Project $project): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $zipPath  = $this->handoffService->buildPackage($project, $user);
            $slug     = Str::slug($project->name);
            $date     = now()->format('Ymd');
            $filename = "phase3-handoff-{$slug}-{$date}.zip";

            return response()->download($zipPath, $filename)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '패키지 생성 실패: ' . $e->getMessage()], 500);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DEV_HANDOFF->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
    }

    private function getPhase3ArtifactStatus(Project $project): array
    {
        $types = [
            ArtifactType::DESIGN_TOKENS     => 'Design Tokens (T28)',
            ArtifactType::COMPONENT_SPEC    => 'Components (T29)',
            ArtifactType::LAYOUT_SPEC       => 'Layouts (T30)',
            ArtifactType::DESIGN_REVIEW     => '일관성 검수 (T32)',
            ArtifactType::DESIGN_SYSTEM_DOC => '디자인 시스템 문서 (T33)',
        ];

        $result = [];
        foreach ($types as $type => $label) {
            $artifact = AiAgentArtifact::where('project_id', $project->id)
                ->where('type', $type->value)
                ->where('scope_type', 'project')
                ->latest()
                ->first();

            $result[] = [
                'label'   => $label,
                'ready'   => $artifact !== null && !empty($artifact->content),
                'version' => $artifact?->version,
            ];
        }

        return $result;
    }

    private function authorizeProject(Project $project): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists(),
            403, '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
