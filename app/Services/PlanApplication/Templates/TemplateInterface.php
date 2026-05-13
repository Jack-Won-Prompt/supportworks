<?php

namespace App\Services\PlanApplication\Templates;

use Illuminate\Support\Collection;

interface TemplateInterface
{
    public function render(Collection $requirements, array $options = []): string;

    public function name(): string;

    public function displayName(): string;
}
