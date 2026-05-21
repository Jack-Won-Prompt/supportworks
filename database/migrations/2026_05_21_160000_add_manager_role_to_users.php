<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','manager','member','client') NOT NULL DEFAULT 'client'");
    }

    public function down(): void
    {
        // 'manager'였던 사용자들을 'member'로 다운그레이드 후 enum 복원
        DB::table('users')->where('role', 'manager')->update(['role' => 'member']);
        DB::statement("ALTER TABLE users MODIFY role ENUM('admin','member','client') NOT NULL DEFAULT 'client'");
    }
};
