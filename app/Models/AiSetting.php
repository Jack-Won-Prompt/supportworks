<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = ['anthropic_key', 'openai_key', 'figma_token', 'manus_key', 'manus_endpoint'];

    public static function current(): self
    {
        return self::firstOrCreate([]);
    }

    private function safeDecrypt(?string $value): ?string
    {
        if (!$value) return null;
        try { return decrypt($value); } catch (\Throwable) { return null; }
    }

    public function anthropicKey(): ?string
    {
        return $this->safeDecrypt($this->anthropic_key) ?? (env('ANTHROPIC_API_KEY') ?: null);
    }

    public function openaiKey(): ?string
    {
        return $this->safeDecrypt($this->openai_key) ?? (env('OPENAI_API_KEY') ?: null);
    }

    public function figmaToken(): ?string
    {
        return $this->safeDecrypt($this->figma_token) ?? (env('FIGMA_TOKEN') ?: null);
    }

    public function manusKey(): ?string
    {
        return $this->safeDecrypt($this->manus_key) ?? (env('MANUS_API_KEY') ?: null);
    }

    public function manusEndpoint(): string
    {
        return $this->manus_endpoint ?: (env('MANUS_API_ENDPOINT') ?: 'https://api.manus.ai/v2');
    }
}
