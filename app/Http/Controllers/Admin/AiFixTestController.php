<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * AI Fix 전체 사이클(에러→알림→분석→승인→Apply→배포승인→Deploy) E2E 검증용
 * 강제 에러 트리거. 검증 완료 후 이 컨트롤러 + 라우트는 같이 제거할 것.
 *
 * 동선: GET /admin/ai-fix/test-trigger (admin.web 미들웨어)
 *   → 의도적 TypeError throw
 *   → bootstrap/app.php 의 withExceptions report 가 SystemErrorLog::record() 호출
 *   → notifyAdmins(이메일+FCM) + maybeTriggerAiFix(AnalyzeSystemErrorJob dispatch)
 *   → AiFixOrchestrator 가 AiFixJob 생성·분석·AWAITING_APPROVAL 진입
 */
class AiFixTestController extends Controller
{
    public function trigger(): never
    {
        throw new \TypeError('AI Fix E2E test: intentional null reference for trigger');
    }
}
