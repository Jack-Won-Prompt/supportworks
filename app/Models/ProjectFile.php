<?php

namespace App\Models;

use App\Traits\LogsActivity;

use Illuminate\Database\Eloquent\Model;

class ProjectFile extends Model
{
    use LogsActivity;
    protected $fillable = [
        'project_id', 'category_id', 'schedule_id', 'sub_task_id', 'uploaded_by', 'original_name', 'stored_name',
        'path', 'converted_pdf_path', 'mime_type', 'size', 'description',
        'source_url', 'file_type', 'share_token',
    ];

    public function isUrlType(): bool
    {
        return ($this->file_type ?? 'file') === 'url';
    }

    public function isShareable(): bool
    {
        return $this->isUrlType() || $this->previewType() !== null;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function category()
    {
        return $this->belongsTo(\App\Models\ProjectFileCategory::class, 'category_id');
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\FileComment::class, 'project_file_id');
    }

    public function reviewRequests()
    {
        return $this->hasMany(ProjectFileReviewRequest::class, 'project_file_id')->with('reviewer:id,name');
    }

    public function versions()
    {
        return $this->hasMany(FileVersion::class, 'project_file_id')->orderBy('version');
    }

    public function latestVersion()
    {
        return $this->hasOne(FileVersion::class, 'project_file_id')->latestOfMany('version');
    }

    public function currentVersionNumber(): int
    {
        return (int) ($this->versions()->max('version') ?? 1);
    }

    public function getFormattedSizeAttribute(): string
    {
        if ($this->isUrlType()) return 'URL';
        $bytes = $this->size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    public function getIconAttribute(): string
    {
        if ($this->isUrlType()) return '🔗';
        $mime = $this->mime_type;
        if (str_contains($mime, 'image')) return '🖼️';
        if (str_contains($mime, 'pdf')) return '📄';
        if (str_contains($mime, 'word') || str_contains($mime, 'document')) return '📝';
        if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheet')) return '📊';
        if (str_contains($mime, 'presentation') || str_contains($mime, 'powerpoint')) return '📊';
        if (str_contains($mime, 'zip') || str_contains($mime, 'rar')) return '📦';
        return '📎';
    }

    public function getEmbedUrl(): string
    {
        $url = $this->source_url ?? '';
        if (!$url) return '';

        // Figma → 공식 embed 엔드포인트
        if (preg_match('#https?://(?:www\.)?figma\.com/(file|design|proto)/#', $url)) {
            return 'https://www.figma.com/embed?embed_host=supportworks&url=' . urlencode($url);
        }

        // Google Docs/Sheets/Slides → /preview
        if (preg_match('#https?://docs\.google\.com/(document|spreadsheets|presentation)/d/[^/]+#', $url)) {
            if (str_contains($url, '/preview') || str_contains($url, '/pub')) return $url;
            $base = preg_replace('#/(edit|view|revisions).*$#', '', $url) ?? $url;
            return $base . '/preview';
        }

        // YouTube → embed
        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        return $url;
    }

    public function previewType(): ?string
    {
        return self::previewTypeFor($this->original_name, $this->mime_type);
    }

    public static function previewTypeFor(?string $fileName, ?string $mime): ?string
    {
        $mime = $mime ?? '';
        $ext  = strtolower(pathinfo((string) $fileName, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'video/')) return 'video';
        if ($mime === 'application/pdf') return 'pdf';

        $officeMimes = [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.ms-powerpoint',
        ];
        if (in_array($mime, $officeMimes)) return 'office';

        if (in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'])) return 'office';
        if ($ext === 'pdf') return 'pdf';
        if (in_array($ext, ['mp4', 'webm', 'ogv', 'ogg', 'mov', 'm4v'])) return 'video';

        return null;
    }
}
