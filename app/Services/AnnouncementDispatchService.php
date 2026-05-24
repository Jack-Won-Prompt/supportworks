<?php

namespace App\Services;

use App\Mail\AnnouncementMail;
use App\Models\Announcement;
use App\Models\CompanyGroup;
use App\Models\Mailbox\Message;
use App\Models\Mailbox\Recipient;
use App\Models\SystemErrorLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 관리자 공지사항을 대상 사용자에게 전파.
 *  - 메일박스(mailbox_messages/recipients) 내부 적재
 *  - SMTP 외부 메일 발송 (AnnouncementMail)
 *  - send_email=false 면 SMTP 스킵, 메일박스 적재만 수행
 *
 * 호출 시점은 보통 app()->terminating() 등 HTTP 응답 후 비동기 컨텍스트.
 */
class AnnouncementDispatchService
{
    public function dispatch(Announcement $announcement): void
    {
        try {
            $users = $this->resolveTargetUsers($announcement);
            if ($users->isEmpty()) return;

            // 1) 메일박스 적재 — 단일 Message + 대상 사용자별 Recipient(inbox)
            DB::transaction(function () use ($announcement, $users) {
                $message = Message::create([
                    'thread_id'        => null,
                    'sender_id'        => null,
                    'subject'          => mb_substr('[공지] ' . $announcement->title, 0, 300),
                    'body_html'        => $this->renderBodyHtml($announcement),
                    'body_text'        => $announcement->body,
                    'message_id'       => '<announce-' . $announcement->id . '-' . Str::ulid()->toBase32() . '@supportworks>',
                    'has_attachment'   => false,
                    'recipient_count'  => $users->count(),
                    'sent_at'          => now(),
                ]);
                $message->update(['thread_id' => $message->id]);

                $rows = [];
                $now = now();
                foreach ($users as $u) {
                    $rows[] = [
                        'message_id' => $message->id,
                        'user_id'    => $u->id,
                        'email'      => $u->email,
                        'name'       => $u->name,
                        'type'       => 'to',
                        'folder'     => 'inbox',
                        'is_read'    => false,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                // 청크 단위 insert (대량 발송 대응)
                foreach (array_chunk($rows, 500) as $chunk) {
                    Recipient::insert($chunk);
                }
            });

            // 2) SMTP 발송 (옵션) — send_email=true 일 때만
            $sentCount = 0;
            if ($announcement->send_email) {
                foreach ($users as $u) {
                    if (!filter_var($u->email, FILTER_VALIDATE_EMAIL)) continue;
                    try {
                        Mail::to($u->email, $u->name)->send(new AnnouncementMail(
                            $announcement->title,
                            $announcement->body,
                            $announcement->type ?? 'info',
                            $u->name,
                        ));
                        $sentCount++;
                    } catch (\Throwable $e) {
                        SystemErrorLog::record($e, 'warning');
                    }
                }
            }

            $announcement->update([
                'email_sent_at'    => $announcement->send_email ? now() : null,
                'email_sent_count' => $sentCount,
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e, 'error');
        }
    }

    /** target_type 별 사용자 collection 반환 */
    private function resolveTargetUsers(Announcement $a)
    {
        $q = User::query()->whereNotNull('email');

        switch ($a->target_type) {
            case 'withworks':
                $cgIds = CompanyGroup::where('uses_withworks', true)->pluck('id');
                $q->whereIn('company_group_id', $cgIds);
                break;
            case 'companies':
                $ids = is_array($a->target_company_group_ids) ? $a->target_company_group_ids : [];
                if (empty($ids)) {
                    return collect();
                }
                $q->whereIn('company_group_id', $ids);
                break;
            case 'all':
            default:
                // 전체 — 추가 필터 없음
                break;
        }

        return $q->get(['id', 'name', 'email', 'company_group_id']);
    }

    private function renderBodyHtml(Announcement $a): string
    {
        $label = ['info' => '안내', 'warning' => '주의', 'maintenance' => '점검', 'update' => '업데이트'][$a->type ?? 'info'] ?? '안내';
        $title = e($a->title);
        $body  = nl2br(e($a->body));
        return "<p style=\"margin:0 0 10px;font-size:11px;color:#7c3aed;font-weight:700;\">📢 관리자 공지 · {$label}</p>"
             . "<p style=\"margin:0 0 14px;font-size:16px;font-weight:700;color:#1e1b2e;\">{$title}</p>"
             . "<div style=\"font-size:14px;color:#27272a;line-height:1.7;\">{$body}</div>";
    }
}
