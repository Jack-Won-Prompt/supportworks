<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('maintenance_id')->constrained('project_maintenances')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('maintenance_category_id')->nullable();
            $table->foreign('maintenance_category_id')->references('id')->on('maintenance_file_categories')->nullOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path')->default('');
            $table->string('converted_pdf_path')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('description', 255)->nullable();
            $table->string('source_url', 2048)->nullable();
            $table->string('file_type', 20)->nullable();
            $table->string('share_token', 64)->nullable()->unique();
            $table->timestamps();
        });

        // 기존 project_files 에서 SR 파일 데이터 이전
        DB::statement("
            INSERT INTO maintenance_files
                (project_id, maintenance_id, uploaded_by, maintenance_category_id,
                 original_name, stored_name, path, converted_pdf_path,
                 mime_type, size, description, source_url, file_type, share_token,
                 created_at, updated_at)
            SELECT
                project_id, maintenance_id, uploaded_by, maintenance_category_id,
                original_name, stored_name, path, converted_pdf_path,
                mime_type, size, description, source_url, file_type, share_token,
                created_at, updated_at
            FROM project_files
            WHERE maintenance_id IS NOT NULL
        ");

        // 이전 완료 후 project_files 에서 SR 파일 제거
        DB::statement("DELETE FROM project_files WHERE maintenance_id IS NOT NULL");
    }

    public function down(): void
    {
        // 롤백 시 maintenance_files 데이터를 project_files 로 복원
        DB::statement("
            INSERT INTO project_files
                (project_id, maintenance_id, maintenance_category_id,
                 uploaded_by, original_name, stored_name, path, converted_pdf_path,
                 mime_type, size, description, source_url, file_type, share_token,
                 created_at, updated_at)
            SELECT
                project_id, maintenance_id, maintenance_category_id,
                uploaded_by, original_name, stored_name, path, converted_pdf_path,
                mime_type, size, description, source_url, file_type, share_token,
                created_at, updated_at
            FROM maintenance_files
        ");

        Schema::dropIfExists('maintenance_files');
    }
};
