<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NewInquiryEvent;
use App\Mail\InquiryNewMail;
use App\Models\AdminUser;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SmsService;
use App\Services\SupportWorksAiAssistant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestChatController extends Controller
{
    public function start(Request $request)
    {
        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'email' => 'required|email|max:160',
            'phone' => 'nullable|string|max:40',
        ]);

        $name  = trim($data['name']);
        $email = strtolower(trim($data['email']));
        $phone = isset($data['phone']) ? trim($data['phone']) : null;

        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'password' => Hash::make(Str::random(40)),
                'role'     => 'client',
                'is_guest' => true,
            ]);
        } else if ($user->is_guest) {
            $user->fill(['name' => $name, 'phone' => $phone ?: $user->phone])->save();
        }

        $token = Str::random(64);

        $conv = Conversation::create([
            'name'         => '[방문 상담] ' . $name,
            'type'         => 'inquiry',
            'status'       => 'open',
            'guest_token'  => $token,
        ]);
        $conv->participants()->attach($user->id, ['last_read_at' => now()]);

        $intro = sprintf(
            "[방문 상담 시작]\n이름: %s\n이메일: %s\n전화: %s",
            $name,
            $email,
            $phone ?: '-'
        );

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'body'            => $intro,
        ]);

        broadcast(new NewInquiryEvent($conv, $name, '방문 상담 시작', $intro));

        $this->notifyAdmins($user, $conv, $intro);

        (new SupportWorksAiAssistant())->scheduleReply($conv);

        return response()->json([
            'ok'              => true,
            'conversation_id' => $conv->id,
            'token'           => $token,
            'messages'        => [$this->serializeMessage($msg)],
        ]);
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $this->authorizeGuest($request, $conversation);

        $afterId = (int) $request->query('after_id', 0);

        $messages = $conversation->messages()
            ->when($afterId, fn($q) => $q->where('id', '>', $afterId))
            ->orderBy('id')
            ->get()
            ->map(fn($m) => $this->serializeMessage($m));

        return response()->json([
            'ok'       => true,
            'status'   => $conversation->status,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $this->authorizeGuest($request, $conversation);

        $data = $request->validate([
            'body' => 'required|string|max:5000',
        ]);

        abort_if($conversation->status === 'closed', 423, '종료된 상담입니다.');

        $senderId = $conversation->messages()->orderBy('id')->value('sender_id');
        abort_unless($senderId, 422);

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $senderId,
            'body'            => trim($data['body']),
        ]);

        $conversation->touch();
        broadcast(new MessageSent($msg))->toOthers();

        (new SupportWorksAiAssistant())->scheduleReply($conversation);

        return response()->json([
            'ok'      => true,
            'message' => $this->serializeMessage($msg),
        ]);
    }

    private function authorizeGuest(Request $request, Conversation $conversation): void
    {
        $token = $request->input('token') ?? $request->query('token') ?? $request->header('X-Guest-Token');
        abort_unless($conversation->type === 'inquiry' && $conversation->guest_token && hash_equals($conversation->guest_token, (string) $token), 403);
    }

    private function serializeMessage(Message $msg): array
    {
        $isAdmin = str_starts_with($msg->body, '[관리자');
        $body = $isAdmin ? preg_replace('/^\[관리자 .+?\] /', '', $msg->body) : $msg->body;

        return [
            'id'         => $msg->id,
            'is_admin'   => $isAdmin,
            'body'       => $body,
            'created_at' => $msg->created_at->toIso8601String(),
            'time'       => $msg->created_at->format('H:i'),
        ];
    }

    private function notifyAdmins(User $user, Conversation $conv, string $bodyText): void
    {
        $admins = AdminUser::where('status', 'active')->get(['id', 'name', 'email', 'phone']);
        if ($admins->isEmpty()) return;

        $smsMsg = "[SupportWorks] {$user->name}님이 방문 상담을 시작했습니다.";

        foreach ($admins as $admin) {
            $mailOk = false;
            if (filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($admin->email)->send(new InquiryNewMail($conv, $user, $bodyText));
                    $mailOk = true;
                } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            }

            if ($mailOk && !empty($admin->phone)) {
                $smsPhone = $admin->phone;
                $smsName  = $admin->name;
                app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                    set_time_limit(0);
                    try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
                });
            }
        }
    }
}
