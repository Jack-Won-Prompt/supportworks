<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('memo_shares', function (Blueprint $table) {
            $table->boolean('is_pinned')->default(false)->after('shared_to');
        });
    }

    public function down(): void
    {
        Schema::table('memo_shares', function (Blueprint $table) {
            $table->dropColumn('is_pinned');
        });
    }
};
