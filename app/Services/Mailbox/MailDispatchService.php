<?php

namespace App\Services\Mailbox;

use App\Mail\ComposeMail;
use App\Models\Mailbox\Attachment;
use App\Models\Mailbox\Message;
use App\Models\Mailbox\Recipient;
use App\Models\SystemErrorLog;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * SupportWorks Mailbox 발송 중앙 서비스.
 *
 *  - mailbox_messages / mailbox_recipients / mailbox_attachments 에 영속화
 *  - SMTP 외부 발송 (실패해도 내부 적재는 보존)
 *  - 스레드 묶기 (Message-ID / In-Reply-To 체인)
 *  - 헤더 팝오버(EmailComposeController) 와 Mailbox UI 양쪽에서 호출
 */
class MailDispatchService
{
    /**
     * @param  User                       $sender
     * @param  string                     $subject
     * @param  string                     $bodyHtml
     * @param  array<int, array{email:string, name?:?string, type?:string}>  $recipients
     *         type: 'to' | 'cc' | 'bcc' (기본 'to')
     * @param  array<int, UploadedFile>   $files
     * @param  array{in_reply_to?:?string, references_chain?:?string, thread_id?:?int}  $threadCtx
     * @param  array<int, int>            $projectFileIds  프로젝트 파일에서 선택한 첨부 (참조 방식)
     * @return Message
     */
    public function send(
        User $sender,
        string $subject,
        string $bodyHtml,
        array $recipients,
        array $files = [],
        array $threadCtx = [],
        array $projectFileIds = [],
    ): Message {
        // 1) 입력 정규화
        $recipients = collect($recipients)
            ->map(fn ($r) => [
                'email' => trim((string) ($r['email'] ?? '')),
                'name'  => isset($r['name']) ? trim((string) $r['name']) : null,
                'type'  => in_array(($r['type'] ?? 'to'), ['to', 'cc', 'bcc'], true) ? $r['type'] : 'to',
            ])
            ->filter(fn ($r) => filter_var($r['email'], FILTER_VALIDATE_EMAIL))
            ->unique(fn ($r) => mb_strtolower($r['email']) . '|' . $r['type'])
            ->values()
            ->all();

        if (empty($recipients)) {
            throw new \InvalidArgumentException('유효한 수신자가 없습니다.');
        }

        $bodyText = $this->htmlToText($bodyHtml);
        $messageId = $this->generateMessageId();
        $now = now();

        // 프로젝트 파일 권한 검증 + 정보 로드 (보낸이가 멤버인 프로젝트의 파일만)
        $projectFileRefs = [];
        if (!empty($projectFileIds)) {
            $myProjectIds = DB::table('project_members')->where('user_id', $sender->id)->pluck('project_id');
            $projectFiles = \App\Models\ProjectFile::whereIn('id', $projectFileIds)
                ->whereIn('project_id', $myProjectIds)
                ->get(['id', 'original_name', 'path', 'mime_type', 'size']);
            foreach ($projectFiles as $pf) {
                $projectFileRefs[] = [
                    'original_name' => $pf->original_name,
                    'disk'          => $this->attachmentDisk(),
                    'path'          => $pf->path,
                    'size'          => (int) $pf->size,
                    'mime'          => $pf->mime_type,
                ];
            }
        }

        // 2) DB 트랜잭션 — 메시지·수신자·첨부 적재
        $message = DB::transaction(function () use (
            $sender, $subject, $bodyHtml, $bodyText, $recipients, $files,
            $messageId, $threadCtx, $now, $projectFileRefs,
        ) {
            $message = Message::create([
                'thread_id'        => null,    // 아래에서 set
                'sender_id'        => $sender->id,
                'subject'          => mb_substr($subject, 0, 300),
                'body_html'        => $bodyHtml,
                'body_text'        => $bodyText,
                'message_id'       => $messageId,
                'in_reply_to'      => $threadCtx['in_reply_to'] ?? null,
                'references_chain' => $threadCtx['references_chain'] ?? null,
                'has_attachment'   => !empty($files),
                'recipient_count'  => count($recipients),
                'sent_at'          => $now,
            ]);

            // thread_id: 답장이면 부모의 thread_id, 첫 메일이면 자기 id
            $threadId = $threadCtx['thread_id'] ?? $message->id;
            $message->update(['thread_id' => $threadId]);

            // 발신자 본인 — 보낸편지함 row
            Recipient::create([
                'message_id' => $message->id,
                'user_id'    => $sender->id,
                'email'      => $sender->email,
                'name'       => $sender->name,
                'type'       => 'to',     // 보낸편지함은 type 무의미하지만 NOT NULL 충족
                'folder'     => 'sent',
                'is_read'    => true,
                'read_at'    => $now,
            ]);

            // 수신자 row — SupportWorks 사용자 매칭하여 inbox 폴더 채움
            foreach ($recipients as $r) {
                $user = User::where('email', $r['email'])->first();
                Recipient::create([
                    'message_id' => $message->id,
                    'user_id'    => $user?->id,
                    'email'      => $r['email'],
                    'name'       => $r['name'] ?: $user?->name,
                    'type'       => $r['type'],
                    'folder'     => $user ? 'inbox' : 'sent',  // 외부 사용자는 받은편지함 없음
                    'is_read'    => false,
                ]);
            }

            // 첨부 — 신규 업로드
            $disk = $this->attachmentDisk();
            $hasAttachment = !empty($projectFileRefs);
            foreach ($files as $file) {
                if (!$file || !$file->isValid()) continue;
                $stored = $file->store('mailbox/' . date('Y/m'), $disk);
                Attachment::create([
                    'message_id'    => $message->id,
                    'original_name' => $file->getClientOriginalName(),
                    'disk'          => $disk,
                    'path'          => $stored,
                    'size'          => $file->getSize() ?: 0,
                    'mime'          => $file->getMimeType(),
                ]);
                $hasAttachment = true;
            }
            // 프로젝트 파일 참조 첨부 (복사 없이 같은 disk/path 가리킴)
            foreach ($projectFileRefs as $ref) {
                Attachment::create([
                    'message_id'    => $message->id,
                    'original_name' => $ref['original_name'],
                    'disk'          => $ref['disk'],
                    'path'          => $ref['path'],
                    'size'          => $ref['size'],
                    'mime'          => $ref['mime'],
                ]);
            }
            if ($hasAttachment) {
                $message->update(['has_attachment' => true]);
            }

            return $message->refresh();
        });

        // 3) SMTP 외부 발송 — 실패해도 DB 적재는 살아남음
        $this->dispatchSmtp($sender, $subject, $bodyHtml, $recipients, $files, $projectFileRefs);

        // 4) SMS — 매칭된 SupportWorks 사용자가 휴대폰 있으면 알림
        $this->dispatchSms($sender, $subject, $recipients);

        return $message;
    }

    /**
     * SMTP 발송 — Mail::send 로 외부 메일 발송.
     */
    private function dispatchSmtp(User $sender, string $subject, string $bodyHtml, array $recipients, array $files, array $projectFileRefs = []): void
    {
        // 첨부파일 임시 정보 — ComposeMail 이 받는 형식으로 변환
        $attachments = [];
        foreach ($files as $file) {
            if (!$file || !$file->isValid()) continue;
            $tmpPath = $file->getRealPath();
            if ($tmpPath && file_exists($tmpPath)) {
                $attachments[] = [
                    'path' => $tmpPath,
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                ];
            }
        }
        // 프로젝트 파일 — 디스크에서 실제 경로 해석
        foreach ($projectFileRefs as $ref) {
            try {
                $absPath = Storage::disk($ref['disk'])->path($ref['path']);
                if (file_exists($absPath)) {
                    $attachments[] = [
                        'path' => $absPath,
                        'name' => $ref['original_name'],
                        'mime' => $ref['mime'],
                    ];
                }
            } catch (\Throwable $e) { /* ignore */ }
        }

        foreach ($recipients as $r) {
            try {
                Mail::to($r['email'], $r['name'])
                    ->send(new ComposeMail($sender, $subject, $bodyHtml, $r['name'], $attachments));
            } catch (\Throwable $e) {
                SystemErrorLog::record($e, 'warning');
            }
        }
    }

    /**
     * SMS 알림 — SupportWorks 사용자(phone 있음) 한정.
     */
    private function dispatchSms(User $sender, string $subject, array $recipients): void
    {
        foreach ($recipients as $r) {
            $user = User::where('email', $r['email'])->first();
            $phone = $user?->phone;
            if (!$phone) continue;
            $msg = "[SupportWorks] {$sender->name}님 이메일: " . mb_strimwidth($subject, 0, 50, '...', 'UTF-8');
            try { SmsService::send($phone, $msg, $user?->name); } catch (\Throwable) {}
        }
    }

    /**
     * 첨부 저장 디스크 — config('filesystems.default') 사용 (기존 체계 일관성).
     */
    private function attachmentDisk(): string
    {
        return (string) config('filesystems.default', 'local');
    }

    /**
     * RFC-style Message-ID 생성 — 자체 발행.
     */
    private function generateMessageId(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'supportworks.local';
        return '<' . Str::ulid()->toBase32() . '@' . $host . '>';
    }

    /**
     * HTML → 텍스트 (검색·미리보기용).
     */
    private function htmlToText(string $html): string
    {
        $text = strip_tags(preg_replace(['/<br\s*\/?>/i', '/<\/p>/i'], "\n", $html));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[ \t]+/', ' ', preg_replace('/\n{3,}/', "\n\n", $text)));
    }
}
