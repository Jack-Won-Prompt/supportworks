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

class DeliverableApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $approveUrl;
    public string $requesterName;
    public string $deliverableName;
    public string $stepTitle;
    public int    $approvalId;

    public function __construct(
        public Deliverable         $deliverable,
        public DeliverableApproval $approval,
        public User                $approver,
        string                     $stepTitle,
        string                     $deliverableName,
    ) {
        $this->requesterName   = $approval->requester->name ?? '알 수 없음';
        $this->deliverableName = $deliverableName;
        $this->stepTitle       = $stepTitle;
        $this->approvalId      = $approval->id;
        $this->approveUrl      = rtrim(config('app.url'), '/')
            . route(
                'ai-agent.projects.deliverables.show',
                ['project' => $deliverable->project_id, 'typeId' => $deliverable->type_id],
                false
            )
            . '?step=' . $approval->step_order
            . '&approval_id=' . $approval->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SupportWorks] 산출물 승인 요청: {$this->deliverableName} {$this->stepTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deliverable-approval',
        );
    }
}
