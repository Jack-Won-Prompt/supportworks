<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = ['version', 'download_url', 'release_notes', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
