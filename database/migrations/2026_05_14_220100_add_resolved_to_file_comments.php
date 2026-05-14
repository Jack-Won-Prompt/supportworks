<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->boolean('resolved')->default(false)->after('content');
            $table->timestamp('resolved_at')->nullable()->after('resolved');
            $table->unsignedBigInteger('resolved_by')->nullable()->after('resolved_at');
            $table->unsignedBigInteger('resolved_at_version')->nullable()->after('resolved_by')
                ->comment('어느 버전에서 반영 완료 처리되었는지');

            $table->foreign('resolved_by')->references('id')->on('users')->nullOnDelete();
            $table->index('resolved');
        });
    }

    public function down(): void
    {
        Schema::table('file_comments', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropIndex(['resolved']);
            $table->dropColumn(['resolved', 'resolved_at', 'resolved_by', 'resolved_at_version']);
        });
    }
};
