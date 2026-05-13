<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'subject_label', 'changes', 'ip_address',
    ];

    protected $casts = [
        'changes'    => 'array',
        'created_at' => 'datetime',
    ];

    // 민감 필드 — updated diff에서 제외
    private const SKIP_FIELDS = [
        'updated_at', 'created_at', 'password',
        'remember_token', 'email_verified_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ── 모델 → 화면(기능 영역) 맵 ────────────────────────────
    public static array $screenLabels = [
        'App\Models\Project'           => '프로젝트',
        'App\Models\Task'              => '프로젝트',
        'App\Models\Schedule'          => '프로젝트',
        'App\Models\ProjectFile'       => '프로젝트',
        'App\Models\MeetingMinute'     => '회의록',
        'App\Models\MeetingMemo'       => '회의록',
        'App\Models\MeetingActionItem' => '회의록',
        'App\Models\ActionItem'        => '액션 아이템',
        'App\Models\Question'          => 'Q&A',
        'App\Models\Answer'            => 'Q&A',
        'App\Models\PlanningDoc'       => '기획서',
        'App\Models\CommunityPost'     => '커뮤니티',
        'App\Models\Comment'           => '커뮤니티',
        'App\Models\Memo'              => '메모',
    ];

    // ── 모델 이름 → 한국어 레이블 맵 ──────────────────────────
    public static array $modelLabels = [
        'App\Models\Project'           => '프로젝트',
        'App\Models\Task'              => '태스크',
        'App\Models\MeetingMinute'     => '회의록',
        'App\Models\MeetingMemo'       => '회의 메모',
        'App\Models\MeetingActionItem' => '회의 액션 아이템',
        'App\Models\ActionItem'        => '액션 아이템',
        'App\Models\Question'          => 'Q&A 질문',
        'App\Models\Answer'            => 'Q&A 답변',
        'App\Models\Schedule'          => '일정',
        'App\Models\PlanningDoc'       => '기획서',
        'App\Models\CommunityPost'     => '커뮤니티 게시글',
        'App\Models\Comment'           => '댓글',
        'App\Models\Memo'              => '메모',
        'App\Models\ProjectFile'       => '프로젝트 파일',
    ];

    public function screenName(): string
    {
        return self::$screenLabels[$this->subject_type] ?? '-';
    }

    public function modelLabel(): string
    {
        return self::$modelLabels[$this->subject_type]
            ?? class_basename($this->subject_type);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'created' => '생성',
            'updated' => '수정',
            'deleted' => '삭제',
            default   => $this->action,
        };
    }

    public function actionColor(): string
    {
        return match ($this->action) {
            'created' => 'emerald',
            'updated' => 'blue',
            'deleted' => 'red',
            default   => 'gray',
        };
    }

    // ── 정적 기록 메서드 (Trait에서 호출) ────────────────────
    public static function record(string $action, Model $model, array $changes = []): void
    {
        $userId = auth()->id();
        if (!$userId) return;

        $label = null;
        foreach (['title', 'name', 'subject', 'content'] as $field) {
            if (!empty($model->$field)) {
                $label = mb_substr((string) $model->$field, 0, 200);
                break;
            }
        }

        $filtered = array_filter(
            $changes,
            fn($k) => !in_array($k, self::SKIP_FIELDS, true),
            ARRAY_FILTER_USE_KEY
        );

        self::create([
            'user_id'       => $userId,
            'action'        => $action,
            'subject_type'  => get_class($model),
            'subject_id'    => $model->getKey(),
            'subject_label' => $label,
            'changes'       => $filtered ?: null,
            'ip_address'    => request()?->ip(),
        ]);
    }
}
