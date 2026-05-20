<?php

namespace App\Jobs\WorksBuilder;

use App\Models\WorksBuilder\Task;
use App\Services\WorksBuilder\Notification\NotificationDispatcher;
use App\Services\WorksBuilder\Packaging\OutputPackageBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 명세 v11 §1.8 — 검수 OK 후 zip 패키지 자동 빌드 Job.
 */
class BuildOutputPackageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1;

    public function __construct(public int $taskId) {}

    public function handle(OutputPackageBuilder $builder, NotificationDispatcher $notifier): void
    {
        $task = Task::find($this->taskId);
        if (!$task) return;

        try {
            $builder->buildFor($task);
            $notifier->dispatchStage($task, 'package_ready');
        } catch (\Throwable $e) {
            Log::error('[WB] BuildOutputPackageJob 실패', [
                'task_id' => $task->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
