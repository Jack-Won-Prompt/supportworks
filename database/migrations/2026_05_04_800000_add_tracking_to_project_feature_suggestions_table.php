<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->foreignId('requirement_id')
                  ->nullable()->after('applied_at')
                  ->constrained('requirements')->nullOnDelete();
            $table->foreignId('planning_doc_id')
                  ->nullable()->after('requirement_id')
                  ->constrained('planning_docs')->nullOnDelete();
            $table->text('inserted_markdown')->nullable()->after('planning_doc_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->dropForeign(['requirement_id']);
            $table->dropForeign(['planning_doc_id']);
            $table->dropColumn(['requirement_id', 'planning_doc_id', 'inserted_markdown']);
        });
    }
};
