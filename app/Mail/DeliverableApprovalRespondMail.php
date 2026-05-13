<?php

namespace App\Mail;

use App\Models\Agent\Deliverable;
use App\Models\Agent\DeliverableApproval;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliverableApprovalRespondMail extends Mailable
{
    use Queueable, SerializesModels;

    public bool   $isApproved;
    public string $approverName;
    public string $requesterName;
    public string $deliverableName;
    public string $stepTitle;
    public string $deliverableUrl;
    public ?string $note;

    public function __construct(
        public Deliverable         $deliverable,
        public DeliverableApproval $approval,
        public User                $requester,
        string                     $stepTitle,
        string                     $deliverableName,
    ) {
        $this->isApproved      = $approval->status === 'approved';
        $this->approverName    = $approval->approver->name  ?? '알 수 없음';
        $this->requesterName   = $requester->name           ?? '알 수 없음';
        $this->deliverableName = $deliverableName;
        $this->stepTitle       = $stepTitle;
        $this->note            = $approval->note ?? null;
        $this->deliverableUrl  = rtrim(config('app.url'), '/')
            . route(
                'ai-agent.projects.deliverables.show',
                ['project' => $deliverable->project_id, 'typeId' => $deliverable->type_id],
                false
            )
            . '?step=' . $approval->step_order;
    }

    public function envelope(): Envelope
    {
        $label = $this->isApproved ? '승인 완료' : '반려';

        return new Envelope(
            subject: "[SupportWorks] 산출물 {$label}: {$this->deliverableName} {$this->stepTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deliverable-approval-respond',
        );
    }
}
