<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlanningContentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string  $mailSubject,
        public string  $content,
        public string  $type,
        public string  $projectName,
        public string  $docTitle,
        public ?string $attachmentData = null,
        public ?string $attachmentName = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "[SupportWorks] {$this->mailSubject}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.planning_content');
    }

    public function attachments(): array
    {
        if ($this->attachmentData && $this->attachmentName) {
            return [
                Attachment::fromData(fn () => $this->attachmentData, $this->attachmentName)
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}
