<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChatFileShareMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $fileName,
        public string $emailSubject,
        public string $filePath,
    ) {}

    public function envelope(): Envelope
    {
        $cfgFromAddr = config('mail.from.address');
        $cfgFromName = config('mail.from.name');

        return new Envelope(
            from:    new Address($cfgFromAddr, $this->senderName ?: $cfgFromName),
            replyTo: filter_var($this->senderEmail, FILTER_VALIDATE_EMAIL)
                ? [new Address($this->senderEmail, $this->senderName)]
                : [],
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.chat-file-share', with: [
            'senderName' => $this->senderName,
            'fileName'   => $this->fileName,
        ]);
    }

    public function attachments(): array
    {
        return [Attachment::fromPath($this->filePath)->as($this->fileName)];
    }
}
