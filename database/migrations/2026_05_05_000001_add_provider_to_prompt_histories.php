<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->string('provider_used', 20)->nullable()->after('llm_model');
            $table->string('fallback_reason', 200)->nullable()->after('provider_used');
            $table->index('provider_used');
        });
    }

    public function down(): void
    {
        Schema::table('prompt_histories', function (Blueprint $table) {
            $table->dropIndex(['provider_used']);
            $table->dropColumn(['provider_used', 'fallback_reason']);
        });
    }
};
