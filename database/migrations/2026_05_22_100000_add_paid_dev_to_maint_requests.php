<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->boolean('paid_dev_enabled')->default(false)->after('completed_at');
            $table->unsignedSmallInteger('paid_dev_days')->nullable()->after('paid_dev_enabled');
            $table->unsignedBigInteger('paid_dev_cost')->nullable()->after('paid_dev_days');
            $table->text('paid_dev_description')->nullable()->after('paid_dev_cost');
            $table->timestamp('paid_dev_sent_at')->nullable()->after('paid_dev_description');
        });
    }

    public function down(): void
    {
        Schema::table('maint_requests', function (Blueprint $table) {
            $table->dropColumn(['paid_dev_enabled', 'paid_dev_days', 'paid_dev_cost', 'paid_dev_description', 'paid_dev_sent_at']);
        });
    }
};
