<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * AI Fix 전체 사이클(에러 → AI 분석 → AI 코드 수정 → PR → 머지 → 배포) E2E
 * 운영 검증용 임시 트리거. AI 가 이 컨트롤러를 fix 한 후 운영 master 에 머지 +
 * deploy.sh 로 배포되는 흐름까지 진짜 사이클로 검증.
 *
 * 검증 끝나면 이 컨트롤러 + 라우트 같이 제거.
 */
class AiFixTestController extends Controller
{
    public function trigger(): void
    {
        throw new \TypeError('AI Fix E2E full-cycle test: intentional null reference for trigger');
    }
}
