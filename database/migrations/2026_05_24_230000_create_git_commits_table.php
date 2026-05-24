<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('git_commits', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('withworks');         // 다중 저장소 대비
            $table->string('branch', 100)->default('origin/master');
            $table->string('sha', 40)->unique();
            $table->string('author_name', 100);
            $table->string('author_email', 200)->nullable();
            $table->foreignId('user_id')->nullable()                    // author_email → users.email 매핑
                  ->constrained('users')->nullOnDelete();
            $table->dateTime('committed_at');
            $table->text('subject')->nullable();                        // 첫 줄 (커밋 제목)
            $table->longText('body')->nullable();                       // 본문 (선택)
            $table->unsignedInteger('files_changed')->default(0);
            $table->unsignedInteger('insertions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->timestamps();

            $table->index(['source', 'committed_at']);
            $table->index(['user_id', 'committed_at']);
            $table->index('author_email');
        });

        Schema::create('git_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 30)->default('withworks');
            $table->string('branch', 100)->default('origin/master');
            $table->dateTime('since')->nullable();
            $table->dateTime('until')->nullable();
            $table->unsignedInteger('inserted')->default(0);
            $table->unsignedInteger('skipped')->default(0);
            $table->string('status', 20)->default('success');           // success | failed
            $table->text('error_message')->nullable();
            $table->foreignId('triggered_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('git_sync_runs');
        Schema::dropIfExists('git_commits');
    }
};
