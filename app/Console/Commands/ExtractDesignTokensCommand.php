<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\Agent\DesignTokenService;
use App\Services\Agent\Figma\Exceptions\FigmaApiException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use Illuminate\Console\Command;

class ExtractDesignTokensCommand extends Command
{
    protected $signature   = 'ai-agent:design:tokens-extract {projectId : 프로젝트 ID} {figmaFileKey : Figma 파일 키 (예: ABC123xyz)}';
    protected $description = 'Figma 파일에서 Design Tokens를 추출하여 저장합니다.';

    public function handle(DesignTokenService $service): int
    {
        $project = Project::find($this->argument('projectId'));
        if (!$project) {
            $this->error("프로젝트 ID [{$this->argument('projectId')}]를 찾을 수 없습니다.");
            return 1;
        }

        // 프로젝트 첫 번째 멤버(매니저 우선)로 실행
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
            $this->info('토큰 추출 중...');
            $result   = $service->extractFromFigma($project, $fileKey, $user);
            $tokenSet = $result['tokenSet'];
            $artifact = $result['artifact'];

            $this->info("✅ 추출 완료!");
            $this->table(
                ['카테고리', '토큰 수'],
                [
                    ['색상',       $tokenSet->getCategoryCount('color')],
                    ['타이포그래피', $tokenSet->getCategoryCount('typography')],
                    ['그림자',      $tokenSet->getCategoryCount('shadow')],
                    ['레이아웃',    $tokenSet->getCategoryCount('layout')],
                    ['합계',        $tokenSet->getTokenCount()],
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
