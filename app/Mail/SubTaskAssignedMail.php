<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\SubTask;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubTaskAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $projectName;
    public string $taskTitle;
    public ?string $startDate;
    public ?string $endDate;
    public string $statusLabel;
    public string $assignerName;
    public string $taskUrl;

    public function __construct(
        User $recipient,
        Project $project,
        SubTask $subTask,
        ?User $assigner,
        string $statusLabel,
    ) {
        $this->recipientName = $recipient->name ?? '';
        $this->projectName   = $project->name ?? '';
        $this->taskTitle     = $subTask->title ?? '';
        $this->startDate     = $subTask->start_date?->format('Y-m-d');
        $this->endDate       = $subTask->end_date?->format('Y-m-d');
        $this->statusLabel   = $statusLabel;
        $this->assignerName  = $assigner?->name ?? '시스템';
        $this->taskUrl       = route('projects.schedules.index', ['project' => $project->id]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[SupportWorks] 일정 담당자 지정: ' . $this->taskTitle);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.sub-task-assigned');
    }
}
