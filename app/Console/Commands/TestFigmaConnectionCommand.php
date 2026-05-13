<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Agent\Figma\Exceptions\FigmaApiException;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use App\Services\Agent\Figma\FigmaClientFactory;
use App\Services\Agent\Figma\FigmaUrlParser;
use Illuminate\Console\Command;

class TestFigmaConnectionCommand extends Command
{
    protected $signature   = 'ai-agent:figma-test {userId : 사용자 ID} {figmaUrl? : Figma 파일 URL (선택)}';
    protected $description = 'Figma API 연결을 테스트합니다.';

    public function handle(FigmaClientFactory $factory): int
    {
        $user = User::find($this->argument('userId'));
        if (!$user) {
            $this->error("사용자 ID [{$this->argument('userId')}]를 찾을 수 없습니다.");
            return 1;
        }

        $this->info("사용자: {$user->name} ({$user->email})");
        $this->newLine();

        try {
            $client = $factory->forUser($user);

            // 1. 토큰 검증
            $this->info('1. 토큰 검증 중...');
            $valid = $client->validateToken();
            $this->info($valid ? '   ✅ 유효' : '   ❌ 무효');
            if (!$valid) return 1;

            // 2. /me 조회
            $this->info('2. 사용자 정보 조회...');
            $me = $client->getMe();
            $this->info("   ✅ {$me['email']} ({$me['handle']})");

            // 3. 파일 조회 (선택)
            if ($figmaUrl = $this->argument('figmaUrl')) {
                $fileKey = FigmaUrlParser::parseFileKey($figmaUrl);
                if (!$fileKey) {
                    $this->error("   ❌ 유효하지 않은 Figma URL: {$figmaUrl}");
                    return 1;
                }

                $this->info("3. 파일 조회: {$fileKey}");
                $file = $client->getFile($fileKey);
                $this->info("   ✅ 파일명: {$file->name}");
                $this->info("   - 마지막 수정: {$file->lastModified}");
                $this->info("   - 버전: {$file->version}");
                $this->info("   - 컴포넌트: {$file->getComponentCount()}개");
                $this->info("   - 스타일: {$file->getStyleCount()}개");
                $this->info("   - 프레임: " . count($file->getFrames()) . "개");
            }

            $this->newLine();
            $this->info('✅ Figma API 연결 테스트 성공');
            return 0;

        } catch (FigmaTokenNotConfiguredException $e) {
            $this->error("❌ 토큰 미설정: " . $e->getMessage());
            return 1;
        } catch (FigmaApiException $e) {
            $this->error("❌ API 오류 [{$e->getCode()}]: " . $e->getMessage());
            return 1;
        } catch (\Exception $e) {
            $this->error("❌ 오류: " . $e->getMessage());
            return 1;
        }
    }
}
