<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_sessions', function (Blueprint $table) {
            $table->string('agent_type', 20)->default('general')->after('prompt_category'); // general|dev|document
            $table->json('dev_settings')->nullable()->after('agent_type');                 // {framework,framework_version,runtime_version,frontend_stack,db_type,db_version}
            $table->string('doc_type', 50)->nullable()->after('dev_settings');             // report|proposal|plan|manual|minutes|email|other
            $table->string('output_filename')->nullable()->after('doc_type');
            $table->string('output_extension', 20)->nullable()->after('output_filename');
        });
    }

    public function down(): void
    {
        Schema::table('ai_sessions', function (Blueprint $table) {
            $table->dropColumn(['agent_type', 'dev_settings', 'doc_type', 'output_filename', 'output_extension']);
        });
    }
};
