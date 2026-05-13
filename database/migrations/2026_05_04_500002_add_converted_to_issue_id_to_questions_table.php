<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('converted_to_issue_id')->nullable()->constrained('issues')->nullOnDelete()->after('is_private');
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['converted_to_issue_id']);
            $table->dropColumn('converted_to_issue_id');
        });
    }
};
