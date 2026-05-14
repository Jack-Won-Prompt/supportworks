<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE planning_doc_inputs MODIFY COLUMN input_type ENUM('text','memo','requirement','file','discussion') NOT NULL");

        Schema::table('planning_doc_inputs', function (Blueprint $table) {
            $table->unsignedBigInteger('source_discussion_id')->nullable()->after('created_by');
            $table->foreign('source_discussion_id')
                ->references('id')->on('discussions')
                ->nullOnDelete();
            $table->index('source_discussion_id');
        });
    }

    public function down(): void
    {
        Schema::table('planning_doc_inputs', function (Blueprint $table) {
            $table->dropForeign(['source_discussion_id']);
            $table->dropIndex(['source_discussion_id']);
            $table->dropColumn('source_discussion_id');
        });

        DB::statement("ALTER TABLE planning_doc_inputs MODIFY COLUMN input_type ENUM('text','memo','requirement','file') NOT NULL");
    }
};
