<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminInvitationMail;
use App\Models\AdminInvitation;
use App\Models\AdminUser;
use App\Models\AdminCompanyGroupAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminInviteController extends Controller
{
    // POST /api/admin/invitations
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email'             => 'required|email',
            'name'              => 'required|string|max:50',
            'role'              => 'required|in:admin,operator,support_agent',
            'company_group_ids' => 'nullable|array',
            'company_group_ids.*' => 'integer|exists:company_groups,id',
        ]);

        $inviter = $request->user();

        // 권한 체크: super_admin만 admin 초대 가능
        if ($request->role === 'admin' && !$inviter->isSuperAdmin()) {
            return response()->json(['message' => 'admin 권한 계정은 super_admin만 초대할 수 있습니다.'], 403);
        }

        // 이미 존재하는 계정 확인
        if (AdminUser::where('email', $request->email)->exists()) {
            return response()->json(['message' => '이미 등록된 이메일입니다.'], 422);
        }

        // 유효한 초대가 이미 있으면 무효화
        AdminInvitation::where('email', $request->email)
            ->where('status', 'invited')
            ->update(['status' => 'disabled']);

        ['invitation' => $invitation, 'raw_token' => $rawToken] = AdminInvitation::createInvitation($inviter, [
            'email'             => $request->email,
            'name'              => $request->name,
            'role'              => $request->role,
            'company_group_ids' => $request->company_group_ids ?? [],
        ]);

        Mail::to($request->email)->send(new AdminInvitationMail($invitation, $rawToken));

        return response()->json(['message' => '초대 이메일이 발송되었습니다.', 'invitation_id' => $invitation->id], 201);
    }

    // GET /api/admin/invitations
    public function index(Request $request): JsonResponse
    {
        $invitations = AdminInvitation::with('invitedBy:id,name')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($invitations);
    }

    // DELETE /api/admin/invitations/{id}
    public function revoke(int $id): JsonResponse
    {
        $inv = AdminInvitation::where('id', $id)->where('status', 'invited')->firstOrFail();
        $inv->update(['status' => 'disabled']);

        return response()->json(['message' => '초대가 취소되었습니다.']);
    }
}
