<?php

namespace App\Http\Controllers;

use App\Events\ConversationRead;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Events\MessageDeleted;
use App\Mail\ChatFileShareMail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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

        // 메시지별 Plan-Do-Act 등록 여부 (message_id => plan_do_act_id)
        $messagePdaMap = \App\Models\PlanDoAct::whereIn('source_message_id', $conversation->messages->pluck('id'))
            ->pluck('id', 'source_message_id');

        return view('messages.index', compact('conversations', 'conversation', 'users', 'participantReadAt', 'messagePdaMap'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id|different:' . auth()->id(),
            'body'        => 'nullable|string|max:2000',
            'files'       => 'nullable|array|max:10',
            'files.*'     => 'file|max:20480',
        ]);

        if (!$request->filled('body') && empty($request->file('files', []))) {
            return back()->withErrors(['body' => '메시지 또는 파일을 입력하세요.']);
        }

        $user     = auth()->user();
        $receiver = User::findOrFail((int) $request->receiver_id);

        // 같은 프로젝트 동료(회사 무관) 또는 같은 회사 구성원에게만 1:1 발송 허용.
        // 그룹 채팅과 동일하게 프로젝트 멤버십 기준을 적용 — 다른 회사라도 공유 프로젝트가 있으면 가능.
        $sharesProject = \DB::table('project_members')
            ->where('user_id', $receiver->id)
            ->whereIn('project_id', $user->projects()->pluck('projects.id'))
            ->exists();

        if (!$sharesProject && !$user->inSameCompany($receiver)) {
            return back()->with('error', '같은 프로젝트 또는 같은 회사 구성원에게만 메시지를 보낼 수 있습니다.');
        }

        $conv = Conversation::findBetween($user->id, $receiver->id);

        if (!$conv) {
            $conv = Conversation::create(['is_group' => false]);
            $conv->participants()->attach([$user->id, $receiver->id], ['last_read_at' => null]);
        }

        $this->sendMessages($request, $conv->id, $user->id);
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
            'files'        => 'nullable|array|max:10',
            'files.*'      => 'file|max:20480',
        ]);

        if (!$request->filled('body') && empty($request->file('files', []))) {
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

        $this->sendMessages($request, $conv->id, $user->id);
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
            'files'           => 'nullable|array|max:10',
            'files.*'         => 'file|max:20480',
            'reply_to_id'     => 'nullable|integer|exists:messages,id',
        ]);

        if (!$request->filled('body') && empty($request->file('files', []))) {
            return $request->expectsJson() ? response()->json(['error' => 'empty'], 422) : back();
        }

        $this->sendMessages($request, $conversation->id, $user->id);
        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('messages.show', $conversation)->withFragment('bottom');
    }

    public function invite(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== null, 404);
        abort_unless($conversation->is_group, 422, '그룹 채팅에만 초대할 수 있습니다.');
        abort_unless($conversation->participants->contains('id', $user->id), 403);

        $request->validate([
            'member_ids'   => 'required|array|min:1',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $projectIds = $user->projects()->pluck('projects.id');
        $allowedIds = User::whereHas('projects', fn($q) => $q->whereIn('projects.id', $projectIds))
            ->where('id', '!=', $user->id)
            ->pluck('id');

        $existingIds = $conversation->participants->pluck('id');

        $toAttach = collect($request->member_ids)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $allowedIds->contains($id))
            ->reject(fn($id) => $existingIds->contains($id))
            ->unique()
            ->values();

        if ($toAttach->isEmpty()) {
            return response()->json([
                'ok'      => false,
                'message' => '초대할 수 있는 사용자가 없습니다. (이미 참여 중이거나 권한이 없는 사용자)',
            ], 422);
        }

        $conversation->participants()->attach(
            $toAttach->mapWithKeys(fn($id) => [$id => ['last_read_at' => null]])->all()
        );

        return response()->json([
            'ok'      => true,
            'added'   => $toAttach->count(),
            'message' => $toAttach->count() . '명을 초대했습니다.',
        ]);
    }

    /**
     * 메시지 본문 수정 — 발신자 본인만, 삭제되지 않은 텍스트 메시지에 한함.
     * 답글(reply_to_id 있는 메시지)도 동일하게 처리.
     */
    public function update(Request $request, Message $message)
    {
        $user         = auth()->user();
        $conversation = $message->conversation;

        abort_if($conversation->type !== null, 404);
        abort_unless($conversation->participants->contains('id', $user->id), 403);
        abort_unless((int) $message->sender_id === (int) $user->id, 403, '본인이 보낸 메시지만 수정할 수 있습니다.');
        abort_if($message->isDeleted(), 410, '삭제된 메시지는 수정할 수 없습니다.');

        $data = $request->validate([
            'body' => 'required|string|max:8000',
        ]);

        $body = trim($data['body']);
        if ($body === '') {
            return response()->json(['ok' => false, 'message' => '내용을 입력하세요.'], 422);
        }

        $message->body = $body;
        // 본문이 바뀌면 기존 번역본은 더 이상 일치하지 않으므로 제거
        $message->translated_body = null;
        $message->translate_lang  = null;
        $message->edited_at       = now();
        $message->save();

        try {
            broadcast(new MessageUpdated($message));
        } catch (\Throwable $e) {
            \App\Models\SystemErrorLog::record($e, 'warning');
        }

        return response()->json([
            'ok'        => true,
            'id'        => $message->id,
            'body'      => $message->body,
            'edited'    => true,
        ]);
    }

    /**
     * 메시지 삭제 — 발신자 본인만. 답글 스레드 보존을 위해 soft tombstone(deleted_at).
     */
    public function destroy(Request $request, Message $message)
    {
        $user         = auth()->user();
        $conversation = $message->conversation;

        abort_if($conversation->type !== null, 404);
        abort_unless($conversation->participants->contains('id', $user->id), 403);
        abort_unless((int) $message->sender_id === (int) $user->id, 403, '본인이 보낸 메시지만 삭제할 수 있습니다.');

        if (!$message->isDeleted()) {
            $message->deleted_at = now();
            $message->save();

            try {
                broadcast(new MessageDeleted($message));
            } catch (\Throwable $e) {
                \App\Models\SystemErrorLog::record($e, 'warning');
            }
        }

        return response()->json(['ok' => true, 'id' => $message->id]);
    }

    public function emailFile(Request $request, Message $message)
    {
        $user = auth()->user();
        $conversation = $message->conversation;

        abort_if($conversation->type !== null, 404);
        abort_unless($conversation->participants->contains('id', $user->id), 403);
        abort_unless($message->file_path && $message->file_name, 422, '파일이 첨부된 메시지에서만 사용할 수 있습니다.');

        $absolutePath = Storage::disk('public')->path($message->file_path);
        abort_unless(is_file($absolutePath), 410, '파일을 찾을 수 없습니다.');

        $data = $request->validate([
            'user_ids'        => 'sometimes|array',
            'user_ids.*'      => 'integer',
            'extra_emails'    => 'sometimes|array',
            'extra_emails.*'  => 'email',
        ]);

        $requestedUserIds = collect($data['user_ids'] ?? [])->map(fn($v) => (int) $v)->unique()->values();
        $extraEmails      = collect($data['extra_emails'] ?? [])
            ->map(fn($e) => trim((string) $e))
            ->filter(fn($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique(fn($e) => mb_strtolower($e))
            ->values();

        // 입력이 전혀 없으면 채팅방 전체(본인 제외)로 발송 (기존 동작 유지)
        if ($requestedUserIds->isEmpty() && $extraEmails->isEmpty()) {
            $memberRecipients = $conversation->participants
                ->where('id', '!=', $user->id)
                ->filter(fn($u) => filter_var($u->email, FILTER_VALIDATE_EMAIL))
                ->values();
        } else {
            // 선택된 user_id는 반드시 채팅방 참여자여야 함 (본인 제외)
            $allowedIds = $conversation->participants
                ->where('id', '!=', $user->id)
                ->pluck('id')
                ->all();

            $memberRecipients = $conversation->participants
                ->where('id', '!=', $user->id)
                ->whereIn('id', $requestedUserIds->intersect($allowedIds)->all())
                ->filter(fn($u) => filter_var($u->email, FILTER_VALIDATE_EMAIL))
                ->values();
        }

        // 중복 제거를 위해 이메일 단위로 모음
        $seen = [];
        $recipients = collect();
        foreach ($memberRecipients as $u) {
            $key = mb_strtolower($u->email);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $recipients->push(['email' => $u->email, 'name' => $u->name]);
        }
        foreach ($extraEmails as $email) {
            $key = mb_strtolower($email);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $recipients->push(['email' => $email, 'name' => null]);
        }

        if ($recipients->isEmpty()) {
            return response()->json([
                'ok'      => false,
                'message' => '이메일 주소를 가진 수신자가 없습니다.',
            ], 422);
        }

        $convName = $conversation->is_group ? ($conversation->name ?: '그룹 채팅') : '1:1 대화';
        $subject  = sprintf('[%s] 파일 공유 - %s', $convName, $message->file_name);

        $sent = 0; $failed = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient['email'], $recipient['name'])
                    ->send(new ChatFileShareMail(
                        senderName:   $user->name ?? '',
                        senderEmail:  $user->email ?? '',
                        fileName:     $message->file_name,
                        emailSubject: $subject,
                        filePath:     $absolutePath,
                    ));
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('[ChatFileShare] 발송 실패: ' . $e->getMessage(), [
                    'to'      => $recipient['email'],
                    'message' => $message->id,
                ]);
            }
        }

        return response()->json([
            'ok'      => $sent > 0,
            'sent'    => $sent,
            'failed'  => $failed,
            'message' => $failed === 0
                ? "{$sent}명에게 메일을 발송했습니다."
                : "{$sent}명 발송 성공, {$failed}명 실패",
        ]);
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

    /**
     * messages 화면 상단 워크스페이스 팝업용 — 내가 멤버인 프로젝트 목록 + 권한 허용 메뉴.
     * 메뉴는 7개 주요 메뉴만 노출, hasFeature() 권한 체크 적용.
     */
    public function workspaceProjects(): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();

        $projects = $user->projects()
            ->orderBy('projects.name')
            ->get(['projects.id', 'projects.name', 'projects.status']);

        // 주요 메뉴 7개 — feature 권한 키와 URL 라우트 키
        $allMenus = [
            ['key' => 'overview',     'feature' => null,           'label' => __('projects.nav_overview'),     'route' => 'projects.show'],
            ['key' => 'planning',     'feature' => 'planning',     'label' => __('projects.planning'),         'route' => 'projects.planning.index'],
            ['key' => 'requirements', 'feature' => 'requirements', 'label' => __('projects.nav_requirements'), 'route' => 'projects.requirements.index'],
            ['key' => 'schedules',    'feature' => 'schedules',    'label' => __('projects.schedule'),         'route' => 'projects.schedules.index'],
            ['key' => 'gantt',        'feature' => 'gantt',        'label' => __('projects.gantt'),            'route' => 'projects.gantt'],
            ['key' => 'issues',       'feature' => 'issues',       'label' => __('projects.nav_issues'),       'route' => 'projects.issues.index'],
            ['key' => 'files',        'feature' => 'files',        'label' => __('projects.files'),            'route' => 'projects.files.index'],
        ];

        $menus = array_values(array_filter($allMenus, fn($m) => $m['feature'] === null || $user->hasFeature($m['feature'])));

        $items = $projects->map(function ($p) use ($menus) {
            $menuList = array_map(fn($m) => [
                'key'   => $m['key'],
                'label' => $m['label'],
                'url'   => route($m['route'], $p),
            ], $menus);

            return [
                'id'     => $p->id,
                'name'   => $p->name,
                'status' => $p->status,
                'menus'  => $menuList,
            ];
        });

        return response()->json([
            'projects' => $items,
        ]);
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

    /**
     * 본문 + 첨부파일(복수)을 메시지로 저장한다.
     * 파일이 여러 개면 파일 수만큼 메시지를 만들고, 본문·답글·번역은 첫 메시지에만 싣는다.
     */
    private function sendMessages(Request $request, int $convId, int $senderId): void
    {
        $files = array_values(array_filter((array) $request->file('files', [])));

        if (empty($files)) {
            $this->createMessage($request, $convId, $senderId, null, true);
            return;
        }

        foreach ($files as $i => $file) {
            $this->createMessage($request, $convId, $senderId, $file, $i === 0);
        }
    }

    private function createMessage(
        Request $request,
        int $convId,
        int $senderId,
        ?\Illuminate\Http\UploadedFile $file = null,
        bool $isFirst = true
    ): void {
        $data = [
            'conversation_id' => $convId,
            'sender_id'       => $senderId,
            'body'            => $isFirst ? $request->input('body', '') : '',
            'translated_body' => $isFirst && $request->filled('translated_body') ? trim($request->input('translated_body')) : null,
            'translate_lang'  => $isFirst && $request->filled('translate_lang') ? $request->input('translate_lang') : null,
            'reply_to_id'     => $isFirst ? ($request->input('reply_to_id') ?: null) : null,
        ];

        if ($file) {
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
