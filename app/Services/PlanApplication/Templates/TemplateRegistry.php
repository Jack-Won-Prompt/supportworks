<?php

namespace App\Services\PlanApplication\Templates;

class TemplateRegistry
{
    /** @var TemplateInterface[] */
    private array $templates = [];

    public function __construct()
    {
        $this->register(new DefaultRequirementTemplate());
    }

    public function register(TemplateInterface $template): void
    {
        $this->templates[$template->name()] = $template;
    }

    public function get(string $name): TemplateInterface
    {
        return $this->templates[$name]
            ?? $this->templates['default']
            ?? throw new \InvalidArgumentException("템플릿을 찾을 수 없습니다: {$name}");
    }

    /** @return array<string, string> name => displayName */
    public function all(): array
    {
        return array_map(fn($t) => $t->displayName(), $this->templates);
    }
}
