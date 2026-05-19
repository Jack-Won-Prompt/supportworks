<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class FcmTest extends Command
{
    protected $signature = 'fcm:test {email?}
        {--token= : FCM 토큰을 직접 지정해 발송 (이메일 대신)}
        {--title=SupportWorks 테스트}
        {--body=FCM 푸시 알림 테스트입니다.}';

    protected $description = '특정 사용자(이메일) 또는 토큰으로 FCM 테스트 푸시를 발송';

    public function handle(): int
    {
        // --token 으로 직접 발송 (DB 등록 없이 즉시 테스트)
        if ($this->option('token')) {
            $token = trim($this->option('token'));
            $this->info('토큰 직접 발송 중...');
            FcmService::sendToTokens(
                [$token],
                $this->option('title'),
                $this->option('body'),
                ['type' => 'test'],
            );
            $this->info('발송 요청 완료. 기기에서 알림을 확인하세요.');
            $this->line('(실패 시 storage/logs/laravel.log 의 FCM 항목 확인)');
            return self::SUCCESS;
        }

        $email = $this->argument('email');
        if (!$email) {
            $this->error('이메일 또는 --token 중 하나는 필요합니다.');
            return self::FAILURE;
        }
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("사용자를 찾을 수 없습니다: {$email}");
            return self::FAILURE;
        }

        $tokens = DeviceToken::where('user_id', $user->id)->get();
        $this->line("대상: {$user->name} (#{$user->id})");
        $this->line("등록된 기기 토큰: {$tokens->count()}개");

        if ($tokens->isEmpty()) {
            $this->warn('등록된 FCM 토큰이 없습니다.');
            $this->warn('→ 해당 사용자가 모바일 앱에서 로그인해야 device_tokens 에 토큰이 등록됩니다.');
            return self::FAILURE;
        }

        foreach ($tokens as $t) {
            $this->line("  - [{$t->platform}] " . substr($t->token, 0, 24) . '...');
        }

        $this->info('FCM 발송 중...');
        FcmService::notifyUser(
            $user->id,
            $this->option('title'),
            $this->option('body'),
            ['type' => 'test'],
        );

        $this->info('발송 요청 완료. 기기에서 알림을 확인하세요.');
        $this->line('(실패 시 storage/logs/laravel.log 의 FCM 항목 확인)');
        return self::SUCCESS;
    }
}