<?php

namespace App\Services\AiWork;

use App\Enums\AiWork\AiwJobStatus;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwDeployTarget;
use App\Models\AiWork\AiwErrorReport;
use App\Models\AiWork\AiwJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 판정이 끝난 오류를 작업 지시로 만든다.
 *
 * 밤에 사람 없이 도는 자리다. 그래서 "만들 수 있는가" 보다 **"만들지 말아야 할
 * 때 만들지 않는가"** 가 이 클래스의 일이다. 막는 곳이 네 군데다.
 *
 *  1. 판정·발생 횟수·시도 상한 — ErrorTriage::shouldQueue()
 *  2. 프로젝트당 동시에 도는 에러 패치 수 — 한 폴더를 여러 작업이 동시에
 *     고치면 서로의 변경을 덮는다
 *  3. 같은 지문으로 이미 도는 작업 — 상태로 막지만 한 번 더 본다
 *  4. 기능 스위치(auto_create_jobs) — 판정만 먼저 보고 나중에 켠다
 *
 * 지시문은 템플릿에서 만든다. 사람이 쓰는 것과 달리 배경을 설명해 줄 사람이
 * 없으므로, 오류 본문·발생 횟수·재현 URL 을 본문에 그대로 박아 넣는다.
 */
class ErrorPatchDispatcher
{
    public function __construct(
        private ErrorTriage $triage,
        private JobCreator $creator,
    ) {}

    /**
     * 만들 수 있는 것을 만든다.
     *
     * @return array<int, AiwJob>  이번에 만든 지시
     */
    public function run(?Project $only = null): array
    {
        if (! config('aiw.error_triage.auto_create_jobs', false)) {
            return [];
        }

        $made = [];

        $candidates = AiwErrorReport::query()
            ->where('verdict', ErrorTriage::AUTO)
            ->where('status', AiwErrorReport::STATUS_NEW)
            ->when($only, fn ($q) => $q->where('project_id', $only->id))
            // 자주 나는 것부터. 한 번 난 것과 천 번 난 것은 다른 일이다.
            ->orderByDesc('count')
            ->get();

        foreach ($candidates as $report) {
            $job = $this->dispatch($report);

            if ($job) {
                $made[] = $job;
            }
        }

        return $made;
    }

    /** 하나를 지시로 만든다. 만들지 않기로 했으면 null. */
    public function dispatch(AiwErrorReport $report): ?AiwJob
    {
        if (! $this->triage->shouldQueue($report)) {
            return null;
        }

        $project = $report->project;

        if (! $project) {
            return null;
        }

        if ($this->openPatchJobs($project->id) >= (int) config('aiw.error_triage.max_open_per_project', 1)) {
            // 한 폴더를 여러 작업이 동시에 고치면 서로의 변경을 덮는다.
            // 지금 만들지 않을 뿐이고, 다음 차례에 다시 본다.
            return null;
        }

        $agent = AiwAgent::query()
            ->whereHas('agentProjects', fn ($q) => $q->where('project_id', $project->id))
            ->first();

        if (! $agent) {
            Log::warning('AI Works: 에러 패치를 만들 담당자가 없다', ['project_id' => $project->id]);

            return null;
        }

        $creator = $this->creatorUser($project);

        if (! $creator) {
            Log::warning('AI Works: 에러 패치를 낼 사람을 찾지 못했다', ['project_id' => $project->id]);

            return null;
        }

        // 배포 대상이 없으면 배포까지 자동으로 갈 수 없다. 그래도 고치는 데까지는
        // 간다 — 사람이 아침에 결과를 보고 배포를 누르면 된다.
        $target = AiwDeployTarget::where('project_id', $project->id)
            ->deploys()->where('enabled', true)->orderBy('id')->first();

        try {
            [$job] = $this->creator->create($project, $creator, [
                'agent_id'    => $agent->id,
                'title'       => $this->title($report),
                'instruction' => $this->instruction($report, $target !== null),
                /*
                 * 단발이다. 사람이 없는 시간에 도는 작업이라 물어볼 상대가 없다.
                 * 대화형으로 두면 할 말을 마치고 답을 기다리다 수명이 다한다.
                 */
                'mode'            => 'batch',
                'model'           => null,
                'allowed_tools'   => ToolPolicy::defaults(),
                'permission_mode' => 'acceptEdits',
                // 상한 없음. 금액은 환산값이고, 중간에 끊기면 고치다 만 코드가 남는다.
                'cost_limit_usd'  => null,
                'use_branch'      => true,
                'auto_deploy'     => $target !== null,
                'auto_deploy_target_id' => $target?->id,
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 에러 패치 지시를 만들지 못했다', [
                'report_id' => $report->id,
                'error'     => $e->getMessage(),
            ]);

            return null;
        }

        DB::transaction(function () use ($job, $report) {
            $job->forceFill([
                'kind'            => AiwJob::KIND_ERROR_PATCH,
                'error_report_id' => $report->id,
            ])->save();

            $report->forceFill([
                'status'         => AiwErrorReport::STATUS_QUEUED,
                'job_id'         => $job->id,
                'patch_attempts' => $report->patch_attempts + 1,
            ])->save();
        });

        return $job;
    }

    /** 지금 이 프로젝트에서 돌고 있는 에러 패치 수. */
    private function openPatchJobs(int $projectId): int
    {
        return AiwJob::query()
            ->where('project_id', $projectId)
            ->where('kind', AiwJob::KIND_ERROR_PATCH)
            // 끝나지 않은 것 = 아직 폴더를 잡고 있을 수 있는 것.
            ->whereNotIn('status', [
                AiwJobStatus::Completed->value,
                AiwJobStatus::Failed->value,
                AiwJobStatus::Cancelled->value,
            ])
            ->count();
    }

    /**
     * 이 지시를 낸 사람으로 기록할 계정.
     *
     * 사람이 만든 지시가 아니지만 created_by 는 비울 수 없고, 알림도 이 사람에게
     * 간다. 그 프로젝트에서 작업 지시를 낼 수 있는 사람 중 하나를 쓴다.
     */
    private function creatorUser(Project $project): ?User
    {
        return User::query()
            ->where('is_aiw_operator', true)
            ->whereHas('projectMembers', fn ($q) => $q->where('project_id', $project->id))
            ->orderBy('id')
            ->first()
            ?? User::where('role', 'admin')->orderBy('id')->first();
    }

    private function title(AiwErrorReport $report): string
    {
        $where = $report->file
            ? basename((string) $report->file).($report->line ? ':'.$report->line : '')
            : '위치 미상';

        return mb_substr(sprintf('운영 오류 수정 — %s (%s)', $report->exception ?: '예외', $where), 0, 191);
    }

    /**
     * 지시문.
     *
     * 사람이 쓰는 지시와 다른 점은 둘이다 — 배경을 설명해 줄 사람이 없다는 것,
     * 그리고 끝나면 곧바로 운영에 나간다는 것. 그래서 오류 본문을 그대로 넣고,
     * 손대면 안 되는 선을 분명히 적는다.
     */
    private function instruction(AiwErrorReport $report, bool $willDeploy): string
    {
        $lines = [];

        $lines[] = '## 무슨 일이 났나';
        $lines[] = '';
        $lines[] = '운영에서 아래 예외가 **'.number_format($report->count).'번** 났습니다.'
            .' 이 지시는 사람이 쓴 것이 아니라 그 오류를 보고 자동으로 만들어졌습니다.';
        $lines[] = '';
        $lines[] = '```';
        $lines[] = ($report->exception ?: '(예외 클래스 없음)');
        $lines[] = (string) $report->message;
        $lines[] = '';
        $lines[] = '위치: '.($report->file ?: '(모름)').($report->line ? ':'.$report->line : '');

        if ($report->url) {
            $lines[] = '요청: '.$report->url;
        }

        $lines[] = '처음: '.($report->first_seen_at?->format('Y-m-d H:i') ?? '(모름)');
        $lines[] = '마지막: '.($report->last_seen_at?->format('Y-m-d H:i') ?? '(모름)');
        $lines[] = '```';

        if ($report->trace) {
            $lines[] = '';
            $lines[] = '<details><summary>스택</summary>';
            $lines[] = '';
            $lines[] = '```';
            $lines[] = mb_substr((string) $report->trace, 0, 4000);
            $lines[] = '```';
            $lines[] = '';
            $lines[] = '</details>';
        }

        $lines[] = '';
        $lines[] = '## 할 일';
        $lines[] = '';
        $lines[] = '1. **먼저 원인을 찾으세요.** 위 위치를 열어 보고, 어떤 입력이나 상태에서 이 예외가 나는지 짚어 주세요.';
        $lines[] = '2. 원인을 고칩니다. **증상만 덮지 마세요** — 예외를 try/catch 로 감싸 조용히 넘기는 것은'
            .' 고친 것이 아니라 안 보이게 한 것입니다. 그렇게 하면 같은 고장이 다음에는 더 찾기 어려운 모습으로 돌아옵니다.';
        $lines[] = '3. **재현하는 테스트를 먼저 쓰고** 고치세요. 그 테스트가 고치기 전에는 실패하고 고친 뒤에 통과해야,'
            .' 무엇을 고쳤는지가 남습니다.';
        $lines[] = '4. `php artisan test` 로 기존 테스트가 깨지지 않았는지 확인하세요.';
        $lines[] = '';
        $lines[] = '## 멈춰야 할 때';
        $lines[] = '';
        $lines[] = '아래에 해당하면 **고치지 말고, 무엇이 문제인지 적고 끝내 주세요.** 사람이 아침에 읽습니다.';
        $lines[] = '';
        $lines[] = '- 원인이 결제·정산·인증·권한 코드에 있을 때';
        $lines[] = '- 마이그레이션이나 DB 스키마를 바꿔야 할 때';
        $lines[] = '- 고치려면 설정(`.env`)이나 외부 서비스 키가 필요할 때';
        $lines[] = '- 원인이 코드가 아니라 데이터나 외부 서비스로 보일 때';
        $lines[] = '- 무엇을 고쳐야 할지 확신이 서지 않을 때';
        $lines[] = '';
        $lines[] = '**확신이 없으면 고치지 않는 편이 낫습니다.** 잘못 고친 코드는 원래 오류보다 비쌉니다.';
        $lines[] = '';
        $lines[] = '## 끝나면';
        $lines[] = '';

        if ($willDeploy) {
            $lines[] = '고치고 테스트가 통과하면 **결과가 자동으로 운영에 배포됩니다.**'
                .' 그래서 확인이 곧 마지막 방어선입니다 — 테스트를 돌리지 않은 채로 끝내지 마세요.';
        } else {
            $lines[] = '이 프로젝트에는 등록된 배포 대상이 없어 자동 배포는 하지 않습니다.'
                .' 결과를 보고 사람이 배포합니다.';
        }

        $lines[] = '';
        $lines[] = '무엇이 원인이었고 무엇을 고쳤는지 한눈에 알 수 있게 적어 주세요.';

        return implode("\n", $lines);
    }
}
