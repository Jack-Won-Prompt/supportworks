<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileVersion extends Model
{
    protected $fillable = [
        'project_file_id', 'version', 'original_name', 'stored_name',
        'path', 'converted_pdf_path', 'mime_type', 'size', 'uploaded_by', 'change_note',
    ];

    public function file()
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function formattedSize(): string
    {
        if (!$this->size) return '';
        $kb = $this->size / 1024;
        return $kb >= 1024 ? round($kb / 1024, 1) . ' MB' : round($kb, 0) . ' KB';
    }
}
