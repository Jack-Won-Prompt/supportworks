<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ComposeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $senderName;
    public string $senderEmail;
    public string $emailSubject;
    public string $emailBody;
    public ?string $recipientName;
    public array $attachmentsInfo;

    /**
     * @param array<int, array{path:string, name:string, mime?:string}> $attachmentsInfo
     */
    public function __construct(
        User $sender,
        string $subject,
        string $body,
        ?string $recipientName = null,
        array $attachmentsInfo = [],
    ) {
        $this->senderName      = $sender->name ?? '';
        $this->senderEmail     = $sender->email ?? '';
        $this->emailSubject    = $subject;
        $this->emailBody       = $body;
        $this->recipientName   = $recipientName;
        $this->attachmentsInfo = $attachmentsInfo;
    }

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
        return new Content(view: 'emails.compose');
    }

    public function attachments(): array
    {
        return array_map(
            fn ($a) => Attachment::fromPath($a['path'])->as($a['name'])->withMime($a['mime'] ?? null),
            $this->attachmentsInfo,
        );
    }
}
