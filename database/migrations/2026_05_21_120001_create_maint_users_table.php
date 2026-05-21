<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100)->comment('담당자 이름');
            $t->enum('team', ['colo', 'withworks'])->comment('colo=콜로담당자, withworks=위드웍스 개발자');
            $t->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Laravel 로그인 사용자 매핑 (withworks 담당자 등)');
            $t->boolean('is_active')->default(true);
            $t->timestamps();

            $t->unique(['team', 'name'], 'uk_maint_users_team_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_users');
    }
};
