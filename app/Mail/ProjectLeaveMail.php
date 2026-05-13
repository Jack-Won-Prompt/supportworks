<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\ProjectLeave;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectLeaveMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $type  approval_request | approved | rejected
     */
    public function __construct(
        public Project      $project,
        public ProjectLeave $leave,
        public User         $actor,
        public string       $leaveUrl,
        public string       $type = 'approval_request',
    ) {}

    public function envelope(): Envelope
    {
        $subject = match ($this->type) {
            'approved' => '[' . $this->project->name . '] 휴무가 승인되었습니다',
            'rejected' => '[' . $this->project->name . '] 휴무가 반려되었습니다',
            default    => '[' . $this->project->name . '] ' . $this->actor->name . '님의 휴무 결재 요청',
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.project_leave');
    }
}
