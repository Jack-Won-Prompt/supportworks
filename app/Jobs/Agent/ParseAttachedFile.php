<?php

namespace App\Jobs\Agent;

use App\Models\Agent\AiAgentArtifactFile;
use App\Services\Agent\AsIsAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseAttachedFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly AiAgentArtifactFile $file) {}

    public function handle(AsIsAnalysisService $service): void
    {
        $service->parseFile($this->file);
    }
}
