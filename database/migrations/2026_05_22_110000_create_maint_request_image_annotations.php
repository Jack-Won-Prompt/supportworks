<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_request_image_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maint_request_id')->constrained('maint_requests')->cascadeOnDelete();
            $table->string('image_url', 500);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('shape', 16); // rect / circle / line / number / text
            $table->string('color', 16)->default('#ef4444');
            $table->json('payload'); // { x, y, w, h, text?, num? } (이미지 좌표 비율 0~1)
            $table->timestamps();

            $table->index(['maint_request_id', 'image_url'], 'mr_img_ann_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_request_image_annotations');
    }
};
