<?php

namespace App\Console\Commands;

use App\Models\Agent\AiAgentArtifact;
use App\Services\Agent\AsIsAnalysisAiService;
use Illuminate\Console\Command;

class TestAsIsAnalysisCommand extends Command
{
    protected $signature   = 'ai-agent:test-as-is-analysis {analysisId : AiAgentArtifact ID}';
    protected $description = 'AS-IS 분석을 CLI에서 직접 실행하여 결과를 출력합니다 (디버깅용).';

    public function handle(AsIsAnalysisAiService $service): int
    {
        $id       = (int) $this->argument('analysisId');
        $artifact = AiAgentArtifact::find($id);

        if (!$artifact) {
            $this->error("AiAgentArtifact #{$id} 를 찾을 수 없습니다.");
            return self::FAILURE;
        }

        $artifact->load('files');
        $files    = $artifact->files;
        $fileInfo = $files->map(fn($f) => "[{$f->file_type}] {$f->file_name} ({$f->parse_status})")->implode(', ');

        $this->info("산출물: #{$artifact->id} — {$artifact->scope_label}");
        $this->info("파일 ({$files->count()}개): {$fileInfo}");
        $this->newLine();

        $parsedCount = $files->where('parse_status', 'completed')->count();
        if ($parsedCount === 0) {
            $this->error('파싱 완료된 파일이 없습니다.');
            return self::FAILURE;
        }

        $userId = (int) (\App\Models\User::first()?->id ?? 1);
        $this->info("사용자 ID {$userId} 로 분석 시작...");
        $this->newLine();

        $startTime = microtime(true);

        try {
            $result  = $service->analyze($artifact, $userId);
            $elapsed = round(microtime(true) - $startTime, 2);

            $this->info("✓ 분석 완료 ({$elapsed}초)");
            $this->newLine();

            $this->line('<fg=cyan>== 현황 요약 ==</>');
            $this->line($result['summary'] ?? '(없음)');
            $this->newLine();

            $issues = $result['issues'] ?? [];
            $this->line("<fg=cyan>== 이슈 ({$issues} 건) ==</>");
            foreach ($issues as $i => $issue) {
                $severity = match ($issue['severity'] ?? '') {
                    'high'   => '<fg=red>HIGH</>',
                    'medium' => '<fg=yellow>MED</>',
                    default  => '<fg=gray>LOW</>',
                };
                $this->line("  [{$severity}] [{$issue['category']}] {$issue['title']}");
                $this->line("       {$issue['description']}");
            }
            $this->newLine();

            $this->line('<fg=cyan>== 전체 JSON ==</>');
            $this->line(json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $elapsed = round(microtime(true) - $startTime, 2);
            $this->error("분석 실패 ({$elapsed}초): " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
