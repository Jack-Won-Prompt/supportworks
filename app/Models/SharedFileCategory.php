<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SharedFileCategory extends Model
{
    protected $fillable = [
        'company_group_id', 'name', 'color', 'sort_order',
    ];

    public function companyGroup(): BelongsTo
    {
        return $this->belongsTo(CompanyGroup::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(SharedFile::class, 'category_id');
    }
}
