<?php

namespace App\Services\WorksPrompt;

use Illuminate\Support\Facades\Cache;

class SystemPromptProvider
{
    private const CACHE_KEY = 'works_prompt_system_v1';

    public function get(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $path = resource_path('prompts/works_prompt_v1.md');
            return file_get_contents($path);
        });
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
