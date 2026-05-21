<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maint_menus', function (Blueprint $t) {
            $t->id();
            $t->string('name', 255)->comment('메뉴명 (엑셀 [메뉴] 컬럼 원본)');
            $t->integer('request_cnt')->default(0)->comment('연결된 요청 건수 (집계용)');
            $t->timestamps();

            $t->unique('name', 'uk_maint_menus_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maint_menus');
    }
};
