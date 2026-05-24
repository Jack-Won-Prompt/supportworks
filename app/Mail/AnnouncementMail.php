<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $announcementTitle;
    public string $announcementBody;
    public string $announcementType;
    public ?string $recipientName;

    public function __construct(
        string $title,
        string $body,
        string $type = 'info',
        ?string $recipientName = null,
    ) {
        $this->announcementTitle = $title;
        $this->announcementBody  = $body;
        $this->announcementType  = $type;
        $this->recipientName     = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[공지] ' . $this->announcementTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.announcement');
    }
}
