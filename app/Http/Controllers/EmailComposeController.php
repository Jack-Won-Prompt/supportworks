<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Mailbox\MailDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
     *   Mailbox 시스템으로 일원화 — MailDispatchService 가 적재·SMTP·SMS 모두 처리.
     */
    public function send(Request $request, MailDispatchService $dispatcher): JsonResponse
    {
        $sender = Auth::user();
        abort_unless($sender, 401);

        $request->validate([
            'subject'        => 'required|string|max:300',
            'body'           => 'required|string|max:1000000',
            'recipients'     => 'required|array|min:1',
            'recipients.*'   => 'string|max:300',
            'attachments'    => 'nullable|array|max:10',
            'attachments.*'  => 'file|max:20480',
        ]);

        // 입력 정규화: "이름 <email>" / "email" / "name|email|phone" 형태 허용
        $entries = collect($request->input('recipients', []))
            ->map(fn($raw) => $this->parseRecipient(trim((string) $raw)))
            ->filter(fn($e) => $e && filter_var($e['email'], FILTER_VALIDATE_EMAIL))
            ->unique('email')
            ->values()
            ->all();

        if (empty($entries)) {
            return response()->json(['ok' => false, 'message' => '유효한 수신 이메일이 없습니다.'], 422);
        }

        try {
            $msg = $dispatcher->send(
                sender: $sender,
                subject: trim((string) $request->input('subject')),
                bodyHtml: (string) $request->input('body'),
                recipients: array_map(fn ($e) => [
                    'email' => $e['email'],
                    'name'  => $e['name'] ?? null,
                    'type'  => 'to',
                ], $entries),
                files: array_values(array_filter((array) $request->file('attachments', []))),
            );
        } catch (\Throwable $ex) {
            \App\Models\SystemErrorLog::record($ex, 'warning');
            return response()->json(['ok' => false, 'message' => '발송에 실패했습니다.'], 500);
        }

        return response()->json([
            'ok'      => true,
            'sent'    => $msg->recipient_count,
            'message' => "{$msg->recipient_count}건 발송 완료",
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
