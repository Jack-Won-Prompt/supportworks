<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mailbox_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('thread_id')->nullable()->index();   // 첫 메일은 자기 id 로 set
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('subject', 300);
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();   // 플레인 텍스트 (검색·미리보기용)
            $table->string('message_id', 255)->unique();        // RFC Message-ID, 자체 생성
            $table->string('in_reply_to', 255)->nullable()->index();
            $table->text('references_chain')->nullable();        // 부모 Message-ID 공백 연결
            $table->boolean('has_attachment')->default(false);
            $table->unsignedInteger('recipient_count')->default(0);
            $table->timestamp('sent_at')->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index('sender_id');
        });

        // FULLTEXT(ngram) 시도 — MariaDB 환경에서는 지원 안 되면 무시 (LIKE 폴백)
        try {
            DB::statement('ALTER TABLE mailbox_messages ADD FULLTEXT ft_subject_body (subject, body_text) WITH PARSER ngram');
        } catch (\Throwable $e) {
            // ngram 미지원 — 추후 필요 시 일반 FULLTEXT 또는 별도 검색 인프라로 보강
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_messages');
    }
};
