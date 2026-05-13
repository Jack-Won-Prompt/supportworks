<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\DesignSystemAiService;
use App\Services\Agent\DesignSystemDataContext;
use App\Services\Agent\DesignSystemTemplateService;
use App\Enums\Agent\ArtifactType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DesignSystemController extends Controller
{
    public function __construct(
        private readonly DesignSystemDataContext   $dataContext,
        private readonly DesignSystemTemplateService $templateService,
        private readonly DesignSystemAiService    $aiService,
    ) {}

    /**
     * Live preview + action page.
     */
    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $dataStatus = $this->dataContext->getDataStatus($project->id);
        $missing    = $this->dataContext->getMissingRequired($project->id);
        $artifact   = $this->getCurrent($project);

        $pageTitle  = '디자인 시스템 문서';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.system.index', compact(
            'project', 'dataStatus', 'missing', 'artifact', 'pageTitle', 'stageLabel'
        ));
    }

    /**
     * Generate and save the artifact.
     */
    public function generate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $missing = $this->dataContext->getMissingRequired($project->id);
        if (!empty($missing)) {
            return response()->json([
                'success' => false,
                'message' => '필수 데이터 누락: ' . implode(', ', $missing),
            ], 422);
        }

        try {
            $data     = $this->dataContext->build($project->id);
            $data['ai_sections'] = [];

            /** @var \App\Models\User $user */
            $user     = Auth::user();
            $artifact = $this->aiService->saveArtifact($project, $data, $user);

            return response()->json([
                'success'     => true,
                'message'     => '디자인 시스템 문서가 생성되었습니다.',
                'artifact_id' => $artifact->id,
                'version'     => $artifact->version,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '생성 실패: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 웍스 enrichment — fills in empty descriptions, generates philosophy.
     */
    public function enrich(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '먼저 문서를 생성하세요.'], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $data = is_array($artifact->content)
                ? $artifact->content
                : json_decode($artifact->content, true);

            $aiSections = $data['ai_sections'] ?? [];

            // Philosophy
            if (empty($aiSections['philosophy'])) {
                $context = $this->dataContext->build($project->id);
                $aiSections['philosophy'] = $this->aiService->generatePhilosophy(
                    context:   $context,
                    userId:    $user->id,
                    projectId: $project->id,
                );
            }

            // Component descriptions
            if (!empty($data['components'])) {
                $enriched = $this->aiService->enrichComponentDescriptions(
                    components: $data['components'],
                    userId:     $user->id,
                    projectId:  $project->id,
                );
                foreach ($enriched as $key => $desc) {
                    if (isset($data['components']['components'][$key])) {
                        $data['components']['components'][$key]['description'] = $desc;
                    }
                }
            }

            $data['ai_sections'] = $aiSections;

            $artifact->updateWithVersion(
                content: json_encode($data, JSON_UNESCAPED_UNICODE),
                userId:  $user->id,
                meta:    ['change_type' => 'ai_enrichment'],
            );

            return response()->json([
                'success' => true,
                'message' => '웍스 보강이 완료되었습니다.',
                'version' => $artifact->version,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '웍스 보강 실패: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export as Markdown or HTML download.
     */
    public function export(Request $request, Project $project): \Illuminate\Http\Response|JsonResponse
    {
        $this->authorizeProject($project);

        $format = $request->query('format', 'html');

        // Always build from live data for freshness
        $missing = $this->dataContext->getMissingRequired($project->id);
        if (!empty($missing)) {
            return response()->json(['success' => false, 'message' => '필수 데이터 누락: ' . implode(', ', $missing)], 422);
        }

        $data     = $this->dataContext->build($project->id);
        $artifact = $this->getCurrent($project);
        $data['ai_sections'] = $artifact
            ? ((is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true))['ai_sections'] ?? [])
            : [];

        // Flatten helpers
        $data['flat_colors']     = DesignSystemTemplateService::flattenColors($data['tokens'] ?? []);
        $data['flat_typography'] = DesignSystemTemplateService::flattenTypography($data['tokens'] ?? []);
        $data['flat_shadows']    = DesignSystemTemplateService::flattenShadows($data['tokens'] ?? []);

        $slug = Str::slug($project->name);
        $date = now()->format('Ymd');

        if ($format === 'md') {
            $content  = $this->templateService->renderMarkdown($data);
            $filename = "design-system-{$slug}-{$date}.md";
            $mime     = 'text/markdown';
        } else {
            $content  = $this->templateService->renderHtml($data);
            $filename = "design-system-{$slug}-{$date}.html";
            $mime     = 'text/html';
        }

        return response($content, 200, [
            'Content-Type'        => $mime . '; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * User manual edit — saves arbitrary JSON patch.
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'content' => ['required', 'array'],
        ]);

        $artifact = $this->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '문서가 없습니다.'], 404);
        }

        $artifact->updateWithVersion(
            content: json_encode($request->content, JSON_UNESCAPED_UNICODE),
            userId:  (int) Auth::id(),
            meta:    ['change_type' => 'user_edit'],
        );

        return response()->json(['success' => true, 'message' => '저장되었습니다.', 'version' => $artifact->version]);
    }

    /**
     * Inline HTML preview (renders in iframe / new tab, not a download).
     */
    public function preview(Request $request, Project $project): \Illuminate\Http\Response|JsonResponse
    {
        $this->authorizeProject($project);

        $missing = $this->dataContext->getMissingRequired($project->id);
        if (!empty($missing)) {
            return response('<p>필수 데이터 누락: ' . implode(', ', $missing) . '</p>', 422);
        }

        $data     = $this->dataContext->build($project->id);
        $artifact = $this->getCurrent($project);
        $data['ai_sections'] = $artifact
            ? ((is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true))['ai_sections'] ?? [])
            : [];

        $data['flat_colors']     = DesignSystemTemplateService::flattenColors($data['tokens'] ?? []);
        $data['flat_typography'] = DesignSystemTemplateService::flattenTypography($data['tokens'] ?? []);
        $data['flat_shadows']    = DesignSystemTemplateService::flattenShadows($data['tokens'] ?? []);

        $html = $this->templateService->renderHtml($data);

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function getCurrent(Project $project): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_SYSTEM_DOC->value)
            ->where('scope_type', 'project')
            ->latest()
            ->first();
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
