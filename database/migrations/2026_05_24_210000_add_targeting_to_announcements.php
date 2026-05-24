<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            // 수신 대상: all | withworks | companies
            $table->string('target_type', 20)->default('all')->after('type');
            // companies 타입일 때 회사 그룹 ID 배열
            $table->json('target_company_group_ids')->nullable()->after('target_type');
            // 이메일 발송 여부 (등록 시 체크)
            $table->boolean('send_email')->default(false)->after('target_company_group_ids');
            // 백그라운드 발송 완료 시각
            $table->timestamp('email_sent_at')->nullable()->after('send_email');
            $table->unsignedInteger('email_sent_count')->default(0)->after('email_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['target_type', 'target_company_group_ids', 'send_email', 'email_sent_at', 'email_sent_count']);
        });
    }
};
