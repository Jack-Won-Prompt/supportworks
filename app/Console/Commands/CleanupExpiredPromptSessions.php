<?php

namespace App\Console\Commands;

use App\Models\PromptSession;
use Illuminate\Console\Command;

class CleanupExpiredPromptSessions extends Command
{
    protected $signature   = 'prompt-refiner:cleanup';
    protected $description = '만료된 프롬프트 정제 세션을 expired 상태로 변경합니다.';

    public function handle(): int
    {
        $count = PromptSession::where('status', 'in_progress')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("정리 완료: {$count}개 세션이 만료 처리되었습니다.");

        return Command::SUCCESS;
    }
}
