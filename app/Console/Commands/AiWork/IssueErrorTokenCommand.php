<?php

namespace App\Console\Commands\AiWork;

use App\Models\AiWork\AiwErrorSource;
use App\Models\Project;
use Illuminate\Console\Command;

/**
 * 오류 수집 토큰을 발급하고, 곧바로 대상 사이트의 .env 에 써 넣는다.
 *
 * 화면에서 발급하면 원문이 사람의 눈과 클립보드를 거쳐 사이트까지 간다.
 * 운영 사이트가 이 서버에 함께 있으므로, 그 경로를 아예 없앨 수 있다 —
 * 만들어서 바로 옆 폴더의 .env 에 적고 끝낸다. 원문은 화면에도, 로그에도,
 * 사람의 손에도 남지 않는다.
 *
 * 사용:
 *   php artisan aiw:issue-error-token korsafety --env=/home/ubuntu/www/korsafety/.env
 */
class IssueErrorTokenCommand extends Command
{
    protected $signature = 'aiw:issue-error-token
        {project : 프로젝트 이름 또는 id}
        {--env= : 토큰을 써 넣을 .env 경로}
        {--name= : 출처 이름(기본은 프로젝트 이름)}';

    protected $description = '오류 수집 토큰을 발급해 사이트 .env 에 직접 써 넣는다';

    public function handle(): int
    {
        $key = (string) $this->argument('project');

        $project = is_numeric($key)
            ? Project::find((int) $key)
            : Project::where('name', $key)->first();

        if (! $project) {
            $this->error("프로젝트를 찾지 못했습니다: {$key}");

            return self::FAILURE;
        }

        $envPath = (string) $this->option('env');

        if ($envPath === '' || ! is_file($envPath)) {
            $this->error(".env 경로가 잘못되었습니다: {$envPath}");

            return self::FAILURE;
        }

        $raw = AiwErrorSource::generateToken();

        $source = AiwErrorSource::create([
            'project_id' => $project->id,
            'name'       => $this->option('name') ?: $project->name,
            'token_hash' => AiwErrorSource::hashToken($raw),
            'enabled'    => true,
        ]);

        $written = $this->writeEnv($envPath, [
            'SW_ERROR_URL'   => url('/api/aiw/errors'),
            'SW_ERROR_TOKEN' => $raw,
        ]);

        // 원문은 여기서 사라진다. 이 뒤로는 해시만 남는다.
        unset($raw);

        $this->info("출처 #{$source->id} ({$source->name}) 을(를) 만들었습니다.");
        $this->line("  {$envPath} 에 ".implode(', ', $written).' 을(를) 적었습니다.');
        $this->line('  사이트에서 설정 캐시를 쓰고 있다면 config:clear 가 필요합니다.');

        return self::SUCCESS;
    }

    /**
     * 있는 키는 바꾸고, 없으면 끝에 붙인다.
     *
     * @param  array<string, string>  $values
     * @return array<int, string>  적은 키 이름
     */
    private function writeEnv(string $path, array $values): array
    {
        $body  = (string) file_get_contents($path);
        $lines = preg_split('/\r\n|\r|\n/', $body) ?: [];
        $done  = [];

        foreach ($values as $key => $value) {
            $replaced = false;

            foreach ($lines as $i => $line) {
                if (preg_match('/^\s*'.preg_quote($key, '/').'\s*=/', $line)) {
                    $lines[$i] = $key.'='.$value;
                    $replaced  = true;
                    break;
                }
            }

            if (! $replaced) {
                $lines[] = $key.'='.$value;
            }

            $done[] = $key;
        }

        file_put_contents($path, implode("\n", $lines)."\n");

        return $done;
    }
}
