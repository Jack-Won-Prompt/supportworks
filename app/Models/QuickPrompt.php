<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuickPrompt extends Model
{
    protected $fillable = [
        'user_id',
        'original_input',
        'refined_prompt',
        'base_refined_prompt',
        'append_confirmation',
        'applied_suffix_ids',
        'provider_used',
        'model_used',
        'fallback_reason',
        'elapsed_ms',
    ];

    protected $casts = [
        'append_confirmation' => 'boolean',
        'applied_suffix_ids'  => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
