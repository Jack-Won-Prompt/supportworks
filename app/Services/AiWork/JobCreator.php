<?php

namespace App\Services\AiWork;

use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwJobMessage;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * 작업 지시 등록.
 *
 * 웹 화면과 모바일 앱이 같은 경로로 지시를 만든다. 입력을 받는 방식(폼 / JSON)과
 * 고를 수 있는 항목은 달라도, 만들어지는 job 과 작업 PC 에 전달되는 모양은 하나여야
 * 한다 — 두 벌로 두면 한쪽만 고쳐져 같은 지시가 다르게 돈다.
 *
 * 입력 형식 검증은 호출자가 한다. 여기서는 서버가 강제해야 하는 것(담당자 매핑,
 * 툴 목록, 배포 대상의 프로젝트, 자동 배포 성립 조건)만 다시 확인한다.
 */
class JobCreator
{
    public function __construct(
        private JobDispatcher $dispatcher,
        private ToolPolicy $tools,
        private AttachmentService $attachments,
    ) {}

    /**
     * @param  array{
     *     title: string,
     *     agent_id: int,
     *     instruction: string,
     *     mode: string,
     *     model?: ?string,
     *     allowed_tools: array<int, string>,
     *     permission_mode: string,
     *     cost_limit_usd?: float|string|null,
     *     use_branch: bool,
     *     parent_job_id?: ?int,
     *     auto_deploy?: bool,
     *     auto_deploy_target_id?: ?int,
     * }  $input  cost_limit_usd 가 null 이면 상한 없음
     * @param  array<int, UploadedFile>  $images  첫 메시지(지시문)에 붙일 이미지
     * @return array{0: AiwJob, 1: bool}  [만든 job, 작업 PC 에 바로 보냈는가(false = 오프라인이라 대기)]
     */
    public function create(Project $project, User $user, array $input, array $images = []): array
    {
        $agent = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->findOrFail($input['agent_id']);

        // 툴 목록은 서버가 강제한다. 클라이언트가 보낸 값을 그대로 쓰지 않는다.
        $tools = $this->tools->sanitize($input['allowed_tools']);

        // 배포 대상도 서버가 확인한다. 다른 프로젝트의 대상을 끼워 넣을 수 없다.
        $autoTarget = ! empty($input['auto_deploy_target_id'])
            ? AiwDeployTarget::where('project_id', $project->id)->where('enabled', true)
                ->find((int) $input['auto_deploy_target_id'])
            : null;

        $useBranch = (bool) $input['use_branch'];

        // 자동 배포는 브랜치 분리가 켜져 있어야 성립한다 — 변경이 현재
        // 브랜치에 섞이면 이 작업만 골라 올릴 수 없다.
        $autoDeploy = ! empty($input['auto_deploy']) && $useBranch && $autoTarget !== null;

        $job = AiwJob::create([
            'project_id'            => $project->id,
            'agent_id'              => $agent->id,
            'parent_job_id'         => $input['parent_job_id'] ?? null,
            'title'                 => $input['title'],
            'instruction'           => $input['instruction'],
            'mode'                  => $input['mode'],
            'model'                 => $input['model'] ?? null,
            'context_limit_tokens'  => $this->contextLimitFor($input['model'] ?? null),
            'allowed_tools'         => $tools,
            'permission_mode'       => $input['permission_mode'],
            'cost_limit_usd'        => $input['cost_limit_usd'] ?? null,
            'use_branch'            => $useBranch,
            'auto_deploy'           => $autoDeploy,
            'auto_deploy_target_id' => $autoDeploy ? $autoTarget?->id : null,
            'created_by'            => $user->id,
        ]);

        // 최초 지시문을 대화의 첫 메시지로 남긴다(화면의 첫 말풍선).
        $first = AiwJobMessage::create([
            'job_id'        => $job->id,
            'seq'           => 0,
            'role'          => 'user',
            'content'       => $job->instruction,
            'user_id'       => $user->id,
            'session_index' => 0,
            'delivered_at'  => now(),   // 지시문은 JobDispatched 로 함께 전달된다
        ]);

        // 첨부는 dispatch 전에 저장해야 담당자가 받는 payload 에 함께 실린다.
        $this->attachments->attach($first, $images, $user);

        $sent = $job->parent_job_id
            ? $this->dispatcher->dispatchFollowUp($job)
            : $this->dispatcher->dispatch($job);

        return [$job, $sent];
    }

    private function contextLimitFor(?string $model): int
    {
        $limits = (array) config('aiw.model_context_limits', []);

        return (int) ($limits[$model] ?? config('aiw.default_context_limit_tokens', 200000));
    }
}
