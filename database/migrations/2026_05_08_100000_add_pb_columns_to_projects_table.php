<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('pb_framework')->nullable()->after('description');
            $table->string('pb_framework_version')->nullable()->after('pb_framework');
            $table->enum('pb_language', ['typescript', 'javascript'])->nullable()->after('pb_framework_version');
            $table->string('pb_styling')->nullable()->after('pb_language');
            $table->string('pb_state_management')->nullable()->after('pb_styling');
            $table->string('pb_data_fetching')->nullable()->after('pb_state_management');
            $table->enum('pb_auto_update_mode', [
                'disabled', 'suggest_only', 'auto_high_confidence', 'fully_automated',
            ])->default('suggest_only')->after('pb_data_fetching');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'pb_framework', 'pb_framework_version', 'pb_language',
                'pb_styling', 'pb_state_management', 'pb_data_fetching', 'pb_auto_update_mode',
            ]);
        });
    }
};
