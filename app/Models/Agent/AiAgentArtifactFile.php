<?php

namespace App\Models\Agent;

use App\Models\User;
use App\Services\Agent\Parsers\ParsedFileContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAgentArtifactFile extends Model
{
    protected $table = 'ai_agent_artifact_files';

    protected $fillable = [
        'artifact_id',
        'file_name',
        'file_type',
        'file_size',
        'mime_type',
        'storage_path',
        'parsed_content',
        'parse_status',
        'parse_error',
        'uploaded_by',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function artifact(): BelongsTo
    {
        return $this->belongsTo(AiAgentArtifact::class, 'artifact_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeParsed(Builder $query): Builder
    {
        return $query->where('parse_status', 'completed');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('parse_status', 'pending');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('parse_status', 'failed');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isParsed(): bool
    {
        return $this->parse_status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->parse_status === 'failed';
    }

    public function isPending(): bool
    {
        return in_array($this->parse_status, ['pending', 'parsing']);
    }

    public function markParsing(): void
    {
        $this->update(['parse_status' => 'parsing']);
    }

    public function markCompleted(ParsedFileContent $result): void
    {
        $this->update([
            'parsed_content' => $result->toJson(),
            'parse_status'   => 'completed',
            'parse_error'    => null,
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'parse_status' => 'failed',
            'parse_error'  => $error,
        ]);
    }

    public function getParsedResult(): ?ParsedFileContent
    {
        if (!$this->parsed_content) {
            return null;
        }
        return ParsedFileContent::fromJson($this->parsed_content);
    }

    /**
     * 사람이 읽기 좋은 파일 크기 포맷
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
