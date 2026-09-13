<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 운영 사이트에서 올라온 에러. **지문 하나에 한 행이다.**
 *
 * 운영에서 에러 하나가 터지면 5분에 수백 건이 온다. 그대로 쌓으면 화면도 못
 * 읽고, 작업 지시를 자동으로 만들기 시작하면 첫날 밤에 수백 개가 생겨 데몬
 * 큐가 막힌다. 그래서 (프로젝트, 지문) 을 유일 키로 두고 같은 것은 세기만 한다.
 *
 * 여기까지가 이 단계의 범위다 — 받아서 묶고 화면에 보이는 것. 작업 지시를
 * 자동으로 만드는 판단은 다음 단계에서 이 표를 읽어 한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aiw_error_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_id')->constrained('aiw_error_sources')->cascadeOnDelete();

            // exception + file + line 을 정규화해 만든 sha256.
            $table->char('fingerprint', 64);

            $table->string('level', 16)->default('error');
            $table->string('exception', 255)->nullable();
            $table->text('message')->nullable();
            $table->string('file', 500)->nullable();
            $table->unsignedInteger('line')->nullable();

            // 어느 화면에서 났는지. 보내는 쪽이 민감한 값을 가려서 보낸다.
            $table->string('url', 1000)->nullable();

            $table->longText('trace')->nullable();
            $table->json('context')->nullable();

            $table->unsignedInteger('count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            /*
             * new      받아 두었고 아직 아무 판단도 하지 않았다
             * ignored  고칠 대상이 아니다(404·봇 스캔 등) 또는 사람이 덮었다
             * queued   작업 지시를 만들었다
             * patching 그 지시가 돌고 있다
             * resolved 고쳐졌다
             * blocked  자동으로 손대면 안 되는 경로다(결제·인증 등). 사람이 본다
             */
            $table->string('status', 16)->default('new');

            $table->foreignId('job_id')->nullable()
                ->constrained('aiw_jobs')->nullOnDelete();

            /*
             * 이 지문으로 작업 지시를 몇 번 만들었는가.
             *
             * 고친 코드가 또 에러를 내면 웹훅 → 새 지시 → 또 에러로 끝없이 돈다.
             * 사람이 없는 시간에 그 고리가 돌면 아무도 멈추지 못한다. 상한을
             * 넘으면 더 만들지 않고 사람을 부른다.
             */
            $table->unsignedTinyInteger('patch_attempts')->default(0);

            $table->timestamps();

            // 묶기를 DB 가 보장한다. 코드가 한 군데서 실수해도 중복이 생기지 않는다.
            $table->unique(['project_id', 'fingerprint']);
            $table->index(['project_id', 'status', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aiw_error_reports');
    }
};
