<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $inviteUrl;
    public string $inviterName;
    public ?string $inviteMessage;
    public array $invitedProjects;

    public function __construct(public Invitation $invitation, array $invitedProjects = [], ?string $inviterNameOverride = null)
    {
        $this->inviteUrl       = rtrim(config('app.url'), '/') . route('team.accept', $invitation->token, false);
        $this->inviterName     = $inviterNameOverride ?? $invitation->inviter?->name ?? 'SupportWorks';
        $this->inviteMessage   = $invitation->message;
        $this->invitedProjects = $invitedProjects;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('emails.invite_subject', ['inviter' => $this->inviterName]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitation',
        );
    }
}
