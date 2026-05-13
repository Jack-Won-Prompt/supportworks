<?php

namespace App\Mail;

use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProjectActivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Project  $project,
        public User     $actor,
        public string   $eventType,
        public string   $entityTitle,
        public string   $url,
        public ?string  $reviewMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[' . $this->project->name . '] ' . $this->eventLabel());
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.project_activity',
            with: [
                'eventLabel'    => $this->eventLabel(),
                'eventIcon'     => $this->eventIcon(),
                'actionColor'   => $this->actionColor(),
                'actionBadge'   => $this->actionBadge(),
                'hasLink'       => $this->hasLink(),
                'reviewMessage' => $this->reviewMessage,
            ],
        );
    }

    public function eventLabel(): string
    {
        return match ($this->eventType) {
            'schedule_created' => '일정이 등록되었습니다',
            'schedule_updated' => '일정이 수정되었습니다',
            'schedule_deleted' => '일정이 삭제되었습니다',
            'file_uploaded'         => '파일이 등록되었습니다',
            'file_deleted'          => '파일이 삭제되었습니다',
            'file_review_requested' => '파일 검토 요청이 도착했습니다',
            'file_comment_added'    => '파일에 의견이 등록되었습니다',
            'question_created' => 'Q&A가 등록되었습니다',
            'question_updated' => 'Q&A가 수정되었습니다',
            'question_deleted' => 'Q&A가 삭제되었습니다',
            'answer_created'       => '답변이 등록되었습니다',
            'answer_deleted'       => '답변이 삭제되었습니다',
            'maintenance_created'  => 'SR 접수가 등록되었습니다',
            'maintenance_deleted'  => 'SR 접수가 삭제되었습니다',
            'maintenance_replied'  => 'SR 접수 답글이 등록되었습니다',
            default                => '프로젝트 활동이 발생했습니다',
        };
    }

    public function eventIcon(): string
    {
        return match (true) {
            str_starts_with($this->eventType, 'schedule') => '📅',
            str_starts_with($this->eventType, 'file')     => '📁',
            str_starts_with($this->eventType, 'question')    => '❓',
            str_starts_with($this->eventType, 'answer')      => '💬',
            str_starts_with($this->eventType, 'maintenance') => '🔧',
            default                                        => '🔔',
        };
    }

    public function actionColor(): string
    {
        return match (true) {
            str_ends_with($this->eventType, '_deleted') => '#dc2626',
            str_ends_with($this->eventType, '_updated') => '#d97706',
            default                                      => '#7c3aed',
        };
    }

    public function actionBadge(): string
    {
        return match (true) {
            str_ends_with($this->eventType, '_deleted') => '삭제',
            str_ends_with($this->eventType, '_updated') => '수정',
            default                                      => '신규',
        };
    }

    public function hasLink(): bool
    {
        return !str_ends_with($this->eventType, '_deleted');
    }
}
