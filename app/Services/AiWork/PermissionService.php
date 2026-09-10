<?php

namespace App\Services\AiWork;

use App\Events\AiWork\PermissionDecided;
use App\Events\AiWork\PermissionRequested;
use App\Models\AiWork\AiwJob;
use App\Models\AiWork\AiwPermissionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * 툴 실행 승인 요청의 생성과 결정.
 *
 * 결정은 되돌릴 수 없다 — 이미 결정된 요청에 다시 결정을 시도해도 첫 결정이
 * 유지된다. 데몬은 결정을 한 번만 소비하므로, 나중 클릭이 이기면 사람이 본
 * 화면과 실제 동작이 어긋난다.
 */
class PermissionService
{
    /**
     * request_key 기준 idempotent 생성.
     * 이미 있으면 그대로 돌려준다(재요청이 레코드를 늘리지 않는다).
     */
    public function request(AiwJob $job, string $requestKey, string $tool, array $input): AiwPermissionRequest
    {
        $permission = AiwPermissionRequest::firstOrCreate(
            ['request_key' => $requestKey],
            [
                'job_id'     => $job->id,
                'tool_name'  => $tool,
                'tool_input' => $input,
                'status'     => 'pending',
                'created_at' => now(),
            ],
        );

        if ($permission->wasRecentlyCreated) {
            $this->emit(new PermissionRequested($permission), $job->id);
        }

        return $permission;
    }

    /**
     * 사용자의 허용/거부.
     *
     * @return bool 이 호출이 실제로 결정을 바꿨는지(false = 이미 결정됨)
     */
    public function decide(AiwPermissionRequest $request, User $user, bool $allow, ?string $reason = null): bool
    {
        if (! $request->isPending()) {
            return false;
        }

        $request->forceFill([
            'status'      => $allow ? 'allowed' : 'denied',
            'decided_by'  => $user->id,
            'decided_at'  => now(),
            'deny_reason' => $allow ? null : ($reason ?: '사용자가 거부했습니다.'),
        ])->save();

        $this->emit(new PermissionDecided($request, $request->job->agent_id), $request->job_id);

        return true;
    }

    /**
     * 방치된 승인 요청 만료.
     *
     * 이게 없으면 사용자가 자리를 비운 사이 데몬이 무한정 대기한다.
     * 만료는 거부로 취급하고 데몬에 timeout 을 알린다.
     *
     * @return int 만료 처리한 건수
     */
    public function expireStale(): int
    {
        $cutoff = now()->subMinutes((int) config('aiw.permission_timeout_min', 30));

        $stale = AiwPermissionRequest::query()
            ->pending()
            ->where('created_at', '<=', $cutoff)
            ->with('job:id,agent_id')
            ->get();

        foreach ($stale as $request) {
            $request->forceFill([
                'status'      => 'expired',
                'decided_at'  => now(),
                'deny_reason' => 'timeout',
            ])->save();

            if ($request->job) {
                $this->emit(new PermissionDecided($request, $request->job->agent_id), $request->job_id);
            }
        }

        return $stale->count();
    }

    private function emit(object $event, ?int $jobId): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            // 결정은 이미 저장됐다. 데몬은 /inbox 폴백으로 결정을 받아 간다.
            Log::warning('AI Works: 승인 브로드캐스트 실패(결정은 저장됨)', [
                'job_id' => $jobId,
                'event'  => $event::class,
                'error'  => $e->getMessage(),
            ]);
        }
    }
}
