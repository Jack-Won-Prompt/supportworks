<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPageLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'route_name', 'screen_name', 'url', 'ip_address'];

    protected $casts = ['created_at' => 'datetime'];

    public const SCREEN_MAP = [
        'dashboard'                => '대시보드',
        'calendar'                 => '캘린더',
        'messages.index'           => '메시지 목록',
        'messages.show'            => '메시지 대화',
        'projects.index'           => '프로젝트 목록',
        'projects.show'            => '프로젝트 상세',
        'projects.create'          => '프로젝트 생성',
        'projects.edit'            => '프로젝트 수정',
        'projects.gantt'           => '간트 차트',
        'projects.planning.index'  => '기획서 목록',
        'projects.planning.show'   => '기획서 상세',
        'projects.questions.index' => 'Q&A 목록',
        'projects.questions.show'  => 'Q&A 상세',
        'projects.questions.create'=> 'Q&A 작성',
        'projects.questions.edit'  => 'Q&A 수정',
        'schedules.index'          => '일정 목록',
        'schedules.show'           => '일정 상세',
        'schedules.create'         => '일정 생성',
        'schedules.edit'           => '일정 수정',
        'maintenances.show'        => '유지보수 상세',
        'maintenances.detail'      => '유지보수 상세',
        'projects.files.index'     => '파일 목록',
        'projects.files.preview'   => '파일 미리보기',
        'community.index'          => '커뮤니티',
        'community.show'           => '커뮤니티 게시글',
        'memos.index'              => '메모',
        'action-items.index'       => '액션 아이템',
        'meeting-minutes.index'    => '회의록 목록',
        'meeting-minutes.show'     => '회의록 상세',
        'meeting-minutes.create'   => '회의록 작성',
        'meeting-minutes.edit'     => '회의록 수정',
        'tasks.index'              => '태스크',
        'ai.index'                 => '웍스 어시스턴트',
        'ai.prompts.index'         => '웍스 프롬프트',
        'ai.executions.index'      => '웍스 실행 이력',
        'team.index'               => '팀원 관리',
        'inquiry.index'            => '문의하기 목록',
        'inquiry.show'             => '문의하기 상세',
        'profile.edit'             => '프로필',
        'projects.members.index'   => '프로젝트 멤버',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
