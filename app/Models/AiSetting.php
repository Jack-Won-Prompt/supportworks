<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    protected $fillable = ['anthropic_key', 'openai_key', 'figma_token', 'manus_key', 'manus_endpoint', 'withworks_github_token'];

    public static function current(): self
    {
        // 테이블이 아직 없는 시점(마이그레이션 직전, 신규 환경, 복구 중)에도
        // artisan 부팅이 막히지 않도록 방어. fallback 인스턴스는 저장되지 않으며
        // 모든 키 조회는 anthropicKey() 등의 env() fallback 경로로 떨어진다.
        try {
            return self::firstOrCreate([]);
        } catch (\Throwable) {
            return new self();
        }
    }

    private function safeDecrypt(?string $value): ?string
    {
        if (!$value) return null;
        try { return decrypt($value); } catch (\Throwable) { return null; }
    }

    public function anthropicKey(): ?string
    {
        // env() 는 config:cache 시 작동 안 함 → config() 통해 fallback
        return $this->safeDecrypt($this->anthropic_key) ?? (config('services.anthropic.key') ?: null);
    }

    public function openaiKey(): ?string
    {
        return $this->safeDecrypt($this->openai_key) ?? (config('services.openai.key') ?: null);
    }

    public function figmaToken(): ?string
    {
        return $this->safeDecrypt($this->figma_token) ?? (config('services.figma.token') ?: null);
    }

    public function manusKey(): ?string
    {
        return $this->safeDecrypt($this->manus_key) ?? (config('services.manus.key') ?: null);
    }

    public function manusEndpoint(): string
    {
        return $this->manus_endpoint ?: (env('MANUS_API_ENDPOINT') ?: 'https://api.manus.ai/v2');
    }

    public function withWorksGithubToken(): ?string
    {
        return $this->safeDecrypt($this->withworks_github_token);
    }
}
