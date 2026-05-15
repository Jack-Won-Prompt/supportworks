<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            $table->foreignId('source_message_id')
                ->nullable()
                ->after('project_id')
                ->constrained('messages')
                ->nullOnDelete();
            $table->json('source_context')->nullable()->after('source_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('action_items', function (Blueprint $table) {
            $table->dropForeign(['source_message_id']);
            $table->dropColumn(['source_message_id', 'source_context']);
        });
    }
};
