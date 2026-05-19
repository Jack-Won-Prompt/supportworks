<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 공유폴더 카테고리 (회사 단위)
        Schema::create('shared_file_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_group_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('color', 7)->default('#6366f1');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('company_group_id');
        });

        // 공유폴더 파일 (회사 단위)
        Schema::create('shared_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()
                  ->constrained('shared_file_categories')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 150)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['company_group_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_files');
        Schema::dropIfExists('shared_file_categories');
    }
};
