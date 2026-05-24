<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyGroup extends Model
{
    protected $fillable = ['name', 'code', 'description', 'is_active', 'uses_withworks', 'features'];

    protected $casts = [
        'is_active'      => 'boolean',
        'uses_withworks' => 'boolean',
        'features'       => 'array',
    ];

    public const FEATURE_KEYS = [
        'dashboard'      => '홈/대시보드',
        'my_projects'    => '내 프로젝트',
        'calendar'       => '캘린더',
        'messages'       => '메시지',
        'team'           => '팀',
        'sr'             => 'SR 접수',
        'meeting_minutes'=> '회의록',
        'teams'          => 'Teams',
        'ai_chat'        => '웍스 채팅',
        'ai_agent'       => '웍스 에이전트',
        'prompt_builder' => 'Prompt Builder',
        'prompt_agent'   => '프롬프트 Agent',
        'community'      => '커뮤니티',
        'inquiry'        => '문의하기',
        'tasks'          => 'Tasks',
        'action_items'   => 'Action 아이템',
        'memos'          => '메모',
        // 프로젝트 네비게이션 항목
        'requirements'   => '요구사항',
        'planning'       => '프로젝트 기획서',
        'schedules'      => '프로젝트 일정',
        'gantt'          => '프로젝트 간트',
        'qa'             => '프로젝트 Q&A',
        'issues'         => '프로젝트 이슈',
        'files'          => '프로젝트 파일',
        'weekly_reports' => '프로젝트 주간 보고',
        'leaves'         => '프로젝트 휴무',
    ];

    public function hasFeature(string $key): bool
    {
        $features = $this->features;
        if ($features === null) return true;
        // ai_chat / ai_agent: 기존 'ai' 키로 저장된 설정을 폴백으로 사용
        if (in_array($key, ['ai_chat', 'ai_agent'], true) && !array_key_exists($key, $features)) {
            return (bool) ($features['ai'] ?? true);
        }
        return (bool) ($features[$key] ?? true);
    }

    public function adminAccesses(): HasMany
    {
        return $this->hasMany(AdminCompanyGroupAccess::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_company_group_access', 'company_group_id', 'admin_user_id')
            ->withPivot('can_manage_users', 'can_view_chats')
            ->withTimestamps();
    }
}
