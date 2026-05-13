<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AdminCompanyGroupAccess extends Pivot
{
    protected $table = 'admin_company_group_access';

    protected $fillable = ['admin_user_id', 'company_group_id', 'can_manage_users', 'can_view_chats'];

    protected $casts = [
        'can_manage_users' => 'boolean',
        'can_view_chats'   => 'boolean',
    ];
}
