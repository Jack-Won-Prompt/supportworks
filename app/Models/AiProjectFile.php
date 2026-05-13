<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiProjectFile extends Model
{
    protected $fillable = [
        'user_id', 'project_id', 'session_id',
        'file_name', 'lang', 'content', 'version',
    ];

    protected $casts = [
        'version' => 'integer',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function project() { return $this->belongsTo(Project::class); }
    public function session() { return $this->belongsTo(AiSession::class); }
}
