<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mailbox_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('mailbox_messages')->cascadeOnDelete();
            $table->string('original_name', 255);
            $table->string('disk', 32)->default('local');     // 기존 디스크 설정 사용
            $table->string('path', 500);                       // disk 내 상대 경로
            $table->unsignedBigInteger('size')->default(0);    // bytes
            $table->string('mime', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_attachments');
    }
};
