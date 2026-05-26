<?php

namespace App\Models\Maint;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SR 요청 첨부파일.
 *   - private 디스크 (storage/app/maint-attachments/{request_id}/...) 에 저장
 *   - 다운로드는 서명 URL 로만 접근 (controller 가 ACL 검사 + Storage::download)
 *   - 등록 후 삭제 불가 (정책상 — 컨트롤러에서 destroy 라우트 미제공)
 */
class MaintRequestAttachment extends Model
{
    protected $table = 'maint_request_attachments';

    protected $fillable = [
        'request_id', 'uploaded_by',
        'original_name', 'disk', 'path', 'size', 'mime',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintRequest::class, 'request_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFormattedSizeAttribute(): string
    {
        $b = (int) $this->size;
        if ($b < 1024) return $b . 'B';
        if ($b < 1024 * 1024) return round($b / 1024, 1) . 'KB';
        return round($b / (1024 * 1024), 1) . 'MB';
    }
}
