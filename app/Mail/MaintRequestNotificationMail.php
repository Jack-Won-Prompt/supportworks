<?php

namespace App\Mail;

use App\Models\Maint\MaintRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintRequestNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $eventLabel;        // '등록' 또는 '수정'
    public string $companyName;
    public string $summary;
    public string $priorityLabel;
    public string $statusLabel;
    public string $coloAssignee;
    public string $devAssignee;
    public string $detailUrl;
    public string $createdAt;

    /** @var array<int, string> */
    public array $recipients;

    /**
     * @param MaintRequest $request   대상 SR
     * @param array<int, string> $recipients  To 이메일 목록
     * @param string $eventLabel  '등록' 또는 '수정'
     */
    public function __construct(public MaintRequest $request, array $recipients, string $eventLabel)
    {
        $this->recipients    = $recipients;
        $this->eventLabel    = $eventLabel;
        $this->companyName   = $request->companyGroup?->name ?? '';
        $this->summary       = (string) ($request->summary ?? '');
        $this->priorityLabel = self::priorityLabel($request->priority);
        $this->statusLabel   = self::statusLabel($request->status);
        $this->coloAssignee  = $request->coloUser?->name ?? '';
        $this->devAssignee   = $request->assignee?->name ?? '';
        $this->detailUrl     = rtrim(config('app.url'), '/')
            . route('maint-requests.show', $request->id, false);
        $this->createdAt     = optional($request->updated_at ?? $request->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
    }

    public function envelope(): Envelope
    {
        $subject = sprintf('[SR #%d %s] %s', $this->request->id, $this->eventLabel, $this->summary);
        return new Envelope(
            subject: $subject,
            to: $this->recipients,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.maint-request-notification');
    }

    private static function priorityLabel(?string $p): string
    {
        return [
            'normal' => '일반', 'urgent' => '긴급', 'critical' => '최긴급', 'recheck' => '재확인',
        ][$p] ?? ($p ?? '');
    }

    private static function statusLabel(?string $s): string
    {
        return [
            'draft' => '작성중', 'requested' => '요청', 'planned' => '진행예정', 'in_progress' => '진행중',
            'additional_dev' => '추가 개발',
            'pending_check' => '확인대기', 'discussion_needed' => '논의필요', 'on_hold' => '보류',
            'awaiting_file' => '파일대기', 'replied' => '답변완료', 'review_requested' => '검토요청',
            'review_again' => '재확인', 'completed' => '완료',
        ][$s] ?? ($s ?? '');
    }
}
