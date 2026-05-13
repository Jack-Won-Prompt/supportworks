<?php

namespace App\Mail;

use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AiOutputMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AiMessage $message,
        public User $user,
        public string $sessionTitle,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[웍스 Agent] {$this->sessionTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ai_output',
        );
    }
}
