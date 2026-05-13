<?php

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $conversations = Conversation::whereNull('type')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['participants', 'lastMessage.sender'])
            ->get()
            ->sortByDesc(fn($c) => optional($c->lastMessage)->created_at)
            ->values();

        $users = $this->projectMates($user);

        return view('messages.index', compact('conversations', 'users'));
    }

    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== null, 404);

        if (!$conversation->participants->contains('id', $user->id)) {
            abort(403);
        }

        $conversation->load(['participants', 'messages.sender', 'messages.replyTo']);

        $now = now();
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => $now]);

        try {
            broadcast(new ConversationRead($conversation->id, $user->id, $now->toIso8601String()));
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            \Log::error('[ConversationRead broadcast] ' . $e->getMessage());
        }

        // 참여자별 last_read_at (읽음 표시용) — pivot 재로드
        $conversation->load('participants');
        $participantReadAt = $conversation->participants->mapWithKeys(fn($p) => [
            $p->id => $p->pivot->last_read_at,
        ]);

        $conversations = Conversation::whereNull('type')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['participants', 'lastMessage.sender'])
            ->get()
            ->sortByDesc(fn($c) => optional($c->lastMessage)->created_at)
            ->values();

        $users = $this->projectMates($user);

        return view('messages.index', compact('conversations', 'conversation', 'users', 'participantReadAt'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id|different:' . auth()->id(),
            'body'        => 'nullable|string|max:2000',
            'file'        => 'nullable|file|max:20480',
        ]);

        if (!$request->filled('body') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => '메시지 또는 파일을 입력하세요.']);
        }

        $user     = auth()->user();
        $receiver = User::findOrFail((int) $request->receiver_id);

        if ($user->hasCompany() && !$user->inSameCompany($receiver)) {
            return back()->with('error', '같은 회사 구성원에게만 메시지를 보낼 수 있습니다.');
        }

        $conv = Conversation::findBetween($user->id, $receiver->id);

        if (!$conv) {
            $conv = Conversation::create(['is_group' => false]);
            $conv->participants()->attach([$user->id, $receiver->id], ['last_read_at' => null]);
        }

        $this->createMessage($request, $conv->id, $user->id);
        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return redirect()->route('messages.show', $conv);
    }

    public function storeGroup(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name'         => 'required|string|max:100',
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'exists:users,id',
            'body'         => 'nullable|string|max:2000',
            'file'         => 'nullable|file|max:20480',
        ]);

        if (!$request->filled('body') && !$request->hasFile('file')) {
            return back()->withErrors(['body' => '메시지 또는 파일을 입력하세요.']);
        }

        $conv = Conversation::create(['name' => $request->name, 'is_group' => true]);

        $projectIds   = $user->projects()->pluck('projects.id');
        $allowedIds   = User::whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds))
            ->where('id', '!=', $user->id)
            ->pluck('id');

        $memberIds = collect($request->member_ids)
            ->map(fn($id) => (int)$id)
            ->filter(fn($id) => $allowedIds->contains($id))
            ->reject(fn($id) => $id === $user->id)
            ->prepend($user->id)
            ->unique()
            ->values();

        $conv->participants()->attach($memberIds->toArray(), ['last_read_at' => null]);

        $this->createMessage($request, $conv->id, $user->id);
        $conv->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        return redirect()->route('messages.show', $conv);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        if (!$conversation->participants->contains('id', $user->id)) {
            abort(403);
        }

        $request->validate([
            'body'            => 'nullable|string|max:8000',
            'translated_body' => 'nullable|string|max:8000',
            'translate_lang'  => 'nullable|string|in:ko,en,ja,zh',
            'file'            => 'nullable|file|max:20480',
            'reply_to_id'     => 'nullable|integer|exists:messages,id',
        ]);

        if (!$request->filled('body') && !$request->hasFile('file')) {
            return $request->expectsJson() ? response()->json(['error' => 'empty'], 422) : back();
        }

        $this->createMessage($request, $conversation->id, $user->id);
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('messages.show', $conversation)->withFragment('bottom');
    }

    public function leave(Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== null, 404);

        if (!$conversation->participants->contains('id', $user->id)) {
            abort(403);
        }

        $conversation->participants()->detach($user->id);

        if ($conversation->participants()->count() === 0) {
            $conversation->messages()->delete();
            $conversation->delete();
        }

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('messages.index');
    }

    // 내가 속한 프로젝트의 다른 멤버 목록 (중복 제거, 이름순)
    private function projectMates(User $user): \Illuminate\Database\Eloquent\Collection
    {
        $projectIds = $user->projects()->pluck('projects.id');

        return User::whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds))
            ->where('id', '!=', $user->id)
            ->orderBy('name')
            ->get();
    }

    private function createMessage(Request $request, int $convId, int $senderId): void
    {
        $data = [
            'conversation_id' => $convId,
            'sender_id'       => $senderId,
            'body'            => $request->input('body', ''),
            'translated_body' => $request->filled('translated_body') ? trim($request->input('translated_body')) : null,
            'translate_lang'  => $request->filled('translate_lang') ? $request->input('translate_lang') : null,
            'reply_to_id'     => $request->input('reply_to_id') ?: null,
        ];

        if ($request->hasFile('file')) {
            $file          = $request->file('file');
            $data['file_path'] = $file->store('messages', 'public');
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
        }

        $message = Message::create($data);
        try {
            broadcast(new MessageSent($message));
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
            \Log::error('[Broadcast] '.$e->getMessage());
        }

        $this->notifyUnreadBySms($message, $convId, $senderId);
    }

    /**
     * 수신자에게 이전 미읽음 메시지가 없을 때만 SMS 발송.
     * 빠르게 연달아 보내는 경우 첫 번째 메시지만 SMS로 알림 → 도배 방지.
     */
    private function notifyUnreadBySms(Message $message, int $convId, int $senderId): void
    {
        $convo = Conversation::with('participants')->find($convId);
        if (!$convo) return;

        $sender = User::find($senderId);
        if (!$sender) return;

        $body = trim((string) $message->body);
        if ($body === '' && !empty($message->file_name)) {
            $body = '[파일] ' . $message->file_name;
        }
        if ($body === '') return;

        $excerpt   = mb_strimwidth($body, 0, 100, '...', 'UTF-8');
        $convLabel = $convo->is_group ? ('[' . ($convo->name ?? '그룹') . '] ') : '';
        $smsText   = "[SupportWorks] {$convLabel}{$sender->name}: {$excerpt}";

        foreach ($convo->participants as $p) {
            if ($p->id === $senderId) continue;
            if (empty($p->phone))     continue;

            $lastReadAt = $p->pivot->last_read_at ?? null;

            $unreadQuery = Message::where('conversation_id', $convId)
                ->where('id', '!=', $message->id)
                ->where('sender_id', '!=', $p->id);

            if ($lastReadAt) {
                $unreadQuery->where('created_at', '>', $lastReadAt);
            }

            if ($unreadQuery->count() > 0) {
                continue; // 이미 읽지 않은 메시지가 있음 → SMS 생략
            }

            $smsPhone = $p->phone;
            $smsName  = $p->name;
            app()->terminating(static function () use ($smsPhone, $smsName, $smsText) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsText, $smsName); } catch (\Throwable) {}
            });
        }
    }
}
