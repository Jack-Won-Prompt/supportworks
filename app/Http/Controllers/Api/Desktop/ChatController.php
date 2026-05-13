<?php

namespace App\Http\Controllers\Api\Desktop;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    /**
     * GET /api/desktop/chats
     * 상담원이 참여하거나 그룹에 열려있는 문의 목록 반환
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Conversation::where('type', 'inquiry');

        if ($user->company_group_id) {
            // 그룹 소속 상담원: 내가 참여 중이거나 미배정(같은 그룹) 상담 모두 표시
            $query->where(function ($q) use ($user) {
                $q->whereHas('participants', fn($p) => $p->where('user_id', $user->id))
                  ->orWhere(function ($q2) use ($user) {
                      $q2->where('company_group_id', $user->company_group_id)
                         ->whereNull('assigned_agent_id')
                         ->whereIn('status', ['open']);
                  });
            });
        } else {
            // 그룹 미지정 상담원: 기존 방식(본인 참여 목록)
            $query->whereHas('participants', fn($p) => $p->where('user_id', $user->id));
        }

        $conversations = $query
            ->with(['participants', 'lastMessage.sender', 'assignedAgent'])
            ->get()
            ->map(fn(Conversation $c) => $this->formatRoom($c, $user))
            ->sortByDesc('last_message_at')
            ->values();

        return response()->json($conversations);
    }

    /**
     * POST /api/desktop/chats/{room_id}/assign
     * 미배정 상담을 현재 상담원이 수락
     */
    public function assign(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::findOrFail($roomId);

        if ($conv->type !== 'inquiry') {
            return response()->json(['message' => '문의 상담이 아닙니다.'], 400);
        }

        if ($conv->assigned_agent_id && $conv->assigned_agent_id !== $user->id) {
            return response()->json(['message' => '이미 다른 상담원이 배정되었습니다.'], 409);
        }

        $conv->update(['assigned_agent_id' => $user->id, 'status' => 'active']);

        if (!$conv->participants->contains('id', $user->id)) {
            $conv->participants()->attach($user->id, ['last_read_at' => now()]);
        }

        return response()->json(['message' => '상담이 배정되었습니다.', 'room' => $this->formatRoom($conv->fresh(['participants', 'lastMessage.sender', 'assignedAgent']), $user)]);
    }

    /**
     * POST /api/desktop/chats/{room_id}/transfer
     * 다른 상담원으로 이관
     */
    public function transfer(Request $request, int $roomId): JsonResponse
    {
        $request->validate(['agent_id' => 'required|integer|exists:users,id']);

        $user = $request->user();
        $conv = Conversation::findOrFail($roomId);

        if ($conv->assigned_agent_id !== $user->id) {
            return response()->json(['message' => '본인이 담당한 상담만 이관할 수 있습니다.'], 403);
        }

        $newAgent = User::findOrFail($request->agent_id);

        $conv->update(['assigned_agent_id' => $newAgent->id]);

        if (!$conv->participants->contains('id', $newAgent->id)) {
            $conv->participants()->attach($newAgent->id, ['last_read_at' => now()]);
        }

        return response()->json(['message' => "{$newAgent->name}님에게 이관되었습니다."]);
    }

    /**
     * POST /api/desktop/chats/{room_id}/close
     * 상담 종료
     */
    public function close(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::findOrFail($roomId);

        if (!$conv->participants->contains('id', $user->id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $conv->update(['status' => 'closed']);

        return response()->json(['message' => '상담이 종료되었습니다.']);
    }

    /**
     * GET /api/desktop/chats/{room_id}/messages
     */
    public function messages(Request $request, int $roomId): JsonResponse
    {
        $user = $request->user();
        $conv = Conversation::with('participants')->findOrFail($roomId);

        if (!$conv->participants->contains('id', $user->id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $messages = $conv->messages()
            ->with('sender')
            ->orderBy('created_at')
            ->get()
            ->map(fn(Message $m) => $this->formatMessage($m, $user));

        return response()->json($messages);
    }

    /**
     * POST /api/desktop/chats/{room_id}/messages
     */
    public function sendMessage(Request $request, int $roomId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:5000']);

        $user = $request->user();
        $conv = Conversation::with('participants')->findOrFail($roomId);

        if (!$conv->participants->contains('id', $user->id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'body'            => $request->message,
        ]);

        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);
        broadcast(new MessageSent($msg));

        return response()->json($this->formatMessage($msg->load('sender'), $user), 201);
    }

    /**
     * POST /api/desktop/chats/{room_id}/files
     */
    public function sendFile(Request $request, int $roomId): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:20480']);

        $user = $request->user();
        $conv = Conversation::with('participants')->findOrFail($roomId);

        if (!$conv->participants->contains('id', $user->id)) {
            return response()->json(['message' => '접근 권한이 없습니다.'], 403);
        }

        $file     = $request->file('file');
        $path     = $file->store('messages', 'public');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'body'            => null,
            'file_path'       => $path,
            'file_name'       => $fileName,
            'file_size'       => $fileSize,
        ]);

        broadcast(new MessageSent($msg));

        return response()->json($this->formatMessage($msg->load('sender'), $user), 201);
    }

    // ── 포맷 헬퍼 ────────────────────────────────────────────

    private function formatRoom(Conversation $c, User $agent): array
    {
        $customer = $c->participants->firstWhere('id', '!=', $agent->id);
        $last     = $c->lastMessage;
        $unread   = $c->unreadCount($agent->id);

        return [
            'room_id'              => $c->id,
            'subject'              => $c->name ?? '',
            'customer_id'          => $customer?->id,
            'customer_name'        => $customer?->name ?? '(알 수 없음)',
            'customer_company'     => $customer?->company,
            'assigned_agent_id'    => $c->assigned_agent_id,
            'assigned_agent_name'  => $c->assignedAgent?->name,
            'last_message'         => $last?->body,
            'last_message_at'      => $last?->created_at?->toIso8601String(),
            'unread_count'         => $unread,
            'status'               => $c->status ?? 'open',
            'company_group_id'     => $c->company_group_id,
        ];
    }

    private function formatMessage(Message $m, User $agent): array
    {
        return [
            'id'          => $m->id,
            'room_id'     => $m->conversation_id,
            'sender_type' => $m->sender_id === $agent->id ? 'agent' : 'customer',
            'sender_name' => $m->sender?->name,
            'message'     => $m->body ?? '',
            'file_url'    => $m->fileUrl(),
            'file_name'   => $m->file_name,
            'created_at'  => $m->created_at->toIso8601String(),
            'is_read'     => true,
        ];
    }
}
