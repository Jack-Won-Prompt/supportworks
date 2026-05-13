<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use Illuminate\Http\Request;

class AdminLoginLogController extends Controller
{
    public function index(Request $request)
    {
        $result = $request->query('result', 'all');
        $search = $request->query('search');

        $query = AdminLoginLog::with('admin')
            ->orderByDesc('created_at');

        if ($result !== 'all') {
            $query->where('result', $result);
        }
        if ($search) {
            $query->where('login_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
        }

        $logs = $query->paginate(30)->withQueryString();

        $stats = [
            'total'   => AdminLoginLog::count(),
            'success' => AdminLoginLog::where('result', 'success')->count(),
            'fail'    => AdminLoginLog::where('result', 'fail')->count(),
            'locked'  => AdminLoginLog::where('result', 'locked')->count(),
            'today'   => AdminLoginLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.login-logs.index', compact('logs', 'result', 'search', 'stats'));
    }
}
