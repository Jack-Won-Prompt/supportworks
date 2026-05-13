<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_agent_artifacts', function (Blueprint $table) {
            $table->enum('scope_type', ['project', 'screen'])->default('project')->after('stage_id');
            $table->unsignedBigInteger('scope_id')->default(0)->after('scope_type');

            $table->index(['project_id', 'scope_type', 'scope_id', 'type'], 'ai_artifacts_scope_idx');
        });

        // 기존 레코드 백필: scope_id = project_id
        DB::statement('UPDATE ai_agent_artifacts SET scope_id = project_id WHERE scope_type = \'project\'');
    }

    public function down(): void
    {
        Schema::table('ai_agent_artifacts', function (Blueprint $table) {
            $table->dropIndex('ai_artifacts_scope_idx');
            $table->dropColumn(['scope_type', 'scope_id']);
        });
    }
};
