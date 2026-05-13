<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceFile extends Model
{
    protected $fillable = [
        'project_id', 'maintenance_id', 'uploaded_by', 'maintenance_category_id',
        'original_name', 'stored_name', 'path', 'converted_pdf_path',
        'mime_type', 'size', 'description', 'source_url', 'file_type', 'share_token',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function maintenance()
    {
        return $this->belongsTo(ProjectMaintenance::class);
    }

    public function category()
    {
        return $this->belongsTo(MaintenanceFileCategory::class, 'maintenance_category_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function comments()
    {
        return $this->hasMany(\App\Models\FileComment::class, 'project_file_id');
    }

    public function annotations()
    {
        return $this->hasMany(\App\Models\FileAnnotation::class, 'project_file_id');
    }

    public function isUrlType(): bool
    {
        return ($this->file_type ?? 'file') === 'url';
    }

    public function isShareable(): bool
    {
        return $this->isUrlType() || $this->previewType() !== null;
    }

    public function getFormattedSizeAttribute(): string
    {
        if ($this->isUrlType()) return 'URL';
        $bytes = $this->size;
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return number_format($bytes / 1048576,    2) . ' MB';
        if ($bytes >= 1024)       return number_format($bytes / 1024,       2) . ' KB';
        return $bytes . ' B';
    }

    public function getIconAttribute(): string
    {
        if ($this->isUrlType()) return '🔗';
        $mime = $this->mime_type ?? '';
        if (str_contains($mime, 'image'))        return '🖼️';
        if (str_contains($mime, 'pdf'))          return '📄';
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

        if (preg_match('#https?://(?:www\.)?figma\.com/(file|design|proto)/#', $url)) {
            return 'https://www.figma.com/embed?embed_host=supportworks&url=' . urlencode($url);
        }

        if (preg_match('#https?://docs\.google\.com/(document|spreadsheets|presentation)/d/[^/]+#', $url)) {
            if (str_contains($url, '/preview') || str_contains($url, '/pub')) return $url;
            $base = preg_replace('#/(edit|view|revisions).*$#', '', $url) ?? $url;
            return $base . '/preview';
        }

        if (preg_match('#(?:youtube\.com/watch\?v=|youtu\.be/)([A-Za-z0-9_-]{11})#', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }

        return $url;
    }

    public function previewType(): ?string
    {
        $mime = $this->mime_type ?? '';
        $ext  = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));

        if (str_starts_with($mime, 'image/')) return 'image';
        if ($mime === 'application/pdf')       return 'pdf';

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

        return null;
    }
}
