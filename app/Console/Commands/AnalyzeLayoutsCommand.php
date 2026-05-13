<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\Agent\LayoutSpecService;
use App\Services\Agent\Figma\Exceptions\FigmaApiException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Console\Command;

class AnalyzeLayoutsCommand extends Command
{
    protected $signature   = 'ai-agent:design:layouts-analyze {projectId : 프로젝트 ID} {figmaFileKey : Figma 파일 키 (예: ABC123xyz)}';
    protected $description = 'Figma 파일에서 표준 레이아웃 패턴을 분석하여 저장합니다.';

    public function handle(LayoutSpecService $service): int
    {
        $project = Project::find($this->argument('projectId'));
        if (!$project) {
            $this->error("프로젝트 ID [{$this->argument('projectId')}]를 찾을 수 없습니다.");
            return 1;
        }

        $user = $project->members()
            ->wherePivot('role', 'manager')
            ->first()
            ?? $project->members()->first();

        if (!$user) {
            $this->error("프로젝트 멤버가 없습니다.");
            return 1;
        }

        $fileKey = $this->argument('figmaFileKey');

        $this->info("프로젝트: {$project->name}");
        $this->info("실행 계정: {$user->name} ({$user->email})");
        $this->info("Figma 파일 키: {$fileKey}");
        $this->newLine();

        try {
            $this->info('레이아웃 분석 중...');
            $result   = $service->extractFromFigma($project, $fileKey, $user);
            $artifact = $result['artifact'];
            $specSet  = $result['specSet'];
            $stats    = $specSet->getStats();

            $this->info("✅ 분석 완료!");
            $this->table(
                ['항목', '개수'],
                [
                    ['분석 프레임',     $stats['total_frames_analyzed']],
                    ['식별된 표준',     $stats['standard_layouts_identified']],
                    ['비표준 프레임',   $stats['non_standard_frames']],
                ]
            );

            foreach ($specSet->getStandardLayouts() as $key => $layout) {
                $this->line("  · {$layout['name']} — {$layout['usage_count']}개 ({$layout['usage_percent']}%)");
            }

            $this->newLine();
            $this->info("산출물 ID: {$artifact->id} (버전 {$artifact->version})");
            return 0;

        } catch (FigmaTokenNotConfiguredException $e) {
            $this->error("❌ PAT 미설정: " . $e->getMessage());
            return 1;
        } catch (FigmaApiException $e) {
            $this->error("❌ Figma API 오류: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ 오류: " . $e->getMessage());
            return 1;
        }
    }
}
