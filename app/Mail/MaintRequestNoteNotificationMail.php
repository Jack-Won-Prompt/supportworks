<?php

namespace App\Mail;

use App\Models\Maint\MaintRequest;
use App\Models\Maint\MaintRequestNote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * SR 비고/답글 등록 알림 메일.
 *   eventLabel = '비고' (최상위 비고 신규) | '답글' (parent_id 가 있는 답글)
 */
class MaintRequestNoteNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $eventLabel;
    public string $authorName;
    public string $noteBody;
    public ?string $parentBody;
    public string $summary;
    public string $companyName;
    public string $detailUrl;
    public string $postedAt;

    /** @var array<int, string> */
    public array $recipients;

    public function __construct(
        public MaintRequest     $request,
        public MaintRequestNote $note,
        array                   $recipients,
        string                  $eventLabel,
        string                  $authorName = ''
    ) {
        $this->recipients  = $recipients;
        $this->eventLabel  = $eventLabel;
        $this->authorName  = $authorName;
        $this->noteBody    = (string) $note->body;
        $this->parentBody  = $note->parent_id ? (string) ($note->parent?->body ?? '') : null;
        $this->summary     = (string) ($request->summary ?? '');
        $this->companyName = $request->companyGroup?->name ?? '';
        $this->detailUrl   = rtrim(config('app.url'), '/')
            . route('maint-requests.index', ['open' => $request->id], false);
        $this->postedAt    = optional($note->created_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
    }

    public function envelope(): Envelope
    {
        $subject = sprintf('[SR #%d %s] %s', $this->request->id, $this->eventLabel, $this->summary);
        return new Envelope(subject: $subject, to: $this->recipients);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.maint-request-note-notification');
    }
}
