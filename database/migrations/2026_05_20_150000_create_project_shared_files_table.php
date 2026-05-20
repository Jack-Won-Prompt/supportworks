<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 프로젝트 ↔ 공유폴더 파일 링크 (pivot).
 *
 * 프로젝트 멤버가 회사 공유폴더의 파일을 프로젝트에 참조 가능 (링크만 — 파일 복제 X).
 * 같은 (project_id, shared_file_id) 조합은 한 번만.
 * 공유폴더의 원본 파일이 삭제되면 cascade 로 링크도 사라짐.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('project_shared_files', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('project_id');
            $t->unsignedBigInteger('shared_file_id');
            $t->unsignedBigInteger('attached_by')->nullable(); // 첨부한 사용자 (감사용)
            $t->timestamps();

            $t->unique(['project_id', 'shared_file_id']);
            $t->index('project_id');
            $t->index('shared_file_id');

            $t->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $t->foreign('shared_file_id')->references('id')->on('shared_files')->cascadeOnDelete();
            // attached_by 는 users.id 인데 운영 환경에 따라 외래키 위반 가능성 — soft FK 로 둠
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_shared_files');
    }
};