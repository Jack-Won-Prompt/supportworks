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
    public ?string $signupUrl;

    /**
     * @param array<int, array{path:string, name:string, mime?:string}> $attachmentsInfo
     * @param ?string $signupUrl 미가입 수신자에게 노출할 가입 CTA URL (있으면 메일 하단에 버튼 표시)
     */
    public function __construct(
        User $sender,
        string $subject,
        string $body,
        ?string $recipientName = null,
        array $attachmentsInfo = [],
        ?string $signupUrl = null,
    ) {
        $this->senderName      = $sender->name ?? '';
        $this->senderEmail     = $sender->email ?? '';
        $this->emailSubject    = $subject;
        $this->emailBody       = $body;
        $this->recipientName   = $recipientName;
        $this->attachmentsInfo = $attachmentsInfo;
        $this->signupUrl       = $signupUrl;
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
