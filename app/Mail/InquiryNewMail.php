<?php

namespace App\Mail;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InquiryNewMail extends Mailable
{
    use Queueable, SerializesModels;

    public string  $inquirerName;
    public string  $inquirerEmail;
    public string  $companyName;
    public string  $inquirySubject;
    public string  $bodyPreview;
    public string  $inquiryUrl;
    public string  $createdAt;

    public function __construct(
        public Conversation $conversation,
        public User         $inquirer,
        string              $bodyText,
    ) {
        $this->inquirerName  = $inquirer->name  ?? '알 수 없음';
        $this->inquirerEmail = $inquirer->email ?? '';
        $this->companyName   = $inquirer->company ?? ($inquirer->companyGroup->name ?? '');
        $this->inquirySubject = (string) ($conversation->name ?? '문의');
        $this->bodyPreview   = mb_strimwidth(trim(strip_tags($bodyText)), 0, 300, '...', 'UTF-8');
        $this->createdAt     = optional($conversation->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
        $this->inquiryUrl    = rtrim(config('app.url'), '/')
            . route('admin.inquiries.show', $conversation->id, false);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SupportWorks] 신규 문의 등록: {$this->inquirerName} — {$this->inquirySubject}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.inquiry-new');
    }
}
