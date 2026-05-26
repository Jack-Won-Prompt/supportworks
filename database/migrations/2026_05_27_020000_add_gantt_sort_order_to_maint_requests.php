<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->integer('gantt_sort_order')->nullable()->after('eta')->index();
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $t) {
            $t->dropIndex(['gantt_sort_order']);
            $t->dropColumn('gantt_sort_order');
        });
    }
};
