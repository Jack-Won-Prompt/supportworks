<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\LayoutSpecService;
use App\Services\Agent\Figma\FigmaUrlParser;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaInvalidTokenException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LayoutSpecController extends Controller
{
    public function __construct(
        private readonly LayoutSpecService $layoutService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $artifact = $this->layoutService->getCurrent($project);
        $specData = null;

        if ($artifact) {
            $raw      = is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true);
            $specData = $raw;
        }

        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $hasPat = $user->aiAgentCredential?->hasPat() ?? false;

        $pageTitle  = '표준 Layout / Grid';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.layouts.index', compact(
            'project', 'artifact', 'specData', 'hasPat', 'pageTitle', 'stageLabel'
        ));
    }

    public function analyze(Request $request, Project $project): JsonResponse
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
            $result   = $this->layoutService->extractFromFigma($project, $fileKey, $user);
            $artifact = $result['artifact'];
            $specSet  = $result['specSet'];
            $stats    = $specSet->getStats();

            return response()->json([
                'success'                      => true,
                'message'                      => "레이아웃 분석 완료 (표준 {$stats['standard_layouts_identified']}개 식별)",
                'total_frames_analyzed'        => $stats['total_frames_analyzed'],
                'standard_layouts_identified'  => $stats['standard_layouts_identified'],
                'non_standard_frames'          => $stats['non_standard_frames'],
                'artifact_id'                  => $artifact->id,
            ]);
        } catch (FigmaTokenNotConfiguredException) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다. 설정 페이지에서 토큰을 등록해 주세요.'], 403);
        } catch (FigmaInvalidTokenException) {
            return response()->json(['success' => false, 'message' => 'Figma 토큰이 유효하지 않습니다.'], 401);
        } catch (FigmaAccessDeniedException) {
            return response()->json(['success' => false, 'message' => '해당 Figma 파일에 접근 권한이 없습니다.'], 403);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '분석 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function preview(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->layoutService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['exists' => false], 404);
        }

        $data = is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true);

        return response()->json(['exists' => true, 'spec' => $data, 'meta' => $artifact->meta]);
    }

    public function export(Request $request, Project $project): HttpResponse|JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->layoutService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['message' => '분석된 레이아웃이 없습니다.'], 404);
        }

        $specSet = $this->layoutService->parseSpecSet($artifact);
        if (!$specSet) {
            return response()->json(['message' => '데이터 파싱 오류.'], 500);
        }

        $projectSlug = \Illuminate\Support\Str::slug($project->name);

        return response($specSet->toJson(), 200, [
            'Content-Type'        => 'application/json',
            'Content-Disposition' => "attachment; filename=\"layout-spec-{$projectSlug}.json\"",
        ]);
    }

    public function update(Request $request, Project $project, string $layoutKey): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'name'        => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $artifact = $this->layoutService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '레이아웃 산출물이 없습니다.'], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->layoutService->updateStandardLayout(
            $artifact,
            $layoutKey,
            $request->only(['name', 'description']),
            $user->id,
        );

        return response()->json(['success' => true, 'message' => '저장되었습니다.']);
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
