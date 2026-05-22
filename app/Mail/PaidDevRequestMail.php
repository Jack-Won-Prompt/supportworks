<?php

namespace App\Mail;

use App\Models\Maint\MaintRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaidDevRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $companyName;
    public string $summary;
    public string $detailUrl;
    public string $days;
    public string $cost;
    public string $description;
    public string $sentAt;

    /** @var array<int, string> */
    public array $recipients;

    public function __construct(public MaintRequest $request, array $recipients, public string $requesterName)
    {
        $this->recipients   = $recipients;
        $this->companyName  = $request->companyGroup?->name ?? '';
        $this->summary      = (string) ($request->summary ?? '');
        $this->days         = number_format((int) $request->paid_dev_days) . '일';
        $this->cost         = '₩ ' . number_format((int) $request->paid_dev_cost);
        $this->description  = (string) ($request->paid_dev_description ?? '');
        $this->sentAt       = optional($request->paid_dev_sent_at)->format('Y-m-d H:i') ?? now()->format('Y-m-d H:i');
        $this->detailUrl    = rtrim(config('app.url'), '/')
            . route('maint-requests.show', $request->id, false);
    }

    public function envelope(): Envelope
    {
        $subject = sprintf('[SR #%d 추가개발 요청] %s', $this->request->id, $this->summary);
        return new Envelope(subject: $subject, to: $this->recipients);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.paid-dev-request');
    }
}
