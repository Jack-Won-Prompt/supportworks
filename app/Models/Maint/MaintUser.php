<?php

namespace App\Models\Maint;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintUser extends Model
{
    protected $fillable = ['name', 'team', 'user_id', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coloRequests(): HasMany
    {
        return $this->hasMany(MaintRequest::class, 'colo_user_id');
    }

    public function assignedRequests(): HasMany
    {
        return $this->hasMany(MaintRequest::class, 'assignee_id');
    }

    public function scopeColo($query)
    {
        return $query->where('team', 'colo');
    }

    public function scopeWithworks($query)
    {
        return $query->where('team', 'withworks');
    }
}
