<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->foreignId('applied_to_plan_id')
                  ->nullable()
                  ->after('applied_to_plan_at')
                  ->constrained('planning_docs')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('requirements', function (Blueprint $table) {
            $table->dropForeign(['applied_to_plan_id']);
            $table->dropColumn('applied_to_plan_id');
        });
    }
};
