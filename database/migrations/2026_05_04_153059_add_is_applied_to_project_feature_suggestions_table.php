<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->boolean('is_applied')->default(false)->after('reason');
            $table->timestamp('applied_at')->nullable()->after('is_applied');
        });
    }

    public function down(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->dropColumn(['is_applied', 'applied_at']);
        });
    }
};
