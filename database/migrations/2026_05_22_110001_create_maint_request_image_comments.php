<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_request_image_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maint_request_id')->constrained('maint_requests')->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['maint_request_id', 'image_url'], 'mr_img_cmt_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_request_image_comments');
    }
};
