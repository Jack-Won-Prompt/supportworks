<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->softDeletes()->after('inserted_markdown');
        });
    }

    public function down(): void
    {
        Schema::table('project_feature_suggestions', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
