<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\RbacAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RbacController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:rbac:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly RbacAiService $rbacService,
    ) {}

    // ── 메인 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $context  = $this->rbacService->buildContext($project->id);
        $artifact = $this->getArtifact($project->id);

        $rbacData        = null;
        $hasRbac         = false;
        if ($artifact && !empty($artifact->content)) {
            $rbacData = json_decode($artifact->content, true);
            $hasRbac  = !empty($rbacData['roles']);
        }

        $roles       = $rbacData['roles']       ?? [];
        $permissions = $rbacData['permissions'] ?? [];
        $policies    = $rbacData['policies']    ?? [];

        $historyUrl = $artifact
            ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
            : null;

        return view('ai-agent.dev-prep.rbac.index', [
            'project'         => $project,
            'artifact'        => $artifact,
            'hasRbac'         => $hasRbac,
            'context'         => $context,
            'hasErd'          => (bool) $context['erd_artifact_id'],
            'hasApiSpec'      => (bool) $context['api_spec_artifact_id'],
            'screenCount'     => $context['screen_count'],
            'roles'           => $roles,
            'permissions'     => $permissions,
            'policies'        => $policies,
            'rolesCount'      => count($roles),
            'permissionsCount'=> count($permissions),
            'policiesCount'   => count($policies),
            'historyUrl'      => $historyUrl,
            'startUrl'        => route('ai-agent.projects.pre-dev.rbac.generate.start', $project),
            'sseUrlTpl'       => route('ai-agent.projects.pre-dev.rbac.generate.sse', [$project, 'SESSION_ID']),
            'saveUrl'         => route('ai-agent.projects.pre-dev.rbac.save', $project),
            'exportUrl'       => route('ai-agent.projects.pre-dev.rbac.export', $project),
            'regenerateUrl'   => route('ai-agent.projects.pre-dev.rbac.regenerate', $project),
            'rolesStoreUrl'   => route('ai-agent.projects.pre-dev.rbac.roles.store', $project),
            'permissionsStoreUrl' => route('ai-agent.projects.pre-dev.rbac.permissions.store', $project),
            'matrixUpdateUrl' => route('ai-agent.projects.pre-dev.rbac.matrix.update', $project),
            'cancelUrlTpl'    => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'       => '권한 모델 — RBAC',
        ]);
    }

    // ── 생성 시작 ────────────────────────────────────────────────────────────

    public function generateStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id' => $project->id,
            'user_id'    => (int) auth()->id(),
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── SSE 생성 스트림 ──────────────────────────────────────────────────────

    public function generateSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => 'RBAC 권한 모델 생성을 시작합니다...', 'progress' => 5]);
            $startedAt = microtime(true);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $result   = $this->rbacService->generate(
                    projectId:  $project->id,
                    userId:     $session['user_id'],
                    onProgress: function (array $progress) use ($startedAt) {
                        $this->sseEvent('progress', array_merge($progress, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );

                $artifact = $result['artifact'];
                $rbacData = json_decode($artifact->content, true);

                $this->sseEvent('complete', [
                    'status'           => 'COMPLETED',
                    'roles_count'      => $result['roles_count'],
                    'permissions_count'=> $result['permissions_count'],
                    'tokens_in'        => $result['tokens_in'],
                    'tokens_out'       => $result['tokens_out'],
                    'cost_usd'         => round($result['cost'], 4),
                    'model'            => $result['model'],
                    'elapsed'          => round(microtime(true) - $startedAt, 2),
                    'artifact_id'      => $artifact->id,
                    'version'          => $artifact->version,
                    'roles'            => $rbacData['roles']       ?? [],
                    'permissions'      => $rbacData['permissions'] ?? [],
                    'policies'         => $rbacData['policies']    ?? [],
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 저장 (전체 내용 저장) ────────────────────────────────────────────────

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'content' => 'required|string|min:2',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->updateWithVersion(
            content: $validated['content'],
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 내보내기 ─────────────────────────────────────────────────────────────

    public function export(Request $request, Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $format   = $request->query('format', 'json');
        $artifact = $this->getArtifact($project->id);

        if (!$artifact || empty($artifact->content)) {
            abort(404, 'RBAC 산출물이 없습니다.');
        }

        $rbacData = json_decode($artifact->content, true) ?? [];
        $slug     = Str::slug($project->name);
        $date     = now()->format('Ymd');

        return match ($format) {
            'markdown' => $this->exportMarkdown($rbacData, $slug, $date),
            'policy'   => $this->exportLaravelPolicy($rbacData, $slug, $date),
            default    => $this->exportJson($rbacData, $slug, $date),
        };
    }

    // ── 재생성 ───────────────────────────────────────────────────────────────

    public function regenerate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $result   = $this->rbacService->generate(
                projectId:  $project->id,
                userId:     (int) auth()->id(),
                onProgress: fn() => null,
            );

            $artifact = $result['artifact'];
            $rbacData = json_decode($artifact->content, true);

            return response()->json([
                'success'          => true,
                'version'          => $artifact->version,
                'roles_count'      => $result['roles_count'],
                'permissions_count'=> $result['permissions_count'],
                'roles'            => $rbacData['roles']       ?? [],
                'permissions'      => $rbacData['permissions'] ?? [],
                'policies'         => $rbacData['policies']    ?? [],
                'model'            => $result['model'],
                'cost_usd'         => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── 역할 추가 ────────────────────────────────────────────────────────────

    public function storeRole(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'key'         => 'required|string|alpha_dash|max:50',
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물이 없습니다.'], 404);
        }

        $data  = json_decode($artifact->content, true) ?? [];
        $roles = $data['roles'] ?? [];

        if (in_array($validated['key'], array_column($roles, 'key'))) {
            return response()->json(['success' => false, 'message' => "역할 키 '{$validated['key']}'가 이미 존재합니다."], 422);
        }

        $roles[] = [
            'key'         => $validated['key'],
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? '',
            'permissions' => $validated['permissions']  ?? [],
        ];

        $data['roles'] = $roles;
        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'role_added', 'role_key' => $validated['key']],
        );

        return response()->json(['success' => true, 'roles' => $roles]);
    }

    // ── 역할 편집 ────────────────────────────────────────────────────────────

    public function updateRole(Request $request, Project $project, string $roleKey): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물이 없습니다.'], 404);
        }

        $data  = json_decode($artifact->content, true) ?? [];
        $roles = $data['roles'] ?? [];
        $found = false;

        foreach ($roles as &$role) {
            if ($role['key'] === $roleKey) {
                $role['name']        = $validated['name'];
                $role['description'] = $validated['description'] ?? ($role['description'] ?? '');
                $role['permissions'] = $validated['permissions']  ?? $role['permissions'];
                $found               = true;
                break;
            }
        }
        unset($role);

        if (!$found) {
            return response()->json(['success' => false, 'message' => "역할 '{$roleKey}'를 찾을 수 없습니다."], 404);
        }

        $data['roles'] = $roles;
        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'role_updated', 'role_key' => $roleKey],
        );

        return response()->json(['success' => true, 'roles' => $roles]);
    }

    // ── 역할 삭제 ────────────────────────────────────────────────────────────

    public function destroyRole(Request $request, Project $project, string $roleKey): JsonResponse
    {
        $this->authorizeProject($project);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물이 없습니다.'], 404);
        }

        $data  = json_decode($artifact->content, true) ?? [];
        $roles = $data['roles'] ?? [];
        $orig  = count($roles);
        $roles = array_values(array_filter($roles, fn($r) => $r['key'] !== $roleKey));

        if (count($roles) === $orig) {
            return response()->json(['success' => false, 'message' => "역할 '{$roleKey}'를 찾을 수 없습니다."], 404);
        }

        $data['roles'] = $roles;
        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'role_deleted', 'role_key' => $roleKey],
        );

        return response()->json(['success' => true, 'roles' => $roles]);
    }

    // ── 권한 추가 ────────────────────────────────────────────────────────────

    public function storePermission(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'key'          => 'required|string|max:100',
            'name'         => 'required|string|max:100',
            'resource'     => 'required|string|max:50',
            'action'       => 'required|string|in:view,create,edit,delete,approve,export,manage',
            'api_endpoints'=> 'nullable|array',
            'api_endpoints.*' => 'string',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물이 없습니다.'], 404);
        }

        $data        = json_decode($artifact->content, true) ?? [];
        $permissions = $data['permissions'] ?? [];

        if (in_array($validated['key'], array_column($permissions, 'key'))) {
            return response()->json(['success' => false, 'message' => "권한 키 '{$validated['key']}'가 이미 존재합니다."], 422);
        }

        $permissions[] = [
            'key'          => $validated['key'],
            'name'         => $validated['name'],
            'resource'     => $validated['resource'],
            'action'       => $validated['action'],
            'api_endpoints'=> $validated['api_endpoints'] ?? [],
        ];

        $data['permissions'] = $permissions;
        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'permission_added', 'permission_key' => $validated['key']],
        );

        return response()->json(['success' => true, 'permissions' => $permissions]);
    }

    // ── 매트릭스 일괄 편집 ───────────────────────────────────────────────────

    public function updateMatrix(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'matrix'          => 'required|array',
            'matrix.*'        => 'array',
            'matrix.*.*'      => 'boolean',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'RBAC 산출물이 없습니다.'], 404);
        }

        $data        = json_decode($artifact->content, true) ?? [];
        $roles       = $data['roles'] ?? [];
        $permissions = $data['permissions'] ?? [];

        $roles = $this->applyMatrixToRoles($roles, $permissions, $validated['matrix']);

        $data['roles'] = $roles;
        $artifact->updateWithVersion(
            content: json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'matrix_updated'],
        );

        return response()->json(['success' => true, 'roles' => $roles]);
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    private function exportJson(array $rbacData, string $slug, string $date): \Illuminate\Http\Response
    {
        $export = [
            'roles'       => $rbacData['roles']       ?? [],
            'permissions' => $rbacData['permissions'] ?? [],
            'policies'    => $rbacData['policies']    ?? [],
        ];
        $json     = json_encode($export, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $filename = "{$slug}-RBAC-{$date}.json";

        return response($json, 200, [
            'Content-Type'        => 'application/json; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportMarkdown(array $rbacData, string $slug, string $date): \Illuminate\Http\Response
    {
        $roles       = $rbacData['roles']       ?? [];
        $permissions = $rbacData['permissions'] ?? [];
        $policies    = $rbacData['policies']    ?? [];

        $md  = "# RBAC 권한 모델\n\n";
        $md .= "> 생성일: " . now()->format('Y-m-d') . "\n\n";

        $md .= "## 역할 (Roles)\n\n";
        foreach ($roles as $role) {
            $md .= "### {$role['name']} (`{$role['key']}`)\n\n";
            if ($role['description'] ?? '') $md .= "{$role['description']}\n\n";
            if (!empty($role['permissions'])) {
                $md .= "권한: " . implode(', ', array_map(fn($k) => "`{$k}`", $role['permissions'])) . "\n\n";
            }
        }

        // Permission matrix
        $actions   = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'manage'];
        $md       .= "## 권한 매트릭스\n\n";
        $header    = "| 역할 | " . implode(' | ', array_map('ucfirst', $actions)) . " |\n";
        $separator = "|------|" . implode("|", array_fill(0, count($actions), '--------')) . "|\n";
        $md       .= $header . $separator;

        foreach ($roles as $role) {
            $cells = [];
            foreach ($actions as $action) {
                $has = false;
                foreach ($permissions as $perm) {
                    if ($perm['action'] === $action && in_array($perm['key'], $role['permissions'] ?? [])) {
                        $has = true;
                        break;
                    }
                }
                $cells[] = $has ? '✅' : '-';
            }
            $md .= "| {$role['name']} | " . implode(' | ', $cells) . " |\n";
        }

        $md .= "\n## 권한 목록 (Permissions)\n\n";
        $resources = array_values(array_unique(array_column($permissions, 'resource')));
        foreach ($resources as $resource) {
            $md .= "### {$resource}\n\n";
            $resPerms = array_filter($permissions, fn($p) => $p['resource'] === $resource);
            foreach ($resPerms as $perm) {
                $endpoints = empty($perm['api_endpoints']) ? '' : ' — ' . implode(', ', $perm['api_endpoints']);
                $md .= "- `{$perm['key']}` {$perm['name']}{$endpoints}\n";
            }
            $md .= "\n";
        }

        if (!empty($policies)) {
            $md .= "## Policy 후보 (사용자 검토 필요)\n\n";
            foreach ($policies as $policy) {
                $review = ($policy['requires_review'] ?? false) ? ' ⚠️ 검토 필요' : '';
                $md .= "### {$policy['name']}{$review}\n\n";
                if ($policy['description'] ?? '') $md .= "{$policy['description']}\n\n";
                foreach ($policy['methods'] ?? [] as $method => $condition) {
                    $md .= "- `{$method}`: {$condition}\n";
                }
                $md .= "\n";
            }
        }

        $filename = "{$slug}-RBAC-{$date}.md";
        return response($md, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportLaravelPolicy(array $rbacData, string $slug, string $date): \Illuminate\Http\Response
    {
        $policies = $rbacData['policies'] ?? [];
        $code     = "<?php\n\n// RBAC Policy 코드 자동 생성 — " . now()->format('Y-m-d H:i') . "\n";
        $code    .= "// ⚠️ 웍스 추정 코드: 비즈니스 규칙을 반드시 검토 후 적용하세요.\n\n";

        if (empty($policies)) {
            $code .= "// Policy 후보가 없습니다. RBAC을 재생성하거나 수동으로 작성하세요.\n";
        }

        foreach ($policies as $policy) {
            $model      = $policy['model']    ?? 'Model';
            $policyName = $policy['name']     ?? "{$model}Policy";
            $desc       = $policy['description'] ?? '';
            $review     = ($policy['requires_review'] ?? false) ? '⚠️ 사용자 검토 필요' : '';

            $code .= "namespace App\\Policies;\n\n";
            $code .= "use App\\Models\\User;\n";
            $code .= "use App\\Models\\{$model};\n\n";
            if ($desc) $code .= "/** {$desc} */\n";
            if ($review) $code .= "/** {$review} */\n";
            $code .= "class {$policyName}\n{\n";

            foreach ($policy['methods'] ?? [] as $method => $condition) {
                $paramType = in_array($method, ['viewAny', 'create']) ? '' : ", {$model} \${$this->lcfirst($model)}";
                $code .= "    public function {$method}(User \$user{$paramType}): bool\n";
                $code .= "    {\n";
                $code .= "        // 웍스 추정: {$condition}\n";
                $code .= "        // TODO: 비즈니스 규칙 검토 후 구현\n";
                $code .= "        return false;\n";
                $code .= "    }\n\n";
            }

            $code .= "}\n\n";
            $code .= "// " . str_repeat('-', 60) . "\n\n";
        }

        $filename = "{$slug}-RBAC-Policies-{$date}.php";
        return response($code, 200, [
            'Content-Type'        => 'application/octet-stream',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function applyMatrixToRoles(array $roles, array $permissions, array $matrix): array
    {
        foreach ($matrix as $roleKey => $actions) {
            foreach ($roles as &$role) {
                if ($role['key'] !== $roleKey) continue;

                foreach ($actions as $action => $enabled) {
                    $actionPermKeys = array_column(
                        array_filter($permissions, fn($p) => ($p['action'] ?? '') === $action),
                        'key'
                    );

                    $current = $role['permissions'] ?? [];
                    if ($enabled) {
                        $role['permissions'] = array_values(array_unique(array_merge($current, $actionPermKeys)));
                    } else {
                        $role['permissions'] = array_values(array_filter($current, fn($pk) => !in_array($pk, $actionPermKeys)));
                    }
                }
                break;
            }
            unset($role);
        }
        return $roles;
    }

    private function lcfirst(string $str): string
    {
        return lcfirst($str);
    }

    private function getArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::RBAC_MODEL->value)
            ->latest('id')->first();
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();
        if (!ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists()
            && $project->created_by !== $userId) {
            abort(403);
        }
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ];
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        ob_implicit_flush(true);
    }
}
