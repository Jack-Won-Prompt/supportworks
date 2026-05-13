<?php

namespace App\Mail;

use App\Models\Discussion;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DiscussionShareMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $projectName;
    public string $authorName;
    public string $discussionTitle;
    public string $contentPreview;
    public string $url;

    public function __construct(
        public Project    $project,
        public Discussion $discussion,
        public User       $author,
        public User       $recipient,
    ) {
        $this->projectName     = $project->name ?? '';
        $this->authorName      = $author->name  ?? '';
        $this->discussionTitle = $discussion->title ?? '';
        $this->contentPreview  = mb_strimwidth(trim(strip_tags($discussion->content ?? '')), 0, 300, '...', 'UTF-8');
        $this->url             = rtrim(config('app.url'), '/')
            . route('projects.discussions.index', $project, false)
            . '?open=' . $discussion->id;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[SupportWorks] 논의 공유: {$this->discussionTitle}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.discussion-share');
    }
}
