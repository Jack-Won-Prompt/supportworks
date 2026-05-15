<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverable_file_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')
                ->constrained('deliverables')
                ->cascadeOnDelete();
            $table->foreignId('project_file_id')
                ->constrained('project_files')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('file_version');
            $table->string('lang', 4)->default('ko');
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['deliverable_id', 'created_at'], 'dlv_freg_idx');
            $table->index('project_file_id', 'dlv_freg_pf_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverable_file_registrations');
    }
};
