<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::whereNull('type')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['participants', 'lastMessage.sender'])
            ->get()
            ->sortByDesc(fn($c) => optional($c->lastMessage)->created_at)
            ->values();

        return response()->json($conversations->map(fn($c) => $this->conversationResource($c, $user)));
    }

    public function users(Request $request): JsonResponse
    {
        $user  = $request->user();
        $users = User::companyOf($user)->where('id', '!=', $user->id)->orderBy('name')->get();

        return response()->json($users->map(fn($u) => [
            'id'    => $u->id,
            'name'  => $u->name,
            'email' => $u->email,
        ]));
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless($conversation->participants->contains('id', $user->id), 403);

        $messages = Message::where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json([
            'conversation' => $this->conversationResource($conversation->load(['participants', 'lastMessage.sender']), $user),
            'messages'     => $messages->map(fn($m) => $this->messageResource($m)),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'body'        => 'nullable|string|max:2000',
        ]);

        $user     = $request->user();
        $receiver = User::findOrFail($request->receiver_id);

        $conv = Conversation::findBetween($user->id, $receiver->id);

        if (!$conv) {
            $conv = Conversation::create(['is_group' => false]);
            $conv->participants()->attach([$user->id, $receiver->id], ['last_read_at' => null]);
        }

        $message = null;
        if ($request->filled('body')) {
            $message = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $user->id,
                'body'            => $request->body,
            ]);
        }

        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json([
            'conversation_id' => $conv->id,
            'message'         => $message ? $this->messageResource($message->load('sender')) : null,
        ], 201);
    }

    /** POST /messages/group - 그룹 채팅 생성 */
    public function storeGroup(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => 'required|string|max:100',
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'body'       => 'nullable|string|max:2000',
        ]);

        $user = $request->user();

        $conv = Conversation::create([
            'is_group' => true,
            'name'     => $request->name,
        ]);

        // 본인 + 선택한 멤버
        $ids = array_values(array_unique([$user->id, ...$request->user_ids]));
        $conv->participants()->attach($ids, ['last_read_at' => null]);

        $message = null;
        if ($request->filled('body')) {
            $message = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $user->id,
                'body'            => $request->body,
            ]);
        }

        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json([
            'conversation_id' => $conv->id,
            'message'         => $message ? $this->messageResource($message->load('sender')) : null,
        ], 201);
    }

    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();

        abort_unless($conversation->participants->contains('id', $user->id), 403);

        $request->validate([
            'body' => 'required|string|max:2000',
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $request->body,
        ]);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return response()->json($this->messageResource($message->load('sender')), 201);
    }

    private function conversationResource(Conversation $c, User $currentUser): array
    {
        $other = $c->is_group ? null : $c->participants->firstWhere('id', '!=', $currentUser->id);

        return [
            'id'           => $c->id,
            'name'         => $c->name ?? ($other ? $other->name : '그룹 채팅'),
            'is_group'     => $c->is_group ?? false,
            'participants' => $c->participants->map(fn($p) => ['id' => $p->id, 'name' => $p->name]),
            'last_message' => $c->lastMessage ? $this->messageResource($c->lastMessage) : null,
        ];
    }

    private function messageResource(Message $m): array
    {
        return [
            'id'         => $m->id,
            'body'       => $m->body,
            'sender'     => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
            'created_at' => $m->created_at,
        ];
    }
}