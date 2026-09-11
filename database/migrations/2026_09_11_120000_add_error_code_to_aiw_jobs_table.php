<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 실패 사유를 문자열이 아니라 코드로도 남긴다.
 *
 * error_message 만으로는 화면이 "무엇을 할 수 있는지" 판단할 수 없다. 코드가 있으면
 * 상황에 맞는 복구 버튼(브랜치 없이 재실행, 매핑 수정 등)을 띄울 수 있다.
 * error_detail 에는 그 판단에 필요한 값(걸린 파일 목록, 있는 브랜치 목록)을 담는다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->string('error_code', 40)->nullable()->after('error_message');
            $table->json('error_detail')->nullable()->after('error_code');
        });
    }

    public function down(): void
    {
        Schema::table('aiw_jobs', function (Blueprint $table) {
            $table->dropColumn(['error_code', 'error_detail']);
        });
    }
};
