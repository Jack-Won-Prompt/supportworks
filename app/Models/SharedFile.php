<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedFile extends Model
{
    protected $fillable = [
        'company_group_id', 'category_id', 'uploaded_by',
        'original_name', 'stored_name', 'path', 'mime_type', 'size', 'description',
        'is_personal',
    ];

    protected $casts = [
        'size'        => 'integer',
        'is_personal' => 'boolean',
    ];

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SharedFileCategory::class, 'category_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** 확장자 기반 아이콘 이모지 */
    public function getIconAttribute(): string
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return match (true) {
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']) => '🖼️',
            in_array($ext, ['mp4', 'mov', 'avi', 'mkv', 'webm'])                => '🎬',
            $ext === 'pdf'                                                      => '📕',
            in_array($ext, ['doc', 'docx'])                                     => '📝',
            in_array($ext, ['xls', 'xlsx', 'csv'])                              => '📊',
            in_array($ext, ['ppt', 'pptx'])                                     => '📑',
            in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz'])                   => '🗜️',
            default                                                             => '📎',
        };
    }

    /** 사람이 읽는 파일 크기 */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 1) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
