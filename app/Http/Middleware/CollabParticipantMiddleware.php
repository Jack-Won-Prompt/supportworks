<?php

namespace App\Http\Middleware;

use App\Models\CollabSession;
use App\Support\CollabContext;
use Closure;
use Illuminate\Http\Request;

class CollabParticipantMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        // GET 요청(페이지 조회)에서만 협업 컨텍스트 활성화
        // POST/PATCH/DELETE는 정상적인 권한 체크 유지
        if ($request->isMethod('GET')) {
            $user = auth()->user();
            if ($user) {
                $hostId = CollabSession::where('participant_id', $user->id)
                    ->where('status', 'active')
                    ->value('initiator_id');

                CollabContext::set($hostId ? (int) $hostId : null);
            }
        }

        return $next($request);
    }
}
