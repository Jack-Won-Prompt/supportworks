<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_annotations', function (Blueprint $table) {
            $table->unsignedSmallInteger('version')->default(1)->after('project_file_id')
                  ->comment('주석이 속한 파일 버전');
            $table->index(['project_file_id', 'version']);
        });

        // 기존 주석은 해당 파일의 현재(최신) 버전으로 매핑 — 버전이 있는 파일만
        DB::statement('
            UPDATE file_annotations fa
            JOIN (
                SELECT project_file_id, MAX(version) AS mx
                FROM file_versions
                GROUP BY project_file_id
            ) fv ON fv.project_file_id = fa.project_file_id
            SET fa.version = fv.mx
        ');
    }

    public function down(): void
    {
        Schema::table('file_annotations', function (Blueprint $table) {
            $table->dropIndex(['project_file_id', 'version']);
            $table->dropColumn('version');
        });
    }
};
