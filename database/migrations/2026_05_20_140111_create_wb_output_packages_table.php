<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §2.9 — wb_output_packages.
 *
 * 검수 OK 후 자동 빌드되는 zip 패키지.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_output_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('wb_tasks')->cascadeOnDelete();
            $table->string('file_path');
            $table->unsignedBigInteger('file_size_bytes')->default(0);
            $table->char('package_hash', 64)->nullable();
            $table->foreignId('included_html_id')->nullable()
                ->constrained('wb_generated_html')->nullOnDelete();
            $table->json('build_metadata')->nullable();
            $table->timestamp('built_at')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'built_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_output_packages');
    }
};
