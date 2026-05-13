<?php

namespace App\Services\Agent;

use App\Models\Agent\AiAgentPlanningTemplate;

class PlanningTemplateService
{
    /**
     * Returns the active default template, or any active template as fallback.
     */
    public function getActive(): ?AiAgentPlanningTemplate
    {
        return AiAgentPlanningTemplate::getDefault()
            ?? AiAgentPlanningTemplate::getActive();
    }

    /**
     * Returns the template blade file path relative to resources/templates/.
     */
    public function getTemplatePath(AiAgentPlanningTemplate $template): string
    {
        return $template->template_path ?? 'planning/standard_v1.md.blade.php';
    }

    /**
     * Returns all sections from the template flattened with metadata.
     * Used by the preview UI to show which sections have data vs need 웍스.
     *
     * @return array<int, array{id: string, title: string, type: string, parent_title: string}>
     */
    public function getFlatSubsections(AiAgentPlanningTemplate $template): array
    {
        $flat = [];
        foreach ($template->getSections() as $section) {
            foreach ($section['subsections'] ?? [] as $sub) {
                $flat[] = [
                    'id'           => $sub['id'],
                    'title'        => $sub['title'],
                    'type'         => $sub['type'] ?? 'data_injection',
                    'parent_id'    => $section['id'],
                    'parent_title' => $section['title'],
                    'variable'     => $sub['variable'] ?? null,
                    'ai_prompt_key'=> $sub['ai_prompt_key'] ?? null,
                    'iterate_over' => $sub['iterate_over'] ?? null,
                ];
            }
        }
        return $flat;
    }

    /**
     * Maps each required data key to its readiness status for the preview UI.
     *
     * @param array<string, array{ready: bool, count: int|null, label: string, route_key: string, optional: bool}> $dataStatus
     * @return array<int, array{section_id: string, section_title: string, status: string, data_key: string|null}>
     */
    public function getSectionStatuses(AiAgentPlanningTemplate $template, array $dataStatus): array
    {
        $sectionDataMap = [
            '2' => 'asis',
            '3' => 'tobe',
            '4' => 'gap',
            '5' => 'gap',
            '6' => 'screens',
        ];

        $statuses = [];
        foreach ($template->getSections() as $section) {
            $dataKey = $sectionDataMap[$section['id']] ?? null;
            $hasAi   = false;
            $allData = true;

            foreach ($section['subsections'] ?? [] as $sub) {
                $type = $sub['type'] ?? 'data_injection';
                if ($type === 'ai_generated') {
                    $hasAi = true;
                }
                if ($type === 'data_injection' && $dataKey && !($dataStatus[$dataKey]['ready'] ?? true)) {
                    $allData = false;
                }
            }

            if (!$allData) {
                $status = 'missing';
            } elseif ($hasAi) {
                $status = 'ai_pending';
            } else {
                $status = 'ready';
            }

            $statuses[] = [
                'section_id'    => $section['id'],
                'section_title' => $section['title'],
                'status'        => $status,
                'data_key'      => $dataKey,
                'optional'      => $dataKey ? ($dataStatus[$dataKey]['optional'] ?? false) : false,
            ];
        }

        return $statuses;
    }
}
