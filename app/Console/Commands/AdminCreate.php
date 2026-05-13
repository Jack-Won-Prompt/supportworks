<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class AdminCreate extends Command
{
    protected $signature = 'admin:create
                            {--name= : 표시 이름}
                            {--login_id= : 로그인 아이디}
                            {--email= : 이메일}
                            {--password= : 비밀번호}
                            {--role=super_admin : 역할 (super_admin/admin/operator/support_agent)}';

    protected $description = '관리자 계정을 직접 생성합니다 (초기 super_admin 설정용)';

    public function handle(): int
    {
        $this->info('SupportWorks 관리자 계정 생성');
        $this->line('');

        $name     = $this->option('name')     ?? $this->ask('표시 이름');
        $loginId  = $this->option('login_id') ?? $this->ask('로그인 아이디 (영문/숫자/언더스코어)');
        $email    = $this->option('email')    ?? $this->ask('이메일');
        $password = $this->option('password') ?? $this->secret('비밀번호 (8자 이상)');
        $role     = $this->option('role');

        // 유효성 검사
        if (strlen($loginId) < 4 || !preg_match('/^[a-zA-Z0-9_]+$/', $loginId)) {
            $this->error('아이디는 영문, 숫자, 언더스코어만 허용하며 4자 이상이어야 합니다.');
            return 1;
        }

        if (AdminUser::where('login_id', $loginId)->exists()) {
            $this->error("'{$loginId}' 아이디는 이미 사용 중입니다.");
            return 1;
        }

        if (AdminUser::where('email', $email)->exists()) {
            $this->error("'{$email}' 이메일은 이미 사용 중입니다.");
            return 1;
        }

        if (strlen($password) < 8) {
            $this->error('비밀번호는 8자 이상이어야 합니다.');
            return 1;
        }

        if (!in_array($role, ['super_admin', 'admin', 'operator', 'support_agent'])) {
            $this->error('역할은 super_admin, admin, operator, support_agent 중 하나여야 합니다.');
            return 1;
        }

        $this->line('');
        $this->table(['항목', '값'], [
            ['이름',     $name],
            ['아이디',   $loginId],
            ['이메일',   $email],
            ['역할',     $role],
        ]);

        if (!$this->confirm('위 정보로 계정을 생성하시겠습니까?', true)) {
            $this->warn('취소되었습니다.');
            return 0;
        }

        $admin = AdminUser::create([
            'name'        => $name,
            'login_id'    => $loginId,
            'email'       => $email,
            'password'    => Hash::make($password),
            'role'        => $role,
            'status'      => 'active',
            'accepted_at' => now(),
        ]);

        $this->info('');
        $this->info("계정이 생성되었습니다. (ID: {$admin->id})");
        $this->line("로그인 아이디: {$loginId}");
        $this->line("역할: {$role}");

        return 0;
    }
}
