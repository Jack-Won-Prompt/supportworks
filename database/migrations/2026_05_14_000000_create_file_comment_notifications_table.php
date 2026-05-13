<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_comment_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained('project_files')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('알림 수신 대상(파일 업로더)');
            $table->date('sent_date')->comment('당일 중복 발송 방지용 날짜 키');
            $table->boolean('email_sent')->default(false);
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['project_file_id', 'sent_date'], 'fcn_file_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_comment_notifications');
    }
};
