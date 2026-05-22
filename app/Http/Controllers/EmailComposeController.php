<?php

namespace App\Http\Controllers;

use App\Mail\ComposeMail;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class EmailComposeController extends Controller
{
    /**
     * 구성원 선택용 대상자 목록 — 본인이 참여한 프로젝트의 멤버 전원 (회사 무관, 본인 제외).
     * project_members 피벗 테이블 기준.
     */
    public function recipients(): JsonResponse
    {
        $me = Auth::user();
        if (!$me) {
            return response()->json(['users' => []]);
        }

        $myProjectIds = \DB::table('project_members')
            ->where('user_id', $me->id)
            ->pluck('project_id');

        if ($myProjectIds->isEmpty()) {
            return response()->json(['users' => []]);
        }

        $users = User::query()
            ->whereIn('id', function ($q) use ($myProjectIds) {
                $q->select('user_id')
                  ->from('project_members')
                  ->whereIn('project_id', $myProjectIds);
            })
            ->where('id', '!=', $me->id)
            ->orderBy('name')
            ->distinct()
            ->get(['id', 'name', 'email', 'phone', 'company'])
            ->map(fn($u) => [
                'id'      => $u->id,
                'name'    => $u->name,
                'email'   => $u->email,
                'phone'   => $u->phone,
                'company' => $u->company,
            ])
            ->values();

        return response()->json(['users' => $users]);
    }

    /**
     * 이메일 발송 — multipart 폼 (HTML 본문 + 첨부파일).
     *   - 발송 후 휴대폰 번호가 있는 수신자에게 SMS 알림 동반 전송.
     */
    public function send(Request $request): JsonResponse
    {
        $sender = Auth::user();
        abort_unless($sender, 401);

        $request->validate([
            'subject'        => 'required|string|max:200',
            'body'           => 'required|string|max:1000000',  // HTML 이라 max 크게
            'recipients'     => 'required|array|min:1',
            'recipients.*'   => 'string|max:300',
            'attachments'    => 'nullable|array|max:10',
            'attachments.*'  => 'file|max:20480',  // 파일당 20MB
        ]);

        // 입력 정규화: "이름 <email>" / "email" 모두 허용.
        $entries = collect($request->input('recipients', []))
            ->map(fn($raw) => $this->parseRecipient(trim((string) $raw)))
            ->filter(fn($e) => $e && filter_var($e['email'], FILTER_VALIDATE_EMAIL))
            ->unique('email')
            ->values();

        if ($entries->isEmpty()) {
            return response()->json(['ok' => false, 'message' => '유효한 수신 이메일이 없습니다.'], 422);
        }

        // 첨부파일 임시 저장 (메일 발송 후 정리)
        $attachments = [];
        foreach ((array) $request->file('attachments', []) as $file) {
            if (!$file) continue;
            $path = $file->store('email-compose/attachments/' . date('Ymd'), 'local');
            $attachments[] = [
                'path' => storage_path('app/private/' . $path),
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
            ];
        }

        // 동일 회사 구성원 매핑 (id 또는 email 기준) — 휴대폰 번호 조회용.
        $companyUsers = User::query()
            ->when($sender->company_group_id, fn($q) => $q->where('company_group_id', $sender->company_group_id))
            ->get(['id', 'name', 'email', 'phone'])
            ->keyBy(fn($u) => mb_strtolower((string) $u->email));

        $subject = trim($request->subject);
        $body    = (string) $request->body;
        $sent    = 0;
        $errors  = [];

        foreach ($entries as $e) {
            $email = $e['email'];
            $name  = $e['name'] ?: ($companyUsers[mb_strtolower($email)]->name ?? null);
            try {
                Mail::to($email, $name)->send(new ComposeMail($sender, $subject, $body, $name, $attachments));
                $sent++;
            } catch (\Throwable $ex) {
                \App\Models\SystemErrorLog::record($ex, 'warning');
                $errors[] = $email;
                continue;
            }

            // SMS — 회사 구성원으로 매칭되고 phone 이 있으면 발송, 또는 입력 자체에 phone 이 있으면 발송
            $phone = $e['phone'] ?: ($companyUsers[mb_strtolower($email)]->phone ?? null);
            if (!empty($phone)) {
                $smsMsg = "[SupportWorks] {$sender->name}님이 이메일을 보냈습니다: " . mb_strimwidth($subject, 0, 50, '...', 'UTF-8');
                try { SmsService::send($phone, $smsMsg, $name); } catch (\Throwable) {}
            }
        }

        // 임시 첨부파일 정리
        foreach ($attachments as $att) {
            @unlink($att['path']);
        }

        return response()->json([
            'ok'      => $sent > 0,
            'sent'    => $sent,
            'errors'  => $errors,
            'message' => $sent > 0
                ? "{$sent}건 발송 완료" . ($errors ? ' (' . count($errors) . '건 실패)' : '')
                : '발송에 실패했습니다.',
        ]);
    }

    /**
     * 본문 이미지 업로드 (Quill 에디터 paste/insert).
     */
    public function uploadImage(Request $request): JsonResponse
    {
        abort_unless(Auth::check(), 401);
        $request->validate(['image' => 'required|image|max:5120']);  // 5MB
        $path = $request->file('image')->store('email-compose/images/' . date('Ymd'), 'public');
        return response()->json(['url' => asset('storage/' . $path)]);
    }

    /**
     * "이름 <email>" / "email" / "email|phone" 형태 모두 파싱.
     * 프런트는 회사 구성원 선택 시 "name|email|phone" 으로 보냄.
     */
    private function parseRecipient(string $raw): ?array
    {
        if ($raw === '') return null;

        // 파이프 구분자 (회사 구성원 선택)
        if (str_contains($raw, '|')) {
            $parts = array_pad(explode('|', $raw), 3, '');
            return [
                'name'  => trim($parts[0]) ?: null,
                'email' => trim($parts[1]),
                'phone' => trim($parts[2]) ?: null,
            ];
        }

        // "이름 <email>"
        if (preg_match('/^(.*?)\s*<\s*([^>]+)\s*>$/u', $raw, $m)) {
            return [
                'name'  => trim(trim($m[1]), '"\'') ?: null,
                'email' => trim($m[2]),
                'phone' => null,
            ];
        }

        // 단일 이메일
        return [
            'name'  => null,
            'email' => $raw,
            'phone' => null,
        ];
    }
}
