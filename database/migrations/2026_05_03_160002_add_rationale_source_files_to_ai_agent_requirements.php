<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_requirements', function (Blueprint $table) {
            $table->text('rationale')->nullable()->after('description');
            $table->json('source_files')->nullable()->after('rationale');
        });
    }

    public function down(): void
    {
        Schema::table('ai_agent_requirements', function (Blueprint $table) {
            $table->dropColumn(['rationale', 'source_files']);
        });
    }
};
