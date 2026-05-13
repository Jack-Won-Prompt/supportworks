<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_annotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_file_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page')->nullable()->comment('슬라이드/페이지 번호');
            $table->string('type', 20)->comment('number|rect|circle|line|text');
            $table->json('data')->comment('좌표·크기·색상·텍스트 (% 단위)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_annotations');
    }
};
