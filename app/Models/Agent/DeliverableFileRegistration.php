<?php

namespace App\Models\Agent;

use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliverableFileRegistration extends Model
{
    protected $table = 'deliverable_file_registrations';

    protected $fillable = [
        'deliverable_id',
        'project_file_id',
        'file_version',
        'lang',
        'change_note',
        'created_by',
    ];

    protected $casts = [
        'file_version' => 'integer',
    ];

    public function deliverable(): BelongsTo
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function projectFile(): BelongsTo
    {
        return $this->belongsTo(ProjectFile::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
