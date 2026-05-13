<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AdminLoginLog;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Models\UserPageLog;
use Illuminate\Http\Request;

class AdminLogsController extends Controller
{
    private const TABS = ['activity', 'login', 'user-login', 'page'];

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'activity');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'activity';
        }

        return match ($tab) {
            'activity'   => $this->activityTab($request, $tab),
            'login'      => $this->loginTab($request, $tab),
            'user-login' => $this->userLoginTab($request, $tab),
            'page'       => $this->pageTab($request, $tab),
        };
    }

    private function activityTab(Request $request, string $tab)
    {
        $query = ActivityLog::with('user')->latest('created_at');

        if ($request->filled('user_id'))      $query->where('user_id', $request->user_id);
        if ($request->filled('action'))       $query->where('action', $request->action);
        if ($request->filled('subject_type')) $query->where('subject_type', $request->subject_type);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);
        if ($request->filled('search'))       $query->where('subject_label', 'like', '%' . $request->search . '%');

        $logs  = $query->paginate(50)->withQueryString();
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('admin.logs.index', compact('tab', 'logs', 'users'));
    }

    private function loginTab(Request $request, string $tab)
    {
        $result = $request->query('result', 'all');
        $search = $request->query('search');

        $query = AdminLoginLog::with('admin')->orderByDesc('created_at');
        if ($result !== 'all') $query->where('result', $result);
        if ($search) {
            $query->where('login_id', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
        }

        $logs  = $query->paginate(30)->withQueryString();
        $stats = [
            'total'   => AdminLoginLog::count(),
            'success' => AdminLoginLog::where('result', 'success')->count(),
            'fail'    => AdminLoginLog::where('result', 'fail')->count(),
            'locked'  => AdminLoginLog::where('result', 'locked')->count(),
            'today'   => AdminLoginLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.logs.index', compact('tab', 'logs', 'result', 'search', 'stats'));
    }

    private function userLoginTab(Request $request, string $tab)
    {
        $result = $request->query('result', 'all');
        $search = $request->query('search');

        $query = UserLoginLog::with('user')->orderByDesc('created_at');
        if ($result !== 'all') $query->where('result', $result);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs  = $query->paginate(30)->withQueryString();
        $stats = [
            'total'   => UserLoginLog::count(),
            'success' => UserLoginLog::where('result', 'success')->count(),
            'fail'    => UserLoginLog::where('result', 'fail')->count(),
            'today'   => UserLoginLog::whereDate('created_at', today())->count(),
        ];

        return view('admin.logs.index', compact('tab', 'logs', 'result', 'search', 'stats'));
    }

    private function pageTab(Request $request, string $tab)
    {
        $userId   = $request->query('user_id');
        $screen   = $request->query('screen');
        $dateFrom = $request->query('date_from');
        $dateTo   = $request->query('date_to');

        $query = UserPageLog::with('user')->orderByDesc('created_at');
        if ($userId)   $query->where('user_id', $userId);
        if ($screen)   $query->where('screen_name', $screen);
        if ($dateFrom) $query->whereDate('created_at', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('created_at', '<=', $dateTo);

        $logs    = $query->paginate(50)->withQueryString();
        $users   = User::orderBy('name')->get(['id', 'name']);
        $screens = collect(UserPageLog::SCREEN_MAP)->unique()->sort()->values();
        $stats   = [
            'total'        => UserPageLog::count(),
            'today'        => UserPageLog::whereDate('created_at', today())->count(),
            'active_users' => UserPageLog::whereDate('created_at', today())->distinct('user_id')->count('user_id'),
        ];

        return view('admin.logs.index', compact(
            'tab', 'logs', 'users', 'screens', 'stats',
            'userId', 'screen', 'dateFrom', 'dateTo'
        ));
    }
}
