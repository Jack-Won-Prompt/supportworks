<?php

namespace App\Console\Commands;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Console\Command;

class FcmTest extends Command
{
    protected $signature = 'fcm:test {email}
        {--title=SupportWorks 테스트}
        {--body=FCM 푸시 알림 테스트입니다.}';

    protected $description = '특정 사용자(이메일)에게 FCM 테스트 푸시를 발송';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user  = User::where('email', $email)->first();

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