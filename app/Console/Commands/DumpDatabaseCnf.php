<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 운영 mysqldump 용 임시 .my.cnf 파일 생성. 자격증명을 명령줄에 노출하지 않고
 * config('database.connections.<name>') 을 통해 정상적인 Laravel context 에서 읽어옴.
 *
 * deploy.sh 의 make_mysql_cnf 가 `php -r 'require "config/database.php"'` 패턴을
 * 쓰는데 그 컨텍스트엔 env() 가 미정의라 fatal → 0 byte .my.cnf → mysqldump 백업
 * 0 byte 결과. 이 명령으로 우회.
 *
 * 사용: php artisan db:cnf-dump /tmp/db.cnf
 *      php artisan db:cnf-dump /tmp/db.cnf --connection=mysql
 */
class DumpDatabaseCnf extends Command
{
    protected $signature = 'db:cnf-dump {target} {--connection=supportworks}';
    protected $description = 'Dump DB credentials to a temp .my.cnf (for mysqldump in deploy.sh).';

    public function handle(): int
    {
        $target = $this->argument('target');
        $conn   = (string) $this->option('connection');

        $c = config("database.connections.$conn");
        if (!is_array($c)) {
            $this->error("Unknown DB connection: $conn");
            return 1;
        }

        $body = sprintf(
            "[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n",
            $c['host']     ?? '127.0.0.1',
            $c['port']     ?? 3306,
            $c['username'] ?? '',
            $c['password'] ?? '',
        );

        if (file_put_contents($target, $body) === false) {
            $this->error("Failed to write $target");
            return 2;
        }
        chmod($target, 0600);

        $this->info("wrote $target (connection=$conn, host={$c['host']}, db={$c['database']})");
        return 0;
    }
}
