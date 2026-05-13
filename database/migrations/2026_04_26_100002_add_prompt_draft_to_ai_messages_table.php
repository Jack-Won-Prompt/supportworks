<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->json('prompt_draft')->nullable()->after('content');       // {goal,role,input,constraints,output_format}
            $table->boolean('prompt_approved')->default(false)->after('prompt_draft');
        });
    }

    public function down(): void
    {
        Schema::table('ai_messages', function (Blueprint $table) {
            $table->dropColumn(['prompt_draft', 'prompt_approved']);
        });
    }
};
