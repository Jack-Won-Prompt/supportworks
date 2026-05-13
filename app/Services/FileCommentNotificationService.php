<?php

namespace App\Services;

use App\Mail\ProjectActivityMail;
use App\Models\FileComment;
use App\Models\FileCommentNotification;
use App\Models\SystemErrorLog;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class FileCommentNotificationService
{
    /**
     * 파일에 의견(댓글)이 추가되었을 때 업로더에게 이메일+SMS 알림 발송.
     * (project_file_id, sent_date) 유니크 제약으로 당일 1회만 발송된다.
     */
    public static function notifyUploader(FileComment $comment): void
    {
        $comment->loadMissing('file.uploader', 'file.project', 'user');
        $file = $comment->file;

        if (!$file || !$file->uploader) {
            return;
        }

        $uploader = $file->uploader;
        $project  = $file->project;

        // 본인이 본인 파일에 댓글 단 경우 알림 생략
        if ($comment->user_id && (int) $comment->user_id === (int) $uploader->id) {
            return;
        }

        $today = now()->toDateString();

        // 당일 중복 방지 — 유니크 제약을 이용한 atomic claim
        try {
            $row = FileCommentNotification::create([
                'project_file_id' => $file->id,
                'user_id'         => $uploader->id,
                'sent_date'       => $today,
                'email_sent'      => false,
                'sms_sent'        => false,
                'sent_at'         => now(),
            ]);
        } catch (QueryException $e) {
            // 동일 (file, date) 행이 이미 존재 → 오늘은 이미 발송됨
            return;
        }

        $commenterName = $comment->user?->name ?? $comment->guest_name ?? '외부 리뷰어';
        $fileName      = $file->original_name;
        $projectName   = $project?->name ?? '프로젝트';
        $url           = $project
            ? route('projects.files.index', $project)
            : url('/');

        $emailOk = false;
        if (filter_var($uploader->email, FILTER_VALIDATE_EMAIL) && $project) {
            try {
                $mailable = new ProjectActivityMail(
                    $project,
                    $comment->user ?? $uploader, // actor: 댓글 작성자(게스트면 업로더 자리표시)
                    'file_comment_added',
                    $fileName,
                    $url,
                    $commenterName . '님이 의견을 남겼습니다: ' . mb_substr($comment->content, 0, 200),
                );
                Mail::to($uploader->email)->send($mailable);
                $emailOk = true;
            } catch (\Throwable $e) {
                Log::warning('[FileCommentNotify][Mail] ' . $e->getMessage());
                SystemErrorLog::record($e, 'warning');
            }
        }

        $smsOk = false;
        if ($uploader->phone) {
            $smsMsg = "[SupportWorks] {$commenterName}님이 '{$projectName}'의 '{$fileName}' 파일에 의견을 남겼습니다.";
            try {
                $smsOk = SmsService::send($uploader, $smsMsg);
            } catch (\Throwable $e) {
                Log::warning('[FileCommentNotify][SMS] ' . $e->getMessage());
            }
        }

        $row->forceFill([
            'email_sent' => $emailOk,
            'sms_sent'   => $smsOk,
        ])->save();
    }
}
