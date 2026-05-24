<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\CompanyGroup;
use App\Services\AnnouncementDispatchService;
use Illuminate\Http\Request;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        $announcements   = Announcement::with('creator')->latest()->paginate(20);
        $companyGroups   = CompanyGroup::orderBy('name')->get(['id', 'name', 'uses_withworks']);
        return view('admin.announcements.index', compact('announcements', 'companyGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'                      => 'required|string|max:200',
            'body'                       => 'required|string',
            'type'                       => 'required|in:info,warning,maintenance,update',
            'is_active'                  => 'sometimes|boolean',
            'starts_at'                  => 'nullable|date',
            'ends_at'                    => 'nullable|date|after_or_equal:starts_at',
            'target_type'                => 'nullable|in:all,withworks,companies',
            'target_company_group_ids'   => 'nullable|array',
            'target_company_group_ids.*' => 'integer',
            'send_email'                 => 'sometimes|boolean',
        ]);

        $data['is_active']         = $request->boolean('is_active', true);
        $data['send_email']        = $request->boolean('send_email', false);
        $data['target_type']       = $data['target_type'] ?? 'all';
        $data['created_by']        = auth('admin')->user()->id;
        // companies 가 아닐 때는 회사 ID 배열 비움
        if ($data['target_type'] !== 'companies') {
            $data['target_company_group_ids'] = null;
        }

        $announcement = Announcement::create($data);

        // 메일박스 적재 + 옵션 SMTP 발송 — HTTP 응답 후 백그라운드 처리
        app()->terminating(function () use ($announcement) {
            try {
                app(AnnouncementDispatchService::class)->dispatch($announcement);
            } catch (\Throwable $e) {
                \App\Models\SystemErrorLog::record($e, 'warning');
            }
        });

        $msg = '공지사항이 등록되었습니다.';
        if ($announcement->send_email)             $msg .= ' (이메일 발송이 백그라운드에서 진행됩니다)';
        elseif ($announcement->target_type !== 'all') $msg .= ' (대상 사용자의 메일함에 발송됩니다)';
        return back()->with('success', $msg);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'                      => 'required|string|max:200',
            'body'                       => 'required|string',
            'type'                       => 'required|in:info,warning,maintenance,update',
            'is_active'                  => 'sometimes|boolean',
            'starts_at'                  => 'nullable|date',
            'ends_at'                    => 'nullable|date|after_or_equal:starts_at',
            'target_type'                => 'nullable|in:all,withworks,companies',
            'target_company_group_ids'   => 'nullable|array',
            'target_company_group_ids.*' => 'integer',
            'send_email'                 => 'sometimes|boolean',
            'resend'                     => 'sometimes|boolean',
        ]);

        $data['is_active']   = $request->boolean('is_active', true);
        $data['send_email']  = $request->boolean('send_email', false);
        $data['target_type'] = $data['target_type'] ?? 'all';
        if ($data['target_type'] !== 'companies') {
            $data['target_company_group_ids'] = null;
        }
        $resend = $request->boolean('resend', false);
        unset($data['resend']);

        $announcement->update($data);

        // 재발송 옵션 — 명시적 체크 시 백그라운드 발송
        if ($resend) {
            app()->terminating(function () use ($announcement) {
                try {
                    app(AnnouncementDispatchService::class)->dispatch($announcement);
                } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            });
            return back()->with('success', '공지사항이 수정되었고 재발송이 진행됩니다.');
        }

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
