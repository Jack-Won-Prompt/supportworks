<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SR 비고에 답글(트리 1단계) 기능 추가.
 *   parent_id = NULL  → 최상위 비고
 *   parent_id = N     → 비고 #N 에 대한 답글
 * 부모 삭제 시 답글은 FK CASCADE 로 함께 삭제.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_request_notes', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('note_type');
            $table->foreign('parent_id')
                ->references('id')->on('maint_request_notes')
                ->onDelete('cascade');
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('maint_request_notes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
