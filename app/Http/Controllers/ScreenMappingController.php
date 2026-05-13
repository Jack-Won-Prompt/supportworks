<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ScreenMappingService;
use App\Services\Agent\Figma\FigmaUrlParser;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaInvalidTokenException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ScreenMappingController extends Controller
{
    public function __construct(
        private readonly ScreenMappingService $mappingService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $hasPat = $user->aiAgentCredential?->hasPat() ?? false;

        $status  = $this->mappingService->getMappingStatus($project->id);
        $screens = AiAgentScreen::where('project_id', $project->id)
            ->whereNull('archived_at')
            ->orderBy('screen_id')
            ->get(['id', 'screen_id', 'title', 'figma_frame_id', 'figma_frame_name', 'figma_file_key', 'figma_url'])
            ->map(fn($s) => [
                'id'              => $s->id,
                'screen_id'       => $s->screen_id,
                'title'           => $s->title,
                'figma_frame_id'  => $s->figma_frame_id,
                'figma_frame_name'=> $s->figma_frame_name,
                'figma_file_key'  => $s->figma_file_key,
                'figma_url'       => $s->figma_url,
            ])
            ->values()
            ->all();

        $pageTitle  = '화면 매핑 (SCR ↔ Figma)';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.screen-mapping.index', compact(
            'project', 'hasPat', 'status', 'screens', 'pageTitle', 'stageLabel'
        ));
    }

    public function loadFigma(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'figma_url' => ['required', 'string', 'max:500'],
        ]);

        $fileKey = FigmaUrlParser::parseFileKey($request->figma_url);
        if (!$fileKey) {
            return response()->json(['success' => false, 'message' => '유효하지 않은 Figma URL입니다.'], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $frames = $this->mappingService->getFigmaFrames($fileKey, $user);
            return response()->json([
                'success'  => true,
                'file_key' => $fileKey,
                'frames'   => $frames,
                'count'    => count($frames),
            ]);
        } catch (FigmaTokenNotConfiguredException) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다.'], 403);
        } catch (FigmaInvalidTokenException) {
            return response()->json(['success' => false, 'message' => 'Figma 토큰이 유효하지 않습니다.'], 401);
        } catch (FigmaAccessDeniedException) {
            return response()->json(['success' => false, 'message' => '해당 Figma 파일에 접근 권한이 없습니다.'], 403);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '로드 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function suggestions(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'figma_url' => ['required', 'string', 'max:500'],
        ]);

        $fileKey = FigmaUrlParser::parseFileKey($request->figma_url);
        if (!$fileKey) {
            return response()->json(['success' => false, 'message' => '유효하지 않은 Figma URL입니다.'], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $suggestions = $this->mappingService->suggestMappings($project->id, $fileKey, $user);
            return response()->json([
                'success'     => true,
                'suggestions' => $suggestions,
                'count'       => count($suggestions),
            ]);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '제안 생성 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function apply(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'screen_id'        => ['required', 'integer', 'exists:ai_agent_screens,id'],
            'figma_file_key'   => ['required', 'string', 'max:100'],
            'figma_node_id'    => ['required', 'string', 'max:100'],
            'figma_frame_name' => ['required', 'string', 'max:255'],
        ]);

        $screen = AiAgentScreen::findOrFail($request->screen_id);

        if ($screen->project_id !== $project->id) {
            abort(403);
        }

        if ($screen->hasFigmaMapping()) {
            return response()->json(['success' => false, 'message' => '이미 Figma 프레임이 매핑되어 있습니다.'], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $this->mappingService->applyMapping(
                screen:    $screen,
                fileKey:   $request->figma_file_key,
                nodeId:    $request->figma_node_id,
                frameName: $request->figma_frame_name,
                userId:    $user->id,
            );

            $status = $this->mappingService->getMappingStatus($project->id);

            return response()->json([
                'success' => true,
                'message' => "{$screen->screen_id} → {$request->figma_frame_name} 매핑 완료",
                'status'  => $status,
            ]);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '매핑 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function applyBatch(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'suggestions'   => ['required', 'array', 'min:1'],
            'suggestions.*' => ['array'],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $applied = $this->mappingService->applySuggestionsBatch($request->suggestions, $user);
            $status  = $this->mappingService->getMappingStatus($project->id);

            return response()->json([
                'success' => true,
                'message' => "{$applied}개 화면 매핑 완료",
                'applied' => $applied,
                'status'  => $status,
            ]);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '일괄 매핑 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function unmap(Project $project, AiAgentScreen $screen): JsonResponse
    {
        $this->authorizeProject($project);

        if ($screen->project_id !== $project->id) {
            abort(403);
        }

        $screen->unmapFromFigma();
        $status = $this->mappingService->getMappingStatus($project->id);

        return response()->json([
            'success' => true,
            'message' => "{$screen->screen_id} 매핑 해제 완료",
            'status'  => $status,
        ]);
    }

    public function export(Project $project): HttpResponse|JsonResponse
    {
        $this->authorizeProject($project);

        $data        = $this->mappingService->exportMappings($project->id);
        $projectSlug = \Illuminate\Support\Str::slug($project->name);

        return response(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"screen-mapping-{$projectSlug}.json\"",
        ]);
    }

    private function authorizeProject(Project $project): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if ($user->isAdmin()) return;
        abort_unless(
            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }
}
