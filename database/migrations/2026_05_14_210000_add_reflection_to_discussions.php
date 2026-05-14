<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->enum('reflection_status', ['pending','reflected','rejected'])
                ->default('pending')
                ->after('status');
            $table->text('reflection_note')->nullable()->after('reflection_status');
            $table->unsignedBigInteger('reflected_planning_doc_id')->nullable()->after('reflection_note');
            $table->unsignedBigInteger('reflection_decided_by')->nullable()->after('reflected_planning_doc_id');
            $table->timestamp('reflection_decided_at')->nullable()->after('reflection_decided_by');

            $table->foreign('reflected_planning_doc_id')->references('id')->on('planning_docs')->nullOnDelete();
            $table->foreign('reflection_decided_by')->references('id')->on('users')->nullOnDelete();
            $table->index('reflection_status');
        });
    }

    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->dropForeign(['reflected_planning_doc_id']);
            $table->dropForeign(['reflection_decided_by']);
            $table->dropIndex(['reflection_status']);
            $table->dropColumn(['reflection_status','reflection_note','reflected_planning_doc_id','reflection_decided_by','reflection_decided_at']);
        });
    }
};
