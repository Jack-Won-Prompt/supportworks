<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropForeign(['project_file_id']);
        });

        Schema::table('file_annotations', function (Blueprint $table) {
            $table->dropForeign(['project_file_id']);
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->foreign('project_file_id')->references('id')->on('project_files')->cascadeOnDelete();
        });

        Schema::table('file_annotations', function (Blueprint $table) {
            $table->foreign('project_file_id')->references('id')->on('project_files')->cascadeOnDelete();
        });
    }
};
