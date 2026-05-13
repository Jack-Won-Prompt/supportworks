<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Project;
use App\Models\User;

class DeployGuideService
{
    public function __construct(
        private readonly DeployGuideDataContext $dataContext,
    ) {}

    /**
     * 배포 가이드를 생성하고 DEPLOY_GUIDE artifact를 반환합니다.
     */
    public function generate(Project $project, User $user): AiAgentArtifact
    {
        $context = $this->dataContext->build($project->id);
        $content = $this->renderTemplate($context);

        return $this->persistArtifact($project, $content, $user);
    }

    /**
     * 최신 DEPLOY_GUIDE artifact를 로드합니다.
     */
    public function loadExisting(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::DEPLOY_GUIDE->value)
            ->latest('created_at')
            ->first();
    }

    /**
     * Markdown 원문을 HTML로 변환합니다 (기본 변환).
     */
    public function toHtml(string $markdown): string
    {
        // Headings
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $markdown);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

        // Code blocks
        $html = preg_replace('/```(\w*)\n(.*?)```/s', '<pre><code class="language-$1">$2</code></pre>', $html);

        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);

        // Bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

        // Tables (basic)
        $html = preg_replace_callback('/(\|.+\|\n)+/', function ($m) {
            $rows   = array_filter(explode("\n", trim($m[0])));
            $output = '<table class="table table-sm">';
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
        }, $html);

        // Checkboxes
        $html = preg_replace('/^- \[ \] (.+)$/m', '<li><input type="checkbox" disabled> $1</li>', $html);
        $html = preg_replace('/^- \[x\] (.+)$/m', '<li><input type="checkbox" checked disabled> $1</li>', $html);

        // Lists
        $html = preg_replace('/^- (.+)$/m', '<li>$1</li>', $html);
        $html = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $html);

        // Blockquote
        $html = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $html);

        // Horizontal rule
        $html = preg_replace('/^---$/m', '<hr>', $html);

        // Paragraphs (simple: double newlines)
        $html = preg_replace('/\n{2,}/', '</p><p>', $html);
        $html = '<p>' . $html . '</p>';

        return $html;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function renderTemplate(array $context): string
    {
        $templatePath = resource_path('templates/release/deploy_guide_v1.md.blade.php');

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
            type:      ArtifactType::DEPLOY_GUIDE,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "배포 가이드 — {$project->name} (" . now()->format('Y-m-d') . ")",
            content:   $content,
            userId:    $user->id,
            meta: [
                'template_version' => 'v1',
                'generated_at'     => now()->toIso8601String(),
            ],
        );
    }
}
