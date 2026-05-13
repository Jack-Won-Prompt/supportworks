<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemMaintenanceController extends Controller
{
    public function index()
    {
        $setting = SystemSetting::current();
        return view('admin.system-maintenance.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'maintenance_mode'    => 'required|boolean',
            'maintenance_message' => 'nullable|string|max:2000',
        ]);

        $setting = SystemSetting::current();
        $setting->update($data);

        return back()->with('success', $data['maintenance_mode']
            ? '유지보수 모드가 켜졌습니다. 사용자 영역 접근이 차단됩니다.'
            : '유지보수 모드가 꺼졌습니다.');
    }
}
