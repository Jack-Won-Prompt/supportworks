<?php

namespace App\Http\Controllers\Api\Desktop;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * POST /api/desktop/agents/me/status
     * 상담원 상태 변경 (online | away | offline)
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:online,away,offline',
        ]);

        $user = $request->user();
        $user->update(['agent_status' => $request->status]);

        return response()->json([
            'user' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'status' => $user->agent_status,
            ],
        ]);
    }
}
