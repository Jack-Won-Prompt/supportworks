<?php

namespace App\Services\PlanApplication;

class PlanApplicationResult
{
    /** @var array{requirement_id: int, application_id: int}[] */
    public array $applied = [];

    /** @var array{requirement_id: int, reason: string, applied_at: ?string}[] */
    public array $skipped = [];

    /** @var array{requirement_id: int, reason: string}[] */
    public array $failed = [];

    public function toArray(): array
    {
        return [
            'applied' => $this->applied,
            'skipped' => $this->skipped,
            'failed'  => $this->failed,
        ];
    }
}
