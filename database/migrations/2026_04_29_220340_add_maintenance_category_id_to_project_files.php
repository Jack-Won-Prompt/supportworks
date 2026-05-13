<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->unsignedBigInteger('maintenance_category_id')->nullable()->after('category_id');
            $table->foreign('maintenance_category_id')
                  ->references('id')->on('maintenance_file_categories')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropForeign(['maintenance_category_id']);
            $table->dropColumn('maintenance_category_id');
        });
    }
};
