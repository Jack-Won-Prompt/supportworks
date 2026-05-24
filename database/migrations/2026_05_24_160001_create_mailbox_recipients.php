<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mailbox_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('mailbox_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('email', 255);
            $table->string('name', 150)->nullable();
            $table->enum('type', ['to', 'cc', 'bcc'])->default('to');
            // 사용자별 폴더 — 외부 수신자는 user_id NULL 이고 folder 무의미 (검색·통계용)
            $table->enum('folder', ['inbox', 'sent', 'trash', 'spam'])->default('inbox');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'folder', 'is_read']);
            $table->index(['message_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_recipients');
    }
};
