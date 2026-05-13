<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\ComponentSpecService;
use App\Services\Agent\Figma\Exceptions\FigmaApiException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Console\Command;

class ExtractComponentsCommand extends Command
{
    protected $signature   = 'ai-agent:design:components-extract {projectId : 프로젝트 ID} {figmaFileKey : Figma 파일 키 (예: ABC123xyz)}';
    protected $description = 'Figma 파일에서 Component 명세를 추출하여 저장합니다.';

    public function handle(ComponentSpecService $service): int
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
            $this->info('컴포넌트 명세 추출 중...');
            $result   = $service->extractFromFigma($project, $fileKey, $user);
            $artifact = $result['artifact'];
            $specSet  = $result['specSet'];
            $stats    = $specSet->getStats();

            $this->info("✅ 추출 완료!");
            $this->table(
                ['항목', '개수'],
                [
                    ['총 컴포넌트',     $stats['total_components']],
                    ['ComponentSet',     $stats['component_sets']],
                    ['단일 컴포넌트',   $stats['single_components']],
                    ['총 Variants',      $stats['total_variants']],
                ]
            );

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
