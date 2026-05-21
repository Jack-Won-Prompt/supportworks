<?php

namespace App\Models\Maint;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintMenu extends Model
{
    protected $fillable = ['name', 'request_cnt'];

    protected $casts = [
        'request_cnt' => 'integer',
    ];

    public function requests(): HasMany
    {
        return $this->hasMany(MaintRequest::class, 'menu_id');
    }
}
