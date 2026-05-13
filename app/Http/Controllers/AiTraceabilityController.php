<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\Agent\TraceabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AiTraceabilityController extends Controller
{
    public function __construct(private TraceabilityService $traceability) {}

    public function links(Project $project, string $type, int $id): JsonResponse
    {
        $from = $this->traceability->linksFrom($type, $id)
            ->map(fn($l) => [
                'id'          => $l->id,
                'target_type' => $l->target_type,
                'target_id'   => $l->target_id,
                'target_ref'  => $l->target_ref,
                'link_type'   => $l->link_type,
            ])->values();

        $to = $this->traceability->linksTo($type, $id)
            ->map(fn($l) => [
                'id'          => $l->id,
                'source_type' => $l->source_type,
                'source_id'   => $l->source_id,
                'source_ref'  => $l->source_ref,
                'link_type'   => $l->link_type,
            ])->values();

        return response()->json([
            'node'       => ['type' => $type, 'id' => $id],
            'links_from' => $from,
            'links_to'   => $to,
        ]);
    }

    public function impact(Project $project, string $type, int $id): JsonResponse
    {
        $results = $this->traceability->impactAnalysis($project->id, $type, $id);

        return response()->json([
            'node'    => ['type' => $type, 'id' => $id],
            'impacts' => $results,
        ]);
    }

    // ── Demo endpoints ────────────────────────────────────────────────

    public function demoLinks(string $type, int $id): JsonResponse
    {
        return response()->json([
            'node'       => ['type' => $type, 'id' => $id],
            'links_from' => [
                ['id' => 1, 'target_type' => 'screen',       'target_id' => 101, 'target_ref' => 'SCR-001',            'link_type' => 'designs'],
                ['id' => 2, 'target_type' => 'screen',       'target_id' => 102, 'target_ref' => 'SCR-002',            'link_type' => 'designs'],
                ['id' => 3, 'target_type' => 'api_endpoint', 'target_id' => 201, 'target_ref' => 'GET /api/tasks',     'link_type' => 'implements'],
                ['id' => 4, 'target_type' => 'component',    'target_id' => 301, 'target_ref' => 'TaskListComponent',  'link_type' => 'designs'],
            ],
            'links_to' => [
                ['id' => 10, 'source_type' => 'artifact',  'source_id' => 501, 'source_ref' => 'ART-001',                                    'link_type' => 'documents'],
                ['id' => 11, 'source_type' => 'code_file', 'source_id' => 601, 'source_ref' => 'app/Http/Controllers/TaskController.php',     'link_type' => 'implements'],
                ['id' => 12, 'source_type' => 'code_file', 'source_id' => 602, 'source_ref' => 'resources/views/tasks/index.blade.php',      'link_type' => 'implements'],
            ],
        ]);
    }

    public function demoImpact(string $type, int $id): JsonResponse
    {
        return response()->json([
            'node'    => ['type' => $type, 'id' => $id],
            'impacts' => [
                ['type' => 'screen',       'id' => 101, 'ref' => 'SCR-001',                                           'depth' => 1],
                ['type' => 'screen',       'id' => 102, 'ref' => 'SCR-002',                                           'depth' => 1],
                ['type' => 'api_endpoint', 'id' => 201, 'ref' => 'GET /api/tasks',                                    'depth' => 1],
                ['type' => 'component',    'id' => 301, 'ref' => 'TaskListComponent',                                 'depth' => 1],
                ['type' => 'code_file',    'id' => 601, 'ref' => 'app/Http/Controllers/TaskController.php',           'depth' => 2],
                ['type' => 'code_file',    'id' => 602, 'ref' => 'resources/views/tasks/index.blade.php',             'depth' => 2],
                ['type' => 'code_file',    'id' => 603, 'ref' => 'resources/js/components/TaskList.vue',              'depth' => 3],
                ['type' => 'artifact',     'id' => 701, 'ref' => 'ART-테스트케이스-001',                              'depth' => 3],
            ],
        ]);
    }
}
