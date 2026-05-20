<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 명세 v11 §1.9 — wb_notification_settings.
 * 사용자별 stage·channel ON/OFF.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('stage_code', 32);
            $table->enum('channel', ['web', 'mobile_push']);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'stage_code', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_notification_settings');
    }
};
