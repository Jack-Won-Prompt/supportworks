<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->enum('preferred_llm_provider', ['anthropic', 'openai'])->default('anthropic')->after('si_mode_enabled');
            $table->string('preferred_llm_model')->nullable()->after('preferred_llm_provider');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['preferred_llm_provider', 'preferred_llm_model']);
        });
    }
};
