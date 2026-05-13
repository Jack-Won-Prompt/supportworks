<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    protected $fillable = ['email', 'phone', 'message', 'project_ids', 'token', 'invited_by', 'company_group_id', 'accepted_at'];

    protected $casts = [
        'accepted_at' => 'datetime',
        'project_ids' => 'array',
    ];

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function isPending(): bool
    {
        return is_null($this->accepted_at);
    }
}
