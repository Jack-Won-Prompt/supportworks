<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error')->index();   // error, warning, critical
            $table->string('exception', 255)->nullable();              // exception class
            $table->text('message');
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->longText('trace')->nullable();
            $table->json('context')->nullable();                       // url, method, user_id, ip
            $table->boolean('is_resolved')->default(false)->index();
            $table->unsignedBigInteger('resolved_by')->nullable();     // admin_users.id
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_error_logs');
    }
};
