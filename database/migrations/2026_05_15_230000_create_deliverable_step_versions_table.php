<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverable_step_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')
                ->constrained('deliverables')
                ->cascadeOnDelete();
            $table->integer('step_order');
            $table->unsignedSmallInteger('version_no');
            $table->longText('snapshot_fields')->nullable();
            $table->longText('snapshot_tools')->nullable();
            $table->text('change_note')->nullable();
            $table->foreignId('created_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['deliverable_id', 'step_order', 'version_no'],
                'dlv_step_ver_unique'
            );
            $table->index(['deliverable_id', 'step_order'], 'dlv_step_ver_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverable_step_versions');
    }
};
