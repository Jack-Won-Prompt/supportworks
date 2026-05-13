<?php

namespace App\Services\Agent;

use App\Enums\Agent\ArtifactType;
use App\Enums\Agent\StageType;
use App\Models\Agent\AiAgentArtifact;
use App\Models\Agent\AiAgentProjectStage;
use App\Models\Agent\AiAgentScreen;
use App\Models\Project;
use App\Models\User;
use App\Services\Agent\Figma\FigmaClientFactory;
use Illuminate\Support\Str;

class UserManualService
{
    public function __construct(
        private readonly UserManualDataContext $dataContext,
        private readonly FigmaClientFactory   $figmaFactory,
    ) {}

    /**
     * 사용자 매뉴얼 마크다운을 생성하고 artifact를 반환합니다.
     */
    public function generate(Project $project, User $user): AiAgentArtifact
    {
        $context = $this->dataContext->build($project->id);
        $context = $this->enrichWithFigmaImages($context, $user);

        $content = $this->renderTemplate($context);

        return $this->persistArtifact($project, $content, $user);
    }

    /**
     * 매뉴얼 + Figma 이미지를 ZIP으로 패키징합니다.
     */
    public function generatePackage(Project $project, User $user): string
    {
        $artifact = $this->generate($project, $user);

        $slug    = Str::slug($project->name) ?: 'project';
        $date    = now()->format('Y-m-d');
        $dir     = storage_path('app/user-manuals/' . $project->id);
        $zipPath = $dir . "/user-manual-{$slug}-{$date}.zip";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("ZIP 생성 실패: {$zipPath}");
        }

        $zip->addFromString('MANUAL.md', $artifact->content ?? '');

        // Figma 이미지 포함
        $context = $this->dataContext->build($project->id);
        $context = $this->enrichWithFigmaImages($context, $user);

        foreach ($context['screens'] as $screen) {
            if ($screen['figma_image_url']) {
                try {
                    $imageData = file_get_contents($screen['figma_image_url']);
                    if ($imageData !== false) {
                        $zip->addFromString('images/' . $screen['id'] . '.png', $imageData);
                    }
                } catch (\Throwable) {
                    // 이미지 다운로드 실패 시 계속 진행
                }
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * 최신 USER_MANUAL artifact를 로드합니다.
     */
    public function loadExisting(int $projectId): ?AiAgentArtifact
    {
        return AiAgentArtifact::where('project_id', $projectId)
            ->where('type', ArtifactType::USER_MANUAL->value)
            ->latest('created_at')
            ->first();
    }

    /**
     * Markdown을 HTML로 변환합니다 (간단 변환).
     */
    public function toHtml(string $markdown, string $projectName = ''): string
    {
        $title = $projectName ? "{$projectName} 사용자 매뉴얼" : '사용자 매뉴얼';

        $body = $markdown;

        // 이미지
        $body = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1" style="max-width:100%;border-radius:8px;margin:12px 0;">', $body);

        // Headings
        $body = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $body);
        $body = preg_replace('/^## (.+)$/m',  '<h2>$1</h2>', $body);
        $body = preg_replace('/^# (.+)$/m',   '<h1>$1</h1>', $body);

        // Blockquote
        $body = preg_replace('/^> (.+)$/m', '<blockquote>$1</blockquote>', $body);

        // Bold
        $body = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $body);

        // HR
        $body = preg_replace('/^---$/m', '<hr>', $body);

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

        // Italic
        $body = preg_replace('/_(.+?)_/', '<em>$1</em>', $body);

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
                body { font-family: 'Malgun Gothic', '맑은 고딕', sans-serif; max-width: 900px; margin: 0 auto; padding: 32px 24px; color: #1e1b2e; line-height: 1.75; }
                h1 { font-size: 26px; font-weight: 800; border-bottom: 2px solid #7c3aed; padding-bottom: 8px; margin-bottom: 20px; }
                h2 { font-size: 20px; font-weight: 700; margin: 36px 0 12px; color: #2d1f5e; border-left: 4px solid #a78bfa; padding-left: 12px; }
                h3 { font-size: 16px; font-weight: 700; margin: 24px 0 8px; color: #4c1d95; }
                table { width: 100%; border-collapse: collapse; margin: 16px 0; }
                th { background: #f3f0ff; padding: 8px 12px; text-align: left; border: 1px solid #ddd6fe; font-weight: 700; }
                td { padding: 8px 12px; border: 1px solid #e2d9f3; }
                tr:nth-child(even) td { background: #fafbff; }
                blockquote { border-left: 3px solid #a78bfa; margin: 12px 0; padding: 8px 16px; background: #f3f0ff; color: #4c1d95; border-radius: 0 8px 8px 0; }
                ul { padding-left: 24px; }
                li { margin: 4px 0; }
                hr { border: none; border-top: 1.5px dashed #ddd6fe; margin: 28px 0; }
                em { color: #64748b; font-size: 13px; }
                strong { color: #1e1b2e; }
                img { box-shadow: 0 4px 12px rgba(0,0,0,.1); }
                p { margin: 8px 0; }
            </style>
        </head>
        <body>
        {$body}
        </body>
        </html>
        HTML;
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function enrichWithFigmaImages(array $context, User $user): array
    {
        $screensWithFigma = array_filter($context['screens'], fn($s) => $s['is_figma_mapped']);
        if (empty($screensWithFigma)) return $context;

        try {
            $client = $this->figmaFactory->forUser($user);
        } catch (\Throwable) {
            return $context; // Figma 토큰 미설정 시 이미지 없이 진행
        }

        // fileKey별로 그룹화
        $byFile = [];
        foreach ($context['screens'] as $idx => $screen) {
            if (!$screen['is_figma_mapped'] || !$screen['figma_file_key'] || !$screen['figma_frame_id']) {
                continue;
            }
            $byFile[$screen['figma_file_key']][$idx] = $screen['figma_frame_id'];
        }

        // 파일별 일괄 이미지 URL 조회
        $imageUrls = [];
        foreach ($byFile as $fileKey => $frameMap) {
            try {
                $urls = $client->getImages($fileKey, array_values($frameMap), 'png', 1.0);
                foreach ($frameMap as $idx => $nodeId) {
                    $imageUrls[$idx] = $urls[$nodeId] ?? null;
                }
            } catch (\Throwable) {
                // 파일 조회 실패 시 해당 파일 건너뜀
            }
        }

        // context 업데이트
        foreach ($imageUrls as $idx => $url) {
            $context['screens'][$idx]['figma_image_url'] = $url;
        }

        return $context;
    }

    private function renderTemplate(array $context): string
    {
        $templatePath = resource_path('templates/release/user_manual_v1.md.blade.php');

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
            type:      ArtifactType::USER_MANUAL,
            scopeType: 'project',
            scopeId:   $project->id,
            title:     "사용자 매뉴얼 — {$project->name} (" . now()->format('Y-m-d') . ")",
            content:   $content,
            userId:    $user->id,
            meta: [
                'template_version' => 'v1',
                'generated_at'     => now()->toIso8601String(),
            ],
        );
    }
}
