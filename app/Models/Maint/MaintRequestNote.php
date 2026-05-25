<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintRequestNote extends Model
{
    protected $fillable = ['request_id', 'note_type', 'body', 'parent_id'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintRequest::class, 'request_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** 답글 (1단계 트리 — 답글의 답글은 허용하지 않음) */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest('id');
    }
}
