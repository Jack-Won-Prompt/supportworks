<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stage_code', 32);
            $table->boolean('via_web')->default(true);
            $table->boolean('via_mobile_push')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'stage_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_notification_settings');
    }
};
