<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InquiryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $inquiries = Conversation::where('type', 'inquiry')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['lastMessage.sender', 'participants'])
            ->latest()
            ->get();

        return response()->json($inquiries->map(fn($c) => [
            'id'           => $c->id,
            'name'         => $c->name ?? '문의',
            'status'       => $c->status ?? 'open',
            'last_message' => $c->lastMessage ? [
                'body'       => $c->lastMessage->body,
                'created_at' => $c->lastMessage->created_at,
            ] : null,
            'created_at'   => $c->created_at,
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'content' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $conv = Conversation::create([
            'type'   => 'inquiry',
            'name'   => $request->title,
            'status' => 'open',
        ]);

        $conv->participants()->attach($user->id, ['last_read_at' => now()]);

        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'body'            => $request->content,
        ]);

        return response()->json(['id' => $conv->id, 'message' => '문의가 접수되었습니다.'], 201);
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
            'id'       => $conversation->id,
            'name'     => $conversation->name,
            'status'   => $conversation->status ?? 'open',
            'messages' => $messages->map(fn($m) => [
                'id'         => $m->id,
                'body'       => $m->body,
                'sender'     => $m->sender ? ['id' => $m->sender->id, 'name' => $m->sender->name] : null,
                'created_at' => $m->created_at,
            ]),
        ]);
    }

    public function reply(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->participants->contains('id', $user->id), 403);

        $request->validate(['content' => 'required|string|max:2000']);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $request->content,
        ]);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $message->load('sender');

        return response()->json([
            'id'         => $message->id,
            'body'       => $message->body,
            'sender'     => ['id' => $message->sender->id, 'name' => $message->sender->name],
            'created_at' => $message->created_at,
        ], 201);
    }

    public function close(Request $request, Conversation $conversation): JsonResponse
    {
        $user = $request->user();
        abort_unless($conversation->participants->contains('id', $user->id), 403);
        $conversation->update(['status' => 'closed']);
        return response()->json(['message' => '문의가 종료되었습니다.']);
    }
}