<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_files', function (Blueprint $table) {
            $table->dropForeign(['maintenance_id']);
            $table->unsignedBigInteger('maintenance_id')->nullable()->change();
            $table->foreign('maintenance_id')->references('id')->on('project_maintenances')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_files', function (Blueprint $table) {
            $table->dropForeign(['maintenance_id']);
            $table->unsignedBigInteger('maintenance_id')->nullable(false)->change();
            $table->foreign('maintenance_id')->references('id')->on('project_maintenances')->cascadeOnDelete();
        });
    }
};
