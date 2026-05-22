<?php

namespace App\Models\Maint;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintRequestImageComment extends Model
{
    protected $fillable = [
        'maint_request_id', 'image_url', 'user_id', 'body',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintRequest::class, 'maint_request_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
