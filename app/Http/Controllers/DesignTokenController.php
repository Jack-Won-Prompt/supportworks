<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Services\Agent\DesignTokenService;
use App\Services\Agent\Figma\FigmaUrlParser;
use App\Services\Agent\Figma\Exceptions\FigmaAccessDeniedException;
use App\Services\Agent\Figma\Exceptions\FigmaInvalidTokenException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DesignTokenController extends Controller
{
    public function __construct(
        private readonly DesignTokenService $tokenService,
    ) {}

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $artifact  = $this->tokenService->getCurrent($project);
        $tokenData = null;

        if ($artifact) {
            $data = is_array($artifact->content)
                ? $artifact->content
                : json_decode($artifact->content, true);
            $tokenData = $data;
        }

        /** @var \App\Models\User $user */
        $user     = Auth::user();
        $hasPat   = $user->aiAgentCredential?->hasPat() ?? false;

        $pageTitle  = 'Design Tokens';
        $stageLabel = '단계 2: 디자인';

        return view('ai-agent.design.tokens.index', compact(
            'project', 'artifact', 'tokenData', 'hasPat', 'pageTitle', 'stageLabel'
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
            $result   = $this->tokenService->extractFromFigma($project, $fileKey, $user);
            $artifact = $result['artifact'];
            $tokenSet = $result['tokenSet'];

            return response()->json([
                'success'       => true,
                'message'       => "디자인 토큰 추출 완료 ({$tokenSet->getTokenCount()}개)",
                'token_count'   => $tokenSet->getTokenCount(),
                'color_count'   => $tokenSet->getCategoryCount('color'),
                'typography_count' => $tokenSet->getCategoryCount('typography'),
                'shadow_count'  => $tokenSet->getCategoryCount('shadow'),
                'artifact_id'   => $artifact->id,
            ]);
        } catch (FigmaTokenNotConfiguredException) {
            return response()->json(['success' => false, 'message' => 'Figma PAT이 설정되지 않았습니다. 설정 페이지에서 토큰을 등록해 주세요.'], 403);
        } catch (FigmaInvalidTokenException) {
            return response()->json(['success' => false, 'message' => 'Figma 토큰이 유효하지 않습니다.'], 401);
        } catch (FigmaAccessDeniedException) {
            return response()->json(['success' => false, 'message' => '해당 Figma 파일에 접근 권한이 없습니다.'], 403);
        } catch (\Exception $e) {
            \App\Models\SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => '토큰 추출 중 오류: ' . $e->getMessage()], 500);
        }
    }

    public function preview(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->tokenService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['exists' => false], 404);
        }

        $data = is_array($artifact->content)
            ? $artifact->content
            : json_decode($artifact->content, true);

        return response()->json(['exists' => true, 'tokens' => $data, 'meta' => $artifact->meta]);
    }

    public function export(Request $request, Project $project): HttpResponse|JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->tokenService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['message' => '추출된 토큰이 없습니다.'], 404);
        }

        $format   = $request->query('format', 'json');
        $tokenSet = $this->tokenService->parseTokenSet($artifact);

        if (!$tokenSet) {
            return response()->json(['message' => '토큰 데이터 파싱 오류.'], 500);
        }

        $projectSlug = \Illuminate\Support\Str::slug($project->name);

        return match ($format) {
            'css' => response($tokenSet->toCss(), 200, [
                'Content-Type'        => 'text/css',
                'Content-Disposition' => "attachment; filename=\"design-tokens-{$projectSlug}.css\"",
            ]),
            'tailwind' => response($tokenSet->toTailwindConfig(), 200, [
                'Content-Type'        => 'application/javascript',
                'Content-Disposition' => "attachment; filename=\"tailwind.config.tokens-{$projectSlug}.js\"",
            ]),
            default => response($tokenSet->toJson(), 200, [
                'Content-Type'        => 'application/json',
                'Content-Disposition' => "attachment; filename=\"design-tokens-{$projectSlug}.json\"",
            ]),
        };
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $request->validate([
            'content' => ['required', 'string'],
        ]);

        $decoded = json_decode($request->content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => '유효하지 않은 JSON 형식입니다.'], 422);
        }

        $artifact = $this->tokenService->getCurrent($project);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => '토큰 산출물이 없습니다.'], 404);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $artifact->updateWithVersion($request->content, $user->id, '사용자 직접 편집');

        return response()->json(['success' => true, 'message' => '저장되었습니다. (버전 ' . $artifact->fresh()->version . ')']);
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
