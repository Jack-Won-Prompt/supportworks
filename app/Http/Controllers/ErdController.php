<?php

namespace App\Http\Controllers;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\SystemErrorLog;
use App\Services\Agent\ErdAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ErdController extends Controller
{
    private const CACHE_PREFIX = 'ai-agent:erd:sse:';
    private const CACHE_TTL    = 3600;

    public function __construct(
        private readonly ErdAiService $erdService,
    ) {}

    // ── 메인 페이지 ──────────────────────────────────────────────────────────

    public function index(Project $project): View
    {
        $this->authorizeProject($project);

        $context = $this->erdService->buildContext($project->id);
        $stage   = $this->resolveDevPrepStage($project);
        $artifact = AiAgentArtifact::where('project_id', $project->id)
            ->where('type', ArtifactType::ERD->value)
            ->latest('id')->first();

        $erdData   = null;
        $hasErd    = false;
        if ($artifact && !empty($artifact->content)) {
            $erdData = json_decode($artifact->content, true);
            $hasErd  = !empty($erdData['tables']);
        }

        $historyUrl = $artifact
            ? route('ai-agent.projects.artifact.versions', [$project, $artifact])
            : null;

        return view('ai-agent.dev-prep.erd.index', [
            'project'        => $project,
            'artifact'       => $artifact,
            'erdData'        => $erdData,
            'hasErd'         => $hasErd,
            'context'        => $context,
            'hasDoc'         => (bool) $context['planning_doc_id'],
            'screenCount'    => $context['screen_count'],
            'reqCount'       => $context['requirements_count'],
            'tablesCount'    => count($erdData['tables'] ?? []),
            'historyUrl'     => $historyUrl,
            'startUrl'       => route('ai-agent.projects.pre-dev.erd.generate.start', $project),
            'sseUrlTpl'      => route('ai-agent.projects.pre-dev.erd.generate.sse', [$project, 'SESSION_ID']),
            'saveUrl'        => route('ai-agent.projects.pre-dev.erd.save', $project),
            'exportUrl'      => route('ai-agent.projects.pre-dev.erd.export', $project),
            'regenerateUrl'  => route('ai-agent.projects.pre-dev.erd.regenerate', $project),
            'cancelUrlTpl'   => route('ai-agent.stream.cancel', ['sessionId' => 'SESSION_ID']),
            'pageTitle'      => 'ERD — 데이터 모델',
        ]);
    }

    // ── 생성 시작 ────────────────────────────────────────────────────────────

    public function generateStart(Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $sessionId = Str::uuid()->toString();
        Cache::put(self::CACHE_PREFIX . $sessionId, [
            'project_id' => $project->id,
            'user_id'    => (int) auth()->id(),
        ], self::CACHE_TTL);

        return response()->json(['success' => true, 'sessionId' => $sessionId]);
    }

    // ── SSE 생성 스트림 ──────────────────────────────────────────────────────

    public function generateSse(Project $project, string $sessionId): StreamedResponse
    {
        $this->authorizeProject($project);
        $session = Cache::get(self::CACHE_PREFIX . $sessionId);

        return response()->stream(function () use ($sessionId, $session, $project) {
            $this->clearOutputBuffer();

            if (!$session || $session['project_id'] !== $project->id) {
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => '세션을 찾을 수 없습니다.']);
                return;
            }

            $this->sseEvent('status', ['status' => 'STARTING', 'message' => 'ERD 생성을 시작합니다...', 'progress' => 5]);
            $startedAt = microtime(true);
            Cache::forget(self::CACHE_PREFIX . $sessionId);

            try {
                $result = $this->erdService->generate(
                    projectId:  $project->id,
                    userId:     $session['user_id'],
                    onProgress: function (array $progress) use ($startedAt) {
                        $this->sseEvent('progress', array_merge($progress, [
                            'elapsed' => round(microtime(true) - $startedAt, 1),
                        ]));
                    },
                );

                $artifact = $result['artifact'];
                $erdData  = json_decode($artifact->content, true);

                $this->sseEvent('complete', [
                    'status'       => 'COMPLETED',
                    'tables_count' => $result['tables_count'],
                    'tokens_in'    => $result['tokens_in'],
                    'tokens_out'   => $result['tokens_out'],
                    'cost_usd'     => round($result['cost'], 4),
                    'model'        => $result['model'],
                    'elapsed'      => round(microtime(true) - $startedAt, 2),
                    'artifact_id'  => $artifact->id,
                    'version'      => $artifact->version,
                    'mermaid'      => $erdData['mermaid_diagram'] ?? '',
                    'tables'       => array_values($erdData['tables'] ?? []),
                    'design_notes' => $erdData['design_notes'] ?? '',
                ]);
            } catch (\Throwable $e) {
                SystemErrorLog::record($e);
                $this->sseEvent('error', ['status' => 'ERROR', 'message' => $e->getMessage()]);
            }
        }, 200, $this->sseHeaders());
    }

    // ── 저장 (사용자 편집) ────────────────────────────────────────────────────

    public function save(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        $validated = $request->validate([
            'content' => 'required|string|min:2',
        ]);

        $artifact = $this->getArtifact($project->id);
        if (!$artifact) {
            return response()->json(['success' => false, 'message' => 'ERD 산출물을 찾을 수 없습니다.'], 404);
        }

        $artifact->updateWithVersion(
            content: $validated['content'],
            userId:  (int) auth()->id(),
            meta:    ['change_type' => 'user_edited', 'edited_at' => now()->toIso8601String()],
        );

        return response()->json(['success' => true, 'version' => $artifact->fresh()->version]);
    }

    // ── 내보내기 ─────────────────────────────────────────────────────────────

    public function export(Request $request, Project $project): \Illuminate\Http\Response
    {
        $this->authorizeProject($project);

        $format   = $request->query('format', 'mermaid');
        $artifact = $this->getArtifact($project->id);

        if (!$artifact || empty($artifact->content)) {
            abort(404, 'ERD 산출물이 없습니다.');
        }

        $erdData  = json_decode($artifact->content, true) ?? [];
        $slug     = Str::slug($project->name);
        $date     = now()->format('Ymd');

        return match ($format) {
            'sql'  => $this->exportSql($erdData, $slug, $date),
            'dbml' => $this->exportDbml($erdData, $slug, $date),
            default => $this->exportMermaid($erdData, $slug, $date),
        };
    }

    // ── 재생성 ───────────────────────────────────────────────────────────────

    public function regenerate(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($project);

        try {
            $result = $this->erdService->generate(
                projectId:  $project->id,
                userId:     (int) auth()->id(),
                onProgress: fn() => null,
            );

            $artifact = $result['artifact'];
            $erdData  = json_decode($artifact->content, true);

            return response()->json([
                'success'      => true,
                'version'      => $artifact->version,
                'tables_count' => $result['tables_count'],
                'mermaid'      => $erdData['mermaid_diagram'] ?? '',
                'tables'       => array_values($erdData['tables'] ?? []),
                'design_notes' => $erdData['design_notes'] ?? '',
                'model'        => $result['model'],
                'cost_usd'     => round($result['cost'], 4),
            ]);
        } catch (\Throwable $e) {
            SystemErrorLog::record($e);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ── Export helpers ────────────────────────────────────────────────────────

    private function exportMermaid(array $erdData, string $slug, string $date): \Illuminate\Http\Response
    {
        $mermaid  = $erdData['mermaid_diagram'] ?? 'erDiagram';
        $filename = "{$slug}-ERD-{$date}.md";
        $content  = "# ERD — 데이터 모델\n\n> 생성일: " . now()->format('Y-m-d') . "\n\n```mermaid\n{$mermaid}\n```\n";

        return response($content, 200, [
            'Content-Type'        => 'text/markdown; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportSql(array $erdData, string $slug, string $date): \Illuminate\Http\Response
    {
        $sql      = $this->buildSql($erdData['tables'] ?? []);
        $filename = "{$slug}-ERD-{$date}.sql";

        return response($sql, 200, [
            'Content-Type'        => 'application/sql; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function exportDbml(array $erdData, string $slug, string $date): \Illuminate\Http\Response
    {
        $dbml     = $this->buildDbml($erdData['tables'] ?? [], $erdData['relationships'] ?? []);
        $filename = "{$slug}-ERD-{$date}.dbml";

        return response($dbml, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    private function buildSql(array $tables): string
    {
        $lines = [
            "-- ERD 자동 생성 — " . now()->format('Y-m-d H:i'),
            "-- SupportWorks 웍스 Agent (T36)",
            "SET FOREIGN_KEY_CHECKS = 0;",
            "",
        ];

        foreach ($tables as $table) {
            $name = $table['name'] ?? '';
            if (!$name) continue;

            $desc = $table['description'] ?? '';
            if ($desc) $lines[] = "-- {$desc}";
            $lines[] = "CREATE TABLE `{$name}` (";

            $colDefs = [];
            foreach ($table['columns'] ?? [] as $col) {
                $def = "    `{$col['name']}` {$col['type']}";
                if (!($col['nullable'] ?? true) || ($col['primary_key'] ?? false)) $def .= ' NOT NULL';
                if ($col['auto_increment'] ?? false) $def .= ' AUTO_INCREMENT';
                if ($col['primary_key']   ?? false) $def .= ' PRIMARY KEY';
                if ($col['unique']        ?? false) $def .= ' UNIQUE';
                if (isset($col['default']) && $col['default'] !== '') $def .= " DEFAULT {$col['default']}";
                if ($col['comment']       ?? '') $def .= " COMMENT '{$col['comment']}'";
                $colDefs[] = $def;
            }

            foreach ($table['foreign_keys'] ?? [] as $fk) {
                $ref    = $fk['references'] ?? [];
                $onDel  = $fk['on_delete'] ?? 'RESTRICT';
                $colDefs[] = "    FOREIGN KEY (`{$fk['column']}`) REFERENCES `{$ref['table']}` (`{$ref['column']}`) ON DELETE {$onDel}";
            }

            foreach ($table['indexes'] ?? [] as $idx) {
                $uniq  = ($idx['unique'] ?? false) ? 'UNIQUE ' : '';
                $cols  = implode('`, `', $idx['columns'] ?? []);
                $colDefs[] = "    {$uniq}INDEX `{$idx['name']}` (`{$cols}`)";
            }

            $lines[] = implode(",\n", $colDefs);
            $lines[] = ");";
            $lines[] = "";
        }

        $lines[] = "SET FOREIGN_KEY_CHECKS = 1;";

        return implode("\n", $lines);
    }

    private function buildDbml(array $tables, array $relationships): string
    {
        $lines = [
            "// ERD 자동 생성 — " . now()->format('Y-m-d H:i'),
            "// Project: SupportWorks 웍스 Agent",
            "",
        ];

        foreach ($tables as $table) {
            $name = $table['name'] ?? '';
            if (!$name) continue;

            if ($table['description'] ?? '') $lines[] = "// {$table['description']}";
            $lines[] = "Table {$name} {";

            foreach ($table['columns'] ?? [] as $col) {
                $flags = [];
                if ($col['primary_key']   ?? false) $flags[] = 'pk';
                if ($col['auto_increment'] ?? false) $flags[] = 'increment';
                if ($col['unique']        ?? false) $flags[] = 'unique';
                if ($col['nullable']      ?? true)  $flags[] = 'null';
                else                                 $flags[] = 'not null';
                if ($col['default'] ?? '') $flags[] = "default: `{$col['default']}`";
                if ($col['comment'] ?? '') $flags[] = "note: '{$col['comment']}'";

                $type  = strtolower(explode(' ', $col['type'] ?? 'varchar')[0]);
                $flag  = $flags ? ' [' . implode(', ', $flags) . ']' : '';
                $lines[] = "    {$col['name']} {$type}{$flag}";
            }

            $lines[] = "}";
            $lines[] = "";
        }

        foreach ($relationships as $rel) {
            $from = $rel['from'] ?? '';
            $to   = $rel['to']   ?? '';
            if (!$from || !$to) continue;
            $lines[] = "Ref: {$from} > {$to}";
        }

        return implode("\n", $lines);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function getArtifact(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::ERD->value)
            ->latest('id')->first();
    }

    private function resolveDevPrepStage(Project $project): AiAgentProjectStage
    {
        return AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::DEV_PREP)
            ->firstOrFail();
    }

    private function authorizeProject(Project $project): void
    {
        $userId = (int) auth()->id();
        if (!ProjectMember::where('project_id', $project->id)->where('user_id', $userId)->exists()
            && $project->created_by !== $userId) {
            abort(403);
        }
    }

    private function sseHeaders(): array
    {
        return [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ];
    }

    private function sseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    private function clearOutputBuffer(): void
    {
        while (ob_get_level() > 0) { ob_end_clean(); }
        ob_implicit_flush(true);
    }
}
