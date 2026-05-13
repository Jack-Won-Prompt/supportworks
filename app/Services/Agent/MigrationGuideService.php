<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;

class MigrationGuideService
{
    public function __construct(
        private readonly MigrationGuideDataContext $dataContext,
    ) {}

    /**
     * 마이그레이션 가이드를 생성하고 artifact를 반환합니다.
     */
    public function generate(Project $project, User $user): AiAgentArtifact
    {
        $context = $this->dataContext->build($project->id);
        $content = $this->renderTemplate($context);

        return $this->persistArtifact($project, $content, $user);
    }

    /**
     * 최신 MIGRATION_GUIDE artifact를 로드합니다.
     */
    public function loadExisting(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::MIGRATION_GUIDE->value)
            ->latest('created_at')
            ->first();
    }

    /**
     * Markdown을 HTML로 변환합니다.
     */
    public function toHtml(string $markdown, string $projectName = ''): string
    {
        $title = $projectName ? "{$projectName} 마이그레이션 가이드" : '마이그레이션 가이드';
        $body  = $markdown;

        // Escape
        $body = htmlspecialchars($body, ENT_NOQUOTES, 'UTF-8');

        // Code blocks
        $body = preg_replace('/```(\w*)\n([\s\S]*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $body);

        // Headings
        $body = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $body);
        $body = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $body);
        $body = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $body);

        // Blockquote
        $body = preg_replace('/^&gt; (.+)$/m', '<blockquote>$1</blockquote>', $body);

        // HR
        $body = preg_replace('/^---$/m', '<hr>', $body);

        // Bold
        $body = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body);

        // Inline code
        $body = preg_replace('/`([^`]+)`/', '<code>$1</code>', $body);

        // Tables
        $body = preg_replace_callback('/((?:\|.+\|\n)+)/', function ($m) {
            $rows   = array_filter(explode("\n", trim($m[0])));
            $output = '<table>';
            $first  = true;
            foreach ($rows as $row) {
                if (preg_match('/^\|[-| ]+\|$/', $row)) continue;
                $cells = array_map('trim', explode('|', trim($row, '|')));
                if ($first) {
                    $output .= '<thead><tr>' . implode('', array_map(fn($c) => "<th>{$c}</th>", $cells)) . '</tr></thead><tbody>';
                    $first = false;
                } else {
                    $output .= '<tr>' . implode('', array_map(fn($c) => "<td>{$c}</td>", $cells)) . '</tr>';
                }
            }
            $output .= '</tbody></table>';
            return $output;
        }, $body);

        // Lists
        $body = preg_replace('/^- (.+)$/m', '<li>$1</li>', $body);

        // Paragraphs
        $body = preg_replace('/\n{2,}/', '</p><p>', $body);
        $body = '<p>' . $body . '</p>';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$title}</title>
            <style>
                body { font-family: 'Malgun Gothic','맑은 고딕',sans-serif; max-width:900px; margin:0 auto; padding:32px 24px; color:#1e1b2e; line-height:1.75; }
                h1 { font-size:26px; font-weight:800; border-bottom:2px solid #7c3aed; padding-bottom:8px; margin-bottom:20px; }
                h2 { font-size:20px; font-weight:700; margin:36px 0 12px; color:#2d1f5e; border-left:4px solid #a78bfa; padding-left:12px; }
                h3 { font-size:16px; font-weight:700; margin:24px 0 8px; color:#4c1d95; }
                pre { background:#1e1b2e; color:#e2d9f3; border-radius:10px; padding:14px 18px; overflow-x:auto; font-size:13px; margin:10px 0; }
                code { font-family:monospace; font-size:13px; background:#ede8ff; color:#4c1d95; padding:1px 5px; border-radius:4px; }
                pre code { background:none; color:inherit; padding:0; }
                table { width:100%; border-collapse:collapse; margin:16px 0; }
                th { background:#f3f0ff; padding:8px 12px; text-align:left; border:1px solid #ddd6fe; font-weight:700; }
                td { padding:8px 12px; border:1px solid #e2d9f3; }
                tr:nth-child(even) td { background:#fafbff; }
                blockquote { border-left:3px solid #f59e0b; margin:10px 0; padding:8px 16px; background:#fffbeb; color:#92400e; border-radius:0 8px 8px 0; }
                ul { padding-left:24px; } li { margin:4px 0; }
                hr { border:none; border-top:1.5px dashed #ddd6fe; margin:28px 0; }
                strong { color:#1e1b2e; } em { color:#64748b; font-size:13px; }
                p { margin:8px 0; }
            </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function renderTemplate(array $context): string
    {
        $templatePath = resource_path('templates/release/migration_guide_v1.md.blade.php');

        return view()->file($templatePath, $context)->render();
    }

    private function persistArtifact(Project $project, string $content, User $user): AiAgentArtifact
    {
        $stage = AiAgentProjectStage::where('project_id', $project->id)
            ->where('type', StageType::RELEASE->value)
            ->first();

        return AiAgentArtifact::upsertForScope(
            projectId: $project->id,
            stageId:   $stage?->id ?? 0,
            type:      ArtifactType::MIGRATION_GUIDE,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "마이그레이션 가이드 — {$project->name} (" . now()->format('Y-m-d') . ")",
            content:   $content,
            userId:    $user->id,
            meta: [
                'template_version' => 'v1',
                'mode'             => 'fresh_install',
                'generated_at'     => now()->toIso8601String(),
            ],
        );
    }
}
