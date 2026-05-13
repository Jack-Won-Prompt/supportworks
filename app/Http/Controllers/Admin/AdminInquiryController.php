<?php

namespace App\Http\Controllers\Admin;

use App\Events\MessageSent;
use App\Events\NewAdminMessage;
use App\Http\Controllers\Controller;
use App\Mail\InquiryStatusMail;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminInquiryController extends Controller
{
    public function index(Request $request)
    {
        $admin   = auth('admin')->user();
        $status  = $request->query('status', 'all');
        $search  = $request->query('search');
        $groupId = $request->query('group_id');

        $query = Conversation::where('type', 'inquiry')
            ->with(['participants.companyGroup', 'lastMessage', 'firstMessage.sender.companyGroup', 'assignedAdmin'])
            ->withCount('messages');

        // 그룹 기반 접근 제어
        if (!$admin->isSuperAdmin()) {
            $groupIds = $admin->companyGroups()->pluck('company_groups.id');
            $query->whereIn('company_group_id', $groupIds);
        }

        if ($groupId) {
            $query->where('company_group_id', $groupId);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->whereHas('participants', fn($q) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            );
        }

        $conversations = $query->orderByRaw("FIELD(status,'open','active','closed')")
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        // 접근 가능 그룹 목록 (필터 드롭다운용)
        $groups = $admin->isSuperAdmin()
            ? \App\Models\CompanyGroup::orderBy('name')->get(['id', 'name'])
            : $admin->companyGroups()->orderBy('name')->get(['company_groups.id', 'company_groups.name']);

        $baseQuery = fn() => Conversation::where('type', 'inquiry')
            ->when(!$admin->isSuperAdmin(), function($q) use ($admin) {
                $ids = $admin->companyGroups()->pluck('company_groups.id');
                $q->whereIn('company_group_id', $ids);
            });

        $stats = [
            'all'    => $baseQuery()->count(),
            'open'   => $baseQuery()->where('status', 'open')->count(),
            'active' => $baseQuery()->where('status', 'active')->count(),
            'closed' => $baseQuery()->where('status', 'closed')->count(),
        ];

        return view('admin.inquiries.index', compact('conversations', 'status', 'search', 'stats', 'groups', 'groupId'));
    }

    public function show(Conversation $conversation)
    {
        abort_if($conversation->type !== 'inquiry', 404);
        $conversation->load(['participants', 'firstMessage.sender.companyGroup', 'messages.sender', 'messages.imageComments.user', 'messages.imageComments.adminUser', 'assignedAdmin']);
        $admin = auth('admin')->user();

        return view('admin.inquiries.show', compact('conversation', 'admin'));
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['image' => 'required|image|max:5120']);
        $path = $request->file('image')->store('inquiry/images', 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    public function reply(Request $request, Conversation $conversation)
    {
        abort_if($conversation->type !== 'inquiry', 404);
        $request->validate(['body' => ['required', 'string', 'max:50000', function($attr, $val, $fail) {
            if (trim(strip_tags($val)) === '' && !str_contains($val, '<img')) $fail('내용을 입력해주세요.');
        }]]);

        $admin = auth('admin')->user();
        $conversation->load('participants');

        $customerId = $conversation->messages()->value('sender_id');

        $senderUser = ($conversation->assigned_agent_id ? User::find($conversation->assigned_agent_id) : null)
            ?? $conversation->participants->firstWhere('id', '!=', $customerId)
            ?? User::where('role', 'admin')->first();

        if (!$senderUser) {
            $err = '답변을 보낼 수 있는 계정이 없습니다.';
            return $request->wantsJson()
                ? response()->json(['ok' => false, 'error' => $err], 422)
                : back()->withErrors(['body' => $err]);
        }

        $file     = $request->file('file');
        $filePath = $fileName = $fileSize = null;
        if ($file) {
            $filePath = $file->store('inquiry/files', 'public');
            $fileName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        }

        $body = "[관리자 {$admin->name}] " . $request->body;

        $msg = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $senderUser->id,
            'body'            => $body,
            'file_path'       => $filePath,
            'file_name'       => $fileName,
            'file_size'       => $fileSize,
        ]);

        $conversation->touch();
        $statusChangedToActive = false;
        if ($conversation->status === 'open') {
            $conversation->update(['status' => 'active', 'assigned_admin_id' => $admin->id]);
            $statusChangedToActive = true;
        }

        broadcast(new MessageSent($msg));

        // 처리중(open→active) 전환 시 문의 등록자에게 이메일 + SMS
        if ($statusChangedToActive) {
            $this->notifyInquiryStatusChange($conversation, 'active', '처리중', $admin->name);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'             => true,
                'id'             => $msg->id,
                'body'           => $request->body,
                'admin_name'     => $admin->name,
                'created_at'     => $msg->created_at->toIso8601String(),
                'file_url'       => $msg->fileUrl(),
                'file_name'      => $fileName,
                'is_image'       => $msg->isImage(),
                'formatted_size' => $msg->formattedSize(),
            ]);
        }

        return back()->with('success', '답변이 전송됐습니다.');
    }

    public function close(Conversation $conversation)
    {
        abort_if($conversation->type !== 'inquiry', 404);
        $conversation->update(['status' => 'closed']);

        $admin = auth('admin')->user();
        $this->notifyInquiryStatusChange($conversation, 'closed', '종료', $admin->name ?? '관리자');

        return back()->with('success', '문의가 종료되었습니다.');
    }

    public function reopen(Conversation $conversation)
    {
        abort_if($conversation->type !== 'inquiry', 404);
        $conversation->update(['status' => 'active']);

        $admin = auth('admin')->user();
        $this->notifyInquiryStatusChange($conversation, 'active', '처리중', $admin->name ?? '관리자');

        return back()->with('success', '문의가 재개되었습니다.');
    }

    /**
     * 문의 상태 변경(처리중/종료) 시 등록자에게 이메일 발송 후, 성공 시 SMS 추가 발송.
     */
    private function notifyInquiryStatusChange(
        Conversation $conversation,
        string $statusKey,
        string $statusLabel,
        string $adminName,
    ): void {
        $customerId = $conversation->messages()->orderBy('id')->value('sender_id');
        if (!$customerId) return;

        $customer = User::find($customerId);
        if (!$customer) return;

        // 1) 이메일 발송 (동기)
        $mailOk = false;
        if (filter_var($customer->email, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::to($customer->email)
                    ->send(new InquiryStatusMail($conversation, $customer, $statusKey, $statusLabel, $adminName));
                $mailOk = true;
            } catch (\Throwable $e) {
                \App\Models\SystemErrorLog::record($e, 'warning');
            }
        }

        // 2) 이메일 성공 + 휴대폰 등록 시 SMS 비동기 발송
        if ($mailOk && !empty($customer->phone)) {
            $subject  = $conversation->name ?? '문의';
            $smsPhone = $customer->phone;
            $smsName  = $customer->name;
            $smsMsg   = "[SupportWorks] '{$subject}' 문의가 {$statusLabel} 상태로 변경되었습니다. ({$adminName})";

            app()->terminating(static function () use ($smsPhone, $smsName, $smsMsg) {
                set_time_limit(0);
                try { SmsService::send($smsPhone, $smsMsg, $smsName); } catch (\Throwable) {}
            });
        }
    }

    // ── 사용자 검색 (JSON) ────────────────────────────────────────

    public function searchUsers(Request $request)
    {
        $q = trim($request->query('q', ''));

        $users = User::query()
            ->when($q, fn($query) => $query->where(function ($w) use ($q) {
                $w->where('name',  'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhere('company', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'company']);

        return response()->json($users);
    }

    // ── 특정 사용자(들)에게 메시지 보내기 ────────────────────────

    public function sendToUsers(Request $request)
    {
        $request->validate([
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'message'    => 'required|string|max:2000',
        ]);

        $admin      = auth('admin')->user();
        $senderUser = User::where('role', 'admin')->first();

        if (!$senderUser) {
            return response()->json(['ok' => false, 'error' => '시스템에 admin 역할 사용자가 없습니다. 사용자 관리에서 role=admin 계정을 먼저 등록하세요.'], 422);
        }

        $body  = "[관리자 {$admin->name}] " . trim($request->message);
        $count = 0;

        foreach ($request->user_ids as $userId) {
            if ((int)$userId === $senderUser->id) continue;

            $user = User::find($userId);
            if (!$user) continue;

            $conv = Conversation::create([
                'type'              => 'inquiry',
                'status'            => 'active',
                'assigned_admin_id' => $admin->id,
                'company_group_id'  => $user->company_group_id,
            ]);
            $conv->participants()->attach([$user->id, $senderUser->id]);

            $msg = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $senderUser->id,
                'body'            => $body,
            ]);

            broadcast(new MessageSent($msg));
            broadcast(new NewAdminMessage($msg, $conv, (int) $userId, $admin->name));
            $count++;
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }

    // ── 전체 사용자에게 메시지 보내기 ────────────────────────────

    public function broadcastToAll(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $admin      = auth('admin')->user();
        $senderUser = User::where('role', 'admin')->first();

        if (!$senderUser) {
            return response()->json(['ok' => false, 'error' => '시스템에 admin 역할 사용자가 없습니다.'], 422);
        }

        $body  = "[관리자 {$admin->name}] " . trim($request->message);
        $users = User::where('id', '!=', $senderUser->id)->get(['id', 'company_group_id']);
        $count = 0;

        foreach ($users as $user) {
            $conv = Conversation::create([
                'type'              => 'inquiry',
                'status'            => 'active',
                'assigned_admin_id' => $admin->id,
                'company_group_id'  => $user->company_group_id,
            ]);
            $conv->participants()->attach([$user->id, $senderUser->id]);

            $msg = Message::create([
                'conversation_id' => $conv->id,
                'sender_id'       => $senderUser->id,
                'body'            => $body,
            ]);

            broadcast(new MessageSent($msg));
            broadcast(new NewAdminMessage($msg, $conv, $user->id, $admin->name));
            $count++;
        }

        return response()->json(['ok' => true, 'count' => $count]);
    }
}
