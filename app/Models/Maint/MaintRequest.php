<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintRequest extends Model
{
    protected $fillable = [
        'excel_no', 'source_sheet', 'menu_id', 'company_group_id',
        'request_date', 'priority', 'category',
        'summary', 'content', 'status',
        'ai_summary', 'ai_summary_at', 'ai_summary_context_ids',
        'progress_raw', 'colo_check_raw',
        'colo_user_id', 'assignee_id', 'assignee_raw',
        'eta', 'grid_refresh', 'completed_at',
        'paid_dev_enabled', 'paid_dev_days', 'paid_dev_cost', 'paid_dev_description', 'paid_dev_sent_at',
    ];

    protected $casts = [
        'request_date'            => 'date',
        'eta'                     => 'date',
        'completed_at'            => 'datetime',
        'excel_no'                => 'integer',
        'ai_summary_at'           => 'datetime',
        'ai_summary_context_ids'  => 'array',
        'paid_dev_enabled'        => 'boolean',
        'paid_dev_days'           => 'integer',
        'paid_dev_cost'           => 'integer',
        'paid_dev_sent_at'        => 'datetime',
    ];

    public const PRIORITIES = ['normal', 'urgent', 'recheck'];

    /**
     * SR 상태 5유형.
     *   requested      : 요청 / 작성중 (초기 등록 상태)
     *   in_progress    : 진행중 (작업 진행)
     *   additional_dev : 추가 개발 (유상 — 매니저 승인 후 진행)
     *   reviewing      : 검토 (확인·논의·보류·파일대기·답변완료 등)
     *   completed      : 완료
     */
    public const STATUSES = [
        'requested', 'in_progress', 'additional_dev', 'reviewing', 'completed',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(MaintMenu::class, 'menu_id');
    }

    public function coloUser(): BelongsTo
    {
        return $this->belongsTo(MaintUser::class, 'colo_user_id');
    }

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CompanyGroup::class, 'company_group_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(MaintUser::class, 'assignee_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(MaintRequestNote::class, 'request_id');
    }

    public function coloNotes(): HasMany
    {
        return $this->notes()->where('note_type', 'colo');
    }

    public function linkNotes(): HasMany
    {
        return $this->notes()->where('note_type', 'link');
    }
}
