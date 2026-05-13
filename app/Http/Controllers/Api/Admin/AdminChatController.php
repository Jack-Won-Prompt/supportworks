<?php

namespace App\Http\Controllers\Api\Admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminChatController extends Controller
{
    /**
     * GET /api/admin/chats
     * 관리자가 접근 가능한 그룹의 문의 목록
     */
    public function index(Request $request): JsonResponse
    {
        $admin  = $request->user();
        $status = $request->query('status', 'all');

        $groupIds = $admin->isSuperAdmin()
            ? \App\Models\CompanyGroup::pluck('id')
            : $admin->companyGroups()->pluck('company_groups.id');

        $query = Conversation::where('type', 'inquiry')
            ->where(function ($q) use ($groupIds, $admin) {
                // super_admin은 그룹 없는 문의도 포함
                if ($admin->isSuperAdmin()) {
                    $q->whereIn('company_group_id', $groupIds)->orWhereNull('company_group_id');
                } else {
                    $q->whereIn('company_group_id', $groupIds);
                }
            })
            ->with(['participants', 'lastMessage.sender', 'companyGroup']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $rooms = $query->orderByDesc('updated_at')->paginate(50);

        $data = $rooms->getCollection()->map(fn($c) => $this->formatRoom($c));
        $rooms->setCollection($data);

        return response()->json($rooms);
    }

    /**
     * GET /api/admin/chats/{roomId}/messages
     */
    public function messages(Request $request, int $roomId): JsonResponse
    {
        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);

        $messages = $conv->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(fn(Message $m) => $this->formatMessage($m));

        return response()->json($messages);
    }

    /**
     * POST /api/admin/chats/{roomId}/messages
     */
    public function sendMessage(Request $request, int $roomId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);

        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);

        // 발신 웹 User 결정: assignedAgent → role='admin' 웹 유저 → 첫 번째 참여자
        $senderUser = $conv->assignedAgent
            ?? \App\Models\User::where('role', 'admin')->first()
            ?? $conv->participants()->first();

        if (!$senderUser) {
            return response()->json(['message' => '메시지를 보낼 상담원 계정이 없습니다.'], 422);
        }

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $senderUser->id,
            'body'            => $request->message,
        ]);

        if ($conv->status === 'open') {
            $conv->update(['status' => 'active']);
        }

        broadcast(new MessageSent($msg->load('sender')));

        return response()->json($this->formatMessage($msg, isAdminMessage: true), 201);
    }

    /**
     * POST /api/admin/chats/{roomId}/files
     */
    public function sendFile(Request $request, int $roomId): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:20480']);

        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);

        $senderUser = $conv->assignedAgent
            ?? \App\Models\User::where('role', 'admin')->first()
            ?? $conv->participants()->first();

        if (!$senderUser) {
            return response()->json(['message' => '메시지를 보낼 상담원 계정이 없습니다.'], 422);
        }

        $file = $request->file('file');
        $path = $file->store('messages', 'public');

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $senderUser->id,
            'body'            => null,
            'file_path'       => $path,
            'file_name'       => $file->getClientOriginalName(),
            'file_size'       => $file->getSize(),
        ]);

        broadcast(new MessageSent($msg->load('sender')));

        return response()->json($this->formatMessage($msg, isAdminMessage: true), 201);
    }

    /**
     * POST /api/admin/chats/{roomId}/accept
     * 본인을 담당 admin으로 지정 (이 시점부터 MessageSent가 본인 채널로 전송됨)
     */
    public function accept(Request $request, int $roomId): JsonResponse
    {
        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);

        $conv->update([
            'assigned_admin_id' => $admin->id,
            'status'            => $conv->status === 'open' ? 'active' : $conv->status,
        ]);

        return response()->json([
            'message'           => '연결되었습니다.',
            'assigned_admin_id' => $admin->id,
            'room_id'           => $conv->id,
        ]);
    }

    /**
     * POST /api/admin/chats/{roomId}/close
     */
    public function close(Request $request, int $roomId): JsonResponse
    {
        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);
        $conv->update(['status' => 'closed']);

        return response()->json(['message' => '상담이 종료되었습니다.']);
    }

    /**
     * POST /api/admin/chats/{roomId}/reopen
     */
    public function reopen(Request $request, int $roomId): JsonResponse
    {
        $admin = $request->user();
        $conv  = Conversation::with(['participants', 'companyGroup'])->findOrFail($roomId);

        $this->authorizeConv($admin, $conv);
        $conv->update(['status' => 'active']);

        return response()->json(['message' => '상담이 재개되었습니다.']);
    }

    // ── 헬퍼 ─────────────────────────────────────────────────

    private function authorizeConv($admin, Conversation $conv): void
    {
        if ($conv->company_group_id && !$admin->canAccessGroup($conv->company_group_id)) {
            abort(403, '접근 권한이 없습니다.');
        }
    }

    private function formatRoom(Conversation $c): array
    {
        $customer = $c->participants->firstWhere('role', '!=', 'admin');

        return [
            'room_id'          => $c->id,
            'subject'          => $c->name ?? '',
            'customer_id'      => $customer?->id,
            'customer_name'    => $customer?->name ?? '(알 수 없음)',
            'customer_company' => $customer?->company,
            'assigned_agent_id' => $c->assigned_agent_id,
            'assigned_admin_id' => $c->assigned_admin_id,
            'last_message'     => $c->lastMessage?->body,
            'last_message_at'  => $c->lastMessage?->created_at?->toIso8601String(),
            'unread_count'     => 0,
            'status'           => $c->status ?? 'open',
            'company_group_id' => $c->company_group_id,
            'group_name'       => $c->companyGroup?->name,
            'created_at'       => $c->created_at->toIso8601String(),
        ];
    }

    private function formatMessage(Message $m, bool $isAdminMessage = false): array
    {
        $senderType = $isAdminMessage
            ? 'agent'
            : (($m->sender?->role === 'admin' || $m->sender?->agent_status !== null) ? 'agent' : 'customer');

        return [
            'id'          => $m->id,
            'room_id'     => $m->conversation_id,
            'sender_id'   => $m->sender_id,
            'sender_name' => $m->sender?->name,
            'sender_type' => $senderType,
            'message'     => $m->body ?? '',
            'file_url'    => $m->fileUrl(),
            'file_name'   => $m->file_name,
            'created_at'  => $m->created_at->toIso8601String(),
        ];
    }
}
