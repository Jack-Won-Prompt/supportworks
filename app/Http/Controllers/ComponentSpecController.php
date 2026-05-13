<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ComponentSpecService;
use App\Services\Agent\Figma\FigmaUrlParser;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaInvalidTokenException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ComponentSpecController extends Controller
{
    public function __construct(
        private readonly ComponentSpecService $specService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $artifact = $this->specService->getCurrent($project);
        $specData = null;

        if ($artifact) {
            $raw      = is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true);
            $specData = $raw;
        }

        /** @var \App\Models\User $user */
        $user   = Auth::user();
        $hasPat = $user->aiAgentCredential?->hasPat() ?? false;

        // T28 토큰 산출물 존재 여부
        $hasTokens = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::DESIGN_TOKENS->value)
            ->exists();

        $pageTitle  = 'Component 명세서';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.components.index', compact(
            'project', 'artifact', 'specData', 'hasPat', 'hasTokens', 'pageTitle', 'stageLabel'
        ));
    }

    public function extract(Request $request, Project $project): JsonResponse
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
            $result   = $this->specService->extractFromFigma($project, $fileKey, $user);
            $artifact = $result['artifact'];
            $specSet  = $result['specSet'];
            $stats    = $specSet->getStats();

            return response()->json([
                'success'           => true,
                'message'           => "컴포넌트 명세 추출 완료 ({$stats['total_components']}개)",
                'total_components'  => $stats['total_components'],
                'component_sets'    => $stats['component_sets'],
                'single_components' => $stats['single_components'],
                'total_variants'    => $stats['total_variants'],
                'artifact_id'       => $artifact->id,
            ]);
        } catch (FigmaTokenNotConfiguredException) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다. 설정 페이지에서 토큰을 등록해 주세요.'], 403);
        } catch (FigmaInvalidTokenException) {
            return response()->json(['success' => false, 'message' => 'Figma 토큰이 유효하지 않습니다.'], 401);
        } catch (FigmaAccessDeniedException) {
            return response()->json(['success' => false, 'message' => '해당 Figma 파일에 접근 권한이 없습니다.'], 403);
        } catch (\Exception $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '추출 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function show(Project $project, string $componentKey): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->specService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['message' => '명세 산출물이 없습니다.'], 404);
        }

        $data = is_array($artifact->content) ? $artifact->content : json_decode($artifact->content, true);
        $component = $data['components'][$componentKey] ?? null;

        if (!$component) {
            return response()->json(['message' => '컴포넌트를 찾을 수 없습니다.'], 404);
        }

        return response()->json(['component' => $component]);
    }

    public function export(Request $request, Project $project): HttpResponse|JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->specService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['message' => '추출된 명세가 없습니다.'], 404);
        }

        $format  = $request->query('format', 'json');
        $specSet = $this->specService->parseSpecSet($artifact);

        if (!$specSet) {
            return response()->json(['message' => '명세 데이터 파싱 오류.'], 500);
        }

        $projectSlug = \Illuminate\Support\Str::slug($project->name);

        return match ($format) {
            'markdown' => response($specSet->toMarkdown(), 200, [
                'Content-Type'        => 'text/markdown',
                'Content-Disposition' => "attachment; filename=\"component-spec-{$projectSlug}.md\"",
            ]),
            default => response($specSet->toJson(), 200, [
                'Content-Type'        => 'application/json',
                'Content-Disposition' => "attachment; filename=\"component-spec-{$projectSlug}.json\"",
            ]),
        };
    }

    public function update(Request $request, Project $project, string $componentKey): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'description'   => ['nullable', 'string', 'max:1000'],
            'documentation' => ['nullable', 'string', 'max:5000'],
        ]);

        $artifact = $this->specService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '명세 산출물이 없습니다.'], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->specService->updateComponent($artifact, $componentKey, $request->only(['description', 'documentation']), $user->id);

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
