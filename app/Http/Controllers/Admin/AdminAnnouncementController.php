<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::with('creator')->latest()->paginate(20);
        return view('admin.announcements.index', compact('announcements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'body'       => 'required|string',
            'type'       => 'required|in:info,warning,maintenance,update',
            'is_active'  => 'sometimes|boolean',
            'starts_at'  => 'nullable|date',
            'ends_at'    => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['is_active']  = $request->boolean('is_active', true);
        $data['created_by'] = auth('admin')->user()->id;

        Announcement::create($data);

        return back()->with('success', '공지사항이 등록되었습니다.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'body'       => 'required|string',
            'type'       => 'required|in:info,warning,maintenance,update',
            'is_active'  => 'sometimes|boolean',
            'starts_at'  => 'nullable|date',
            'ends_at'    => 'nullable|date|after_or_equal:starts_at',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $announcement->update($data);

        return back()->with('success', '공지사항이 수정되었습니다.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', '공지사항이 삭제되었습니다.');
    }

    public function toggleActive(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);
        return back()->with('success', $announcement->is_active ? '공지사항이 활성화되었습니다.' : '공지사항이 비활성화되었습니다.');
    }
}
