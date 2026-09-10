<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AiwAgentMiddleware;
use App\Models\AiWork\AiwAgent;
use App\Models\AiWork\AiwJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 데몬 API 공통 기반.
 *
 * 모든 job 엔드포인트는 job.agent_id 가 호출한 에이전트와 같은지 확인하고,
 * 아니면 403 이 아니라 404 를 낸다 — 다른 에이전트의 job 존재 여부를 노출하지 않는다.
 */
abstract class AgentApiController extends Controller
{
    protected function agent(Request $request): AiwAgent
    {
        $agent = $request->attributes->get(AiwAgentMiddleware::ATTRIBUTE);

        if (! $agent instanceof AiwAgent) {
            // 미들웨어를 거치지 않고 도달했다는 뜻. 라우트 정의 실수다.
            abort(401, '에이전트 인증 정보가 없습니다.');
        }

        return $agent;
    }

    /** 이 에이전트의 job 만 반환한다. 남의 job 이면 404. */
    protected function ownedJob(Request $request, AiwJob $job): AiwJob
    {
        if ((int) $job->agent_id !== (int) $this->agent($request)->id) {
            throw new NotFoundHttpException();
        }

        return $job;
    }

    /**
     * 브로드캐스트를 발행하되, 실패해도 요청을 죽이지 않는다.
     *
     * AI Works 이벤트는 전부 ShouldBroadcastNow 라 요청 스레드에서 Reverb 로
     * HTTP 를 쏜다. Reverb 가 죽어 있거나 인증서가 틀리면 예외가 그대로 올라와
     * 데몬의 보고(logs/status/complete)가 통째로 500 이 된다. 그러면 Reverb
     * 장애가 작업 실행 실패로 번진다.
     *
     * 데이터는 이미 저장된 뒤이므로, 브로드캐스트 실패는 "브라우저가 실시간
     * 갱신을 한 번 놓치는 것"으로 격리한다. 화면은 새로고침이나 /inbox 폴백으로
     * 복구된다.
     */
    protected function emit(object $event): void
    {
        try {
            event($event);
        } catch (\Throwable $e) {
            Log::warning('AI Works: 브로드캐스트 실패(데이터는 저장됨)', [
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 데몬이 Reverb 이벤트를 놓쳐도 다음 API 호출에서 중단을 인지하도록
     * 모든 상태성 응답에 함께 싣는 플래그.
     */
    protected function controlFlags(AiwJob $job): array
    {
        return [
            'cost_over_limit'  => $job->isOverCostLimit(),
            'cancel_requested' => $job->status->isTerminal(),
            'status'           => $job->status->value,
        ];
    }
}
