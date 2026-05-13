<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileAnnotation extends Model
{
    protected $fillable = [
        'project_file_id', 'user_id', 'guest_name', 'page', 'type', 'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function file()
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
