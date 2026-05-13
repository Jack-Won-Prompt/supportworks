<?php

namespace App\Jobs;

use App\Events\AnalysisStatusUpdated;
use App\Models\AnalysisSession;
use App\Services\Analysis\AnalysisService;
use App\Services\Analysis\FileExtractor\FileExtractorFactory;
use App\Services\Analysis\Llm\LlmClientFactory;
use App\Services\Analysis\Validators\AiOutputValidator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnalyzeRequirementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(public readonly int $sessionId) {}

    public function handle(): void
    {
        $session = AnalysisSession::findOrFail($this->sessionId);

        $service = new AnalysisService(
            new FileExtractorFactory(),
            new LlmClientFactory(),
            new AiOutputValidator(),
        );

        try {
            $service->run($session);
        } catch (\Throwable) {
            // AnalysisService already marked the session as 'failed' with error_message.
            // Swallow here so the exception doesn't bubble up to the HTTP response
            // when running in sync queue mode.
        } finally {
            $session->refresh();
            try {
                broadcast(new AnalysisStatusUpdated($session));
            } catch (\Throwable) {}
        }
    }

    public function failed(\Throwable $exception): void
    {
        $session = AnalysisSession::find($this->sessionId);
        if ($session) {
            $session->update([
                'status'        => 'failed',
                'error_message' => $exception->getMessage(),
                'completed_at'  => now(),
            ]);
            broadcast(new AnalysisStatusUpdated($session));
        }
    }
}
