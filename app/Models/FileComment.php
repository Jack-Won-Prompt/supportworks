<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileComment extends Model
{
    protected $fillable = [
        'project_file_id', 'user_id', 'guest_name', 'page', 'video_time', 'content', 'parent_id',
        'resolved', 'resolved_at', 'resolved_by', 'resolved_at_version', 'frozen_at_version',
        'reflected_at', 'reflected_by', 'applied_step_order',
    ];

    protected $casts = [
        'video_time'   => 'float',
        'resolved'     => 'boolean',
        'resolved_at'  => 'datetime',
        'reflected_at' => 'datetime',
    ];

    public function file()
    {
        return $this->belongsTo(ProjectFile::class, 'project_file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(FileComment::class, 'parent_id')->with('user')->orderBy('created_at');
    }

    public function parent()
    {
        return $this->belongsTo(FileComment::class, 'parent_id');
    }

    public function reflectedBy()
    {
        return $this->belongsTo(User::class, 'reflected_by');
    }
}
