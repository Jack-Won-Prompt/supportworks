<?php

namespace App\Services\PromptRefiner;

use Illuminate\Support\Facades\Cache;

class SystemPromptProvider
{
    public function get(): string
    {
        return Cache::rememberForever('prompt_refiner_system_v1', function () {
            $path = resource_path('prompts/refiner_v1.md');
            return file_get_contents($path);
        });
    }

    public function flush(): void
    {
        Cache::forget('prompt_refiner_system_v1');
    }
}
