<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminLoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['admin_user_id', 'login_id', 'ip_address', 'result'];

    protected $casts = ['created_at' => 'datetime'];

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
