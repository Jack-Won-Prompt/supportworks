<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\NewInquiryEvent;
use App\Http\Controllers\Controller;
use App\Models\CompanyGroup;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserSearchController extends Controller
{
    /**
     * GET /api/admin/users/search?q=keyword
     * 관리자 담당 그룹 기준으로 사용자 검색
     */
    public function search(Request $request): JsonResponse
    {
        $admin = $request->user();
        $q = trim($request->query('q', ''));

        $groupIds = $admin->isSuperAdmin()
            ? CompanyGroup::pluck('id')
            : $admin->companyGroups()->pluck('company_groups.id');

        $query = User::whereIn('company_group_id', $groupIds)
            ->with('companyGroup');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name',    'like', "%{$q}%")
                    ->orWhere('email',   'like', "%{$q}%")
                    ->orWhere('phone',   'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')->limit(50)->get();

        return response()->json($users->map(fn(User $u) => [
            'id'               => $u->id,
            'name'             => $u->name,
            'email'            => $u->email,
            'phone'            => $u->phone,
            'company'          => $u->company,
            'company_group_id' => $u->company_group_id,
            'group_name'       => $u->companyGroup?->name,
        ])->values());
    }

    /**
     * GET /api/admin/users/{userId}/conversations
     * 사용자의 문의 이력
     */
    public function conversations(Request $request, int $userId): JsonResponse
    {
        $admin = $request->user();
        $user  = User::findOrFail($userId);

        if ($user->company_group_id && !$admin->canAccessGroup($user->company_group_id)) {
            abort(403, '접근 권한이 없습니다.');
        }

        $convs = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->where('type', 'inquiry')
            ->with(['lastMessage', 'assignedAgent'])
            ->orderByDesc('updated_at')
            ->get();

        return response()->json($convs->map(fn(Conversation $c) => [
            'room_id'          => $c->id,
            'subject'          => $c->name ?? '',
            'status'           => $c->status ?? 'open',
            'last_message'     => $c->lastMessage?->body,
            'last_message_at'  => $c->lastMessage?->created_at?->toIso8601String(),
            'assigned_agent'   => $c->assignedAgent?->name,
            'created_at'       => $c->created_at->toIso8601String(),
        ])->values());
    }

    /**
     * POST /api/admin/users/{userId}/start-chat
     * 사용자와 대화 시작 (기존 open/active 대화 있으면 재사용)
     */
    public function startChat(Request $request, int $userId): JsonResponse
    {
        $admin = $request->user();
        $user  = User::with('companyGroup')->findOrFail($userId);

        if ($user->company_group_id && !$admin->canAccessGroup($user->company_group_id)) {
            abort(403, '접근 권한이 없습니다.');
        }

        // 기존 진행 중인 대화 재사용
        $existing = Conversation::whereHas('participants', fn($q) => $q->where('user_id', $userId))
            ->whereIn('status', ['open', 'active'])
            ->where('type', 'inquiry')
            ->latest('updated_at')
            ->first();

        if ($existing) {
            return response()->json(['room_id' => $existing->id, 'created' => false]);
        }

        // 새 대화 생성
        $conv = Conversation::create([
            'name'              => "{$user->name} 문의",
            'type'              => 'inquiry',
            'status'            => 'active',
            'company_group_id'  => $user->company_group_id,
            'assigned_admin_id' => $admin->id,
        ]);

        $conv->participants()->attach($user->id, ['last_read_at' => now()]);

        broadcast(new NewInquiryEvent($conv, $user->name, "{$user->name} 문의", ''));

        return response()->json(['room_id' => $conv->id, 'created' => true], 201);
    }
}
