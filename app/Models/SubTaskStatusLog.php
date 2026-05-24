<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubTaskStatusLog extends Model
{
    protected $fillable = ['sub_task_id', 'user_id', 'old_status', 'new_status', 'reason'];
}
