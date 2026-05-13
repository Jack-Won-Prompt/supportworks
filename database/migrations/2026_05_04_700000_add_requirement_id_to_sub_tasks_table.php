<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sub_tasks', function (Blueprint $table) {
            $table->foreignId('requirement_id')
                  ->nullable()
                  ->after('source_plan_id')
                  ->constrained('requirements')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sub_tasks', function (Blueprint $table) {
            $table->dropForeign(['requirement_id']);
            $table->dropColumn('requirement_id');
        });
    }
};
