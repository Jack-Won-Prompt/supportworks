<?php

namespace App\Http\Controllers\Api\AiWork;

use App\Http\Controllers\Controller;
use App\Models\AiWork\AiwErrorSource;
use App\Services\AiWork\ErrorIntake;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

/**
 * 운영 사이트가 에러를 보내오는 곳.
 *
 * 보내는 쪽은 우리 코드가 아니라 다섯 개의 다른 애플리케이션이다. 그래서 두
 * 가지를 전제하지 않는다 — 본문이 올바르다는 것, 그리고 보내는 양이 적당하다는 것.
 *
 *  - **프로젝트는 토큰으로 정한다.** 본문의 project_id 는 읽지도 않는다.
 *    믿으면 아무나 남의 프로젝트에 에러를, 나아가 작업 지시를 밀어 넣을 수 있다.
 *  - **막히더라도 200 을 돌려준다**(예외는 예외). 보내는 쪽이 에러 보고에
 *    실패해 스스로 또 에러를 내면, 그 사이트가 우리 때문에 느려진다.
 *    에러 보고는 절대로 보내는 쪽을 망가뜨려서는 안 된다.
 */
class ErrorIntakeController extends Controller
{
    public function __construct(private ErrorIntake $intake) {}

    public function store(Request $request): JsonResponse
    {
        $raw = $this->bearer($request);

        if ($raw === null) {
            return response()->json(['message' => '토큰이 필요합니다.'], 401);
        }

        $source = AiwErrorSource::findByToken($raw);

        if (! $source) {
            // 틀린 토큰과 꺼진 출처를 구분해 알려 주지 않는다.
            return response()->json(['message' => '토큰이 올바르지 않습니다.'], 401);
        }

        /*
         * 한 출처가 쏟아 붓는 것을 막는다. 묶기(fingerprint)가 저장은 한 행으로
         * 눌러 주지만, 요청 자체는 그대로 들어와 DB 를 때린다. 운영 장애 때
         * 초당 수백 건이 오는 상황이 정확히 그런 순간이다.
         */
        $key = 'aiw-error-intake:'.$source->id;

        if (RateLimiter::tooManyAttempts($key, (int) config('aiw.error_intake_per_minute', 120))) {
            // 429 로 답하되 보내는 쪽이 재시도하지 않도록 사실만 전한다.
            return response()->json([
                'accepted' => false,
                'reason'   => 'rate_limited',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        $validated = $request->validate([
            'level'     => ['nullable', 'string', 'max:16'],
            'exception' => ['nullable', 'string', 'max:255'],
            'message'   => ['nullable', 'string'],
            'file'      => ['nullable', 'string', 'max:500'],
            'line'      => ['nullable', 'integer', 'min:0'],
            'url'       => ['nullable', 'string', 'max:1000'],
            'trace'     => ['nullable', 'string'],
            'context'   => ['nullable', 'array'],
        ]);

        [$report, $isNew] = $this->intake->record($source, $validated);

        return response()->json([
            'accepted'  => true,
            'report_id' => $report->id,
            'is_new'    => $isNew,
            'count'     => $report->count,
            'status'    => $report->status,
        ], $isNew ? 201 : 200);
    }

    /** `Authorization: Bearer <토큰>`. 헤더가 없으면 null. */
    private function bearer(Request $request): ?string
    {
        $header = (string) $request->header('Authorization', '');

        if (preg_match('/^Bearer\s+(\S+)$/i', $header, $m)) {
            return $m[1];
        }

        return null;
    }
}
