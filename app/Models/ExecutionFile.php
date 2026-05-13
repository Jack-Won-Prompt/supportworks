<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ExecutionFile extends Model
{
    protected $fillable = ['execution_id', 'type', 'file_path', 'file_name', 'file_size', 'mime_type'];

    public function execution()
    {
        return $this->belongsTo(PromptExecution::class, 'execution_id');
    }

    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    public function formattedSize(): string
    {
        $bytes = $this->file_size;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}
