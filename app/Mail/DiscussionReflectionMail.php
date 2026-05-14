<?php

namespace App\Mail;

use App\Models\Discussion;
use App\Models\PlanningDoc;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DiscussionReflectionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project $project,
        public Discussion $discussion,
        public User $decidedBy,
        public string $decision,
        public ?PlanningDoc $planningDoc = null,
        public ?string $note = null,
    ) {}

    public function envelope(): Envelope
    {
        $cfgFromAddr = config('mail.from.address');
        $cfgFromName = config('mail.from.name');

        $statusKr = $this->decision === 'reflected' ? '반영됨' : '반영하지 않음';
        $subject  = sprintf('[%s] 논의사항 결과 - %s', $this->project->name, $statusKr);

        return new Envelope(
            from:    new Address($cfgFromAddr, $this->decidedBy->name ?: $cfgFromName),
            replyTo: filter_var($this->decidedBy->email, FILTER_VALIDATE_EMAIL)
                ? [new Address($this->decidedBy->email, $this->decidedBy->name)]
                : [],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $planningUrl = ($this->decision === 'reflected' && $this->planningDoc)
            ? route('projects.planning.show', [$this->project, $this->planningDoc])
            : null;

        $discussionUrl = route('projects.discussions.index', $this->project) . '?open=' . $this->discussion->id;

        return new Content(view: 'emails.discussion-reflection', with: [
            'project'       => $this->project,
            'discussion'    => $this->discussion,
            'decidedBy'     => $this->decidedBy,
            'decision'      => $this->decision,
            'planningDoc'   => $this->planningDoc,
            'note'          => $this->note,
            'planningUrl'   => $planningUrl,
            'discussionUrl' => $discussionUrl,
        ]);
    }
}
