<?php

namespace App\Mail;

use App\Models\AdminInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;
    public string $inviterName;
    public string $roleName;

    public function __construct(
        public AdminInvitation $invitation,
        public string $rawToken,
    ) {
        $this->acceptUrl  = rtrim(config('app.url'), '/') . '/admin/invite/accept/' . $rawToken;
        $this->inviterName = $invitation->invitedBy->name;
        $this->roleName   = match ($invitation->role) {
            'admin'         => '관리자',
            'operator'      => '운영자',
            'support_agent' => '상담원',
            default         => $invitation->role,
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[SupportWorks] 관리자 시스템 초대',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin-invitation',
        );
    }
}
