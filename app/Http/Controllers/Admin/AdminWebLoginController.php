<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminLoginLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\AdminUser;

class AdminWebLoginController extends Controller
{
    public function showLogin()
    {
        if (auth('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = AdminUser::where('login_id', $request->login_id)->first();

        if (!$admin) {
            AdminLoginLog::create([
                'admin_user_id' => null,
                'login_id'      => $request->login_id,
                'ip_address'    => $request->ip(),
                'result'        => 'fail',
            ]);
            return back()->withErrors(['login_id' => '아이디 또는 비밀번호가 올바르지 않습니다.'])->withInput();
        }

        if ($admin->isLocked()) {
            AdminLoginLog::create([
                'admin_user_id' => $admin->id,
                'login_id'      => $request->login_id,
                'ip_address'    => $request->ip(),
                'result'        => 'locked',
            ]);
            $until = $admin->locked_until?->format('H:i');
            return back()->withErrors(['login_id' => "계정이 잠겨 있습니다. {$until}까지 로그인할 수 없습니다."])->withInput();
        }

        if ($admin->status !== 'active') {
            return back()->withErrors(['login_id' => '비활성 계정입니다. 관리자에게 문의하세요.'])->withInput();
        }

        if (!Hash::check($request->password, $admin->password)) {
            $admin->increment('login_fail_count');
            if ($admin->login_fail_count >= 5) {
                $admin->update([
                    'status'       => 'locked',
                    'locked_until' => now()->addMinutes(30),
                ]);
            }
            AdminLoginLog::create([
                'admin_user_id' => $admin->id,
                'login_id'      => $request->login_id,
                'ip_address'    => $request->ip(),
                'result'        => 'fail',
            ]);
            $remaining = 5 - $admin->login_fail_count;
            $msg = $remaining > 0
                ? "비밀번호가 올바르지 않습니다. ({$remaining}회 남음)"
                : '5회 실패로 계정이 30분간 잠겼습니다.';
            return back()->withErrors(['login_id' => $msg])->withInput();
        }

        $admin->update([
            'login_fail_count' => 0,
            'last_login_at'    => now(),
        ]);
        AdminLoginLog::create([
            'admin_user_id' => $admin->id,
            'login_id'      => $admin->login_id,
            'ip_address'    => $request->ip(),
            'result'        => 'success',
        ]);

        auth('admin')->login($admin);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        auth('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }
}
