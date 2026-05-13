<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAnalysis extends Model
{
    protected $fillable = ['message_id', 'result'];
    protected $casts    = ['result' => 'array'];
}
