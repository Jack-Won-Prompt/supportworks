<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPageLog;
use Illuminate\Http\Request;

class AdminUserPageLogController extends Controller
{
    public function index(Request $request)
    {
        $userId   = $request->query('user_id');
        $screen   = $request->query('screen');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = UserPageLog::with('user')->orderByDesc('created_at');

        if ($userId) {
            $query->where('user_id', $userId);
        }
        if ($screen) {
            $query->where('screen_name', $screen);
        }
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $logs    = $query->paginate(50)->withQueryString();
        $users   = User::orderBy('name')->get(['id', 'name']);
        $screens = collect(UserPageLog::SCREEN_MAP)->unique()->sort()->values();

        $stats = [
            'total'        => UserPageLog::count(),
            'today'        => UserPageLog::whereDate('created_at', today())->count(),
            'active_users' => UserPageLog::whereDate('created_at', today())->distinct('user_id')->count('user_id'),
        ];

        return view('admin.user-page-logs.index', compact(
            'logs', 'users', 'screens', 'stats',
            'userId', 'screen', 'dateFrom', 'dateTo'
        ));
    }
}
