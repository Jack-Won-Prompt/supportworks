<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ai_settings: OpenAI API 키 추가
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->text('openai_key')->nullable()->after('anthropic_key');
        });

        // ai_messages: 응답한 웍스 제공자 기록
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->string('ai_provider', 20)->nullable()->after('js_output');
        });

        // prompt_executions: 응답한 웍스 제공자 기록
        Schema::table('prompt_executions', function (Blueprint $table) {
            $table->string('ai_provider', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('ai_settings', function (Blueprint $table) {
            $table->dropColumn('openai_key');
        });
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropColumn('ai_provider');
        });
        Schema::table('prompt_executions', function (Blueprint $table) {
            $table->dropColumn('ai_provider');
        });
    }
};
