<?php

namespace App\Http\Controllers;

use App\Events\InquiryAssignedEvent;
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
use Illuminate\Support\Facades\Mail;

class InquiryController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $inquiries = Conversation::where('type', 'inquiry')
            ->whereHas('participants', fn($q) => $q->where('user_id', $user->id))
            ->with(['lastMessage.sender', 'participants', 'assignedAgent'])
            ->orderBy('updated_at', 'desc')
            ->get();

        $openCount = $inquiries->whereIn('status', ['open', 'active'])->count();

        return view('inquiry.index', compact('inquiries', 'user', 'openCount'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:200',
            'message' => ['required', 'string', 'max:20000', function($attr, $val, $fail) {
                if (trim(strip_tags($val)) === '') $fail('내용을 입력해주세요.');
            }],
        ]);

        $user = auth()->user();

        $conv = Conversation::create([
            'name'             => $validated['subject'],
            'type'             => 'inquiry',
            'status'           => 'open',
            'company_group_id' => $user->company_group_id,
        ]);

        $conv->participants()->attach($user->id, ['last_read_at' => now()]);

        // 그룹 기반 상담원 배정: 해당 그룹에서 online 상담원 우선, 없으면 아무 상담원
        $agent = $this->pickAgent($user->company_group_id, $user->id);

        if ($agent) {
            $conv->participants()->syncWithoutDetaching([$agent->id]);
            $conv->update(['assigned_agent_id' => $agent->id]);
        } else {
            // 폴백: 사이트 admin 역할 계정
            $adminIds = User::where('role', 'admin')
                ->where('id', '!=', $user->id)
                ->pluck('id')
                ->all();
            if ($adminIds) {
                $conv->participants()->syncWithoutDetaching($adminIds);
            }
        }

        $msg = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $user->id,
            'body'            => $validated['message'],
        ]);

        // 신규 문의 알림 (super_admin은 그룹 여부 무관 수신)
        // MessageSent는 broadcast하지 않음 — NewInquiryEvent로 통합 알림 처리
        broadcast(new NewInquiryEvent($conv, $user->name, $validated['subject'], $validated['message']));

        // 배정된 상담원에게 개별 알림
        if ($agent) {
            broadcast(new InquiryAssignedEvent($conv->load('participants'), $agent->id));
        }

        // 관리자에게 이메일 발송 후 성공 시 SMS 추가 발송 (내용 제외, 누가 등록했는지만)
        $this->notifyAdminsNewInquiry($user, $conv, $validated['message']);

        (new SupportWorksAiAssistant())->scheduleReply($conv);

        return redirect()->route('inquiry.index')
            ->with('success', '문의가 등록됐습니다. 담당자가 확인 후 답변 드리겠습니다.')
            ->with('new_inquiry_id', $conv->id);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== 'inquiry', 404);
        abort_if(!$conversation->participants->contains('id', $user->id), 403);

        $conversation->load(['messages.sender', 'messages.imageComments.user', 'participants', 'assignedAgent']);

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $isPopup = $request->boolean('popup');

        return view('inquiry.show', compact('conversation', 'user', 'isPopup'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('inquiry/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== 'inquiry', 404);
        abort_if(!$conversation->participants->contains('id', $user->id), 403);

        $request->validate(['message' => ['required', 'string', 'max:50000', function($attr, $val, $fail) {
            if (trim(strip_tags($val)) === '' && !str_contains($val, '<img')) $fail('내용을 입력해주세요.');
        }]]);

        $file     = $request->file('file');
        $filePath = $fileName = $fileSize = null;
        if ($file) {
            $filePath = $file->store('inquiry/files', 'public');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $user->id,
            'body'            => $request->message,
            'file_path'       => $filePath,
            'file_name'       => $fileName,
            'file_size'       => $fileSize,
        ]);

        if ($conversation->status === 'open') {
            $conversation->update(['status' => 'active']);
        } else {
            $conversation->touch();
        }

        broadcast(new MessageSent($msg))->toOthers();

        $conversation->participants()->updateExistingPivot($user->id, ['last_read_at' => now()]);

        (new SupportWorksAiAssistant())->scheduleReply($conversation);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'             => true,
                'id'             => $msg->id,
                'file_url'       => $msg->fileUrl(),
                'file_name'      => $fileName,
                'is_image'       => $msg->isImage(),
                'formatted_size' => $msg->formattedSize(),
            ]);
        }

        return back();
    }

    public function close(Conversation $conversation)
    {
        $user = auth()->user();

        abort_if($conversation->type !== 'inquiry', 404);
        abort_if(!$conversation->participants->contains('id', $user->id), 403);

        $conversation->update(['status' => 'closed']);

        return back()->with('success', '문의가 종료됐습니다.');
    }

    // ── 상담원 선택 로직 ─────────────────────────────────────

    private function pickAgent(?int $groupId, int $excludeUserId): ?User
    {
        if (!$groupId) return null;

        $base = User::where('company_group_id', $groupId)
                    ->where('id', '!=', $excludeUserId);

        return (clone $base)->where('agent_status', 'online')->inRandomOrder()->first()
            ?? (clone $base)->inRandomOrder()->first();
    }

    /**
     * 신규 문의 등록 시 사이트 관리자(admin_users)에게 이메일 + SMS 알림.
     * 이메일은 본문 미리보기를 포함, SMS는 누가 등록했는지만(본문 제외).
     */
    private function notifyAdminsNewInquiry(User $user, Conversation $conv, string $bodyText): void
    {
        // super_admin 또는 같은 그룹 접근 권한이 있는 활성 관리자
        $query = AdminUser::where('status', 'active');

        if ($conv->company_group_id) {
            $query->where(function ($q) use ($conv) {
                $q->where('role', 'super_admin')
                  ->orWhereHas('companyGroups', fn($g) => $g->where('company_groups.id', $conv->company_group_id));
            });
        }

        $admins = $query->get(['id', 'name', 'email', 'phone']);
        if ($admins->isEmpty()) return;

        $smsMsg = "[SupportWorks] {$user->name}님으로부터 문의가 등록되었습니다.";

        foreach ($admins as $admin) {
            // 1) 이메일 발송 (동기)
            $mailOk = false;
            if (filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($admin->email)->send(new InquiryNewMail($conv, $user, $bodyText));
                    $mailOk = true;
                } catch (\Throwable $e) {
                    \App\Models\SystemErrorLog::record($e, 'warning');
                }
            }

            // 2) 이메일 성공 + 휴대폰 등록 시 SMS 비동기 발송
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
