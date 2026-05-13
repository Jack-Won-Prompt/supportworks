<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $inquirySubject;
    public string $statusLabel;
    public string $statusKey;          // active | closed
    public string $adminName;
    public string $customerName;
    public string $inquiryUrl;
    public string $changedAt;
    public bool   $isClosed;

    public function __construct(
        public Conversation $conversation,
        public User         $customer,
        string              $statusKey,
        string              $statusLabel,
        string              $adminName,
    ) {
        $this->inquirySubject = (string) ($conversation->name ?? '문의');
        $this->statusKey      = $statusKey;
        $this->statusLabel    = $statusLabel;
        $this->adminName      = $adminName;
        $this->customerName   = $customer->name ?? '';
        $this->isClosed       = $statusKey === 'closed';
        $this->changedAt      = now()->format('Y-m-d H:i');
        $this->inquiryUrl     = rtrim(config('app.url'), '/')
            . route('inquiry.show', $conversation->id, false);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SupportWorks] 문의 상태 변경({$this->statusLabel}): {$this->inquirySubject}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inquiry-status');
    }
}
