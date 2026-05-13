<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_screens', function (Blueprint $table) {
            $table->id();
            $table->string('screen_key')->unique();
            $table->string('name');
            $table->string('blade_path');
            $table->string('url_pattern')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('maintenance_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained('maintenance_screens')->cascadeOnDelete();
            $table->unsignedInteger('version_no')->default(1);
            $table->string('change_summary');
            $table->json('files');
            $table->json('prompt')->nullable();
            $table->text('user_request')->nullable();
            $table->string('status', 20)->default('draft'); // draft|applied|rolled_back
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamps();
            $table->foreign('applied_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_versions');
        Schema::dropIfExists('maintenance_screens');
    }
};
