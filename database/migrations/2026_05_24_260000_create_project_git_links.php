<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_git_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            // 현재 유일하게 허용되는 source/repo. 컬럼은 유지하되 컨트롤러에서 강제 검증.
            $table->string('source', 30)->default('withworks');
            $table->string('repo', 200)->default('dhlogitsticsPlatform/withworks');
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // 프로젝트당 1개 연결만 (현재는 withworks 만 가능)
            $table->unique(['project_id', 'source'], 'pgl_project_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_git_links');
    }
};
