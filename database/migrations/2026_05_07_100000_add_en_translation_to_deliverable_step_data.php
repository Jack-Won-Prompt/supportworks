<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliverable_step_data', function (Blueprint $table) {
            $table->longText('en_value')->nullable()->after('value');
            $table->string('en_hash', 64)->nullable()->after('en_value');
        });
    }

    public function down(): void
    {
        Schema::table('deliverable_step_data', function (Blueprint $table) {
            $table->dropColumn(['en_value', 'en_hash']);
        });
    }
};
