<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    protected static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            ActivityLog::record('created', $model);
        });

        static::updated(function ($model) {
            if (!$model->wasChanged()) return;

            $skip = ['updated_at', 'created_at', 'password', 'remember_token'];
            $changes = [];

            foreach ($model->getChanges() as $field => $newVal) {
                if (in_array($field, $skip, true)) continue;
                $changes[$field] = [
                    'old' => $model->getOriginal($field),
                    'new' => $newVal,
                ];
            }

            if ($changes) {
                ActivityLog::record('updated', $model, $changes);
            }
        });

        static::deleted(function ($model) {
            ActivityLog::record('deleted', $model);
        });
    }
}
