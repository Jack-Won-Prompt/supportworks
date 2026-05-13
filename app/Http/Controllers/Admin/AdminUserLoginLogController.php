<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserLoginLog;
use Illuminate\Http\Request;

class AdminUserLoginLogController extends Controller
{
    public function index(Request $request)
    {
        $result = $request->query('result', 'all');
        $search = $request->query('search');

        $query = UserLoginLog::with('user')
            ->orderByDesc('created_at');

        if ($result !== 'all') {
            $query->where('result', $result);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(30)->withQueryString();

        $stats = [
            'total'   => UserLoginLog::count(),
            'success' => UserLoginLog::where('result', 'success')->count(),
            'fail'    => UserLoginLog::where('result', 'fail')->count(),
            'today'   => UserLoginLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.user-login-logs.index', compact('logs', 'result', 'search', 'stats'));
    }
}
