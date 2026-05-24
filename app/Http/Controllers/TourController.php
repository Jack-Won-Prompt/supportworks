<?php

namespace App\Http\Controllers;

use App\Models\UserTourVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TourController extends Controller
{
    /**
     * 투어 visited 기록 — 처음 본 시각 저장 (멱등).
     */
    public function markVisited(Request $request, string $key): JsonResponse
    {
        $request->validate(['_' => 'nullable']);   // 단순 sanity

        // tour_key 화이트리스트
        $allowed = ['dashboard'];
        abort_unless(in_array($key, $allowed, true), 422, 'Unknown tour key.');

        $userId = auth()->id();
        abort_unless($userId, 401);

        UserTourVisit::markVisited((int) $userId, $key);
        return response()->json(['ok' => true]);
    }
}
