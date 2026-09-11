<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 작업이 끝나면 커밋·푸시·배포까지 자동으로.
 *
 * 사람이 결과를 보고 누르는 단계를 건너뛴다. 편하지만, 잘못된 변경이 확인 없이
 * 운영까지 간다는 뜻이기도 하다. 그래서 기본값은 꺼짐이고, 실패한 작업에서는
 * 발동하지 않으며, 단계가 하나라도 실패하면 거기서 멈춘다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->boolean('auto_deploy')->default(false)->after('use_branch');
            $table->foreignId('auto_deploy_target_id')->nullable()->after('auto_deploy')
                ->constrained('aiw_deploy_targets')->nullOnDelete();
        });

        Schema::table('aiw_deploys', function (Blueprint $table) {
            // 자동 실행인지 사람이 누른 것인지. 기록을 읽을 때 구분되어야 한다.
            $table->boolean('automatic')->default(false)->after('requested_by');
        });

        Schema::table('aiw_publishes', function (Blueprint $table) {
            $table->boolean('automatic')->default(false)->after('requested_by');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('auto_deploy_target_id');
            $table->dropColumn('auto_deploy');
        });

        Schema::table('aiw_deploys', fn (Blueprint $table) => $table->dropColumn('automatic'));
        Schema::table('aiw_publishes', fn (Blueprint $table) => $table->dropColumn('automatic'));
    }
};
