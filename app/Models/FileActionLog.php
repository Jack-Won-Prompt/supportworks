<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileActionLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['project_file_id', 'user_id', 'action', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
