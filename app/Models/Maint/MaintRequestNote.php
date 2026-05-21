<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintRequestNote extends Model
{
    protected $fillable = ['request_id', 'note_type', 'body'];

    public function request(): BelongsTo
    {
        return $this->belongsTo(MaintRequest::class, 'request_id');
    }
}
