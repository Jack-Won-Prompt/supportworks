<?php

namespace App\Models\PromptBuilder;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuilderVersion extends Model
{
    protected $table = 'pb_builder_versions';

    protected $fillable = [
        'builder_id', 'version_number', 'content',
        'created_by_type', 'created_by_user_id', 'change_reason',
        'change_description', 'triggered_by', 'changes_diff',
        'is_reverted', 'reverted_to_version',
    ];

    protected $casts = [
        'changes_diff' => 'array',
        'is_reverted' => 'boolean',
    ];

    public function builder(): BelongsTo
    {
        return $this->belongsTo(Builder::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
