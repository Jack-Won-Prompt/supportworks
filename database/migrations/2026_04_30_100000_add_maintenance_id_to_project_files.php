<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->unsignedBigInteger('maintenance_id')->nullable()->after('project_id');
            $table->foreign('maintenance_id')->references('id')->on('project_maintenances')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropForeign(['maintenance_id']);
            $table->dropColumn('maintenance_id');
        });
    }
};
