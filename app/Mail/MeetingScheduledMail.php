<?php

namespace App\Mail;

use App\Models\MeetingMinute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingScheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $title;
    public string $dateLabel;
    public string $location;
    public string $agenda;
    public string $organizerName;
    public string $attendeeNames;

    public function __construct(
        public MeetingMinute $minute,
        public string        $recipientName,
        public bool          $isUpdate = false,
        public string        $icsContent = '',
    ) {
        $this->title         = (string) $minute->title;
        $this->dateLabel     = optional($minute->meeting_date)->format('Y년 m월 d일 (D) H:i') ?? '-';
        $this->location      = (string) ($minute->location ?? '');
        $this->agenda        = trim((string) ($minute->agenda ?? ''));
        $this->organizerName = (string) ($minute->author->name ?? '-');
        $this->attendeeNames = $minute->attendees->pluck('name')->filter()->join(', ');
    }

    public function envelope(): Envelope
    {
        $prefix = $this->isUpdate ? '회의 일정 변경' : '회의 일정 안내';

        return new Envelope(
            subject: "[SupportWorks] {$prefix}: {$this->title}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.meeting-scheduled');
    }

    public function attachments(): array
    {
        if ($this->icsContent === '') {
            return [];
        }

        return [
            Attachment::fromData(fn() => $this->icsContent, 'meeting.ics')
                ->withMime('text/calendar; charset=UTF-8; method=REQUEST'),
        ];
    }
}
