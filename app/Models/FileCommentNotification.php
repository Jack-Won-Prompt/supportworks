<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileCommentNotification extends Model
{
    protected $fillable = [
        'project_file_id', 'user_id', 'sent_date',
        'email_sent', 'sms_sent', 'sent_at',
    ];

    protected $casts = [
        'sent_date'  => 'date',
        'email_sent' => 'boolean',
        'sms_sent'   => 'boolean',
        'sent_at'    => 'datetime',
    ];
}
