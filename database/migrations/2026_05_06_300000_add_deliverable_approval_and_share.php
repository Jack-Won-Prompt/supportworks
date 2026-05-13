<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 산출물 단계 승인 요청 이력
        Schema::create('deliverable_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->integer('step_order');
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->timestamp('responded_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['deliverable_id', 'step_order']);
        });

        // 산출물 공개 링크 공유 토큰
        Schema::table('deliverables', function (Blueprint $table) {
            $table->string('share_token', 64)->nullable()->unique()->after('created_by');
        });
    }

    public function down(): void
    {
        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropColumn('share_token');
        });
        Schema::dropIfExists('deliverable_approvals');
    }
};
