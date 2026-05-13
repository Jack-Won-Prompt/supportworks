<?php

namespace App\Services\PlanApplication\Templates;

use Illuminate\Support\Collection;

class DefaultRequirementTemplate implements TemplateInterface
{
    public function name(): string
    {
        return 'default';
    }

    public function displayName(): string
    {
        return '기본 템플릿';
    }

    public function render(Collection $requirements, array $options = []): string
    {
        $date      = now()->format('Y-m-d');
        $siMode    = $options['si_mode'] ?? false;
        $appliedBy = $options['applied_by'] ?? null;

        $blocks = [];

        foreach ($requirements as $req) {
            $lines   = [];
            $lines[] = "## 요구사항: {$req->title} ({$date} 적용)";
            $lines[] = '';

            $meta   = [];
            $meta[] = '- **카테고리**: ' . ($req->category_label ?? $req->category);
            $meta[] = '- **우선순위**: ' . ($req->priority_label ?? $req->priority);

            if ($siMode && $req->requirement_type) {
                $meta[] = '- **유형**: ' . ($req->type_label ?? $req->requirement_type);
            }

            if ($req->source_ref) {
                $meta[] = '- **출처**: ' . $req->source_ref;
            }

            $lines = array_merge($lines, $meta);
            $lines[] = '';

            if ($req->description) {
                $lines[] = $req->description;
                $lines[] = '';
            }

            if ($appliedBy) {
                $lines[] = "*적용자: {$appliedBy} · {$date}*";
            }

            while (end($lines) === '') array_pop($lines);

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n---\n\n", $blocks);
    }
}
