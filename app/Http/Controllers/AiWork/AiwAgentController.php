<?php

namespace App\Http\Controllers\AiWork;

use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwAgentProject;
use App\Models\AiWork\AiwJob;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 화면 1: 작업 PC(데몬) 관리.
 *
 * 토큰은 사실상 그 PC 에서 명령을 실행할 자격이므로 관리자만 다룬다.
 * 원문은 생성 직후 1회만 보여주고 저장하지 않는다(해시만 보관).
 */
class AiwAgentController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageAgents', AiwJob::class);

        $agents = AiwAgent::query()
            ->with('owner:id,name')
            ->withCount('agentProjects')
            ->orderByDesc('last_seen_at')
            ->get();

        return view('aiw.agents.index', [
            'agents'   => $agents,
            'projects' => Project::orderBy('name')->get(['id', 'name']),
            // 방금 생성한 토큰 원문. 세션 플래시로 한 번만 전달된다.
            'newToken' => session('aiw_new_token'),
            'newAgent' => session('aiw_new_agent'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:100'],
            'expires_days'  => ['nullable', 'integer', 'min:1', 'max:730'],
            'allowed_ips'   => ['nullable', 'string', 'max:500'],
        ]);

        $raw = AiwAgent::generateToken();

        $agent = AiwAgent::create([
            'name'        => $validated['name'],
            'token_hash'  => AiwAgent::hashToken($raw),
            'user_id'     => $request->user()->id,
            'expires_at'  => now()->addDays((int) ($validated['expires_days'] ?? config('aiw.token_ttl_days', 90))),
            'allowed_ips' => $this->parseIps($validated['allowed_ips'] ?? null),
        ]);

        // 원문은 여기서만 사용자에게 보인다. 다시 조회할 수 없고 재발급만 가능하다.
        return back()->with([
            'aiw_new_token' => $raw,
            'aiw_new_agent' => $agent->name,
        ]);
    }

    /** 토큰 재발급. 기존 토큰은 즉시 무효가 된다. */
    public function regenerate(Request $request, AiwAgent $agent): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $raw = AiwAgent::generateToken();

        $agent->forceFill([
            'token_hash' => AiwAgent::hashToken($raw),
            'expires_at' => now()->addDays((int) config('aiw.token_ttl_days', 90)),
        ])->save();

        return back()->with([
            'aiw_new_token' => $raw,
            'aiw_new_agent' => $agent->name,
            'status'        => '토큰을 재발급했습니다. 기존 토큰은 즉시 무효입니다.',
        ]);
    }

    public function destroy(AiwAgent $agent): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $agent->delete();

        return back()->with('status', '작업 PC 를 삭제했습니다.');
    }

    /** 프로젝트 매핑 추가 — local_path 가 데몬의 작업 루트(샌드박스 ROOT)가 된다. */
    public function storeMapping(Request $request, AiwAgent $agent): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);

        $validated = $request->validate([
            'project_id'     => ['required', 'integer', 'exists:projects,id'],
            'display_name'   => ['nullable', 'string', 'max:100'],
            'local_path'     => ['required', 'string', 'max:500'],
            'default_branch' => ['nullable', 'string', 'max:100'],
        ]);

        AiwAgentProject::updateOrCreate(
            ['agent_id' => $agent->id, 'project_id' => $validated['project_id']],
            [
                // 프로젝트마다 실제 책임자가 다를 수 있다. 비우면 담당자 본래 이름.
                'display_name'   => trim((string) ($validated['display_name'] ?? '')) ?: null,
                'local_path'     => trim($validated['local_path']),
                'default_branch' => $validated['default_branch'] ?: null,
            ],
        );

        return back()->with('status', '프로젝트 매핑을 저장했습니다.');
    }

    public function destroyMapping(AiwAgent $agent, AiwAgentProject $mapping): RedirectResponse
    {
        $this->authorize('manageAgents', AiwJob::class);
        abort_unless((int) $mapping->agent_id === (int) $agent->id, 404);

        $mapping->delete();

        return back()->with('status', '매핑을 삭제했습니다.');
    }

    /** 줄바꿈·쉼표로 구분된 입력을 배열로. 비어 있으면 null(= 제한 없음). */
    private function parseIps(?string $raw): ?array
    {
        if (blank($raw)) {
            return null;
        }

        $ips = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $raw))));

        return $ips ?: null;
    }
}
